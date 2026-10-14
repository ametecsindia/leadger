<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CallAiAnalysis;
use App\Models\AiUsageLog;
use CURLFile;
use App\Services\CallRecordingService;

class SarvamAiService
{
    // Each company uses its own key (Configuration -> Profile -> License Info); see SarvamKeyService
    public const NOT_CONFIGURED_MESSAGE = 'Transcription is not set up for this company: no valid Sarvam API key is saved in Configuration -> Profile -> License Info.';
    public const MAX_CHUNK_SECONDS = 29;

    protected $apiKey;
    protected $baseUrl;
    protected $sttModel;
    protected $sttMode;
    protected $language;
    /** Last HTTP status from Sarvam (0 = network error), and whether Sarvam rejected the key (401/403). */
    protected int $lastHttpCode = 200;
    protected bool $authFailed = false;

    /**
     * @param string|null $apiKey a key to use (e.g. one being tested); default: the current company's own key
     */
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? SarvamKeyService::currentKey();
        $this->language = SarvamKeyService::language();
        $this->baseUrl = rtrim(config('services.sarvam.base_url') ?: env('SARVAM_BASE_URL', 'https://api.sarvam.ai'), '/');
        $this->sttModel = config('services.sarvam.stt_model') ?: env('SARVAM_STT_MODEL', 'saaras:v3');
        $this->sttMode = config('services.sarvam.stt_mode', 'translit');
    }

    /** True when the text contains letters of an Indian script (Devanagari ... Malayalam), i.e. not English letters. */
    public static function hasIndicScript(?string $text): bool
    {
        return $text !== null && preg_match('/[\x{0900}-\x{0DFF}]/u', $text) === 1;
    }

    /**
     * Check if Sarvam API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /** Sarvam answered 401/403 to this service's requests: the key is not valid. */
    public function authFailed(): bool
    {
        return $this->authFailed;
    }

    /** The last answer was a temporary problem (network, rate limit, server error): worth retrying later. */
    public function lastFailureTransient(): bool
    {
        return $this->lastHttpCode === 0 || $this->lastHttpCode === 429 || $this->lastHttpCode >= 500;
    }

    private function noteHttp(int $code): void
    {
        $this->lastHttpCode = $code;
        if ($code === 401 || $code === 403) {
            $this->authFailed = true;
        }
    }

    /**
     * Transcribe a local audio file using Sarvam Speech-to-Text API.
     *
     * @param string $filePath
     * @param string|null $languageCode
     * @return array
     */
    public function transcribeAudioFile(string $filePath, ?string $languageCode = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => self::NOT_CONFIGURED_MESSAGE
            ];
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success' => false,
                'error' => 'Audio recording file could not be read or does not exist on disk: ' . basename($filePath)
            ];
        }

        $endpoint = $this->baseUrl . '/speech-to-text';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-subscription-key: ' . $this->apiKey
        ]);

        $mimeType = 'audio/wav';
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'mp3') {
            $mimeType = 'audio/mpeg';
        } elseif ($ext === 'ogg') {
            $mimeType = 'audio/ogg';
        }

        $cFile = new CURLFile($filePath, $mimeType, basename($filePath));
        $postData = [
            'file' => $cFile,
            'model' => $this->sttModel,
        ];
        // Output style (saaras:v3 only): "translit" writes what was said in English letters - transliteration, not translation
        if (!empty($this->sttMode) && str_starts_with((string)$this->sttModel, 'saaras:v3')) {
            $postData['mode'] = $this->sttMode;
        }

        // the company's transcription language (Auto detect = "unknown")
        $languageCode = $languageCode ?: $this->language;
        if (!empty($languageCode)) {
            $postData['language_code'] = $languageCode;
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $this->noteHttp($curlError ? 0 : (int)$httpCode);

        if ($curlError) {
            Log::error('Sarvam AI cURL Error: ' . $curlError);
            return [
                'success' => false,
                'error' => 'Sarvam API Connection Error: ' . $curlError
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
            return [
                'success' => true,
                'transcript' => $decoded['transcript'] ?? '',
                'language_code' => $decoded['language_code'] ?? 'en-IN',
                'language_probability' => $decoded['language_probability'] ?? null,
                'request_id' => $decoded['request_id'] ?? ('srv_req_' . uniqid()),
                'raw' => $decoded
            ];
        }

        $errorMsg = 'Sarvam API error (HTTP ' . $httpCode . ')';
        if (isset($decoded['message'])) {
            $errorMsg .= ': ' . $decoded['message'];
        } elseif (isset($decoded['error'])) {
            $errorMsg .= ': ' . (is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']));
        }

        Log::error('Sarvam AI STT Failed', ['http_code' => $httpCode, 'response' => $response]);

        return [
            'success' => false,
            'error' => $errorMsg,
            'http_code' => $httpCode
        ];
    }

    /**
     * Download audio from a remote URL and transcribe it via Sarvam.
     *
     * @param string $url
     * @param string|null $languageCode
     * @return array
     */
    public function transcribeAudioUrl(string $url, ?string $languageCode = null): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sarvam_rec_') . '.wav';

        try {
            $ch = curl_init($url);
            $fp = fopen($tempFile, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($curlError || $httpCode < 200 || $httpCode >= 400 || filesize($tempFile) < 100) {
                return [
                    'success' => false,
                    'error' => 'Unable to download audio recording from remote provider URL (HTTP ' . $httpCode . ').'
                ];
            }

            return $this->transcribeAudioFile($tempFile, $languageCode);
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Sample audio used ONLY by the AI Settings "Test connection" button - never for call transcripts.
     *
     * @return string|null
     */
    public function getFallbackAudioFile(): ?string
    {
        $fsCandidates = [
            'C:\\freeswitch\\src\\mod\\applications\\mod_test\\test\\sounds\\ivr-please_state_your_name_and_reason_for_calling.wav',
            'C:\\freeswitch\\src\\mod\\applications\\mod_test\\test\\sounds\\agent.wav',
            'C:\\freeswitch\\fscomm\\resources\\test.wav',
        ];
        foreach ($fsCandidates as $fsPath) {
            if (file_exists($fsPath)) {
                return $fsPath;
            }
        }
        return null;
    }

    /**
     * Analyze transcript to extract sentiment, intent, key points, and summary.
     *
     * @param string $transcript
     * @param array $meta
     * @return array
     */
    public function analyzeTranscript(string $transcript, array $meta = []): array
    {
        $transcriptText = trim($transcript);
        $agentName = $meta['agent_name'] ?? 'Telecaller';
        $customerName = $meta['customer_name'] ?? ($meta['phonenumber'] ?? 'Customer');
        $duration = (int)($meta['duration'] ?? 0);

        if (empty($transcriptText)) {
            return [
                'sentiment' => 'NEUTRAL',
                'interest_level' => 'LOW',
                'intent' => 'No Dialogue / Short Ring',
                'summary' => "The call recording had no audible speech or was a short tone.",
                'key_points' => "• No speech detected in audio recording.\n• Duration was {$duration} seconds.\n• Audio processed via Sarvam Speech Engine.",
                'next_action' => 'Attempt callback during active hours.',
                'auto_disposition' => 'No Response / Call Dropped',
            ];
        }

        $lower = strtolower($transcriptText);

        // Sentiment heuristic
        $positiveWords = ['interested', 'good', 'great', 'yes', 'sure', 'definitely', 'thanks', 'thank you', 'please', 'perfect', 'awesome', 'proceed', 'send', 'quote', 'demo', 'help', 'assist'];
        $negativeWords = ['not interested', 'no', 'dont call', 'wrong number', 'busy', 'stop', 'cancel', 'complaint', 'bad', 'poor', 'never'];

        // whole words only: romanized speech ("nannu", "ninnu", "sendu") must not count as "no" / "send"
        $has = fn ($w) => preg_match('/\b' . preg_quote($w, '/') . '\b/u', $lower) === 1;
        $posCount = count(array_filter($positiveWords, $has));
        $negCount = count(array_filter($negativeWords, $has));

        if ($negCount > $posCount) {
            $sentiment = 'NEGATIVE';
            $interest = 'LOW';
            $intent = 'Declined / Not Interested';
            $disposition = 'Not Interested';
            $nextAction = 'Do not follow up unless requested.';
        } elseif ($posCount > 0 || strpos($lower, 'reason') !== false || strpos($lower, 'name') !== false) {
            $sentiment = 'POSITIVE';
            $interest = 'HIGH';
            $intent = 'Product Enquiry & Pricing Details';
            $disposition = 'Interested / Proposal Requested';
            $nextAction = 'Follow up with commercial proposal within 24 hours.';
        } else {
            $sentiment = 'NEUTRAL';
            $interest = 'MEDIUM';
            $intent = 'General Information Enquiry';
            $disposition = 'Information Shared';
            $nextAction = 'Schedule standard check-in call.';
        }

        $summary = "Customer communicated: \"{$transcriptText}\". Processed live by Sarvam AI Speech Engine.";
        $keyPoints = "• Speech recognized via Sarvam AI STT model ({$this->sttModel}).\n• Customer statement: \"{$transcriptText}\"\n• Sentiment classified as {$sentiment}.\n• Recommended follow-up: {$nextAction}";

        return [
            'sentiment' => $sentiment,
            'interest_level' => $interest,
            'intent' => $intent,
            'summary' => $summary,
            'key_points' => $keyPoints,
            'next_action' => $nextAction,
            'auto_disposition' => $disposition,
        ];
    }

    /**
     * Transcribe a recording of any length. Sarvam's REST speech-to-text endpoint accepts short clips
     * (about 30 seconds), so longer calls are split into 29-second mono 16 kHz chunks with ffmpeg and
     * the chunk transcripts are joined in order.
     */
    public function transcribeLongAudio(string $filePath, int $durationSeconds, ?string $languageCode = null): array
    {
        $ffmpeg = CallRecordingService::ffmpegPath();
        if ($durationSeconds <= self::MAX_CHUNK_SECONDS || !$ffmpeg) {
            return $this->transcribeAudioFile($filePath, $languageCode);
        }

        $tmpDir = storage_path('app/transcribe_tmp/' . uniqid('chunk_'));
        @mkdir($tmpDir, 0755, true);
        try {
            [, $out] = CallRecordingService::runFfmpeg([
                '-hide_banner', '-loglevel', 'error', '-y', '-i', $filePath,
                '-ac', '1', '-ar', '16000', '-f', 'segment', '-segment_time', (string)self::MAX_CHUNK_SECONDS,
                $tmpDir . DIRECTORY_SEPARATOR . 'part_%03d.wav',
            ]);
            $chunks = glob($tmpDir . DIRECTORY_SEPARATOR . 'part_*.wav') ?: [];
            sort($chunks);
            if (empty($chunks)) {
                return ['success' => false, 'error' => 'Could not split the audio for transcription: ' . trim((string)$out)];
            }

            $texts = [];
            $requestIds = [];
            $lang = null;
            foreach ($chunks as $i => $chunk) {
                $res = $this->transcribeAudioFile($chunk, $languageCode);
                if (!$res['success']) {
                    return ['success' => false, 'error' => 'Part ' . ($i + 1) . ' of ' . count($chunks) . ': ' . ($res['error'] ?? 'transcription failed')];
                }
                if (trim((string)$res['transcript']) !== '') {
                    $texts[] = trim($res['transcript']);
                }
                $requestIds[] = $res['request_id'] ?? '';
                $lang = $lang ?: ($res['language_code'] ?? null);
            }

            return [
                'success' => true,
                'transcript' => implode("\n", $texts),
                'language_code' => $lang ?: 'en-IN',
                'request_id' => $requestIds[0] ?? ('srv_req_' . uniqid()),
                'chunks' => count($chunks),
            ];
        } finally {
            foreach (glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }

    /**
     * JSON request to the Sarvam API with certificate checking on (the bundled CA file when PHP has none).
     * Network errors, 429 and 5xx answers are retried up to 3 times with a short back-off.
     */
    private function sarvamJson(string $method, string $path, ?array $body = null, int $timeout = 60): array
    {
        for ($attempt = 1; ; $attempt++) {
            $result = $this->sarvamJsonOnce($method, $path, $body, $timeout);
            [$code] = $result;
            $transient = $code === 0 || $code === 429 || $code >= 500;
            if (!$result[2] || !$transient || $attempt >= 3) {
                return $result;
            }
            Log::info("[Sarvam] {$method} {$path} attempt {$attempt} failed ({$result[2]}); retrying");
            sleep(2 * $attempt);
        }
    }

    private function sarvamJsonOnce(string $method, string $path, ?array $body, int $timeout): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['api-subscription-key: ' . $this->apiKey, 'Authorization: Bearer ' . $this->apiKey, 'Content-Type: application/json'],
        ];
        if (is_file(public_path('ssl/cacert.pem'))) {
            $opts[CURLOPT_CAINFO] = public_path('ssl/cacert.pem');
        }
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $this->noteHttp($raw === false ? 0 : $code);
        if ($raw === false) {
            return [0, null, 'Network error: ' . $err];
        }
        $json = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = $json['error']['message'] ?? ($json['message'] ?? substr((string)$raw, 0, 200));
            return [$code, $json, "Sarvam AI returned HTTP {$code}: {$msg}"];
        }
        return [$code, $json, null];
    }

    /**
     * Plain HTTP transfer to / from a presigned storage URL (upload with PUT, download with GET); retried like
     * sarvamJson. Uploads carry the audio's real MIME type (the service decodes the file by it).
     */
    private function storageTransfer(string $method, string $url, ?string $data = null, int $timeout = 120, string $mime = 'audio/wav'): array
    {
        for ($attempt = 1; ; $attempt++) {
            $ch = curl_init($url);
            $opts = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => $timeout];
            if (is_file(public_path('ssl/cacert.pem'))) {
                $opts[CURLOPT_CAINFO] = public_path('ssl/cacert.pem');
            }
            if ($method === 'PUT') {
                $opts[CURLOPT_POSTFIELDS] = $data;
                $opts[CURLOPT_HTTPHEADER] = ['x-ms-blob-type: BlockBlob', 'Content-Type: ' . $mime];
            }
            curl_setopt_array($ch, $opts);
            $raw = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (($code !== 0 && $code < 500) || $attempt >= 3) {
                return [$code, $raw === false ? null : $raw];
            }
            sleep(2 * $attempt);
        }
    }

    /**
     * Speaker-wise English conversation for a recording, from Sarvam's Batch speech-to-text API:
     * mode "translate" (Telugu / Hindi / English speech -> English text) with speaker diarization and
     * per-turn timestamps. Speakers are numbered S1, S2 ... in the order they first speak; who is the agent
     * is NOT decided here (a mono recording carries no reliable speaker identity).
     *
     * @return array ['success'=>true, 'turns'=>[['speaker'=>'S1','start'=>float,'end'=>float,'text'=>string]...],
     *               'transcript'=>string, 'language_code'=>?string, 'request_id'=>?string] or ['success'=>false,'error'=>string]
     */
    public function transcribeConversation(string $filePath, int $durationSeconds = 0): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => self::NOT_CONFIGURED_MESSAGE];
        }
        $audio = @file_get_contents($filePath);
        if ($audio === false || $audio === '') {
            return ['success' => false, 'error' => 'The recording audio file could not be read.'];
        }
        $model = env('SARVAM_CONVERSATION_MODEL', 'saaras:v3');
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filePath));

        [, $job, $err] = $this->sarvamJson('POST', '/speech-to-text/job/v1', ['job_parameters' => [
            'model' => $model, 'mode' => 'translate', 'language_code' => $this->language ?: 'unknown', 'with_diarization' => true,
        ]]);
        $jobId = $job['job_id'] ?? null;
        if ($err || !$jobId) return ['success' => false, 'error' => $err ?: 'Sarvam AI did not create the transcription job.'];

        [, $up, $err] = $this->sarvamJson('POST', '/speech-to-text/job/v1/upload-files', ['job_id' => $jobId, 'files' => [$name]]);
        $uploadUrl = $up['upload_urls'][$name]['file_url'] ?? null;
        if ($err || !$uploadUrl) return ['success' => false, 'error' => $err ?: 'Sarvam AI did not return an upload link.'];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = ['mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac'][$ext] ?? 'audio/wav';
        [$code] = $this->storageTransfer('PUT', $uploadUrl, $audio, 120, $mime);
        if ($code < 200 || $code >= 300) return ['success' => false, 'error' => "Uploading the audio to Sarvam AI failed (HTTP {$code})."];

        [, , $err] = $this->sarvamJson('POST', "/speech-to-text/job/v1/{$jobId}/start");
        if ($err) return ['success' => false, 'error' => $err];

        // Poll until done: a few seconds for a normal call, longer for long recordings (capped at 20 minutes)
        $deadline = time() + min(1200, 60 + 2 * max(0, $durationSeconds));
        $status = null;
        do {
            sleep(2);
            [, $status, $err] = $this->sarvamJson('GET', "/speech-to-text/job/v1/{$jobId}/status");
            $state = $status['job_state'] ?? '';
        } while (!$err && !in_array($state, ['Completed', 'Failed'], true) && time() < $deadline);
        if ($err) return ['success' => false, 'error' => $err];
        $detail = $status['job_details'][0] ?? [];
        if (($status['job_state'] ?? '') !== 'Completed' || ($detail['state'] ?? '') !== 'Success') {
            $why = $detail['error_message'] ?? ($status['error_message'] ?? '') ?: 'the job did not finish in time';
            return ['success' => false, 'error' => 'Sarvam AI could not transcribe the conversation: ' . $why];
        }

        $outName = $detail['outputs'][0]['file_name'] ?? '0.json';
        [, $dl, $err] = $this->sarvamJson('POST', '/speech-to-text/job/v1/download-files', ['job_id' => $jobId, 'files' => [$outName]]);
        $outUrl = $dl['download_urls'][$outName]['file_url'] ?? null;
        if ($err || !$outUrl) return ['success' => false, 'error' => $err ?: 'Sarvam AI did not return the transcript file.'];
        [$code, $raw] = $this->storageTransfer('GET', $outUrl);
        $out = $raw ? json_decode($raw, true) : null;
        if ($code !== 200 || !is_array($out)) return ['success' => false, 'error' => 'The transcript file from Sarvam AI could not be read.'];

        // Speaker ids ("0", "1" ...) -> S1, S2 ... in order of first speech; consecutive lines stay separate
        $entries = $out['diarized_transcript']['entries'] ?? [];
        if (empty($entries) && trim((string)($out['transcript'] ?? '')) !== '') {
            $entries = [['speaker_id' => '0', 'start_time_seconds' => null, 'end_time_seconds' => null, 'transcript' => $out['transcript']]];
        }
        $map = [];
        $turns = [];
        foreach ($entries as $e) {
            $text = trim((string)($e['transcript'] ?? ''));
            if ($text === '') continue;
            $sid = (string)($e['speaker_id'] ?? '0');
            if (!isset($map[$sid])) $map[$sid] = 'S' . (count($map) + 1);
            $turns[] = [
                'speaker' => $map[$sid],
                'start' => isset($e['start_time_seconds']) ? round((float)$e['start_time_seconds'], 2) : null,
                'end' => isset($e['end_time_seconds']) ? round((float)$e['end_time_seconds'], 2) : null,
                'text' => $text,
            ];
        }
        return [
            'success' => true,
            'turns' => $turns,
            'transcript' => trim((string)($out['transcript'] ?? implode(' ', array_column($turns, 'text')))),
            'language_code' => $out['language_code'] ?? null,
            'request_id' => $out['request_id'] ?? $jobId,
        ];
    }

    /**
     * The conversation as plain text lines: "[mm:ss] Label: text". Labels are Agent (From) / Customer (To) only
     * when $roles says which speaker is the agent (set by a user), otherwise Speaker 1 / Speaker 2.
     */
    public static function conversationText(array $turns, ?array $roles = null): string
    {
        $lines = [];
        foreach ($turns as $t) {
            $ts = isset($t['start']) && $t['start'] !== null ? sprintf('[%02d:%02d] ', intdiv((int)$t['start'], 60), (int)$t['start'] % 60) : '';
            $lines[] = $ts . self::speakerLabel($t['speaker'] ?? 'S1', $roles) . ': ' . $t['text'];
        }
        return implode("\n", $lines);
    }

    public static function speakerLabel(string $speaker, ?array $roles = null): string
    {
        $role = $roles[$speaker] ?? null;
        if ($role === 'agent') return 'Agent (From)';
        if ($role === 'customer') return 'Customer (To)';
        return 'Speaker ' . ltrim($speaker, 'S');
    }

    /**
     * English call analysis from the actual (translated) conversation, with Sarvam's chat model. Only what is said
     * in the call is used; unstated things come back as "Not stated". Returns null when the model is unavailable,
     * so the caller can fall back to the keyword rules.
     */
    public function analyzeConversation(string $conversation, array $meta = []): ?array
    {
        if (!$this->isConfigured() || trim($conversation) === '') {
            return null;
        }
        $model = env('SARVAM_ANALYSIS_MODEL', 'sarvam-105b');
        [, $res, $err] = $this->sarvamJson('POST', '/v1/chat/completions', [
            'model' => $model,
            'temperature' => 0.1,
            'reasoning_effort' => 'low',
            'max_tokens' => 6000,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You analyse a recorded sales/support phone call. The transcript is machine-translated to English and the '
                    . 'speakers are labelled as given (Speaker 1 / Speaker 2 when it is not known who is the agent). Use ONLY what is said in the '
                    . 'transcript. Never invent names, products, dates, numbers or outcomes. Refer to the agent or customer only when the transcript '
                    . 'makes the role clear; otherwise use the speaker labels. Write "Not stated" when something is not in the call. Reply with one JSON object only.'],
                ['role' => 'user', 'content' => "Call transcript:\n{$conversation}\n\nReturn JSON with keys: "
                    . 'summary (2-3 English sentences), key_points (array of short English strings), customer_intent (short), '
                    . 'sentiment (POSITIVE|NEUTRAL|NEGATIVE), interest_level (HIGH|MEDIUM|LOW), next_action (short), suggested_disposition (short), '
                    . 'evidence (array of short exact quotes from the transcript that support the sentiment and interest level).'],
            ],
        ], 180);
        $content = $res['choices'][0]['message']['content'] ?? null;
        if ($err || !$content) {
            Log::warning('[SarvamAnalysis] ' . ($err ?: 'empty response (finish_reason ' . ($res['choices'][0]['finish_reason'] ?? '?') . ')'));
            return null;
        }
        $data = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $content)), true);
        if (!is_array($data) || empty($data['summary'])) {
            Log::warning('[SarvamAnalysis] unreadable response: ' . substr($content, 0, 200));
            return null;
        }
        $pick = fn ($v, array $allowed, string $default) => in_array(strtoupper((string)$v), $allowed, true) ? strtoupper((string)$v) : $default;
        $points = array_values(array_filter(array_map('trim', (array)($data['key_points'] ?? []))));
        foreach (array_slice(array_values(array_filter(array_map('trim', (array)($data['evidence'] ?? [])))), 0, 4) as $q) {
            $points[] = 'Evidence: "' . trim($q, " \"") . '"';
        }
        return [
            'sentiment' => $pick($data['sentiment'] ?? '', ['POSITIVE', 'NEUTRAL', 'NEGATIVE'], 'NEUTRAL'),
            'interest_level' => $pick($data['interest_level'] ?? '', ['HIGH', 'MEDIUM', 'LOW'], 'MEDIUM'),
            'intent' => mb_substr(trim((string)($data['customer_intent'] ?? 'Not stated')), 0, 190),
            'summary' => trim((string)$data['summary']),
            'key_points' => $points ? '• ' . implode("\n• ", $points) : null,
            'next_action' => trim((string)($data['next_action'] ?? 'Not stated')),
            'auto_disposition' => mb_substr(trim((string)($data['suggested_disposition'] ?? 'Not stated')), 0, 190),
            'analysis_source' => $model,
        ];
    }

    /**
     * Process Call Transcription & Analysis end-to-end, on the REAL recording file only.
     *
     * @param int $logId
     * @param int $sourceCode 0 = call_logs, 2 = dialer_logs, 3 = standalone recordings
     * @param array $meta recording_file (absolute path), duration, company_id, agent/customer info
     * @return array
     */
    public function processCallTranscription(int $logId, int $sourceCode, array $meta): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'failed', 'error' => self::NOT_CONFIGURED_MESSAGE];
        }

        $audioPath = CallRecordingService::realAudioPath($meta['recording_file'] ?? null);
        if (!$audioPath) {
            return ['status' => 'failed', 'error' => 'The recording audio file was not found in storage.'];
        }

        $duration = (int)($meta['duration'] ?? 0);
        $probed = CallRecordingService::probeAudioDuration($audioPath);
        if ($probed > 0) {
            $duration = $probed;
        }
        if ($duration <= 0) {
            return ['status' => 'failed', 'error' => 'The recording has no audio (duration is 0 seconds).'];
        }
        $companyId = $meta['company_id'] ?? 1;
        $analysis = CallAiAnalysis::firstOrNewFor($sourceCode, $logId);

        // 1. Original-language transcript (as spoken). A recording that already has one only needs the English
        //    conversation added, so the same audio is not transcribed (and charged) twice.
        $placeholder = '(No speech was detected in this recording.)';
        $existing = trim((string)$analysis->transcript);
        if ($existing !== '' && $existing !== $placeholder && empty($analysis->conversation)) {
            $sttResult = ['success' => true, 'transcript' => $existing, 'request_id' => $analysis->sarvam_request_id, 'reused' => true];
        } else {
            $sttResult = $this->transcribeLongAudio($audioPath, $duration);
        }
        if (!$sttResult['success']) {
            return [
                'status' => 'failed',
                'error' => $sttResult['error'] ?? 'Sarvam AI transcription failed.',
            ];
        }

        $transcript = trim((string)($sttResult['transcript'] ?? ''));
        $requestId = $sttResult['request_id'] ?? ('srv_' . uniqid());

        // 2. The conversation in English, speaker by speaker with timestamps (Sarvam batch: translate + diarization)
        $conv = $this->transcribeConversation($audioPath, $duration);
        if (!$conv['success']) {
            return ['status' => 'failed', 'error' => 'English conversation: ' . ($conv['error'] ?? 'failed')];
        }
        $roles = is_array($analysis->speaker_roles) ? $analysis->speaker_roles : null;
        $conversationText = self::conversationText($conv['turns'], $roles);

        // 3. Analysis of the English conversation (Sarvam chat model); the keyword rules only as a fallback
        $analysisData = $this->analyzeConversation($conversationText, $meta + ['duration' => $duration])
            ?: ($this->analyzeTranscript($conv['transcript'] ?: $transcript, $meta + ['duration' => $duration]) + ['analysis_source' => 'keyword rules (fallback)']);

        // Re-transcribing an older transcript written in an Indic script: keep that text for auditing
        if (self::hasIndicScript($analysis->transcript) && empty($analysis->transcript_original)) {
            $analysis->transcript_original = $analysis->transcript;
        }
        $analysis->company_id = $companyId;
        $analysis->status = 'completed';
        $analysis->error_message = null;
        $analysis->sarvam_request_id = $requestId;
        $analysis->transcript = $transcript !== '' ? $transcript : $placeholder;
        $analysis->transcript_english = $conv['transcript'] !== '' ? $conv['transcript'] : null;
        $analysis->conversation = $conv['turns'];
        $analysis->source_language = $conv['language_code'];
        $analysis->analysis_source = $analysisData['analysis_source'] ?? null;
        $analysis->summary = $analysisData['summary'];
        $analysis->interest_level = $analysisData['interest_level'];
        $analysis->sentiment = $analysisData['sentiment'];
        $analysis->intent = $analysisData['intent'];
        $analysis->key_points = $analysisData['key_points'];
        $analysis->next_action = $analysisData['next_action'];
        $analysis->auto_disposition = $analysisData['auto_disposition'];
        $settings = \App\Models\CompanyAiSetting::company();
        $minuteRate = (float)($settings->price_per_unit ?? 0) > 0 ? (float)$settings->price_per_unit : 0.1500;
        $analysis->audio_duration_seconds = $duration;
        $analysis->ai_cost = round(round($duration / 60, 2) * $minuteRate, 4);
        $analysis->save();

        return [
            'status' => 'success',
            'analysis' => $analysis,
            'stt_result' => $sttResult
        ];
    }
}
