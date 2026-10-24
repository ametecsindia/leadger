<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CallLog;
use App\Models\Click2CallLog;
use App\Models\IncomingVmnLogs;
use App\Models\Lead;
use App\Models\Status;
use App\Models\WebmasterSection;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class ReportsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * View Campaign Leads.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */


    public function callReport()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $Telecallers = self::reportAgents(Auth::user());

        return view("dashboard.reports.callReport", compact("GeneralWebmasterSections", "Campaigns", "Telecallers"));
    }

    /**
     * Agent filter of the Call Report: every active user who can make calls and whom the viewer may see (not only the
     * Telecaller role): admins all users, managers / team leaders their teams' users, everyone else themselves.
     */
    public static function reportAgents($user)
    {
        $role = $user->permissionsGroup->name ?? '';
        $q = User::where('status', 1)->orderBy('name');
        if (in_array($role, ['Manager', 'Team Leader'], true)) {
            $teams = array_values(array_filter(array_map('intval', explode(',', (string)$user->team_id)))) ?: [0];
            $q->where(fn ($w) => $w->whereIn('id', DB::table('team_user')->whereIn('team_id', $teams)->select('user_id'))
                ->orWhere('id', $user->id));
        } elseif (!in_array($role, ['Super Admin', 'Admin', 'Webmaster'], true)) {
            $q->where('id', $user->id);
        }
        return $q->get(['id', 'name']);
    }

    /**
     * AJAX endpoint for the Call Report table (DataTables, server side): ALL calls, campaign calls (call_logs) and
     * manual dial calls (dialer_logs), from one query (App\Services\CallReportQuery), so rows, KPI cards, counts and
     * the export always match.
     * Filters: from_date, to_date, find_campaign ('' | id | 'manual'), telecaller_id, source (all | campaign |
     * manual), q (search), view (KPI card), status / scope=agents (opened from an Agent Performance card).
     */
    public function callReportData(Request $request)
    {
        $query = $this->callReportQuery($request);
        $kpis = $query->kpis();

        $columns = $request->input('columns', []);
        $sort = $columns[$request->input('order.0.column')]['data'] ?? 'time';
        $rows = $query->rows((int)$request->input('start', 0), (int)$request->input('length', 25), (string)$sort, (string)$request->input('order.0.dir', 'desc'));

        return response()->json(array_merge($kpis, [
            'draw' => (int)$request->input('draw'),
            'recordsTotal' => $kpis['total_calls'],
            'recordsFiltered' => $kpis['view_count'],
            'data' => $rows,
            'scope' => $query->filters()['mode'] === 'agents' ? 'agents' : null,
            'view' => $query->filters()['view'],
            'conversion' => $query->filters()['mode'] === 'agents' ? $query->conversion() : null,
        ]));
    }

    /** The Call Report query for this request's filters and the signed-in user's visibility. */
    private function callReportQuery(Request $request): \App\Services\CallReportQuery
    {
        return new \App\Services\CallReportQuery([
            'from' => $request->input('from_date'),
            'to' => $request->input('to_date'),
            'campaign' => $request->input('find_campaign'),
            'agent' => $request->input('telecaller_id'),
            'source' => $request->input('source'),
            'search' => $request->input('q', $request->input('search.value')),
            'view' => $request->input('view'),
            'status' => $request->input('status'),
            'mode' => $request->input('scope') === 'agents' ? 'agents' : 'report',
        ], Auth::user());
    }

    /**
     * Call Report export (Excel .xlsx / CSV): every row of the table for the current filters, search, card view and
     * sort (same query as the table). GET /reports/call-report/export/{format}?<the table's filters>&sort=&dir=
     */
    public function callReportExport(Request $request, string $format)
    {
        $format = strtolower($format);
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(404);
        }
        $query = $this->callReportQuery($request);
        $sort = (string)$request->input('sort', 'time');
        $dir = (string)$request->input('dir', 'desc');

        // with_url=1: a Recording URL column (link to play / download each recording); otherwise no recording column
        $withUrl = $request->boolean('with_url');
        $headers = ['Lead ID', 'Time', 'Telecaller', 'Source', 'Campaign', 'Customer Name', 'Mobile Number', 'Duration',
            'Duration (sec)', 'Call Result', 'Status', 'Substatus', 'Call Type', 'Feedback', 'Date'];
        $types = ['text', 'time', 'text', 'text', 'text', 'text', 'text', 'text', 'int', 'text', 'text', 'text', 'text', 'text', 'date'];
        $widths = [12, 11, 18, 10, 20, 22, 16, 10, 12, 14, 14, 18, 11, 36, 13];
        if ($withUrl) {
            $headers[] = 'Recording URL';
            $types[] = 'text';
            $widths[] = 60;
        }
        $feedback = fn (array $r) => trim(($r['feedback'] !== '-' ? $r['feedback'] : '') . ($r['comments'] ? ' | ' . $r['comments'] : ''), ' |') ?: '-';
        $line = function (array $r) use ($feedback, $withUrl) {
            $l = [(string)($r['lead_id'] ?? '-'), $r['time'], $r['telecaller'], $r['source'], $r['campaign'], $r['lead'],
                (string)$r['mobile'], $r['duration'], (int)$r['seconds'], $r['call_result'], $r['status'], $r['substatus'], $r['call_type'], $feedback($r), $r['date']];
            if ($withUrl) {
                $l[] = $r['recording_url'] ?: '-';
            }
            return $l;
        };
        $f = $query->filters();
        $fileName = 'call-report-' . $f['from'] . '-to-' . $f['to'];

        try {
            if ($format === 'xlsx') {
                $lines = [];
                foreach ($query->cursor($sort, $dir) as $r) {
                    $at = \Carbon\Carbon::createFromFormat('d-M-Y h:i A', $r['date'] . ' ' . $r['time'], 'UTC');
                    $l = $line($r);
                    $l[1] = $at;
                    $l[14] = $at;
                    $lines[] = $l;
                }
                $path = \App\Services\SimpleXlsxWriter::write($headers, $lines, $types, $widths, 'Call Report');
                return response()->download($path, $fileName . '.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[CallReportExport] ' . $e->getMessage());
            return redirect()->back()->with('errorMessage', 'The call report could not be exported. Please try again.');
        }

        // CSV: UTF-8 with BOM, rows streamed one by one; text that starts like a formula is prefixed with '
        $safe = fn ($v) => (is_string($v) && strlen($v) > 1 && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
        return response()->streamDownload(function () use ($query, $headers, $line, $safe, $sort, $dir) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($query->cursor($sort, $dir) as $r) {
                fputcsv($out, array_map($safe, $line($r)));
            }
            fclose($out);
        }, $fileName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function callLogs()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $telecallerPermission = Permissions::where('name', 'Telecaller')->first();

        $Telecallers = collect(); // default empty
        if ($telecallerPermission) {
            // Get all users with this permission_id
            $Telecallers = User::where('permissions_id', $telecallerPermission->id)->orderBy('name', 'asc')->get();
        }
        if ($Telecallers->isEmpty()) {
            $Telecallers = User::where('status', 1)->orderBy('name', 'asc')->get();
        }

        return view("dashboard.reports.callLogs", compact("GeneralWebmasterSections", "Campaigns","Telecallers"));
    }
    
    public function callLogsData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $q = $request->input('find_q');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $find_campaign = $request->input('find_campaign');
        $telecaller_id = $request->input('telecaller_id'); 
        // the table's column names -> database columns (anything else: newest first)
        $sortable = ['date_time' => 'call_logs.created_at', 'campaign' => 'campaigns.name', 'lead_name' => 'leads.name',
            'number' => 'call_logs.phonenumber', 'agent' => 'users.name', 'call_type' => 'call_logs.call_type',
            'status' => 'statuses.name', 'substatus' => 'substatuses.name', 'duration' => 'call_logs.duration'];
        $order = $sortable[$columns[$request->input('order.0.column')]['data'] ?? ''] ?? 'call_logs.created_at';
        $dir = strtolower((string)$dir) === 'asc' ? 'asc' : 'desc';
        
        $CallLogs = CallLog::select([
            'call_logs.*',
            'statuses.name as status_name',
            'substatuses.name as substatus_name',
            'users.name as called_by', 
            'campaigns.name as campaign_name',
            'campaigns.id as campaign_id',
            'leads.name as customer_name',
            'leads.product_service',   
            'leads.clientType',
            'call_logs.click_response'
        ])
        ->where('call_logs.call_source', 'campaign')
        ->leftJoin('leads', 'leads.id', 'call_logs.lead_id')
        ->leftJoin('campaigns', function($join) {
            $join->on('campaigns.id', '=', 'call_logs.campaign_id')
                 ->orWhere(function($q) {
                     $q->whereNull('call_logs.campaign_id')
                       ->whereColumn('campaigns.id', 'leads.campaign_id');
                 });
        })
        ->leftJoin('users','users.id','call_logs.created_by')
        ->leftJoin('statuses', 'statuses.id', 'call_logs.next_status')
        ->leftJoin('substatuses', 'substatuses.id', 'call_logs.substatus_id');

        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';
        $userTeamIds = array_filter(array_map('intval', explode(',', $user->team_id ?? '')));
        if (!empty($userTeamIds) && !in_array($userRole, ['Super Admin', 'Admin', 'Webmaster'])) {
            $CallLogs = $CallLogs->where(function ($query) use ($userTeamIds) {
                $query->whereIn('campaigns.team_id', $userTeamIds)
                    ->orWhereNull('campaigns.team_id');
            });
        }
                
        if($userRole == 'Telecaller')
        {
            $CallLogs = $CallLogs->where(function ($query) {
                $query->where('leads.assigned_to','=',Auth::user()->id)
                    ->orWhere('leads.is_public', '=', '1')
                    ->orWhere('call_logs.created_by', '=', Auth::user()->id);
                });
        }
        else if($userRole == 'Sales Person')
        {
            $CallLogs = $CallLogs->where(function ($query) {
                $query->where('leads.sales_person_id','=',Auth::user()->id)
                    ->orWhere('leads.is_public', '=', '1')
                    ->orWhere('call_logs.created_by', '=', Auth::user()->id);
                });
        }

        if ($q != "") {
            $CallLogs = $CallLogs->where(function ($query) use ($q) {
                $query->where('call_logs.lead_id', '=', $q)
                        ->orwhere('call_logs.phonenumber', 'like', '%' . $q . '%')
                        ->orWhere('leads.name', 'like', '%' . $q . '%')
                        ->orWhere('users.name','like','%' . $q . '%')
                        ->orWhere('statuses.name','like','%' . $q . '%')
                        ->orWhere('substatuses.name','like','%' . $q . '%')
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(leadger_call_logs.click_response, '$.B_DIAL_STATUS')) LIKE ?", ['%' . $q . '%']);

            });
        }
        if($find_campaign != "")
        {
            $CallLogs = $CallLogs->where(function($query) use ($find_campaign) {
                $query->where('campaigns.id', $find_campaign)
                      ->orWhere('call_logs.campaign_id', $find_campaign);
            });
        }
        if (!empty($telecaller_id)) {
            $CallLogs = $CallLogs->where('call_logs.created_by', $telecaller_id);
        }
        $today = Carbon::today()->format('Y-m-d');

        if (!empty($from_date) && !empty($to_date)) {
            // Use provided range
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
        } elseif (empty($to_date) && !empty($from_date)) {
            // Only to_date given, use from today
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$from_date. " 00:00:00", $today . " 23:59:59"]);
        } else {
            // Default to today's data
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$today . " 00:00:00", $today . " 23:59:59"]);
        }

        $totalData = $CallLogs->count();
        $totalFiltered = $totalData;
        if ($limit == -1||$limit=='All') {
            $limit = $totalData; // Fetch all records if "All" is selected
           // Reset start for proper pagination
        }

        $CallLogs = $CallLogs->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($CallLogs as $CallLog) {
                $nestedData = [];
                $nestedData['log_id'] = $CallLog->id;
                $nestedData['call_log_id'] = $CallLog->id;
                $nestedData['id'] = $CallLog->lead_id ? "<a onclick=\"leadDetail('".$CallLog->lead_id."')\" class='text-primary'>#CL-".$CallLog->id."</a>" : "#CL-".$CallLog->id;
                $Campaign = $CallLog->campaign_id ? \App\Models\Campaign::find($CallLog->campaign_id) : null;
                $maskedNumber = $Campaign ? \App\Helpers\Helper::number_masking($CallLog->phonenumber, $Campaign->number_masking) : $CallLog->phonenumber;

                // Standard 10 columns for Call Logs
                $nestedData['date_time'] = date('d-M-Y h:i A', strtotime($CallLog->created_at));
                $nestedData['date'] = date('d-M-Y', strtotime($CallLog->created_at));
                $nestedData['time'] = date('h:i A', strtotime($CallLog->created_at));
                $nestedData['campaign'] = $CallLog->campaign_name ?? '—';
                $nestedData['campaign_name'] = $CallLog->campaign_name ?? '—';
                $nestedData['lead_name'] = $CallLog->customer_name ?? '—';
                $nestedData['customer_name'] = $CallLog->customer_name ?? '—';
                $nestedData['number'] = $maskedNumber;
                $nestedData['phonenumber'] = $maskedNumber;
                $nestedData['agent'] = $CallLog->called_by ?? '—';
                $nestedData['called_by'] = $CallLog->called_by ?? '—';
                $nestedData['call_type'] = ucfirst($CallLog->call_type ?: 'Click2Call');
                $nestedData['status'] = $CallLog->status_name ?? ucfirst($CallLog->call_state ?: 'Completed');
                $nestedData['status_name'] = $nestedData['status'];
                $nestedData['substatus'] = $CallLog->substatus_name ?? '—';
                $nestedData['substatus_name'] = $nestedData['substatus'];

                $durSec = (int)($CallLog->duration ?? 0);
                $nestedData['duration'] = \App\Helpers\Helper::formatDuration($durSec);

                // Playback only when real audio exists; otherwise show the recording's status
                $recRow = $CallLog->recording_id ? \App\Models\Recording::find($CallLog->recording_id) : null;
                $recStatus = $recRow ? strtolower((string)$recRow->download_status) : '';
                $hasRec = in_array($recStatus, ['completed', 'failed'], true)
                    || (!$recRow && \App\Services\CallRecordingService::validRecordingUrl($CallLog->click_recording_url));
                $nestedData['call_id'] = $CallLog->reference_id ?: ($CallLog->provider_call_id ?: ('call_' . $CallLog->id));
                if ($recStatus === 'unavailable' || $recStatus === 'deleted') {
                    $label = $recStatus === 'deleted' ? 'Deleted' : 'Not available';
                    $tip = $recStatus === 'deleted' ? 'The recording was deleted by an administrator' : 'The call provider did not deliver a recording for this call';
                    $nestedData['recording'] = "<span class='badge' style='background-color: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; font-size: 11px; padding: 4px 8px; border-radius: 4px;' title='{$tip}'>{$label}</span>";
                    $nestedData['play'] = "<span style='color: #94A3B8;'>N/A</span>";
                } elseif ($hasRec) {
                    $playUrl = route('downloadRecordings', [$CallLog->id, 0]);
                    $nestedData['recording'] = "<div style='display: inline-flex; align-items: center; gap: 6px;'>
                        <button type='button' class='btn btn-xs btn-primary rounded-circle' style='width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;' onclick='playCallLogAudio(\"{$playUrl}\", this)' title='Play Recording'><i class='fa fa-play' style='font-size: 11px;'></i></button>
                        <a href='{$playUrl}' class='btn btn-xs btn-outline-secondary rounded-circle' style='width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;' title='Download Recording'><i class='fa fa-download' style='font-size: 11px;'></i></a>
                    </div>";
                    $nestedData['play'] = "<button type='button' class='lg-play-btn' onclick='playCallLogAudio(\"{$playUrl}\", this)'><i class='fa fa-play'></i> Play</button>";
                } elseif ($CallLog->duration > 0 || in_array($CallLog->call_state, ['completed', 'answered'])) {
                    $nestedData['recording'] = "<span class='badge' style='background-color: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; font-size: 11px; padding: 4px 8px; border-radius: 4px;'><i class='fa fa-spinner fa-spin me-1'></i> Processing...</span>";
                    $nestedData['play'] = "<span style='color: #94A3B8;'>Pending</span>";
                } else {
                    $nestedData['recording'] = "<span style='color: #94A3B8;'>N/A</span>";
                    $nestedData['play'] = "<span style='color: #94A3B8;'>N/A</span>";
                }

                $nestedData['action'] = "<a href='javascript:void(0)' onclick=\"leadDetail('".$CallLog->lead_id."')\" class='lg-action-btn-circle' title='View Lead'><i class='fa fa-eye'></i></a>";
                $nestedData['click_recording_url'] = $CallLog->click_recording_url;
                $nestedData['remarks'] = $CallLog->remarks ?? '—';
                $nestedData['created_at'] = date('Y-m-d H:i:s', strtotime($CallLog->created_at));

                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
 
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allocations()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        
        return view("dashboard.reports.allocations", compact("GeneralWebmasterSections", "Campaigns"));
    }

    public function allocationsData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $q = $request->input('find_q');
        $find_campaign = $request->input('find_campaign');
        $find_user_type = $request->input('find_user_type');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."allocation_history allocation
INNER JOIN ".env('DB_TABLE_PREFIX')."leads lead
ON lead.id = allocation.lead_id
INNER JOIN ".env('DB_TABLE_PREFIX')."campaigns campaign
ON campaign.id = lead.campaign_id
INNER JOIN ".env('DB_TABLE_PREFIX')."users added
ON added.id = allocation.created_by
LEFT JOIN ".env('DB_TABLE_PREFIX')."users frm
ON frm.id = allocation.from_user
LEFT JOIN ".env('DB_TABLE_PREFIX')."users t
ON t.id = allocation.to_user 
where 1=1 ";
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $sql .= " and coalesce(frm.team_id,t.team_id) in (".Auth::user()->team_id.") ";
        }
        else if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $sql .= " and user_type = 'Staff' and (frm.id = ".Auth::user()->id." or t.id = ".Auth::user()->id.") ";
        }
        else if(Auth::user()->permissionsGroup->name == 'Sales Person')
        {
            $sql .= " and user_type = 'Sales Person' and (frm.id = ".Auth::user()->id." or t.id = ".Auth::user()->id.") ";
        }

        if ($q != "") {
            $sql .= " and (allocation.lead_id like '%".$q."%' 
                        or added.name like '%".$q."%' 
                        or frm.name like '%".$q."%' 
                        or t.name like '%".$q."%'
                        or campaign.name like '%".$q."%' )";
        }
        if($find_campaign != "")
        {
            $sql .= " and campaign.id = '$find_campaign' ";
        }
        if(!empty($find_user_type))
        {
            $sql .= " and allocation.user_type = '".addslashes($find_user_type)."' ";
        }
        if(!empty($from_date) && !empty($to_date))
        {
            $sql .= " and DATE(allocation.created_at) between '".addslashes($from_date)."' and '".addslashes($to_date)."' ";
        } elseif(!empty($from_date)) {
            $sql .= " and DATE(allocation.created_at) >= '".addslashes($from_date)."' ";
        } elseif(!empty($to_date)) {
            $sql .= " and DATE(allocation.created_at) <= '".addslashes($to_date)."' ";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Allocations = DB::select("Select allocation.*,frm.name AS from_user,t.name AS to_user,added.name AS added_by,campaign.name as campaign_name
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Allocations as $Allocation) {
                $nestedData = [];
                // Lead ID — styled blue pill
                $nestedData['lead_id'] = "<a onclick=\"leadDetail('".$Allocation->lead_id."')\" class='la-lead-id-link'>".$Allocation->lead_id."</a>";
                $nestedData['campaign_name'] = e($Allocation->campaign_name ?? '—');
                $nestedData['user_type'] = e($Allocation->user_type ?? '—');
                $nestedData['from_user'] = !empty($Allocation->from_user) ? e($Allocation->from_user) : '<span class="la-dash">—</span>';
                $nestedData['to_user']   = !empty($Allocation->to_user)   ? e($Allocation->to_user)   : '<span class="la-dash">—</span>';
                // Allocation type badge
                $atype = strtolower(trim($Allocation->allocation_type ?? ''));
                if ($atype === 'transfer') {
                    $badgeCls = 'la-badge-blue';
                } elseif (str_contains($atype, 'save')) {
                    $badgeCls = 'la-badge-green';
                } elseif (str_contains($atype, 'reassign')) {
                    $badgeCls = 'la-badge-purple';
                } else {
                    $badgeCls = 'la-badge-gray';
                }
                $nestedData['allocation_type'] = "<span class='la-badge {$badgeCls}'>".e($Allocation->allocation_type ?? '—')."</span>";
                $nestedData['added_by'] = e($Allocation->added_by ?? '—');

                $nestedData['created_at'] = $Allocation->created_at;
                
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function visitHistory()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.reports.visitHistory", compact("GeneralWebmasterSections"));
    }

    public function visitHistoryData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $q = $request->input('find_q');
        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."tracking_history th
INNER JOIN ".env('DB_TABLE_PREFIX')."users u
ON th.user_id = u.id 
where 1=1 ";
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $sql .= " and u.team_id in (".Auth::user()->team_id.") ";
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $sql .= " and th.user_id = ".Auth::user()->id." ";
        }

        if ($q != "") {
            $sql .= " and (lead_id like '%".$q."%' 
                        or u.name like '%".$q."%' )";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $visitHistory = DB::select("SELECT lead_id,u.name as called_by,th.created_at,lat,`long`,address 
                    ".$sql." order by $order $dir,lead_id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($visitHistory as $row) {
                $nestedData = [];
                $nestedData['lead_id'] = "<a onclick=\"leadDetail('".$row->lead_id."')\" class='text-primary'>".$row->lead_id."</a>";
                $nestedData['called_by'] = $row->called_by;
                $nestedData['created_at'] = $row->created_at;
                $nestedData['lat_long'] = $row->lat.','.$row->long;
                $nestedData['address'] = $row->address;
                $nestedData['map'] = "<div class='action-items'><a target='_blank' href='https://www.google.com/maps/search/".$row->lat.",".$row->long."'><i class='material-icons' data-toggle='tooltip' data-placement='bottom' title='Map'>&#xe0c8;</i></a></div>";
                
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }

    public function downloadRecordings($id, $incoming = 0)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }
        $userRole = $user->permissionsGroup->name ?? '';
        $userId = $user->id;

        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $crm_id = '';
        if ($settings && !empty($settings->full_response)) {
            $full_response = json_decode($settings->full_response, true);
            $crm_id = $full_response['data']['crm_id'] ?? '';
        }

        if ($incoming == 1) {
            $incomingLog = IncomingVmnLogs::find($id);
            if (!$incomingLog || empty($incomingLog->file_path)) {
                abort(404, 'Recording file not found.');
            }
            if (file_exists($incomingLog->file_path)) {
                return response()->download($incomingLog->file_path, basename($incomingLog->file_path), ['Content-Type' => 'audio/wav']);
            }
            abort(404, 'Recording file not found.');
        }

        if ($incoming == 3) {
            // Standalone recording (no call log): serve that recording row's own audio
            $rec = \App\Models\Recording::whereNull('call_log_id')->whereNull('dialer_log_id')->find($id);
            if (!$rec || $rec->download_status === 'deleted') {
                abort(404, 'Recording not found.');
            }
            if (in_array($userRole, ['Telecaller', 'Sales Person']) && $rec->user_id != $userId) {
                abort(403, 'Unauthorized access to recording.');
            }
            $path = \App\Services\CallRecordingService::realAudioPath($rec->file_path);
            if ($path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $type = $ext === 'mp3' ? 'audio/mpeg' : ($ext === 'ogg' ? 'audio/ogg' : 'audio/wav');
                return request()->has('play')
                    ? response()->file($path, ['Content-Type' => $type, 'Accept-Ranges' => 'bytes'])
                    : response()->download($path, basename($path), ['Content-Type' => $type]);
            }
            if ($url = \App\Services\CallRecordingService::validRecordingUrl($rec->recording_url)) {
                return redirect($url);
            }
            abort(404, 'Recording file not found.');
        }

        if ($incoming == 2) {
            // Manual dial call (Dialing, dialer_logs)
            $dialerLog = \App\Models\DialerLog::find($id);
            if (!$dialerLog) {
                abort(404, 'Recording log not found.');
            }

            // Role permission check
            if (in_array($userRole, ['Telecaller', 'Sales Person']) && $dialerLog->created_by != $userId) {
                abort(403, 'Unauthorized access to recording.');
            }

            $filePath = !empty($dialerLog->file_path) ? $dialerLog->file_path : $dialerLog->click_recording_url;
            $recordingRec = $dialerLog->recording_id ? \App\Models\Recording::find($dialerLog->recording_id) : null;
            if ($recordingRec && $recordingRec->download_status === 'deleted') {
                abort(404, 'This recording was deleted.');
            }
            if (!$filePath && $recordingRec) {
                $filePath = $recordingRec->file_path ?: $recordingRec->recording_url;
            }

            $candidatePaths = [
                $filePath,
                \App\Support\Tenancy::path(storage_path('app/recordings')) . DIRECTORY_SEPARATOR . basename($filePath),
                storage_path('app/' . basename($filePath)),
                public_path('assets/uploads/recordings/' . basename($filePath)),
                public_path('uploads/recordings/' . basename($filePath)),
                base_path(basename($filePath)),
                'C:/Clients/lt_files/call_recordings/' . basename($filePath)
            ];

            if ($recordingRec && $recordingRec->file_path) {
                array_unshift($candidatePaths, $recordingRec->file_path);
            }

            $foundPath = null;
            foreach ($candidatePaths as $p) {
                if (!empty($p) && file_exists($p)) {
                    $foundPath = $p;
                    break;
                }
            }


            if ($foundPath) {
                $ext = strtolower(pathinfo($foundPath, PATHINFO_EXTENSION));
                $contentType = match ($ext) {
                    'mp3' => 'audio/mpeg',
                    'ogg' => 'audio/ogg',
                    'm4a' => 'audio/mp4',
                    default => 'audio/wav'
                };

                if (request()->has('play') || request()->header('Range') !== null || str_contains(request()->header('Accept', ''), 'audio')) {
                    return response()->file($foundPath, [
                        'Content-Type' => $contentType,
                        'Accept-Ranges' => 'bytes'
                    ]);
                }
                return response()->download($foundPath, basename($filePath), ['Content-Type' => $contentType]);
            } elseif (!empty($filePath) && filter_var($filePath, FILTER_VALIDATE_URL)) {
                return redirect($filePath);
            } elseif ($recordingRec && !empty($recordingRec->recording_url) && filter_var($recordingRec->recording_url, FILTER_VALIDATE_URL)) {
                return redirect($recordingRec->recording_url);
            }
            abort(404, 'Recording file not found.');
        } else {
            // Call Logs (Campaign Calling)
            $callLog = CallLog::find($id);
            if (!$callLog) {
                abort(404, 'Call log not found.');
            }

            $Lead = $callLog->lead_id ? Lead::find($callLog->lead_id) : null;

            // Role permission check
            if ($userRole == 'Telecaller' && $callLog->created_by != $userId) {
                if (!$Lead || ($Lead->assigned_to != $userId && $Lead->is_public != '1')) {
                    abort(403, 'Unauthorized access to recording.');
                }
            } elseif ($userRole == 'Sales Person' && $callLog->created_by != $userId) {
                if (!$Lead || ($Lead->sales_person_id != $userId && $Lead->is_public != '1')) {
                    abort(403, 'Unauthorized access to recording.');
                }
            }

            $campaignId = $Lead ? $Lead->campaign_id : 0;
            $fullPath = env('recordings_upload') . "/" . $crm_id . "/" . $campaignId . "/" . $callLog->recording;

            $recordingRec = $callLog->recording_id ? \App\Models\Recording::find($callLog->recording_id) : null;
            if ($recordingRec && $recordingRec->download_status === 'deleted') {
                abort(404, 'This recording was deleted.');
            }
            $candidatePaths = [
                $fullPath,
                $callLog->file_path,
                $callLog->recording,
                \App\Support\Tenancy::path(storage_path('app/recordings')) . DIRECTORY_SEPARATOR . basename($callLog->recording),
                storage_path('app/' . basename($callLog->recording)),
                public_path('assets/uploads/recordings/' . basename($callLog->recording)),
                public_path('uploads/recordings/' . basename($callLog->recording)),
                base_path(basename($callLog->recording)),
                'C:/Clients/lt_files/call_recordings/' . basename($callLog->recording)
            ];

            if ($recordingRec && $recordingRec->file_path) {
                array_unshift($candidatePaths, $recordingRec->file_path);
            }

            $foundPath = null;
            foreach ($candidatePaths as $p) {
                if (!empty($p) && file_exists($p)) {
                    $foundPath = $p;
                    break;
                }
            }


            if ($foundPath) {
                $ext = strtolower(pathinfo($foundPath, PATHINFO_EXTENSION));
                $contentType = match ($ext) {
                    'mp3' => 'audio/mpeg',
                    'ogg' => 'audio/ogg',
                    'm4a' => 'audio/mp4',
                    default => ($callLog->file_extension ?? 'audio/wav')
                };

                if (request()->has('play') || request()->header('Range') !== null || str_contains(request()->header('Accept', ''), 'audio')) {
                    return response()->file($foundPath, [
                        'Content-Type' => $contentType,
                        'Accept-Ranges' => 'bytes'
                    ]);
                }
                return response()->download($foundPath, basename($callLog->recording ?: 'recording.wav'), ['Content-Type' => $contentType]);
            } elseif (!empty($callLog->click_recording_url) && filter_var($callLog->click_recording_url, FILTER_VALIDATE_URL)) {
                return redirect($callLog->click_recording_url);
            } elseif (!empty($callLog->recording) && filter_var($callLog->recording, FILTER_VALIDATE_URL)) {
                return redirect($callLog->recording);
            } elseif ($recordingRec && !empty($recordingRec->recording_url) && filter_var($recordingRec->recording_url, FILTER_VALIDATE_URL)) {
                return redirect($recordingRec->recording_url);
            }
            abort(404, 'Recording file not found.');
        }
    }
    /**
     * View Campaign Leads.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function campaignStatus()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $Statuses = Status::where('name','!=','Deal')->get();

        return view("dashboard.reports.campaignStatus", compact("GeneralWebmasterSections", "Campaigns", "Statuses"));
    }
    
    public function campaignStatusData(Request $request)
    {

        $limit = max(1, (int)$request->input('length', 30));
        $start = max(0, (int)$request->input('start', 0));
        $columns = $request->input('columns');
        $dir = strtolower((string)$request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $find_campaign = $request->input('find_campaign');
        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        // only the table's own columns can be sorted on (the value goes into ORDER BY)
        if (!in_array($order, ['campaign_name', 'total', 'call_count', 'leads', 'deal'], true) && !preg_match('/^status\d+$/', (string)$order)) {
            $order = "c.name";
        }

        $q = trim($request->input('find_q') ?? ($request->input('search.value') ?? ($request->input('search')['value'] ?? '')));
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."leads l
INNER JOIN ".env('DB_TABLE_PREFIX')."campaigns c
ON l.campaign_id = c.id
LEFT JOIN ".env('DB_TABLE_PREFIX')."statuses s
ON l.status = s.id
LEFT JOIN (SELECT lead_id,COUNT(*) AS call_count FROM leadger_call_logs WHERE duration IS NOT NULL GROUP BY lead_id) cl
ON l.id = cl.lead_id
LEFT JOIN (SELECT lead_id,COUNT(*) AS visit_count FROM leadger_tracking_history GROUP BY lead_id) th
ON l.id = th.lead_id
where 1=1 ";

        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $sql .= " and c.team_id in (".Auth::user()->team_id.") ";
        }
        else if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $sql .= " and c.team_id in (".Auth::user()->team_id.") and (l.assigned_to = ".Auth::user()->id." or is_public = 1) ";
        }
        else if(Auth::user()->permissionsGroup->name == 'Sales Person')
        {
            $sql .= " and c.team_id in (".Auth::user()->team_id.") and (l.sales_person_id = ".Auth::user()->id." or is_public = 1) ";
        }

        if ($find_campaign != "") {
            $sql .= " and c.id = '".intval($find_campaign)."' ";
        }

        $totalData = DB::select("select count(DISTINCT campaign_id) as cnt ".$sql)[0]->cnt;

        if ($q != "") {
            $escapedQ = addslashes($q);
            $sql .= " and c.name like '%".$escapedQ."%' ";
        }

        $totalFiltered = DB::select("select count(DISTINCT campaign_id) as cnt ".$sql)[0]->cnt;
        
        $Statuses = Status::where('name','!=','Deal')->get();
//order, paginate
        $selectSql = " select c.name as campaign_name,count(*) as total,
                sum(coalesce(call_count,0)) as call_count,
                sum(coalesce(visit_count,0)) as visit_count,
                sum(case when s.lead_pool = '1' then 1 else 0 end) as leads,
                sum(case when s.name = 'Deal' then 1 else 0 end) as deal ";
        foreach($Statuses as $Status)
        {
            $selectSql .= " ,sum(case when l.status = '".$Status->id."' then 1 else 0 end) as status".$Status->id." ";
        }
        $res = DB::select($selectSql.",campaign_id ".$sql." group by campaign_id,c.name order by $order $dir,c.name asc limit $start,$limit");

        // Totals of every campaign row for this filter and search (not only this page): the KPI cards and the
        // table's Total row, so they always match the table
        $sumCols = array_merge(['total', 'call_count', 'leads', 'deal'], $Statuses->map(fn ($s) => 'status' . $s->id)->all());
        $totalsRow = DB::select("select count(*) as campaigns, " . implode(', ', array_map(fn ($col) => "coalesce(sum($col),0) as $col", $sumCols))
            . " from (" . $selectSql . ",campaign_id " . $sql . " group by campaign_id,c.name) x")[0] ?? null;
        $totals = ['campaigns' => (int)($totalsRow->campaigns ?? 0)];
        foreach ($sumCols as $col) {
            $totals[$col] = (int)($totalsRow->{$col} ?? 0);
        }

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['campaign_name'] = "<a href=\"".route('campaignsView',$row->campaign_id)."\" class='text-primary'>".$row->campaign_name."</a>";
                $nestedData['total'] = $row->total;
                $nestedData['call_count'] = $row->call_count;
                $nestedData['visit_count'] = $row->visit_count;
                $nestedData['leads'] = $row->leads . '('.($row->total > 0 ? round($row->leads/$row->total*100,2) : 0).'%)';
                $nestedData['deal'] = $row->deal . '('.($row->total > 0 ? round($row->deal/$row->total*100,2) : 0).'%)';
                foreach($Statuses as $Status)
                {
                    $nestedData['status'.$Status->id] = $row->{'status'.$Status->id} . '('.($row->total > 0 ? round($row->{'status'.$Status->id}/$row->total*100,2) : 0).'%)';
                }
                $data[] = $nestedData;
            }
        }
        $statics = ['totals' => $totals];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
    /**
     * View Campaign Leads.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function userStatus()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $Statuses = Status::where('name','!=','Deal')->get();

        return view("dashboard.reports.userStatus", compact("GeneralWebmasterSections", "Campaigns", "Statuses"));
    }
    
    public function userStatusData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir', 'asc');
        $q = trim($request->input('find_q') ?? ($request->input('search.value') ?? ($request->input('search')['value'] ?? '')));
        $find_campaign = $request->input('find_campaign');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');

        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        if ($order == "") {
            $order = "u.name";
        }
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."leads l
INNER JOIN ".env('DB_TABLE_PREFIX')."campaigns c
ON l.campaign_id = c.id
INNER JOIN ".env('DB_TABLE_PREFIX')."users u
ON l.last_actioned_by = u.id
LEFT JOIN ".env('DB_TABLE_PREFIX')."statuses s
ON l.status = s.id
LEFT JOIN (SELECT lead_id,COUNT(*) AS call_count FROM leadger_call_logs WHERE duration IS NOT NULL GROUP BY lead_id) cl
ON l.id = cl.lead_id
LEFT JOIN (SELECT lead_id,COUNT(*) AS visit_count FROM leadger_tracking_history GROUP BY lead_id) th
ON l.id = th.lead_id
where 1=1 ";

        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $sql .= " and c.team_id in (".Auth::user()->team_id.") ";
        }
        else if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $sql .= " and l.assigned_to = " . Auth::user()->id . " ";
        }
        
        else if(Auth::user()->permissionsGroup->name == 'Sales Person')
        {
            $sql .= " and c.team_id in (".Auth::user()->team_id.") and (l.sales_person_id = ".Auth::user()->id." or is_public = 1) ";
        }

        if ($find_campaign != "") {
            $sql .= " and c.id = '".$find_campaign."' ";
        }
        if (!empty($from_date)) {
            $sql .= " AND DATE(l.last_actioned_date) >= '".date('Y-m-d', strtotime($from_date))."' ";
        }
        if (!empty($to_date)) {
            $sql .= " AND DATE(l.last_actioned_date) <= '".date('Y-m-d', strtotime($to_date))."' ";
        }
        

        $totalData = DB::select("select count(DISTINCT assigned_to) as cnt ".$sql)[0]->cnt;

        if ($q != "") {
            $escapedQ = addslashes($q);
            $sql .= " and u.name like '%".$escapedQ."%' ";
        }

        $totalFiltered = DB::select("select count(DISTINCT assigned_to) as cnt ".$sql)[0]->cnt;
        
        $Statuses = Status::where('name','!=','Deal')->get();
//order, paginate
        $selectSql = " select u.name as assigned,count(distinct campaign_id) as campaign_count,count(*) as total,
                sum(coalesce(call_count,0)) as call_count,
                sum(coalesce(visit_count,0)) as visit_count,
                sum(case when s.lead_pool = '1' then 1 else 0 end) as leads,
                sum(case when s.name = 'Deal' then 1 else 0 end) as deal ";
        foreach($Statuses as $Status)
        {
            $selectSql .= " ,sum(case when l.status = '".$Status->id."' then 1 else 0 end) as status".$Status->id." ";
        }
        $res = DB::select($selectSql.",assigned_to ".$sql." group by assigned_to,u.name order by $order $dir,u.name asc limit $start,$limit");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['assigned'] = $row->assigned;
                $nestedData['campaign_count'] = $row->campaign_count;
                $nestedData['total'] = $row->total;
                $nestedData['call_count'] = $row->call_count;
                $nestedData['visit_count'] = $row->visit_count;
                $nestedData['leads'] = $row->leads . '('.($row->total > 0 ? round($row->leads/$row->total*100,2) : 0).'%)';
                $nestedData['deal'] = $row->deal . '('.($row->total > 0 ? round($row->deal/$row->total*100,2) : 0).'%)';
                foreach($Statuses as $Status)
                {
                    $nestedData['status'.$Status->id] = $row->{'status'.$Status->id} . '('.($row->total > 0 ? round($row->{'status'.$Status->id}/$row->total*100,2) : 0).'%)';
                }
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
    public function payout()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', 1)->get();
        return view('dashboard.payout.payout', compact('GeneralWebmasterSections'));
    }


    public function payoutData(Request $request)
{
    $limit = $request->input('length');
    $start = $request->input('start');
    $columns = $request->input('columns');
    $dir = $request->input('order.0.dir');
    $from_date = $request->input('from_date');
    $to_date = $request->input('to_date');
    $order = $columns[$request->input('order.0.column')]['data'] ?? '';
    if ($order == "") {
        $order = "u.name";
    }

    // -------------------------------
    // Main Query
    // -------------------------------
    $sql = "
    FROM leadger_users u
    LEFT JOIN (
        SELECT 
            created_by, 
            SUM(call_count) AS no_of_calls,
            SUM(payout) AS payout
        FROM (
            SELECT 
                created_by,
                FLOOR( (DATEDIFF(created_at, '{$from_date}') / 7) ) AS week_no, -- group every 6 days as a week
                COUNT(*) AS call_count,
                CASE 
                    WHEN COUNT(*) <= 600 THEN COUNT(*) * 2
                    WHEN COUNT(*) <= 900 THEN (600 * 2) + ((COUNT(*) - 600) * 2.25)
                    WHEN COUNT(*) <= 1250 THEN (600 * 2) + (300 * 2.25) + ((COUNT(*) - 900) * 2.5)
                    ELSE (600 * 2) + (300 * 2.25) + (350 * 2.5) + ((COUNT(*) - 1250) * 2.75)
                END AS payout

            FROM leadger_call_logs
            WHERE DATE(created_at) BETWEEN '{$from_date}' AND '{$to_date}'
            GROUP BY created_by, week_no
        ) AS weekly_payout
        GROUP BY created_by
    ) cl
    ON u.id = cl.created_by
    WHERE 1=1
    ";

    // -------------------------------
    // Total Records
    // -------------------------------
    $totalData = DB::select("SELECT COUNT(*) AS cnt {$sql}")[0]->cnt;
    $totalFiltered = $totalData;

    // -------------------------------
    // Data Query
    // -------------------------------
    $res = DB::select("
        SELECT 
            u.id,
            u.name,
            u.account_number,
            u.ifsc_code,
            u.branch_name,
            COALESCE(cl.no_of_calls, 0) AS no_of_calls,
            COALESCE(cl.payout, 0) AS payout
            {$sql}
        ORDER BY $order $dir, u.name ASC
        LIMIT $start, $limit
    ");

    // -------------------------------
    // Prepare Response
    // -------------------------------
    $data = [];
    foreach ($res as $row) {
        $nestedData = [];
        $nestedData['name'] = $row->name;
        $nestedData['account_number'] = $row->account_number;
        $nestedData['ifsc_code'] = $row->ifsc_code;
        $nestedData['branch_name'] = $row->branch_name;
        $nestedData['no_of_calls'] = $row->no_of_calls;
        $nestedData['payout'] = number_format($row->payout, 2); // formatted nicely
        $data[] = $nestedData;
    }

    // -------------------------------
    // JSON Response for Datatables
    // -------------------------------
    $json_data = [
        "draw" => intval($request->input('draw')),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data,
        "statics" => []
    ];

    echo json_encode($json_data);
}

    
    
    
    public function agentPerformance()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Telecallers = \App\Models\User::orderBy('name', 'asc')->get();
        $Campaigns = \App\Models\Campaign::orderBy('name', 'asc')->get();

        return view("dashboard.reports.agentPerformance", compact("GeneralWebmasterSections", "Telecallers", "Campaigns"));
    }
    
public function agentPerformanceData(Request $request)
{
    $limit = $request->has('length') ? intval($request->input('length')) : 10;
    $start = $request->has('start') ? intval($request->input('start')) : 0;
    $columns = $request->input('columns');
    $dir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
    $from_date = $request->input('from_date', date('Y-m-d'));
    $to_date = $request->input('to_date', date('Y-m-d'));
    // the dates go into the SQL below, so only real Y-m-d values are used
    $isYmd = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
    if (!$isYmd($from_date)) $from_date = date('Y-m-d');
    if (!$isYmd($to_date)) $to_date = date('Y-m-d');
    $campaignId = $request->input('campaign_id');
    $campaignSqlCl = "";
    $campaignSqlDl = "";
    if (!empty($campaignId) && $campaignId !== 'all') {
        $cid = intval($campaignId);
        $campaignSqlCl = " AND campaign_id = $cid";
        $campaignSqlDl = " AND campaign_id = $cid";
    }

    $orderCol = $columns[$request->input('order.0.column')]['data'] ?? 'uname';
    $orderMap = [
        'uname' => 'u.name',
        'role_name' => 'r.name',
        'login' => 'ul.login',
        'logout' => 'ul.logout',
        'call_count' => 'call_count',
        'connected_count' => 'cl.call_connected_count',
        'total_time' => 'cl.total_seconds',
    ];
    $order = $orderMap[$orderCol] ?? 'u.name';
    $limitSql = ($limit > 0) ? "LIMIT $start, $limit" : "";

    $sql = " FROM leadger_users u
        INNER JOIN leadger_permissions r ON u.permissions_id = r.id

        LEFT JOIN (
            SELECT user_id, SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) AS active_usage
            FROM leadger_user_active_usage
            WHERE DATE(start) BETWEEN '$from_date' AND '$to_date'
            GROUP BY user_id
        ) active ON u.id = active.user_id

        LEFT JOIN (
            SELECT l1.user_id,
                l1.login_date AS login,
                l1.logout_date AS logout,
                l1.logged_in_duration AS login_duration
            FROM leadger_user_logs l1
            INNER JOIN (
                SELECT user_id, MAX(id) AS max_id
                FROM leadger_user_logs
                WHERE DATE(login_date) BETWEEN '$from_date' AND '$to_date'
                GROUP BY user_id
            ) l2 ON l1.id = l2.max_id
        ) ul ON u.id = ul.user_id

        LEFT JOIN (
            SELECT created_by,
                COUNT(*) AS call_log_count,
                SUM(CASE WHEN (duration IS NOT NULL AND duration != '' AND duration != '0' AND duration != '00:00') THEN 1 ELSE 0 END) AS call_connected_count,
                SUM(COALESCE(
                    CASE 
                        WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                        ELSE TIME_TO_SEC(duration)
                    END, 0)) AS total_seconds,
                SEC_TO_TIME(SUM(
                    CASE
                        WHEN call_type = 'sip' AND sip_end_time IS NOT NULL AND created_at IS NOT NULL
                        THEN TIMESTAMPDIFF(SECOND, created_at, sip_end_time)
                        ELSE 0
                    END
                )) AS sip_time,
                ROUND(AVG(COALESCE(
                    CASE 
                        WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                        ELSE TIME_TO_SEC(duration)
                    END, 0))) AS avg_call_seconds
            FROM leadger_call_logs
            WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'
              AND (call_source = 'campaign' OR call_source IS NULL)
              $campaignSqlCl
            GROUP BY created_by
        ) cl ON u.id = cl.created_by

        LEFT JOIN (
            SELECT created_by,
                COUNT(*) AS dialer_log_count,
                SUM(CASE 
                    WHEN call_state IN ('completed', 'answered') 
                      OR (time_taken > 0) 
                      OR (duration IS NOT NULL AND duration != '00:00' AND duration != '0' AND duration != '') 
                    THEN 1 ELSE 0 END) AS dialer_connected_count,
                SUM(COALESCE(
                    time_taken,
                    CASE 
                        WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                        ELSE TIME_TO_SEC(duration)
                    END, 0)) AS dialer_seconds
            FROM leadger_dialer_logs
            WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'
              AND (call_source = 'manual' OR call_source IS NULL)
              $campaignSqlDl
            GROUP BY created_by
        ) dl ON u.id = dl.created_by

        WHERE 1=1";

    // Apply user permission filters
    if (in_array(Auth::user()->permissionsGroup->name, ['Manager', 'Team Leader'])) {
        $sql .= " AND u.team_id IN (" . Auth::user()->team_id . ")";
    } else if (!in_array(optional(Auth::user()->permissionsGroup)->name, ['Admin', 'Webmaster'])) {
        $sql .= " AND u.id = " . Auth::user()->id;
    }

    // Online agents filter (currently logged in today)
    if ($request->input('filter') === 'online' || $request->input('online') == 1) {
        $sql .= " AND ul.login IS NOT NULL AND (ul.logout IS NULL OR ul.logout = '')";
    }

    // Search filter (by agent name or role)
    $q = $request->input('search.value') ?: $request->input('find_q') ?: (is_string($request->input('search')) ? $request->input('search') : '');
    if (!empty($q)) {
        $searchTerm = DB::getPdo()->quote("%{$q}%");
        $sql .= " AND (u.name LIKE {$searchTerm} OR r.name LIKE {$searchTerm})";
    }

    // Agent dropdown filter
    $agentId = $request->input('agent_id');
    if (!empty($agentId) && $agentId !== 'all') {
        $sql .= " AND u.id = " . intval($agentId);
    }

    // Count total results
    $totalData = DB::select("SELECT COUNT(*) AS cnt $sql")[0]->cnt;
    $totalFiltered = $totalData;

    // Fetch paginated results with formatted durations and counts
    $res = DB::select("
        SELECT u.id, u.name AS uname, u.status,
               r.name AS role_name,
               ul.login, ul.logout, ul.login_duration,
               active.active_usage,
               cl.call_log_count,
               dl.dialer_log_count,
               (IFNULL(cl.call_log_count, 0) + IFNULL(dl.dialer_log_count, 0)) AS call_count,
               (IFNULL(cl.call_connected_count, 0) + IFNULL(dl.dialer_connected_count, 0)) AS connected_count,
               (IFNULL(cl.total_seconds, 0) + IFNULL(dl.dialer_seconds, 0)) AS total_seconds,
               SEC_TO_TIME(IFNULL(cl.total_seconds, 0) + IFNULL(dl.dialer_seconds, 0)) AS total_time,
               cl.sip_time,
               SEC_TO_TIME(
                   CASE 
                       WHEN (IFNULL(cl.call_connected_count, 0) + IFNULL(dl.dialer_connected_count, 0)) > 0 
                       THEN ROUND((IFNULL(cl.total_seconds, 0) + IFNULL(dl.dialer_seconds, 0)) / (IFNULL(cl.call_connected_count, 0) + IFNULL(dl.dialer_connected_count, 0)))
                       ELSE 0 
                   END
               ) AS avg_call_time
        $sql
        ORDER BY $order $dir, u.name ASC
        $limitSql
    ");

    // Format final data
    $data = [];
    foreach ($res as $row) {
        $callCount = intval($row->call_count ?? 0);
        $connectedCount = intval($row->connected_count ?? 0);
        $interestedCount = round($connectedCount * 0.25);
        $convertedCount = round($connectedCount * 0.10);
        $conversionRate = $callCount > 0 ? round(($convertedCount / $callCount) * 100, 1) . '%' : '0%';
        $userStatus = ($row->status ?? 1) == 1 ? 'Active' : 'Inactive';
        $isOnline = (!empty($row->login) && empty($row->logout));

        $data[] = [
            'id' => $row->id ?? 0,
            'agent_id' => $row->id ?? 0,
            'uname' => $row->uname,
            'agent_name' => $row->uname,
            'role_name' => $row->role_name ?: 'TeleCaller',
            'role' => $row->role_name ?: 'TeleCaller',
            'status' => $userStatus,
            'login' => $row->login,
            'logout' => $row->logout,
            'login_time' => $row->login,
            'logout_time' => $row->logout,
            'online' => $isOnline,
            'login_duration' => $row->login_duration ?? '00:00:00',
            'active_usage' => $row->active_usage ?? '00:00:00',
            'call_log_count' => $row->call_log_count ?? 0,
            'dialer_log_count' => $row->dialer_log_count ?? 0,
            'call_count' => $callCount,
            'total_calls' => $callCount,
            'connected_count' => $connectedCount,
            'connected' => $connectedCount,
            'interested_count' => $interestedCount,
            'interested' => $interestedCount,
            'converted_count' => $convertedCount,
            'converted' => $convertedCount,
            'conversion_rate' => $conversionRate,
            'total_time' => $row->total_time ?? '00:00:00',
            'talk_time' => $row->total_time ?? '00:00:00',
            'sip_time' => $row->sip_time ?? '00:00:00',
            'avg_call_time' => $row->avg_call_time ?? '00:00:00'
        ];
    }

    // Compute real KPI statistics and comparison vs previous 7 days
    $prev_from_date = date('Y-m-d', strtotime($from_date . ' - 7 days'));
    $prev_to_date = date('Y-m-d', strtotime($from_date . ' - 1 day'));

    $userScopeSqlCl = "";
    $userScopeSqlDl = "";
    if (in_array(Auth::user()->permissionsGroup->name, ['Manager', 'Team Leader'])) {
        $userScopeSqlCl .= " AND created_by IN (SELECT user_id FROM leadger_team_user WHERE team_id IN (" . (Auth::user()->team_id ?: 0) . "))";
        $userScopeSqlDl .= " AND created_by IN (SELECT user_id FROM leadger_team_user WHERE team_id IN (" . (Auth::user()->team_id ?: 0) . "))";
    } else if (!in_array(optional(Auth::user()->permissionsGroup)->name, ['Admin', 'Webmaster'])) {
        $userScopeSqlCl .= " AND created_by = " . Auth::user()->id;
        $userScopeSqlDl .= " AND created_by = " . Auth::user()->id;
    }
    if (!empty($agentId) && $agentId !== 'all') {
        $userScopeSqlCl .= " AND created_by = " . intval($agentId);
        $userScopeSqlDl .= " AND created_by = " . intval($agentId);
    }

    // Conversions = calls whose outcome was a "Deal" status (the app's conversion status), campaign + manual
    $dealList = implode(',', DB::table('statuses')->where('name', 'Deal')->pluck('id')->map(fn ($id) => (int)$id)->all() ?: [0]);

    // Current period totals
    $currCl = DB::select("
        SELECT COUNT(*) AS total_calls,
               SUM(CASE WHEN next_status IN ($dealList) THEN 1 ELSE 0 END) AS deal_calls,
               SUM(CASE WHEN (duration IS NOT NULL AND duration != '' AND duration != '0' AND duration != '00:00') THEN 1 ELSE 0 END) AS connected_calls,
               SUM(COALESCE(
                   CASE 
                       WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                       ELSE TIME_TO_SEC(duration)
                   END, 0)) AS total_seconds
        FROM leadger_call_logs
        WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'
          AND (call_source = 'campaign' OR call_source IS NULL)
          $userScopeSqlCl $campaignSqlCl
    ")[0] ?? (object)['total_calls' => 0, 'connected_calls' => 0, 'total_seconds' => 0];

    $currDl = DB::select("
        SELECT COUNT(*) AS total_calls,
               SUM(CASE WHEN status_id IN ($dealList) THEN 1 ELSE 0 END) AS deal_calls,
               SUM(CASE 
                   WHEN call_state IN ('completed', 'answered') 
                     OR (time_taken > 0) 
                     OR (duration IS NOT NULL AND duration != '00:00' AND duration != '0' AND duration != '') 
                   THEN 1 ELSE 0 END) AS connected_calls,
               SUM(COALESCE(
                   time_taken,
                   CASE 
                       WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                       ELSE TIME_TO_SEC(duration)
                   END, 0)) AS total_seconds
        FROM leadger_dialer_logs
        WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'
          AND (call_source = 'manual' OR call_source IS NULL)
          $userScopeSqlDl $campaignSqlDl
    ")[0] ?? (object)['total_calls' => 0, 'connected_calls' => 0, 'total_seconds' => 0];

    $currCalls = intval($currCl->total_calls ?? 0) + intval($currDl->total_calls ?? 0);
    $currConnected = intval($currCl->connected_calls ?? 0) + intval($currDl->connected_calls ?? 0);
    $currSeconds = intval($currCl->total_seconds ?? 0) + intval($currDl->total_seconds ?? 0);
    $currConversions = intval($currCl->deal_calls ?? 0) + intval($currDl->deal_calls ?? 0);
    $currAvgTalkSec = $currConnected > 0 ? round($currSeconds / $currConnected) : 0;
    $currConvRate = $currCalls > 0 ? round(($currConversions / $currCalls) * 100, 1) : 0;

    // Previous 7 days totals
    $prevCl = DB::select("
        SELECT COUNT(*) AS total_calls,
               SUM(CASE WHEN next_status IN ($dealList) THEN 1 ELSE 0 END) AS deal_calls,
               SUM(CASE WHEN (duration IS NOT NULL AND duration != '' AND duration != '0' AND duration != '00:00') THEN 1 ELSE 0 END) AS connected_calls,
               SUM(COALESCE(
                   CASE 
                       WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                       ELSE TIME_TO_SEC(duration)
                   END, 0)) AS total_seconds
        FROM leadger_call_logs
        WHERE DATE(created_at) BETWEEN '$prev_from_date' AND '$prev_to_date'
          AND (call_source = 'campaign' OR call_source IS NULL)
          $userScopeSqlCl $campaignSqlCl
    ")[0] ?? (object)['total_calls' => 0, 'connected_calls' => 0, 'total_seconds' => 0];

    $prevDl = DB::select("
        SELECT COUNT(*) AS total_calls,
               SUM(CASE WHEN status_id IN ($dealList) THEN 1 ELSE 0 END) AS deal_calls,
               SUM(CASE 
                   WHEN call_state IN ('completed', 'answered') 
                     OR (time_taken > 0) 
                     OR (duration IS NOT NULL AND duration != '00:00' AND duration != '0' AND duration != '') 
                   THEN 1 ELSE 0 END) AS connected_calls,
               SUM(COALESCE(
                   time_taken,
                   CASE 
                       WHEN duration REGEXP '^[0-9]+$' THEN CAST(duration AS UNSIGNED)
                       ELSE TIME_TO_SEC(duration)
                   END, 0)) AS total_seconds
        FROM leadger_dialer_logs
        WHERE DATE(created_at) BETWEEN '$prev_from_date' AND '$prev_to_date'
          AND (call_source = 'manual' OR call_source IS NULL)
          $userScopeSqlDl $campaignSqlDl
    ")[0] ?? (object)['total_calls' => 0, 'connected_calls' => 0, 'total_seconds' => 0];

    $prevCalls = intval($prevCl->total_calls ?? 0) + intval($prevDl->total_calls ?? 0);
    $prevConnected = intval($prevCl->connected_calls ?? 0) + intval($prevDl->connected_calls ?? 0);
    $prevSeconds = intval($prevCl->total_seconds ?? 0) + intval($prevDl->total_seconds ?? 0);
    $prevConversions = intval($prevCl->deal_calls ?? 0) + intval($prevDl->deal_calls ?? 0);
    $prevAvgTalkSec = $prevConnected > 0 ? round($prevSeconds / $prevConnected) : 0;
    $prevConvRate = $prevCalls > 0 ? round(($prevConversions / $prevCalls) * 100, 1) : 0;

    $statics = [
        'total_agents' => $totalData,
        'total_calls' => $currCalls,
        'connected_calls' => $currConnected,
        'conversions' => $currConversions,
        'avg_talk_time' => $currAvgTalkSec > 0 ? round($currAvgTalkSec / 60, 1) . ' mins' : '0 mins',
        'conversion_rate' => $currConvRate . '%',
        'trends' => [
            'total_calls' => $this->calculateTrendMetric($currCalls, $prevCalls),
            'connected_calls' => $this->calculateTrendMetric($currConnected, $prevConnected),
            'conversions' => $this->calculateTrendMetric($currConversions, $prevConversions),
            'avg_talk_time' => $this->calculateTrendMetric($currAvgTalkSec, $prevAvgTalkSec),
            'conversion_rate' => $this->calculateTrendMetric($currConvRate, $prevConvRate),
        ]
    ];

    return response()->json([
        "draw" => intval($request->input('draw')),
        "recordsTotal" => $totalData,
        "recordsFiltered" => $totalFiltered,
        "data" => $data,
        "statics" => $statics
    ]);
}

/** Roles that may export the Agent Performance report (the same roles that see "Export Report" on the page). */
private const AGENT_PERFORMANCE_EXPORT_ROLES = ['Webmaster', 'Admin', 'Manager'];

/**
 * Agent Performance Details export (Excel .xlsx / CSV): every row the table shows for the current filters, search and
 * sorting (same query, counting and visibility rules, via agentPerformanceData), not only the visible page.
 * GET /reports/agentPerformance/export/{format}?from_date=&to_date=&agent_id=&campaign_id=&search=&filter=&order_column=&order_dir=
 * Rows are one per agent, so the result is bounded by the number of users; the CSV is streamed.
 */
public function agentPerformanceExport(Request $request, string $format)
{
    $format = strtolower($format);
    if (!in_array($format, ['xlsx', 'csv'], true)) {
        abort(404);
    }
    if (!in_array(optional(Auth::user()->permissionsGroup)->name, self::AGENT_PERFORMANCE_EXPORT_ROLES, true)) {
        return response()->json(['message' => 'You do not have permission to export this report.'], 403);
    }

    // Table columns, in table order (also the sort keys agentPerformanceData understands)
    $columns = ['uname', 'role_name', 'login', 'logout', 'call_count', 'connected_count', 'total_time', 'interested_count', 'converted_count', 'conversion_rate'];
    $validator = Validator::make($request->all(), [
        'from_date' => 'required|date_format:Y-m-d',
        'to_date' => 'required|date_format:Y-m-d|after_or_equal:from_date',
        'agent_id' => ['nullable', 'regex:/^(all|\d+)$/'],
        'campaign_id' => ['nullable', 'regex:/^(all|\d+)$/'],
        'search' => 'nullable|string|max:100',
        'filter' => 'nullable|in:online',
        'order_column' => ['nullable', \Illuminate\Validation\Rule::in($columns)],
        'order_dir' => 'nullable|in:asc,desc',
    ], [
        'to_date.after_or_equal' => "'To Date' cannot be earlier than 'From Date'.",
    ]);
    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first()], 422);
    }

    $from = $request->input('from_date');
    $to = $request->input('to_date');
    $agentId = $request->input('agent_id') ?: 'all';
    $campaignId = $request->input('campaign_id') ?: 'all';
    $search = trim((string)$request->input('search', ''));
    $online = $request->input('filter') === 'online';

    try {
        $sub = Request::create('/', 'POST', [
            'from_date' => $from, 'to_date' => $to, 'agent_id' => $agentId, 'campaign_id' => $campaignId,
            'find_q' => $search, 'search' => ['value' => $search], 'filter' => $online ? 'online' : '',
            'start' => 0, 'length' => 0,   // 0 = no LIMIT: every matching row
            'columns' => array_map(fn ($c) => ['data' => $c], $columns),
            'order' => [['column' => array_search($request->input('order_column', 'uname'), $columns, true), 'dir' => $request->input('order_dir', 'asc')]],
        ]);
        $sub->setUserResolver($request->getUserResolver());
        $rows = $this->agentPerformanceData($sub)->getData(true)['data'] ?? [];
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('[AgentPerformanceExport] ' . $e->getMessage());
        return response()->json(['message' => 'The report could not be exported. Please try again.'], 500);
    }
    if (!$rows) {
        return response()->json(['message' => 'No data to export'], 422);
    }

    // Values as the table shows them: login / logout date and time, "Currently Online", talk time HH:MM:SS, rate with %
    $when = function ($v) {
        if (empty($v) || ($t = strtotime($v)) === false || (int)date('Y', $t) < 1971) return null;
        return Carbon::parse($v);
    };
    $seconds = function ($hms) {
        if (!preg_match('/^(-?)(\d+):(\d{2}):(\d{2})/', (string)$hms, $m)) return 0;
        return ($m[1] === '-' ? -1 : 1) * ((int)$m[2] * 3600 + (int)$m[3] * 60 + (int)$m[4]);
    };
    $headers = ['Agent', 'Role', 'Login', 'Logout', 'Total Calls', 'Connected', 'Talk Time', 'Interested', 'Converted', 'Conv. Rate'];
    $lines = array_map(function ($r) use ($when, $seconds) {
        $login = $when($r['login'] ?? null);
        $logout = !empty($r['online']) ? 'Currently Online' : ($when($r['logout'] ?? null) ?? '-');
        $rate = (float)rtrim((string)($r['conversion_rate'] ?? '0'), '%');
        return [
            'agent' => (string)($r['uname'] ?? '') ?: '-',
            'role' => (string)($r['role_name'] ?? '') ?: '-',
            'login' => $login ?? '-',
            'logout' => $logout,
            'calls' => (int)($r['call_count'] ?? 0),
            'connected' => (int)($r['connected_count'] ?? 0),
            'talk' => (string)($r['total_time'] ?? '') ?: '00:00:00',
            'talk_seconds' => $seconds($r['total_time'] ?? ''),
            'interested' => (int)($r['interested_count'] ?? 0),
            'converted' => (int)($r['converted_count'] ?? 0),
            'rate_text' => ($r['conversion_rate'] ?? '') ?: '0%',
            'rate' => $rate / 100,
        ];
    }, $rows);
    $show = fn ($v) => $v instanceof \DateTimeInterface ? $v->format('d M Y h:i A') : (string)$v;
    $fileName = 'agent-performance-' . $from . '-to-' . $to;

    if ($format === 'xlsx') {
        $cells = array_map(fn ($l) => [$l['agent'], $l['role'], $l['login'], $l['logout'], $l['calls'], $l['connected'],
            $l['talk_seconds'], $l['interested'], $l['converted'], $l['rate']], $lines);

        // Auto-sized columns: the longest shown value (or header) per column
        $widths = array_map(fn ($h) => mb_strlen($h), $headers);
        foreach ($lines as $l) {
            foreach ([$l['agent'], $l['role'], $show($l['login']), $show($l['logout']), $l['calls'], $l['connected'], $l['talk'], $l['interested'], $l['converted'], $l['rate_text']] as $i => $v) {
                $widths[$i] = max($widths[$i], mb_strlen((string)$v));
            }
        }
        $widths = array_map(fn ($w) => min(50, max(10, $w + 3)), $widths);

        $campaignName = $campaignId === 'all' ? 'All Campaigns' : (\App\Models\Campaign::where('id', (int)$campaignId)->value('name') ?? ('Campaign #' . (int)$campaignId));
        $agentName = $agentId === 'all' ? 'All Agents' : (User::where('id', (int)$agentId)->value('name') ?? ('Agent #' . (int)$agentId));
        $preamble = [
            ['Agent Performance'],
            ['Date range', Carbon::parse($from)->format('d M Y') . ' to ' . Carbon::parse($to)->format('d M Y')],
            ['Campaign', $campaignName],
            ['Agent', $agentName],
        ];
        if ($search !== '') $preamble[] = ['Search', $search];
        if ($online) $preamble[] = ['Showing', 'Online agents only'];
        $preamble[] = ['Generated on', now()->format('d M Y h:i A')];
        $preamble[] = ['Generated by', (string)Auth::user()->name];

        $path = \App\Services\SimpleXlsxWriter::write($headers, $cells,
            ['text', 'text', 'datetime_long', 'datetime_long', 'int', 'int', 'duration', 'int', 'int', 'percent'],
            $widths, 'Agent Performance', $preamble);
        return response()->download($path, $fileName . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // CSV: header + data rows only, UTF-8 with BOM; text that starts like a formula is prefixed with '
    $safe = fn ($v) => (is_string($v) && strlen($v) > 1 && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
    return response()->streamDownload(function () use ($lines, $headers, $safe, $show) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($lines as $l) {
            fputcsv($out, array_map($safe, [$l['agent'], $l['role'], $show($l['login']), $show($l['logout']), $l['calls'], $l['connected'],
                $l['talk'], $l['interested'], $l['converted'], $l['rate_text']]));
        }
        fclose($out);
    }, $fileName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
}

private function calculateTrendMetric($curr, $prev)
{
    if ($prev == 0 && $curr == 0) {
        return ['direction' => 'neutral', 'text' => '—', 'has_data' => false];
    }
    if ($prev == 0 && $curr > 0) {
        return ['direction' => 'up', 'text' => '↑ 100%', 'has_data' => true];
    }
    if ($prev > 0) {
        $diff = $curr - $prev;
        $pct = round(($diff / $prev) * 100, 1);
        if ($pct > 0) {
            return ['direction' => 'up', 'text' => '↑ ' . $pct . '%', 'has_data' => true];
        } elseif ($pct < 0) {
            return ['direction' => 'down', 'text' => '↓ ' . abs($pct) . '%', 'has_data' => true];
        } else {
            return ['direction' => 'neutral', 'text' => '0%', 'has_data' => true];
        }
    }
    return ['direction' => 'neutral', 'text' => '—', 'has_data' => false];
}

    /**
     * Export Shift Log for the current shift / specified day as CSV.
     */
    public function exportShiftLog(Request $request)
    {
        $targetDate = $request->input('date', date('Y-m-d'));

        $sql = " FROM leadger_users u
            INNER JOIN leadger_permissions r ON u.permissions_id = r.id
            LEFT JOIN (
                SELECT l1.user_id,
                    l1.login_date AS login,
                    l1.logout_date AS logout,
                    l1.logged_in_duration AS login_duration
                FROM leadger_user_logs l1
                INNER JOIN (
                    SELECT user_id, MAX(id) AS max_id
                    FROM leadger_user_logs
                    WHERE DATE(login_date) = '{$targetDate}'
                    GROUP BY user_id
                ) l2 ON l1.id = l2.max_id
            ) ul ON u.id = ul.user_id

            LEFT JOIN (
                SELECT created_by,
                    COUNT(*) AS call_log_count,
                    SUM(TIME_TO_SEC(duration)) AS total_seconds
                FROM leadger_call_logs
                WHERE DATE(created_at) = '{$targetDate}'
                  AND (call_source = 'campaign' OR call_source IS NULL)
                  AND duration IS NOT NULL
                GROUP BY created_by
            ) cl ON u.id = cl.created_by

            LEFT JOIN (
                SELECT created_by,
                    COUNT(*) AS dialer_log_count,
                    SUM(CASE 
                        WHEN call_state IN ('completed', 'answered') 
                          OR (time_taken > 0) 
                          OR (duration IS NOT NULL AND duration != '00:00' AND duration != '0' AND duration != '') 
                        THEN 1 ELSE 0 END) AS dialer_connected_count,
                    SUM(COALESCE(time_taken, TIME_TO_SEC(duration), 0)) AS dialer_seconds
                FROM leadger_dialer_logs
                WHERE DATE(created_at) = '{$targetDate}'
                  AND (call_source = 'manual' OR call_source IS NULL)
                GROUP BY created_by
            ) dl ON u.id = dl.created_by

            WHERE 1=1";

        if (in_array(optional(Auth::user()->permissionsGroup)->name, ['Manager', 'Team Leader'])) {
            $sql .= " AND u.team_id IN (" . Auth::user()->team_id . ")";
        } else if (!in_array(optional(Auth::user()->permissionsGroup)->name, ['Admin', 'Webmaster'])) {
            $sql .= " AND u.id = " . Auth::user()->id;
        }

        $res = DB::select("
            SELECT u.id, u.name AS uname, u.status,
                   r.name AS role_name,
                   ul.login, ul.logout,
                   cl.call_log_count,
                   dl.dialer_log_count,
                   (IFNULL(cl.call_log_count, 0) + IFNULL(dl.dialer_log_count, 0)) AS call_count,
                   (IFNULL(cl.call_log_count, 0) + IFNULL(dl.dialer_connected_count, 0)) AS connected_count,
                   SEC_TO_TIME(IFNULL(cl.total_seconds, 0) + IFNULL(dl.dialer_seconds, 0)) AS total_time
            $sql
            ORDER BY u.name ASC
        ");

        $rows = [];
        foreach ($res as $row) {
            $callCount = intval($row->call_count ?? 0);
            $connectedCount = intval($row->connected_count ?? ($row->call_log_count ?? 0));
            $hasShift = !empty($row->login) || $callCount > 0;
            if ($hasShift) {
                $interestedCount = round($connectedCount * 0.25);
                $convertedCount = round($connectedCount * 0.10);
                $rows[] = [
                    'Agent' => $row->uname,
                    'Role' => $row->role_name ?: 'TeleCaller',
                    'Login time' => $row->login ?: '-',
                    'Logout time' => $row->logout ?: ($row->login ? 'Active' : '-'),
                    'Total calls' => $callCount,
                    'Connected' => $connectedCount,
                    'Talk time' => $row->total_time ?: '00:00:00',
                    'Interested' => $interestedCount,
                    'Converted' => $convertedCount,
                ];
            }
        }

        if (empty($rows)) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No shift data to export',
                'count' => 0
            ]);
        }

        $fileName = 'shift-log-' . $targetDate . '.csv';
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Agent', 'Role', 'Login time', 'Logout time', 'Total calls', 'Connected', 'Talk time', 'Interested', 'Converted']);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['Agent'],
                    $row['Role'],
                    $row['Login time'],
                    $row['Logout time'],
                    $row['Total calls'],
                    $row['Connected'],
                    $row['Talk time'],
                    $row['Interested'],
                    $row['Converted'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function userLogs()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $Users = \App\Models\User::where('status', 1)->orderBy('name', 'asc')->get();

        return view("dashboard.reports.userLogs", compact("GeneralWebmasterSections", "Campaigns", "Users"));
    }
    
    public function userLogsData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        if ($order == "") {
            $order = "u.name";
        }
        
        $sql = " FROM leadger_user_logs ul
INNER JOIN leadger_users u
ON ul.user_id = u.id
WHERE DATE(login_date) between '".$from_date."' and '".$to_date."'
 ";
        if (in_array(Auth::user()->permissionsGroup->name, ['Telecaller', 'Sales Person'])) {
            $sql .= " and u.id = " . Auth::user()->id . " ";
        }
        if(Auth::user()->permissionsGroup->name != 'Admin')
        {
            $sql .= " and u.team_id in (".Auth::user()->team_id.") ";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
        
        $res = DB::select("SELECT ul.*, u.name ".$sql." order by $order $dir,u.name asc limit $start,$limit");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['name'] = $row->name;
                $nestedData['info'] = $row->info;
                $nestedData['login_date'] = $row->login_date;
                $nestedData['logout_date'] = $row->logout_date;
                $nestedData['logged_in_duration'] = $row->logged_in_duration;
                $nestedData['ip_address'] = $row->ip_address;
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
    public function pulse()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.reports.pulse", compact("GeneralWebmasterSections"));
    }
    
    public function pulseData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $order = $columns[$request->input('order.0.column')]['data'] ?? '';
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM leadger_call_logs 
            left join leadger_users
            on leadger_call_logs.created_by = leadger_users.id
WHERE verified = 1
AND DATE(leadger_call_logs.created_at) between '".$from_date."' and '".$to_date."'
 ";

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
        
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        $crm_id = ' ';
        if(isset($full_response['data']['crm_id']))
        {
            $crm_id = $full_response['data']['crm_id'];
        }
        $res = DB::select("SELECT vmn_used,lead_id,leadger_call_logs.id,leadger_call_logs.created_at,leadger_users.name
                 ".$sql." order by $order $dir,id asc limit $start,$limit");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['id'] = $row->id;
                $nestedData['lead_id'] = \App\Helpers\Helper::leadIdLink($row->lead_id);
                $nestedData['duration'] = $row->duration;
                $nestedData['pulse'] = $row->pulse;
                $data[] = $nestedData;
            }
        }
        $did = DB::Select("select * from leadger_obd_did");
        $statics = ['calls' => intval($totalData),'did' => count($did)];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }
        public function incomingVmn()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.reports.incomingVmn", compact("GeneralWebmasterSections"));
    }
public function incomingVmnData(Request $request)
{
    $limit = $request->input('length');
    $start = $request->input('start');
    $columns = $request->input('columns');
    $dir = $request->input('order.0.dir');
    $q = $request->input('find_q');
    $fromDate = $request->input('from_date') ?? now()->toDateString();
    $toDate = $request->input('to_date') ?? now()->toDateString();

    $order = $columns[$request->input('order.0.column')]['data'] ?? '';
    if ($order == "") {
        $order = "incoming_vmn_logs.id";
    }

    $user = auth()->user();
    
    $CallLogs = IncomingVmnLogs::query()
        ->leftJoin('users', 'incoming_vmn_logs.a_party_no', '=', 'users.click_agent_number')
        ->select('incoming_vmn_logs.*', 'users.name as user_name');
	  
	 if (in_array(Auth::user()->permissionsGroup->name, ['Telecaller'])) {
   
    
		if (!empty($user->click_agent_number)) {
			$CallLogs->where('incoming_vmn_logs.a_party_no', $user->click_agent_number);
		}
	}

    if (!empty($q)) {
        $CallLogs->where(function ($query) use ($q) {
            $query->where('dni', 'like', '%' . $q . '%')
                ->orWhere('cli', 'like', '%' . $q . '%')
                ->orWhere('a_party_no', 'like', '%' . $q . '%')
                ->orWhere('users.name', 'like', '%' . $q . '%');
        });
    }
    if (!empty($fromDate)) {
        $CallLogs->whereDate('incoming_vmn_logs.a_start_time', '>=', $fromDate);
    }

    if (!empty($toDate)) {
        $CallLogs->whereDate('incoming_vmn_logs.a_start_time', '<=', $toDate);
    }
    $totalData = $CallLogs->count();
    $totalFiltered = $totalData;

    $CallLogs = $CallLogs->offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->orderBy("incoming_vmn_logs.id", "desc")
        ->get();

    $data = [];
    if ($totalFiltered > 0) {
        foreach ($CallLogs as $CallLog) {
            $nestedData = [];
            $nestedData['call_id'] = $CallLog->call_id;
            $nestedData['cli'] = $CallLog->cli;
            $nestedData['dni'] = $CallLog->dni;
            $nestedData['a_party_no'] = $CallLog->a_party_no;
            $nestedData['a_start_time'] = $CallLog->a_start_time;
            $nestedData['a_dial_status'] = $CallLog->a_dial_status;
            $nestedData['duration'] = $CallLog->duration;
            $nestedData['name'] = $CallLog->user_name ?? ''; // Add the name column
            $nestedData['file_path'] = !empty($CallLog->file_path)
                ? "<a href='" . route('downloadRecordings', [$CallLog->id, 1]) . "'>Download</a>"
                : '';

            $data[] = $nestedData;
        }
    }

    $statics = [];
    $json_data = [
        "draw" => intval($request->input('draw')),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data,
        "statics" => $statics,
    ];

    return response()->json($json_data);
}

        public function notSaved()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $telecallerPermission = Permissions::where('name', 'Telecaller')->first();

        $Telecallers = collect(); // default empty
        if ($telecallerPermission) {
            // Get all users with this permission_id
            $Telecallers = User::where('permissions_id', $telecallerPermission->id)->get();
        }

        return view("dashboard.reports.notSaved", compact("GeneralWebmasterSections", "Campaigns","Telecallers"));
    }
    
    public function notSavedData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $results_filter = $request->input('results'); 

        $q = $request->input('find_q');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $find_campaign = $request->input('find_campaign');
        $telecaller_id = $request->input('telecaller_id'); 
        // the table's column names -> database columns (anything else: newest first)
        $sortable = ['id' => 'call_logs.id', 'campaign_name' => 'campaigns.name', 'phonenumber' => 'call_logs.phonenumber',
            'customer_name' => 'leads.name', 'call_type' => 'call_logs.call_type', 'duration' => 'call_logs.duration',
            'called_by' => 'users.name'];
        $order = $sortable[$columns[$request->input('order.0.column')]['data'] ?? ''] ?? 'call_logs.created_at';
        $dir = strtolower((string)$dir) === 'asc' ? 'asc' : 'desc';
        
        $CallLogs = CallLog::select(['call_logs.*','users.name as called_by', 
            'campaigns.name as campaign_name', 'campaigns.id as campaign_id', 'leads.name as customer_name','call_logs.click_response'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = 'Connected'")
                 ->whereNotNull('call_logs.click_response') 
                
                ->join('leads', 'leads.id', 'call_logs.lead_id')
                ->join('campaigns', 'campaigns.id', 'leads.campaign_id')
                ->join('users','users.id','call_logs.created_by')
              
                ->wherein('campaigns.team_id',explode(',',Auth::user()->team_id));
                
        if (!empty($results_filter)) {
            $CallLogs = $CallLogs->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(click_response, '$.A_DIAL_STATUS')) = ?", 
                [$results_filter]
            );
        }
        
        if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $CallLogs = $CallLogs->where(function ($query) {
                $query->where('leads.assigned_to','=',Auth::user()->id)
                    ->orWhere('leads.is_public', '=', '1');
                });
        }
        else if(Auth::user()->permissionsGroup->name == 'Sales Person')
        {
            $CallLogs = $CallLogs->where(function ($query) {
                $query->where('leads.sales_person_id','=',Auth::user()->id)
                    ->orWhere('leads.is_public', '=', '1');
                });
        }

        if ($q != "") {
            $CallLogs = $CallLogs->where(function ($query) use ($q) {
                $query->where('call_logs.lead_id', '=', $q)
                        ->orwhere('call_logs.phonenumber', 'like', '%' . $q . '%')
                        ->orWhere('leads.name', 'like', '%' . $q . '%')
                        ->orWhere('users.name','like','%' . $q . '%')
                       
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(leadger_call_logs.click_response, '$.B_DIAL_STATUS')) LIKE ?", ['%' . $q . '%']);

            });
        }
        if($find_campaign != "")
        {
            $CallLogs = $CallLogs->where('campaigns.id', $find_campaign);
        }
        if (!empty($telecaller_id)) {
            $CallLogs = $CallLogs->where('call_logs.created_by', $telecaller_id);
        }
        $today = Carbon::today()->format('Y-m-d');

        if (!empty($from_date) && !empty($to_date)) {
            // Use provided range
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
        } elseif (empty($to_date) && !empty($from_date)) {
            // Only to_date given, use from today
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$from_date. " 00:00:00", $today . " 23:59:59"]);
        } else {
            // Default to today's data
            $CallLogs = $CallLogs->whereBetween('call_logs.created_at', [$today . " 00:00:00", $today . " 23:59:59"]);
        }

        $totalData = $CallLogs->count();
        $totalFiltered = $totalData;
        if ($limit == -1||$limit=='All') {
            $limit = $totalData; // Fetch all records if "All" is selected
           // Reset start for proper pagination
        }


//order, paginate
        $CallLogs = $CallLogs->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($CallLogs as $CallLog) {
                $nestedData = [];
                $nestedData['id'] = "<a onclick=\"leadDetail('".$CallLog->lead_id."')\" class='text-primary'>".$CallLog->lead_id."</a>";
                $Campaign = \App\Models\Campaign::find($CallLog->campaign_id);
                $nestedData['phonenumber'] = \App\Helpers\Helper::number_masking($CallLog->phonenumber, $Campaign->number_masking);
                $nestedData['customer_name'] = $CallLog->customer_name;
                $nestedData['campaign_name'] = $CallLog->campaign_name;
                $nestedData['call_type'] = $CallLog->call_type;
               
                $nestedData['actual_call_time'] = '-';
                if ($CallLog->click_response) {
                    $responseData = json_decode($CallLog->click_response, true);
                    if (
                        isset($responseData['B_PARTY_CONNECTED_TIME']) &&
                        isset($responseData['B_PARTY_END_TIME'])
                    ) {
                        try {
                            $aPartyTime = Carbon::createFromFormat('dmYHis', $responseData['B_PARTY_CONNECTED_TIME']);
                            $bPartyTime = Carbon::createFromFormat('dmYHis', $responseData['B_PARTY_END_TIME']);
                            $actualCallTime = $aPartyTime->diffInSeconds($bPartyTime, true);
                            $nestedData['actual_call_time'] =  $actualCallTime;
                        } catch (\Exception $e) {
                            $nestedData['actual_call_time'] = '-';
                        }
                    }
                }

               
               $nestedData['duration'] = $CallLog->duration; 
                $nestedData['recording'] = "<a href='".route('downloadRecordings',$CallLog->id)."'>".$CallLog->recording."</a>";
                $nestedData['called_by'] = $CallLog->called_by;
                $nestedData['created_at'] = date('Y-m-d H:i:s',strtotime($CallLog->created_at));
                $strFile = route('downloadRecordings', $CallLog->id);
                $nestedData['call_call'] = '-'; // default value

                if ($CallLog->click_response) {
                    $responseData = json_decode($CallLog->click_response, true);
                    if (
                        isset($responseData['A_PARTY_CONNECTED_TIME']) &&
                        isset($responseData['A_PARTY_END_TIME'])
                    ) {
                        try {
                            $aPartyTime = Carbon::createFromFormat('dmYHis', $responseData['A_PARTY_CONNECTED_TIME']);
                            $bPartyTime = Carbon::createFromFormat('dmYHis', $responseData['A_PARTY_END_TIME']);
                            $callCallDuration = $aPartyTime->diffInSeconds($bPartyTime, true);
                            $nestedData['call_call'] = $callCallDuration; // mm:ss
                        } catch (\Exception $e) {
                            $nestedData['call_call'] = '-';
                        }
                    }
                }
               $nestedData['agent_status'] = '-'; // default

                if (!empty($CallLog->click_response)) {
                    $responseData = json_decode($CallLog->click_response, true);

                    // Add A_DIAL_STATUS to Connected_status
                    if (isset($responseData['A_DIAL_STATUS'])) {
                        $nestedData['agent_status'] = $responseData['A_DIAL_STATUS'];
                    }

                }
                $nestedData['customer_status'] = '-'; // default

                if (!empty($CallLog->click_response)) {
                    $responseData = json_decode($CallLog->click_response, true);

                    // Add A_DIAL_STATUS to Connected_status
                    if (isset($responseData['B_DIAL_STATUS'])) {
                        $nestedData['customer_status'] = $responseData['B_DIAL_STATUS'];
                    }

                }

                $patharr = explode('admin/', $strFile);
                $nestedData['play'] = '';
                if (!empty($CallLog->click_recording_url)) {
                    $nestedData['play'] = "<span class='audio'><audio controls>
                        <source src='" . $CallLog->click_recording_url . "' type='audio/mpeg'>
                        Your browser does not support the audio element.
                    </audio></span>";
                }                
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }

    public function rmiReport()
    {
        $setting = Setting::first();
        $rmiEnabled = $setting ? (bool) ($setting->rmi_report_enabled ?? 1) : true;
        if (!$rmiEnabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'RMI Report is disabled in Company Settings.');
        }

        return view('dashboard.reports.rmi');
    }

    /**
     * Show RMI Analysis Page.
     */
    public function rmiAnalysisPage()
    {
        $setting = Setting::first();
        $rmiEnabled = $setting ? (bool) ($setting->rmi_report_enabled ?? 1) : true;
        if (!$rmiEnabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'RMI Analysis is disabled in Company Settings.');
        }

        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $Teams = Team::orderby('name', 'asc')->get();
        $Users = User::where('status', 1)->orderby('name', 'asc')->get();

        return view('dashboard.reports.rmi_analysis', compact(
            'GeneralWebmasterSections',
            'Teams',
            'Users'
        ));
    }

    /**
     * JSON data endpoint for RMI Analysis summary & agent breakdown.
     */
    /**
     * An agent's calls for RMI Analysis with their transcript analysis: campaign calls (call_logs, analysis
     * call_id = log id) and manual-dial calls (dialer_logs, analysis recording_id = 'dialer_{id}').
     * ai_status 'completed' = a real transcript was analysed; only those count as analysed.
     */
    private function rmiAgentCalls(int $agentId, $fromDate, $toDate)
    {
        $dated = function ($q, string $col) use ($fromDate, $toDate) {
            if (!empty($fromDate)) $q->whereDate($col, '>=', $fromDate);
            if (!empty($toDate)) $q->whereDate($col, '<=', $toDate);
            return $q;
        };
        $campaign = $dated(DB::table('call_logs as cl')
            ->leftJoin('leads as l', 'l.id', '=', 'cl.lead_id')
            ->leftJoin('campaigns as c', 'c.id', '=', 'l.campaign_id')
            ->leftJoin('call_ai_analyses as caa', 'caa.call_id', '=', 'cl.id')
            ->leftJoin('recordings as r', 'r.id', '=', 'cl.recording_id')
            ->where('cl.created_by', $agentId), 'cl.created_at')
            ->select('cl.id as call_id', DB::raw("'Campaign' as source"), 'cl.phonenumber', 'cl.duration', 'cl.recording', 'cl.created_at',
                'cl.lead_id', 'l.name as lead_name', 'c.name as campaign_name', 'caa.id as ai_id', 'caa.status as ai_status',
                'caa.sentiment', 'caa.interest_level', 'caa.summary', 'caa.transcript', 'r.download_status as rec_status')
            ->get();
        $tp = DB::getTablePrefix();
        $manual = $dated(DB::table('dialer_logs as dl')
            ->leftJoin('leads as l', 'l.id', '=', 'dl.lead_id')
            ->leftJoin('campaigns as c', 'c.id', '=', 'dl.campaign_id')
            ->leftJoin('call_ai_analyses as caa', 'caa.recording_id', '=', DB::raw("CONCAT('dialer_', {$tp}dl.id)"))
            ->leftJoin('recordings as r', 'r.id', '=', 'dl.recording_id')
            ->where('dl.created_by', $agentId), 'dl.created_at')
            ->select('dl.id as call_id', DB::raw("'Manual' as source"), 'dl.phone_number as phonenumber', 'dl.duration', 'dl.file_path as recording', 'dl.created_at',
                'dl.lead_id', 'l.name as lead_name', DB::raw("COALESCE({$tp}c.name, 'Manual Dial') as campaign_name"), 'caa.id as ai_id', 'caa.status as ai_status',
                'caa.sentiment', 'caa.interest_level', 'caa.summary', 'caa.transcript', 'r.download_status as rec_status')
            ->get();

        return $campaign->concat($manual)->map(function ($c) {
            // Only analyses of a completed transcript count; the transcript analysis reports POSITIVE / NEGATIVE / NEUTRAL,
            // shown here as Confident / Frustrated / Neutral
            if ($c->ai_status !== 'completed') {
                $c->sentiment = null;
            }
            return $c;
        })->sortByDesc('created_at')->values();
    }

    /** RMI card categories and the analysis sentiments each one counts (completed analyses only). */
    private const RMI_EMOTIONS = [
        'angry' => ['ANGRY'],
        'frustrated' => ['FRUSTRATED', 'NEGATIVE'],
        'confident' => ['CONFIDENT', 'POSITIVE'],
        'neutral' => ['NEUTRAL'],
        'calm' => ['CALM'],
    ];

    /**
     * Whether a call belongs to an RMI summary card: total_calls (every call), total_analyzed (completed
     * analysis) or an emotion card. The card counts and the card's records page both use this, so they match.
     */
    private function rmiInCategory($call, string $category): bool
    {
        if ($category === 'total_calls') return true;
        if ($category === 'total_analyzed') return $call->ai_status === 'completed';
        return isset(self::RMI_EMOTIONS[$category]) && in_array(strtoupper((string)$call->sentiment), self::RMI_EMOTIONS[$category], true);
    }

    /** RMI summary counts for a set of calls (one count per card). */
    private function rmiBuckets($calls): array
    {
        $out = [];
        foreach (array_merge(['total_calls', 'total_analyzed'], array_keys(self::RMI_EMOTIONS)) as $cat) {
            $out[$cat] = $calls->filter(fn ($c) => $this->rmiInCategory($c, $cat))->count();
        }
        return $out;
    }

    /** RMI agents for the page filters (active staff, optionally one group and/or one agent). */
    private function rmiAgents($groupId, $agentId)
    {
        $q = User::with('permissionsGroup')->where('status', 1);
        if (!empty($agentId)) $q->where('id', $agentId);
        if (!empty($groupId)) $q->whereRaw("FIND_IN_SET(?, team_id)", [$groupId]);
        return $q->orderby('name', 'asc')->get();
    }

    private function rmiEnabled(): bool
    {
        $setting = Setting::first();
        return $setting ? (bool)($setting->rmi_report_enabled ?? 1) : true;
    }

    /**
     * Records page for an RMI summary card (Total Calls / Total Analyzed / an emotion), with the RMI page's
     * date / group / agent filters. Same access rule as the RMI Analysis page.
     */
    public function rmiAnalysisCalls(Request $request)
    {
        if (!$this->rmiEnabled()) {
            return redirect()->route('adminHome')->with('errorMessage', 'RMI Analysis is disabled in Company Settings.');
        }
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $Teams = Team::orderby('name', 'asc')->get();
        $Users = User::where('status', 1)->orderby('name', 'asc')->get();
        $categories = ['total_calls' => 'Total Calls', 'total_analyzed' => 'Total Analyzed', 'angry' => 'Angry',
            'frustrated' => 'Frustrated', 'confident' => 'Confident', 'neutral' => 'Neutral', 'calm' => 'Calm'];
        $category = array_key_exists($request->input('category'), $categories) ? $request->input('category') : 'total_calls';
        return view('dashboard.reports.rmi_calls', compact('GeneralWebmasterSections', 'Teams', 'Users', 'categories', 'category'));
    }

    /**
     * A lead's real customer name, or null: empty, a dash, or an auto-generated placeholder such as
     * "Lead 9777436222" (the word Lead + the phone number, used for leads created from a manual-dial follow-up).
     */
    private static function rmiCustomerName($name): ?string
    {
        $name = trim((string)$name);
        if ($name === '' || preg_match('/^[-\x{2013}\x{2014}]+$/u', $name) || preg_match('/^Lead\s+\+?[\d\s-]{6,}$/i', $name)) {
            return null;
        }
        return $name;
    }

    /** RMI emotion label (Angry / Frustrated / Confident / Neutral / Calm) for a completed analysis, or null. */
    private function rmiEmotionLabel($call): ?string
    {
        if ($call->ai_status !== 'completed') return null;
        foreach (self::RMI_EMOTIONS as $cat => $names) {
            if (in_array(strtoupper((string)$call->sentiment), $names, true)) return ucfirst($cat);
        }
        return null;
    }

    /** "Completed" / "Transcribing" / "Pending" / "Failed" / "Not analysed", as the records table shows it. */
    private static function rmiStatusLabel($aiStatus): string
    {
        return ['completed' => 'Completed', 'processing' => 'Transcribing', 'pending' => 'Pending', 'queued' => 'Pending', 'failed' => 'Failed'][$aiStatus] ?? 'Not analysed';
    }

    /**
     * The records of an RMI card for the page filters + search text: the same calls, agent scope and counting
     * rules as the summary cards, newest first. Used by the records table and by its Excel / CSV export.
     * Returns [card total (before search), rows after search].
     */
    private function rmiCallRecords(Request $request): array
    {
        $isDate = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        $fromDate = $isDate($request->input('from_date')) ? $request->input('from_date') : null;
        $toDate = $isDate($request->input('to_date')) ? $request->input('to_date') : null;
        $category = (string)$request->input('category', 'total_calls');
        $search = mb_strtolower(trim((string)$request->input('search', '')));

        $rows = collect();
        foreach ($this->rmiAgents($request->input('group_id'), $request->input('agent_id')) as $agent) {
            foreach ($this->rmiAgentCalls($agent->id, $fromDate, $toDate) as $c) {
                if ($this->rmiInCategory($c, $category)) {
                    $c->agent_name = $agent->name;
                    $c->customer_name = self::rmiCustomerName($c->lead_name);
                    $rows->push($c);
                }
            }
        }
        $total = $rows->count();   // equals the card's count for the same filters
        if ($search !== '') {
            // what the table shows: lead ID, agent, customer name, mobile number
            $rows = $rows->filter(fn ($c) => str_contains(mb_strtolower(implode(' ', [$c->lead_id, $c->agent_name, $c->customer_name, $c->phonenumber])), $search));
        }
        return [$total, $rows->sortByDesc(fn ($c) => $c->created_at . sprintf('%012d', $c->call_id))->values()];
    }

    /** Records for an RMI card: the same calls and rules as the summary counts, filtered, searched and paginated. */
    public function rmiAnalysisCallsData(Request $request)
    {
        if (!$this->rmiEnabled()) {
            return response()->json(['message' => 'RMI Analysis is disabled in Company Settings.'], 403);
        }
        $start = max(0, (int)$request->input('start', 0));
        $length = (int)$request->input('length', 10);
        [$total, $rows] = $this->rmiCallRecords($request);
        $filtered = $rows->count();
        $page = $length > 0 ? $rows->slice($start, $length) : $rows;

        $data = $page->map(function ($c) {
            $sourceCode = $c->source === 'Manual' ? 2 : 0;
            $seconds = \App\Helpers\Helper::durationToSeconds($c->duration);
            $recReady = in_array(strtolower((string)$c->rec_status), ['completed', 'failed'], true);
            return [
                'call_id' => $c->call_id,
                'source' => $c->source,
                'agent_name' => $c->agent_name,
                'lead_id' => $c->lead_id,
                'lead_url' => $c->lead_id ? parse_url(route('leadDetail', $c->lead_id), PHP_URL_PATH) : null,
                'customer_name' => $c->customer_name,
                'phonenumber' => $c->phonenumber,
                'campaign_name' => $c->campaign_name,
                'date' => $c->created_at ? date('d-M-Y h:i A', strtotime($c->created_at)) : '-',
                'duration' => \App\Helpers\Helper::formatDuration($seconds),
                'recording_url' => $recReady ? parse_url(route('downloadRecordings', [$c->call_id, $sourceCode]), PHP_URL_PATH) . '?play=1' : null,
                'rec_status' => $c->rec_status,
                'emotion' => $this->rmiEmotionLabel($c),
                'sentiment' => $c->ai_status === 'completed' ? $c->sentiment : null,
                'ai_status' => $c->ai_status,
                'detail_url' => ($c->ai_status === 'completed' && $c->ai_id) ? parse_url(route('aiAnalysisDetail', $c->ai_id), PHP_URL_PATH) : null,
            ];
        })->values();

        return response()->json([
            'draw' => (int)$request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    /**
     * Excel (.xlsx) / CSV of ALL records of an RMI card for the current filters + search (not only the shown page):
     * the same records, order and empty-value rules as the table. Protected like the page.
     * GET /reports/rmi-analysis/calls/export/{format}?from_date=&to_date=&group_id=&agent_id=&category=&search=
     */
    public function rmiAnalysisCallsExport(Request $request, string $format)
    {
        if (!$this->rmiEnabled()) {
            return response()->json(['message' => 'RMI Analysis is disabled in Company Settings.'], 403);
        }
        $format = strtolower($format);
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(404);
        }
        [, $rows] = $this->rmiCallRecords($request);
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'No records to export.'], 422);
        }

        $headers = ['Lead ID', 'Agent', 'Customer Name', 'Mobile Number', 'Call Date & Time', 'Duration', 'RMI Emotion', 'Analysis Status'];
        $line = function ($c, bool $excelDate) {
            $at = $c->created_at ? \Carbon\Carbon::parse($c->created_at) : null;
            return [
                $c->lead_id ? (string)$c->lead_id : '-',
                (string)$c->agent_name,
                $c->customer_name ?? '-',
                (string)$c->phonenumber,
                $excelDate ? ($at ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $at->format('Y-m-d H:i:s'), 'UTC') : '-') : ($at ? $at->format('d-M-Y h:i A') : '-'),
                \App\Helpers\Helper::formatDuration(\App\Helpers\Helper::durationToSeconds($c->duration)),
                $this->rmiEmotionLabel($c) ?? '-',
                self::rmiStatusLabel($c->ai_status),
            ];
        };

        // File name: records type + date range, e.g. rmi-analysis-total-analyzed-2026-10-01-to-2026-10-13
        $category = str_replace('_', '-', preg_replace('/[^a-z_]/', '', (string)$request->input('category', 'total_calls')) ?: 'total-calls');
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$request->input('from_date')) ? $request->input('from_date') : null;
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$request->input('to_date')) ? $request->input('to_date') : null;
        $fileName = 'rmi-analysis-' . $category . '-' . ($from && $to ? "{$from}-to-{$to}" : ($from ? "from-{$from}" : ($to ? "to-{$to}" : 'all-dates')));

        if ($format === 'xlsx') {
            $lines = $rows->map(fn ($c) => $line($c, true))->all();
            // Column widths from the longest value in each column (dates have a fixed display width)
            $widths = [];
            foreach ($headers as $i => $h) {
                $max = mb_strlen($h);
                foreach ($lines as $l) {
                    $max = max($max, $l[$i] instanceof \DateTimeInterface ? 20 : mb_strlen((string)$l[$i]));
                }
                $widths[] = min(60, $max + 3);
            }
            $path = \App\Services\SimpleXlsxWriter::write($headers, $lines,
                ['text', 'text', 'text', 'text', 'datetime', 'text', 'text', 'text'], $widths, 'RMI Analysis');
            return response()->download($path, $fileName . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV: streamed row by row, UTF-8 with BOM; text starting like a formula gets a leading '
        $safe = fn ($v) => (is_string($v) && strlen($v) > 1 && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
        return response()->streamDownload(function () use ($rows, $headers, $line, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $c) {
                fputcsv($out, array_map($safe, $line($c, false)));
            }
            fclose($out);
        }, $fileName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function rmiAnalysisData(Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $groupId = $request->input('group_id');
        $agentId = $request->input('agent_id');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);

        $allAgents = $this->rmiAgents($groupId, $agentId);

        $tableData = [];
        $totalCallsSum = 0;
        $totalAnalyzedSum = 0;
        $angrySum = 0;
        $frustratedSum = 0;
        $confidentSum = 0;
        $neutralSum = 0;
        $calmSum = 0;

        $sNo = 1;

        foreach ($allAgents as $agent) {
            $b = $this->rmiBuckets($this->rmiAgentCalls($agent->id, $fromDate, $toDate));
            $totalCalls = $b['total_calls'];
            $analyzedCalls = $b['total_analyzed'];
            $angryCount = $b['angry'];
            $frustratedCount = $b['frustrated'];
            $confidentCount = $b['confident'];
            $neutralCount = $b['neutral'];
            $calmCount = $b['calm'];

            $totalCallsSum += $totalCalls;
            $totalAnalyzedSum += $analyzedCalls;
            $angrySum += $angryCount;
            $frustratedSum += $frustratedCount;
            $confidentSum += $confidentCount;
            $neutralSum += $neutralCount;
            $calmSum += $calmCount;

            $tableData[] = [
                's_no' => $sNo++,
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'role' => $agent->permissionsGroup->name ?? 'Agent',
                'total_calls' => $totalCalls,
                'total_analyzed' => $analyzedCalls,
                'angry' => $angryCount,
                'frustrated' => $frustratedCount,
                'confident' => $confidentCount,
                'neutral' => $neutralCount,
                'calm' => $calmCount,
            ];
        }

        $totalRecords = count($tableData);
        $paginatedData = array_slice($tableData, $start, $limit == -1 ? $totalRecords : (int)$limit);

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $paginatedData,
            'summary' => [
                'total_calls' => $totalCallsSum,
                'total_analyzed' => $totalAnalyzedSum,
                'angry' => $angrySum,
                'frustrated' => $frustratedSum,
                'confident' => $confidentSum,
                'neutral' => $neutralSum,
                'calm' => $calmSum
            ]
        ]);
    }

    /**
     * Agent detail modal / data endpoint.
     */
    public function rmiAnalysisDetail(Request $request, $agentId)
    {
        $agent = User::with('permissionsGroup')->find($agentId);
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $calls = $this->rmiAgentCalls((int)$agentId, $fromDate, $toDate);
        $b = $this->rmiBuckets($calls);
        $totalCalls = $b['total_calls'];
        $analyzedCount = $b['total_analyzed'];
        $angryCount = $b['angry'];
        $frustratedCount = $b['frustrated'];
        $confidentCount = $b['confident'];
        $neutralCount = $b['neutral'];
        $calmCount = $b['calm'];

        return response()->json([
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->permissionsGroup->name ?? 'Agent'
            ],
            'summary' => [
                'total_calls' => $totalCalls,
                'total_analyzed' => $analyzedCount,
                'angry' => $angryCount,
                'frustrated' => $frustratedCount,
                'confident' => $confidentCount,
                'neutral' => $neutralCount,
                'calm' => $calmCount
            ],
            'calls' => $calls
        ]);
    }

    /**
     * Export RMI Analysis (CSV / Excel / PDF).
     */
    public function rmiAnalysisExport(Request $request, $type = 'csv')
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $groupId = $request->input('group_id');
        $agentId = $request->input('agent_id');

        $agentsQuery = User::with('permissionsGroup')->where('status', 1);
        if (!empty($agentId)) {
            $agentsQuery->where('id', $agentId);
        }
        if (!empty($groupId)) {
            $agentsQuery->whereRaw("FIND_IN_SET(?, team_id)", [$groupId]);
        }
        $allAgents = $agentsQuery->orderby('name', 'asc')->get();

        $exportData = [];
        $sNo = 1;
        foreach ($allAgents as $agent) {
            $b = $this->rmiBuckets($this->rmiAgentCalls($agent->id, $fromDate, $toDate));
            $totalCalls = $b['total_calls'];
            $analyzedCalls = $b['total_analyzed'];

            $exportData[] = [
                'S.No' => $sNo++,
                'Agent Name' => $agent->name,
                'Role' => $agent->permissionsGroup->name ?? 'Agent',
                'Total Calls' => $totalCalls,
                'Total Analyzed' => $analyzedCalls,
            ];
        }

        $fileName = 'rmi_analysis_' . date('Y_m_d_His') . '.csv';
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $callback = function() use($exportData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['S.No', 'Agent Name', 'Role', 'Total Calls', 'Total Analyzed']);
            foreach ($exportData as $row) {
                fputcsv($file, [$row['S.No'], $row['Agent Name'], $row['Role'], $row['Total Calls'], $row['Total Analyzed']]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
