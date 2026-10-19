<?php

namespace App\Services;

use App\Models\InboundCall;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound calls to the company's VMNs: saves provider events (webhook) and pulled call logs into inbound_calls,
 * one row per provider call_id, and links each call to its lead (customer number) and agent (agent number).
 * Company isolation: each company has its own database, and only events for this company's VMNs are stored.
 */
class InboundCallService
{
    public const STATUSES = ['ringing', 'missed', 'answered'];   // later in the list wins (a call never goes back)

    private const SECRET_KEYS = '/secret|token|password|passwd|api[_-]?key|signature|authorization/i';
    private const MAX_EVENTS = 20;

    private ?array $companyVmns = null;

    /**
     * Save one provider event (ringing / answered / ended, or a pulled call log) for its call_id.
     *
     * @return array ['result' => created|updated|ignored|invalid, 'call' => ?InboundCall, 'message' => string]
     */
    public function record(array $payload, string $source = 'webhook'): array
    {
        $e = self::extract($payload);
        if ($e['call_id'] === '') {
            return ['result' => 'invalid', 'call' => null, 'message' => 'call_id is required'];
        }
        // a later event of a saved call may leave out the VMN; a new call must come to a VMN of this company
        $known = $e['vmn'] === '' && InboundCall::where('call_id', $e['call_id'])->exists();
        if (!$known && ($e['vmn'] === '' || !$this->isCompanyVmn($e['vmn']))) {
            Log::info('[InboundCalls] Event ignored: the VMN does not belong to this company', [
                'call_id' => $e['call_id'], 'vmn' => $e['vmn'], 'source' => $source,
            ]);
            return ['result' => 'ignored', 'call' => null, 'message' => 'Unknown VMN'];
        }

        try {
            return $this->upsert($e, $payload, $source);
        } catch (UniqueConstraintViolationException $ex) {
            // the same call_id arrived twice at the same moment: the other request created it, update that row
            return $this->upsert($e, $payload, $source);
        }
    }

    private function upsert(array $e, array $payload, string $source): array
    {
        return DB::transaction(function () use ($e, $payload, $source) {
            $call = InboundCall::where('call_id', $e['call_id'])->lockForUpdate()->first();
            $created = !$call;
            $call ??= new InboundCall(['call_id' => $e['call_id']]);

            // a field that an event does not carry keeps its saved value
            foreach (['customer_mobile', 'agent_number'] as $f) {
                if ($e[$f] !== '') $call->{$f} = $e[$f];
            }
            if ($e['vmn'] !== '') $call->vmn = self::normalizeMobile($e['vmn']);   // compared with obd_did numbers (team visibility)
            if ($e['customer_mobile'] !== '') $call->customer_mobile_normalized = self::normalizeMobile($e['customer_mobile']);
            if ($e['start_time'] && (!$call->start_time || $e['start_time']->lt($call->start_time))) $call->start_time = $e['start_time'];
            if ($e['end_time']) $call->end_time = $e['end_time'];
            if ($e['duration'] !== null) $call->duration_seconds = max((int)$call->duration_seconds, $e['duration']);
            if (!$call->duration_seconds && $call->start_time && $call->end_time && $call->end_time->gt($call->start_time)) {
                $call->duration_seconds = $call->end_time->diffInSeconds($call->start_time, true);
            }
            $call->start_time ??= $call->end_time ?? Carbon::now();

            $status = $e['status'] ?? ($e['ended'] ? ((int)$call->duration_seconds > 0 ? 'answered' : 'missed') : null);
            if ($status && array_search($status, self::STATUSES, true) >= (int)array_search($call->status, self::STATUSES, true)) {
                $call->status = $status;
            }

            $call->raw_payload = self::withEvent($call->raw_payload, $payload, $source);
            $this->match($call);
            $call->save();

            return ['result' => $created ? 'created' : 'updated', 'call' => $call, 'message' => 'Saved'];
        });
    }

    /** Fill lead_id / agent_user_id when they are still empty (on save, and by the nightly rematch). */
    public function match(InboundCall $call): void
    {
        if (!$call->lead_id && $call->customer_mobile_normalized) {
            $call->lead_id = self::findLeadId($call->customer_mobile_normalized);
        }
        if (!$call->agent_user_id && $call->agent_number) {
            $call->agent_user_id = self::findUserId(self::normalizeMobile($call->agent_number));
        }
    }

    /** Nightly: try again for calls of the last $days days that have no lead or no agent yet. */
    public function rematch(int $days = 30): int
    {
        $updated = 0;
        InboundCall::where('start_time', '>=', Carbon::now()->subDays($days))
            ->where(fn ($q) => $q->whereNull('lead_id')->orWhere(fn ($w) => $w->whereNull('agent_user_id')->whereNotNull('agent_number')))
            ->chunkById(500, function ($calls) use (&$updated) {
                foreach ($calls as $call) {
                    $this->match($call);
                    if ($call->isDirty()) {
                        $call->save();
                        $updated++;
                    }
                }
            });
        return $updated;
    }

    /* ------------------------------------------------------------------ */

    /** Digits only, without the country code or a leading 0: 9958887262 / +919958887262 / 09958887262 -> 9958887262 */
    public static function normalizeMobile($number): string
    {
        $n = ltrim(preg_replace('/\D/', '', (string)$number), '0');
        if (strlen($n) === 12 && str_starts_with($n, '91')) {
            $n = substr($n, 2);
        }
        return $n;
    }

    /** The most recently active lead with this number (main or second number). */
    public static function findLeadId(string $normalized): ?int
    {
        if ($normalized === '') return null;
        $tp = DB::getTablePrefix();
        $candidates = DB::table('leads')
            ->where(function ($q) use ($tp, $normalized) {
                $q->whereRaw("REGEXP_REPLACE({$tp}leads.phonenumber, '[^0-9]', '') LIKE ?", ['%' . $normalized])
                    ->orWhereRaw("REGEXP_REPLACE({$tp}leads.phonenumber_2, '[^0-9]', '') LIKE ?", ['%' . $normalized]);
            })
            ->orderByRaw("COALESCE({$tp}leads.last_actioned_date, {$tp}leads.updated_at, {$tp}leads.created_at) DESC")
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'phonenumber', 'phonenumber_2']);
        $lead = $candidates->first(fn ($l) => self::normalizeMobile($l->phonenumber) === $normalized || self::normalizeMobile($l->phonenumber_2) === $normalized);
        return $lead ? (int)$lead->id : null;
    }

    /** The user whose mobile or Click2Call agent number is this number. */
    public static function findUserId(string $normalized): ?int
    {
        if ($normalized === '') return null;
        $tp = DB::getTablePrefix();
        $users = DB::table('users')
            ->where(function ($q) use ($tp, $normalized) {
                $q->whereRaw("REGEXP_REPLACE({$tp}users.mobile, '[^0-9]', '') LIKE ?", ['%' . $normalized])
                    ->orWhereRaw("REGEXP_REPLACE({$tp}users.click_agent_number, '[^0-9]', '') LIKE ?", ['%' . $normalized]);
            })
            ->orderByDesc('status')
            ->orderBy('id')
            ->get(['id', 'mobile', 'click_agent_number']);
        $user = $users->first(fn ($u) => self::normalizeMobile($u->mobile) === $normalized || self::normalizeMobile($u->click_agent_number) === $normalized);
        return $user ? (int)$user->id : null;
    }

    /** VMNs of this company: Add Voucher numbers (obd_did) and the Smart31 / 1600 pool. */
    public function isCompanyVmn(string $vmn): bool
    {
        if ($this->companyVmns === null) {
            $numbers = DB::table('obd_did')->pluck('virtual_number');
            if (Schema::hasTable('smart31_vmn_numbers')) {
                $numbers = $numbers->merge(DB::table('smart31_vmn_numbers')->pluck('vmn'));
            }
            $this->companyVmns = $numbers->map(fn ($n) => self::normalizeMobile($n))->filter()->flip()->all();
        }
        return isset($this->companyVmns[self::normalizeMobile($vmn)]);
    }

    /** The provider's field names (webhook events and the incoming_data pull) in one shape. */
    public static function extract(array $p): array
    {
        $get = function (array $keys) use ($p) {
            foreach ($keys as $k) {
                if (isset($p[$k]) && !is_array($p[$k]) && trim((string)$p[$k]) !== '') return trim((string)$p[$k]);
            }
            return '';
        };
        $duration = $get(['duration', 'DURATION', 'talk_time', 'bill_sec', 'billsec', 'call_duration']);
        $event = strtolower($get(['event', 'EVENT', 'event_type', 'status', 'call_status', 'a_dial_status', 'A_DIAL_STATUS', 'dial_status']));

        return [
            'call_id' => mb_substr($get(['call_id', 'CALL_ID', 'CallId', 'callid', 'uniqueid', 'unique_id', 'uuid']), 0, 100),
            'customer_mobile' => mb_substr($get(['cli', 'CLI', 'caller', 'caller_number', 'customer_number', 'customer_mobile', 'from']), 0, 30),
            'vmn' => mb_substr($get(['dni', 'DNI', 'vmn', 'VMN', 'did', 'called_number', 'to']), 0, 30),
            'agent_number' => mb_substr($get(['a_party_no', 'A_PARTY_NO', 'agent_number', 'answered_by']), 0, 30),
            'start_time' => self::time($get(['a_start_time', 'A_START_TIME', 'start_time', 'call_start', 'answer_time'])),
            'end_time' => self::time($get(['a_end_time', 'A_END_TIME', 'end_time', 'call_end'])),
            'duration' => $duration === '' ? null : \App\Helpers\Helper::durationToSeconds($duration),
            'status' => self::status($event),
            'ended' => (bool)preg_match('/end|hangup|complete|disconnect/', $event),
        ];
    }

    /** ringing | answered | missed for a provider event / dial status; null for "ended" (decided by talk time). */
    private static function status(string $event): ?string
    {
        if ($event === '') return null;
        if (preg_match('/no[\s_-]?answer|missed|busy|fail|cancel|reject|unanswered|not[\s_-]?connected/', $event)) return 'missed';
        if (preg_match('/answer|connect|bridge|picked/', $event)) return 'answered';
        if (preg_match('/ring|initiat|incoming|queue|new/', $event)) return 'ringing';
        return null;
    }

    /** A provider time (text in the app timezone unless it carries an offset, or a Unix timestamp) in the app timezone. */
    private static function time(string $v): ?Carbon
    {
        if ($v === '' || str_starts_with($v, '0000')) return null;
        try {
            $tz = config('app.timezone');
            $t = ctype_digit($v) ? Carbon::createFromTimestamp((int)(strlen($v) > 10 ? intdiv((int)$v, 1000) : $v)) : Carbon::parse($v, $tz);
            return $t->setTimezone($tz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** raw_payload: the events received for the call (without secrets), newest last; an event received again is not added twice. */
    private static function withEvent($saved, array $payload, string $source): array
    {
        $clean = self::withoutSecrets($payload);
        $events = is_array($saved['events'] ?? null) ? $saved['events'] : [];
        $hash = md5(json_encode($clean));
        if (!collect($events)->contains(fn ($ev) => ($ev['hash'] ?? '') === $hash)) {
            $events[] = ['source' => $source, 'received_at' => Carbon::now()->format('Y-m-d H:i:s'), 'hash' => $hash, 'payload' => $clean];
        }
        return ['events' => array_slice($events, -self::MAX_EVENTS)];
    }

    public static function withoutSecrets(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (is_string($k) && preg_match(self::SECRET_KEYS, $k)) continue;
            $out[$k] = is_array($v) ? self::withoutSecrets($v) : $v;
        }
        return $out;
    }
}
