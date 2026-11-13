<?php

namespace App\Console\Commands;

use App\Services\GcsBucket;
use App\Services\RecordingAudio;
use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Copy call recordings into the Google Cloud Storage bucket (see App\Services\GcsBucket), for the current company:
 *   php artisan tenants:each "recordings:to-bucket"              recordings of the last 48 hours (scheduled)
 *   php artisan tenants:each "recordings:to-bucket --all"        also every older recording (one-time copy)
 * The audio is taken from the server folder or, when it is not there, from the provider's link. Originals are never
 * changed or deleted. A failed copy is tried again after 15 minutes, at most 5 times.
 */
class RecordingsToBucketCommand extends Command
{
    protected $signature = 'recordings:to-bucket {--all : also every older recording} {--hours=48 : how far back without --all}
        {--limit=300 : at most this many per run (0 = no limit)}';
    protected $description = 'Copy call recordings into the Google Cloud Storage bucket';

    public function handle(): int
    {
        if (!GcsBucket::enabled()) {
            $this->line('Bucket not set up (GCS_RECORDINGS_BUCKET / GCS_KEY_FILE): nothing to do.');
            return self::SUCCESS;
        }
        GcsBucket::ensureTable();
        $folder = $this->crmFolder();

        $limit = (int)$this->option('limit');
        $since = $this->option('all') ? null : now()->subHours(max(1, (int)$this->option('hours')));
        $done = $failed = 0;
        foreach ([0, 2, 3] as $sourceCode) {
            $lastId = 0;
            while ($limit === 0 || $done + $failed < $limit) {
                $ids = $this->pending($sourceCode, $since, $lastId);
                if (!$ids) {
                    break;
                }
                foreach ($ids as $id) {
                    $lastId = $id;
                    $this->copyOne($sourceCode, $id, $folder) ? $done++ : $failed++;
                    if ($limit > 0 && $done + $failed >= $limit) {
                        break;
                    }
                }
            }
        }
        $this->line("Copied {$done} recording(s) to the bucket" . ($failed ? ", {$failed} failed (tried again later)" : '') . '.');
        return self::SUCCESS;
    }

    /** Next ids of one source that have a recording and no bucket copy (failed ones wait 15 minutes, max 5 tries). */
    private function pending(int $sourceCode, $since, int $afterId): array
    {
        [$table, $hasAudio] = match ($sourceCode) {
            0 => ['call_logs', fn ($q) => $q->where(fn ($w) => $w->where('call_logs.recording', '!=', '')
                    ->orWhere('call_logs.click_recording_url', '!=', '')->orWhereExists($this->liveRecording('call_logs')))],
            2 => ['dialer_logs', fn ($q) => $q->where(fn ($w) => $w->where('dialer_logs.file_path', '!=', '')
                    ->orWhere('dialer_logs.click_recording_url', '!=', '')->orWhereExists($this->liveRecording('dialer_logs')))],
            default => ['recordings', fn ($q) => $q->whereNull('recordings.call_log_id')->whereNull('recordings.dialer_log_id')
                    ->where('recordings.download_status', '!=', 'deleted')
                    ->where(fn ($w) => $w->where('recordings.file_path', '!=', '')->orWhere('recordings.recording_url', '!=', ''))],
        };
        $retryAfter = now()->subMinutes(15);
        return DB::table($table)
            ->where("{$table}.id", '>', $afterId)
            ->when($since, fn ($q) => $q->where("{$table}.created_at", '>=', $since))
            ->tap($hasAudio)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from(GcsBucket::TABLE . ' as b')
                ->where('b.source_code', $sourceCode)->whereColumn('b.log_id', "{$table}.id")
                ->where(fn ($w) => $w->where('b.status', 'done')->orWhere('b.attempts', '>=', 5)->orWhere('b.updated_at', '>', $retryAfter)))
            ->orderBy("{$table}.id")->limit(100)->pluck("{$table}.id")->map(fn ($v) => (int)$v)->all();
    }

    /** The call's recordings row has audio and is not deleted. */
    private function liveRecording(string $table): \Closure
    {
        return fn ($q) => $q->select(DB::raw(1))->from('recordings as r')->whereColumn('r.id', "{$table}.recording_id")
            ->where('r.download_status', '!=', 'deleted')
            ->where(fn ($w) => $w->where('r.file_path', '!=', '')->orWhere('r.recording_url', '!=', ''));
    }

    /** The company's CRM ID from its licence (folder in the bucket); without a licence: company-<site>. */
    private function crmFolder(): string
    {
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $crmId = preg_replace('/[^A-Za-z0-9_-]/', '', (string)(json_decode($settings->full_response ?? '{}', true)['data']['crm_id'] ?? ''));
        return $crmId !== '' ? $crmId : 'company-' . (Tenancy::storageKey() ?: 'main');
    }

    /** Day of the call (Y-m-d) for the bucket folder. */
    private function callDate(int $sourceCode, int $id): string
    {
        $when = match ($sourceCode) {
            0 => DB::table('call_logs')->where('id', $id)->value('created_at'),
            2 => DB::table('dialer_logs')->where('id', $id)->value('created_at'),
            default => DB::table('recordings')->where('id', $id)->selectRaw('COALESCE(call_date, created_at) as d')->value('d'),
        };
        return $when ? \Carbon\Carbon::parse($when)->format('Y-m-d') : now()->format('Y-m-d');
    }

    private function copyOne(int $sourceCode, int $id, string $folder): bool
    {
        $key = ['source_code' => $sourceCode, 'log_id' => $id];
        $row = DB::table(GcsBucket::TABLE)->where($key)->first();
        $base = ['attempts' => (int)($row->attempts ?? 0) + 1, 'created_at' => $row->created_at ?? now(), 'updated_at' => now()];
        try {
            $audio = RecordingAudio::find($sourceCode, $id, false);
            if (!empty($audio['error'])) {
                throw new \RuntimeException($audio['error']);
            }
            $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($audio['name'])) ?: 'recording.wav';
            // same layout as SmartDCM: <prefix>/<month>/<CRM ID>/<call date>/<type>_<id>_<file>,
            // e.g. recordings/2026-11/90022/2026-11-12/campaign_1234_xxx.wav (each CRM ID has its own folder)
            $type = [0 => 'campaign', 2 => 'manual'][$sourceCode] ?? 'recording';
            $day = $this->callDate($sourceCode, $id);
            $object = implode('/', [config('services.gcs.prefix') ?: 'recordings', substr($day, 0, 7), $folder, $day, "{$type}_{$id}_{$name}"]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $type = ['mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac'][$ext] ?? 'audio/wav';

            $size = GcsBucket::upload($object, $audio['path'] ?? null, $audio['contents'] ?? null, $type);
            DB::table(GcsBucket::TABLE)->updateOrInsert($key, [
                'object' => $object, 'size' => $size, 'status' => 'done', 'error' => null,
            ] + $base);
            return true;
        } catch (\Throwable $e) {
            DB::table(GcsBucket::TABLE)->updateOrInsert($key, ['status' => 'failed', 'error' => substr($e->getMessage(), 0, 250)] + $base);
            Log::warning("[RecordingsToBucket] source {$sourceCode} #{$id}: " . $e->getMessage());
            return false;
        }
    }
}
