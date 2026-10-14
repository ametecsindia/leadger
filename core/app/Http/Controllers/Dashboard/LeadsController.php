<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\AllocationHistory;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\ImageUpload;
use App\Models\Lead;
use App\Models\Review;
use Carbon\Carbon;
use App\Models\Status;
use App\Models\Substatus;
use App\Models\TrackingHistory;
use App\Models\ProductService;
use App\Models\ClientType;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use App\Models\Click2CallLog;
use App\Http\Controllers\Dashboard\DialerController;
use Illuminate\Support\Facades\Http;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class LeadsController extends Controller
{

    public function __construct()
    {
        $current_url = \Illuminate\Support\Facades\Request::url();
        if(in_array($current_url, [route('recordingsUpload'), route('mobileCallRecordingUpload'), route('sipEndCall')]))
        {

        }
        else
        {
                $this->middleware('auth');
        }
    }

    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        $id = '';

        $Campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->get();
        $Users = User::orderBy('name')->get();
        $totalLeads = \App\Models\Lead::count();
        $activeCampaignsCount = \App\Models\Campaign::where('status', 1)->count();
        $pendingFollowupsCount = \App\Models\Lead::openFollowups()->count();

        return view("dashboard.campaigns.leads", compact("GeneralWebmasterSections", "id", "Campaigns", "Users", "totalLeads", "activeCampaignsCount", "pendingFollowupsCount"));
    }

    /**
     * Download the standard sample CSV file for leads upload.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadSampleFile()
    {
        return Helper::downloadSampleLeadCsv('sample_leads.csv');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($CampaignId = '')
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $ProductServices = ProductService::orderBy('name', 'asc')->get();
        $Campaigns = DB::select("select * from ".env('DB_TABLE_PREFIX')."campaigns where expiry_date >= current_date order by id desc");
        $sql = "select l.id,l.name,c.name as campaign_name from ".env('DB_TABLE_PREFIX')."leads l 
                LEFT JOIN ".env('DB_TABLE_PREFIX')."campaigns c
                on l.campaign_id = c.id
                where l.status in (select id from ".env('DB_TABLE_PREFIX')."statuses where name = 'Deal') ";
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
        $Deals = DB::select($sql);

        if(!empty($CampaignId))
        {
            $AssignedTo = DB::select("SELECT * FROM leadger_users
WHERE team_id IN 
(SELECT team_id FROM leadger_campaigns WHERE id = $CampaignId)
AND permissions_id IN (SELECT id FROM leadger_permissions WHERE NAME != 'Sales Person')");
            $SalesPersons = DB::select("SELECT * FROM leadger_users
WHERE team_id IN 
(SELECT team_id FROM leadger_campaigns WHERE id = $CampaignId)
AND permissions_id IN (SELECT id FROM leadger_permissions WHERE NAME = 'Sales Person')");
        }
        else
        {
            $AssignedTo = $SalesPersons = [];
        }
        $User = Auth::user();

        return view("dashboard.leads.create", compact("GeneralWebmasterSections", "ProductServices", "Campaigns", "CampaignId", 
                "Deals", "AssignedTo", "SalesPersons", "User"));
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
            'phonenumber' => 'required|min:10',
            'campaign_id' => 'required',
            'assigned_to' => 'required'
        ]);
        
        $last = DB::select("select max(id) as last from ".env('DB_TABLE_PREFIX')."leads where campaign_id = '".$request->campaign_id."'");

        $Lead = new Lead;
        $Lead->id = $last[0]->last > 0 ? $last[0]->last + 1 : $request->campaign_id.sprintf("%06d", $last[0]->last + 1);
        $Lead->campaign_id = $request->campaign_id;
        $Lead->name = $request->name;
        $Lead->designation = $request->designation;
        $Lead->phonenumber = $request->phonenumber;
        $Lead->phonenumber_2 = $request->phonenumber_2;
        $Lead->company = $request->company;
        $Lead->description = $request->description;
        $Lead->country = $request->country;
        $Lead->pin_code = $request->pin_code;
        $Lead->city = $request->city;
        $Lead->state = $request->state;
        $Lead->address = $request->address;
        $Lead->email = $request->email;
        $Lead->website = $request->website;
        $Lead->position = $request->position;
        $Lead->latitude = $request->latitude;
        $Lead->longitude = $request->longitude;
        $Lead->product_service = !empty($request->product_service) ? implode(',',$request->product_service) : '';
        $Lead->lead_value = $request->lead_value;
        $Lead->created_by = Auth::user()->id;
        $Lead->is_public = empty($request->is_public) ? 0 : 1;
        $Lead->sales_person_id = $request->sales_person;
        $Lead->assigned_to = $request->assigned_to;
        $Lead->save();

        $user = \App\Models\User::find(Auth::user()->id);
        $user->default_lead_campaign = ($request->default_lead_campaign == '1' && $request->campaign_id) ? $request->campaign_id : 0;
        $user->save();

        return redirect()->route('campaignsView', ['id' => $request->campaign_id])->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Import Leads from CSV/Excel file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function importLeads(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file|max:20480',
            'campaign_id' => 'required',
        ]);

        $campaignId = $request->campaign_id;
        $file = $request->file('file');
        
        $importedCount = 0;
        $filePath = $file->getRealPath();

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 2000, ',');
            
            $headerMap = [];
            if ($header) {
                foreach ($header as $index => $colName) {
                    $cleanCol = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['-', ' '], '_', $colName))));
                    $headerMap[$cleanCol] = $index;
                }
            }

            while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
                if (empty($data) || (count($data) == 1 && empty($data[0]))) {
                    continue;
                }

                $getValue = function($keys, $defaultIndex = null) use ($headerMap, $data) {
                    foreach ((array)$keys as $k) {
                        if (isset($headerMap[$k]) && isset($data[$headerMap[$k]])) {
                            return trim($data[$headerMap[$k]]);
                        }
                    }
                    if ($defaultIndex !== null && isset($data[$defaultIndex])) {
                        return trim($data[$defaultIndex]);
                    }
                    return '';
                };

                $name        = $getValue(['name'], 0);
                $designation = $getValue(['designation'], 1);
                $phone       = $getValue(['phone_number', 'phonenumber', 'phone', 'mobile'], 2);
                $phone2      = $getValue(['phone_number2', 'phonenumber2', 'phonenumber_2', 'phone2', 'mobile2'], 3);
                $company     = $getValue(['company'], 4);
                $description = $getValue(['description'], 5);
                $country     = $getValue(['country'], 6);
                $pinCode     = $getValue(['pin_code', 'pincode', 'pin'], 7);
                $city        = $getValue(['city'], 8);
                $state       = $getValue(['state'], 9);
                $address     = $getValue(['address'], 10);
                $email       = $getValue(['email'], 11);
                $website     = $getValue(['website'], 12);
                $leadValue   = $getValue(['lead_value', 'leadvalue', 'value'], 13);
                $position    = $getValue(['position'], 14);
                $tags        = $getValue(['tags'], 15);
                $userId      = $getValue(['user_id', 'userid', 'assigned_to'], 16);

                if (strtolower($name) == 'name' || strtolower($phone) == 'phone-number' || strtolower($phone) == 'phonenumber') {
                    continue;
                }

                if (empty($name) && empty($phone)) {
                    continue;
                }

                $last = DB::select("select max(id) as last from ".env('DB_TABLE_PREFIX')."leads where campaign_id = '".$campaignId."'");
                $nextId = ($last && isset($last[0]->last) && $last[0]->last > 0) ? $last[0]->last + 1 : (int)($campaignId.sprintf("%06d", 1));

                $Lead = new Lead;
                $Lead->id = $nextId;
                $Lead->campaign_id = $campaignId;
                $Lead->name = !empty($name) ? $name : 'Lead '.($importedCount+1);
                $Lead->designation = $designation;
                $Lead->phonenumber = $phone;
                $Lead->phonenumber_2 = $phone2;
                $Lead->company = $company;
                $Lead->description = $description;
                $Lead->country = $country;
                $Lead->pin_code = $pinCode;
                $Lead->city = $city;
                $Lead->state = $state;
                $Lead->address = $address;
                $Lead->email = $email;
                $Lead->website = $website;
                $Lead->lead_value = $leadValue;
                $Lead->position = $position;
                $Lead->tags = $tags;
                if (!empty($userId)) {
                    $Lead->assigned_to = $userId;
                }
                $Lead->created_by = Auth::user()->id ?? 1;
                $Lead->status = 1;
                $Lead->save();

                $importedCount++;
            }
            fclose($handle);
        }

        return redirect()->route('leads')->with('doneMessage', "Successfully uploaded and imported {$importedCount} leads!");
    }

    /**
     * View Campaign Leads.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Record-level access to one lead - the same visibility rule the lead pages already used:
     *  Telecaller:   leads assigned to them, public leads, or leads of their teams' campaigns
     *  Sales Person: leads where they are the sales person, or public leads
     *  Manager / Team Leader: leads of their teams' campaigns
     *  other roles (Admin, Webmaster, ...): all leads
     * Returns a 403 response (or aborts) when denied, null when allowed.
     */
    private function leadAccessDenied(Lead $Lead)
    {
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';
        $Campaign = Campaign::find($Lead->campaign_id);
        $userTeamIds = explode(',', $user->team_id ?? '');

        $allowed = true;
        if ($userRole === 'Telecaller') {
            $allowed = $Lead->assigned_to == $user->id || $Lead->is_public == 1
                || ($Campaign && in_array($Campaign->team_id, $userTeamIds));
        } elseif ($userRole === 'Sales Person') {
            $allowed = $Lead->sales_person_id == $user->id || $Lead->is_public == 1;
        } elseif (in_array($userRole, ['Manager', 'Team Leader'])) {
            $allowed = empty($user->team_id) || !$Campaign || in_array($Campaign->team_id, $userTeamIds);
        }
        if ($allowed) {
            return null;
        }
        if (request()->ajax() || request()->wantsJson()) {
            return response('<div class="p-4 text-center text-danger font-weight-bold"><i class="fa fa-lock fa-2x mb-2 d-block"></i>You are not authorized to view this lead.</div>', 403);
        }
        abort(403, 'Unauthorized access to this lead.');
    }

    public function view($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $c2cChannels = count(DB::Select("select * FROM ".env('DB_TABLE_PREFIX')."users
where c2c_channel = 1"));
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $licenseC2cChannels = 0;
        $full_reponse = json_decode($settings->full_response ?? '', true);
        // no active licence (full_response empty): no Click2Call limit to read - the lead must still open
        if(is_array($full_reponse['data'] ?? null) && array_key_exists('c2c_channels',$full_reponse['data']))
        {
            $licenseC2cChannels = $full_reponse['data']['c2c_channels'];
        }
        // (Removed: viewing a lead used to switch Click2Call OFF for every user whenever the enabled count
        //  was above the licence - or the licence had no Click2Call limit at all. The limit is enforced
        //  when Click2Call is enabled for a user: User::setClick2Call.)

        // Lead IDs are the leads primary key, digits only ("12abc" must not resolve to lead 12)
        $Lead = ctype_digit((string)$id) ? Lead::find($id) : null;
        if (empty($Lead)) {
            if (request()->ajax()) {
                return response('<div class="p-4 text-center text-danger font-weight-bold">Lead ' . htmlspecialchars($id) . ' not found.</div>', 404);
            }
            return redirect()->action('Dashboard\CampaignsController@index');
        }

        $Campaign = Campaign::find($Lead->campaign_id);
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';

        if ($denied = $this->leadAccessDenied($Lead)) {
            return $denied;
        }

        $Statuses = Status::orderBy("seq_order","asc")->get();
        $SubStatuses = !empty($Lead->status) ? Substatus::where('status_id',$Lead->status)->orderBy("seq_order","asc")->get() : [];
        $teamCondition = ($Campaign && !empty($Campaign->team_id)) ? "FIND_IN_SET(".$Campaign->team_id.", u.team_id)" : "1=1";
        $Staff = DB::select("SELECT u.* FROM ".env('DB_TABLE_PREFIX')."users u
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions p
ON u.`permissions_id` = p.id AND p.name != 'Sales Person'
WHERE {$teamCondition} ORDER BY u.name");
        
        $SalesPersons = DB::select("SELECT u.* FROM ".env('DB_TABLE_PREFIX')."users u
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions p
ON u.`permissions_id` = p.id AND p.name = 'Sales Person'
WHERE {$teamCondition} ORDER BY u.name");
        
        $ProductServices = ProductService::orderBy("seq_order", "asc")->get();
        $clientTypes = ClientType::orderBy("id", "asc")->get();
        $CallLogs = CallLog::select(['call_logs.*','statuses.name as status_name','users.name as called_by'])
                ->leftJoin('users','users.id','call_logs.created_by')
                ->leftJoin('statuses', 'statuses.id', 'call_logs.next_status')
                ->where('lead_id', $id)
                ->orderBy('call_logs.created_at', 'desc')->get();
        $ImageUploads = ImageUpload::select(["image_uploads.*", "users.name as added_by"])
                ->join('users','users.id','image_uploads.created_by')
                ->where('lead_id', $id)->orderBy('created_at','desc')->get();
        $VisitHistory = TrackingHistory::select(['tracking_history.*','users.name as added_by'])
                ->join('users','users.id','tracking_history.user_id')
                ->where('lead_id', $id)->orderBy('created_at','desc')->get();
        $Reviews = Review::select(['reviews.*','users.name as added_by'])
                ->join('users','users.id','reviews.user_id')
                ->where('lead_id', $id)->orderBy('created_at','desc')->get();
        $AuditReports = DB::select("
SELECT leadger_audit_reports.*, leadger_users.name AS audited
FROM leadger_audit_reports
INNER JOIN leadger_users
ON audited_by = leadger_users.id
WHERE lead_id = ".$id."
ORDER BY leadger_audit_reports.created_at DESC
");

        // Assigned agent
        $assignedAgentName = 'Unassigned';
        if (!empty($Lead->assigned_to)) {
            $ag = \App\Models\User::find($Lead->assigned_to);
            if ($ag) {
                $assignedAgentName = $ag->name;
            }
        }

        // Product name
        $productName = 'Home Loan';
        if (!empty($Lead->product_service)) {
            $prod = $ProductServices->where('id', $Lead->product_service)->first();
            if ($prod && !empty($prod->name)) {
                $productName = $prod->name;
            }
        } elseif (!empty($Campaign->segment_name)) {
            $productName = $Campaign->segment_name;
        } elseif (!empty($Campaign->name)) {
            $productName = $Campaign->name;
        }

        // Current status name & badge class
        $currentStatusName = 'Connected';
        if (!empty($Lead->status)) {
            $st = $Statuses->where('id', $Lead->status)->first();
            if ($st && !empty($st->name)) {
                $currentStatusName = $st->name;
            }
        }
        $sLower = strtolower($currentStatusName);
        $statusBadgeClass = 'ok';
        if (strpos($sLower, 'follow') !== false || strpos($sLower, 'pending') !== false || strpos($sLower, 'hold') !== false) {
            $statusBadgeClass = 'warn';
        } elseif (strpos($sLower, 'new') !== false || strpos($sLower, 'unactioned') !== false || strpos($sLower, 'ringing') !== false) {
            $statusBadgeClass = 'live';
        } elseif (strpos($sLower, 'dnd') !== false || strpos($sLower, 'not interested') !== false || strpos($sLower, 'reject') !== false || strpos($sLower, 'junk') !== false || strpos($sLower, 'lost') !== false) {
            $statusBadgeClass = 'bad';
        }

        // Disposition name
        $dispositionName = $currentStatusName;
        if ($CallLogs->isNotEmpty() && !empty($CallLogs->first()->status_name)) {
            $dispositionName = $CallLogs->first()->status_name;
        }

        // Sub-disposition name
        $subDispositionName = '—';
        if (!empty($Lead->substatus)) {
            $subst = Substatus::find($Lead->substatus);
            if ($subst && !empty($subst->name)) {
                $subDispositionName = $subst->name;
            }
        } elseif ($CallLogs->isNotEmpty() && !empty($CallLogs->first()->remarks)) {
            $subDispositionName = $CallLogs->first()->remarks;
        }

        // Last call text
        $lastCallText = '—';
        $lastDate = $Lead->last_actioned_date ?: ($CallLogs->isNotEmpty() ? $CallLogs->first()->created_at : null);
        if ($lastDate) {
            $ts = strtotime($lastDate);
            if (date('Y-m-d') === date('Y-m-d', $ts)) {
                $lastCallText = 'Today, ' . date('h:i A', $ts);
            } elseif (date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', $ts)) {
                $lastCallText = 'Yesterday, ' . date('h:i A', $ts);
            } else {
                $lastCallText = date('d-M-Y, h:i A', $ts);
            }
        }

        // Next follow-up
        $nextFollowupText = '—';
        if (!empty($Lead->follow_up_on)) {
            $nextFollowupText = date('d-M-Y, h:i A', strtotime($Lead->follow_up_on));
        }

        // Total talk time
        $totalSeconds = 0;
        foreach ($CallLogs as $cl) {
            if (is_numeric($cl->duration)) {
                $totalSeconds += (int)$cl->duration;
            }
        }
        $totalTalkTimeText = sprintf('%02d:%02d', floor($totalSeconds / 60), $totalSeconds % 60);

        // First name for button
        $firstName = 'Lead';
        if (!empty($Lead->name)) {
            $parts = explode(' ', trim($Lead->name));
            $firstName = $parts[0];
        }

        $User = Auth::user();
        if($User->permissionsGroup->name == 'Admin'){
            $vmnList = \App\Models\ObdDID::where('click2call','1')->get();
            $wvmnList= \App\Models\ObdDID::where('webrtc','1')->get();
        }
        else
        {
            $on = [];
            foreach(explode(',',$User->team_id) as $row)
            {
                $on[] = " find_in_set(".$row.", leadger_obd_did.team_id) ";
            }
            $vmnList = DB::select("SELECT DISTINCT virtual_number FROM leadger_users 
INNER JOIN leadger_obd_did
ON (".(!empty($on) ? implode(" OR ",$on) : '1=2').")
WHERE leadger_users.id = ".$User->id." and click2call = 1");
            $wvmnList = DB::select("SELECT DISTINCT virtual_number FROM leadger_users 
INNER JOIN leadger_obd_did
ON (".(!empty($on) ? implode(" OR ",$on) : '1=2').")
WHERE leadger_users.id = ".$User->id." and webrtc = 1");
        }
        $Credits = DB::select("SELECT FROM_BASE64(AES_DECRYPT(obd_credits, 'MyNicotex@1')) as obd_credits FROM leadger_users where id = ".Auth::user()->id);

        if (!empty($Lead)) {
            $viewName = request()->ajax() ? "dashboard.leads.modal" : "dashboard.leads.view";
            return view($viewName, compact(
                "User", "Lead", "Staff", "SalesPersons", "Campaign", "Statuses", 
                "SubStatuses", "GeneralWebmasterSections", "ProductServices", "CallLogs", "ImageUploads", "VisitHistory", 
                "Reviews", "vmnList","wvmnList", "Credits", "AuditReports","clientTypes",
                "assignedAgentName", "productName", "currentStatusName", "statusBadgeClass",
                "dispositionName", "subDispositionName", "lastCallText", "nextFollowupText",
                "totalTalkTimeText", "firstName"
            ));
        } else {
            if (request()->ajax()) {
                return response('<div class="p-4 text-center text-danger font-weight-bold">Lead not found.</div>', 404);
            }
            return redirect()->action('Dashboard\CampaignsController@index');
        }
    }

    /**
     * Schedule a follow-up via AJAX from modal.
     */
    public function scheduleFollowup(Request $request, $id)
    {
        $this->validate($request, [
            'follow_up_on' => 'required',
        ]);

        $Lead = Lead::findOrFail($id);
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';

        if ($userRole === 'Telecaller' && $Lead->assigned_to != $user->id && $Lead->is_public != 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $formattedDate = Carbon::parse($request->follow_up_on)->format('Y-m-d H:i:s');
        $Lead->follow_up_on = $formattedDate;

        if ($request->has('remarks') && !empty($request->remarks)) {
            $Lead->remarks = $request->remarks;
        }

        // Auto update status to Follow-up if exists
        $followupStatus = Status::where('name', 'like', '%follow%')->first();
        if ($followupStatus) {
            $Lead->status = $followupStatus->id;
        }

        $Lead->save();

        return response()->json([
            'success' => true,
            'message' => 'Follow-up scheduled successfully for ' . date('d-M-Y, h:i A', strtotime($formattedDate)),
            'follow_up_on' => date('d-M-Y, h:i A', strtotime($formattedDate)),
            'raw_follow_up_on' => $formattedDate,
            'remarks' => $Lead->remarks
        ]);
    }

    /**
     * Reassign lead via AJAX from modal.
     */
    public function reassignLead(Request $request, $id)
    {
        $this->validate($request, [
            'assigned_to' => 'required',
        ]);

        $Lead = Lead::findOrFail($id);
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';

        if (in_array($userRole, ['Telecaller', 'Sales Person'])) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to reassign leads.'], 403);
        }

        $oldAgentId = $Lead->assigned_to;
        $Lead->assigned_to = $request->assigned_to;
        $Lead->save();

        // Record in allocation history
        $allocationHistory = new AllocationHistory;
        $allocationHistory->lead_id = $Lead->id;
        $allocationHistory->user_type = 'Staff';
        $allocationHistory->from_user = $oldAgentId;
        $allocationHistory->to_user = $request->assigned_to;
        $allocationHistory->allocation_type = 'Lead Reassign';
        $allocationHistory->created_by = $user->id;
        $allocationHistory->save();

        $newAgent = User::find($request->assigned_to);
        $agentName = $newAgent ? $newAgent->name : 'Assigned Agent';

        return response()->json([
            'success' => true,
            'message' => 'Lead successfully reassigned to ' . $agentName,
            'agent_name' => $agentName,
            'assigned_to' => $Lead->assigned_to
        ]);
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
        if($request->call_type == 'click2call')
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
        $CallLog->campaign_id = $Lead ? $Lead->campaign_id : null;
        $CallLog->call_source = 'campaign';
        $CallLog->call_state = 'initiated';
        $CallLog->start_time = Carbon::now();
        $CallLog->current_status = $Lead ? $Lead->status : null;
        $CallLog->duration = 0;
        
        $CallLog->verified = 0;
        if($request->call_type == 'click2call')
        {
            $did = DB::select("select * from leadger_obd_did where virtual_number = '".$request->vmn."'");
            $CallLog->vmn_used = $request->vmn;
            $CallLog->verified = $did[0]->tc_verified??'1';
        }

        $CallLog->created_by = Auth::user()->id;
        $CallLog->created_at = Carbon::now();
        $CallLog->save();
        $referenceId = $request->lead_id.'_M'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified;
        $CallLog->reference_id = $referenceId;
        $CallLog->provider_call_id = $referenceId;
        $CallLog->save();

        if($request->call_type == 'click2call')
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
                "a_party_no" => Auth::user()->click_agent_number,
                "b_party_no" => $request->phonenumber,
                "ref_id" => $request->lead_id.'_M'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified,
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

        echo json_encode(['call_log_id' => $CallLog->id, 'success' => true]);
    }
    
    public function leadAction(Request $request)
    {
        $this->validate($request, [
            'lead_id' => 'required',
            'phonenumber' => 'required',
            'call_type' => 'required',
            'call_log_id' => 'required',
            'status' => 'required'
        ]);
        
        $Lead = Lead::find($request->lead_id);
        
        if($Lead->assigned_to != $request->assign_to)
        {
            $allocationHistory = new AllocationHistory;
            $allocationHistory->lead_id = $request->lead_id;
            $allocationHistory->user_type = 'Staff';
            $allocationHistory->from_user = $Lead->assigned_to;
            $allocationHistory->to_user = $request->assign_to;
            $allocationHistory->allocation_type = 'Lead Save';
            $allocationHistory->created_by = Auth::user()->id;
            $allocationHistory->save();
        }
        
        if($Lead->sales_person_id != $request->sales_person)
        {
            $allocationHistory = new AllocationHistory;
            $allocationHistory->lead_id = $request->lead_id;
            $allocationHistory->user_type = 'Sales Person';
            $allocationHistory->from_user = $Lead->sales_person_id;
            $allocationHistory->to_user = $request->sales_person;
            $allocationHistory->allocation_type = 'Lead Save';
            $allocationHistory->created_by = Auth::user()->id;
            $allocationHistory->save();
        }
        
        $Lead->name = $request->name;
        $Lead->designation = $request->designation;
        $Lead->phonenumber = $request->phonenumber;
        $Lead->phonenumber_2 = $request->phonenumber_2;
        $Lead->company = $request->company;
        $Lead->description = $request->description;
        $Lead->country = $request->country;
        $Lead->pin_code = $request->pin_code;
        $Lead->city = $request->city;
        $Lead->state = $request->state;
        $Lead->address = $request->address;
        $Lead->email = $request->email;
        $Lead->website = $request->website;
        $Lead->latitude = $request->latitude;
        $Lead->longitude = $request->longitude;
        $Lead->lead_value = $request->lead_value;
        
        if($Lead->assigned_to != $request->assign_to) {
          $Lead->assigned_to = $request->assign_to;  
        } else if(empty($Lead->status) && $request->assign_to != Auth::user()->id && Auth::user()->permissionsGroup->name != 'Sales Person') {
            $Lead->assigned_to = $request->assign_to;
        }

        $Lead->follow_up_on = date('Y-m-d H:i',strtotime($request->call_back));
        $Lead->last_actioned_by = Auth::user()->id;
        $Lead->last_actioned_date = date('Y-m-d H:i:s');
        $Lead->status = $request->status;
        $Lead->substatus = $request->substatus;
        $Lead->sales_person_id = $request->sales_person;
        $Lead->remarks = $request->remarks;
        $Lead->reminder = $request->reminder;
        $Lead->product_service = !empty($request->product_service) ? implode(',',$request->product_service) : '';
        $Lead->clientType = !empty($request->clientType) ? implode(',',$request->clientType) : '';
        $Lead->save();
        
        $CallLog = CallLog::find($request->call_log_id);
        if ($CallLog) {
            // Talk time comes from the call (dialer state / provider call-end data), not from
            // start -> now, which would include ringing and the time spent filling this form.
            if (empty($CallLog->end_time)) {
                $CallLog->end_time = Carbon::now();
            }
            $CallLog->call_source = 'campaign';
            $CallLog->call_state = 'completed';
            $CallLog->next_status = $request->status;
            if (!empty($request->substatus) && is_numeric($request->substatus)) {
                $CallLog->substatus_id = (int)$request->substatus;
            }
            $CallLog->remarks = $request->remarks;
            $CallLog->follow_up_on = !empty($request->call_back) ? date('Y-m-d H:i',strtotime($request->call_back)) : null;
            $CallLog->reminder = $request->reminder;
            $CallLog->save();
        }
		//$this->updateAllClick2CallResponses();
        echo json_encode(['success' => true]);
    }
      
    public function getSubStatus(Request $request)
    {
        $SubStatuses = Substatus::where('status_id',$request->status)->get();
        $Lead = Lead::find($request->lead_id);
        $html = "<option value=''>-- Select SubStatus --</option>";
        foreach($SubStatuses as $SubStatus)
        {
            $html .= "<option value='".$SubStatus->id."'>".$SubStatus->name."</option>";
        }
        echo $html;
    }

    public function downloadFile()
    {
        if(isset($_FILES['upload']['name']))
        {
            $myfile = fopen(public_path() . DIRECTORY_SEPARATOR . date('Y-m-d') .".txt", "a");

            $txt  = json_encode( ['data' => $_FILES['upload'],'Date-Time'=>date('Y-m-d H:i:s')])."\n";

            fwrite($myfile, $txt);
            fclose($myfile);
            $path = env('ftp_upload_path')."\\".$_FILES['upload']['name'];
            echo move_uploaded_file($_FILES['upload']['tmp_name'],$path)?"OK":"ERROR";

            return json_encode(true);
        }
        else
        {
            echo "File not found";
        }
    }
    
    public function callImageUpload(Request $request)
    {
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $fileinfo = pathinfo($_FILES['image']['name']);

            $extension = $fileinfo['extension'];
            $filename = $fileinfo['filename'];

            $strFile = env("call_image_upload");

            if (!is_dir($strFile)) {
                mkdir($strFile, 0777, true);
            }

            $_FILES['file']['name'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)) . "." . $extension;

            $strFile = $strFile . DIRECTORY_SEPARATOR . $_FILES['file']['name'];

            $objImageUploads = new ImageUpload;
            $objImageUploads->file_name = $_FILES['file']['name'];
            $objImageUploads->file_path = $strFile;
            $objImageUploads->file_extension = $fileinfo['extension'];
            $objImageUploads->created_at = date('Y-m-d H:i:s');
            $objImageUploads->created_by = Auth::user()->id;
            $objImageUploads->lead_id = $request->lead_id;
//            $objImageUploads->image_type = $_POST['image_type'];
            $objImageUploads->lat = $request->lat;
            $objImageUploads->long = $request->long;
            $objImageUploads->address = Helper::getAddress($request->lat, $request->long);
            $objImageUploads->save();
            
            $d = $this->compress($_FILES['image']['tmp_name'],'assets/uploads/call_images/'.DIRECTORY_SEPARATOR.$_FILES['file']['name'],90);

            if ($d && copy($d, $strFile)) {
                echo '{"status":"success"}';
                exit;
            }
        }
    }
    
    public function compress($source, $destination, $quality = 90) {

        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg') 
            $image = imagecreatefromjpeg($source);

        elseif ($info['mime'] == 'image/gif') 
            $image = imagecreatefromgif($source);

        elseif ($info['mime'] == 'image/png') 
            $image = imagecreatefrompng($source);

        imagejpeg($image, $destination, $quality);

        return $destination;
    }
    public function mobileCallRecordingUpload(Request $request = null)
    {
        if (isset($_FILES['upload']) && $_FILES['upload']['error'] == 0) {
            $fileinfo = pathinfo($_FILES['upload']['name']);
            $extension = $fileinfo['extension'];

            if ($extension != 'wav') {
                echo 'ERROR1';
                return json_encode(true);
            }
            $filename = $fileinfo['filename'];

            $strFile = env("recordings_upload");

            if (!is_dir($strFile)) {
                mkdir($strFile, 0777, true);
                // return Reply::error('Directory is not created: '.$strFile);
            }

            $_FILES['file']['name'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)) . "." . $extension;

            $strFile = $strFile . DIRECTORY_SEPARATOR . $_FILES['file']['name'];

            if ($extension == "wav") {
                $fileNameArr = explode("_", $fileinfo['filename']);
                $moilenumber = $fileNameArr[1];
                $moilenumber = preg_replace('/[^0-9]/', '', $moilenumber);
                $interested = $fileNameArr[3];
                $userId = $fileNameArr[4];

                if ($moilenumber != "") {
                    $mobile = PhoneNumber::parse($moilenumber, 'IN')->getNationalNumber();
                    $CallLog = CallLog::where('phonenumber', $mobile)->orderBy('id', 'desc')->first();
                    //if mobile number not found in call log, we are not inserting in callrecordings
                    if (!is_null($CallLog)) {
                        
                        $CallLog->recording = $fileinfo['basename'];
                        $CallLog->save();

                        if (move_uploaded_file($_FILES['upload']['tmp_name'], $strFile)) {
                            echo 'OK';
                        }
                    }
                }
            }
        } else {
            echo "ERROR3";
        }
        return json_encode(true);
    }
    
    public function saveReview(Request $request)
    {
        $review = new Review;
        $review->user_id = Auth::user()->id;
        $review->lead_id = $request->lead_id;
        $review->review = $request->review;
        $review->save();
        echo '{"status":"success"}';
    }
    
    public function saveAuditReport(Request $request)
    {
        $audit = new \App\Models\AuditReport;
        $audit->lead_id = $request->lead_id;
        $audit->campaign_id = $request->campaign_id;
        $audit->flag = $request->audit_flag;
        $audit->role = $request->audit_role;
        $audit->remark = $request->audit_remarks;
        $audit->audited_by = Auth::user()->id;
        $audit->save();
        
        $lead = Lead::find($request->lead_id);
        
        switch($request->audit_role)
        {
            case 'TCL':
                $lead->tcl_audit = 'Audited';
                break;
            case 'FOS':
                $lead->fos_audit = 'Audited';
                break;
            case 'Both':
                $lead->tcl_audit = 'Audited';
                $lead->fos_audit = 'Audited';
                break;
        }
        $lead->save();

        echo '{"status":"success"}';
    }
    
    public function sendSms(Request $request)
    {
        $response = \App\Helpers\Helper::sendSms($request->lead_id, $request->phonenumber, $request->content);
        echo json_encode($response);
    }
    
    public function sendEmail(Request $request)
    {
        if(\App\Helpers\Helper::sendMail($request->email_address, $request->email_subject, $request->content))
            echo json_encode(['success' => true, 'error' => '']);
        else
            echo json_encode(['success' => false, 'error' => 'Mail Error']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Lead = Lead::find($id);
        if ($Lead && ($denied = $this->leadAccessDenied($Lead))) {
            return $denied;
        }
        $CampaignId = $Lead->campaign_id;
        $ProductServices = ProductService::orderBy('name', 'asc')->get();
        $Campaigns = DB::select("select * from ".env('DB_TABLE_PREFIX')."campaigns where expiry_date >= current_date order by id desc");

        if (!empty($Lead)) {
            return view("dashboard.leads.edit", compact("Lead", "ProductServices", "CampaignId", "Campaigns", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\LeadsController@index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $Lead = Lead::find($id);
        if (!empty($Lead) && ($denied = $this->leadAccessDenied($Lead))) {
            return $denied;
        }
        if (!empty($Lead)) {

            $this->validate($request, [
                'name' => 'required',
                'phonenumber' => 'required|min:10',
                'campaign_id' => 'required'
            ]);

//            $Lead->campaign_id = $request->campaign_id;
            $Lead->name = $request->name;
            $Lead->designation = $request->designation;
            $Lead->phonenumber = $request->phonenumber;
            $Lead->phonenumber_2 = $request->phonenumber_2;
            $Lead->company = $request->company;
            $Lead->description = $request->description;
            $Lead->country = $request->country;
            $Lead->pin_code = $request->pin_code;
            $Lead->city = $request->city;
            $Lead->state = $request->state;
            $Lead->address = $request->address;
            $Lead->email = $request->email;
            $Lead->website = $request->website;
            $Lead->position = $request->position;
            $Lead->latitude = $request->latitude;
            $Lead->longitude = $request->longitude;
            $Lead->product_service = !empty($request->product_service) ? implode(',',$request->product_service) : '';
            $Lead->lead_value = $request->lead_value;
            // created_by keeps the original creator; record who changed it last instead
            $Lead->last_actioned_by = Auth::user()->id;
            $Lead->last_actioned_date = Carbon::now();
            $Lead->is_public = empty($request->is_public) ? 0 : 1;
            $Lead->save();

            return redirect()->action('Dashboard\LeadsController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\LeadsController@index');
        }
    }
    
    public function data($id)
    {
        $Lead = Lead::find($id);
        echo json_encode($Lead);
    }
    
    public function getTeamMembersByCampaign($CampaignId)
    {
            $AssignedTo = DB::select("SELECT * FROM leadger_users
WHERE team_id IN 
(SELECT team_id FROM leadger_campaigns WHERE id = $CampaignId)
AND permissions_id IN (SELECT id FROM leadger_permissions WHERE NAME != 'Sales Person')");
            $SalesPersons = DB::select("SELECT * FROM leadger_users
WHERE team_id IN 
(SELECT team_id FROM leadger_campaigns WHERE id = $CampaignId)
AND permissions_id IN (SELECT id FROM leadger_permissions WHERE NAME = 'Sales Person')");
        $html = "<option value=''>-- Select User --</option>";
        foreach($AssignedTo as $row)
        {
            $html .= "<option value='".$row->id."' ".($row->id == Auth::user()->id ? 'selected' : '').">".$row->name."</option>";
        }
        $shtml = "<option value=''>-- Select User --</option>";
        foreach($SalesPersons as $row)
        {
            $shtml .= "<option value='".$row->id."' ".($row->id == Auth::user()->id ? 'selected' : '').">".$row->name."</option>";
        }
        echo json_encode(['assigned_to' => $html, 'sales_person' => $shtml]);
    }

    public function sipEndCall(Request $request)
    {
        $mobile = $request->mobile;
        $callLog = CallLog::where('phonenumber', $mobile)
                ->where('call_type', 'sip')
                ->whereNull('sip_end_time')
                ->where(DB::raw('date(created_at)'), date('Y-m-d'))
                ->orderBy('id', 'desc')->first();

        if(!empty($callLog))
        {
            $callLog->sip_end_time = date('Y-m-d H:i:s');
            $callLog->save();
            echo "OKtrue";
        }
        else
        {
            echo "NOTOK";
        }
    }

    public function duplicateNumber(Request $request)
    {
        $this->validate($request, [
            'campaign_id' => 'required',
            'phonenumber' => 'required'
        ]);
        
        $exists = DB::select("select * from leadger_leads where phonenumber = ".$request->phonenumber);
        if(!empty($exists))
            return json_encode(['success' => false, 'error' => true, 'message' => 'Phone Number already exists']);
        else
            return json_encode(['success' => true, 'message' => 'Phone Number is Unique']);
    }

}
