<?php

namespace Tests\Feature;

use App\Services\SarvamAiService;
use Tests\TestCase;

/** Recordings Sarvam's REST speech-to-text refuses as too long (over 30 s) are transcribed through the batch API. */
class SarvamLongAudioTest extends TestCase
{
    private function service(array $rest): SarvamAiService
    {
        return new class($rest) extends SarvamAiService {
            public array $batchModes = [];
            public function __construct(private array $rest) { parent::__construct('test-key'); }
            public function transcribeAudioFile(string $filePath, ?string $languageCode = null): array { return $this->rest; }
            public function transcribeConversation(string $filePath, int $durationSeconds = 0, string $mode = 'translate'): array
            {
                $this->batchModes[] = $mode;
                return ['success' => true, 'turns' => [], 'transcript' => 'batch text', 'language_code' => 'te-IN', 'request_id' => 'job-1'];
            }
        };
    }

    public function test_a_clip_refused_as_too_long_is_sent_to_the_batch_api_in_the_rest_mode()
    {
        $svc = $this->service(['success' => false, 'error' => 'Sarvam API error (HTTP 400): {"message":"Audio duration exceeds the maximum limit of 30 seconds. Please use the batch API for longer audio files."}']);
        $res = $svc->transcribeLongAudio(__FILE__, 20);   // stored duration said 20 s

        $this->assertTrue($res['success']);
        $this->assertSame('batch text', $res['transcript']);
        $this->assertSame([config('services.sarvam.stt_mode', 'translit')], $svc->batchModes, 'same output style as the REST transcript');
    }

    public function test_short_clips_still_use_the_rest_api_and_other_errors_are_reported()
    {
        $ok = $this->service(['success' => true, 'transcript' => 'rest text', 'language_code' => 'te-IN', 'request_id' => 'r1']);
        $this->assertSame('rest text', $ok->transcribeLongAudio(__FILE__, 20)['transcript']);
        $this->assertSame([], $ok->batchModes);

        $bad = $this->service(['success' => false, 'error' => 'Sarvam API error (HTTP 401): invalid key']);
        $this->assertFalse($bad->transcribeLongAudio(__FILE__, 20)['success']);
        $this->assertSame([], $bad->batchModes, 'only "too long" falls back to the batch API');
    }
}
