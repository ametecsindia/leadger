<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\ObdDID;
use App\Models\ObdCreditsHistory;
use App\Models\ObdCreditsTransfer;
use App\Models\ObdCampaign;
use App\Models\ObdCampaignDetail;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class ObdController extends Controller
{
    private $uploadPath = "uploads/obd/";

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
public function did()
{
    // General for all pages
    $GeneralWebmasterSections = WebmasterSection::where('status', 1)
        ->orderBy('row_no', 'asc')
        ->get();

    // Leadger OBD DIDs (existing)
    $Did = DB::select("
        SELECT 
            leadger_obd_did.id,
            leadger_obd_did.virtual_number, 
            leadger_obd_did.created_at, 
            leadger_obd_did.obd, 
            leadger_obd_did.click2call, 
            leadger_obd_did.webrtc, 
            leadger_obd_did.tc_verified,
            leadger_obd_did.team_id,
            leadger_obd_did.created_by,
            leadger_users.name AS added_by,
            GROUP_CONCAT(DISTINCT leadger_teams.name ORDER BY leadger_teams.name ASC SEPARATOR ', ') AS team_names
        FROM leadger_obd_did
        LEFT JOIN leadger_teams ON FIND_IN_SET(leadger_teams.id, leadger_obd_did.team_id)
        LEFT JOIN leadger_users ON leadger_users.id = leadger_obd_did.created_by
        GROUP BY 
            leadger_obd_did.id, 
            leadger_obd_did.virtual_number, 
            leadger_obd_did.created_at, 
            leadger_obd_did.obd, 
            leadger_obd_did.click2call, 
            leadger_obd_did.webrtc, 
            leadger_obd_did.tc_verified,
            leadger_obd_did.team_id,
            leadger_obd_did.created_by,
            leadger_users.name
        ORDER BY leadger_obd_did.id ASC
    ");

    // Smart31 group data
    $smartGroups = DB::table('smart31_vmn_numbers')
        ->select(
            'group_name',
            'assigned_groups',
            DB::raw('COUNT(DISTINCT vmn) as total_numbers'),
            DB::raw('MAX(usage_date) as last_usage')
        )
        ->groupBy('group_name', 'assigned_groups')
        ->orderBy('group_name', 'asc')
        ->get();

    // Fetch all Smart31 details grouped by group_name (for modal display)
    $smartDetails = DB::table('smart31_vmn_numbers')
        ->select('group_name', 'vmn', 'usage_date', 'obd', 'c2c', 'webrtc', 'verified')
        ->orderBy('group_name', 'asc')
        ->orderByDesc('usage_date')
        ->get()
        ->groupBy('group_name');

    // Team names (for mapping assigned_groups IDs)
    $teams = \App\Models\Team::orderBy('name', 'asc')->get()->keyBy('id');

    return view('dashboard.obd.did', compact(
        'Did',
        'GeneralWebmasterSections',
        'teams',
        'smartGroups',
        'smartDetails'
    ));
}

    public function did_store(Request $request)
    {
        $this->validate($request, [
            'virtual_number' => 'required|unique:obd_did',
            'usage' => 'required',
            'team_id' => 'required'
        ]);
        
        $obd = $request->usage == 'obd' || $request->usage == 'both' ? 1 : 0;
        $click2call = $request->usage == 'click2call' || $request->usage == 'both' ? 1 : 0;
        
//        if($click2call && !in_array($request->virtual_number, \App\Helpers\TcfgHelper::click2call_vmn))
//            return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', 
//                    'Virtual Number '.$request->virtual_number.' is not eligible for Click2call');
//        if($obd && !in_array($request->virtual_number, \App\Helpers\TcfgHelper::obd_vmn))
//            return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', 
//                    'Virtual Number '.$request->virtual_number.' is not eligible for OBD');
        
        $ObdDid = new ObdDID;
        $ObdDid->virtual_number = $request->virtual_number;
        $ObdDid->obd = $obd;
        $ObdDid->click2call = $click2call;
        $ObdDid->team_id = !empty($request->team_id) ? implode(',',$request->team_id) : '';
        $ObdDid->created_by = Auth::user()->id;
        $ObdDid->save();

        return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', __('backend.addDone'));
    }
    
    public function edit_did($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $ObdDid = ObdDID::find($id);
        $teams = \App\Models\Team::orderBy('name', 'asc')->get();

        return view("dashboard.obd.edit", compact("ObdDid", "GeneralWebmasterSections", "teams"));
    }
    
    public function did_update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'team_id' => 'required'
        ]);

        $ObdDid = ObdDID::find($request->id);
        $ObdDid->team_id = !empty($request->team_id) ? implode(',',$request->team_id) : '';
        $ObdDid->save();
        
        return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', __('backend.saveDone'));
    }

    public function did_bulk_update(Request $request)
    {
        $ids = $request->ids;
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('trim', (array)$ids));

        if (empty($ids)) {
            return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', 'No DID records selected for bulk update.');
        }

        if ($request->action == 'delete') {
            if (!Helper::canDelete('campaigns.add_voucher')) {
                return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', 'Unauthorized: Delete operations are restricted.');
            }
            ObdDID::whereIn('id', $ids)->delete();
            return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', 'Selected DIDs deleted successfully.');
        }

        $updateData = [];

        if ($request->has('team_id') && !empty($request->team_id)) {
            $teamIds = is_array($request->team_id) ? implode(',', $request->team_id) : $request->team_id;
            $updateData['team_id'] = $teamIds;
        }

        if ($request->has('usage') && !empty($request->usage)) {
            if ($request->usage == 'obd') {
                $updateData['obd'] = 1;
                $updateData['click2call'] = 0;
            } elseif ($request->usage == 'click2call') {
                $updateData['obd'] = 0;
                $updateData['click2call'] = 1;
            } elseif ($request->usage == 'both') {
                $updateData['obd'] = 1;
                $updateData['click2call'] = 1;
            } elseif ($request->usage == 'webrtc') {
                $updateData['webrtc'] = 1;
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = now();
            ObdDID::whereIn('id', $ids)->update($updateData);
        }

        return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', 'Selected DIDs updated successfully.');
    }

    public function did_delete($id)
    {
        ObdDID::find($id)->delete();
        return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', __('backend.deleteDone'));
    }

    public function credits()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $CreditHistory = ObdCreditsHistory::orderby("id", "desc")->get();
        $Setting = DB::select("SELECT FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits FROM leadger_settings");
        
        return view("dashboard.obd.credits", compact("CreditHistory", "GeneralWebmasterSections", "Setting"));
    }
    
    public function add_credits(Request $request)
    {
        $this->validate($request, [
            'voucher_code' => 'required',
        ]);
        
        require_once(base_path().'/vendor/ezywing/Helper.php');
        $result = \License\Helpers\Helper::voucherValidate($request->voucher_code);
        if(empty($result))
        {
            return redirect()->action('Dashboard\ObdController@credits')->with('errorMessage', 'Something went wrong');
        }
        
        if($result->status != 200)
        {
            return redirect()->action('Dashboard\ObdController@credits')->with('errorMessage', $result->message);
        }
        if(\License\Helpers\Helper::voucherUsed($request->voucher_code))
        {
            DB::statement("UPDATE leadger_settings SET obd_credits = AES_ENCRYPT(TO_BASE64 (COALESCE(FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')),0)+".$result->data->credits."), 'MyNicotex@1')");
            
            $history = new ObdCreditsHistory;
            $history->voucher_code = $request->voucher_code;
            $history->credits = $result->data->credits;
            $history->created_by = Auth::user()->id;
            $history->save();
        }
        return redirect()->action('Dashboard\ObdController@credits')->with('doneMessage', $result->data->credits." Credits added Successfully");
    }
    
    public function transfer()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Setting = DB::select("SELECT FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits FROM leadger_settings");
        $UserCredits = DB::select("select id,name,
                FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits from leadger_users");
        return view("dashboard.obd.transfer", compact("GeneralWebmasterSections", "Setting", "UserCredits"));
    }

    public function transferData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $find_user = $request->input('find_user');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM leadger_obd_credits_transfer oc
INNER JOIN leadger_users added
ON added.id = oc.created_by
LEFT JOIN leadger_users frm
ON frm.id = oc.from_user
LEFT JOIN leadger_users t
ON t.id = oc.to_user
where 1=1 
";
//        if(Auth::user()->permissionsGroup->name != 'Admin')
//            $sql .= " and team.id in (".Auth::user()->team_id.") ";

        if ($find_user != "") {
            $sql .= " and (from_user = '".$find_user."' 
                        or to_user = '".$find_user."')";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $TransferHistory = DB::select("SELECT oc.*,
added.name AS added_by,
CASE WHEN frm.id IS NULL THEN 'Master' ELSE frm.name END AS from_user,
CASE WHEN t.id IS NULL THEN 'Master' ELSE t.name END AS to_user
                    ".$sql." ORDER BY $order $dir,oc.created_at DESC limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($TransferHistory as $Row) {
                $nestedData = [];
                $nestedData['id'] = $Row->id;
                $nestedData['created_at'] = $Row->created_at;
                $nestedData['credits'] = $Row->credits;
                $nestedData['from_user'] = $Row->from_user;
                $nestedData['to_user'] = $Row->to_user;
                $nestedData['reason'] = $Row->reason;
                $nestedData['added_by'] = $Row->added_by;
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
    
    public function transferCredits(Request $request)
    {
        $this->validate($request, [
            'from_user' => 'required',
            'to_user' => 'required',
            'credits' => 'required',
            'reason' => 'required',
        ]);
        
        if($request->credits <= 0)
        {
            return redirect()->action('Dashboard\ObdController@transfer')->with('errorMessage', 'Please enter credits');
        }
        if($request->from_user == 0)
        {
            $fromuser = DB::select("select FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits from leadger_settings");
        }
        else
        {
            $fromuser = DB::select("select FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits 
                from leadger_users where id = ".$request->from_user);
        }
        
        if($fromuser[0]->obd_credits < $request->credits)
        {
            return redirect()->action('Dashboard\ObdController@transfer')->with('errorMessage', 'Credit Balance low');
        }
        
        if($request->from_user == 0)
        {
            DB::statement("UPDATE leadger_settings SET obd_credits = AES_ENCRYPT(TO_BASE64 (FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1'))-".$request->credits."), 'MyNicotex@1')");
        }
        else
        {
            DB::statement("UPDATE leadger_users SET obd_credits = AES_ENCRYPT(TO_BASE64 (FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1'))-".$request->credits."), 'MyNicotex@1') where id = ".$request->from_user);
        }
        
        if($request->to_user == 0)
        {
            DB::statement("UPDATE leadger_settings SET obd_credits = AES_ENCRYPT(TO_BASE64 (FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1'))+".$request->credits."), 'MyNicotex@1')");
        }
        else
        {
            DB::statement("UPDATE leadger_users SET obd_credits = AES_ENCRYPT(TO_BASE64 (FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1'))+".$request->credits."), 'MyNicotex@1') where id = ".$request->to_user);
        }
        
        $transfer = new ObdCreditsTransfer;
        $transfer->from_user = $request->from_user;
        $transfer->to_user = $request->to_user;
        $transfer->credits = $request->credits;
        $transfer->reason = $request->reason;
        $transfer->created_by = Auth::user()->id;
        $transfer->save();

        return redirect()->action('Dashboard\ObdController@transfer')->with('doneMessage', $request->credits." Credits transferred Successfully");
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Campaigns = \App\Models\Campaign::where('expiry_date','>=',date('Y-m-d'))->get();
        $Setting = DB::select("SELECT FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits FROM leadger_users where id = ".Auth::user()->id);
        $Did = ObdDID::where('obd',1)->get();

        return view("dashboard.obd.create", compact("GeneralWebmasterSections", "Campaigns", "Setting", "Did"));
    }
    
    public function getPhoneNumbers(Request $request)
    {
        $phonenumber1 = \App\Models\Lead::wherein('campaign_id', $request->campaigns)
                    ->whereNotIn('status', function ($query) {
                        $query->select('id')
                            ->from('statuses')
                            ->where('name', 'DND');
                        })
                    ->pluck('phonenumber');
        $phonenumber2 = \App\Models\Lead::wherein('campaign_id', $request->campaigns)->whereNotNull('phonenumber_2')
                    ->whereNotIn('status', function ($query) {
                        $query->select('id')
                            ->from('statuses')
                            ->where('name', 'DND');
                        })
                    ->pluck('phonenumber_2');
        
        $phonenumbers = $phonenumber1->merge($phonenumber2)->unique()->toArray();
        
        echo json_encode(['success'=>true, 'numbers'=> implode(',',$phonenumbers)]);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone_numbers' => 'required',
            'did' => 'required',
            'schedule_start' => 'required',
            'schedule_end' => 'required',
            'file' => 'required',
            'credits' => 'required'
        ]);
        
        if(strtotime($request->start_time) < strtotime('09:00'))
        {
            return redirect()->action('Dashboard\ObdController@create')->with('errorMessage', 'Start Time must be after 9 AM');
        }
        if(strtotime($request->end_time) > strtotime('21:00'))
        {
            return redirect()->action('Dashboard\ObdController@create')->with('errorMessage', 'End Time must be before 9 PM');
        }

        // Start of Upload Files
        $formFileName = "file";
        $fileFinalName_ar = "";
        if ($request->$formFileName != "") {
            $fileFinalName_ar = time() . rand(1111,
                    9999) . '.' . $request->file($formFileName)->getClientOriginalExtension();
            $path = $this->uploadPath;
            $request->file($formFileName)->move($path, $fileFinalName_ar);
        }
        // End of Upload Files

        $Campaign = new ObdCampaign;
        $Campaign->name = $request->name;
        $Campaign->schedule_start = date('Y-m-d H:i',strtotime($request->schedule_start.' '.$request->start_time));
        $Campaign->schedule_end = date('Y-m-d H:i',strtotime($request->schedule_end.' '.$request->end_time));
        $Campaign->did = $request->did;
        $Campaign->audio_file = $fileFinalName_ar;
        $Campaign->status = 0;
        $Campaign->created_by = Auth::user()->id;
        $Campaign->save();
        
        $CampaignId = $Campaign->id;
        
        $numbers = collect(explode(',', $request->phone_numbers));
        
        $count = $numbers->filter(function($number) {
                return strlen($number) >= 10;
            })
            ->each(function($number) use($CampaignId) {
                $CampaignDetail = new ObdCampaignDetail;
                $CampaignDetail->obd_campaign_id = $CampaignId;
                $CampaignDetail->mobile_number = trim($number);
                $CampaignDetail->save();
                return $number;
            })->count();
        $used = $Campaign->credits = $count * $request->credits;
        $Campaign->save();
        DB::statement("UPDATE leadger_users SET obd_credits = AES_ENCRYPT(TO_BASE64 (FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1'))-".$used."), 'MyNicotex@1') where id = ".Auth::user()->id);
        
        return redirect()->action('Dashboard\ObdController@viewCampaigns')->with('doneMessage', __('backend.addDone'));
    }
    
    public function viewCampaigns()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.obd.list_campaigns", compact("GeneralWebmasterSections"));
    }

    public function viewCampaignsData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $find_q = $request->input('find_q');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM leadger_obd_campaigns
INNER JOIN leadger_users
ON leadger_users.id = leadger_obd_campaigns.created_by
where 1=1 
";
        if(Auth::user()->permissionsGroup->name != 'Admin')
            $sql .= " and team.id in (".Auth::user()->team_id.") ";

        if ($find_q != "") {
            $sql .= " and leadger_obd_campaigns.name like '%".$find_q."%' ";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Campaigns = DB::select("SELECT leadger_obd_campaigns.*,
leadger_users.name AS added_by
                    ".$sql." ORDER BY $order $dir,created_at DESC limit $start,$limit
                    ");

        $Status = [0 => 'Created',1 => 'Processing', 2 => 'Executed', 3 => 'Completed'];
        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Campaigns as $Row) {
                $nestedData = [];
                $nestedData['id'] = $Row->id;
                $nestedData['created_at'] = $Row->created_at;
                $nestedData['name'] = $Row->name;
                $nestedData['schedule_start'] = $Row->schedule_start;
                $nestedData['schedule_end'] = $Row->schedule_end;
                $nestedData['did'] = $Row->did;
                $nestedData['credits'] = $Row->credits;
                $nestedData['vi_campaign_id'] = $Row->vi_campaign_id;
                $nestedData['added_by'] = $Row->added_by;
                $nestedData['status'] = $Status[$Row->status];
                $viewurl = route('obdViewCampaignDetail', $Row->id);
                $downloadurl = $this->uploadPath.$Row->audio_file;
                $nestedData['options'] = <<<AAA
                        <div class="action-items">
                        <a 
                                       href="$viewurl">
                                        <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="View">&#xe8f4;</i>
                                        </small>
                                    </a>
                        <a
                                       href="$downloadurl" target="_blank">
                                        <small><span class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Download">file_download</span>
                                        </small>
                                    </a>
                        AAA;
                if(in_array($Row->status,[1,2]) && !empty($Row->vi_campaign_id))
                {
                    $fetchurl = route('obdFetchCampaignStatus', $Row->vi_campaign_id);
                    $nestedData['options'] .= <<<AAA
                            <a 
                                           href="$fetchurl">
                                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Fetch Status">&#xf8e8;</i> 
                                            </small>
                                        </a>
                            AAA;
                }
                $nestedData['options'] .= <<<AAA
                            </div>
                            AAA;
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
    
    public function viewCampaignDetail($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Campaign = ObdCampaign::find($id);
        
        $details = DB::select("SELECT COUNT(*) AS calls_count,
SUM(CASE WHEN hangup_cause = 'Call Connected' THEN 1 ELSE 0 END) AS connected,
SUM(CASE WHEN hangup_cause = 'Busy / Switch Off / Out of Coverage' THEN 1 ELSE 0 END) AS busy,
SUM(CASE WHEN hangup_cause = 'No Ring' THEN 1 ELSE 0 END) AS no_ring,
SUM(CASE WHEN hangup_cause = 'No Answer' THEN 1 ELSE 0 END) AS no_answer,
SUM(CASE WHEN hangup_cause = 'No Dial Tone' THEN 1 ELSE 0 END) AS no_dialtone,
SUM(CASE WHEN hangup_cause = 'Time Out' THEN 1 ELSE 0 END) AS timeout 
FROM leadger_obd_campaign_detail
WHERE obd_campaign_id = $id ");

        return view("dashboard.obd.list_campaign_detail", compact("GeneralWebmasterSections", "Campaign", "details"));
    }

    public function viewCampaignDetailData($id, Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $find_q = $request->input('find_q');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM leadger_obd_campaign_detail
where obd_campaign_id = $id
";
//        if(Auth::user()->permissionsGroup->name != 'Admin')
//            $sql .= " and team.id in (".Auth::user()->team_id.") ";

        if ($find_q != "") {
            $sql .= " and mobile_number like '%".$find_q."%' ";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Campaigns = DB::select("SELECT *
                    ".$sql." ORDER BY $order $dir,created_at DESC limit $start,$limit
                    ");

        $Status = [0 => 'Created',1 => 'Executed',2 => 'Completed'];
        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Campaigns as $Row) {
                $nestedData = [];
                $nestedData['id'] = $Row->id;
                $nestedData['mobile_number'] = $Row->mobile_number;
                $nestedData['call_initiated_time'] = $Row->call_initiated_time;
                $nestedData['call_answered_time'] = $Row->call_answered_time;
                $nestedData['call_end_time'] = $Row->call_end_time;
                $nestedData['call_duration'] = $Row->call_duration;
                $nestedData['hangup_cause'] = $Row->hangup_cause;
                $nestedData['disconnected_by'] = $Row->disconnected_by;
                $nestedData['status'] = $Status[$Row->status];
                $nestedData['no_of_calls'] = $Row->no_of_calls;
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

    public function fetchCampaignStatus($id)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: ffcab6c009f568588842b54f15de6efb"
            )
        );

        try{
            $context = stream_context_create($opts);
            $response = @file_get_contents('https://obd.ametecsindia.com/admin/obd_data/'.$id,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        
        if(empty($response))
        {
            return redirect()->action('Dashboard\ObdController@viewCampaigns')->with('errorMessage', 'Error fetching details');
        }
        if($response->status != 200)
        {
            return redirect()->action('Dashboard\ObdController@viewCampaigns')->with('errorMessage', $response->message);
        }
        $data = $response->data;
        $obd = ObdCampaign::where('vi_campaign_id', $id)->first();
        DB::Statement("update leadger_obd_campaign_detail set no_of_calls = 0 where obd_campaign_id = '".$obd->id."'");
        foreach($data as $row)
        {
            $detail = ObdCampaignDetail::where('obd_campaign_id', $obd->id)
                    ->where('mobile_number', $row->customer_number)->first();
            if(empty($detail))
                continue;
            $detail->call_initiated_time = $row->call_initiated_time;
            $detail->call_answered_time = $row->call_answered_time;
            $detail->call_end_time = $row->call_end_time;
            $detail->call_duration = $row->call_duration;
            $detail->hangup_cause = $row->hangup_cause;
            $detail->disconnected_by = $row->disconnected_by;
            $detail->status = 2;
            $detail->no_of_calls = $detail->no_of_calls + 1;
            $detail->save();
        }
        
        $updated = DB::Select("select * from leadger_obd_campaign_detail where obd_campaign_id = ".$obd->id." and call_duration is null");
        if(empty($updated))
        {
            $obd->status = 3;
            $obd->save();
        }
        
        return redirect()->action('Dashboard\ObdController@viewCampaigns')->with('errorMessage', $obd->name.' Campaign Status Updated');
    }

    public function did_add(Request $request)
    {
        $this->validate($request, [
            'voucher_code' => 'required',
        ]);
        
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        require_once(base_path().'/vendor/ezywing/Helper.php');
        $result = \License\Helpers\Helper::didVoucherValidate($request->voucher_code, $full_response['data']['crm_id']);
       
        if(empty($result))
        {
            return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', 'Something went wrong');
        }
        
        if($result->status != 200)
        {
            return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', $result->message);
        }
        $voucherUsed = \License\Helpers\Helper::didVoucherUsed($request->voucher_code);
        if($voucherUsed || ($result && isset($result->status) && $result->status == 200))
        {
            DB::beginTransaction();
            try {
                DB::statement("
                    INSERT INTO leadger_obd_did_history 
                        (virtual_number, obd, click2call, tc_verified, created_by, created_at, updated_at, team_id, expiry, voucher_id, webrtc)
                    SELECT 
                        virtual_number, obd, click2call, tc_verified, created_by, created_at, updated_at, team_id, expiry, voucher_id, webrtc
                    FROM leadger_obd_did
                ");

                // ✅ 2. Copy Smart31 VMN Numbers data to history table
                DB::statement("
                    INSERT INTO leadger_smart31_history 
                        (vmn, usage_date, webrtc, c2c, obd, verified, group_id, type, created_at, updated_at, group_name, assigned_groups, is_changed)
                    SELECT 
                        vmn, usage_date, webrtc, c2c, obd, verified, group_id, type, created_at, updated_at, group_name, assigned_groups, is_changed
                    FROM leadger_smart31_vmn_numbers
                ");
                DB::statement("delete from leadger_obd_did");
                DB::statement("delete from leadger_smart31_vmn_numbers");
                $click2call = explode(',',$result->data->click2call_did);
                $obd = explode(',',$result->data->obd_did);
                $verified = explode(',',$result->data->tc_verified);
                $webrtc= explode(',',$result->data->webrtc_did);
                $all = array_unique(array_merge($click2call,$obd,$webrtc));
                $expiry = DB::select("select AES_ENCRYPT(TO_BASE64('".$result->data->expiry."'), 'MyNicotex@1') as expiry")[0]->expiry;
                
                $history = new ObdCreditsHistory;
                $history->voucher_code = $request->voucher_code;
                $history->credits = 0;
                $history->created_by = Auth::user()->id;
                $history->save();
                foreach($all as $each)
                {
                    if(empty($each))
                        continue;
                    $ObdDid = new ObdDID;
                    $ObdDid->virtual_number = $each;
                    $ObdDid->obd = in_array($each, $obd) ? 1 : 0;
                    $ObdDid->click2call = in_array($each, $click2call) ? 1 : 0;
                    $ObdDid->webrtc = in_array($each, $webrtc) ? 1 : 0;
                    $ObdDid->tc_verified = in_array($each, $verified) ? 1 : 0;
                    $ObdDid->created_by = Auth::user()->id;
                    $ObdDid->expiry = $expiry;
                    $ObdDid->team_id = '';
                    $ObdDid->voucher_id = $history->id;
                    $ObdDid->save();
                }
                $today = date('Y-m-d');
                $smartGroups = $result->data->smart_groups;
                foreach($smartGroups as $group) {
                    // Only process VMNs with usage_date = today
                    if ($group->usage_date !== $today) {
                        continue;
                    }
                    DB::statement("
                        INSERT INTO leadger_smart31_vmn_numbers 
                        (vmn, usage_date, webrtc, c2c, obd, verified, group_id, type, created_at, updated_at, group_name) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            webrtc = VALUES(webrtc), 
                            c2c = VALUES(c2c), 
                            obd = VALUES(obd), 
                            verified = VALUES(verified), 
                            group_id = VALUES(group_id), 
                            type = VALUES(type), 
                            created_at = VALUES(created_at), 
                            updated_at = VALUES(updated_at), 
                            group_name = VALUES(group_name)
                    ", [
                        $group->vmn,
                        $group->usage_date,
                        $group->webrtc,
                        $group->c2c,
                        $group->obd,
                        $group->verified,
                        $group->group_id,
                        $group->type ?? null,
                        $group->created_at,
                        $group->updated_at,
                        $group->group_name
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Illuminate\Support\Facades\Log::error("Failed to store voucher: " . $e->getMessage());
                return redirect()->action('Dashboard\ObdController@did')->with('errorMessage', "Error saving voucher data: " . $e->getMessage());
            }
        }
        return redirect()->action('Dashboard\ObdController@did')->with('doneMessage', "DID's added Successfully");
    }
    
    public function downloadCampaignDetail($id)
    {
        
        $sql = " FROM leadger_obd_campaign_detail
where obd_campaign_id = $id
";
        $Campaigns = DB::select("SELECT *
                    ".$sql." ORDER BY id asc,created_at DESC
                    ");
        $Status = [0 => 'Created',1 => 'Executed',2 => 'Completed'];
        $data = array();
        foreach ($Campaigns as $Row) {
            $nestedData = [];
            $nestedData['id'] = $Row->id;
            $nestedData['mobile_number'] = $Row->mobile_number;
            $nestedData['call_initiated_time'] = $Row->call_initiated_time;
            $nestedData['call_answered_time'] = $Row->call_answered_time;
            $nestedData['call_end_time'] = $Row->call_end_time;
            $nestedData['call_duration'] = $Row->call_duration;
            $nestedData['hangup_cause'] = $Row->hangup_cause;
            $nestedData['disconnected_by'] = $Row->disconnected_by;
            $nestedData['status'] = $Status[$Row->status];
            $nestedData['no_of_calls'] = $Row->no_of_calls;
            $data[] = $nestedData;
        }
        
        return response()->streamDownloadCSV(
            'OBD',
            [
                'Mobile number',
                'Call initiated time',
                'Call answered time',
                'Call end time',
                'Call duration',
                'Hangup cause',
                'Disconnected by',
                'Status',
                'No of calls'
            ],
            [
                'mobile_number',
                'call_initiated_time',
                'call_answered_time',
                'call_end_time',
                'call_duration',
                'hangup_cause',
                'disconnected_by',
                'status',
                'no_of_calls'
            ],
            $data
        );
    }
}
