<?php

namespace App\Jobs;

use App\Models\CallLog;
use App\Services\CallRecordingService;
use App\Models\DialerLog;
use App\Models\Recording;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DownloadRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [5, 15, 30, 60, 120];

    /**
     * The recording ID.
     *
     * @var int
     */
    public $recordingId;

    /**
     * Create a new job instance.
     *
     * @param int $recordingId
     */
    public function __construct(int $recordingId)
    {
        $this->recordingId = $recordingId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $recording = Recording::find($this->recordingId);
        if ($recording && $recording->download_status === 'deleted') {
            return; // deleted by an Admin
        }
        if (!$recording || empty($recording->recording_url)) {
            Log::warning("[DownloadRecordingJob] Recording #{$this->recordingId} not found or has no URL.");
            return;
        }
        if ($recording->download_status === 'completed' && CallRecordingService::realAudioPath($recording->file_path)) {
            return; // already downloaded (a duplicate / re-queued job)
        }

        $recording->download_attempts = (int)$recording->download_attempts + 1;
        $recording->download_status = 'downloading';
        $recording->save();

        $callId = $recording->provider_call_id ?: $recording->call_id;
        $attemptNum = $recording->download_attempts;

        Log::info("[DownloadRecordingJob] Download attempt {$attemptNum}/{$this->tries} started for recording #{$recording->id} (call_id: {$callId}) from URL: {$recording->recording_url}");

        try {
            $url = $recording->recording_url;

            // Extract file extension or default to wav
            $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
            $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
            if (empty($extension) || !in_array($extension, ['wav', 'mp3', 'ogg', 'm4a', 'aac'])) {
                $extension = 'wav';
            }

            $datePrefix = Carbon::parse($recording->call_date ?? Carbon::now())->format('Ymd_His');
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$recording->phone_number) ?: 'unknown';
            $filename = "{$datePrefix}_{$recording->id}_{$cleanPhone}.{$extension}";

            // Standard project storage path
            $storageDir = \App\Support\Tenancy::path(storage_path('app/recordings'));   // own folder per company
            if (!File::isDirectory($storageDir)) {
                File::makeDirectory($storageDir, 0755, true, true);
            }
            $targetPath = $storageDir . DIRECTORY_SEPARATOR . $filename;

            // Perform HTTP download using cURL with strict timeouts
            $fp = fopen($targetPath, 'w+');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $execSuccess = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if (!$execSuccess || $httpCode < 200 || $httpCode >= 400 || !File::exists($targetPath) || filesize($targetPath) === 0) {
                if (File::exists($targetPath)) {
                    File::delete($targetPath);
                }
                throw new \Exception("HTTP request failed with status {$httpCode}" . ($curlError ? ": {$curlError}" : ""));
            }

            $fileSize = filesize($targetPath);
            $recording->file_path = $targetPath;
            $recording->file_size = $fileSize;
            $recording->download_status = 'completed';

            // Last-resort duration: read it from the audio file when the provider sent no talk time
            $audioSeconds = CallRecordingService::probeAudioDuration($targetPath);
            if ((int)$recording->duration <= 0 && $audioSeconds > 0) {
                $recording->duration = $audioSeconds;
            }
            $recording->save();
            CallRecordingService::syncCallRecordDuration($recording, (int)$recording->duration);

            // Link the real file on the related dialer_log / call_log (call_logs has no file_path column)
            if ($recording->dialer_log_id) {
                DialerLog::where('id', $recording->dialer_log_id)->update([
                    'file_path' => $targetPath
                ]);
            }
            if ($recording->call_log_id) {
                CallLog::where('id', $recording->call_log_id)->update([
                    'recording' => $filename
                ]);
            }

            Log::info("[DownloadRecordingJob] Successfully downloaded recording #{$recording->id} (call_id: {$callId}) to {$targetPath} (size: {$fileSize} bytes, audio: {$audioSeconds}s)");

            // Automatic transcript when "AI Transcript" is enabled in Company Settings (the recording is already playable)
            CallRecordingService::autoTranscribe($recording);

        } catch (\Exception $e) {
            Log::error("[DownloadRecordingJob] Download failed for recording #{$recording->id} (call_id: {$callId}, attempt: {$attemptNum}): " . $e->getMessage());

            if ($this->attempts() >= $this->tries || (int)$recording->download_attempts >= $this->tries) {
                $recording->download_status = 'failed';
                $recording->save();
                Log::warning("[DownloadRecordingJob] Max attempts reached for recording #{$recording->id}. Falling back to provider URL for playback: {$recording->recording_url}");
            } else {
                throw $e; // Re-throw to trigger queue worker retry with backoff
            }
        }
    }
}
