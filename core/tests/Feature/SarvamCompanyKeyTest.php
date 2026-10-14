<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\SarvamKeyController;
use App\Jobs\TranscribeRecordingJob;
use App\Models\CompanySarvamSetting;
use App\Models\TranscriptionJob;
use App\Models\User;
use App\Services\SarvamAiService;
use App\Services\SarvamKeyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Per-company Sarvam AI key. Each company has its own database; here two in-memory databases stand for company A
 * and company B. Sarvam is faked: only the GOOD_* keys are accepted (the status probe answers 400 for a valid key,
 * 403 otherwise), so no real key is used.
 */
class SarvamCompanyKeyTest extends TestCase
{
    private const KEY_A = 'GOOD_A_sk_1234567890abcdefA1a2';
    private const KEY_B = 'GOOD_B_sk_0987654321fedcbaB9c3';
    private const BAD_KEY = 'BAD_sk_ffffffffffffffffffff0000';

    private User $admin;
    private User $telecaller;
    private string $original;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->telecaller = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->admin->load('permissionsGroup');
        $this->telecaller->load('permissionsGroup');
        $this->original = DB::getDefaultConnection();
        config(['services.sarvam.allow_global_key' => false]);

        Http::fake(function (HttpRequest $request) {
            $key = $request->header('api-subscription-key')[0] ?? '';
            return str_starts_with($key, 'GOOD_')
                ? Http::response(['error' => ['message' => 'job_id must match format']], 400)
                : Http::response(['error' => ['message' => 'Invalid or missing authentication credentials']], 403);
        });
        Queue::fake();
        Auth::login($this->admin);
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->original);
        parent::tearDown();
    }

    /** Switch to company $name's own database (created on first use). */
    private function company(string $name): void
    {
        $conn = 'company_' . $name;
        if (!config("database.connections.$conn")) {
            config(["database.connections.$conn" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'leadger_']]);
        }
        DB::setDefaultConnection($conn);
        if (!Schema::hasTable('company_sarvam_settings')) {
            (require base_path('database/migrations/2026_10_14_150000_create_company_sarvam_settings_table.php'))->up();
            Schema::create('transcription_jobs', function (Blueprint $t) {
                $t->id(); $t->string('status', 30); $t->text('error_message')->nullable(); $t->integer('source_code')->default(0);
                $t->string('call_id')->nullable(); $t->string('recording_id')->nullable(); $t->integer('duration_seconds')->default(0);
                $t->timestamp('completed_at')->nullable(); $t->timestamps();
            });
            Schema::create('call_ai_analyses', function (Blueprint $t) {
                $t->id(); $t->integer('company_id')->default(1); $t->string('call_id')->nullable(); $t->string('recording_id')->nullable();
                $t->string('status', 30)->default('pending'); $t->text('error_message')->nullable(); $t->timestamps();
            });
        }
    }

    private function endpoint(string $method, array $data = [], ?User $as = null)
    {
        Auth::login($as ?? $this->admin);
        $req = Request::create('/x', $method === 'destroy' ? 'DELETE' : 'POST', $data);
        app()->instance('request', $req);
        $c = app(SarvamKeyController::class);
        $res = match ($method) {
            'show' => $c->show(),
            'test' => $c->test($req),
            'save' => $c->save($req),
            'destroy' => $c->destroy(),
        };
        return [$res->getStatusCode(), $res->getData(true), $res->getContent()];
    }

    private function save(string $key = null, array $extra = []): array
    {
        return $this->endpoint('save', array_merge(['language' => 'unknown', 'scope' => 'connected', 'auto_transcribe' => 1], $key ? ['key' => $key] : [], $extra));
    }

    private function keyUsedBySarvamService(): ?string
    {
        $p = new \ReflectionProperty(SarvamAiService::class, 'apiKey');
        $p->setAccessible(true);
        return $p->getValue(new SarvamAiService());
    }

    public function test_only_admins_can_use_the_endpoints()
    {
        $this->company('a');
        foreach (['show', 'test', 'save', 'destroy'] as $m) {
            [$code] = $this->endpoint($m, ['key' => self::KEY_A, 'language' => 'unknown', 'scope' => 'all', 'auto_transcribe' => 1], $this->telecaller);
            $this->assertSame(403, $code, "$m must be refused for a telecaller");
        }
        $this->assertFalse(CompanySarvamSetting::current()->hasKey(), 'nothing saved by a non-admin');
    }

    public function test_an_invalid_key_is_refused_and_nothing_is_saved()
    {
        $this->company('a');
        [$code, $json] = $this->endpoint('test', ['key' => self::BAD_KEY]);
        $this->assertSame(422, $code);
        $this->assertSame('Key is invalid', $json['message']);
        [$code, $json] = $this->save(self::BAD_KEY);
        $this->assertSame(422, $code);
        $this->assertSame('Key is invalid', $json['errors']['key']);
        $this->assertFalse(CompanySarvamSetting::current()->hasKey());
        $this->assertSame(422, $this->save('short key')[0], 'format: no spaces, 20-200 characters');
        $this->assertSame(422, $this->save(self::KEY_A, ['language' => 'xx-XX'])[0], 'only the listed languages');
    }

    public function test_a_valid_key_is_saved_encrypted_and_never_returned()
    {
        $this->company('a');
        [$code, $json, $body] = $this->save(self::KEY_A);
        $this->assertSame(200, $code);
        $this->assertSame('connected', $json['status']);
        $this->assertSame(substr(self::KEY_A, -4), $json['last4']);
        $this->assertNotNull($json['verified_at']);
        $this->assertStringNotContainsString(self::KEY_A, $body, 'the key is not in the response');
        [, , $showBody] = $this->endpoint('show');
        $this->assertStringNotContainsString(self::KEY_A, $showBody);

        $raw = DB::table('company_sarvam_settings')->value('sarvam_api_key');
        $this->assertNotSame(self::KEY_A, $raw, 'stored encrypted, not in plain text');
        $this->assertStringNotContainsString(self::KEY_A, (string)$raw);
        $this->assertArrayNotHasKey('sarvam_api_key', CompanySarvamSetting::current()->toArray(), 'hidden from serialization');
        $this->assertSame(self::KEY_A, SarvamKeyService::currentKey());

        $audit = DB::table('sarvam_key_audit_logs')->pluck('action')->all();
        $this->assertContains('added', $audit);
        $this->assertSame(0, DB::table('sarvam_key_audit_logs')->where('detail', 'like', '%' . self::KEY_A . '%')->count(), 'audit never holds the key');
    }

    public function test_company_a_and_company_b_keep_their_own_keys()
    {
        $this->company('a');
        $this->save(self::KEY_A);

        $this->company('b');
        [, $json] = $this->endpoint('show');
        $this->assertSame('not_configured', $json['status'], 'company B does not see company A\'s key');
        $this->assertNull(SarvamKeyService::currentKey(), 'company B cannot use company A\'s key');
        $this->assertNull($this->keyUsedBySarvamService());
        $this->save(self::KEY_B);
        $this->assertSame(self::KEY_B, $this->keyUsedBySarvamService(), 'company B transcribes with its own key');

        $this->company('a');
        $this->assertSame(self::KEY_A, SarvamKeyService::currentKey(), 'company B did not overwrite company A');
        $this->assertSame(self::KEY_A, $this->keyUsedBySarvamService(), 'company A transcribes with its own key');
        [, $json] = $this->endpoint('show');
        $this->assertSame(substr(self::KEY_A, -4), $json['last4']);
    }

    public function test_removing_the_key_stops_transcription()
    {
        $this->company('a');
        $this->save(self::KEY_A);
        $this->assertTrue(SarvamKeyService::autoTranscribeOn());
        [$code, $json] = $this->endpoint('destroy');
        $this->assertSame(200, $code);
        $this->assertSame('not_configured', $json['status']);
        $this->assertFalse($json['auto_transcribe']);
        $this->assertNull(SarvamKeyService::currentKey());
        $this->assertFalse((new SarvamAiService())->isConfigured(), 'Sarvam is not called without a key');
        $this->assertFalse(SarvamKeyService::autoTranscribeOn());
    }

    public function test_scope_none_means_no_automatic_transcription()
    {
        $this->company('a');
        $this->save(self::KEY_A, ['scope' => 'none']);
        $this->assertFalse(SarvamKeyService::autoTranscribeOn());
        $this->save(null, ['scope' => 'connected']);
        $this->assertTrue(SarvamKeyService::autoTranscribeOn());
        $this->assertTrue(SarvamKeyService::inAutoScope(25), 'connected call');
        $this->assertFalse(SarvamKeyService::inAutoScope(0), 'not connected: not in "Connected calls only"');
    }

    public function test_a_rejected_key_pauses_jobs_and_a_valid_key_resumes_them()
    {
        $this->company('a');
        $this->save(self::KEY_A);
        $job = TranscriptionJob::forceCreate(['status' => 'QUEUED', 'source_code' => 0, 'call_id' => '501', 'recording_id' => 'call_501']);

        // a job got 401/403 from Sarvam
        SarvamKeyService::markInvalid('test');
        $this->assertSame('invalid', CompanySarvamSetting::current()->sarvam_key_status, 'badge: Key invalid');
        $this->assertNull(SarvamKeyService::currentKey(), 'an invalid key is not used');
        $this->assertFalse(SarvamKeyService::autoTranscribeOn(), 'automatic transcription stops');
        $this->assertSame(SarvamKeyService::WAITING_KEY, $job->fresh()->status, 'queued transcripts wait for a key');

        // a valid key saved again: the waiting transcripts run
        [, $json] = $this->save(self::KEY_B);
        $this->assertSame('connected', $json['status']);
        $this->assertSame(1, $json['resumed']);
        $this->assertSame('QUEUED', $job->fresh()->status);
        Queue::assertPushed(TranscribeRecordingJob::class, fn ($j) => $j->transcriptionJobId === $job->id && !isset($j->company['password']));
    }

    public function test_the_queued_job_carries_the_company_but_never_the_key()
    {
        $this->company('a');
        $this->save(self::KEY_A);
        $payload = serialize(new TranscribeRecordingJob(7));
        $this->assertStringNotContainsString(self::KEY_A, $payload);
        $this->assertStringContainsString('database', $payload, 'the company (database name) travels with the job');
    }
}
