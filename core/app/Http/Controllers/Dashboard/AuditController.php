<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Campaign;
use App\Models\TrackingLog;
use App\Models\WorkLocation;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class AuditController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function report()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = Campaign::orderBy('created_at', 'desc')->get();
        
        return view("dashboard.audit.report", compact("GeneralWebmasterSections", "Campaigns"));
    }

    public function reportData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $from_date = $request->input('from_date') ?? date('Y-m-d');
        $to_date = $request->input('to_date') ?? date('Y-m-d');
        $q = $request->input('find_q');
        $find_campaign = $request->input('find_campaign');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $sql = "FROM leadger_audit_reports
LEFT JOIN leadger_users
ON audited_by = leadger_users.id
LEFT JOIN leadger_campaigns
ON leadger_campaigns.id = leadger_audit_reports.campaign_id
LEFT JOIN leadger_leads
ON leadger_leads.id = leadger_audit_reports.lead_id
LEFT JOIN leadger_users tcl
ON tcl.id = leadger_leads.assigned_to
LEFT JOIN leadger_users fos
ON fos.id = leadger_leads.sales_person_id
WHERE DATE(leadger_audit_reports.created_at) BETWEEN '".$from_date."' AND '".$to_date."'
";
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader','Auditor']))
        {
            $sql .= " and find_in_set(leadger_campaigns.team_id, ".Auth::user()->team_id.") ";
        }
//        else if(Auth::user()->permissionsGroup->name == 'Telecaller')
//        {
//            $sql .= " and user_type = 'Staff' and (frm.id = ".Auth::user()->id." or t.id = ".Auth::user()->id.") ";
//        }
//        else if(Auth::user()->permissionsGroup->name == 'Sales Person')
//        {
//            $sql .= " and user_type = 'Sales Person' and (frm.id = ".Auth::user()->id." or t.id = ".Auth::user()->id.") ";
//        }

//        if ($q != "") {
//            $sql .= " and (allocation.lead_id like '%".$q."%' 
//                        or added.name like '%".$q."%' 
//                        or frm.name like '%".$q."%' 
//                        or t.name like '%".$q."%' )";
//        }
        if($find_campaign != "")
        {
            $sql .= " and leadger_campaigns.id = '$find_campaign' ";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Reports = DB::select("SELECT leadger_audit_reports.*,leadger_campaigns.name AS campaign_name,
leadger_users.name AS audited, 
tcl.name AS tcl, 
fos.name AS fos
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Reports as $Report) {
                $nestedData = [];
                $nestedData['lead_id'] = "<a onclick=\"leadDetail('".$Report->lead_id."')\" class='text-primary'>".$Report->lead_id."</a>";
                $nestedData['campaign_name'] = $Report->campaign_name;
                $nestedData['tcl'] = $Report->tcl;
                $nestedData['fos'] = $Report->fos;
                $nestedData['flag'] = $Report->flag;
                $nestedData['role'] = $Report->role;
                $nestedData['audited'] = $Report->audited;
                $nestedData['remark'] = $Report->remark;
                $nestedData['created_at'] = $Report->created_at;
                
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
    
    public function portfolios()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $condition = '';
        if(Auth::user()->permissionsGroup->name == 'Auditor')
        {
            $condition = " where find_in_set(".Auth::user()->id.",auditor_ids) ";
        }
        else if (Auth::user()->permissionsGroup->name !== 'Admin') {
            $condition .= " where find_in_set(leadger_campaigns.team_id, ".Auth::user()->team_id.") ";
        }

        $Portfolios = DB::select("SELECT * FROM leadger_campaigns
LEFT JOIN (
SELECT campaign_id,COUNT(*) AS total_leads,
SUM(CASE WHEN `status` = 'actioned' THEN 1 ELSE 0 END) AS actioned,
SUM(CASE WHEN tcl_audit IS NOT NULL OR fos_audit IS NOT NULL THEN 1 ELSE 0 END) AS audited,
MAX(updated_at) AS last_actioned
FROM leadger_leads
GROUP BY campaign_id
) leads
ON leads.campaign_id = leadger_campaigns.id
".$condition."
ORDER BY leadger_campaigns.id DESC");
        $Auditors = DB::Select("select leadger_users.id,leadger_users.name
                FROM leadger_users
INNER JOIN leadger_permissions
ON permissions_id = leadger_permissions.id
WHERE leadger_permissions.name = 'Auditor'
");

        return view("dashboard.audit.portfolios", compact("Auditors", "Portfolios", "GeneralWebmasterSections"));
    }

    public function assignAuditors(Request $request)
    {
        $campaign = Campaign::find($request->campaign_id);
        $campaign->auditor_ids = implode(',',$request->auditors);
        $campaign->save();
        
        return json_encode(['success' => true, 'message'=>"Auditors added Successfully"]);
    }

    public function auditors(Request $request)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = Campaign::orderBy('created_at', 'desc')->get();
        
        $from_date = $request->input('from_date') ?? date('Y-m-d');
        $to_date = $request->input('to_date') ?? date('Y-m-d');
        $sql = "SELECT leadger_users.name AS auditor_name,leadger_users.id,
'Auditor' AS role,
COUNT(*) AS campaign_count,
COALESCE(total_accounts,0) AS total_accounts,
COALESCE(audited,0) AS audited,
last_audited
FROM leadger_campaigns
INNER JOIN leadger_users
ON FIND_IN_SET(leadger_users.id, auditor_ids)
LEFT JOIN (
SELECT campaign_id,COUNT(*) total_accounts FROM leadger_leads GROUP BY campaign_id
) l
ON leadger_campaigns.id = l.campaign_id
LEFT JOIN (
SELECT audited_by,COUNT(DISTINCT lead_id) AS audited,MAX(created_at) AS last_audited FROM leadger_audit_reports
WHERE DATE(created_at) BETWEEN '".$from_date."' and '".$to_date."'
GROUP BY audited_by
) a
ON leadger_users.id = audited_by
where 1 = 1 
";
        if(!empty($this->campaign_id))
            $sql .= " and  leadger_campaigns.id = '".$this->campaign_id."' ";
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader','Auditor']))
        {
            $sql .= " and find_in_set(leadger_campaigns.team_id, ".Auth::user()->team_id.") ";
        }

        $sql .= " group by leadger_users.id, leadger_users.name, total_accounts, audited, last_audited";
        $Auditors = DB::select($sql);

        return view("dashboard.audit.auditors", compact("GeneralWebmasterSections", "Campaigns", "Auditors"));
    }
}
