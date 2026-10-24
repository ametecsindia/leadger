<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\ReportsController;
use App\Http\Middleware\TelecallerMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Call Report testing feedback (Oct 2026): agent filter with every user the viewer may see, call result and seconds
 * per row, feedback counts, export with or without the recording URL, Lead ID only for the dialled number, Call Logs.
 */
class CallReportFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->today = date('Y-m-d');
    }

    private function manualCall(string $phone, array $attrs = []): int
    {
        return DB::table('dialer_logs')->insertGetId(array_merge(['phone_number' => $phone, 'created_by' => $this->admin->id,
            'call_source' => 'manual', 'call_type' => 'click2call', 'call_state' => 'no-answer', 'time_taken' => 0, 'duration' => '00:00',
            'created_at' => now(), 'updated_at' => now()], $attrs));
    }

    private function report(array $params = []): array
    {
        return $this->actingAs($this->admin)->post(route('callReportData'), array_merge(['_token' => csrf_token(), 'from_date' => $this->today,
            'to_date' => $this->today, 'source' => 'manual', 'length' => 500], $params))->assertOk()->json();
    }

    public function test_agent_filter_lists_every_active_user_for_admins_and_only_self_for_telecallers()
    {
        $names = ReportsController::reportAgents($this->admin)->pluck('id')->all();
        $this->assertEqualsCanonicalizing(User::where('status', 1)->pluck('id')->all(), $names);

        $telecaller = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->first();
        if ($telecaller) {
            $this->assertSame([$telecaller->id], ReportsController::reportAgents($telecaller)->pluck('id')->all());
        }
    }

    public function test_rows_show_result_seconds_and_the_lead_only_for_its_own_number()
    {
        $status = DB::table('statuses')->value('id');
        $lead = DB::table('leads')->insertGetId(['name' => 'Report Lead', 'phonenumber' => '9000000301', 'created_at' => now(), 'updated_at' => now()]);
        $this->manualCall('9000000301', ['lead_id' => $lead, 'call_state' => 'completed', 'time_taken' => 83, 'status_id' => $status]);
        $this->manualCall('9000000302', ['lead_id' => $lead]);   // linked to a lead with another number

        $res = $this->report(['q' => '900000030']);
        $rows = collect($res['data'])->keyBy('mobile');
        $this->assertSame(['Connected', 83, $lead], [$rows['9000000301']['call_result'], $rows['9000000301']['seconds'], $rows['9000000301']['lead_id']]);
        $this->assertSame(['No Answer', null], [$rows['9000000302']['call_result'], $rows['9000000302']['lead_id']]);
        $this->assertSame([1, 1], [$res['with_feedback'], $res['without_feedback']]);
    }

    public function test_export_with_and_without_the_recording_url()
    {
        $this->manualCall('9000000401', ['call_state' => 'completed', 'time_taken' => 61]);
        $params = ['from_date' => $this->today, 'to_date' => $this->today, 'source' => 'manual', 'q' => '9000000401'];
        $lines = function (array $p) {
            $body = $this->actingAs($this->admin)->get(route('callReportExport', ['format' => 'csv'] + $p))->assertOk()->streamedContent();
            return array_map('str_getcsv', array_filter(explode("\n", trim(substr($body, 3)))));
        };

        $plain = $lines($params);
        $this->assertNotContains('Recording URL', $plain[0]);
        $this->assertSame(['01:01', '61', 'Connected'], [$plain[1][7], $plain[1][8], $plain[1][9]], 'Duration, Duration (sec), Call Result');

        $withUrl = $lines($params + ['with_url' => 1]);
        $this->assertSame('Recording URL', end($withUrl[0]));
        $this->assertCount(count($withUrl[0]), $withUrl[1]);
    }

    public function test_call_logs_table_sorts_by_its_column_names()
    {
        $this->actingAs($this->admin)->post(route('callLogsData'), ['_token' => csrf_token(), 'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [['data' => 'date_time']], 'order' => [['column' => 0, 'dir' => 'asc']]])->assertOk();
        $this->actingAs($this->admin)->post(route('callLogsData'), ['_token' => csrf_token(), 'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [['data' => 'id; DROP TABLE x']], 'order' => [['column' => 0, 'dir' => 'sideways']]])->assertOk();
    }
}
