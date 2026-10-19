<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InboundCall;
use App\Models\WebmasterSection;
use App\Services\InboundCallDestination;
use App\Services\InboundCallQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/** Reports > Inbound Calls: calls to the company's VMNs (inbound_calls), table + KPI cards + Excel / CSV export. */
class InboundCallsController extends Controller
{
    public const COLUMNS = ['Lead ID', 'Call ID', 'Customer Mobile', 'VMN', 'Start Time', 'Agent Number', 'Agent Name'];
    public const MAX_DAYS = 92;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!(int)(Auth::user()->permissionsGroup->inbound_calls_status ?? 0)) {
                return $request->ajax() || $request->wantsJson()
                    ? response()->json(['message' => 'You do not have permission to view inbound calls.'], 403)
                    : redirect()->route('NoPermission');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        return view('dashboard.reports.inboundCalls', compact('GeneralWebmasterSections'));
    }

    /** POST: one page of rows, the total and the three KPI numbers, all from the same filtered query. */
    public function data(Request $request)
    {
        $v = $this->validated($request, true);
        $query = $this->query($v);
        $kpis = $query->kpis();
        $rows = $query->rows((int)($v['start'] ?? 0), (int)($v['length'] ?? 10), $v['sort'] ?? 'start_time', $v['dir'] ?? 'desc');

        // Customer mobile link: where it opens (tooltip), for the whole page with one lead query
        $where = (new InboundCallDestination(Auth::user()))->forNumbers(array_column($rows, 'customer_mobile_normalized'));
        foreach ($rows as &$r) {
            $d = $where[$r['customer_mobile_normalized']] ?? null;
            $r['call_url'] = route('inboundCallOpen', ['inboundCall' => $r['id']]);
            $r['call_kind'] = $d['type'] ?? 'manual';
            $r['call_title'] = $d['title'] ?? 'Call in Manual Dial';
            unset($r['customer_mobile_normalized']);
        }
        unset($r);

        return response()->json([
            'draw' => (int)($v['draw'] ?? 0),
            'recordsTotal' => $kpis['total'],
            'recordsFiltered' => $kpis['total'],
            'kpis' => $kpis,
            'data' => $rows,
        ]);
    }

    /**
     * GET /reports/inbound-calls/{inboundCall}/call: where to call this customer back, decided now (fresh data).
     * 302 to the campaign lead calling page or to Manual Dial with the number; several campaigns -> the chooser on
     * the Inbound Calls page. With Accept: application/json the decision is returned instead (the page's chooser).
     */
    public function open(Request $request, $inboundCall)
    {
        $call = ctype_digit((string)$inboundCall) ? InboundCall::find((int)$inboundCall) : null;
        if (!$call) {
            abort(404);
        }
        if (!InboundCallQuery::userSees(Auth::user(), $call->id)) {
            abort(403);
        }

        $d = (new InboundCallDestination(Auth::user(), $call->id))->forNumber((string)$call->customer_mobile_normalized);
        if ($request->wantsJson()) {
            if (!empty($d['hidden'])) {
                session()->flash('inboundNotice', 'Opened in Manual Dial');   // shown on the dialer the page opens next
            }
            return response()->json(['type' => $d['type'], 'url' => $d['url'], 'options' => $d['options'],
                'number' => (string)$call->customer_mobile, 'notice' => !empty($d['hidden']) ? 'Opened in Manual Dial' : null]);
        }
        if ($d['type'] === 'choose') {
            return redirect()->route('inboundCalls', ['choose' => $call->id]);
        }
        $redirect = redirect()->to($d['url']);
        return !empty($d['hidden']) ? $redirect->with('inboundNotice', 'Opened in Manual Dial') : $redirect;
    }

    /** GET /reports/inbound-calls/export/{xlsx|csv}: every row of the dates + search (not only the page), in the table's sort. */
    public function export(Request $request, string $format)
    {
        $v = $this->validated($request, false);
        $query = $this->query($v);
        $sort = $v['sort'] ?? 'start_time';
        $dir = $v['dir'] ?? 'desc';
        $f = $query->filters();
        $fileName = 'inbound-calls-' . $f['from'] . ($f['to'] !== $f['from'] ? '-to-' . $f['to'] : '');
        $line = fn (array $r) => [$r['lead_id'] ? (string)$r['lead_id'] : '-', $r['call_id'], $r['customer_mobile'], $r['vmn'],
            $r['start_time'], $r['agent_number'], $r['agent_name']];

        if ($format === 'xlsx') {
            try {
                $lines = [];
                foreach ($query->cursor($sort, $dir) as $r) {
                    $lines[] = $line($r);
                }
                $path = \App\Services\SimpleXlsxWriter::write(self::COLUMNS, $lines, array_fill(0, 7, 'text'),
                    [10, 24, 16, 14, 21, 15, 22], 'Inbound Calls');
                return response()->download($path, $fileName . '.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            } catch (\Throwable $e) {
                Log::error('[InboundCallsExport] ' . $e->getMessage());
                return response()->json(['message' => 'The inbound calls could not be exported. Please try again.'], 500);
            }
        }

        // CSV: UTF-8 with BOM, streamed row by row; text that starts like a formula is prefixed with '
        $safe = fn ($v) => (is_string($v) && strlen($v) > 1 && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
        return response()->streamDownload(function () use ($query, $line, $safe, $sort, $dir) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            foreach ($query->cursor($sort, $dir) as $r) {
                fputcsv($out, array_map($safe, $line($r)));
            }
            fclose($out);
        }, $fileName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ------------------------------------------------------------------ */

    private function query(array $v): InboundCallQuery
    {
        return new InboundCallQuery(['from' => $v['from_date'], 'to' => $v['to_date'], 'search' => $v['search'] ?? ''], Auth::user());
    }

    /** Every parameter checked; a bad one answers 422 with the field's message (shown under that field). */
    private function validated(Request $request, bool $paging): array
    {
        $rules = [
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:' . implode(',', array_keys(InboundCallQuery::SORTABLE))],
            'dir' => ['nullable', 'in:asc,desc'],
        ];
        if ($paging) {
            $rules += ['start' => ['nullable', 'integer', 'min:0'], 'length' => ['nullable', 'integer', 'in:10,25,50,100'], 'draw' => ['nullable', 'integer']];
        }
        $validator = Validator::make($request->all(), $rules, [
            'to_date.after_or_equal' => "'To Date' cannot be earlier than 'From Date'.",
            'from_date.date_format' => 'Enter a valid date (YYYY-MM-DD).',
            'to_date.date_format' => 'Enter a valid date (YYYY-MM-DD).',
        ]);
        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->hasAny(['from_date', 'to_date'])) return;
            $days = Carbon::parse($request->input('from_date'))->diffInDays(Carbon::parse($request->input('to_date'))) + 1;
            if ($days > self::MAX_DAYS) {
                $validator->errors()->add('to_date', 'Choose a range of at most ' . self::MAX_DAYS . ' days.');
            }
        });
        return $validator->validate();
    }
}
