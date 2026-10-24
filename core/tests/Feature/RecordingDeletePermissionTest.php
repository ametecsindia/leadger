<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Http\Controllers\Dashboard\RecordingsController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Permissions;
use App\Models\Recording;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Admin-only Delete Permission for call recordings: the four permission cases, server-side 403s,
 * and safe deletion (only the selected recording's audio; call log / lead kept; failures change nothing).
 * Controllers are called directly: the Tenants middleware reconnects the DB, escaping the test transaction.
 */
class RecordingDeletePermissionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $nonAdmin;
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        // The real Telecaller account (not just "any non-admin")
        $this->nonAdmin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            foreach ([$f, $f . '.deleting'] as $x) {
                if (is_file($x)) @unlink($x);
            }
        }
        parent::tearDown();
    }

    private function setPermission(bool $on): void
    {
        Permissions::where('name', 'Manager')->update(['delete_status' => $on ? 1 : 0]);
    }

    private function actAs(User $user): void
    {
        Auth::login($user);
        $user->unsetRelation('permissionsGroup');
    }

    /** A manual call with a real (temporary) audio file and its recording row. */
    private function callWithRecording(): array
    {
        $dir = storage_path('app/recordings');
        @mkdir($dir, 0755, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'test_delete_' . uniqid() . '.wav';
        file_put_contents($file, str_repeat("\0", 2048));
        $this->files[] = $file;

        $lead = Lead::forceCreate([
            'name' => 'Delete Test Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'status' => Status::first()->id ?? 1, 'created_by' => $this->admin->id,
        ]);
        $log = DialerLog::forceCreate([
            'phone_number' => $lead->phonenumber, 'lead_id' => $lead->id, 'created_by' => $this->admin->id,
            'time_taken' => 30, 'duration' => '00:30', 'call_type' => 'click2call', 'call_source' => 'manual',
            'call_state' => 'completed', 'reference_id' => 'TESTDEL_' . uniqid(), 'file_path' => $file,
            'click_recording_url' => 'https://voip.example.test/rec/x.wav',
        ]);
        $rec = Recording::forceCreate([
            'provider_call_id' => $log->reference_id, 'call_id' => $log->reference_id, 'dialer_log_id' => $log->id,
            'source_code' => 2, 'source_type' => 'Dialing', 'phone_number' => $log->phone_number, 'duration' => 30,
            'file_path' => $file, 'recording_url' => 'https://voip.example.test/rec/x.wav',
            'download_status' => 'completed', 'call_status' => 'completed', 'call_date' => Carbon::now(),
        ]);
        $log->forceFill(['recording_id' => $rec->id])->save();
        return [$log, $rec, $file, $lead];
    }

    private function rowFor(DialerLog $log): ?array
    {
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 200,
            'columns' => [['data' => 'created_at']], 'order' => [['column' => 0, 'dir' => 'desc']]]);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        $json = json_decode(app(RecordingsController::class)->recordingData($req)->getContent(), true);
        return collect($json['data'])->first(fn ($r) => (int)$r['source_code'] === 2 && (int)$r['log_id'] === $log->id);
    }

    private function deleteAs(User $user, DialerLog $log): \Illuminate\Http\JsonResponse
    {
        $this->actAs($user);
        return app(RecordingsController::class)->destroy($log->id, 2);
    }

    private function assertNothingDeleted(DialerLog $log, Recording $rec, string $file): void
    {
        $this->assertFileExists($file);
        $this->assertSame('completed', $rec->fresh()->download_status);
        $this->assertSame($file, $log->fresh()->file_path);
    }

    public function test_case1_permission_disabled_admin_hidden_and_blocked()
    {
        $this->setPermission(false);
        [$log, $rec, $file] = $this->callWithRecording();
        $this->actAs($this->admin);

        $this->assertFalse(Helper::canDeleteRecordings());
        $this->assertStringNotContainsString('deleteRecording', $this->rowFor($log)['play'], 'delete icon hidden');
        $res = $this->deleteAs($this->admin, $log);
        $this->assertSame(403, $res->getStatusCode());
        $this->assertNothingDeleted($log, $rec, $file);
    }

    public function test_case2_permission_enabled_admin_visible_and_deletes_only_that_recording()
    {
        $this->setPermission(true);
        [$log, $rec, $file, $lead] = $this->callWithRecording();
        [$otherLog, $otherRec, $otherFile] = $this->callWithRecording();
        $this->actAs($this->admin);

        $this->assertTrue(Helper::canDeleteRecordings());
        $this->assertStringContainsString("deleteRecording({$log->id}, 2)", $this->rowFor($log)['play'], 'delete icon visible to Admin');

        $res = $this->deleteAs($this->admin, $log);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->getData()->success);

        $this->assertFileDoesNotExist($file, 'audio removed');
        $this->assertFileDoesNotExist($file . '.deleting');
        $this->assertSame('deleted', $rec->fresh()->download_status);
        $this->assertNull($rec->fresh()->file_path);
        $this->assertNotNull(DialerLog::find($log->id), 'dialer log (the call) kept');
        $this->assertSame('00:30', DialerLog::find($log->id)->duration);
        $this->assertNotNull(Lead::find($lead->id), 'lead kept');
        $this->assertNull($this->rowFor($log), 'recording no longer listed');

        $this->assertNothingDeleted($otherLog, $otherRec, $otherFile); // unrelated recording untouched
        $this->assertSame(404, $this->deleteAs($this->admin, $log)->getStatusCode(), 'second delete: already gone');

        // Provider callback after deletion must not bring the audio back
        (new \App\Services\CallRecordingService())->processWebhook(['ref_id' => $log->reference_id, 'recording_url' => 'https://voip.example.test/rec/again.wav', 'B_DIAL_STATUS' => 'ANSWER', 'talk_duration' => 30]);
        $this->assertSame('deleted', $rec->fresh()->download_status);
    }

    public function test_case3_permission_enabled_non_admin_hidden_and_blocked()
    {
        $this->setPermission(true);
        [$log, $rec, $file] = $this->callWithRecording();
        $this->actAs($this->nonAdmin);

        $this->assertFalse(Helper::canDeleteRecordings());
        $row = $this->rowFor($log);
        if ($row) {
            $this->assertStringNotContainsString('deleteRecording', $row['play']);
        }
        $this->assertSame(403, $this->deleteAs($this->nonAdmin, $log)->getStatusCode());
        $req = Request::create('/x', 'POST', ['items' => [['id' => $log->id, 'source_code' => 2]]]);
        $this->assertSame(403, app(RecordingsController::class)->bulkDestroy($req)->getStatusCode());
        $this->assertNothingDeleted($log, $rec, $file);

        // Every other non-admin role is blocked as well (Webmaster, Manager, Team Leader, ...)
        foreach (User::whereHas('permissionsGroup', fn ($q) => $q->where('name', '!=', 'Admin'))->get() as $other) {
            $this->assertSame(403, $this->deleteAs($other, $log)->getStatusCode(), "role {$other->permissionsGroup->name} must get 403");
        }
        $this->assertNothingDeleted($log, $rec, $file);
    }

    public function test_case4_permission_disabled_non_admin_hidden_and_blocked()
    {
        $this->setPermission(false);
        [$log, $rec, $file] = $this->callWithRecording();
        $this->actAs($this->nonAdmin);

        $this->assertFalse(Helper::canDeleteRecordings());
        $this->assertSame(403, $this->deleteAs($this->nonAdmin, $log)->getStatusCode());
        $this->assertNothingDeleted($log, $rec, $file);
    }

    public function test_locked_file_changes_nothing()
    {
        $this->setPermission(true);
        [$log, $rec, $file] = $this->callWithRecording();
        // Make the file impossible to move aside: a non-empty folder occupies the target name
        $blocker = $file . '.deleting';
        mkdir($blocker);
        file_put_contents($blocker . DIRECTORY_SEPARATOR . 'x', 'x');
        try {
            $res = $this->deleteAs($this->admin, $log);
        } finally {
            @unlink($blocker . DIRECTORY_SEPARATOR . 'x');
            @rmdir($blocker);
        }
        $this->assertSame(500, $res->getStatusCode());
        $this->assertNothingDeleted($log, $rec, $file);
    }

    public function test_files_outside_the_recordings_folder_are_never_deleted()
    {
        $this->setPermission(true);
        [$log, $rec] = $this->callWithRecording();
        $outside = storage_path('app/not_a_recording_' . uniqid() . '.wav');
        file_put_contents($outside, 'keep me');
        $this->files[] = $outside;
        $rec->forceFill(['file_path' => $outside])->save();
        $log->forceFill(['file_path' => $outside])->save();

        $this->assertSame(200, $this->deleteAs($this->admin, $log)->getStatusCode());
        $this->assertFileExists($outside);
    }

    public function test_only_admin_can_change_the_setting_in_company_settings()
    {
        $this->setPermission(false);
        $this->actAs($this->nonAdmin);
        $req = Request::create('/company-settings/update', 'POST', ['company_name' => 'X', 'manager_delete_permission' => '1']);
        $req->setUserResolver(fn () => $this->nonAdmin);
        app()->instance('request', $req);
        $res = app(UsersController::class)->updateCompanySettings($req);
        $this->assertSame(403, $res->getStatusCode());
        $this->assertFalse(Helper::isDeletePermissionEnabled(), 'non-admin could not enable it');
    }

    /** Call Recordings table: Campaign / Type come from the call (no Lead ID for manual dial), and the table Search box filters. */
    public function test_table_shows_lead_manual_dial_and_call_type_and_searches()
    {
        $this->setPermission(true);
        [$log, $rec, , $lead] = $this->callWithRecording();
        [$noLeadLog, $noLeadRec, , $otherLead] = $this->callWithRecording();
        $noLeadLog->forceFill(['lead_id' => null])->save();   // lead known only on the recording row
        $noLeadRec->forceFill(['lead_id' => $otherLead->id])->save();
        $this->actAs($this->admin);

        $row = $this->rowFor($log);
        // Lead ID is shown for campaign calls only (testing feedback, Oct 2026): a manual dial call shows a dash
        $this->assertStringNotContainsString("<span>{$lead->id}</span>", $row['id']);
        $this->assertStringContainsString('—', $row['id']);
        $this->assertSame('Manual Dial', strip_tags($row['campaign_name']));
        $this->assertStringContainsString('Click2Call', strip_tags($row['source_type']));
        $this->assertStringContainsString('lgDlPlay(this)', $row['play']);
        $this->assertStringContainsString("data-url='/admin/reports/download-recordings/{$log->id}/2'", $row['play']);


        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 200, 'search' => ['value' => (string)$lead->id],
            'columns' => [['data' => 'created_at']], 'order' => [['column' => 0, 'dir' => 'desc']]]);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        $ids = collect(json_decode(app(RecordingsController::class)->recordingData($req)->getContent(), true)['data'])->pluck('log_id')->all();
        $this->assertContains($log->id, $ids);
        $this->assertNotContains($noLeadLog->id, $ids);
    }
}
