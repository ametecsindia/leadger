<?php

namespace App\Services;

use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;

/**
 * Call Report: campaign calls (call_logs) and manual dial calls (dialer_logs) as ONE result, built by a single
 * UNION ALL query in one row shape. The table rows, KPI cards, counts and the Excel / CSV export all come from it,
 * so they always match. Nothing is copied into another table.
 *
 * Modes:
 *  - report (default): the Call Report page. Visibility as before: campaign calls by the campaign team / lead rules,
 *    manual calls by the Dialer Logs rules (agents their own, managers their team). Connected = talk time > 0.
 *  - agents: opened from an Agent Performance card. Exactly the calls (and counting rules) of those cards.
 */
class CallReportQuery
{
    /** Sortable columns (request name => result column). Default: newest first. */
    public const SORTABLE = [
        'lead_id' => 'lead_id', 'time' => 'created_at', 'date' => 'created_at', 'telecaller' => 'agent_name',
        'source' => 'source', 'campaign' => 'campaign_name', 'customer' => 'customer_name', 'mobile' => 'phone',
        'duration' => 'seconds', 'status' => 'status_name', 'substatus' => 'substatus_name',
        'call_type' => 'call_type', 'feedback' => 'feedback',
    ];

    private array $f;
    private $user;
    private string $role;
    private string $tp;

    /**
     * @param array $filters from, to (Y-m-d) | campaign ('' | id | 'manual') | agent (id) | source (all | campaign |
     *                       manual) | search | view (all | connected | not_connected | with_talk_time) |
     *                       status ('' | connected | converted) | mode (report | agents)
     */
    public function __construct(array $filters, $user)
    {
        $isDate = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        $today = \Carbon\Carbon::today(CallingRestrictionService::getTimezone())->format('Y-m-d');
        $from = $isDate($filters['from'] ?? null) ? $filters['from'] : $today;
        $to = $isDate($filters['to'] ?? null) ? $filters['to'] : $today;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $campaign = (string)($filters['campaign'] ?? '');
        $this->f = [
            'from' => $from,
            'to' => $to,
            'campaign' => $campaign === 'manual' ? 'manual' : (ctype_digit($campaign) && (int)$campaign > 0 ? (int)$campaign : ''),
            'agent' => ctype_digit((string)($filters['agent'] ?? '')) ? (int)$filters['agent'] : 0,
            'source' => in_array($filters['source'] ?? '', ['campaign', 'manual'], true) ? $filters['source'] : 'all',
            'search' => mb_substr(trim((string)($filters['search'] ?? '')), 0, 100),
            'view' => in_array($filters['view'] ?? '', ['connected', 'not_connected', 'with_talk_time'], true) ? $filters['view'] : 'all',
            'status' => in_array(strtolower((string)($filters['status'] ?? '')), ['connected', 'converted'], true) ? strtolower($filters['status']) : '',
            'mode' => ($filters['mode'] ?? '') === 'agents' ? 'agents' : 'report',
        ];
        $this->user = $user;
        $this->role = (string)optional($user->permissionsGroup)->name;
        $this->tp = DB::getTablePrefix();
    }

    public function filters(): array
    {
        return $this->f;
    }

    /** KPI numbers for every row of the filters + search (the selected card's view does not change them). */
    public function kpis(): array
    {
        $talkExpr = $this->f['mode'] === 'agents' ? 'seconds' : 'CASE WHEN connected = 1 THEN seconds ELSE 0 END';
        $r = $this->outer(false)->selectRaw("COUNT(*) AS total, COALESCE(SUM(connected),0) AS connected, COALESCE(SUM($talkExpr),0) AS talk,
                COALESCE(SUM(source = 'campaign'),0) AS campaign_calls, COALESCE(SUM(source = 'manual'),0) AS manual_calls,
                COALESCE(SUM(connected = 1 AND seconds > 0),0) AS with_talk,
                COALESCE(SUM(status_id IS NOT NULL AND status_id != 0),0) AS with_feedback")->first();
        $total = (int)$r->total;
        $connected = (int)$r->connected;
        $talk = (int)$r->talk;
        $avg = $connected > 0 ? (int)round($talk / $connected) : 0;
        return [
            'total_calls' => $total,
            'connected' => $connected,
            'not_connected' => $total - $connected,
            'talk_seconds' => $talk,
            'avg_seconds' => $avg,
            'total_talk' => $talk < 3600 ? Helper::formatDuration($talk) : sprintf('%dh %02dm', intdiv($talk, 3600), intdiv($talk % 3600, 60)),
            'avg_duration' => Helper::formatDuration($avg),
            'campaign_calls' => (int)$r->campaign_calls,
            'manual_calls' => (int)$r->manual_calls,
            // calls whose Call Outcome (feedback) was saved; the rest still wait for it
            'with_feedback' => (int)$r->with_feedback,
            'without_feedback' => $total - (int)$r->with_feedback,
            'view_count' => match ($this->f['view']) {
                'connected' => $connected,
                'not_connected' => $total - $connected,
                'with_talk_time' => (int)$r->with_talk,
                default => $total,
            },
        ];
    }

    /** Conversion as the Agent Performance card computes it: Deal-outcome calls / all calls (status filter ignored). */
    public function conversion(): array
    {
        $plain = new self(array_merge($this->f, ['status' => '', 'view' => 'all', 'search' => '']), $this->user);
        $deals = $this->dealIds();
        $r = $plain->outer(false)->selectRaw('COUNT(*) AS calls, COALESCE(SUM(status_id IN (' . implode(',', $deals) . ')),0) AS deals')->first();
        $calls = (int)$r->calls;
        return ['deals' => (int)$r->deals, 'calls' => $calls, 'rate' => $calls > 0 ? round(((int)$r->deals / $calls) * 100, 1) : 0];
    }

    /** One page of rows for the current card view and sort. */
    public function rows(int $start, int $length, string $sort = 'time', string $dir = 'desc'): array
    {
        $q = $this->sorted($sort, $dir);
        if ($length > 0) {
            $q->offset(max(0, $start))->limit(min($length, 500));
        }
        return array_map(fn ($r) => $this->formatRow($r), $q->get()->all());
    }

    /** Every row for the current card view and sort, one at a time (export). */
    public function cursor(string $sort = 'time', string $dir = 'desc'): \Generator
    {
        foreach ($this->sorted($sort, $dir)->cursor() as $r) {
            yield $this->formatRow($r);
        }
    }

    /* ------------------------------------------------------------------ */

    private function sorted(string $sort, string $dir)
    {
        $col = self::SORTABLE[$sort] ?? 'created_at';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
        $q = $this->outer(true)->select('*');
        // the talk-time card lists the longest calls first unless another order was chosen
        if ($this->f['view'] === 'with_talk_time' && !isset(self::SORTABLE[$sort])) {
            $col = 'seconds';
        }
        return $q->orderBy($col, $dir)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    /** The union wrapped as one table "c", with the search (and, for rows, the card view) applied. */
    private function outer(bool $withView)
    {
        $q = DB::query()->fromSub($this->union(), 'c');
        $s = $this->f['search'];
        if ($s !== '') {
            $like = '%' . addcslashes($s, '%_\\') . '%';
            $digits = preg_replace('/\D/', '', $s);
            $q->where(function ($w) use ($s, $like, $digits) {
                if (ctype_digit($s)) {
                    $w->orWhere('lead_id', (int)$s);
                }
                $w->orWhere('customer_name', 'like', $like)->orWhere('agent_name', 'like', $like)->orWhere('phone', 'like', $like);
                if ($digits !== '' && $digits !== $s) {
                    $w->orWhere('phone', 'like', '%' . $digits . '%');
                }
            });
        }
        if ($withView) {
            match ($this->f['view']) {
                'connected' => $q->where('connected', 1),
                'not_connected' => $q->where('connected', 0),
                'with_talk_time' => $q->where('connected', 1)->where('seconds', '>', 0),
                default => null,
            };
        }
        return $q;
    }

    private function union()
    {
        $wantCampaign = $this->f['source'] !== 'manual' && $this->f['campaign'] !== 'manual';
        // a campaign chosen in the Campaign filter: campaign calls only (manual calls have no campaign)
        $wantManual = $this->f['source'] !== 'campaign' && !is_int($this->f['campaign']);
        $parts = [];
        if ($wantCampaign) $parts[] = $this->campaignCalls();
        if ($wantManual) $parts[] = $this->manualCalls();
        if (!$parts) {
            return $this->campaignCalls()->whereRaw('1 = 0');
        }
        $q = array_shift($parts);
        foreach ($parts as $p) {
            $q->unionAll($p);
        }
        return $q;
    }

    /** Campaign calls (call_logs). */
    private function campaignCalls()
    {
        $tp = $this->tp;
        $agents = $this->f['mode'] === 'agents';
        $q = DB::table('call_logs')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('campaigns', 'campaigns.id', '=', DB::raw("COALESCE({$tp}call_logs.campaign_id, {$tp}leads.campaign_id)"))
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
            ->leftJoin('statuses', 'statuses.id', '=', 'call_logs.next_status')
            ->leftJoin('substatuses', 'substatuses.id', '=', 'call_logs.substatus_id')
            ->leftJoin('recordings', 'recordings.id', '=', 'call_logs.recording_id')
            ->whereBetween('call_logs.created_at', [$this->f['from'] . ' 00:00:00', $this->f['to'] . ' 23:59:59']);

        if ($agents) {
            // Agent Performance: campaign calls (call_source campaign or not set), its own scope and seconds / connected rules
            $q->where(fn ($w) => $w->where('call_logs.call_source', 'campaign')->orWhereNull('call_logs.call_source'));
            $this->agentPerformanceScope($q, 'call_logs');
            if (is_int($this->f['campaign'])) $q->where('call_logs.campaign_id', $this->f['campaign']);
            $seconds = "COALESCE(CASE WHEN {$tp}call_logs.duration REGEXP '^[0-9]+$' THEN CAST({$tp}call_logs.duration AS UNSIGNED) ELSE TIME_TO_SEC({$tp}call_logs.duration) END, 0)";
            $connected = "({$tp}call_logs.duration IS NOT NULL AND {$tp}call_logs.duration != '' AND {$tp}call_logs.duration != '0' AND {$tp}call_logs.duration != '00:00')";
        } else {
            $q->where('call_logs.call_source', 'campaign');
            $this->campaignScope($q);
            if (is_int($this->f['campaign'])) $q->where('campaigns.id', $this->f['campaign']);
            // talk seconds: the call's duration, else the recording's length (as before)
            $own = $this->secondsSql("{$tp}call_logs.duration");
            $seconds = "(CASE WHEN $own > 0 THEN $own ELSE " . $this->secondsSql("{$tp}recordings.duration") . " END)";
            $connected = "($seconds > 0)";
        }
        if ($this->f['agent']) $q->where('call_logs.created_by', $this->f['agent']);
        $this->statusFilter($q, 'call_logs.next_status', $connected);

        return $q->selectRaw("'campaign' AS source, {$tp}call_logs.id AS id, {$tp}call_logs.lead_id AS lead_id,
            {$tp}call_logs.phonenumber AS phone, {$tp}call_logs.call_type AS call_type, {$tp}call_logs.call_state AS call_state,
            {$tp}call_logs.created_at AS created_at, {$tp}call_logs.start_time AS start_time, {$tp}call_logs.end_time AS end_time,
            {$tp}call_logs.created_by AS agent_id, {$tp}users.name AS agent_name,
            {$tp}campaigns.id AS campaign_id, {$tp}campaigns.name AS campaign_name, {$tp}campaigns.number_masking AS number_masking,
            {$tp}leads.name AS customer_name, {$tp}call_logs.next_status AS status_id, {$tp}statuses.name AS status_name,
            {$tp}substatuses.name AS substatus_name, {$tp}call_logs.remarks AS feedback, NULL AS comments,
            {$tp}recordings.download_status AS rec_status, {$tp}recordings.file_path AS rec_file,
            {$tp}call_logs.reference_id AS reference_id, {$tp}call_logs.provider_call_id AS provider_call_id,
            $seconds AS seconds, (CASE WHEN $connected THEN 1 ELSE 0 END) AS connected");
    }

    /** Manual dial calls (dialer_logs). */
    private function manualCalls()
    {
        $tp = $this->tp;
        $agents = $this->f['mode'] === 'agents';
        $q = DB::table('dialer_logs')
            ->leftJoin('users', 'users.id', '=', 'dialer_logs.created_by')
            // the call's lead only when it has the dialled number (older calls were sometimes linked to the lead on screen)
            ->leftJoin('leads', fn ($j) => $j->on('leads.id', '=', 'dialer_logs.lead_id')
                ->whereRaw("(RIGHT({$tp}leads.phonenumber, 10) = RIGHT({$tp}dialer_logs.phone_number, 10) OR RIGHT({$tp}leads.phonenumber_2, 10) = RIGHT({$tp}dialer_logs.phone_number, 10))"))
            ->leftJoin('campaigns', 'campaigns.id', '=', 'dialer_logs.campaign_id')
            ->leftJoin('statuses', 'statuses.id', '=', 'dialer_logs.status_id')
            ->leftJoin('substatuses', 'substatuses.id', '=', 'dialer_logs.substatus_id')
            ->leftJoin('recordings', 'recordings.id', '=', 'dialer_logs.recording_id')
            ->whereBetween('dialer_logs.created_at', [$this->f['from'] . ' 00:00:00', $this->f['to'] . ' 23:59:59']);

        if ($agents) {
            $q->where(fn ($w) => $w->where('dialer_logs.call_source', 'manual')->orWhereNull('dialer_logs.call_source'));
            $this->agentPerformanceScope($q, 'dialer_logs');
            if (is_int($this->f['campaign'])) $q->where('dialer_logs.campaign_id', $this->f['campaign']);
            $seconds = "COALESCE({$tp}dialer_logs.time_taken, CASE WHEN {$tp}dialer_logs.duration REGEXP '^[0-9]+$' THEN CAST({$tp}dialer_logs.duration AS UNSIGNED) ELSE TIME_TO_SEC({$tp}dialer_logs.duration) END, 0)";
            $connected = "({$tp}dialer_logs.call_state IN ('completed', 'answered') OR ({$tp}dialer_logs.time_taken > 0) OR ({$tp}dialer_logs.duration IS NOT NULL AND {$tp}dialer_logs.duration != '00:00' AND {$tp}dialer_logs.duration != '0' AND {$tp}dialer_logs.duration != ''))";
        } else {
            $q->where('dialer_logs.call_source', 'manual');
            $this->manualScope($q);
            // talk seconds: talk time, else the stored duration, else the recording's length
            $own = $this->secondsSql("{$tp}dialer_logs.duration");
            $seconds = "(CASE WHEN {$tp}dialer_logs.time_taken > 0 THEN {$tp}dialer_logs.time_taken WHEN $own > 0 THEN $own ELSE "
                . $this->secondsSql("{$tp}recordings.duration") . " END)";
            $connected = "($seconds > 0)";
        }
        if ($this->f['agent']) $q->where('dialer_logs.created_by', $this->f['agent']);
        $this->statusFilter($q, 'dialer_logs.status_id', $connected);

        return $q->selectRaw("'manual' AS source, {$tp}dialer_logs.id AS id, {$tp}leads.id AS lead_id,
            {$tp}dialer_logs.phone_number AS phone, {$tp}dialer_logs.call_type AS call_type, {$tp}dialer_logs.call_state AS call_state,
            {$tp}dialer_logs.created_at AS created_at, {$tp}dialer_logs.start_time AS start_time, {$tp}dialer_logs.end_time AS end_time,
            {$tp}dialer_logs.created_by AS agent_id, {$tp}users.name AS agent_name,
            {$tp}campaigns.id AS campaign_id, {$tp}campaigns.name AS campaign_name, NULL AS number_masking,
            {$tp}leads.name AS customer_name, {$tp}dialer_logs.status_id AS status_id, {$tp}statuses.name AS status_name,
            {$tp}substatuses.name AS substatus_name, {$tp}dialer_logs.feedback AS feedback, {$tp}dialer_logs.comments AS comments,
            {$tp}recordings.download_status AS rec_status, {$tp}recordings.file_path AS rec_file,
            {$tp}dialer_logs.reference_id AS reference_id, {$tp}dialer_logs.provider_call_id AS provider_call_id,
            $seconds AS seconds, (CASE WHEN $connected THEN 1 ELSE 0 END) AS connected");
    }

    /** Helper::durationToSeconds in SQL: seconds as a number, or "m:ss" / "h:mm:ss" text. */
    private function secondsSql(string $col): string
    {
        $v = "TRIM($col)";
        return "(CASE WHEN $col IS NULL OR $v = '' THEN 0
            WHEN $v REGEXP '^[0-9]+(\\\\.[0-9]+)?$' THEN ROUND($v)
            WHEN $v REGEXP '^[0-9]+:[0-9]{1,2}:[0-9]{1,2}$' THEN SUBSTRING_INDEX($v, ':', 1) * 3600 + SUBSTRING_INDEX(SUBSTRING_INDEX($v, ':', 2), ':', -1) * 60 + SUBSTRING_INDEX($v, ':', -1)
            WHEN $v REGEXP '^[0-9]+:[0-9]{1,2}$' THEN SUBSTRING_INDEX($v, ':', 1) * 60 + SUBSTRING_INDEX($v, ':', -1)
            ELSE 0 END)";
    }

    /** Campaign calls: who sees what (unchanged Call Report rules). */
    private function campaignScope($q): void
    {
        $user = $this->user;
        $teams = array_values(array_filter(array_map('intval', explode(',', (string)$user->team_id))));
        if (!in_array($this->role, ['Super Admin', 'Admin', 'Webmaster'], true) && $teams) {
            $q->where(fn ($w) => $w->whereIn('campaigns.team_id', $teams)->orWhere('call_logs.created_by', $user->id));
        }
        if ($this->role === 'Telecaller') {
            $q->where(fn ($w) => $w->where('leads.assigned_to', $user->id)->orWhere('leads.is_public', '1')->orWhere('call_logs.created_by', $user->id));
        } elseif ($this->role === 'Sales Person') {
            $q->where(fn ($w) => $w->where('leads.sales_person_id', $user->id)->orWhere('leads.is_public', '1')->orWhere('call_logs.created_by', $user->id));
        }
    }

    /** Manual calls: who sees what (the former Dialer Logs rules). */
    private function manualScope($q): void
    {
        $user = $this->user;
        if (in_array($this->role, ['Telecaller', 'Sales Person'], true)) {
            $q->where('dialer_logs.created_by', $user->id);
            return;
        }
        if (in_array($this->role, ['Manager', 'Team Leader'], true)) {
            $teams = array_values(array_filter(array_map('intval', explode(',', (string)$user->team_id))));
            if ($teams) {
                $q->where(fn ($w) => $w->whereIn('users.team_id', $teams)->orWhere('dialer_logs.created_by', $user->id));
            }
        }
    }

    /** Agent Performance scope: managers / team leaders their team's agents, admins all, everyone else their own. */
    private function agentPerformanceScope($q, string $table): void
    {
        $user = $this->user;
        if (in_array($this->role, ['Manager', 'Team Leader'], true)) {
            $teams = array_values(array_filter(array_map('intval', explode(',', (string)$user->team_id)))) ?: [0];
            $q->whereIn("$table.created_by", DB::table('team_user')->whereIn('team_id', $teams)->select('user_id'));
        } elseif (!in_array($this->role, ['Admin', 'Webmaster'], true)) {
            $q->where("$table.created_by", $user->id);
        }
    }

    /** status=connected (connected calls) / converted (Deal outcome), used by Agent Performance links. */
    private function statusFilter($q, string $statusCol, string $connectedSql): void
    {
        if ($this->f['status'] === 'connected') {
            $q->whereRaw($connectedSql);
        } elseif ($this->f['status'] === 'converted') {
            $q->whereIn($statusCol, $this->dealIds());
        }
    }

    private function dealIds(): array
    {
        return DB::table('statuses')->where('name', 'Deal')->pluck('id')->map(fn ($id) => (int)$id)->all() ?: [0];
    }

    /** One table / export row. */
    public function formatRow(object $r): array
    {
        $manual = $r->source === 'manual';
        $seconds = (int)$r->seconds;
        $recStatus = strtolower((string)$r->rec_status);
        if ($recStatus === 'completed' || $recStatus === 'failed') {
            $recording = ['state' => 'available', 'url' => route('downloadRecordings', [$r->id, $manual ? 2 : 0]) . '?play=1'];
        } elseif (in_array($recStatus, ['awaiting', 'pending', 'downloading'], true)) {
            $recording = ['state' => 'processing'];
        } elseif ($recStatus === 'unavailable') {
            $recording = ['state' => 'unavailable'];
        } else {
            $recording = ['state' => 'none'];
        }

        // Saved Call Outcome of this call; none saved = "-" in both
        $saved = !empty($r->status_id);
        $type = strtolower((string)$r->call_type);
        $callType = str_contains($type, 'webrtc') ? 'WebRTC' : (str_contains($type, 'click') || str_contains($type, 'c2c') ? 'Click2Call' : ($type !== '' ? ucfirst($type) : '-'));
        $start = $r->start_time ?: $r->created_at;
        $phone = (string)$r->phone;

        return [
            'id' => (int)$r->id,
            'source' => $manual ? 'Manual' : 'Campaign',
            'call_id' => $r->reference_id ?: ($r->provider_call_id ?: (($manual ? 'dial_' : 'call_') . $r->id)),
            'lead_id' => $r->lead_id ? (int)$r->lead_id : null,
            'date' => date('d-M-Y', strtotime($r->created_at)),
            'time' => date('h:i A', strtotime($r->created_at)),
            'start_time' => $start ? date('h:i:s A', strtotime($start)) : '-',
            'end_time' => $r->end_time ? date('h:i:s A', strtotime($r->end_time)) : '-',
            'telecaller' => $r->agent_name ?: '-',
            'campaign' => $manual ? 'Manual Dial' : ($r->campaign_name ?: '-'),
            'campaign_id' => $r->campaign_id ? (int)$r->campaign_id : null,
            'lead' => self::customerName($r->customer_name, $phone),
            'mobile' => !$manual && $r->number_masking !== null ? Helper::number_masking($phone, $r->number_masking) : $phone,
            'call_type' => $callType,
            'duration' => Helper::formatDuration($seconds),
            'seconds' => $seconds,
            'recording_url' => $recording['state'] === 'available' ? $recording['url'] : null,
            'status' => $saved && trim((string)$r->status_name) !== '' ? (string)$r->status_name : '-',
            'substatus' => $saved && trim((string)$r->substatus_name) !== '' ? (string)$r->substatus_name : '-',
            'feedback' => trim((string)$r->feedback) !== '' ? trim((string)$r->feedback) : '-',
            'comments' => trim((string)$r->comments) !== '' && trim((string)$r->comments) !== trim((string)$r->feedback) ? trim((string)$r->comments) : null,
            'call_result' => (int)$r->connected === 1 ? 'Connected' : Helper::callOutcome($r->call_state, 0),
            'recording' => $recording,
            'recording_file' => $recording['state'] === 'available' && $r->rec_file ? basename(str_replace('\\', '/', $r->rec_file)) : null,
        ];
    }

    /** The customer's name; "-" when empty or only a placeholder such as "Lead 9777436222". */
    public static function customerName(?string $name, string $phone = ''): string
    {
        $name = trim((string)$name);
        if ($name === '' || preg_match('/^lead\s*[#:-]?\s*\+?[\d\s-]{6,}$/i', $name)) {
            return '-';
        }
        $digits = preg_replace('/\D/', '', $name);
        if ($digits !== '' && strlen($digits) >= 6 && preg_replace('/[\d\s+()-]/', '', $name) === '') {
            return '-';   // the name is just a phone number
        }
        return $name;
    }
}
