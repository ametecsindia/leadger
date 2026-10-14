<?php

namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Models\WebmasterSection;
use Auth;
use App\Models\Lead;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;
use App\Models\Click2CallLog;
use File;
use App\Models\Campaign;
use App\Models\User;
use Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FlexiDialerVouchers;


class FlexiDialerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');


    }

    public function index() {
     
       $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

        $flexiDialerVouchers = FlexiDialerVouchers::select('flexi_dialer_vouchers.*','users.name as user_name', DB::raw("FROM_BASE64(AES_DECRYPT(expiry, 'MyNicotex@1')) as expiry"))
                                        ->join('users','flexi_dialer_vouchers.user_id', 'users.id')
                                        ->orderBy('id', 'desc')
                                        ->get();
        
        return view('dashboard.flexidial.voucher', compact("GeneralWebmasterSections","flexiDialerVouchers"));
    }

    public function store(Request $request) {
        $this->validate($request, [
            'voucher_code' => 'required',
        ]);
        
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        require_once(base_path().'/vendor/ezywing/Helper.php');
        $result = \License\Helpers\Helper::flexiDialerVoucherValidate($request->voucher_code, $full_response['data']['crm_id']);
       
        if(empty($result))
        {
            return redirect()->action('Dashboard\FlexiDialerController@index')->with('errorMessage', 'Something went wrong');
        }
        
        if($result->status != 200)
        {
            return redirect()->action('Dashboard\FlexiDialerController@index')->with('errorMessage', $result->message);
        }
    

        if(!empty($result->data->status) && $result->data->status == 'used'){
              return redirect()->action('Dashboard\FlexiDialerController@index')->with('errorMessage','Flexi Dialer Voucher already used');
        }
        
        if(!empty($result->data->status) && \License\Helpers\Helper::updateFlexiDialerVoucher($request->voucher_code))
        {
            $expiry = DB::select("select AES_ENCRYPT(TO_BASE64('".$result->data->expiry."'), 'MyNicotex@1') as expiry")[0]->expiry;
            $user=auth()->user();
            $voucher = new FlexiDialerVouchers();
            $voucher->voucher_code = $request->voucher_code;
            $voucher->type = $result->data->type;
            $voucher->expiry = $expiry;
            $voucher->channels = $result->data->click2call_channels;
            $voucher->webrtc_channels = $result->data->webrtc_channels;
            $voucher->channel_type = $result->data->channel_type;
            $voucher->user_id = $user->id;
            $voucher->save();

           
            
          return redirect()->action('Dashboard\FlexiDialerController@index')->with('doneMessage', "Flexi Voucher added Successfully");

        }
        return redirect()->action('Dashboard\FlexiDialerController@index')->with("Something went wrong");
    }
     public function logCall(Request $request)
    {
        $this->validate($request, [
            'lead_id' => 'required',
            'phonenumber' => 'required',
            'call_type' => 'required'
        ]);
        $Lead = Lead::find($request->lead_id);
        $campaign = DB::table('campaigns')->where('id', $Lead->campaign_id)->first();


        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Campaign not found'
            ], 404);
        }
    
        // Check if the campaign is expired
        if ($campaign->expiry_date < now()) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Campaign has expired'
            ], 400);
        }
        // Enforce backend outbound calling restrictions (hours + retry limit)
        $callCheck = \App\Services\CallingRestrictionService::canPlaceOutboundCall($request->phonenumber, Auth::user(), $request->call_type, !in_array($request->call_type, ['click2call', 'flexidial'], true));   // Click2Call: counted by Helper::click2call
        if (!$callCheck['allowed']) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $callCheck['message'],
                'reason' => $callCheck['reason'] ?? 'restriction'
            ], $callCheck['code'] ?? 403);
        }

        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        if ($request->call_type == 'click2call' && !Auth::user()->canClick2Call()) {
            return response()->json(['success' => false, 'error' => true, 'message' => 'Click2Call is not enabled for your account. Ask your administrator to enable Click2Call (People -> Staff, or Click2Call) with your mobile number.'], 403);
        }
        if($request->call_type == 'click2call'|| $request->call_type == 'flexidial')
        {
            if(!isset($full_response['data']['crm_id']))
            {
                return json_encode(['success' => false, 'error' => true, 'message' => 'CRM ID is not available']);
            }
        }

        $Lead = Lead::find($request->lead_id);
       
        $CallLog = new CallLog;
        $CallLog->phonenumber = $request->phonenumber;
        $CallLog->call_type = $request->call_type;
        $CallLog->lead_id = $request->lead_id;
        $CallLog->current_status = $Lead->status;
        // FlexiDial calls are campaign calls: Call Report (Source: Campaign) + Call Recording
        $CallLog->campaign_id = $Lead->campaign_id;
        $CallLog->call_source = 'campaign';
        $CallLog->call_state = 'initiated';
        $CallLog->start_time = now();
        $CallLog->duration = 0;

        $CallLog->verified = 0;
        if($request->call_type == 'click2call'|| $request->call_type == 'flexidial')
        {
            $did = DB::select("select * from leadger_obd_did where virtual_number = '".$request->vmn."'");
            $CallLog->vmn_used = $request->vmn;
            $CallLog->verified = $did[0]->tc_verified??'1';
        }

        $CallLog->created_by = Auth::user()->id;
        $CallLog->created_at = date('Y-m-d H:i:s');
        $CallLog->save();
        $referenceId = $request->lead_id.'_Mfc'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified;
        $CallLog->reference_id = $referenceId;
        $CallLog->save();
        if($request->call_type == 'click2call'||$request->call_type == 'flexidial')
        {
            if($request->default_vmn == 'true')
            {
                $userModel = User::find(Auth::user()->id);
                $userModel->c2c_default_number = $request->vmn;
                $userModel->save();
            }
           
            Click2CallLog::create([
                'lead_id' => $request->lead_id,
                'reference_id' => $referenceId,
                'user_id' => Auth::user()->id,
                'vmn'=>$request->vmn,
            ]);
            $callfields = array(
                "did" => $request->vmn,
                "a_party_no" => $request->phonenumber,
                "b_party_no" => Auth::user()->click_agent_number,
                "ref_id" => $request->lead_id.'_Mfc'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified,
                "record" => 1,
                "recording" => 1,
                "record_call" => 1,
            );
            $CallLog->provider_call_id = $referenceId;
            $CallLog->save();
            return \App\Helpers\Helper::click2call($callfields, $CallLog->id);
        }
        else if($request->call_type == 'phone' && !empty(Auth::user()->fcm_key) && Auth::user()->fcm_key != '' && !empty(env('firebase_key')))
        {
            $fields = ['data'=>[
                'userId'=>Auth::user()->id,
                'mobileNumber'=>$request->phonenumber,
                'leadId'=>$request->lead_id,
            ],
            "android" => [
                "ttl" => "120s",
                "priority" => "high",
            ],
                'to'=>Auth::user()->fcm_key];
            //\App\Helpers\Helper::sendFCM($fields);
        }

        return response()->json([
            'call_log_id' => $CallLog->id,
            'success' => true
        ]);
    }
      private function flexidial()
    {
        $user=auth()->user();

        $running = DB::select("SELECT * FROM leadger_flexidialer
                WHERE dial_status = 1 AND c2c_status is not null
                and c2c_status != 'Call End' and agent_id = '".$user->id."'
                and CURRENT_TIMESTAMP-updated_at <= ".env('flexidial_delay_sec', 120));
        if(empty($running))
        {
            $pending = DB::select("SELECT * FROM leadger_flexidialer WHERE dial_status = 0 and agent_id = '".$user->id."' limit ".$user->flexidial_call_rate * $user->flexidial_parallel_users);
            if(!empty($pending)) {
                $campaign = Campaign::find($pending[0]->campaign_id);
                $groups = [$campaign->team_id];

                $conditions = [];

                foreach ($groups as $group_id) {
                    if (is_numeric($group_id)) {
                        $conditions[] = "FIND_IN_SET($group_id, team_id)";
                    }
                }
                $obd = DB::select("select * from leadger_obd_did where click2call = 1 and (".implode(' OR ',$conditions).") limit ".count($pending));
                if(count($pending) != count($obd))
                {
                    return false;
                }
                if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
                {
                    $employeeList = DB::table('users')
                            ->pluck('id')
                            ->toArray();
                    $employeeIdString = implode("','",$employeeList);
                    $users = DB::select("SELECT * FROM leadger_users
                            WHERE c2c_channel = 1
                            and flexidial_status = 1
                            AND id NOT IN (SELECT agent_id FROM leadger_flexidialer WHERE dial_status = 0)
                            and id in ('".$employeeIdString."')
                            limit ".$user->flexidial_parallel_users);
                }
                else
                {
                    $users = DB::select("SELECT * FROM leadger_users
                        WHERE c2c_channel = 1
                        and flexidial_status = 1
                        and id = ".$user->id);
                }
                $request = [
                    'call_type' => 'flexidial',
                    'manualcall' => 'false',
                    'fieldcall' => 'false',
                    'vmn' => '',
                    'default_vmn' => 'false',
                    'dialerType' => 'flexidial'
                ];
                for($j=0; $j<count($users); $j++)
                {
                    $user = User::find($users[$j]->id);
                    if(count($users) != 1) {
                        $pending = DB::select("SELECT * FROM leadger_flexidialer WHERE dial_status = 0 and agent_id = '".$user->id."' limit ".$user->flexidial_call_rate);
                    }
                    for($i=0; $i<count($pending); $i++)
                    {
                        $requestData = [
                            'lead_id' => $pending[$i]->lead_id,
                            'phonenumber' => $pending[$i]->mobile_number,
                            'call_type' => 'flexidial',
                            'vmn' => $obd[$i]->virtual_number,
                            'default_vmn' => 'false'
                        ];

                        $requestObj = new Request($requestData);

                        $leadController = new FlexiDialerController();
                        $response = $leadController->logCall($requestObj);

                        /* convert response to array */
                        if ($response instanceof \Illuminate\Http\JsonResponse) {
                            $response = $response->getData(true);
                        } elseif (is_string($response)) {
                            $response = json_decode($response, true);
                        }

                        Log::info($response);

                        if(isset($response['success']) && $response['success'] == false)
                        {
                            echo $response['message'];
                        }
                        else
                        {
                        
                            DB::update("
                                UPDATE leadger_flexidialer 
                                SET dial_status = 1,
                                    c2c_status='Dialed',
                                    agent_did = '".$obd[$i]->virtual_number."',
                                    call_log_id = '".$response['call_log_id']."',
                                    updated_at = CURRENT_TIMESTAMP,
                                    agent_number = '".$user->click_agent_number."'
                                WHERE id = '".$pending[$i]->id."'
                            ");
                        }
                    }
                }
            }
        }
        return true;
    }
private function webrtcflexidial()
{
    // 0. Enforce calling hours: stop dialing outside window, leave pending leads untouched
    $hoursCheck = \App\Services\CallingRestrictionService::checkCallingHours();
    if (!$hoursCheck['allowed']) {
        return 'outside_calling_hours';
    }

    // 1?? Already running ? stay calm
   $user = auth()->user();
   sleep(1);
    $running = DB::select(
        "SELECT id FROM leadger_webrtc_flexidialer
         WHERE dial_status = 1
           AND c2c_status IS NOT NULL
           AND c2c_status != 'Call End'
           AND agent_id = ?
           AND CURRENT_TIMESTAMP - updated_at <= ?",
        [$user->id, env('flexidial_delay_sec', 120)]
    );

    if (!empty($running)) {
        return 'ok';
    }

    // 2?? Pending leads
    $pending = DB::select(
        "SELECT * FROM leadger_webrtc_flexidialer
         WHERE dial_status = 0
           AND agent_id = ?
         LIMIT ?",
        [
            $user->id,
            $user->webrtc_flexidial_call_rate * $user->webrtc_flexidial_parallel_users
        ]
    );

    if (empty($pending)) {
        return 'idle';
    }

    // 3?? Campaign validation
    $campaign = Campaign::find($pending[0]->campaign_id);
    if (!$campaign || empty($campaign->group_id)) {
        return 'vmn_insufficient';
    }

    $groups = array_filter(explode(',', $campaign->group_id));
    if (empty($groups)) {
        return 'vmn_insufficient';
    }

    $conditions = array_map(
        fn ($g) => "FIND_IN_SET($g, `group`)",
        $groups
    );

    // 4?? VMN check
$obd = DB::select(
    "SELECT * FROM leadger_obd_did
     WHERE webrtc = 1
       AND (" . implode(' OR ', $conditions) . ")"
);
$randomDid = $obd[array_rand($obd)];
$selectedDid = $randomDid->virtual_number;

if (empty($obd)) {
    return 'vmn_insufficient';
}
    // 5?? Users selection
    if (!$this->user->hasRole('TeleCaller') && !$this->user->hasRole('TeleCaller-WFH')) {

        $employeeList = Common::getEmployees($this->user);
        if (empty($employeeList)) {
            return 'idle';
        }

        $employeeIds = implode("','", $employeeList);

        $users = DB::select(
            "SELECT * FROM leadger_users
             WHERE webrtc_channel = 1
               AND webrtc_flexidial_status = 1
               AND id IN ('$employeeIds')
               AND id IN (
                    SELECT user_id FROM campaign_members
                    WHERE campaign_id = ?
               )
             LIMIT ?",
            [$campaign->id, $user->webrtc_flexidial_parallel_users]
        );

    } else {
        $users = [$user];
    }

    // 6?? Dial loop
    foreach ($users as $u) {

        $pending = DB::select(
            "SELECT * FROM webrtc_flexidialer
             WHERE dial_status = 0
               AND agent_id = ?
             LIMIT ?",
            [$this->user->id, $this->user->webrtc_flexidial_call_rate]
        );

        if (empty($pending)) {
            continue;
        }

        $callTargets = [];
        $flexiIds    = [];

        foreach ($pending as $row) {
            $callTargets[] = [
                'lead_id' => $row->lead_id,
                'mobile'  => $row->mobile_number
            ];
            $flexiIds[] = $row->id;
        }

        // ?? Call API
$callResult = $this->sendMultiWebrtcCall(
    $callTargets,
    $selectedDid
);
if (is_string($callResult)) {
    return $callResult;
}



        if (in_array($callResult, ['agent_busy', 'api_error'], true)) {
            return $callResult;
        }
       $webrtcCallId = $callResult['webrtc_call_id'];
      $callLogMap   = $callResult['call_log_map'];


foreach ($pending as $row) {

    if (!isset($callLogMap[$row->lead_id])) {
        continue;
    }

    DB::table('webrtc_flexidialer')
        ->where('id', $row->id)
        ->update([
            'dial_status'  => 1,
            'c2c_status'   => 'Dialed',
            'agent_did'    => $selectedDid,
            'call_log_id'  => $callLogMap[$row->lead_id],
            'agent_number' => $user->click_agent_number,
            'updated_at'   => now()
        ]);
}
    }

    return 'ok';
}
private function sendMultiWebrtcCall(array $callTargets, $vmn)
{
    $user = auth()->user();

    // Filter targets through outbound call restrictions
    $filteredTargets = [];
    foreach ($callTargets as $row) {
        $check = \App\Services\CallingRestrictionService::canPlaceOutboundCall($row['mobile'], $user, 'webrtc-flexi', true);
        if ($check['allowed']) {
            $filteredTargets[] = $row;
        }
    }
    $callTargets = $filteredTargets;

    if (empty($callTargets)) {
        return 'all_restricted';
    }

    $callLogIds = [];
    $numbers    = [];
    $callLogMap = [];
    foreach ($callTargets as $row) {

        $lead = Lead::find($row['lead_id']);
        if (!$lead) {
            continue;
        }

        // Real call_logs columns (the old keys campaign_name / called_mobile_number / attempted_by do not exist)
        $log = CallLog::create([
            'lead_id'      => $lead->id,
            'campaign_id'  => $lead->campaign_id,
            'phonenumber'  => $row['mobile'],
            'created_by'   => $user->id,
            'call_type'    => 'webrtc-flexi',
            'call_source'  => 'campaign',
            'call_state'   => 'initiated',
            'start_time'   => now(),
            'duration'     => 0,
            'current_status' => $lead->status,
            'verified'     => 1,
            'vmn_used'     => $vmn
        ]);

        $callLogIds[] = $log->id;
        $callLogMap[$row['lead_id']] = $log->id;

        $numbers[]    = $row['mobile'];
    }

    if (empty($numbers)) {
        return 'api_error';
    }

    $payload = [
        "username"        => $user->smartping_user_id,
        "customer_number" => implode('|', $numbers),
        "reference_id" => implode(',', $callLogIds) . '_wfc',
        "user_group"      => "ametec",
        "did"             => $vmn
    ];

    $ch = curl_init("https://smartcloud.smartping.io/api/clickToCall");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return 'api_error';
    }

    $api = json_decode($response, true);

    if (!empty($api['error']) && str_contains(strtolower($api['error']), 'busy')) {
        return 'agent_busy';
    }

    if (empty($api['message'])) {
        return 'api_error';
    }

    CallLog::whereIn('id', $callLogIds)
        ->update(['webrtc_call_id' => $api['message']]);

    return [
    'webrtc_call_id' => $api['message'],
    'call_log_map'   => $callLogMap
];

}


    public function flexidialView()
    {
        $obdActive = true;
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

        $user=auth()->user();
        $colsarr = ['lead_id', 'customer_name','campaign_name', 'mobile_number', 'dial_status', 'a_party_status', 'b_party_status', 'agent_did'];
        $usedChannels = DB::select("SELECT COALESCE(SUM(flexidial_call_rate * flexidial_parallel_users),0) AS used FROM leadger_users WHERE flexidial_status = 1");
        $running=0;
        $total_ch = DB::selectOne("
    SELECT channels
    FROM leadger_flexi_dialer_vouchers
    WHERE status = 'active'
    LIMIT 1
")->channels ?? 0;

        if($user->flexidial_status == '1') {
            $running = DB::select("
                    SELECT flexidialer.*, c.name as campaign_name,
                    leadger_leads.name as customer_name
                    FROM leadger_flexidialer as flexidialer
                    LEFT JOIN leadger_campaigns c ON flexidialer.campaign_id = c.id
                    left join leadger_leads
            on leadger_leads.id = flexidialer.lead_id and c.id = leadger_leads.campaign_id and flexidialer.campaign_id = leadger_leads.campaign_id
                    WHERE dial_status = 1
                      AND c2c_status IS NOT NULL
                      AND c2c_status != 'Call End'
                      and CURRENT_TIMESTAMP-flexidialer.updated_at <= ".env('flexidial_delay_sec', 120)."
                      AND (agent_id = ? or agent_number = ?)", [$user->id, $user->click_agent_number]);

        }
      $campaigns= DB::table('flexidialer as f')
        ->join('campaigns as c', 'c.id', '=', 'f.campaign_id')
        ->select('c.id', 'c.name')
        ->distinct()
        ->orderBy('c.name')
        ->get();
     
        return view('dashboard.flexidial.click2call', compact("GeneralWebmasterSections","user","campaigns","running","usedChannels","colsarr","total_ch"));
    }
    public function WebRTCFlexidialView()
    {
         $obdActive = true;
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

        $user=auth()->user();
        $colsarr = ['lead_id', 'customer_name','campaign_name', 'mobile_number', 'dial_status', 'a_party_status', 'b_party_status', 'agent_did'];
        $usedChannels = DB::select("SELECT COALESCE(SUM(webrtc_flexidial_call_rate * webrtc_flexidial_parallel_users),0) AS used FROM leadger_users WHERE webrtc_flexidial_status = 1");
        $running=0;
        $total_ch = DB::selectOne("
    SELECT webrtc_channels
    FROM leadger_flexi_dialer_vouchers
    WHERE status = 'active'
    LIMIT 1
")->webrtc_channels ?? 0;

        if($user->flexidial_status == '1') {
            $running = DB::select("
                    SELECT flexidialer.*, c.name as campaign_name,
                    leadger_leads.name as customer_name
                    FROM leadger_webrtc_flexidialer as flexidialer
                    LEFT JOIN leadger_campaigns c ON flexidialer.campaign_id = c.id
                    left join leadger_leads
            on leadger_leads.id = flexidialer.lead_id and c.id = leadger_leads.campaign_id and flexidialer.campaign_id = leadger_leads.campaign_id
                    WHERE dial_status = 1
                      AND c2c_status IS NOT NULL
                      AND c2c_status != 'Call End'
                      and CURRENT_TIMESTAMP-flexidialer.updated_at <= ".env('flexidial_delay_sec', 120)."
                      AND (agent_id = ? or agent_number = ?)", [$user->id, $user->click_agent_number]);

        }
      $campaigns= DB::table('webrtc_flexidialer as f')
        ->join('campaigns as c', 'c.id', '=', 'f.campaign_id')
        ->select('c.id', 'c.name')
        ->distinct()
        ->orderBy('c.name')
        ->get();
     
        return view('dashboard.flexidial.webrtc', compact("GeneralWebmasterSections","user","campaigns","running","usedChannels","colsarr","total_ch"));
    }


    public function flexidialData(Request $request)
    {
        $employeeList=null;
        if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $employeeList = [Auth::id()];
        }
        else
        {
            $employeeList = DB::table('users')
                            ->pluck('id')
                            ->toArray();
        }


       
        $employeeIdString = implode("','",$employeeList);
        $colsinput = $request->input('columns');
	    $campaignId = $request->input('campaign_id'); 
        $searchData = [];
        $i = 0;

        $limit = '';
        $pagelimit = $request->input('length');
        $q = $request->input('search')['value'];
        $pagination = (isset($pagelimit)) ? $request->input('length') : 0;
        if ($pagination) {
            $limit = $request->input('length');
            $start = $request->input('start');
            $limit = "LIMIT " . $start . ", " . $limit;
        }

        $sql = "FROM leadger_flexidialer
            left join leadger_campaigns c
            on leadger_flexidialer.campaign_id = c.id
            left join leadger_call_logs
            on leadger_call_logs.id = leadger_flexidialer.call_log_id
            left join leadger_leads
            on leadger_leads.id = leadger_flexidialer.lead_id and c.id = leadger_leads.campaign_id and leadger_flexidialer.campaign_id = leadger_leads.campaign_id
                where agent_id in ('".$employeeIdString."') ";
	    if (!empty($campaignId)) {
        $sql .= " and leadger_flexidialer.campaign_id = " . intval($campaignId) . " ";
    }
        if($q)
        {
            $sql .= " and (leadger_flexidialer.lead_id like '%$q%' or c.name like '%$q%'  or c.name like '%$q%' or
                    leadger_flexidialer.mobile_number like '%$q%' or leadger_leads.name like '%$q%')";
        }
        $totalSql = "select count(*) as total ".$sql;
        $totalRes = DB::select(DB::raw($totalSql));
        $filteredCount = $totalCount = intval($totalRes[0]->total);
        $sql = "SELECT leadger_flexidialer.*,c.name as campaign_name,
        REPLACE(JSON_EXTRACT(click_response, \"$.A_DIAL_STATUS\"),'\"','') as a_party_status,
        REPLACE(JSON_EXTRACT(click_response, \"$.B_DIAL_STATUS\"),'\"','') as b_party_status,
        leadger_leads.name as customer_name "
        .$sql.$limit;
        $res = DB::select($sql);

        $output = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalCount,
            "recordsFiltered" => $filteredCount,
            "data" => $res
        );
        echo json_encode($output);
    }
public function webrtcflexidialData(Request $request)
    {
          $employeeList=null;
        if(Auth::user()->permissionsGroup->name == 'Telecaller')
        {
            $employeeList = [Auth::id()];
        }
        else
        {
            $employeeList = DB::table('users')
                            ->pluck('id')
                            ->toArray();
        }


       
        $employeeIdString = implode("','",$employeeList);
        $colsinput = $request->input('columns');
	    $campaignId = $request->input('campaign_id'); 
        $searchData = [];
        $i = 0;

        $limit = '';
        $pagelimit = $request->input('length');
        $q = $request->input('search')['value'];
        $pagination = (isset($pagelimit)) ? $request->input('length') : 0;
        if ($pagination) {
            $limit = $request->input('length');
            $start = $request->input('start');
            $limit = "LIMIT " . $start . ", " . $limit;
        }

        $sql = "FROM leadger_webrtc_flexidialer
            left join leadger_campaigns c
            on leadger_webrtc_flexidialer.campaign_id = c.id
            left join leadger_call_logs
            on leadger_call_logs.id = leadger_webrtc_flexidialer.call_log_id
            left join leadger_leads
            on leadger_leads.id = leadger_webrtc_flexidialer.lead_id and c.id = leadger_leads.campaign_id and leadger_webrtc_flexidialer.campaign_id = leadger_leads.campaign_id
                where agent_id in ('".$employeeIdString."') ";
	    if (!empty($campaignId)) {
        $sql .= " and leadger_flexidialer.campaign_id = " . intval($campaignId) . " ";
    }
        if($q)
        {
            $sql .= " and (leadger_webrtc_flexidialer.lead_id like '%$q%' or c.name like '%$q%'  or c.name like '%$q%' or
                    leadger_webrtc_flexidialer.mobile_number like '%$q%' or leadger_leads.name like '%$q%')";
        }
        $totalSql = "select count(*) as total ".$sql;
        $totalRes = DB::select(DB::raw($totalSql));
        $filteredCount = $totalCount = intval($totalRes[0]->total);
        $sql = "SELECT leadger_webrtc_flexidialer.*,c.name as campaign_name,
        REPLACE(JSON_EXTRACT(click_response, \"$.A_DIAL_STATUS\"),'\"','') as a_party_status,
        REPLACE(JSON_EXTRACT(click_response, \"$.B_DIAL_STATUS\"),'\"','') as b_party_status,
        leadger_leads.name as customer_name "
        .$sql.$limit;
        $res = DB::select($sql);

        $output = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalCount,
            "recordsFiltered" => $filteredCount,
            "data" => $res
        );
        echo json_encode($output);
    }



public function createFlexidial()
{
    $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

    $user=auth()->user();
    $exists = DB::select("
        SELECT COUNT(*) AS count 
        FROM leadger_flexidialer 
        WHERE agent_id = ?", 
        [$user->id]
    );

    if ($exists[0]->count > 0) {
         return redirect()->action('Dashboard\FlexiDialerController@flexidialView')->with('errorMessage', 'Already exists');
    }

    $campaignId = DB::table('campaigns')
        ->where('team_id', $user->team_id)
        ->pluck('id');


    $campaigns = DB::table('campaigns')
        ->whereIn('id', $campaignId)
        ->whereDate('expiry_date', '>', now())
        ->get();
  
    /** -------- Voucher channels -------- */
    $voucher = DB::table('flexi_dialer_vouchers')
        ->where('status', '=','Active')
        ->first();
   
    $flexidial_channels = $voucher->channels ?? 0;
    $webrtc_channels    = $voucher->webrtc_channels ?? 0;

    /** -------- Used channels -------- */
    $usedChannels = DB::table('users')
        ->where('flexidial_status', 1)
        ->sum(DB::raw('flexidial_call_rate * flexidial_parallel_users'));

    $usedWebRTCChannels = DB::table('users')
        ->where('webrtc_flexidial_status', 1)
        ->sum(DB::raw('webrtc_flexidial_call_rate * webrtc_flexidial_parallel_users'));

    /** -------- Remaining channels -------- */
    $remaining_flexidial_channels = max(
        $flexidial_channels - $usedChannels,
        0
    );

    $remaining_webrtc_channels = max(
        $webrtc_channels - $usedWebRTCChannels,
        0
    );

    /** -------- Max rate -------- */
    $obd = DB::table('obd_did')->where('click2call', 1)->count();
    $webrtc=DB::table('obd_did')->where('webrtc', 1)->count();
    $max_flexidial_rate = min($obd, env('flexidial_rate', 10));
    $max_webrtc_flexidial_rate=min($webrtc, env('flexidial_rate', 10));
    return view('dashboard.flexidial.create', compact("GeneralWebmasterSections","user","obd","webrtc","flexidial_channels","webrtc_channels","remaining_flexidial_channels","remaining_webrtc_channels","max_flexidial_rate","max_webrtc_flexidial_rate","campaigns","usedChannels","usedWebRTCChannels"));
}

public function saveFlexidial(Request $request)
{
    $campaignId = $request->input('campaign');
    $campaign   = Campaign::find($campaignId);
        $user=auth()->user();
    if (!$campaign) {
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('errorMessage', 'Invalid Campaign');    
    }

    /* -------------------------------
     | GROUP CONDITIONS
     |-------------------------------*/
    $groups = [$campaign->team_id];

    $conditions = [];

    foreach ($groups as $group_id) {
        if (is_numeric($group_id)) {
            $conditions[] = "FIND_IN_SET($group_id, team_id)";
        }
    }

    $did = DB::select("
        SELECT * FROM leadger_obd_did
        WHERE click2call = 1
        AND (" . implode(' OR ', $conditions) . ")
    ");
   
    if (count($did) === 0) {
         return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('errorMessage', 'Virtual Numbers not assigned to group');
    }

    /* -------------------------------
     | FILTER CONDITIONS
     |-------------------------------*/
    $condition = '';
    $type = $request->input('type');

    if ($type === 'dp_code') {

        $dp_code = (array) $request->input('dp_code', []);

        if (!empty($dp_code)) {

            if (in_array('Blank', $dp_code) && count($dp_code) === 1) {
                $condition = " AND `status` IS NULL ";
            }
            elseif (in_array('Blank', $dp_code)) {
                $codes = array_diff($dp_code, ['Blank']);
                $in = "'" . implode("','", $codes) . "'";
                $condition = " AND (`status` IS NULL OR `status` IN ($in)) ";
            }
            elseif (!in_array('All', $dp_code)) {
                $in = "'" . implode("','", $dp_code) . "'";
                $condition = " AND `status` IN ($in) ";
            }
        }

    } 

    /* -------------------------------
     | MOBILE LOOP COUNT
     |-------------------------------*/
    if ($request->input('mobile_numbers') === 'all') {

        $res = DB::select("
            SELECT MAX(
                (CASE WHEN phonenumber IS NOT NULL AND phonenumber != '' THEN 1 ELSE 0 END) +
                (CASE WHEN phonenumber_2 IS NOT NULL AND phonenumber_2 != '' THEN 1 ELSE 0 END)
            ) AS num_elements
            FROM leadger_leads
            WHERE campaign_id = ?
            AND assigned_to = ?
            $condition
        ", [$campaignId, $user->id]);

        $loop = (int) ($res[0]->num_elements ?? 1);

    } else {
        $loop = 1;
    }

    /* -------------------------------
     | INSERT FLEXIDIAL DATA
     |-------------------------------*/
    DB::delete("
        DELETE FROM leadger_flexidialer
        WHERE agent_id = ?
    ", [$user->id]);

if ($request->input('mobile_numbers') === 'primary') {

    DB::insert("
        INSERT INTO leadger_flexidialer
        (lead_id, campaign_id, mobile_number, lead_status, agent_id, created_at, updated_at)
        SELECT
            id,
            campaign_id,
            phonenumber,
            status,
            assigned_to,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber IS NOT NULL
        AND phonenumber != ''
        $condition
    ", [$campaignId, $user->id]);

}
if ($request->mobile_numbers == 'all') {

    DB::insert("

        INSERT INTO leadger_flexidialer
        (lead_id, campaign_id, mobile_number, lead_status, agent_id, created_at, updated_at)

        SELECT id, campaign_id, phonenumber, status, assigned_to, NOW(), NOW()
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber IS NOT NULL
        $condition

        UNION ALL

        SELECT id, campaign_id, phonenumber_2, status, assigned_to, NOW(), NOW()
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber_2 IS NOT NULL
        $condition

    ", [$campaignId, $user->id, $campaignId, $user->id]);

}

    /* -------------------------------
     | UPDATE USER SETTINGS
     |-------------------------------*/
    $user = User::find($user->id);
    $user->flexidial_call_rate      = $request->input('flexidial_call_rate');
    $user->flexidial_parallel_users = $request->input('flexidial_parallel_users');
    $user->save();

    return redirect()->action('Dashboard\FlexiDialerController@flexidialView')->with('success', 'Successfully Created');
}


public function saveWebRTCFlexidial(Request $request)
{
    $campaignId = $request->input('campaign');
    $campaign   = Campaign::find($campaignId);
        $user=auth()->user();
    if (!$campaign) {
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('errorMessage', 'Invalid Campaign');    
    }

    /* -------------------------------
     | GROUP CONDITIONS
     |-------------------------------*/
    $groups = [$campaign->team_id];

    $conditions = [];

    foreach ($groups as $group_id) {
        if (is_numeric($group_id)) {
            $conditions[] = "FIND_IN_SET($group_id, team_id)";
        }
    }

    $did = DB::select("
        SELECT * FROM leadger_obd_did
        WHERE webrtc = 1
        AND (" . implode(' OR ', $conditions) . ")
    ");
   
    if (count($did) === 0) {
         return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('errorMessage', 'Virtual Numbers not assigned to group');
    }

    /* -------------------------------
     | FILTER CONDITIONS
     |-------------------------------*/
    $condition = '';
    $type = $request->input('type');

    if ($type === 'dp_code') {

        $dp_code = (array) $request->input('dp_code', []);

        if (!empty($dp_code)) {

            if (in_array('Blank', $dp_code) && count($dp_code) === 1) {
                $condition = " AND `status` IS NULL ";
            }
            elseif (in_array('Blank', $dp_code)) {
                $codes = array_diff($dp_code, ['Blank']);
                $in = "'" . implode("','", $codes) . "'";
                $condition = " AND (`status` IS NULL OR `status` IN ($in)) ";
            }
            elseif (!in_array('All', $dp_code)) {
                $in = "'" . implode("','", $dp_code) . "'";
                $condition = " AND `status` IN ($in) ";
            }
        }

    } 

    /* -------------------------------
     | MOBILE LOOP COUNT
     |-------------------------------*/
    if ($request->input('mobile_numbers') === 'all') {

        $res = DB::select("
            SELECT MAX(
                (CASE WHEN phonenumber IS NOT NULL AND phonenumber != '' THEN 1 ELSE 0 END) +
                (CASE WHEN phonenumber_2 IS NOT NULL AND phonenumber_2 != '' THEN 1 ELSE 0 END)
            ) AS num_elements
            FROM leadger_leads
            WHERE campaign_id = ?
            AND assigned_to = ?
            $condition
        ", [$campaignId, $user->id]);

        $loop = (int) ($res[0]->num_elements ?? 1);

    } else {
        $loop = 1;
    }

    /* -------------------------------
     | INSERT FLEXIDIAL DATA
     |-------------------------------*/
    DB::delete("
        DELETE FROM leadger_webrtc_flexidialer
        WHERE agent_id = ?
    ", [$user->id]);

if ($request->input('mobile_numbers') === 'primary') {

    DB::insert("
        INSERT INTO leadger_webrtc_flexidialer
        (lead_id, campaign_id, mobile_number, lead_status, agent_id, created_at, updated_at)
        SELECT
            id,
            campaign_id,
            phonenumber,
            status,
            assigned_to,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber IS NOT NULL
        AND phonenumber != ''
        $condition
    ", [$campaignId, $user->id]);

}
if ($request->mobile_numbers == 'all') {

    DB::insert("

        INSERT INTO leadger_webrtc_flexidialer
        (lead_id, campaign_id, mobile_number, lead_status, agent_id, created_at, updated_at)

        SELECT id, campaign_id, phonenumber, status, assigned_to, NOW(), NOW()
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber IS NOT NULL
        $condition

        UNION ALL

        SELECT id, campaign_id, phonenumber_2, status, assigned_to, NOW(), NOW()
        FROM leadger_leads
        WHERE campaign_id = ?
        AND assigned_to = ?
        AND phonenumber_2 IS NOT NULL
        $condition

    ", [$campaignId, $user->id, $campaignId, $user->id]);

}

    /* -------------------------------
     | UPDATE USER SETTINGS
     |-------------------------------*/
    $user = User::find($user->id);
    $user->webrtc_flexidial_call_rate      = $request->input('webrtc_flexidial_call_rate');
    $user->webrtc_flexidial_parallel_users = $request->input('webrtc_flexidial_parallel_users');
    $user->save();

    return redirect()->route('admin.callmanager.webrtc-flexidial')
        ->with('success', 'Successfully Created');
    }

    public function flexidialStatus(Request $request)
    {

        $user = auth()->user();
        $user = User::find($user->id);
        if($request->input('status') == 1)
        {
            $usedChannels = DB::select("SELECT COALESCE(SUM(flexidial_call_rate*flexidial_parallel_users),0) AS used FROM leadger_users WHERE flexidial_status = 1");
            //if($this->license->flexi_dialer_channels == null || $this->license->flexi_dialer_channels < $usedChannels[0]->used + ($user->flexidial_call_rate * $user->flexidial_parallel_users))
            //{
              //  return Reply::error("Channels not available");
            //}
        }
        $user->flexidial_status = $request->input('status');
        $user->save();
        return redirect()->action('Dashboard\FlexiDialerController@flexidialView')->with('success', 'Successfully Created');
    }
    public function webrtcflexidialStatus(Request $request)
    {
        $user = auth()->user();
        $user = User::find($user->id);
        if($request->input('status') == 1)
        {
            $usedChannels = DB::select("SELECT COALESCE(SUM(webrtc_flexidial_call_rate*webrtc_flexidial_parallel_users),0) AS used FROM leadger_users WHERE webrtc_flexidial_status = 1");
            //if($this->license->flexi_dialer_channels == null || $this->license->flexi_dialer_channels < $usedChannels[0]->used + ($user->flexidial_call_rate * $user->flexidial_parallel_users))
            //{
              //  return Reply::error("Channels not available");
            //}
        }
        $user->webrtc_flexidial_status = $request->input('status');
        $user->save();
         return redirect()->route('admin.callmanager.webrtc-flexidial')
                ->with('success', 'Successfully Created');
            

    }


    public function flexidialClear()
    {
        $user = auth()->user();
        DB::delete("delete from leadger_flexidialer where agent_id = '".$user->id."'");
        $user = User::find($user->id);
        $user->flexidial_call_rate = 0;
        $user->flexidial_parallel_users = 1;
        $user->save();
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('sucess', 'Cleared');    

    }
    public function webrtcflexidialClear()
    {
        $user = auth()->user();
        DB::delete("delete from leadger_webrtc_flexidialer where agent_id = '".$user->id."'");
        $user = User::find($user->id);
        $user->webrtc_flexidial_call_rate = 0;
        $user->webrtc_flexidial_parallel_users = 1;
        $user->save();
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('sucess', 'Cleared');  

    }


    public function refreshFlexidial()
    {
        $user = auth()->user();
        DB::Statement("SELECT leadger_flexidialer.id FROM leadger_flexidialer
INNER JOIN leadger_call_logs
ON leadger_flexidialer.call_log_id = leadger_call_logs.id
WHERE TIME_TO_SEC(CONCAT('00:',duration)) > 10
AND REPLACE(JSON_EXTRACT(click_response, \"$.A_DIAL_STATUS\"),'\"','') = 'Connected'
AND REPLACE(JSON_EXTRACT(click_response, \"$.B_DIAL_STATUS\"),'\"','') = 'Connected'
AND agent_id= '".$user->id."'");
        DB::Statement("UPDATE leadger_flexidialer SET dial_status=0,c2c_status=NULL,c2c_status=NULL,call_log_id=NULL,agent_number=NULL WHERE agent_id = '".$user->id."'");
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('sucess', 'refreshed');    
    }

    public function webrtcrefreshFlexidial()
    {
        $user = auth()->user();
        DB::Statement("SELECT leadger_webrtc_flexidialer.id FROM leadger_webrtc_flexidialer
INNER JOIN leadger_call_logs
ON leadger_webrtc_flexidialer.call_log_id = leadger_call_logs.id
WHERE TIME_TO_SEC(CONCAT('00:',duration)) > 10
AND REPLACE(JSON_EXTRACT(click_response, \"$.A_DIAL_STATUS\"),'\"','') = 'Connected'
AND REPLACE(JSON_EXTRACT(click_response, \"$.B_DIAL_STATUS\"),'\"','') = 'Connected'
AND agent_id= '".$user->id."'");
        DB::Statement("UPDATE leadger_flexidialer SET dial_status=0,c2c_status=NULL,c2c_status=NULL,call_log_id=NULL,agent_number=NULL WHERE agent_id = '".$user->id."'");
        return redirect()->action('Dashboard\FlexiDialerController@createFlexidial')->with('sucess', 'refreshed');    
    }

    public function flexidial_sse()
    {
        $user = auth()->user();
        
    
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () {
            // Set unlimited execution time to keep the connection open
            set_time_limit(0);
            $user = auth()->user();
            // Ensure that the output is not buffered by PHP
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // Set the headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            ob_start();
     
//            while (true) {
                // Fetch data from the database
                if(self::flexidial())
                {
                $running = DB::select("
                    SELECT leadger_flexidialer.*,c.name as campaign_name,leadger_flexidialer.call_log_id as call_log_hash,
                    leadger_leads.name as customer_name
                    FROM leadger_flexidialer
                    LEFT JOIN leadger_campaigns c ON leadger_flexidialer.campaign_id = c.id
                    left join leadger_leads
            on leadger_leads.id = leadger_flexidialer.lead_id and c.id = leadger_leads.campaign_id and leadger_flexidialer.campaign_id = leadger_leads.campaign_id
                    WHERE dial_status = 1
                      AND c2c_status IS NOT NULL
                      AND c2c_status != 'Call End'
                      and CURRENT_TIMESTAMP-leadger_flexidialer.updated_at <= ".env('flexidial_delay_sec', 120)."
                      AND (agent_id = ? or agent_number = ?)", [$user->id, $user->click_agent_number]);

                // Output the data in SSE format
                echo "data: " . json_encode($running) . "\n\n";
                }
                else
                {
                    echo "data: ". json_encode(array(array('lead_id'=>'Insufficient virtual numbers'))). "\n\n";
                }

                // Ensure data is sent immediately
                ob_flush();
                flush();

                // Sleep for a certain interval before sending the next update
                sleep(20); // Adjust the interval as needed
//            }
        });

        // Set headers for the response
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
public function syncConnectedCalls(Request $request)
{
    $calls    = $request->calls; // [{call_log_id, vmn, mobile_number}]
   
    $user     = auth()->user();

    $settings      = Common::fetchSettings();
    
    $full_response = json_decode($settings->full_response, true);


    $crm_id = $full_response['data']['crm_id'];
   
    /* ------------------------------------------
     | 1?? Build connection payload + track calls
     ------------------------------------------ */
    $connections      = [];
    $attemptedCalls   = []; // call_log_id => mobile_number
    $uniqueId='';
    foreach ($calls as $row) {

        if (empty($row['call_log_id'])) {
            continue;
        }

        $callLog = CallLog::find($row['call_log_id']);
        if (!$callLog) {
            continue;
        }
        $uniqueId=$callLog->webrtc_call_id;
        
        $lead = Lead::find($callLog->lead_id);
        if (!$lead || !$lead->campaign) {
            continue;
        }

        // ?? Group ID (campaign priority)
        $campaignGroupIds = array_filter(
            array_map('trim', explode(',', $lead->campaign->group_id))
        );

        $group_id = DB::table('group_user')
            ->where('user_id', $user->id)
            ->whereIn('group_id', $campaignGroupIds)
            ->orderByRaw("FIELD(group_id," . implode(',', $campaignGroupIds) . ")")
            ->value('group_id');

        // ?? Reference ID (exact same format)
        $referenceId =
            $lead->id . "_" .
            $callLog->id . "_" .
            $crm_id . "_" .
            $user->id . "_" .
            ($callLog->verified ?? 0) . "_" .
            ($lead->account_number ?? '') . "_" .
            $group_id . "_webrtc_" .
            $lead->campaign_id;

        $connections[] = [
            'mobile_number' => $callLog->called_mobile_number,
            'ref_id'        => $referenceId
        ];

        $attemptedCalls[$callLog->id] = $callLog->called_mobile_number;
    }

    if (empty($connections)) {
        return response()->json([
            'unique_id' => $uniqueId,
            'connected_numbers' => []
        ]);
    }

    /* ------------------------------------------
     | 2?? External API payload
     ------------------------------------------ */
    $payload = [
        'unique_id'   => $uniqueId,
        'connections' => $connections
    ];

    /* ------------------------------------------
     | 3?? Retry logic
     ------------------------------------------ */
    sleep(10); // allow provider to register calls

    $connectedNumbers    = [];
    $connectedCallLogIds = [];

    $maxRetries = 5;
    $sleepUs    = 5000000; // 0.5 sec

    for ($i = 1; $i <= $maxRetries; $i++) {

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'http://35.244.4.88/admin/webrtc_flexi_fetch',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT        => 5,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            usleep($sleepUs);
            continue;
        }

        $res = json_decode($response, true);

        if (($res['status'] ?? '') === 'success'
            && !empty($res['data']['customer_number'])) {

            $customerNumber = $res['data']['customer_number'];
            $connectedNumbers[] = $customerNumber;

            // ?? map customer_number ? call_log_id
            foreach ($attemptedCalls as $logId => $mobile) {
                if ($mobile == $customerNumber) {
                    $connectedCallLogIds[] = $logId;
                    break;
                }
            }

            break; // stop retry on first success
        }

        usleep($sleepUs);
    }

    /* ------------------------------------------
     | 4?? Insert NOT CONNECTED into cr_calllogs
     ------------------------------------------ */
    $notConnectedLogIds = array_diff(
        array_keys($attemptedCalls),
        $connectedCallLogIds
    );

    foreach ($notConnectedLogIds as $callLogId) {

        $callLog = CallLog::find($callLogId);
        if (!$callLog) {
            continue;
        }

        // ? avoid duplicates
        $exists = DB::table('cr_calllogs')
            ->where('call_log_id', $callLogId)
            ->where('call_type', 'webrtc_flexidial')
            ->exists();

        if ($exists) {
            continue;
        }

        DB::table('cr_calllogs')->insert([
            'call_log_id'     => $callLogId,
            'lead_id'         => $callLog->lead_id,
            'user_id'         => $user->id,
            'call_type'       => 'webrtc_flexidial',

            'A_DIAL_STATUS'   => 'ANSWERED',
            'B_DIAL_STATUS'   => 'NO ANSWER',
            'a_party_no'      => $user->smartping_user_id,
            'b_party_no'      => $callLog->called_mobile_number, 
            'dni'             =>$callLog->vmn_used,
            'duration'        => 0,
            'agent_duration'  => 0,
            'cust_duration'   => 0,

            'created_at'      => now(),
           
        ]);
    }

    /* ------------------------------------------
     | 5?? Response
     ------------------------------------------ */
    return response()->json([
        'unique_id' => $uniqueId,
        'connected_numbers' => $connectedNumbers
    ]);
}
    public function webrtc_flexidial_sse()
    { 
	
       $user = auth()->user();
        
    
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () {
            // Set unlimited execution time to keep the connection open
            set_time_limit(0);
            $user = auth()->user();
            // Ensure that the output is not buffered by PHP
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // Set the headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            ob_start();
            $status=self::webrtcflexidial();
//            while (true) {
                // Fetch data from the database
                if($status=="ok")
                {
                $running = DB::select("
                    SELECT leadger_webrtc_flexidialer.*,c.name as campaign_name,leadger_webrtc_flexidialer.call_log_id as call_log_hash,
                    leadger_leads.name as customer_name
                    FROM leadger_webrtc_flexidialer
                    LEFT JOIN leadger_campaigns c ON leadger_flexidialer.campaign_id = c.id
                    left join leadger_leads
            on leadger_leads.id = leadger_webrtc_flexidialer.lead_id and c.id = leadger_leads.campaign_id and leadger_webrtc_flexidialer.campaign_id = leadger_leads.campaign_id
                    WHERE dial_status = 1
                      AND c2c_status IS NOT NULL
                      AND c2c_status != 'Call End'
                      and CURRENT_TIMESTAMP-leadger_webrtc_flexidialer.updated_at <= ".env('flexidial_delay_sec', 120)."
                      AND (agent_id = ? or agent_number = ?)", [$user->id, $user->click_agent_number]);

                // Output the data in SSE format
                echo "data: " . json_encode($running) . "\n\n";
                }
                elseif($status=="vmn_insufficient")
                {
                    echo "data: ". json_encode(array(array('lead_id'=>'Insufficient virtual numbers'))). "\n\n";
                }elseif($status=="agent_busy"){
                                 $running = DB::select("
                    SELECT leadger_webrtc_flexidialer.*,c.name as campaign_name,leadger_webrtc_flexidialer.call_log_id as call_log_hash,
                    leadger_leads.name as customer_name
                    FROM leadger_webrtc_flexidialer
                    LEFT JOIN leadger_campaigns c ON leadger_flexidialer.campaign_id = c.id
                    left join leadger_leads
            on leadger_leads.id = leadger_webrtc_flexidialer.lead_id and c.id = leadger_leads.campaign_id and leadger_webrtc_flexidialer.campaign_id = leadger_leads.campaign_id
                    WHERE dial_status = 1
                      AND c2c_status IS NOT NULL
                      AND c2c_status != 'Call End'
                      and CURRENT_TIMESTAMP-leadger_webrtc_flexidialer.updated_at <= ".env('flexidial_delay_sec', 120)."
                      AND (agent_id = ? or agent_number = ?)", [$user->id, $user->click_agent_number]);

                // Output the data in SSE format
                echo "data: " . json_encode($running) . "\n\n";
                }

                // Ensure data is sent immediately
                ob_flush();
                flush();

                // Sleep for a certain interval before sending the next update
                sleep(20); // Adjust the interval as needed
//            }
        });

        // Set headers for the response
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    public function flexidial_options($id)
    {
        $user = auth()->user();

        // Lead status from statuses table
        $leadStatuses = DB::select("
            SELECT DISTINCT 
                COALESCE(s.name,'Blank') AS status
            FROM leadger_leads l
            LEFT JOIN leadger_statuses s 
                ON s.id = l.status
            WHERE l.campaign_id = ?
            AND l.assigned_to = ?
            ORDER BY status
        ", [$id, $user->id]);

        $interested = '';
        foreach ($leadStatuses as $row) {
            $interested .= "<option value='".$row->status."'>".$row->status."</option>";
        }

        // Click2Call status from JSON
        $callStatuses = DB::select("
            SELECT DISTINCT 
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.click_response, '$.B_DIAL_STATUS')), 'Blank') AS status
            FROM leadger_leads l
            INNER JOIN leadger_call_logs c 
                ON c.lead_id = l.id
            WHERE l.campaign_id = ?
            AND c.created_by = ?
            ORDER BY status
        ", [$id, $user->id]);

        $click2call = '';
        foreach ($callStatuses as $row) {
            $click2call .= "<option value='".$row->status."'>".$row->status."</option>";
        }

        return response()->json([
            'status' => 'success',
            'statushtml' => $interested,
            'click2callhtml' => $click2call
        ]);
    }
    public function webrtcflexidial_options($id)
    {
        $user = auth()->user();

        // Lead status from statuses table
        $leadStatuses = DB::select("
            SELECT DISTINCT 
                COALESCE(s.name,'Blank') AS status
            FROM leadger_leads l
            LEFT JOIN leadger_statuses s 
                ON s.id = l.status
            WHERE l.campaign_id = ?
            AND l.assigned_to = ?
            ORDER BY status
        ", [$id, $user->id]);

        $interested = '';
        foreach ($leadStatuses as $row) {
            $interested .= "<option value='".$row->status."'>".$row->status."</option>";
        }

        // Click2Call status from JSON
        $callStatuses = DB::select("
            SELECT DISTINCT 
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.click_response, '$.B_DIAL_STATUS')), 'Blank') AS status
            FROM leadger_leads l
            INNER JOIN leadger_call_logs c 
                ON c.lead_id = l.id
            WHERE l.campaign_id = ?
            AND c.created_by = ?
            ORDER BY status
        ", [$id, $user->id]);

        $click2call = '';
        foreach ($callStatuses as $row) {
            $click2call .= "<option value='".$row->status."'>".$row->status."</option>";
        }

        return response()->json([
            'status' => 'success',
            'statushtml' => $interested,
            'click2callhtml' => $click2call
        ]);
    }


    public function activeFlexidial(Request $request)
    {
        $employeeList = Common::getEmployees($this->user);
        $employeeIdString = implode("','",$employeeList);

        $limit = '';
        $pagelimit = $request->input('length');
        $pagination = (isset($pagelimit)) ? $request->input('length') : 0;
        if ($pagination) {
            $limit = $request->input('length');
            $start = $request->input('start');
            $limit = "LIMIT " . $start . ", " . $limit;
        }

        $sql = "FROM users
            left join (select agent_id,count(*) as count from flexidialer where dial_status = 0 group by agent_id) a
            on users.id = a.agent_id
                where users.id in ('".$employeeIdString."') and flexidial_status = 1 ";
        $totalSql = "select count(*) as total ".$sql;
        $totalRes = DB::select(DB::raw($totalSql));
        $filteredCount = $totalCount = intval($totalRes[0]->total);
        $sql = "SELECT concat(coalesce(first_name,''),' ',coalesce(last_name,'')) as name,coalesce(count,0) as pending_calls,flexidial_call_rate * flexidial_parallel_users as channels_used "
                .$sql.$limit;
        $res = DB::select($sql);

        $output = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalCount,
            "recordsFiltered" => $filteredCount,
            "data" => $res
        );
        echo json_encode($output);
    }
    public function webrtcactiveFlexidial(Request $request)
    {
        $employeeList = Common::getEmployees($this->user);
        $employeeIdString = implode("','",$employeeList);

        $limit = '';
        $pagelimit = $request->input('length');
        $pagination = (isset($pagelimit)) ? $request->input('length') : 0;
        if ($pagination) {
            $limit = $request->input('length');
            $start = $request->input('start');
            $limit = "LIMIT " . $start . ", " . $limit;
        }

        $sql = "FROM users
            left join (select agent_id,count(*) as count from webrtc_flexidialer where dial_status = 0 group by agent_id) a
            on users.id = a.agent_id
                where users.id in ('".$employeeIdString."') and webrtc_flexidial_status = 1 ";
        $totalSql = "select count(*) as total ".$sql;
        $totalRes = DB::select(DB::raw($totalSql));
        $filteredCount = $totalCount = intval($totalRes[0]->total);
        $sql = "SELECT concat(coalesce(first_name,''),' ',coalesce(last_name,'')) as name,coalesce(count,0) as pending_calls,webrtc_flexidial_call_rate * webrtc_flexidial_parallel_users as channels_used "
                .$sql.$limit;
        $res = DB::select($sql);

        $output = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalCount,
            "recordsFiltered" => $filteredCount,
            "data" => $res
        );
        echo json_encode($output);
    }



}