<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Jobs\TranscribeRecordingJob;
use App\Models\CompanySarvamSetting;
use App\Models\TranscriptResult;
use App\Models\User;
use App\Services\TranscriptOptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Transcript options (Summary / Agent and Customer / Description) from a recording's saved transcript: each saved
 * per recording and option, reopened without a new AI call, never transcribed again when a transcript exists.
 */
class TranscriptOptionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private int $log;
    public int $chatCalls = 0;
    public $chatAnswer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        CompanySarvamSetting::current()->forceFill(['sarvam_api_key' => 'GOOD_sk_1234567890abcdef1234', 'sarvam_key_last4' => '1234',
            'sarvam_key_status' => CompanySarvamSetting::STATUS_CONNECTED])->save();
        $this->log = DB::table('dialer_logs')->insertGetId(['phone_number' => '9000000801', 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'completed', 'time_taken' => 40, 'duration' => '00:40', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('call_ai_analyses')->insert(['call_id' => 9000000 + $this->log, 'recording_id' => 'dialer_' . $this->log, 'status' => 'completed',
            'transcript' => 'Namaste. Loan kavali.', 'transcript_english' => 'Hello. I need a loan.', 'source_language' => 'te-IN',
            'conversation' => json_encode([['speaker' => 'S1', 'start' => 0.5, 'end' => 2, 'text' => 'Hello, this is Ravi from ABC Finance.'],
                ['speaker' => 'S2', 'start' => 3.2, 'end' => 5, 'text' => 'I need a home loan. What is the interest?'], ['speaker' => 'S2', 'start' => 6, 'end' => 6.5, 'text' => '...']]),
            'created_at' => now(), 'updated_at' => now()]);
        $this->chatAnswer = ['purpose' => 'Customer asked about a home loan.', 'customer_requirements' => ['Home loan'], 'key_points' => ['Interest rate asked'],
            'interest_level' => 'high', 'call_outcome' => 'Not stated', 'next_action' => 'Share the interest rate'];
        // the Sarvam chat model, stubbed (the real service talks to Sarvam with curl): counts calls, returns the set answer
        $test = $this;
        $this->app->instance(\App\Services\SarvamAiService::class, new class($test) extends \App\Services\SarvamAiService {
            public function __construct(private $test) { parent::__construct('test-key'); }
            public function chatJson(string $system, string $user, int $maxTokens = 4000): array
            {
                $this->test->chatCalls++;
                $a = $this->test->chatAnswer;
                return is_int($a) ? [null, "Sarvam AI returned HTTP {$a}: Server busy", 'sarvam-105b', null] : [$a, null, 'sarvam-105b', 321];
            }
        });
        Http::fake();   // nothing else may reach the network
    }

    private function start(string $option, array $keys = null)
    {
        return $this->actingAs($this->admin)->postJson(route('recordingsTranscriptionStartJobs'), ['recordings' => $keys ?? ['2_' . $this->log], 'option' => $option]);
    }

    private function saved(): array
    {
        return $this->actingAs($this->admin)->getJson(route('recordingTranscriptOptions', [$this->log, 2]))->assertOk()->json('options');
    }

    public function test_summary_is_generated_once_from_the_saved_transcript_and_reopened_without_a_new_call()
    {
        Queue::fake([TranscribeRecordingJob::class]);   // the audio must not be transcribed again
        $this->start('summary')->assertOk()->assertJsonPath('results.2_' . $this->log . '.state', 'queued');
        $o = $this->saved()['summary'];
        $this->assertSame(['completed', 'High', 'Share the interest rate', 321], [$o['status'], $o['result']['interest_level'], $o['result']['next_action'], $o['usage_tokens']]);

        $this->start('summary')->assertOk()->assertJsonPath('results.2_' . $this->log . '.state', 'completed');   // shown again
        $this->assertSame(1, $this->chatCalls, 'no second AI call for a saved result');
        Queue::assertNotPushed(TranscribeRecordingJob::class);
        $this->assertSame(['none', 'none'], [$this->saved()['agent_customer']['status'], $this->saved()['description']['status']], 'options are separate');
    }

    public function test_agent_and_customer_keeps_unclear_roles_as_speakers_and_drops_unknown_speakers()
    {
        $this->chatAnswer = ['speakers' => [
            ['speaker' => 'S1', 'role' => 'Agent', 'statements' => ['This is Ravi from ABC Finance'], 'questions' => [], 'information_given' => [], 'requirements' => [], 'objections' => []],
            ['speaker' => 'S2', 'role' => 'Unknown', 'statements' => ['I need a home loan'], 'questions' => ['What is the interest?'], 'information_given' => [], 'requirements' => ['Home loan'], 'objections' => []],
            ['speaker' => 'S7', 'role' => 'Customer', 'statements' => ['not in this call'], 'questions' => [], 'information_given' => [], 'requirements' => [], 'objections' => []],
        ], 'customer_interest_level' => 'Medium', 'role_basis' => 'S1 introduces the company.'];
        $this->start('agent_customer')->assertOk();
        $r = $this->saved()['agent_customer']['result'];
        $this->assertSame(['S1', 'S2'], array_column($r['speakers'], 'speaker'), 'only the speakers of this call');
        $this->assertStringStartsWith('Agent', $r['speakers'][0]['label']);
        $this->assertSame('Speaker 2', $r['speakers'][1]['label'], 'an unclear role stays Speaker 2');
        $this->assertFalse($r['roles_confirmed']);
    }

    public function test_description_is_the_saved_conversation_without_any_ai_call()
    {
        $this->start('description')->assertOk();
        $o = $this->saved()['description'];
        $this->assertSame('completed', $o['status']);
        $this->assertNull($o['model'], 'built from the saved transcript');
        $this->assertSame(0, $this->chatCalls);
        $this->assertSame(['00:00', '00:03', '00:06'], array_column($o['result']['turns'], 'time'));
        $this->assertSame('[unclear speech]', $o['result']['turns'][2]['text']);
        $this->assertSame('Namaste. Loan kavali.', $o['result']['original'], 'original language kept separately');

        $file = $this->get(route('recordingTranscriptOptionDownload', [$this->log, 2]) . '?option=description')->assertOk();
        $this->assertStringContainsString('[00:03] Speaker 2: I need a home loan. What is the interest?', $file->getContent());
        $this->get(route('recordingTranscriptOptionDownload', [$this->log, 2]) . '?option=summary')->assertNotFound();   // not generated yet
    }

    public function test_a_failed_attempt_never_replaces_a_saved_result_and_can_be_retried()
    {
        $this->chatAnswer = 500;
        $this->start('summary')->assertOk();
        $o = $this->saved()['summary'];
        $this->assertSame('failed', $o['status']);
        $this->assertStringContainsString('HTTP 500', $o['error']);

        $this->chatAnswer = ['purpose' => 'Loan enquiry.', 'customer_requirements' => [], 'key_points' => [], 'interest_level' => 'Low', 'call_outcome' => 'Not stated', 'next_action' => 'Call back'];
        $this->start('summary')->assertOk();
        $this->assertSame('Loan enquiry.', $this->saved()['summary']['result']['purpose'], 'retry worked');

        $this->chatAnswer = 500;
        $this->start('summary')->assertOk()->assertJsonPath('results.2_' . $this->log . '.state', 'completed');
        $this->assertSame('Loan enquiry.', $this->saved()['summary']['result']['purpose'], 'still the saved result');
        $this->assertSame(1, TranscriptResult::where(['source_code' => 2, 'log_id' => $this->log, 'option' => 'summary'])->count(), 'one row');
    }

    public function test_a_recording_without_transcript_is_transcribed_first_and_the_option_follows()
    {
        Queue::fake([TranscribeRecordingJob::class]);
        // a manual call whose audio file is really there (the transcription job is queued, not run)
        $billing = new \App\Services\TranscriptionBillingService();
        $log = DB::table('dialer_logs')->whereNotNull('recording_id')->orderByDesc('id')->limit(50)->pluck('id')
            ->first(fn ($id) => ($r = $billing->getRecordingDetails(2, $id)) && !$r['audio_issue']);
        if (!$log) {
            $this->markTestSkipped('No manual call with an audio file in this database.');
        }
        DB::table('call_ai_analyses')->whereIn('recording_id', ['dialer_' . $log])->delete();   // rolled back
        DB::table('transcript_results')->where(['source_code' => 2, 'log_id' => $log])->delete();
        $jobs = DB::table('transcription_jobs')->count();

        $r = $this->start('summary', ['2_' . $log])->assertOk()->json();
        $this->assertSame('needs_transcript', $r['results']['2_' . $log]['state']);
        $this->assertNotNull($r['batch_id'], 'the existing transcription (and billing) runs first');
        $this->assertSame($jobs + 1, DB::table('transcription_jobs')->count());
        Queue::assertPushed(TranscribeRecordingJob::class, 1);
        $this->assertSame('pending', TranscriptResult::where(['source_code' => 2, 'log_id' => $log, 'option' => 'summary'])->value('status'));
        $this->assertSame(0, $this->chatCalls, 'nothing generated before the transcript exists');

        // the transcription finished (its analysis row, made pending when the job was created, is now completed):
        // the waiting option is generated from it
        $this->assertSame('pending', DB::table('call_ai_analyses')->where('recording_id', 'dialer_' . $log)->value('status'));
        DB::table('call_ai_analyses')->where('recording_id', 'dialer_' . $log)->update(['status' => 'completed', 'transcript_english' => 'Hello. I need a loan.']);
        TranscriptOptionService::afterTranscript(2, $log, true);
        $this->assertSame('completed', TranscriptResult::where(['source_code' => 2, 'log_id' => $log, 'option' => 'summary'])->value('status'));
        $this->assertSame(1, $this->chatCalls);

        // a transcription that fails fails the waiting options of that recording too
        DB::table('transcript_results')->where(['source_code' => 2, 'log_id' => $log])->update(['status' => 'pending', 'option' => 'agent_customer']);
        TranscriptOptionService::afterTranscript(2, $log, false, 'Sarvam could not read the audio');
        $this->assertStringContainsString('Sarvam could not read the audio', TranscriptResult::where(['source_code' => 2, 'log_id' => $log])->value('error_message'));
    }

    public function test_permissions_and_unknown_options()
    {
        $tele = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->actingAs($tele)->postJson(route('recordingsTranscriptionStartJobs'), ['recordings' => ['2_' . $this->log], 'option' => 'summary'])->assertForbidden();
        $this->start('everything')->assertStatus(422);
        $this->actingAs($this->admin)->getJson(route('recordingTranscriptOptions', [999999999, 2]))->assertNotFound();
        $this->assertSame(0, $this->chatCalls);
    }
}
