<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Inbound Calls report: one query for the table rows, the KPI cards and the Excel / CSV export, so they always match.
 * Who sees what: Admin / Webmaster all calls; Manager / Team Leader the calls of their teams (answered by a team
 * member, or to a VMN of their team; all calls when they have no team); everyone else the calls they answered, the
 * callbacks from numbers they called (Click2Call or WebRTC) and the calls from their own leads.
 */
class InboundCallQuery
{
    /** Sortable columns (request name => SQL column). Default: newest first. */
    public const SORTABLE = [
        'lead_id' => 'inbound_calls.lead_id', 'call_id' => 'inbound_calls.call_id',
        'customer_mobile' => 'inbound_calls.customer_mobile_normalized', 'vmn' => 'inbound_calls.vmn',
        'start_time' => 'inbound_calls.start_time', 'agent_number' => 'inbound_calls.agent_number', 'agent_name' => 'agent_name',
    ];

    private array $f;
    private $user;
    private string $role;

    /** @param array $filters from, to (Y-m-d, already validated), search */
    public function __construct(array $filters, $user)
    {
        $this->f = [
            'from' => $filters['from'],
            'to' => $filters['to'],
            'search' => mb_substr(trim((string)($filters['search'] ?? '')), 0, 100),
        ];
        $this->user = $user;
        $this->role = (string)optional($user->permissionsGroup)->name;
    }

    public function filters(): array
    {
        return $this->f;
    }

    public function kpis(): array
    {
        $r = $this->base()->selectRaw('COUNT(*) AS total, COUNT(DISTINCT ' . DB::getTablePrefix() . 'inbound_calls.customer_mobile_normalized) AS callers,
            COALESCE(SUM(' . DB::getTablePrefix() . 'inbound_calls.lead_id IS NOT NULL), 0) AS matched')->first();
        return ['total' => (int)$r->total, 'unique_callers' => (int)$r->callers, 'matched' => (int)$r->matched];
    }

    public function rows(int $start, int $length, string $sort = 'start_time', string $dir = 'desc'): array
    {
        return array_map(fn ($r) => $this->formatRow($r), $this->sorted($sort, $dir)->offset(max(0, $start))->limit($length)->get()->all());
    }

    /** Every row for the filters and sort, one at a time (export). */
    public function cursor(string $sort = 'start_time', string $dir = 'desc'): \Generator
    {
        foreach ($this->sorted($sort, $dir)->cursor() as $r) {
            yield $this->formatRow($r);
        }
    }

    /** May the user see this inbound call in the report (any date)? Same visibility rules as the table. */
    public static function userSees($user, int $id): bool
    {
        $q = DB::table('inbound_calls')->leftJoin('users', 'users.id', '=', 'inbound_calls.agent_user_id')->where('inbound_calls.id', $id);
        (new self(['from' => '', 'to' => ''], $user))->scope($q);
        return $q->exists();
    }

    public function formatRow(object $r): array
    {
        $dash = fn ($v) => trim((string)$v) !== '' ? (string)$v : '-';
        return [
            'id' => (int)$r->id,
            'customer_mobile_normalized' => (string)$r->customer_mobile_normalized,
            'lead_id' => $r->lead_id ? (int)$r->lead_id : null,
            'call_id' => $dash($r->call_id),
            'customer_mobile' => $dash($r->customer_mobile),
            'vmn' => $dash($r->vmn),
            'start_time' => $r->start_time ? Carbon::parse($r->start_time, config('app.timezone'))->setTimezone(self::reportTimezone())->format('d M Y, h:i A') : '-',
            'call_type' => $this->callType($r),
            'agent_number' => $dash($r->agent_number),
            // the agent of this number / WebRTC ID; with no agent, the user the lead is allocated to
            'agent_name' => trim((string)$r->agent_name) !== '' ? (string)$r->agent_name
                : (trim((string)($r->owner_name ?? '')) !== '' ? $r->owner_name . ' (allocated)' : '-'),
        ];
    }

    /**
     * Click2Call or WebRTC: a WebRTC agent ID (the user's smartping_user_id, or a short extension such as 100266) is a
     * WebRTC call, an agent mobile number a Click2Call call; with no agent, the VMN's channel when it has only one.
     */
    /** @var array<string, array{0: bool, 1: bool}>|null this report's VMNs: [click2call, webrtc] */
    private ?array $vmnChannels = null;

    public function callType(object $r): string
    {
        $agent = InboundCallService::normalizeMobile($r->agent_number ?? '');
        if ($agent !== '') {
            if ((trim((string)($r->agent_webrtc_id ?? '')) !== '' && trim((string)$r->agent_webrtc_id) === $agent) || strlen($agent) < 10) {
                return 'WebRTC';
            }
            return 'Click2Call';
        }
        if ($this->vmnChannels === null) {
            $this->vmnChannels = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('obd_did')) {
                foreach (DB::table('obd_did')->get(['virtual_number', 'click2call', 'webrtc']) as $d) {
                    $this->vmnChannels[InboundCallService::normalizeMobile($d->virtual_number)] = [(int)$d->click2call === 1, (int)$d->webrtc === 1];
                }
            }
        }
        [$c2c, $web] = $this->vmnChannels[InboundCallService::normalizeMobile($r->vmn ?? '')] ?? [false, false];
        return $web && !$c2c ? 'WebRTC' : ($c2c && !$web ? 'Click2Call' : '-');
    }

    /* ------------------------------------------------------------------ */

    /** The dates people pick are days in India time (Asia/Kolkata); times are stored in the app timezone. */
    private static function reportTimezone(): string
    {
        return config('services.telephony.timezone') ?: config('app.timezone');
    }

    private static function stored(string $localTime): string
    {
        return Carbon::parse($localTime, self::reportTimezone())->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    private function sorted(string $sort, string $dir)
    {
        return $this->base()
            ->select('inbound_calls.id', 'inbound_calls.lead_id', 'inbound_calls.call_id', 'inbound_calls.customer_mobile',
                'inbound_calls.customer_mobile_normalized', 'inbound_calls.vmn',
                'inbound_calls.start_time', 'inbound_calls.agent_number', 'inbound_calls.status', 'users.name as agent_name',
                'users.smartping_user_id as agent_webrtc_id', 'owners.name as owner_name')
            // the lead's allocated user: shown when no agent took the call
            ->leftJoin('leads as call_leads', 'call_leads.id', '=', 'inbound_calls.lead_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'call_leads.assigned_to')
            ->orderBy(self::SORTABLE[$sort] ?? 'inbound_calls.start_time', strtolower($dir) === 'asc' ? 'asc' : 'desc')
            ->orderBy('inbound_calls.start_time', 'desc')
            ->orderBy('inbound_calls.id', 'desc');
    }

    /** The calls of the dates, search and the user's visibility. */
    private function base()
    {
        $q = DB::table('inbound_calls')
            ->leftJoin('users', 'users.id', '=', 'inbound_calls.agent_user_id')
            ->whereBetween('inbound_calls.start_time', [self::stored($this->f['from'] . ' 00:00:00'), self::stored($this->f['to'] . ' 23:59:59')]);
        $this->scope($q);

        $s = $this->f['search'];
        if ($s !== '') {
            $like = '%' . addcslashes($s, '%_\\') . '%';
            $digits = InboundCallService::normalizeMobile($s);
            $q->where(function ($w) use ($s, $like, $digits) {
                if (ctype_digit($s) && strlen($s) <= 18) {
                    $w->orWhere('inbound_calls.lead_id', (int)$s);
                }
                foreach (['inbound_calls.call_id', 'inbound_calls.customer_mobile', 'inbound_calls.vmn', 'inbound_calls.agent_number'] as $col) {
                    $w->orWhere($col, 'like', $like);
                }
                if ($digits !== '') {
                    $w->orWhere('inbound_calls.customer_mobile_normalized', 'like', '%' . $digits . '%');
                }
                $w->orWhere(fn ($n) => $n->where('inbound_calls.status', 'answered')->where('users.name', 'like', $like));
            });
        }
        return $q;
    }

    private function scope($q): void
    {
        $user = $this->user;
        if (in_array($this->role, ['Super Admin', 'Admin', 'Webmaster'], true)) {
            return;
        }
        if (in_array($this->role, ['Manager', 'Team Leader'], true)) {
            $teams = VmnService::teamIds($user);
            if (!$teams) {
                return;   // a manager without a team sees the company's calls
            }
            $teamVmns = DB::table('obd_did')
                ->where(function ($w) use ($teams) {
                    foreach ($teams as $t) $w->orWhereRaw('FIND_IN_SET(?, team_id)', [$t]);
                })
                ->pluck('virtual_number')->map(fn ($n) => InboundCallService::normalizeMobile($n))->filter()->unique()->values()->all();
            $q->where(function ($w) use ($teams, $teamVmns, $user) {
                $w->whereIn('inbound_calls.agent_user_id', DB::table('team_user')->whereIn('team_id', $teams)->select('user_id'))
                    ->orWhere('inbound_calls.agent_user_id', $user->id);
                if ($teamVmns) $w->orWhereIn('inbound_calls.vmn', $teamVmns);
            });
            return;
        }
        // Telecaller and every other role: the calls they answered themselves, callbacks from numbers they called
        // (Click2Call or WebRTC, campaign or manual dial, last CALLBACK_DAYS days) and calls from their own leads.
        // A WebRTC agent has no phone number, so a callback to a WebRTC number is only theirs through the number called.
        $uid = (int)$user->id;
        $tp = DB::getTablePrefix();
        $since = now()->subDays(self::CALLBACK_DAYS);
        $dialed = DB::table('call_logs')->where('created_by', $uid)->where('created_at', '>=', $since)
            ->selectRaw("RIGHT(REGEXP_REPLACE({$tp}call_logs.phonenumber, '[^0-9]', ''), 10) AS n")
            ->union(DB::table('dialer_logs')->where('created_by', $uid)->where('created_at', '>=', $since)
                ->selectRaw("RIGHT(REGEXP_REPLACE({$tp}dialer_logs.phone_number, '[^0-9]', ''), 10) AS n"));
        $q->where(function ($w) use ($uid, $dialed) {
            $w->where(fn ($a) => $a->where('inbound_calls.agent_user_id', $uid)->where('inbound_calls.status', 'answered'))
                ->orWhereIn('inbound_calls.customer_mobile_normalized', DB::query()->fromSub($dialed, 'dialed')->select('n'))
                ->orWhereIn('inbound_calls.lead_id', DB::table('leads')
                    ->where(fn ($l) => $l->where('assigned_to', $uid)->orWhere('sales_person_id', $uid))->select('id'));
        });
    }

    /** How far back a number a telecaller called still makes its inbound call theirs (a callback). */
    public const CALLBACK_DAYS = 90;
}
