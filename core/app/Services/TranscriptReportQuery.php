<?php

namespace App\Services;

use App\Models\CallAiAnalysis;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Transcript Report (Reports > Transcript Report): one row per transcription request = one batch of
 * transcription_jobs (a user selects one or more recordings and starts a transcript; an automatic transcript is a
 * batch of one, requested_by 0). Read from the company's own database only (the request's tenant connection).
 *
 *  - Call Recordings Count: distinct recordings of the request whose transcript completed (a recording transcribed
 *    again in the same request, or analysed in several ways, counts once).
 *  - Transcript Type: what the saved analysis (call_ai_analyses) contains: Summary (summary), Agent & Customer
 *    (speaker-separated conversation), Detailed Conversation (full transcript text).
 *  - Status: Processing (a job running) > Pending (queued / waiting for the API key) > Completed (at least one
 *    recording done) > Failed.
 *  - Credits: confirmed = actual_cost of completed jobs (billed from the real audio length); estimated =
 *    estimated_cost of jobs not completed yet. Currency from the jobs (company setting).
 * Estimates that were never confirmed (PENDING_CONFIRMATION) and cancelled jobs are not transcription requests.
 */
class TranscriptReportQuery
{
    public const TYPES = ['summary' => 'Summary', 'conversation' => 'Agent & Customer', 'detailed' => 'Detailed Conversation'];
    public const STATUSES = ['Pending', 'Processing', 'Completed', 'Failed'];
    public const SORTABLE = ['used_by' => 'used_by', 'role' => 'role', 'recordings' => 'recordings', 'credits' => 'credits_confirmed',
        'date' => 'requested_at', 'status' => 'status', 'type' => 'has_summary'];

    private array $f;
    private $user;
    private string $tp;

    /** @param array $filters from, to (Y-m-d, India days), user (id | 'auto'), role (permission id), type, status, search */
    public function __construct(array $filters, $user)
    {
        $tz = self::tz();
        $date = fn ($v, $fallback) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? (string)$v : $fallback;
        $from = $date($filters['from'] ?? null, Carbon::now($tz)->subDays(29)->format('Y-m-d'));
        $to = $date($filters['to'] ?? null, Carbon::now($tz)->format('Y-m-d'));
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $this->f = [
            'from' => $from, 'to' => $to,
            'user' => ($filters['user'] ?? '') === 'auto' ? 'auto' : (ctype_digit((string)($filters['user'] ?? '')) ? (int)$filters['user'] : null),
            'role' => ctype_digit((string)($filters['role'] ?? '')) ? (int)$filters['role'] : null,
            'type' => isset(self::TYPES[$filters['type'] ?? '']) ? $filters['type'] : null,
            'status' => in_array($filters['status'] ?? '', self::STATUSES, true) ? $filters['status'] : null,
            'search' => mb_substr(trim((string)($filters['search'] ?? '')), 0, 100),
        ];
        $this->user = $user;
        $this->tp = DB::getTablePrefix();
    }

    public function filters(): array
    {
        return $this->f;
    }

    /** Who may open the report: the roles that may request transcripts. */
    public static function canView($user): bool
    {
        return in_array($user->permissionsGroup->name ?? '', ['Super Admin', 'Admin', 'Webmaster', 'Manager'], true);
    }

    /** Admins see every request (also the automatic ones); a Manager the requests of their teams' users and their own. */
    private function scope($q): void
    {
        $role = $this->user->permissionsGroup->name ?? '';
        if (in_array($role, ['Super Admin', 'Admin', 'Webmaster'], true)) {
            return;
        }
        $teams = array_values(array_filter(array_map('intval', explode(',', (string)$this->user->team_id)))) ?: [0];
        $q->where(fn ($w) => $w->whereIn('j.requested_by', DB::table('team_user')->whereIn('team_id', $teams)->select('user_id'))
            ->orWhere('j.requested_by', $this->user->id));
    }

    /** One row per request (batch), with its counts, outcome types, credits and status. */
    private function batches()
    {
        $tp = $this->tp;
        $j = "{$tp}j";
        $a = "{$tp}a";
        $tz = self::tz();
        $start = Carbon::parse($this->f['from'] . ' 00:00:00', $tz)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $end = Carbon::parse($this->f['to'] . ' 23:59:59', $tz)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $done = "$j.status = 'COMPLETED'";
        $has = fn (string $col) => "MAX(CASE WHEN $done THEN COALESCE($a.$col, 0) ELSE 0 END)";

        // What the saved analysis of each recording contains: one row per recording, so a job is never counted twice
        $c = "{$tp}call_ai_analyses";
        $outputs = DB::table('call_ai_analyses')->where('status', 'completed')->groupBy('call_id')->selectRaw("call_id,
            MAX(COALESCE($c.summary, '') <> '') AS summary,
            MAX(COALESCE($c.conversation, '') NOT IN ('', '[]', 'null')) AS conversation,
            MAX(COALESCE($c.transcript_english, '') <> '' OR COALESCE($c.transcript, '') <> '') AS detailed");

        $q = DB::table('transcription_jobs as j')
            // the analysis saved for the job's recording (CallAiAnalysis::keysFor)
            ->leftJoinSub($outputs, 'a', 'a.call_id', '=', DB::raw("CASE $j.source_code WHEN 2 THEN 9000000 + $j.call_id WHEN 3 THEN 8000000 + $j.call_id ELSE $j.call_id END"))
            ->whereNotIn('j.status', ['PENDING_CONFIRMATION', 'CANCELLED'])
            ->whereBetween('j.created_at', [$start, $end]);
        $this->scope($q);
        if ($this->f['user'] === 'auto') {
            $q->where('j.requested_by', 0);
        } elseif ($this->f['user']) {
            $q->where('j.requested_by', $this->f['user']);
        }

        return $q->groupBy('j.batch_id')->selectRaw("$j.batch_id AS batch_id, MIN($j.requested_by) AS requested_by, MIN($j.created_at) AS requested_at,
            MAX($j.completed_at) AS completed_at, COUNT(*) AS jobs,
            SUM($done) AS completed_jobs, SUM($j.status = 'FAILED') AS failed_jobs, SUM($j.status = 'PROCESSING') AS processing_jobs,
            SUM($j.status IN ('QUEUED', 'WAITING_KEY')) AS pending_jobs,
            COUNT(DISTINCT CASE WHEN $done THEN CONCAT($j.source_code, '_', $j.call_id) END) AS recordings,
            COUNT(DISTINCT CONCAT($j.source_code, '_', $j.call_id)) AS recordings_requested,
            COALESCE(SUM(CASE WHEN $done THEN $j.actual_cost ELSE 0 END), 0) AS credits_confirmed,
            COALESCE(SUM(CASE WHEN $j.status IN ('QUEUED', 'WAITING_KEY', 'PROCESSING') THEN $j.estimated_cost ELSE 0 END), 0) AS credits_estimated,
            MAX($j.currency) AS currency,
            {$has('summary')} AS has_summary, {$has('conversation')} AS has_conversation, {$has('detailed')} AS has_detailed,
            CASE WHEN SUM($j.status = 'PROCESSING') > 0 THEN 'Processing'
                 WHEN SUM($j.status IN ('QUEUED', 'WAITING_KEY')) > 0 THEN 'Pending'
                 WHEN SUM($done) > 0 THEN 'Completed' ELSE 'Failed' END AS status");
    }

    /** The requests of the filters, with user, role and the type / status / search filters applied. */
    private function outer()
    {
        $b = $this->batches();
        $q = DB::query()->fromSub($b, 'b')
            ->leftJoin('users as u', 'u.id', '=', 'b.requested_by')
            ->leftJoin('permissions as p', 'p.id', '=', 'u.permissions_id')
            ->selectRaw("{$this->tp}b.*, CASE WHEN {$this->tp}b.requested_by = 0 THEN 'Automatic transcript' ELSE COALESCE({$this->tp}u.name, 'Deleted user') END AS used_by,
                CASE WHEN {$this->tp}b.requested_by = 0 THEN 'System' ELSE COALESCE({$this->tp}p.name, '-') END AS role");
        if ($this->f['role']) {
            $q->where('u.permissions_id', $this->f['role']);
        }
        if ($this->f['type']) {
            $q->where('b.has_' . $this->f['type'], 1);
        }
        if ($this->f['status']) {
            $q->where('b.status', $this->f['status']);
        }
        if ($this->f['search'] !== '') {
            $like = '%' . addcslashes($this->f['search'], '%_\\') . '%';
            $q->where(fn ($w) => $w->where('u.name', 'like', $like)->orWhere('p.name', 'like', $like)->orWhere('b.batch_id', 'like', $like));
        }
        return $q;
    }

    /** KPI cards for every request of the filters. */
    public function kpis(): array
    {
        $ids = $this->outer()->pluck('b.batch_id')->all();
        $empty = ['requests' => 0, 'recordings' => 0, 'completed' => 0, 'waiting' => 0, 'credits' => 0.0, 'estimated' => 0.0, 'currency' => self::currency()];
        if (!$ids) {
            return $empty;
        }
        $done = "status = 'COMPLETED'";
        $r = DB::table('transcription_jobs')->whereIn('batch_id', $ids)->whereNotIn('status', ['PENDING_CONFIRMATION', 'CANCELLED'])
            ->selectRaw("COUNT(DISTINCT CASE WHEN $done THEN CONCAT(source_code, '_', call_id) END) AS recordings, SUM($done) AS completed,
                SUM(status IN ('QUEUED', 'WAITING_KEY', 'PROCESSING')) AS waiting,
                COALESCE(SUM(CASE WHEN $done THEN actual_cost ELSE 0 END), 0) AS credits,
                COALESCE(SUM(CASE WHEN status IN ('QUEUED', 'WAITING_KEY', 'PROCESSING') THEN estimated_cost ELSE 0 END), 0) AS estimated,
                MAX(currency) AS currency")->first();
        return ['requests' => count($ids), 'recordings' => (int)$r->recordings, 'completed' => (int)$r->completed, 'waiting' => (int)$r->waiting,
            'credits' => round((float)$r->credits, 4), 'estimated' => round((float)$r->estimated, 4), 'currency' => $r->currency ?: self::currency()];
    }

    public function count(): int
    {
        return DB::query()->fromSub($this->outer(), 'o')->count();
    }

    /** One page of rows. */
    public function rows(int $start, int $length, string $sort = 'date', string $dir = 'desc'): array
    {
        $col = self::SORTABLE[$sort] ?? 'requested_at';
        $rows = $this->outer()->orderBy($col, strtolower($dir) === 'asc' ? 'asc' : 'desc')->orderBy('requested_at', 'desc')
            ->offset(max(0, $start))->limit(max(1, min(100, $length)))->get();
        return $rows->map(fn ($r) => $this->formatRow($r))->all();
    }

    public function formatRow(object $r): array
    {
        $types = array_values(array_filter([
            (int)$r->has_summary ? self::TYPES['summary'] : null,
            (int)$r->has_conversation ? self::TYPES['conversation'] : null,
            (int)$r->has_detailed ? self::TYPES['detailed'] : null,
        ]));
        $at = Carbon::parse($r->requested_at, config('app.timezone'))->setTimezone(self::tz());
        return [
            'batch_id' => $r->batch_id,
            'used_by' => $r->used_by,
            'role' => $r->role,
            'recordings' => (int)$r->recordings,
            'recordings_requested' => (int)$r->recordings_requested,
            'types' => $types,
            'status' => $r->status,
            'failed' => (int)$r->failed_jobs,
            'credits_confirmed' => round((float)$r->credits_confirmed, 4),
            'credits_estimated' => round((float)$r->credits_estimated, 4),
            'currency' => $r->currency ?: self::currency(),
            'date' => $at->format('d-M-Y'),
            'time' => $at->format('h:i A'),
            'has_text' => (int)$r->recordings > 0 && count($types) > 0,
        ];
    }

    /** One request's row, only when this user may see it (same scope as the table); null otherwise. */
    public function rowFor(string $batchId): ?array
    {
        $r = $this->outer()->where('b.batch_id', $batchId)->first();
        return $r ? $this->formatRow($r) : null;
    }

    /**
     * The saved transcripts of a request (no Sarvam call, nothing charged): one entry per completed recording, with
     * its summary, the speaker-separated conversation and the full transcript, as stored.
     */
    public static function transcripts(string $batchId): array
    {
        $jobs = DB::table('transcription_jobs')->where('batch_id', $batchId)->where('status', 'COMPLETED')->orderBy('id')->get()
            ->unique(fn ($j) => $j->source_code . '_' . $j->call_id);
        $out = [];
        foreach ($jobs as $job) {
            $a = CallAiAnalysis::findFor((int)$job->source_code, (int)$job->call_id);
            if (!$a || $a->status !== 'completed') {
                continue;
            }
            $roles = is_array($a->speaker_roles) ? $a->speaker_roles : [];
            $label = function (string $s) use ($roles) {
                $r = strtolower((string)($roles[$s] ?? ''));
                return $r === 'agent' ? 'Agent' : ($r === 'customer' ? 'Customer' : 'Speaker ' . ltrim($s, 'Ss'));
            };
            $conversation = [];
            foreach ((array)$a->conversation as $line) {
                if (!is_array($line) || trim((string)($line['text'] ?? '')) === '') continue;
                $sec = (int)($line['start'] ?? 0);
                $conversation[] = ['time' => sprintf('%02d:%02d', intdiv($sec, 60), $sec % 60), 'speaker' => $label((string)($line['speaker'] ?? '')),
                    'text' => trim((string)$line['text'])];
            }
            $out[] = [
                'recording' => trim(($job->customer_name ?: '-') . ' · ' . ($job->phonenumber ?: '-'), ' ·'),
                'agent' => $job->agent_name ?: '-',
                'duration' => \App\Helpers\Helper::formatDuration((int)($job->actual_duration_seconds ?: $job->duration_seconds)),
                'summary' => trim((string)$a->summary),
                'conversation' => $conversation,
                'detailed' => trim((string)($a->transcript_english ?: $a->transcript)),
                'language' => $a->source_language,
            ];
        }
        return $out;
    }

    /** The transcripts as plain text (Download / Copy). */
    public static function asText(string $batchId, array $meta): string
    {
        $lines = ['LeadGER transcript – ' . $meta['used_by'] . ' – ' . $meta['date'] . ' ' . $meta['time'], ''];
        foreach (self::transcripts($batchId) as $i => $t) {
            $lines[] = str_repeat('=', 60);
            $lines[] = 'Recording ' . ($i + 1) . ': ' . $t['recording'] . ' | Agent: ' . $t['agent'] . ' | Duration: ' . $t['duration'];
            $lines[] = str_repeat('=', 60);
            if ($t['summary'] !== '') {
                $lines[] = 'SUMMARY';
                $lines[] = $t['summary'];
                $lines[] = '';
            }
            if ($t['conversation']) {
                $lines[] = 'AGENT & CUSTOMER';
                foreach ($t['conversation'] as $c) {
                    $lines[] = "[{$c['time']}] {$c['speaker']}: {$c['text']}";
                }
                $lines[] = '';
            }
            if ($t['detailed'] !== '') {
                $lines[] = 'DETAILED CONVERSATION';
                $lines[] = $t['detailed'];
                $lines[] = '';
            }
        }
        return implode("\n", $lines);
    }

    /** Users / roles for the filter dropdowns (the same scope as the rows). */
    public function filterOptions(): array
    {
        $q = DB::table('transcription_jobs as j')->whereNotIn('j.status', ['PENDING_CONFIRMATION', 'CANCELLED']);
        $this->scope($q);
        $ids = $q->distinct()->pluck('j.requested_by')->all();
        $users = DB::table('users')->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'permissions_id']);
        return [
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()->all(),
            'auto' => in_array(0, array_map('intval', $ids), true),
            'roles' => DB::table('permissions')->whereIn('id', $users->pluck('permissions_id')->filter()->unique())->orderBy('name')->get(['id', 'name'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
        ];
    }

    private static function tz(): string
    {
        return config('services.telephony.timezone') ?: config('app.timezone');
    }

    private static function currency(): string
    {
        try {
            return \App\Models\CompanyAiSetting::company()->currency ?: 'INR';
        } catch (\Throwable $e) {
            return 'INR';
        }
    }
}
