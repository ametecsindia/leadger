<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\ReportsController;
use App\Models\Recording;
use App\Models\User;
use App\Services\CallRecordingService;
use App\Services\GcsBucket;
use App\Services\RecordingAudio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Call recordings in the Google Cloud Storage bucket: copy (recordings:to-bucket), private 10-minute links for
 * Play / Download, bulk ZIP from the bucket, removal with Delete, and "off" without settings. Google is faked
 * (Http::fake) with a test service-account key generated here; no real bucket or credentials are used.
 */
class RecordingsBucketTest extends TestCase
{
    use DatabaseTransactions;

    private string $dir;
    private string $publicKey;
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable(GcsBucket::TABLE)) {
            // creating a table commits in MySQL: do it outside the test transaction
            DB::rollBack();
            GcsBucket::ensureTable();
            DB::beginTransaction();
        }

        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lg_gcs_' . uniqid();
        mkdir($this->dir);
        $cnf = $this->dir . DIRECTORY_SEPARATOR . 'openssl.cnf';
        file_put_contents($cnf, "[req]\ndistinguished_name=dn\n[dn]\n");
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $cnf]);
        openssl_pkey_export($key, $pem, null, ['config' => $cnf]);
        $this->publicKey = openssl_pkey_get_details($key)['key'];
        file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'key.json', json_encode([
            'type' => 'service_account', 'client_email' => 'lg-test@project.iam.gserviceaccount.com', 'private_key' => $pem,
        ]));
        config(['services.gcs.bucket' => 'lg-test-bucket', 'services.gcs.key_file' => $this->dir . DIRECTORY_SEPARATOR . 'key.json',
            'services.gcs.prefix' => 'recordings']);
        Cache::flush();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'storage.googleapis.com/upload/*' => fn (HttpRequest $r) => Http::response(['size' => (string)strlen($r->body())]),
            'metadata.google.internal/*' => Http::response(['access_token' => 'server-token', 'expires_in' => 3599]),
            'storage.googleapis.com/storage/v1/*' => fn (HttpRequest $r) => match (true) {
                $r->method() === 'DELETE' => Http::response('', 204),
                $r->hasHeader('Range') => Http::response('UCK', 206, ['Content-Type' => 'audio/wav', 'Content-Range' => 'bytes 1-3/12']),
                default => Http::response('BUCKET-AUDIO', 200, ['Content-Type' => 'audio/wav']),
            },
            '*' => Http::response('not found', 404),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (array_merge($this->files, glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: []) as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    /** A standalone recording with a real (temporary) audio file on the server. */
    private function recording(): Recording
    {
        $dir = storage_path('app/recordings');
        @mkdir($dir, 0755, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'test_bucket_' . uniqid() . '.wav';
        file_put_contents($file, 'RIFF' . str_repeat("\1", 4096));
        $this->files[] = $file;
        return Recording::forceCreate([
            'provider_call_id' => 'TESTBKT_' . uniqid(), 'call_id' => 'TESTBKT', 'source_code' => 3, 'source_type' => 'Recording',
            'phone_number' => '9876500000', 'duration' => 20, 'file_path' => $file, 'download_status' => 'completed',
            'call_status' => 'completed', 'call_date' => Carbon::now(),
        ]);
    }

    private function copy(Recording $rec): object
    {
        Artisan::call('recordings:to-bucket', ['--hours' => 1]);
        $row = DB::table(GcsBucket::TABLE)->where(['source_code' => 3, 'log_id' => $rec->id])->first();
        $this->assertNotNull($row, Artisan::output());
        return $row;
    }

    private function download(Recording $rec, bool $play)
    {
        Auth::login(User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail());
        request()->query->replace($play ? ['play' => 1] : []);
        return app(ReportsController::class)->downloadRecordings($rec->id, 3);
    }

    public function test_a_recording_is_copied_to_the_bucket_once_and_unchanged()
    {
        $rec = $this->recording();
        $row = $this->copy($rec);

        $this->assertSame('done', $row->status);
        $this->assertSame(filesize($rec->file_path), (int)$row->size);
        // recordings/<month>/<CRM ID>/<call date>/<type>_<id>_<file>
        $this->assertMatchesRegularExpression('#^recordings/' . now()->format('Y-m') . '/[A-Za-z0-9_-]+/' . now()->format('Y-m-d') . '/recording_' . $rec->id . '_test_bucket_[0-9a-f]+\.wav$#', $row->object);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/upload/storage/v1/b/lg-test-bucket/o?')
            && str_contains($r->url(), 'ifGenerationMatch=0') && $r->hasHeader('Authorization', 'Bearer test-token')
            && $r->body() === file_get_contents($rec->file_path));

        // the original file and row are untouched; a second run does not upload it again
        $this->assertFileExists($rec->file_path);
        $this->assertSame('completed', $rec->fresh()->download_status);
        Http::fake();   // record new requests only
        Artisan::call('recordings:to-bucket', ['--hours' => 1]);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'name=' . rawurlencode($row->object)));
    }

    public function test_download_and_play_go_to_a_private_10_minute_link_with_a_valid_signature()
    {
        $rec = $this->recording();
        $row = $this->copy($rec);

        foreach ([false, true] as $play) {
            $url = $this->download($rec, $play)->getTargetUrl();
            $this->assertStringStartsWith('https://storage.googleapis.com/lg-test-bucket/' . $row->object . '?', $url);
            $this->assertStringContainsString('X-Goog-Expires=600', $url);
            $this->assertSame(!$play, str_contains($url, 'response-content-disposition=attachment'));

            // GOOG4-RSA-SHA256: the signature verifies against the service account's public key
            [$path, $query] = explode('?', substr($url, strlen('https://storage.googleapis.com')), 2);
            [$canonicalQuery, $sig] = explode('&X-Goog-Signature=', $query);
            parse_str($canonicalQuery, $q);
            $scope = substr($q['X-Goog-Date'], 0, 8) . '/auto/storage/goog4_request';
            $this->assertSame('lg-test@project.iam.gserviceaccount.com/' . $scope, $q['X-Goog-Credential']);
            $canonical = "GET\n{$path}\n{$canonicalQuery}\nhost:storage.googleapis.com\n\nhost\nUNSIGNED-PAYLOAD";
            $toSign = "GOOG4-RSA-SHA256\n{$q['X-Goog-Date']}\n{$scope}\n" . hash('sha256', $canonical);
            $this->assertSame(1, openssl_verify($toSign, hex2bin($sig), $this->publicKey, OPENSSL_ALGO_SHA256));
        }
    }

    public function test_bulk_zip_reads_the_bucket_copy_when_the_server_file_is_gone()
    {
        $rec = $this->recording();
        $this->copy($rec);
        @unlink($rec->file_path);

        $audio = RecordingAudio::find(3, $rec->id);
        $this->assertSame('BUCKET-AUDIO', $audio['contents'] ?? null);
    }

    public function test_deleting_a_recording_removes_its_bucket_copy_and_link()
    {
        $rec = $this->recording();
        $row = $this->copy($rec);

        $result = CallRecordingService::deleteRecording(3, $rec->id, 1);
        $this->assertTrue($result['ok']);
        $this->assertNull(DB::table(GcsBucket::TABLE)->where(['source_code' => 3, 'log_id' => $rec->id])->first());
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/o/' . rawurlencode($row->object)));
        $this->assertNull(GcsBucket::redirect(3, $rec->id, false));
    }

    public function test_the_servers_own_account_works_without_a_key_file()
    {
        config(['services.gcs.key_file' => '', 'services.gcs.server_account' => true]);
        $rec = $this->recording();
        $row = $this->copy($rec);
        $this->assertSame('done', $row->status);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'metadata.google.internal') && $r->hasHeader('Metadata-Flavor', 'Google'));
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/upload/storage/v1/b/lg-test-bucket/o?') && $r->hasHeader('Authorization', 'Bearer server-token'));

        // no key: the audio comes through LeadGER (after the access check), not as a signed link
        $res = $this->download($rec, false);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $res);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringStartsWith('attachment; filename="recording_' . $rec->id . '_', $res->headers->get('Content-Disposition'));
        ob_start(); $res->sendContent(); $this->assertSame('BUCKET-AUDIO', ob_get_clean());

        // the player asks for a part: Range is passed on and answered with 206
        request()->headers->set('Range', 'bytes=1-3');
        $res = $this->download($rec, true);
        $this->assertSame(206, $res->getStatusCode());
        $this->assertSame('bytes 1-3/12', $res->headers->get('Content-Range'));
        $this->assertStringStartsWith('inline', $res->headers->get('Content-Disposition'));
        ob_start(); $res->sendContent(); $this->assertSame('UCK', ob_get_clean());
        request()->headers->remove('Range');
    }

    public function test_without_bucket_settings_nothing_changes()
    {
        config(['services.gcs.bucket' => '']);
        $rec = $this->recording();

        $this->assertSame(0, Artisan::call('recordings:to-bucket'));
        $this->assertStringContainsString('nothing to do', Artisan::output());
        $this->assertNull(GcsBucket::redirect(3, $rec->id, false));
        $this->assertSame(realpath($rec->file_path), realpath($this->download($rec, false)->getFile()->getPathname()));
    }
}
