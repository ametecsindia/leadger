<?php

namespace Tests\Feature;

use App\Console\Commands\FetchPendingRecordingsCommand;
use App\Jobs\DownloadRecordingJob;
use App\Models\Campaign;
use App\Models\DialerLog;
use App\Models\Recording;
use App\Models\User;
use App\Services\CallRecordingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CallRecordingTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have a user and campaign for testing
        $this->user = User::first() ?? User::create([
            'name' => 'Test Agent',
            'email' => 'agent_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'permissions_id' => 1,
            'status' => 1,
        ]);

        $this->campaign = Campaign::first() ?? Campaign::create([
            'name' => 'Test Campaign',
            'status' => 1,
            'team_id' => 1,
        ]);
    }

    /**
     * Test 1: A connected call with talk time > 0 creates a recording immediately.
     */
    public function test_connected_call_creates_recording()
    {
        Queue::fake([DownloadRecordingJob::class]);

        $providerCallId = 'CALL_TEST_' . uniqid();
        $refId = 'REF_TEST_' . uniqid();

        // Create initial dialer log
        $dialerLog = DialerLog::create([
            'phone_number' => '9876543210',
            'created_by' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'time_taken' => 0,
            'call_type' => 'click2call',
            'reference_id' => $refId,
            'provider_call_id' => $providerCallId,
            'created_at' => Carbon::now(),
        ]);

        $service = new CallRecordingService();

        $payload = [
            'ref_id' => $refId,
            'call_id' => $providerCallId,
            'b_party_no' => '9876543210',
            'recording_url' => 'https://sip.telozar.com/recordings/audio_test_1.wav',
            'A_DIAL_STATUS' => 'Connected',
            'B_DIAL_STATUS' => 'Connected',
            'talk_duration' => 45,
            'status' => 'connected',
        ];

        $result = $service->processWebhook($payload);

        $this->assertEquals('success', $result['status']);
        $this->assertFalse($result['is_duplicate'] ?? false);

        // Check leadger_recordings table
        $recording = Recording::where('provider_call_id', $providerCallId)->first();
        $this->assertNotNull($recording, 'Recording record was not created.');
        $this->assertEquals($providerCallId, $recording->provider_call_id);
        $this->assertEquals(45, $recording->duration);
        $this->assertEquals('https://sip.telozar.com/recordings/audio_test_1.wav', $recording->recording_url);
        $this->assertEquals('connected', $recording->call_status);
        $this->assertEquals($dialerLog->id, $recording->dialer_log_id);

        // Verify dialer log was updated with recording info
        $dialerLog->refresh();
        $this->assertEquals('https://sip.telozar.com/recordings/audio_test_1.wav', $dialerLog->click_recording_url);
        $this->assertEquals(45, $dialerLog->time_taken);
        $this->assertEquals($recording->id, $dialerLog->recording_id);

        // Verify background download job was dispatched
        Queue::assertPushed(DownloadRecordingJob::class, function ($job) use ($recording) {
            return $job->recordingId === $recording->id;
        });
    }

    /**
     * Test 2: A no-answer or busy call creates no recording.
     */
    public function test_no_answer_or_busy_call_creates_no_recording()
    {
        Queue::fake([DownloadRecordingJob::class]);

        $providerCallId = 'CALL_BUSY_' . uniqid();
        $refId = 'REF_BUSY_' . uniqid();

        DialerLog::create([
            'phone_number' => '9876543211',
            'created_by' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'time_taken' => 0,
            'call_type' => 'click2call',
            'reference_id' => $refId,
            'provider_call_id' => $providerCallId,
            'created_at' => Carbon::now(),
        ]);

        $service = new CallRecordingService();

        $payload = [
            'ref_id' => $refId,
            'call_id' => $providerCallId,
            'b_party_no' => '9876543211',
            'recording_url' => '',
            'A_DIAL_STATUS' => 'Connected',
            'B_DIAL_STATUS' => 'Busy',
            'talk_duration' => 0,
            'status' => 'busy',
        ];

        $result = $service->processWebhook($payload);

        $this->assertEquals('ignored', $result['status']);

        // Assert NO recording was created
        $recording = Recording::where('provider_call_id', $providerCallId)->first();
        $this->assertNull($recording, 'Recording must not be created for busy / no-answer calls.');

        Queue::assertNothingPushed();
    }

    /**
     * Test 3: A duplicate callback creates only one record (idempotency).
     */
    public function test_duplicate_callback_creates_one_record()
    {
        Queue::fake([DownloadRecordingJob::class]);

        $providerCallId = 'CALL_DUP_' . uniqid();
        $refId = 'REF_DUP_' . uniqid();

        DialerLog::create([
            'phone_number' => '9876543212',
            'created_by' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'time_taken' => 0,
            'call_type' => 'click2call',
            'reference_id' => $refId,
            'provider_call_id' => $providerCallId,
            'created_at' => Carbon::now(),
        ]);

        $service = new CallRecordingService();

        $payload = [
            'ref_id' => $refId,
            'call_id' => $providerCallId,
            'b_party_no' => '9876543212',
            'recording_url' => 'https://sip.telozar.com/recordings/audio_dup.wav',
            'A_DIAL_STATUS' => 'Connected',
            'B_DIAL_STATUS' => 'Connected',
            'talk_duration' => 60,
            'status' => 'connected',
        ];

        // First callback
        $res1 = $service->processWebhook($payload);
        $this->assertEquals('success', $res1['status']);
        $this->assertFalse($res1['is_duplicate'] ?? false);

        // Second duplicate callback
        $res2 = $service->processWebhook($payload);
        $this->assertEquals('success', $res2['status']);
        $this->assertTrue($res2['is_duplicate'] ?? false);

        // Ensure database only contains exactly 1 row for this provider_call_id
        $count = Recording::where('provider_call_id', $providerCallId)->count();
        $this->assertEquals(1, $count, 'Duplicate callback created multiple recording rows!');
    }

    /**
     * Test 4: A late recording URL gets attached by the scheduled job.
     */
    public function test_late_recording_url_attached_by_scheduled_job()
    {
        Queue::fake([DownloadRecordingJob::class]);

        $refId = 'REF_LATE_' . uniqid();
        $providerCallId = 'CALL_LATE_' . uniqid();

        // Create a connected call from past 24h with no recording yet
        $dialerLog = DialerLog::create([
            'phone_number' => '9876543213',
            'created_by' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'time_taken' => 35,
            'duration' => '00:35',
            'call_type' => 'click2call',
            'reference_id' => $refId,
            'provider_call_id' => $providerCallId,
            'click_recording_url' => null,
            'file_path' => null,
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        // Mock provider fetcher to return the late recording
        FetchPendingRecordingsCommand::$providerFetcher = function ($passedRefId, $vmn, $crmId) use ($refId, $providerCallId) {
            if ($passedRefId === $refId) {
                return [
                    'ref_id' => $refId,
                    'call_id' => $providerCallId,
                    'recording_url' => 'https://obd.ametecsindia.com/recordings/late_rec_101.wav',
                    'status' => 'connected',
                    'talk_duration' => 35,
                    'A_DIAL_STATUS' => 'ANSWER',
                    'B_DIAL_STATUS' => 'ANSWER',
                ];
            }
            return null;
        };

        // Run the scheduled sweep (calls of the last hour that have no recording row yet)
        Artisan::call('recordings:fetch-pending', ['--hours' => 24, '--sweep' => true]);

        FetchPendingRecordingsCommand::$providerFetcher = null;

        // Verify the recording was created and attached
        $recording = Recording::where('provider_call_id', $providerCallId)->first();
        $this->assertNotNull($recording, 'Late recording was not created by the scheduled job.');
        $this->assertEquals('https://obd.ametecsindia.com/recordings/late_rec_101.wav', $recording->recording_url);

        $dialerLog->refresh();
        $this->assertEquals('https://obd.ametecsindia.com/recordings/late_rec_101.wav', $dialerLog->click_recording_url);
        $this->assertEquals($recording->id, $dialerLog->recording_id);
    }

    /**
     * Test 5: A failed download retries and then falls back to provider URL.
     */
    public function test_failed_download_retries_and_falls_back_to_provider_url()
    {
        $providerCallId = 'CALL_FAIL_' . uniqid();

        $recording = Recording::create([
            'provider_call_id' => $providerCallId,
            'call_id' => $providerCallId,
            'phone_number' => '9876543214',
            'duration' => 25,
            'recording_url' => 'http://non-existent-invalid-domain-12345.com/rec.wav',
            'download_status' => 'pending',
            'download_attempts' => 4, // 4 prior failed attempts
            'call_status' => 'connected',
        ]);

        $job = new DownloadRecordingJob($recording->id);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Handled
        }

        $recording->refresh();

        // Attempts must increment to 5 (max tries)
        $this->assertEquals(5, $recording->download_attempts);
        // Marked as failed
        $this->assertEquals('failed', $recording->download_status);
        // Fallback provider URL remains preserved for streaming
        $this->assertEquals('http://non-existent-invalid-domain-12345.com/rec.wav', $recording->recording_url);
    }
}
