<?php

namespace App\Services;

use App\Models\CallLog;
use Illuminate\Support\Facades\Log;

/**
 * The audio of one Recordings page row (bulk ZIP download, copy to the bucket): a local file inside the recordings
 * folders (found the same way the single download does, including files whose stored path still points at an old
 * install folder), else the bucket copy, else the provider's copy.
 */
class RecordingAudio
{
    /**
     * @param bool $useBucket false when copying to the bucket (never read back what is being written)
     * @return array ['label', 'name', 'path'|'contents'] or ['label', 'error']
     */
    public static function find(int $sourceCode, int $id, bool $useBucket = true): array
    {
        $label = ($sourceCode == 2 ? 'Dialing' : ($sourceCode == 3 ? 'Recording' : 'Campaign call')) . " #{$id}";
        $callLog = $dialerLog = $recording = null;
        if ($sourceCode == 2) {
            $dialerLog = \App\Models\DialerLog::find($id);
            $recording = $dialerLog && $dialerLog->recording_id ? \App\Models\Recording::find($dialerLog->recording_id) : null;
            if (!$dialerLog) return ['label' => $label, 'error' => 'recording not found'];
        } elseif ($sourceCode == 3) {
            $recording = \App\Models\Recording::whereNull('call_log_id')->whereNull('dialer_log_id')->find($id);
            if (!$recording) return ['label' => $label, 'error' => 'recording not found'];
        } else {
            $callLog = CallLog::find($id);
            $recording = $callLog && $callLog->recording_id ? \App\Models\Recording::find($callLog->recording_id) : null;
            if (!$callLog) return ['label' => $label, 'error' => 'recording not found'];
        }
        if ($recording && $recording->download_status === 'deleted') {
            return ['label' => $label, 'error' => 'this recording was deleted'];
        }

        $stored = array_values(array_filter([
            $recording->file_path ?? null,
            $dialerLog->file_path ?? null,
            $callLog->file_path ?? null,
            $callLog->recording ?? null,
        ], fn ($p) => is_string($p) && trim($p) !== '' && !preg_match('#^https?://#i', $p)));
        $details = (new \App\Services\TranscriptionBillingService())->getRecordingDetails($sourceCode, $id);

        // Only real audio inside the recordings folders is ever read (no path from the database can reach other files)
        $candidates = array_filter([$details['audio_path'] ?? null]);
        $uploadBase = rtrim((string)env('recordings_upload'), '/\\');
        foreach ($stored as $p) {
            $base = basename(str_replace('\\', '/', $p));
            array_push($candidates, $p, \App\Support\Tenancy::path(storage_path('app/recordings')) . DIRECTORY_SEPARATOR . $base, $uploadBase ? $uploadBase . '/' . $base : null);
        }
        foreach (array_filter($candidates) as $candidate) {
            $real = CallRecordingService::realAudioPath($candidate);
            if ($real && CallRecordingService::isInsideRecordingsFolder($real)) {
                $real = realpath($real);
                return ['label' => $label, 'name' => basename($real), 'path' => $real];
            }
        }

        // Copy in the bucket
        if ($useBucket && ($row = GcsBucket::find($sourceCode, $id))) {
            try {
                $body = GcsBucket::contents($row->object);
                if ($body !== null && $body !== '') {
                    return ['label' => $label, 'name' => basename($row->object), 'contents' => $body];
                }
            } catch (\Throwable $e) {
                Log::warning("[RecordingZip] {$label}: bucket download failed: " . $e->getMessage());
            }
        }

        // Not on this server: fetch the provider's copy (the single download redirects to the same URL)
        $url = null;
        foreach ([$recording->recording_url ?? null, $dialerLog->click_recording_url ?? null, $callLog->click_recording_url ?? null, $callLog->recording ?? null] as $u) {
            if ($url = CallRecordingService::validRecordingUrl($u)) break;
        }
        if ($url) {
            try {
                // Certificate checking stays on, using the CA bundle shipped with the app when PHP has none configured
                $caBundle = public_path('ssl/cacert.pem');
                $http = \Illuminate\Support\Facades\Http::timeout(30);
                if (is_file($caBundle)) {
                    $http = $http->withOptions(['verify' => $caBundle]);
                }
                $res = $http->get($url);
                $body = $res->successful() ? $res->body() : '';
                $type = strtolower((string)$res->header('Content-Type'));
                if ($body !== '' && strlen($body) <= 200 * 1024 * 1024 && !str_contains($type, 'text/html')) {
                    $name = $stored ? basename(str_replace('\\', '/', $stored[0])) : basename((string)parse_url($url, PHP_URL_PATH));
                    return ['label' => $label, 'name' => $name ?: "recording_{$sourceCode}_{$id}.wav", 'contents' => $body];
                }
                Log::warning("[RecordingZip] {$label}: provider returned HTTP {$res->status()} for {$url}");
            } catch (\Throwable $e) {
                Log::warning("[RecordingZip] {$label}: provider download failed: " . $e->getMessage());
            }
            return ['label' => $label, 'error' => 'audio file is missing on the server and could not be fetched from the provider'];
        }
        return ['label' => $label, 'error' => 'audio file is missing'];
    }
}
