<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\RecordingsController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Models\DialerLog;
use App\Models\Permissions;
use App\Models\Recording;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Manual dial call recordings: the Call Report row's play button, and the protected download / play / delete routes. */
class ManualCallRecordingTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $telecaller;
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->telecaller = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->as($this->admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $f) foreach ([$f, $f . '.deleting'] as $x) if (is_file($x)) @unlink($x);
        parent::tearDown();
    }

    private function as(User $u): void { Auth::login($u); $u->unsetRelation('permissionsGroup'); }
    private function setDeletePermission(bool $on): void { Permissions::where('name', 'Manager')->update(['delete_status' => $on ? 1 : 0]); }

    private function recordedCall(User $owner): array
    {
        @mkdir(storage_path('app/recordings'), 0755, true);
        $file = storage_path('app/recordings/test_manualrec_' . uniqid() . '.wav');
        file_put_contents($file, 'RIFF' . str_repeat("\0", 2044));
        $this->files[] = $file;
        $log = DialerLog::forceCreate(['phone_number' => '9' . random_int(100000000, 999999999), 'created_by' => $owner->id,
            'time_taken' => 25, 'duration' => '00:25', 'call_type' => 'click2call', 'call_source' => 'manual', 'call_state' => 'completed',
            'reference_id' => 'TESTMCR_' . uniqid(), 'file_path' => $file, 'created_at' => Carbon::now()]);
        $rec = Recording::forceCreate(['provider_call_id' => $log->reference_id, 'call_id' => $log->reference_id, 'dialer_log_id' => $log->id,
            'source_code' => 2, 'source_type' => 'Dialing', 'user_id' => $owner->id, 'phone_number' => $log->phone_number, 'duration' => 25,
            'file_path' => $file, 'download_status' => 'completed', 'call_status' => 'completed', 'call_date' => Carbon::now()]);
        $log->forceFill(['recording_id' => $rec->id])->save();
        return [$log, $rec, $file];
    }

    /** The manual call's row in the Call Report (Source = Manual). */
    private function reportRow(DialerLog $log): ?array
    {
        $today = Carbon::today()->format('Y-m-d');
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 500, 'from_date' => $today, 'to_date' => $today,
            'source' => 'manual', 'columns' => [['data' => 'time']], 'order' => [['column' => 0, 'dir' => 'desc']]]);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        $rows = app(ReportsController::class)->callReportData($req)->getData(true)['data'] ?? [];
        return collect($rows)->first(fn ($r) => $r['source'] === 'Manual' && $r['id'] === $log->id);
    }

    private function fetch(DialerLog $log, bool $play)
    {
        $req = Request::create('/admin/reports/download-recordings/' . $log->id . '/2' . ($play ? '?play=1' : ''), 'GET');
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        return app(ReportsController::class)->downloadRecordings($log->id, 2);
    }

    public function test_call_report_row_plays_this_manual_calls_recording()
    {
        [$log] = $this->recordedCall($this->admin);
        $row = $this->reportRow($log);
        $this->assertNotNull($row, 'manual call is listed in the Call Report');
        $this->assertSame('Manual Dial', $row['campaign']);
        $this->assertSame('Click2Call', $row['call_type']);
        $this->assertSame('available', $row['recording']['state']);
        $this->assertStringContainsString("/download-recordings/{$log->id}/2", $row['recording']['url'], 'play button points at this row');
    }

    public function test_download_and_play_serve_this_rows_file()
    {
        [$log, , $file] = $this->recordedCall($this->admin);
        $dl = $this->fetch($log, false);
        $this->assertInstanceOf(BinaryFileResponse::class, $dl);
        $this->assertSame(realpath($file), realpath($dl->getFile()->getPathname()));
        $this->assertStringContainsString('attachment', $dl->headers->get('Content-Disposition'));

        $play = $this->fetch($log, true);
        $this->assertInstanceOf(BinaryFileResponse::class, $play);
        $this->assertStringNotContainsString('attachment', (string)$play->headers->get('Content-Disposition'));
        $this->assertSame('bytes', $play->headers->get('Accept-Ranges'));
    }

    public function test_missing_deleted_and_unauthorized_recordings_are_refused()
    {
        [$log, $rec] = $this->recordedCall($this->admin);
        $rec->forceFill(['download_status' => 'deleted', 'file_path' => null])->save();
        try { $this->fetch($log, true); $this->fail('deleted recording served'); }
        catch (HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }

        [$other] = $this->recordedCall($this->admin);
        $this->as($this->telecaller);
        try { $this->fetch($other, false); $this->fail("another agent's recording served to a telecaller"); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_delete_route_enforces_admin_and_permission_and_keeps_the_call()
    {
        [$log, $rec, $file] = $this->recordedCall($this->admin);

        $this->setDeletePermission(false);
        $this->assertSame(403, app(RecordingsController::class)->destroy($log->id, 2)->getStatusCode(), 'permission off: admin blocked');
        $this->setDeletePermission(true);
        $this->as($this->telecaller);
        $this->assertSame(403, app(RecordingsController::class)->destroy($log->id, 2)->getStatusCode(), 'non-admin blocked');
        $this->assertFileExists($file);

        $this->as($this->admin);
        $res = app(RecordingsController::class)->destroy($log->id, 2);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertFileDoesNotExist($file);
        $this->assertSame('deleted', $rec->fresh()->download_status);
        $this->assertNotNull(DialerLog::find($log->id), 'call row kept');
        $row = $this->reportRow($log);
        $this->assertNotNull($row, 'the call stays in the Call Report');
        $this->assertSame('none', $row['recording']['state'], 'no play button after the recording was deleted');
    }
}
