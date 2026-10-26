<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Reports > Transcript Report: requests by user, unique recording counts, types, status, credits, saved text only. */
class TranscriptReportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private int $agent;
    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        Http::fake();                             // any Sarvam call would be recorded here
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        DB::table('transcription_jobs')->delete();   // rolled back after each test
        $this->agent = DB::table('users')->insertGetId(['name' => 'Report Manager Agent', 'email' => uniqid('tr', true) . '@example.test',
            'password' => bcrypt('x'), 'permissions_id' => DB::table('permissions')->where('name', 'Telecaller')->value('id'), 'status' => 1,
            'team_id' => '905', 'created_at' => now(), 'updated_at' => now()]);
        $this->day = now(config('services.telephony.timezone') ?: config('app.timezone'))->format('Y-m-d');
    }

    private function job(string $batch, int $user, int $callId, string $status, float $actual, float $estimated = 0.05): void
    {
        DB::table('transcription_jobs')->insert(['batch_id' => $batch, 'client_id' => 1, 'requested_by' => $user, 'call_id' => $callId,
            'source_code' => 2, 'customer_name' => 'Cust ' . $callId, 'agent_name' => 'Agent', 'phonenumber' => '9000000' . $callId,
            'duration_seconds' => 30, 'billable_minutes' => 0.5, 'provider' => 'sarvam', 'unit_rate' => 0.1, 'estimated_cost' => $estimated,
            'actual_cost' => $actual, 'currency' => 'INR', 'status' => $status, 'billing_status' => 'PENDING', 'created_at' => now(), 'updated_at' => now(),
            'completed_at' => $status === 'COMPLETED' ? now() : null]);
    }

    private function analysis(int $callId): void
    {
        DB::table('call_ai_analyses')->insert(['call_id' => 9000000 + $callId, 'recording_id' => 'dialer_' . $callId, 'status' => 'completed',
            'summary' => 'Customer asked for a callback.', 'transcript' => 'Hello. I want a callback.', 'transcript_english' => 'Hello. I want a callback.',
            'conversation' => json_encode([['speaker' => 'S1', 'start' => 1.2, 'end' => 2, 'text' => 'Hello.'], ['speaker' => 'S2', 'start' => 3, 'end' => 4, 'text' => 'I want a callback.']]),
            'speaker_roles' => json_encode(['S1' => 'agent', 'S2' => 'customer']), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function data(array $params = []): array
    {
        return $this->actingAs($this->admin)->postJson(route('transcriptReportData'), $params + ['from_date' => $this->day, 'to_date' => $this->day,
            'length' => 50, 'columns' => [['data' => 'date']], 'order' => [['column' => 0, 'dir' => 'desc']]])->assertOk()->json();
    }

    public function test_counts_types_status_and_credits_from_the_saved_records()
    {
        // one request: recording 701 transcribed twice + 702 failed; another: 703 still running
        $this->job('trb_a', $this->admin->id, 701, 'COMPLETED', 0.04);
        $this->job('trb_a', $this->admin->id, 701, 'COMPLETED', 0.03);
        $this->job('trb_a', $this->admin->id, 702, 'FAILED', 0);
        $this->job('trb_b', $this->agent, 703, 'PROCESSING', 0, 0.08);
        $this->job('trb_c', $this->admin->id, 704, 'PENDING_CONFIRMATION', 0);   // an estimate never started: not a request
        $this->analysis(701);

        $res = $this->data();
        $rows = collect($res['data'])->keyBy('batch_id');
        $this->assertSame(['trb_a', 'trb_b'], $rows->keys()->sort()->values()->all());
        $a = $rows['trb_a'];
        $this->assertSame([1, 2, 'Completed', 1], [$a['recordings'], $a['recordings_requested'], $a['status'], $a['failed']], 'recording 701 counted once');
        $this->assertSame(['Summary', 'Agent & Customer', 'Detailed Conversation'], $a['types']);
        $this->assertEqualsWithDelta(0.07, $a['credits_confirmed'], 0.0001);
        $this->assertSame([0, 'Processing'], [$rows['trb_b']['recordings'], $rows['trb_b']['status']]);
        $this->assertEqualsWithDelta(0.08, $rows['trb_b']['credits_estimated'], 0.0001, 'estimated, not confirmed');
        $this->assertSame([1, 2, 1], [$res['kpis']['recordings'], $res['kpis']['completed'], $res['kpis']['waiting']]);
        $this->assertEqualsWithDelta(0.07, $res['kpis']['credits'], 0.0001);

        $this->assertSame(['trb_b'], array_column($this->data(['status' => 'Processing'])['data'], 'batch_id'));
        $this->assertSame(['trb_a'], array_column($this->data(['type' => 'summary'])['data'], 'batch_id'));
        $this->assertSame(['trb_b'], array_column($this->data(['user' => $this->agent])['data'], 'batch_id'));
        $this->assertSame([], $this->data(['from_date' => '2020-01-01', 'to_date' => '2020-01-02'])['data']);
    }

    public function test_view_and_download_read_the_saved_transcript_without_calling_sarvam()
    {
        $this->job('trb_v', $this->admin->id, 711, 'COMPLETED', 0.02);
        $this->analysis(711);
        $jobs = DB::table('transcription_jobs')->count();

        $show = $this->actingAs($this->admin)->getJson(route('transcriptReportShow', 'trb_v'))->assertOk()->json();
        $this->assertSame('Agent', $show['items'][0]['conversation'][0]['speaker']);
        $this->assertStringContainsString('[00:03] Customer: I want a callback.', $show['text']);
        $this->assertStringContainsString('SUMMARY', $show['text']);

        $file = $this->get(route('transcriptReportDownload', 'trb_v'))->assertOk();
        $this->assertStringContainsString('attachment; filename="transcript-', $file->headers->get('content-disposition'));
        $this->assertStringContainsString('Customer asked for a callback.', $file->getContent());

        Http::assertNothingSent();
        $this->assertSame($jobs, DB::table('transcription_jobs')->count(), 'nothing queued or charged again');
        $this->get(route('transcriptReportShow', 'trb_unknown'))->assertNotFound();
    }

    public function test_only_the_transcript_roles_open_it_and_a_manager_sees_their_team()
    {
        $tele = User::find($this->agent);
        $this->actingAs($tele)->get(route('transcriptReport'))->assertForbidden();
        $this->actingAs($tele)->postJson(route('transcriptReportData'), [])->assertForbidden();
        $this->actingAs($this->admin)->get(route('transcriptReport'))->assertOk()->assertSee('Transcript Report');
        $this->actingAs($tele)->get(route('callReport'))->assertDontSee(route('transcriptReport'), false);   // not in the menu

        $manager = User::find(DB::table('users')->insertGetId(['name' => 'Report Manager', 'email' => uniqid('trm', true) . '@example.test',
            'password' => bcrypt('x'), 'permissions_id' => DB::table('permissions')->where('name', 'Manager')->value('id'), 'status' => 1,
            'team_id' => '905', 'created_at' => now(), 'updated_at' => now()]));
        DB::table('team_user')->insert(['team_id' => 905, 'user_id' => $this->agent]);
        $this->job('trb_team', $this->agent, 721, 'COMPLETED', 0.01);
        $this->job('trb_other', $this->admin->id, 722, 'COMPLETED', 0.01);
        $ids = array_column($this->actingAs($manager)->postJson(route('transcriptReportData'), ['from_date' => $this->day, 'to_date' => $this->day])->json('data'), 'batch_id');
        $this->assertSame(['trb_team'], $ids);
        $this->actingAs($manager)->getJson(route('transcriptReportShow', 'trb_other'))->assertNotFound();
    }
}
