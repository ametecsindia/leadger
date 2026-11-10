<?php

namespace App\Jobs\C2c;

use App\Models\C2c\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads a call's recording to a private disk. Same as LeadGER's DownloadRecordingJob:
 * 5 tries with back-off 5, 15, 30, 60, 120 s; 60 s timeout; file "{Ymd_His}_{call id}_{phone digits}.{ext}".
 * Needs a queue worker (php artisan queue:work).
 */
class DownloadRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 5;
    public $backoff = [5, 15, 30, 60, 120];
    public $timeout = 120;

    public function __construct(public int $callId)
    {
    }

    public function handle(): void
    {
        $call = Call::find($this->callId);
        if (!$call || !$call->recording_url || $call->recording_status === 'completed') {
            return;
        }
        $call->increment('recording_attempts');

        $res = Http::timeout(60)->connectTimeout(15)->get($call->recording_url);
        if (!$res->successful() || $res->body() === '') {
            throw new \RuntimeException('Recording not available yet (HTTP ' . $res->status() . ')');   // retried with back-off
        }

        $ext = strtolower(pathinfo((string) parse_url($call->recording_url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $ext = in_array($ext, ['wav', 'mp3', 'ogg', 'm4a', 'aac'], true) ? $ext : 'wav';
        $path = 'c2c-recordings/' . ($call->started_at ?? $call->created_at)->format('Ymd_His') . '_' . $call->id . '_' . $call->customer_number . '.' . $ext;
        Storage::disk(config('click2call.recording_disk'))->put($path, $res->body());

        $call->update(['recording_path' => $path, 'recording_status' => 'completed']);
    }

    public function failed(\Throwable $e): void
    {
        Call::whereKey($this->callId)->update(['recording_status' => 'failed']);
        Log::warning('[Click2Call] recording download failed', ['call_id' => $this->callId, 'error' => $e->getMessage()]);
    }
}
