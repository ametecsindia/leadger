<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Jobs\TranscribeRecordingJob;
use App\Models\CallAiAnalysis;
use App\Models\TranscriptionJob;
use App\Models\User;
use App\Services\SarvamAiService;
use App\Services\TranscriptionBillingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Transcription: overlapping Sarvam steps, step timings, and no endless "processing" when a worker stops. */
class TranscriptionPipelineTest extends TestCase
{
    use DatabaseTransactions;

    private string $wav;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        // 2 seconds of silence, 16 kHz mono 16-bit PCM
        $data = str_repeat("\0", 64000);
        $this->wav = tempnam(sys_get_temp_dir(), 'lgwav') . '.wav';
        file_put_contents($this->wav, 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($data)) . $data);
    }

    protected function tearDown(): void
    {
        @unlink($this->wav);
        parent::tearDown();
    }

    public function test_the_conversation_job_runs_while_the_transcript_is_made_and_steps_are_timed()
    {
        $svc = new class extends SarvamAiService {
            public array $calls = [];
            public function __construct() { parent::__construct('test-key'); }
            public function startConversationJob(string $filePath, string $mode = 'translate'): array { $this->calls[] = 'start conversation'; return ['success' => true, 'job_id' => 'j1']; }
            public function transcribeLongAudio(string $filePath, int $durationSeconds, ?string $languageCode = null): array { $this->calls[] = 'transcript'; return ['success' => true, 'transcript' => 'namaste', 'request_id' => 'r1']; }
            public function finishConversationJob(string $jobId, int $durationSeconds = 0): array
            {
                $this->calls[] = 'finish conversation';
                return ['success' => true, 'turns' => [['speaker' => 'S1', 'start' => 0, 'end' => 1, 'text' => 'Hello']], 'transcript' => 'Hello', 'language_code' => 'hi-IN', 'request_id' => 'j1'];
            }
            public function analyzeConversation(string $conversation, array $meta = []): ?array
            {
                $this->calls[] = 'analysis';
                return ['sentiment' => 'NEUTRAL', 'interest_level' => 'MEDIUM', 'intent' => 'x', 'summary' => 's', 'key_points' => null, 'next_action' => 'n', 'auto_disposition' => 'd', 'analysis_source' => 'test'];
            }
        };
        $beats = 0;
        $svc->onProgress = function () use (&$beats) { $beats++; };

        $res = $svc->processCallTranscription(999999901, 3, ['recording_file' => $this->wav, 'duration' => 2]);

        $this->assertSame('success', $res['status']);
        $this->assertSame(['start conversation', 'transcript', 'finish conversation', 'analysis'], $svc->calls);
        $this->assertSame(['conversation_upload', 'transcript', 'conversation_wait', 'analysis'], array_keys($res['timings']));
        $this->assertGreaterThanOrEqual(4, $beats, 'heartbeat after every step');
        $this->assertSame('completed', CallAiAnalysis::findFor(3, 999999901)->status);
    }

    private function job(string $status, int $ageSeconds, int $logId = 999999902): TranscriptionJob
    {
        $job = new TranscriptionJob(['batch_id' => 'trb_test', 'client_id' => 1, 'requested_by' => 1, 'recording_id' => 'r' . $logId, 'call_id' => $logId,
            'source_code' => 3, 'recording_file' => $this->wav, 'duration_seconds' => 2, 'billable_minutes' => 0.03, 'provider' => 'sarvam',
            'unit_rate' => 0.15, 'estimated_cost' => 0, 'actual_cost' => 0, 'currency' => 'INR', 'status' => $status]);
        $job->save();
        DB::table('transcription_jobs')->where('id', $job->id)->update(['created_at' => now()->subSeconds($ageSeconds), 'updated_at' => now()->subSeconds($ageSeconds)]);
        return $job->fresh();
    }

    public function test_a_processing_job_without_a_heartbeat_is_stalled()
    {
        $this->assertFalse($this->job('PROCESSING', 60)->isStalled());
        $this->assertTrue($this->job('PROCESSING', TranscriptionJob::STALL_SECONDS + 30, 999999903)->isStalled());
        $this->assertFalse($this->job('QUEUED', 3600, 999999904)->isStalled(), 'a queued job is waiting, not stalled');
    }

    public function test_the_batch_status_ends_for_a_stalled_job_so_the_page_stops_waiting()
    {
        $this->job('PROCESSING', TranscriptionJob::STALL_SECONDS + 30);
        $b = (new TranscriptionBillingService())->getBatchStatus('trb_test');
        $this->assertTrue($b['is_finished']);
        $this->assertSame(1, $b['failed']);
        $this->assertSame('FAILED', $b['results'][0]['status']);
        $this->assertStringContainsString('Retry', $b['results'][0]['error']);
    }

    public function test_a_stalled_job_handed_out_again_is_run_again()
    {
        $job = $this->job('PROCESSING', TranscriptionJob::STALL_SECONDS + 30);
        DB::table('company_sarvam_settings')->update(['sarvam_api_key' => null]);   // no key: nothing is sent to Sarvam
        (new TranscribeRecordingJob($job->id))->handle();
        $this->assertNotSame('PROCESSING', $job->fresh()->status, 'picked up again instead of being skipped forever');
    }

    public function test_waiting_for_a_free_slot_does_not_use_up_the_jobs_attempts()
    {
        $j = new TranscribeRecordingJob(1);
        $this->assertObjectNotHasProperty('tries', $j);
        $this->assertTrue($j->retryUntil()->gt(now()->addHours(5)));
        $this->assertSame(3, $j->maxExceptions);
    }

    public function test_the_popup_shows_queued_or_processing_and_reports_a_stalled_job_as_failed()
    {
        $log = DB::table('call_logs')->whereNotNull('recording_id')->value('id');
        if (!$log) {
            $this->markTestSkipped('No call with a recording in this database.');
        }
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $analysis = CallAiAnalysis::firstOrNewFor(0, $log);
        $analysis->status = 'processing';
        $analysis->save();
        $job = $this->job('PROCESSING', 40, $log);
        DB::table('transcription_jobs')->where('id', $job->id)->update(['source_code' => 0]);
        $url = route('recordingTranscript', ['id' => $log, 'source_code' => 0]);

        $res = $this->actingAs($admin)->getJson($url)->assertOk()->json();
        $this->assertSame('processing', $res['status']);
        $this->assertSame('processing', $res['progress']['state']);
        $this->assertGreaterThanOrEqual(40, $res['progress']['seconds']);

        DB::table('transcription_jobs')->where('id', $job->id)->update(['updated_at' => now()->subSeconds(TranscriptionJob::STALL_SECONDS + 30)]);
        $res = $this->getJson($url)->assertOk()->json();
        $this->assertSame('failed', $res['status']);
        $this->assertStringContainsString('Retry', $res['message']);
    }
}
