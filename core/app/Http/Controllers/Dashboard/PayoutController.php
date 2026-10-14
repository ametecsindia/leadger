<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\WebmasterSection;
use DB;

class PayoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the payouts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Payouts = Payout::all();

        return view('dashboard.payout.index', compact('Payouts', 'GeneralWebmasterSections'));
    }

    /**
     * Show the form to create a new payout.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view('dashboard.payout.create', compact('GeneralWebmasterSections'));
    }

    /**
     * Store a newly created payout.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
public function store(Request $request)
{
    $request->validate([
        'role_name' => 'required|string',
        'payout_type' => 'required|in:paybycall,paybyduration,both',
    ]);

    $type = $request->payout_type;
    //dd($type);
    $data = $request->only(['role_name', 'payout_type']);

    if (in_array($type, ['paybycall'])) {
        $data = array_merge($data, $request->only([
            'first_range_min_call', 'first_range_max_call', 'first_range_pay_call',
            'second_range_min_call', 'second_range_max_call', 'second_range_pay_call',
            'third_range_min_call', 'third_range_max_call', 'third_range_pay_call',
            'fourth_range_min_call', 'fourth_range_max_call', 'fourth_range_pay_call',
        ]));
    }

    if (in_array($type, ['paybyduration'])) {
        $data = array_merge($data, $request->only([
            'first_range_min_duration', 'first_range_max_duration', 'first_range_pay_duration',
            'second_range_min_duration', 'second_range_max_duration', 'second_range_pay_duration',
            'third_range_min_duration', 'third_range_max_duration', 'third_range_pay_duration',
            'fourth_range_min_duration', 'fourth_range_max_duration', 'fourth_range_pay_duration',
        ]));
    }
        if (in_array($type, ['both'])) {
        $data = array_merge($data, $request->only([
            'first_range_min_call', 'first_range_max_call', 'first_range_pay_call',
            'second_range_min_call', 'second_range_max_call', 'second_range_pay_call',
            'third_range_min_call', 'third_range_max_call', 'third_range_pay_call',
            'fourth_range_min_call', 'fourth_range_max_call', 'fourth_range_pay_call',
            'first_range_min_duration', 'first_range_max_duration', 'first_range_pay_duration',
            'second_range_min_duration', 'second_range_max_duration', 'second_range_pay_duration',
            'third_range_min_duration', 'third_range_max_duration', 'third_range_pay_duration',
            'fourth_range_min_duration', 'fourth_range_max_duration', 'fourth_range_pay_duration',
        ]));
    }

    Payout::create($data);

    return redirect()->route('payout.index')->with('success', 'Payout created successfully.');
}



    /**
     * Show the form to edit an existing payout.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Payout = Payout::findOrFail($id);

        return view('dashboard.payout.edit', compact('Payout', 'GeneralWebmasterSections'));
    }

    /**
     * Update the specified payout.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
public function update(Request $request, $id)
{
    $Payout = Payout::find($id);

    if (!$Payout) {
        return redirect()->route('payout.index')->with('error', 'Payout not found.');
    }

    // Validate base fields
    $rules = [
        'role_name' => 'required|string',
        'payout_type' => 'required|in:paybycall,paybyduration,both',
    ];

    $type = $request->payout_type;

    // Add call fields if needed
    if (in_array($type, ['paybycall', 'both'])) {
        $rules = array_merge($rules, [
            'first_range_min_call' => 'nullable|integer',
            'first_range_max_call' => 'nullable|integer',
            'first_range_pay_call' => 'nullable|numeric',

            'second_range_min_call' => 'nullable|integer',
            'second_range_max_call' => 'nullable|integer',
            'second_range_pay_call' => 'nullable|numeric',

            'third_range_min_call' => 'nullable|integer',
            'third_range_max_call' => 'nullable|integer',
            'third_range_pay_call' => 'nullable|numeric',

            'fourth_range_min_call' => 'nullable|integer',
            'fourth_range_max_call' => 'nullable|integer',
            'fourth_range_pay_call' => 'nullable|numeric',
        ]);
    }

    // Add duration fields if needed
    if (in_array($type, ['paybyduration', 'both'])) {
        $rules = array_merge($rules, [
            'first_range_min_duration' => 'required|integer',
            'first_range_max_duration' => 'required|integer',
            'first_range_pay_duration' => 'required|numeric',

            'second_range_min_duration' => 'required|integer',
            'second_range_max_duration' => 'required|integer',
            'second_range_pay_duration' => 'required|numeric',

            'third_range_min_duration' => 'required|integer',
            'third_range_max_duration' => 'required|integer',
            'third_range_pay_duration' => 'required|numeric',

            'fourth_range_min_duration' => 'required|integer',
            'fourth_range_max_duration' => 'required|integer',
            'fourth_range_pay_duration' => 'required|numeric',
        ]);
    }

    // Validate request
    $validated = $request->validate($rules);

    // Update fields
    $Payout->role_name = $request->role_name;
    $Payout->payout_type = $request->payout_type;

    if (in_array($type, ['paybycall'])) {
        $Payout->first_range_min_call = $request->first_range_min_call;
        $Payout->first_range_max_call = $request->first_range_max_call;
        $Payout->first_range_pay_call = $request->first_range_pay_call;

        $Payout->second_range_min_call = $request->second_range_min_call;
        $Payout->second_range_max_call = $request->second_range_max_call;
        $Payout->second_range_pay_call = $request->second_range_pay_call;

        $Payout->third_range_min_call = $request->third_range_min_call;
        $Payout->third_range_max_call = $request->third_range_max_call;
        $Payout->third_range_pay_call = $request->third_range_pay_call;

        $Payout->fourth_range_min_call = $request->fourth_range_min_call;
        $Payout->fourth_range_max_call = $request->fourth_range_max_call;
        $Payout->fourth_range_pay_call = $request->fourth_range_pay_call;
    }

    if (in_array($type, ['paybyduration'])) {
        $Payout->first_range_min_duration = $request->first_range_min_duration;
        $Payout->first_range_max_duration = $request->first_range_max_duration;
        $Payout->first_range_pay_duration = $request->first_range_pay_duration;

        $Payout->second_range_min_duration = $request->second_range_min_duration;
        $Payout->second_range_max_duration = $request->second_range_max_duration;
        $Payout->second_range_pay_duration = $request->second_range_pay_duration;

        $Payout->third_range_min_duration = $request->third_range_min_duration;
        $Payout->third_range_max_duration = $request->third_range_max_duration;
        $Payout->third_range_pay_duration = $request->third_range_pay_duration;

        $Payout->fourth_range_min_duration = $request->fourth_range_min_duration;
        $Payout->fourth_range_max_duration = $request->fourth_range_max_duration;
        $Payout->fourth_range_pay_duration = $request->fourth_range_pay_duration;
    }
        if (in_array($type, ['both'])) {
        $Payout->first_range_min_call = $request->first_range_min_call;
        $Payout->first_range_max_call = $request->first_range_max_call;
        $Payout->first_range_pay_call = $request->first_range_pay_call;

        $Payout->second_range_min_call = $request->second_range_min_call;
        $Payout->second_range_max_call = $request->second_range_max_call;
        $Payout->second_range_pay_call = $request->second_range_pay_call;

        $Payout->third_range_min_call = $request->third_range_min_call;
        $Payout->third_range_max_call = $request->third_range_max_call;
        $Payout->third_range_pay_call = $request->third_range_pay_call;

        $Payout->fourth_range_min_call = $request->fourth_range_min_call;
        $Payout->fourth_range_max_call = $request->fourth_range_max_call;
        $Payout->fourth_range_pay_call = $request->fourth_range_pay_call;
        $Payout->first_range_min_duration = $request->first_range_min_duration;
        $Payout->first_range_max_duration = $request->first_range_max_duration;
        $Payout->first_range_pay_duration = $request->first_range_pay_duration;

        $Payout->second_range_min_duration = $request->second_range_min_duration;
        $Payout->second_range_max_duration = $request->second_range_max_duration;
        $Payout->second_range_pay_duration = $request->second_range_pay_duration;

        $Payout->third_range_min_duration = $request->third_range_min_duration;
        $Payout->third_range_max_duration = $request->third_range_max_duration;
        $Payout->third_range_pay_duration = $request->third_range_pay_duration;

        $Payout->fourth_range_min_duration = $request->fourth_range_min_duration;
        $Payout->fourth_range_max_duration = $request->fourth_range_max_duration;
        $Payout->fourth_range_pay_duration = $request->fourth_range_pay_duration;
    }

    $Payout->save();

    return redirect()->route('payout.index')->with('success', 'Payout updated successfully.');
}

    
    

    /**
     * Remove the specified payout from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!\App\Helpers\Helper::canDelete()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        // Find the payout and delete it
        $Payout = Payout::findOrFail($id);
        DB::table('users')->where('role_id', $id)->update(['role_id' => null]);
        

        $Payout->delete();

        return redirect()->route('payout.index')->with('success', 'Payout deleted successfully');
    }

    /**
     * Display the payout report page.
     *
     * @return \Illuminate\Http\Response
     */
    public function payout()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view('dashboard.payout.payout', compact('GeneralWebmasterSections'));
    }

    /**
     * Get the payout data for the report.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
public function payoutData(Request $request)
{
    $draw = $request->input('draw');
    $start = $request->start;
    $length = $request->length;
    $from = $request->from_date ? Carbon::parse($request->from_date) : Carbon::today();
    $to = $request->to_date ? Carbon::parse($request->to_date) : Carbon::today();

    if ($from->diffInDays($to) > 6) {
        return response()->json([
            'status' => false,
            'message' => "You can't give more than a week"
        ]);
    }

    $fromFormatted = $from->format('Y-m-d');
    $toFormatted = $to->format('Y-m-d');

    $columns = [
        'u.name',
        'u.account_number',
        'u.ifsc_code',
        'u.branch_name',
        'call_log_total',
        'call_log_connected',
        'call_log_not_connected',
        'call_log_duration',
        'dialer_log_total',
        'dialer_log_connected',
        'dialer_log_not_connected',
        'dialer_log_duration',
        'total_connected_calls',
        'total_not_connected_calls',
        'total_connected_duration',
        'pay_for_calls',
        'pay_for_duration',
        'total_payout'
    ];

    $orderColumnIndex = $request->input('order.0.column', 0);
    $orderColumn = $columns[$orderColumnIndex] ?? 'u.name';
    $orderDir = $request->input('order.0.dir', 'asc');

    // Base users with payout
    $base = DB::table('users as u')
        ->join('permissions as perm', 'u.permissions_id', '=', 'perm.id')
        ->leftJoin('payout as p', 'u.role_id', '=', 'p.id')
        ->where('perm.name', 'Telecaller');

    // Subqueries
    $callLogs = DB::table('call_logs')
        ->select('created_by', DB::raw('COUNT(*) as call_log_total'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $callLogsConnected = DB::table('call_logs')
        ->select('created_by', DB::raw('COUNT(*) as call_log_connected'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $callLogsNotConnected = DB::table('call_logs')
        ->select('created_by', DB::raw('COUNT(*) as call_log_not_connected'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) != 'Connected'")
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $callLogsDuration = DB::table('call_logs')
        ->select('created_by', DB::raw('
            SUM(TIMESTAMPDIFF(
                SECOND,
                STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(click_response, "$.B_PARTY_CONNECTED_TIME")), "%d%m%Y%H%i%s"),
                STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(click_response, "$.B_PARTY_END_TIME")), "%d%m%Y%H%i%s")
            )) as call_log_duration'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $dialerLogs = DB::table('dialer_logs')
        ->select('created_by', DB::raw('COUNT(*) as dialer_log_total'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $dialerLogsConnected = DB::table('dialer_logs')
        ->select('created_by', DB::raw('COUNT(*) as dialer_log_connected'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $dialerLogsNotConnected = DB::table('dialer_logs')
        ->select('created_by', DB::raw('COUNT(*) as dialer_log_not_connected'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) != 'Connected'")
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    $dialerLogsDuration = DB::table('dialer_logs')
        ->select('created_by', DB::raw('
            SUM(TIMESTAMPDIFF(
                SECOND,
                STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(click_response, "$.B_PARTY_CONNECTED_TIME")), "%d%m%Y%H%i%s"),
                STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(click_response, "$.B_PARTY_END_TIME")), "%d%m%Y%H%i%s")
            )) as dialer_log_duration'))
        ->whereBetween('created_at', ["$fromFormatted 00:00:00", "$toFormatted 23:59:59"])
        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.B_DIAL_STATUS')) = 'Connected'")
        ->groupBy('created_by');

    // Final main query
    $query = $base
        ->leftJoinSub($callLogs, 'leadger_cl', 'u.id', '=', 'leadger_cl.created_by')
        ->leftJoinSub($callLogsConnected, 'leadger_clc', 'u.id', '=', 'leadger_clc.created_by')
        ->leftJoinSub($callLogsNotConnected, 'leadger_clnc', 'u.id', '=', 'leadger_clnc.created_by')
        ->leftJoinSub($callLogsDuration, 'leadger_cld', 'u.id', '=', 'leadger_cld.created_by')
        ->leftJoinSub($dialerLogs, 'leadger_dl', 'u.id', '=', 'leadger_dl.created_by')
        ->leftJoinSub($dialerLogsConnected, 'leadger_dlc', 'u.id', '=', 'leadger_dlc.created_by')
        ->leftJoinSub($dialerLogsNotConnected, 'leadger_dlnc', 'u.id', '=', 'leadger_dlnc.created_by')
        ->leftJoinSub($dialerLogsDuration, 'leadger_dld', 'u.id', '=', 'leadger_dld.created_by')
        ->select([
            'u.name',
            'u.account_number',
            'u.ifsc_code',
            'u.branch_name',
            'u.upi_id',
            'p.payout_type',
            'p.first_range_max_call',
            'p.second_range_max_call',
            'p.third_range_max_call',
            'p.first_range_pay_call',
            'p.second_range_pay_call',
            'p.third_range_pay_call',
            'p.fourth_range_pay_call',
            'p.first_range_max_duration',
            'p.second_range_max_duration',
            'p.third_range_max_duration',
            'p.first_range_pay_duration',
            'p.second_range_pay_duration',
            'p.third_range_pay_duration',
            'p.fourth_range_pay_duration',
            DB::raw('COALESCE(leadger_leadger_cl.call_log_total,0) as call_log_total'),
            DB::raw('COALESCE(leadger_leadger_clc.call_log_connected,0) as call_log_connected'),
            DB::raw('COALESCE(leadger_leadger_clnc.call_log_not_connected,0) as call_log_not_connected'),
            DB::raw('COALESCE(leadger_leadger_cld.call_log_duration,0) as call_log_duration'),
            DB::raw('COALESCE(leadger_leadger_dl.dialer_log_total,0) as dialer_log_total'),
            DB::raw('COALESCE(leadger_leadger_dlc.dialer_log_connected,0) as dialer_log_connected'),
            DB::raw('COALESCE(leadger_leadger_dlnc.dialer_log_not_connected,0) as dialer_log_not_connected'),
            DB::raw('COALESCE(leadger_leadger_dld.dialer_log_duration,0) as dialer_log_duration'),
        ]);

    // SQL Sorting if DB field
    $sortAfter = null;
    if (!Str::startsWith($orderColumn, 'u.') && !in_array($orderColumn, [
        'call_log_total', 'call_log_connected', 'call_log_not_connected', 'call_log_duration',
        'dialer_log_total', 'dialer_log_connected', 'dialer_log_not_connected', 'dialer_log_duration'
    ])) {
        $sortAfter = $orderColumn;
        $query = $query->orderByRaw('1=1');
    } else {
        $query = $query->orderBy($orderColumn, $orderDir);
    }

    $recordsTotal = $query->count();
    $rows = $query->offset($start)->limit($length)->get();

    $data = $rows->map(function ($r) {
        $total_connected_calls = $r->call_log_connected + $r->dialer_log_connected;
        $total_not_connected_calls = $r->call_log_not_connected + $r->dialer_log_not_connected;
        $total_calls = $total_connected_calls + $total_not_connected_calls;
        $total_connected_duration = $r->call_log_duration + $r->dialer_log_duration;

        $payCalls = $payDur = 0;

        if ($r->payout_type == 'paybycall') {
            $payCalls = $this->calcSlabPayout($total_calls,
                [$r->first_range_max_call, $r->second_range_max_call, $r->third_range_max_call],
                [$r->first_range_pay_call, $r->second_range_pay_call, $r->third_range_pay_call, $r->fourth_range_pay_call]);
        } elseif ($r->payout_type == 'paybyduration') {
            $payDur = $this->calcSlabPayout($total_connected_duration / 60,
                [$r->first_range_max_duration, $r->second_range_max_duration, $r->third_range_max_duration],
                [$r->first_range_pay_duration, $r->second_range_pay_duration, $r->third_range_pay_duration, $r->fourth_range_pay_duration]);
        } elseif ($r->payout_type == 'both') {
            $payCalls = $this->calcSlabPayout($total_not_connected_calls,
                [$r->first_range_max_call, $r->second_range_max_call, $r->third_range_max_call],
                [$r->first_range_pay_call, $r->second_range_pay_call, $r->third_range_pay_call, $r->fourth_range_pay_call]);
            $payDur = $this->calcSlabPayout($total_connected_duration / 60,
                [$r->first_range_max_duration, $r->second_range_max_duration, $r->third_range_max_duration],
                [$r->first_range_pay_duration, $r->second_range_pay_duration, $r->third_range_pay_duration, $r->fourth_range_pay_duration]);
        }

        return [
            'name' => $r->name,
            'account_number' => $r->account_number,
            'ifsc_code' => $r->ifsc_code,
            'branch_name' => $r->branch_name,
            'upi_id' => $r->upi_id,
            'call_log_total' => $r->call_log_total,
            'call_log_connected' => $r->call_log_connected,
            'call_log_not_connected' => $r->call_log_not_connected,
            'call_log_duration' => round($r->call_log_duration / 60, 2),
            'dialer_log_total' => $r->dialer_log_total,
            'dialer_log_connected' => $r->dialer_log_connected,
            'dialer_log_not_connected' => $r->dialer_log_not_connected,
            'dialer_log_duration' => round($r->dialer_log_duration / 60, 2),
            'total_connected_calls' => $total_connected_calls,
            'total_not_connected_calls' => $total_not_connected_calls,
            'total_connected_duration' => round($total_connected_duration / 60, 2),
            'pay_for_calls' => round($payCalls, 2),
            'pay_for_duration' => round($payDur, 2),
            'total_payout' => round($payCalls + $payDur, 2),
        ];
    });

    // In-memory sort for computed fields if needed
    if ($sortAfter) {
        $data = $orderDir === 'asc'
            ? $data->sortBy($sortAfter)->values()
            : $data->sortByDesc($sortAfter)->values();
    }
    $totalPayout = $data->sum('total_payout');

    return response()->json([
        'draw' => intval($draw),
        'recordsTotal' => $recordsTotal,
        'total_payout' => $totalPayout,
        'recordsFiltered' => $recordsTotal,
        'data' => $data,
    ]);
}



/**
 * Calculate slab-based payout
 *
 * @param int|float $value
 * @param array $maxRanges  e.g. [firstMax, secondMax, thirdMax]
 * @param array $payRates   e.g. [firstRate, secondRate, thirdRate, fourthRate]
 * @return float
 */
protected function calcSlabPayout($value, array $maxRanges, array $payRates)
{
    [$m1, $m2, $m3] = $maxRanges;
    [$r1, $r2, $r3, $r4] = $payRates;

    if ($value <= $m1) return $value * $r1;
    if ($value <= $m2) return ($m1 * $r1) + (($value - $m1) * $r2);
    if ($value <= $m3) return ($m1 * $r1) + (($m2 - $m1) * $r2) + (($value - $m2) * $r3);
    return ($m1 * $r1) + (($m2 - $m1) * $r2) + (($m3 - $m2) * $r3) + (($value - $m3) * $r4);
}

    
}
