<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Status;
use App\Models\DialerLog;
use App\Models\Click2CallLog;
use Illuminate\Support\Facades\Http;

use App\Models\WebmasterSection;
use Auth;
use Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DialerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * The ?number= of a link to Manual Dial (e.g. a customer mobile on the Inbound Calls report), to fill into the
     * phone field: digits, + and spaces only, at most 15 characters; anything else is ignored. Never dialled.
     */
    public static function prefillNumber(): ?string
    {
        $n = request()->query('number');
        return is_string($n) && preg_match('/^[0-9+ ]{1,15}$/', $n) && preg_match('/\d/', $n) ? trim($n) : null;
    }

    public function dialer()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        
        $Campaigns = Campaign::whereDate(DB::raw("DATE(expiry_date)"), '>', date('Y-m-d'))->get();
        $Statuses = Status::orderBy("seq_order","asc")->get();
        $SubStatuses = \App\Models\Substatus::orderBy('seq_order', 'asc')->get();
        $User = Auth::user();

        // Click2Call / WebRTC numbers saved in Add Voucher (team-scoped for non-admins, with availability)
        $vmnList = \App\Services\VmnService::listFor($User, 'click2call');
        $wvmnList = \App\Services\VmnService::listFor($User, 'webrtc');

        // Fetch all leads for manual dialer drawer
        $allLeads = \App\Models\Lead::limit(50)->orderBy('id', 'desc')->get();
        foreach ($allLeads as $aLead) {
            $parts = explode(' ', trim($aLead->name ?: 'Lead'));
            $aLead->initials = count($parts) >= 2 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($aLead->name ?: 'LD', 0, 2));
            $aLead->city_name = $aLead->city ?: 'Hyderabad';
            $aStatus = 'New Lead';
            if (!empty($aLead->status)) {
                $st = $Statuses->where('id', $aLead->status)->first();
                if ($st) $aStatus = $st->name;
            }
            $aLead->status_name = $aStatus;
        }

        // Default campaign & lead data for Tab 1 if requested/needed
        $firstCampaign = $Campaigns->first();
        $campaignId = $firstCampaign ? $firstCampaign->id : 1;
        $Lead = \App\Models\Lead::where('campaign_id', $campaignId)->first() ?? \App\Models\Lead::first();
        
        $campaignLeads = collect([]);
        $callLogs = collect([]);
        $latestCallDuration = '00:00:00';
        $totalQueueCount = 0;
        $nextLeadId = null;
        $leadId = null;
        $Campaign = null;

        if ($Lead) {
            $leadId = $Lead->id;
            $Campaign = Campaign::find($Lead->campaign_id) ?? (object)['id' => 1, 'name' => 'Default Campaign'];
            $campaignId = $Campaign->id;

            $remainingLeads = \App\Models\Lead::where('campaign_id', $campaignId)
                ->where('id', '!=', $Lead->id)
                ->orderBy('id', 'asc')
                ->get();
            $campaignLeads = collect([$Lead])->concat($remainingLeads);
            $totalQueueCount = $campaignLeads->count();

            $callLogs = DB::table('call_logs')
                ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
                ->leftJoin('statuses', 'statuses.id', '=', 'call_logs.next_status')
                ->select('call_logs.*', 'users.name as called_by_name', 'statuses.name as status_name')
                ->where('call_logs.lead_id', $Lead->id)
                ->orderBy('call_logs.id', 'desc')
                ->get();

            $Lead->formatted_id = (string)$Lead->id;
            $Lead->city_name = !empty($Lead->city) ? $Lead->city : 'Hyderabad';
            $productName = 'Home Loan';
            if (!empty($Lead->product_service)) {
                $ps = \App\Models\ProductService::find($Lead->product_service);
                if ($ps && !empty($ps->name)) $productName = $ps->name;
            } elseif (!empty($Campaign->name)) {
                $productName = $Campaign->name;
            }
            $Lead->product_name = $productName;
            $Lead->source_name = !empty($Lead->source) ? $Lead->source : 'Website';
            $Lead->calls_count = count($callLogs);

            $statusName = 'New Lead';
            if (!empty($Lead->status)) {
                $st = $Statuses->where('id', $Lead->status)->first();
                if ($st && !empty($st->name)) $statusName = $st->name;
            }
            $Lead->status_name = $statusName;

            $nameParts = explode(' ', trim($Lead->name ?: 'Lead'));
            $Lead->initials = (count($nameParts) >= 2) 
                ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)) 
                : strtoupper(substr($Lead->name ?: 'LD', 0, 2));

            if ($callLogs->isNotEmpty()) {
                $firstLog = $callLogs->first();
                if (!empty($firstLog->duration) && is_numeric($firstLog->duration)) {
                    $dur = (int)$firstLog->duration;
                    $latestCallDuration = sprintf('%02d:%02d:%02d', floor($dur / 3600), floor(($dur % 3600) / 60), $dur % 60);
                }
            }

            foreach ($campaignLeads as $cLead) {
                $parts = explode(' ', trim($cLead->name ?: 'Lead'));
                $cLead->initials = (count($parts) >= 2) ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($cLead->name ?: 'LD', 0, 2));
                $cStatusName = 'New';
                if (!empty($cLead->status)) {
                    $st = $Statuses->where('id', $cLead->status)->first();
                    if ($st) $cStatusName = $st->name;
                }
                $cLead->status_name = $cStatusName;
                $cLead->product_name = $productName;
                $sLower = strtolower($cStatusName);
                if (strpos($sLower, 'connect') !== false) $cLead->badge_color = '#10b981';
                elseif (strpos($sLower, 'follow') !== false) $cLead->badge_color = '#f59e0b';
                else $cLead->badge_color = '#3b82f6';
            }
        }

        $campaignCallingType = null;
        if ($Campaign) {
            $rawType = $Campaign->calling_type
                ?? $Campaign->call_type
                ?? $Campaign->calling_method
                ?? $Campaign->dialer_type
                ?? $Campaign->type
                ?? null;
            if (!empty($rawType)) {
                $val = strtolower(trim((string)$rawType));
                if (str_contains($val, 'webrtc')) {
                    $campaignCallingType = 'webrtc';
                } elseif (str_contains($val, 'click') || str_contains($val, 'c2c')) {
                    $campaignCallingType = 'click2call';
                }
            }
        }

        $activeTab = request()->get('tab', 'manual');

        $recentNumbers = DB::table('dialer_logs')
            ->where('created_by', Auth::id())
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->orderBy('id', 'desc')
            ->pluck('phone_number')
            ->unique()
            ->take(5)
            ->values();

        if ($recentNumbers->isEmpty()) {
            $recentNumbers = DB::table('dialer_logs')
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '')
                ->orderBy('id', 'desc')
                ->pluck('phone_number')
                ->unique()
                ->take(5)
                ->values();
        }

        // The user's own saved channels only (Staff / Click2Call / WebRTC pages). A user with a channel
        // disabled must not get it from a global fallback.
        $c2cEnabled = $User->canClick2Call();
        $webrtcEnabled = ($User->webrtc_channel == 1);

        $allowedCampaigns = \App\Http\Controllers\Dashboard\CampaignsController::getAllowedCampaignsForUser($User);
        $selectedCampaignId = session('selected_campaign_id') ?: ($Campaign ? $Campaign->id : null);

        return view('dashboard.calling-workspace', compact(
            'GeneralWebmasterSections',
            'Campaigns',
            'allowedCampaigns',
            'selectedCampaignId',
            'Statuses',
            'SubStatuses',
            'User',
            'vmnList',
            'wvmnList',
            'allLeads',
            'activeTab',
            'Campaign',
            'Lead',
            'campaignLeads',
            'totalQueueCount',
            'callLogs',
            'latestCallDuration',
            'nextLeadId',
            'campaignId',
            'leadId',
            'campaignCallingType',
            'recentNumbers',
            'c2cEnabled',
            'webrtcEnabled'
        ));
    }
    
    public function click2call(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
            'vmn' => ['required'],
        ], [
            'mobile.required' => 'Please enter mobile number.',
            'mobile.regex' => 'Phone number must contain only numeric digits (0–9).',
            'mobile.min' => 'Please enter at least 10 digits mobile number.',
            'mobile.max' => 'Mobile number cannot exceed 20 digits.',
            'vmn.required' => 'Please select VMN.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Enforce backend outbound calling restrictions (hours + retry limit)
        if (!$user->canClick2Call()) {
            return response()->json(['success' => false, 'message' => 'Click2Call is not enabled for your account. Ask your administrator to enable Click2Call (People -> Staff, or Click2Call) with your mobile number.'], 403);
            }

            $callCheck = \App\Services\CallingRestrictionService::canPlaceOutboundCall($request->mobile, $user, 'click2call', false);   // counted by Helper::click2call
        if (!$callCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $callCheck['message'],
                'reason' => $callCheck['reason'] ?? 'restriction'
            ], $callCheck['code'] ?? 403);
        }

        return \App\Services\CallingRestrictionService::withCallLock($user, function () use ($request, $user) {

            $uuid = (string)Str::uuid();
            $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            $full_response = json_decode($settings->full_response ?? '{}', true);
            if(!isset($full_response['data']['crm_id']))
            {
                return response()->json(['success' => false, 'message' => 'CRM ID is not available'], 422);
            }

            $cleanVmn = preg_replace('/[^0-9]/', '', $request->vmn);
            if (strlen($cleanVmn) == 12 && str_starts_with($cleanVmn, '91')) {
                $cleanVmn = substr($cleanVmn, 2);
            }

            // The VMN must be configured for Click2Call (Add Voucher / Smart31) and not expired; with Smart31 or
            // Enabled 1600 selected it must also be a number of that option
            $pool = $request->boolean('smart31_enabled') ? 'smart31' : ($request->boolean('vmn1600_enabled') ? '1600' : null);
            $vmnCheck = \App\Services\VmnService::check($user, $cleanVmn, 'click2call', $pool);
            if (!$vmnCheck['ok']) {
                return response()->json(['success' => false, 'message' => $vmnCheck['message']], 422);
            }

            $cleanMobile = preg_replace('/[^0-9]/', '', $request->mobile);
            if (strlen($cleanMobile) == 12 && str_starts_with($cleanMobile, '91')) {
                $cleanMobile = substr($cleanMobile, 2);
            }

            $agentNumber = $user->click_agent_number ?: $user->mobile;
            if (!$agentNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent mobile number is not configured in your profile. Please update your profile.'
                ], 422);
            }

            $cleanAgentNumber = preg_replace('/[^0-9]/', '', $agentNumber);
            if (strlen($cleanAgentNumber) == 12 && str_starts_with($cleanAgentNumber, '91')) {
                $cleanAgentNumber = substr($cleanAgentNumber, 2);
            }

            $didToUse = $cleanVmn ?: $request->vmn;

            if($request->default_vmn == 'true')
            {
                $user->c2c_default_number = $didToUse;
                $user->save();
            }
            
            $targetMobile = $cleanMobile ?: $request->mobile;
            $leadId = !empty($request->lead_id) ? $request->lead_id : null;
            $campaignId = !empty($request->campaign_id) ? $request->campaign_id : null;

            $sourceParam = strtolower(trim((string)$request->input('source', $request->input('tab', ''))));
            $isCampaignCall = false;

            if ($sourceParam === 'campaign') {
                $isCampaignCall = true;
            } elseif ($sourceParam === 'manual') {
                $isCampaignCall = false;
            } else {
                $isCampaignCall = (!empty($campaignId) && !empty($leadId));
            }

            // For manual calls: check if dialed number matches an existing lead.
            // If it matches, link lead_id and campaign_id on the manual call record.
            // If not, both remain null.
            if (!$isCampaignCall) {
                $matchedLead = \App\Models\Lead::where('phonenumber', $targetMobile)
                    ->orWhere('phonenumber_2', $targetMobile)
                    ->first();
                if ($matchedLead) {
                    $leadId = $matchedLead->id;
                    $campaignId = $matchedLead->campaign_id;
                } else {
                    $leadId = null;
                    $campaignId = null;
                }
            }

            $referenceId = $uuid . '_d_' . $full_response['data']['crm_id'] . '_' . $user->id . '_1';
            $crmId = $full_response['data']['crm_id'];

            try {
                if ($isCampaignCall) {
                    $callLog = new \App\Models\CallLog();
                    $callLog->lead_id = $leadId;
                    $callLog->campaign_id = $campaignId;
                    $callLog->phonenumber = $targetMobile;
                    $callLog->call_type = 'click2call';
                    $callLog->call_source = 'campaign';
                    $callLog->call_state = 'initiated';
                    $callLog->created_by = $user->id;
                    $callLog->vmn_used = $didToUse;
                    $callLog->reference_id = $referenceId;
                    $callLog->provider_call_id = $referenceId;
                    $callLog->verified = 1;
                    $callLog->duration = 0;
                    $callLog->start_time = Carbon::now();
                    $callLog->created_at = Carbon::now();
                    $callLog->save();


                    $targetLogId = $callLog->id;
                    $activeRecord = $callLog;
                } else {
                    $log = new DialerLog();
                    $log->phone_number = $targetMobile;
                    $log->created_by = $user->id;
                    $log->lead_id = $leadId ?: null; // manual call: no campaign (DialerLog enforces this)
                    $log->time_taken = 0;
                    $log->duration = '00:00';
                    $log->uuid = $uuid . '_d_' . $crmId . '_' . $user->id . '_';
                    $log->vmn_used = $didToUse;
                    $log->call_type = 'click2call';
                    $log->call_source = 'manual';
                    $log->verified = 1;
                    $log->call_state = 'initiated';
                    $log->start_time = Carbon::now();
                    $log->reference_id = $referenceId;
                    $log->provider_call_id = $referenceId;
                    $log->save();


                    $targetLogId = $log->id;
                    $activeRecord = $log;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('[Click2Call] Failed creating initial call record: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);
                return response()->json(['success' => false, 'message' => 'Failed to initialize call record: ' . $e->getMessage()], 500);
            }

            Click2CallLog::create([
                'uuid' => $uuid,
                'reference_id' => $referenceId,
                'user_id' => $user->id,
                'vmn' => $didToUse,
            ]);

            $callfields = array(
                "did" => $didToUse,
                "a_party_no" => $cleanAgentNumber,
                "b_party_no" => $cleanMobile ?: $request->mobile,
                "ref_id" => $referenceId,
                "record" => 1,
                "recording" => 1,
                "record_call" => 1,
            );

            $response = Helper::click2call($callfields, $targetLogId);
            $respData = json_decode($response, true);
            if (!($respData['success'] ?? false)) {
                $errMsg = $respData['message'] ?? ($respData['response']['error'] ?? 'Call failed to initiate at gateway');
                \Illuminate\Support\Facades\Log::error('[Click2Call] Gateway failure: ' . $errMsg, ['resp' => $respData]);
                return response()->json([
                    'success' => false,
                    'message' => $errMsg,
                    'raw' => $respData
                ], 400);
            }

            $providerCallId = $respData['provider_call_id'] ?? ($respData['response']['call_id'] ?? $referenceId);
            $activeRecord->provider_call_id = $providerCallId;
            $activeRecord->save();


            return response()->json([
                'uuid' => $uuid . '_d_' . $crmId,
                'id' => $activeRecord->id,
                'call_log_id' => $isCampaignCall ? $activeRecord->id : null,
                'dialer_log_id' => !$isCampaignCall ? $activeRecord->id : null,
                'is_campaign' => $isCampaignCall,
                'provider_call_id' => $providerCallId,
                'reference_id' => $referenceId,
                'success' => true,
                'message' => 'Call initiated successfully'
            ]);
        });
    }

    public function savelog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
            'status_id' => ['required', 'exists:statuses,id'],
        ], [
            'mobile.required' => 'Please enter mobile number.',
            'mobile.regex' => 'Phone number must contain only numeric digits (0–9).',
            'mobile.min' => 'Please enter at least 10 digits mobile number.',
            'mobile.max' => 'Mobile number cannot exceed 20 digits.',
            'status_id.required' => 'Please select a lead status.',
            'status_id.exists' => 'The selected status no longer exists. Refresh the page and try again.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        // A substatus must be one of the chosen status's substatuses (Master Data -> Lead Status)
        if ($request->filled('substatus_id')
            && !\App\Models\Substatus::where('id', $request->substatus_id)->where('status_id', $request->status_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'The selected substatus does not belong to the selected status.'], 422);
        }

        // Follow-up: required when the status has "Requires follow-up date" (Disposition Settings), optional otherwise
        [$followAt, $followError] = $this->followupFromRequest($request, \App\Models\Lead::statusRequiresFollowup($request->status_id));
        if ($followError) {
            return response()->json(['success' => false, 'message' => $followError], 422);
        }

        try {
            DB::beginTransaction();

            $cleanMobile = preg_replace('/[^0-9]/', '', (string)$request->mobile);
            if (strlen($cleanMobile) == 12 && str_starts_with($cleanMobile, '91')) {
                $cleanMobile = substr($cleanMobile, 2);
            }
            $targetMobile = $cleanMobile ?: $request->mobile;

            $log = null;

            // 1. Try finding by ID in DialerLog or CallLog
            if (!empty($request->id)) {
                // A campaign call's id is a call_logs id: never look it up in dialer_logs (ids overlap)
                $isCampaignCall = (string)$request->input('is_campaign') === '1';
                $log = $isCampaignCall ? null : DialerLog::find($request->id);
                if (!$log) {
                    // Check if it was a CallLog ID (campaign call disposition)
                    $cLog = \App\Models\CallLog::find($request->id);
                    if ($cLog) {
                        $cLog->call_source = 'campaign';
                        $cLog->next_status = $request->status_id;
                        if (!empty($request->substatus_id)) {
                            $cLog->substatus_id = $request->substatus_id;
                        }
                        if (!empty($request->comments)) {
                            $cLog->remarks = $request->comments;
                        } elseif (!empty($request->feedback)) {
                            $cLog->remarks = $request->feedback;
                        }
                        if ($followAt) {
                            $cLog->follow_up_on = $followAt;
                        }
                        if (in_array($cLog->call_state, [null, '', 'initiated', 'ringing', 'answered', 'connected'], true)) { $cLog->call_state = 'completed'; }
                        if (empty($cLog->end_time)) {
                            $cLog->end_time = Carbon::now();
                        }
                        $cLog->save();

                        \App\Services\CallRecordingService::storeOrUpdateRecording('Campaign Calling', $cLog, [
                            'call_status' => 'completed',
                            'duration' => $cLog->duration
                        ]);

                        // The campaign lead this call was made to; a call saved without one is matched by number
                        // inside the call's campaign (the campaign and lead relationship is kept)
                        $lead = !empty($cLog->lead_id) ? \App\Models\Lead::find($cLog->lead_id) : null;
                        if (!$lead) {
                            $lead = $this->findLeadByPhone($this->normalizePhone($cLog->phonenumber ?: $request->mobile), $cLog->campaign_id ?: null);
                            if ($lead) {
                                $cLog->lead_id = $lead->id;
                                $cLog->save();
                            }
                        }
                        if ($lead) {
                            $lead->status = $request->status_id;
                            if (!empty($request->substatus_id)) $lead->substatus = $request->substatus_id;
                            if (!empty($request->comments)) $lead->remarks = $request->comments;
                            $lead->last_actioned_by = Auth::id();
                            $lead->last_actioned_date = Carbon::now();
                            $lead->save();
                            if ($followAt) $lead->scheduleFollowup($followAt, Auth::user());
                        } elseif ($followAt) {
                            throw new \RuntimeException('this campaign call is not linked to a lead, so the follow-up cannot be scheduled.');
                        }

                        DB::commit();
                        return response()->json($this->savedResponse($followAt, $lead));
                    }
                }
            }

            // 2. Try finding by UUID
            if (!$log && !empty($request->uuid)) {
                $log = DialerLog::where('uuid', 'like', $request->uuid . '%')->orderBy('id', 'desc')->first();
            }

            // 3. Try finding recent active dialer log for this phone & agent (last 2 hours)
            if (!$log && !empty($targetMobile)) {
                $log = DialerLog::where('created_by', Auth::id())
                    ->where('phone_number', $targetMobile)
                    ->where('created_at', '>=', Carbon::now()->subHours(2))
                    ->orderBy('id', 'desc')
                    ->first();
            }

            if ($log) {
                if (empty($log->end_time)) {
                    $log->end_time = Carbon::now();
                }
                // Talk time = connected -> end (not start -> now, which would include ringing and form-filling time).
                // If the call was never answered it stays 0; the provider's call-end data fills it otherwise.
                if (empty($log->time_taken) && !empty($log->answer_time)) {
                    $talk = (int)Carbon::parse($log->answer_time)->diffInSeconds(Carbon::parse($log->end_time), true);
                    if ($talk > 0) {
                        $log->time_taken = $talk;
                        $log->duration = Helper::formatDuration($talk);
                    }
                }
                if (in_array($log->call_state, [null, '', 'initiated', 'ringing', 'answered', 'connected'], true)) { $log->call_state = 'completed'; }
            } else {
                $log = new DialerLog();
                $log->call_type = $request->call_type ?: 'click2call';
                $log->time_taken = 0;
                $log->duration = '00:00';
                $log->call_state = 'completed';
                $log->start_time = Carbon::now();
                $log->end_time = Carbon::now();
            }

            // Manual Dial calls MUST have call_source = 'manual'
            $log->call_source = 'manual';
            $log->phone_number = $targetMobile;
            $log->status_id = $request->status_id;
            $log->substatus_id = !empty($request->substatus_id) ? $request->substatus_id : null;
            $log->feedback = $request->feedback ?: $request->comments;
            $log->comments = $request->comments ?: $request->feedback;
            $log->created_by = Auth::user()->id;

            // The dialed number's lead (any stored format; the lead the page is open on wins). With a follow-up date
            // and no lead yet, a lead is created in the agent's "Manual Dial" campaign so the follow-up can be booked.
            $leadCreated = false;
            $matchedLead = $this->findLeadByPhone($this->normalizePhone($targetMobile), null, (int)$request->input('lead_id'));
            if (!$matchedLead && $followAt) {
                $matchedLead = $this->createManualDialLead($this->normalizePhone($targetMobile), Auth::user());
                $leadCreated = $matchedLead->wasRecentlyCreated;
            }
            // Link the lead for its call history; a manual call never gets the lead's campaign
            $log->lead_id = $matchedLead ? $matchedLead->id : null;

            $log->save();

            \App\Services\CallRecordingService::storeOrUpdateRecording('Dialing', $log, [
                'call_status' => 'completed',
                'duration' => $log->time_taken
            ]);

            $user = \App\Models\User::find(Auth::user()->id);
            if ($user) {
                if ($request->default_dialer_campaign == '1' && !empty($request->campaign_id)) {
                    $user->default_dialer_campaign = $request->campaign_id;
                } else {
                    $user->default_dialer_campaign = 0;
                }
                $user->save();
            }

            // Also update lead if found
            if ($matchedLead) {
                $matchedLead->status = $request->status_id;
                if (!empty($request->substatus_id)) {
                    $matchedLead->substatus = $request->substatus_id;
                }
                if (!empty($request->comments)) {
                    $matchedLead->remarks = $request->comments;
                }
                $matchedLead->last_actioned_by = Auth::user()->id;
                $matchedLead->last_actioned_date = Carbon::now();
                $matchedLead->save();
                if ($followAt) {
                    $matchedLead->scheduleFollowup($followAt, Auth::user());
                }
            }

            DB::commit();

            return response()->json($this->savedResponse($followAt, $matchedLead, $leadCreated));
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error saving dialer outcome: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            // Nothing was saved (one transaction): give the real reason, never a partial success
            return response()->json([
                'success' => false,
                'message' => 'The call outcome was not saved: ' . $e->getMessage()
            ], 500);
        }
    }

    /** Phone number as its last 10 digits (drops spaces, +91, 0 prefixes). */
    private function normalizePhone($phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /**
     * The lead for a dialed number, matched on the last 10 digits of phonenumber / phonenumber_2 whatever format
     * they were stored in. $preferLeadId (the lead the page is open on) wins when it has that number; otherwise
     * a lead assigned to this agent, then the most recently worked one. Optionally limited to one campaign.
     */
    private function findLeadByPhone(string $phone10, ?int $campaignId = null, int $preferLeadId = 0): ?\App\Models\Lead
    {
        if (strlen($phone10) < 10) {
            return null;
        }
        $digits = fn ($col) => "RIGHT(REGEXP_REPLACE(COALESCE({$col}, ''), '[^0-9]', ''), 10)";
        $query = \App\Models\Lead::where(function ($q) use ($digits, $phone10) {
            $q->whereRaw($digits('phonenumber') . ' = ?', [$phone10])->orWhereRaw($digits('phonenumber_2') . ' = ?', [$phone10]);
        });
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }
        if ($preferLeadId > 0 && ($preferred = (clone $query)->where('id', $preferLeadId)->first())) {
            return $preferred;
        }
        return $query->orderByRaw('(assigned_to = ?) DESC', [Auth::id()])
            ->orderByRaw('last_actioned_date IS NULL, last_actioned_date DESC')
            ->orderBy('id')
            ->first();
    }

    /**
     * Lead for a manual-dial number that is not a lead yet, in the agent's team "Manual Dial" campaign (created the
     * first time it is needed; a campaign belongs to one team, so each team has its own). Lead IDs follow the
     * campaign ID + 6-digit sequence used by Add Lead / Import. The campaign row is locked while the lead is created
     * and the number is checked again, so repeated or simultaneous saves never create two leads.
     */
    private function createManualDialLead(string $phone10, \App\Models\User $agent): \App\Models\Lead
    {
        if (strlen($phone10) < 10) {
            throw new \RuntimeException('a valid 10-digit number is needed to create the lead for this follow-up.');
        }
        $team = (string)(array_values(array_filter(array_map('intval', explode(',', (string)$agent->team_id))))[0] ?? '');
        $campaign = \App\Models\Campaign::where('name', 'Manual Dial')->where('team_id', $team)->first();
        if (!$campaign) {
            $campaign = new \App\Models\Campaign();
            $campaign->forceFill([
                'name' => 'Manual Dial', 'source_id' => 0, 'segment_id' => 0, 'team_id' => $team, 'status' => '1',
                'calling_type' => 'both', 'expiry_date' => '2099-12-31 23:59:59', 'created_by' => $agent->id,
                'public' => 0, 'lead_info_edit' => 0, 'number_masking' => 0,
            ])->save();
        }
        \App\Models\Campaign::whereKey($campaign->id)->lockForUpdate()->first();

        if ($existing = $this->findLeadByPhone($phone10)) {
            return $existing;   // created meanwhile by another save
        }
        $max = (int)\App\Models\Lead::where('campaign_id', $campaign->id)->max('id');
        $lead = new \App\Models\Lead();
        $lead->id = $max > 0 ? $max + 1 : (int)($campaign->id . sprintf('%06d', 1));
        $lead->campaign_id = $campaign->id;
        $lead->name = 'Lead ' . $phone10;
        $lead->phonenumber = $phone10;
        $lead->assigned_to = $agent->id;
        $lead->created_by = $agent->id;
        $lead->is_public = 0;
        $lead->save();
        return $lead;
    }

    /**
     * Follow-up date & time from the dialer forms: "follow_up_on" (datetime-local) or "followup_date" + "followup_time".
     * Returns [Carbon|null, error|null]. A follow-up must be a valid date in the future.
     */
    private function followupFromRequest(Request $request, bool $required): array
    {
        $raw = trim((string)$request->input('follow_up_on', $request->input('followup_datetime', '')));
        if ($raw === '' && $request->filled('followup_date')) {
            $raw = trim($request->input('followup_date') . ' ' . $request->input('followup_time', ''));
        }
        if ($raw === '') {
            if (!$required) return [null, null];
            $name = \App\Models\Status::where('id', $request->status_id)->value('name') ?: 'This status';
            return [null, "{$name} requires a follow-up date and time."];
        }
        try {
            $at = Carbon::parse($raw);
        } catch (\Exception $e) {
            return [null, 'Enter a valid follow-up date and time.'];
        }
        if ($at->lt(Carbon::now()->subMinute())) {
            return [null, 'The follow-up date and time must be in the future.'];
        }
        return [$at->second(0), null];
    }

    /**
     * The lead a dialer quick action (note / follow-up / tag) is for. The dialed number decides: the page's
     * lead_id is only used when it belongs to that number (it is pre-filled with whatever lead the page opened on).
     */
    private function quickActionLead(Request $request): ?\App\Models\Lead
    {
        $norm = function ($n) {
            $n = preg_replace('/[^0-9]/', '', (string)$n);
            return (strlen($n) == 12 && str_starts_with($n, '91')) ? substr($n, 2) : $n;
        };
        $phone = $norm($request->input('phone') ?: $request->input('mobile'));
        if ($request->filled('lead_id')) {
            $lead = \App\Models\Lead::find($request->input('lead_id'));
            if ($lead && ($phone === '' || in_array($phone, [$norm($lead->phonenumber), $norm($lead->phonenumber_2)], true))) {
                return $lead;
            }
        }
        return $phone === '' ? null : \App\Models\Lead::where('phonenumber', $phone)->orWhere('phonenumber_2', $phone)->first();
    }

    /** Response after the outcome (and, with a date, the follow-up on the lead) were committed together. */
    private function savedResponse(?Carbon $followAt, ?\App\Models\Lead $lead, bool $leadCreated = false): array
    {
        $res = ['success' => true, 'message' => 'Call outcome saved successfully.', 'follow_up_on' => null, 'lead_id' => $lead->id ?? null, 'lead_created' => $leadCreated];
        if ($followAt && $lead) {
            $res['follow_up_on'] = $followAt->format('Y-m-d H:i:s');
            $res['message'] = 'Call outcome and follow-up saved successfully. Follow-up on ' . $followAt->format('d-M-Y h:i A')
                . ($leadCreated ? " (new lead {$lead->id} in Manual Dial)." : " (lead {$lead->id}).");
        }
        return $res;
    }

    /**
     * Calling-option numbers for the dialer (Make a Call / Campaign Queue): Smart31 or Enabled 1600,
     * for the click2call / webrtc channel. Only that option's stored numbers (VmnService::poolFor).
     */
    private function poolVmnsResponse(Request $request, string $pool)
    {
        $callingType = strtolower($request->get('type', 'webrtc')) === 'click2call' ? 'click2call' : 'webrtc';
        $prefix = ($callingType === 'click2call' ? 'Click2Call ' : 'WebRTC ') . ($pool === '1600' ? '1600 VMN: ' : 'VMN: ');
        $vmns = \App\Services\VmnService::poolFor(Auth::user(), $callingType, $pool)->map(fn ($r) => [
            'number' => $r->virtual_number,
            'display' => $prefix . $r->virtual_number,
            'available' => $r->available,
            'expiry' => $r->expiry_label,
        ])->values();

        return response()->json([
            'success' => true,
            'calling_type' => $callingType,
            'vmns' => $vmns,
            'count' => $vmns->count()
        ]);
    }

    public function getSmart31Vmns(Request $request)
    {
        return $this->poolVmnsResponse($request, 'smart31');
    }

    public function get1600Vmns(Request $request)
    {
        return $this->poolVmnsResponse($request, '1600');
    }

    /**
     * Detect channel type (Click2Call vs WebRTC) for dialed phone number
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detectChannel(Request $request)
    {
        $rawMobile = $request->get('mobile') ?: ($request->get('phone') ?: $request->get('number'));
        $leadId = $request->get('lead_id');

        $clean = preg_replace('/[^0-9]/', '', (string)$rawMobile);
        if (strlen($clean) == 12 && str_starts_with($clean, '91')) {
            $clean10 = substr($clean, 2);
        } elseif (strlen($clean) > 10) {
            $clean10 = substr($clean, -10);
        } else {
            $clean10 = $clean;
        }

        $user = Auth::user();
        $userC2c = $user ? ($user->c2c_channel == 1) : false;
        $userWebRTC = $user ? ($user->webrtc_channel == 1) : false;

        if (!$userC2c && !$userWebRTC) {
            $activeSetting = Helper::GeneralSiteSettings('active_call_channel') ?: 'click2call';
            if ($activeSetting === 'both') {
                $userC2c = true;
                $userWebRTC = true;
            } elseif ($activeSetting === 'webrtc') {
                $userWebRTC = true;
            } else {
                $userC2c = true;
            }
        }

        $userFallbackType = ($userC2c && $userWebRTC) ? 'both' : ($userWebRTC ? 'webrtc' : 'click2call');

        if (empty($clean10)) {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'type' => null,
                'channel' => null,
                'dids' => [],
                'user_fallback' => $userFallbackType,
                'message' => 'Enter a number to see calling options'
            ]);
        }

        $detectedType = null;

        // 1. Check OBD DID table (if the dialed number matches a DID)
        if (!empty($clean10)) {
            $didMatch = DB::table('obd_did')
                ->where(function($q) use ($clean, $clean10) {
                    $q->where('virtual_number', $clean)
                      ->orWhere('virtual_number', $clean10)
                      ->orWhere('virtual_number', 'like', "%{$clean10}%");
                })
                ->first();
            if ($didMatch) {
                $hasC2c = ((int)$didMatch->click2call === 1);
                $hasWeb = ((int)$didMatch->webrtc === 1);
                if ($hasC2c && $hasWeb) {
                    $detectedType = 'both';
                } elseif ($hasC2c) {
                    $detectedType = 'click2call';
                } elseif ($hasWeb) {
                    $detectedType = 'webrtc';
                }
            }
        }

        // 2. Check Leads & Campaigns
        if (!$detectedType) {
            $leadQuery = DB::table('leads');
            if (!empty($leadId)) {
                $leadQuery->where('id', $leadId);
            } else {
                $leadQuery->where(function($q) use ($clean, $clean10) {
                    $q->where('phonenumber', $clean)
                      ->orWhere('phonenumber', $clean10)
                      ->orWhere('phonenumber_2', $clean)
                      ->orWhere('phonenumber_2', $clean10);
                });
            }
            $lead = $leadQuery->first();

            if ($lead) {
                if (!empty($lead->campaign_id)) {
                    $campaign = DB::table('campaigns')->where('id', $lead->campaign_id)->first();
                    if ($campaign && !empty($campaign->calling_type)) {
                        $cType = strtolower(trim((string)$campaign->calling_type));
                        if (str_contains($cType, 'both')) {
                            $detectedType = 'both';
                        } elseif (str_contains($cType, 'webrtc')) {
                            $detectedType = 'webrtc';
                        } elseif (str_contains($cType, 'click') || str_contains($cType, 'c2c')) {
                            $detectedType = 'click2call';
                        }
                    }
                }
                if (!$detectedType && !empty($lead->assigned_to)) {
                    $assignedUser = DB::table('users')->where('id', $lead->assigned_to)->first();
                    if ($assignedUser) {
                        $uC2c = ($assignedUser->c2c_channel == 1);
                        $uWeb = ($assignedUser->webrtc_channel == 1);
                        if ($uC2c && $uWeb) {
                            $detectedType = 'both';
                        } elseif ($uC2c) {
                            $detectedType = 'click2call';
                        } elseif ($uWeb) {
                            $detectedType = 'webrtc';
                        }
                    }
                }
            }
        }

        // 3. Check Users table
        if (!$detectedType) {
            $matchedUser = DB::table('users')
                ->where(function($q) use ($clean, $clean10) {
                    $q->where('mobile', $clean)
                      ->orWhere('mobile', $clean10)
                      ->orWhere('click_agent_number', $clean)
                      ->orWhere('click_agent_number', $clean10);
                })
                ->first();
            if ($matchedUser) {
                $uC2c = ($matchedUser->c2c_channel == 1);
                $uWeb = ($matchedUser->webrtc_channel == 1);
                if ($uC2c && $uWeb) {
                    $detectedType = 'both';
                } elseif ($uC2c) {
                    $detectedType = 'click2call';
                } elseif ($uWeb) {
                    $detectedType = 'webrtc';
                }
            }
        }

        // 4. Check past dialer logs
        if (!$detectedType && !empty($clean10)) {
            $pastTypes = DB::table('dialer_logs')
                ->where(function($q) use ($clean, $clean10) {
                    $q->where('phone_number', $clean)
                      ->orWhere('phone_number', $clean10)
                      ->orWhere('phone_number', 'like', "%{$clean10}%");
                })
                ->whereNotNull('call_type')
                ->where('call_type', '!=', '')
                ->distinct()
                ->pluck('call_type')
                ->map(function($t) { return strtolower(trim((string)$t)); })
                ->toArray();

            $hasC2cLog = in_array('click2call', $pastTypes) || in_array('c2c', $pastTypes);
            $hasWebRtcLog = in_array('webrtc', $pastTypes);

            if ($hasC2cLog && $hasWebRtcLog) {
                $detectedType = 'both';
            } elseif ($hasC2cLog) {
                $detectedType = 'click2call';
            } elseif ($hasWebRtcLog) {
                $detectedType = 'webrtc';
            }
        }

        // 5. Fallback to channels enabled for the logged-in user
        if (!$detectedType) {
            $detectedType = $userFallbackType;
        }

        // Ensure user permissions allow the detected type
        $finalType = $detectedType;
        if ($finalType === 'both') {
            if (!$userC2c && $userWebRTC) {
                $finalType = 'webrtc';
            } elseif ($userC2c && !$userWebRTC) {
                $finalType = 'click2call';
            }
        } elseif ($finalType === 'click2call' && !$userC2c && $userWebRTC) {
            $finalType = 'webrtc';
        } elseif ($finalType === 'webrtc' && !$userWebRTC && $userC2c) {
            $finalType = 'click2call';
        }

        // Fetch DIDs valid for the channel(s) without "(Main Line)" or "(WebRTC Line)"
        $dids = [];
        $isAdmin = ($user && isset($user->permissionsGroup) && $user->permissionsGroup->name === 'Admin');
        $userTeamIds = ($user && !empty($user->team_id)) ? array_filter(explode(',', (string)$user->team_id)) : [];

        $didQuery = DB::table('obd_did');
        if ($finalType === 'click2call') {
            $didQuery->where('click2call', '1');
        } elseif ($finalType === 'webrtc') {
            $didQuery->where('webrtc', '1');
        } else {
            $didQuery->where(function($q) {
                $q->where('click2call', '1')->orWhere('webrtc', '1');
            });
        }

        if (!$isAdmin && !empty($userTeamIds)) {
            $didQuery->where(function($q) use ($userTeamIds) {
                $q->whereNull('team_id')->orWhere('team_id', '=', '');
                foreach ($userTeamIds as $groupId) {
                    $q->orWhereRaw("FIND_IN_SET(?, team_id)", [$groupId]);
                }
            });
        }

        $didList = $didQuery->get();
        if ($didList->isEmpty()) {
            $didList = DB::table('obd_did')->get();
        }

        $defaultNumber = ($finalType === 'webrtc') ? ($user->webrtc_default_number ?? '') : ($user->c2c_default_number ?? '');

        foreach ($didList as $row) {
            $rawVmn = (string)$row->virtual_number;
            $vmnFmt = str_starts_with($rawVmn, '+91') ? $rawVmn : '+91' . (str_starts_with($rawVmn, '91') && strlen($rawVmn) > 10 ? substr($rawVmn, 2) : $rawVmn);
            $dids[] = [
                'number' => $row->virtual_number,
                'display' => $vmnFmt,
                'is_default' => ($row->virtual_number == $defaultNumber)
            ];
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'type' => $finalType,
            'channel' => $finalType,
            'detected' => $detectedType,
            'click2call' => ($finalType === 'both' || $finalType === 'click2call'),
            'webrtc' => ($finalType === 'both' || $finalType === 'webrtc'),
            'dids' => $dids,
            'mobile' => $clean10
        ]);
    }

    /**
     * Live Click2Call status for the Calling Workspace, from REAL provider data only:
     * the provider's call record (stored, or fetched live from the gateway). Nothing is inferred from
     * elapsed time. A final record is saved through CallRecordingService::processWebhook (same path as
     * the provider callback), which is idempotent - repeated or late events never duplicate records.
     *
     * Responses: calling | ringing | connected (customer answered, call live) |
     *            ended (customer had answered; final talk time) | busy | no_answer | failed
     */
    public function callStatus(Request $request)
    {
        $id = $request->get('id');
        $uuid = $request->get('uuid');
        $isCampaign = $request->boolean('is_campaign') || ($request->input('source') === 'campaign');

        if (!$id && !$uuid) {
            return response()->json(['status' => 'unknown', 'message' => 'No call ID provided', 'final' => false]);
        }

        // Campaign calls live in call_logs, manual calls in dialer_logs (ids overlap - never cross-look-up)
        if ($isCampaign) {
            $log = $id ? \App\Models\CallLog::find($id) : null;
            if (!$log && $uuid) $log = \App\Models\CallLog::where('reference_id', 'like', $uuid . '%')->first();
        } else {
            $log = $id ? DialerLog::find($id) : null;
            if (!$log && $uuid) $log = DialerLog::where('uuid', 'like', $uuid . '%')->orderBy('id', 'desc')->first();
        }
        if (!$log) {
            return response()->json(['status' => 'calling', 'message' => 'Calling...', 'final' => false]);
        }

        // 1. Stored provider record, 2. live gateway lookup
        $payload = is_array($log->click_response) ? $log->click_response : json_decode((string)$log->click_response, true);
        $payload = is_array($payload) && (isset($payload['A_DIAL_STATUS']) || isset($payload['B_DIAL_STATUS']) || isset($payload['EVENT_TYPE'])) ? $payload : null;
        $facts = $payload ? \App\Services\CallRecordingService::providerCallFacts($payload) : null;

        if ((!$facts || !$facts['ended']) && !empty($log->reference_id) && !empty($log->vmn_used)) {
            $live = $this->fetchGatewayCallRecord($log->reference_id, $log->vmn_used);
            if ($live) {
                $payload = $live;
                $facts = \App\Services\CallRecordingService::providerCallFacts($payload);
            }
        }

        if (!$facts) {
            return response()->json(['status' => 'calling', 'message' => 'Calling...', 'final' => false]);
        }

        if ($facts['ended']) {
            // Persist the real outcome, talk time, end time and recording (idempotent)
            app(\App\Services\CallRecordingService::class)->processWebhook($payload);
            $outcome = $facts['outcome'] ?? 'completed';
            $map = [
                'completed' => ['ended', 'Call Ended'],
                'busy' => ['busy', 'Customer line is busy'],
                'no-answer' => ['no_answer', 'Customer did not answer'],
                'failed' => ['failed', $facts['agent_answered'] === false ? 'Your phone did not answer, the customer was not dialled' : 'Call failed'],
            ];
            [$status, $message] = $map[$outcome] ?? ['failed', 'Call failed'];
            return response()->json([
                'status' => $status,
                'message' => $message,
                'final' => true,
                'duration' => $facts['talk'],
                'duration_formatted' => \App\Helpers\Helper::formatDuration($facts['talk']),
                'disconnected_by' => $facts['disconnected_by'],
            ]);
        }

        if ($facts['customer_connected_at']) {
            return response()->json([
                'status' => 'connected',
                'message' => 'Call Successful / Connected',
                'final' => false,
                'connected_for' => $facts['customer_connected_at']->diffInSeconds(Carbon::now(), true),
            ]);
        }
        if ($facts['agent_answered']) {
            return response()->json(['status' => 'ringing', 'message' => 'Ringing customer...', 'final' => false]);
        }
        return response()->json(['status' => 'calling', 'message' => 'Calling...', 'final' => false]);
    }

    /**
     * The provider's record for a Click2Call call (null when it has none yet or is unreachable).
     */
    private function fetchGatewayCallRecord(string $refId, string $vmn): ?array
    {
        try {
            $context = stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => ['method' => 'GET', 'header' => "Secret-Key: ffcab6c009f568588842b54f15de6efb\r\n", 'timeout' => 3],
            ]);
            $raw = @file_get_contents("https://obd.ametecsindia.com/admin/click2call_data/{$refId}/{$vmn}", false, $context);
            $json = $raw ? json_decode($raw, true) : null;
            if (!isset($json['data']['response'])) {
                return null;
            }
            $data = $json['data'];
            $resp = is_array($data['response']) ? $data['response'] : json_decode((string)$data['response'], true);
            if (!is_array($resp)) {
                return null;
            }
            $resp['ref_id'] = $refId;
            $resp['call_id'] = $resp['call_id'] ?? $refId;
            if ($url = \App\Services\CallRecordingService::validRecordingUrl($data['recording_url'] ?? ($resp['RecordVoice'] ?? null))) {
                $resp['recording_url'] = $url;
            }
            return $resp;
        } catch (\Throwable $e) {
            return null;
        }
    }
    /**
     * Update call state dynamically on every state change
     * (initiated, ringing, answered, completed, failed, busy, no-answer)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCallState(Request $request)
    {
        $id = $request->input('id');
        $uuid = $request->input('uuid');
        $providerCallId = $request->input('provider_call_id');
        $state = strtolower(trim((string)$request->input('state', $request->input('status', ''))));
        $duration = $request->input('duration', 0);
        $leadId = $request->input('lead_id');
        $campaignId = $request->input('campaign_id');
        $recordingUrl = $request->input('recording_url');
        $isCampaign = $request->boolean('is_campaign') || ($request->input('source') === 'campaign');

        try {
            $callLog = null;
            $dialerLog = null;

            if ($isCampaign) {
                if (!empty($id)) $callLog = \App\Models\CallLog::find($id);
                if (!$callLog && !empty($providerCallId)) $callLog = \App\Models\CallLog::where('provider_call_id', $providerCallId)->first();
                if (!$callLog && !empty($uuid)) $callLog = \App\Models\CallLog::where('reference_id', 'like', $uuid . '%')->first();
            } else {
                if (!empty($id)) $dialerLog = DialerLog::find($id);
                if (!$dialerLog && !empty($providerCallId)) $dialerLog = DialerLog::where('provider_call_id', $providerCallId)->first();
                if (!$dialerLog && !empty($uuid)) $dialerLog = DialerLog::where('uuid', 'like', $uuid . '%')->orderBy('id', 'desc')->first();
            }

            // Fallback cross-search if not found yet
            if (!$callLog && !$dialerLog) {
                if (!empty($id)) {
                    $callLog = \App\Models\CallLog::find($id);
                    if (!$callLog) $dialerLog = DialerLog::find($id);
                }
                if (!$callLog && !$dialerLog && !empty($providerCallId)) {
                    $callLog = \App\Models\CallLog::where('provider_call_id', $providerCallId)->first();
                    if (!$callLog) $dialerLog = DialerLog::where('provider_call_id', $providerCallId)->first();
                }
                if (!$callLog && !$dialerLog && !empty($uuid)) {
                    $callLog = \App\Models\CallLog::where('reference_id', 'like', $uuid . '%')->first();
                    if (!$callLog) $dialerLog = DialerLog::where('uuid', 'like', $uuid . '%')->orderBy('id', 'desc')->first();
                }
            }

            if (!$callLog && !$dialerLog) {
                return response()->json(['success' => false, 'message' => 'Call log record not found'], 404);
            }

            $normState = ($state === 'no_answer') ? 'no-answer' : ($state === 'ended' ? 'completed' : $state);

            if ($callLog) {
                $callLog->call_source = 'campaign';
                if (!empty($normState)) {
                    $callLog->call_state = $normState;
                }
                $durVal = Helper::durationToSeconds($duration);
                if ($normState === 'completed') {
                    // Real talk time only; if unknown it stays 0 until the provider's call-end data arrives
                    if ($durVal > 0) {
                        $callLog->duration = $durVal;
                    }
                    if ($callLog->call_type === 'webrtc' || $callLog->call_type === 'sip') {
                        $callLog->sip_end_time = Carbon::now();
                    }
                    if (empty($callLog->end_time)) {
                        $callLog->end_time = Carbon::now();
                    }
                } elseif (in_array($normState, ['busy', 'no-answer', 'failed', 'cancelled']) && empty($callLog->end_time)) {
                    $callLog->end_time = Carbon::now();
                }

                if (!empty($campaignId) && empty($callLog->campaign_id)) {
                    $callLog->campaign_id = $campaignId;
                }
                if (!empty($leadId) && empty($callLog->lead_id)) {
                    $callLog->lead_id = $leadId;
                }
                if (!empty($recordingUrl)) {
                    $callLog->click_recording_url = $recordingUrl;
                }
                $callLog->save();

                if ($normState === 'completed') {
                    \App\Services\CallRecordingService::storeOrUpdateRecording('Campaign Calling', $callLog, [
                        'duration' => $callLog->duration,
                        'recording_url' => $callLog->click_recording_url ?: null,
                        'call_status' => 'completed'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'id' => $callLog->id,
                    'is_campaign' => true,
                    'call_state' => $callLog->call_state,
                    'duration' => $callLog->duration
                ]);
            }

            if ($dialerLog) {
                $dialerLog->call_source = 'manual';
                if ($normState === 'connected' || $normState === 'answered') {
                    $dialerLog->call_state = 'answered';
                    if (empty($dialerLog->answer_time)) {
                        $dialerLog->answer_time = Carbon::now();
                    }
                } elseif ($normState === 'completed') {
                    $dialerLog->call_state = 'completed';
                    if (empty($dialerLog->end_time)) {
                        $dialerLog->end_time = Carbon::now();
                    }
                    // Talk time: reported value, else connected -> end time; otherwise leave 0 for the provider callback
                    $durVal = Helper::durationToSeconds($duration);
                    if ($durVal <= 0 && empty($dialerLog->time_taken) && !empty($dialerLog->answer_time)) {
                        $durVal = (int)Carbon::parse($dialerLog->answer_time)->diffInSeconds(Carbon::parse($dialerLog->end_time), true);
                    }
                    if ($durVal > 0) {
                        $dialerLog->time_taken = $durVal;
                        $dialerLog->duration = Helper::formatDuration($durVal);
                    }
                } elseif (in_array($normState, ['busy', 'no-answer', 'failed', 'cancelled', 'ringing', 'initiated'])) {
                    $dialerLog->call_state = $normState;
                    if (in_array($normState, ['busy', 'no-answer', 'failed', 'cancelled']) && empty($dialerLog->end_time)) {
                        $dialerLog->end_time = Carbon::now();
                    }
                }

                if (!empty($leadId) && empty($dialerLog->lead_id)) {
                    $dialerLog->lead_id = $leadId;
                }
                if (!empty($recordingUrl)) {
                    $dialerLog->click_recording_url = $recordingUrl;
                }

                $dialerLog->save();

                if ($normState === 'completed') {
                    \App\Services\CallRecordingService::storeOrUpdateRecording('Dialing', $dialerLog, [
                        'duration' => $dialerLog->time_taken,
                        'recording_url' => $dialerLog->click_recording_url ?: null,
                        'file_path' => $dialerLog->file_path,
                        'call_status' => 'completed'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'id' => $dialerLog->id,
                    'is_campaign' => false,
                    'call_state' => $dialerLog->call_state,
                    'duration' => $dialerLog->duration,
                    'time_taken' => $dialerLog->time_taken
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[DialerController] updateCallState error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to update call state'], 500);
        }
    }



    /**
     * Get user enabled calling channels (WebRTC, Click2Call)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChannelConfig(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $c2cEnabled = $user->canClick2Call();
        $webrtcEnabled = ($user->webrtc_channel == 1);

        $channels = [];
        if ($webrtcEnabled) $channels[] = 'webrtc';
        if ($c2cEnabled) $channels[] = 'click2call';

        return response()->json([
            'status' => 'success',
            'success' => true,
            'webrtc_enabled' => (bool)$webrtcEnabled,
            'click2call_enabled' => (bool)$c2cEnabled,
            'webrtc' => (bool)$webrtcEnabled,
            'click2call' => (bool)$c2cEnabled,
            'channels' => $channels,
            'count' => count($channels),
            'default' => $c2cEnabled ? 'click2call' : ($webrtcEnabled ? 'webrtc' : null),
            'default_channel' => $c2cEnabled ? 'click2call' : ($webrtcEnabled ? 'webrtc' : null),
            'click2call_agent_number' => $c2cEnabled ? $user->click2CallAgentNumber() : null,
            'click2call_message' => $c2cEnabled ? null : ((int)$user->c2c_channel === 1
                ? 'Click2Call is enabled but no agent mobile number is saved for you.'
                : 'Click2Call is not enabled for your account.'),
        ]);
    }

    /**
     * Get real Today's Performance metrics for logged-in user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodayPerformance(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $tz = 'Asia/Kolkata';
        $startOfDay = Carbon::now($tz)->startOfDay();
        $endOfDay = Carbon::now($tz)->endOfDay();

        $dialerLogsToday = DB::table('dialer_logs')
            ->where('created_by', $user->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();

        $callsMade = $dialerLogsToday->count();

        $connectedLogs = $dialerLogsToday->filter(function($log) {
            $dur = (int)($log->time_taken ?? ($log->duration ?? 0));
            if ($dur > 0) return true;
            if (isset($log->call_state) && ($log->call_state === 'answered' || $log->call_state === 'completed')) return true;
            if (!empty($log->click_response)) {
                $resp = is_array($log->click_response) ? $log->click_response : json_decode($log->click_response, true);
                $aSt = strtolower($resp['A_DIAL_STATUS'] ?? '');
                $bSt = strtolower($resp['B_DIAL_STATUS'] ?? '');
                if (($aSt === 'connected' || $aSt === 'answered') && ($bSt === 'connected' || $bSt === 'answered')) {
                    return true;
                }
            }
            return false;
        });

        $connectedCount = $connectedLogs->count();

        $totalDuration = $connectedLogs->sum(function($log) {
            return (int)($log->time_taken ?? 0) > 0 ? (int)$log->time_taken : \App\Helpers\Helper::durationToSeconds($log->duration ?? 0);
        });
        $avgSeconds = $connectedCount > 0 ? round($totalDuration / $connectedCount) : 0;
        $avgTalkTime = \App\Helpers\Helper::formatDuration($avgSeconds);

        $followupStatusIds = DB::table('statuses')
            ->where('name', 'like', '%follow%')
            ->pluck('id')
            ->toArray();

        $followupsCount = $dialerLogsToday->whereIn('status_id', $followupStatusIds)->count();
        $leadFollowups = DB::table('leads')
            ->where('last_actioned_by', $user->id)
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->whereNotNull('follow_up_on')
            ->count();
        $totalFollowups = max($followupsCount, $leadFollowups);

        $target = (int)($user->daily_target ?? 50);
        if ($target <= 0) $target = 50;
        $pctCompleted = min(100, round(($callsMade / $target) * 100));

        $data = [
            'calls_made' => $callsMade,
            'connected' => $connectedCount,
            'avg_talk_time' => $avgTalkTime,
            'followups' => $totalFollowups,
            'target' => $target,
            'percent_completed' => $pctCompleted
        ];

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $data,
            'calls_made' => $callsMade,
            'connected' => $connectedCount,
            'avg_talk_time' => $avgTalkTime,
            'followups' => $totalFollowups,
            'target' => $target,
            'percent_completed' => $pctCompleted,
            'pct_completed' => $pctCompleted
        ]);
    }

    /**
     * Get user's last 10 real calls for Recent Activity
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentActivity(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $logs = DB::table('dialer_logs')
            ->leftJoin('statuses', 'statuses.id', '=', 'dialer_logs.status_id')
            ->where('dialer_logs.created_by', $user->id)
            ->where('dialer_logs.call_source', 'manual')
            ->select('dialer_logs.*', 'statuses.name as status_name')
            ->orderBy('dialer_logs.id', 'desc')
            ->take(10)
            ->get();

        $phoneNumbers = $logs->pluck('phone_number')->filter()->unique()->toArray();
        $leads = [];
        if (!empty($phoneNumbers)) {
            $leads = DB::table('leads')
                ->where(function($q) use ($phoneNumbers) {
                    $q->whereIn('phonenumber', $phoneNumbers)
                      ->orWhereIn('phonenumber_2', $phoneNumbers);
                })
                ->pluck('name', 'phonenumber')
                ->toArray();
        }

        $activities = $logs->map(function($log) use ($leads) {
            $phone = $log->phone_number;
            $name = $leads[$phone] ?? null;
            if (!$name && strlen($phone) > 10) {
                $clean10 = substr($phone, -10);
                $name = $leads[$clean10] ?? null;
            }

            $dur = (int)($log->time_taken ?? ($log->duration ?? 0));
            $statusName = $log->status_name;
            if (empty($statusName)) {
                if ((isset($log->call_state) && in_array($log->call_state, ['answered', 'completed'])) || $dur > 0) {
                    $statusName = 'Connected';
                } elseif (isset($log->call_state) && $log->call_state === 'busy') {
                    $statusName = 'Busy';
                } elseif (isset($log->call_state) && $log->call_state === 'no-answer') {
                    $statusName = 'No Answer';
                } elseif (isset($log->call_state) && $log->call_state === 'failed') {
                    $statusName = 'Failed';
                } elseif (!empty($log->click_response)) {
                    $resp = json_decode($log->click_response, true);
                    $bSt = strtolower($resp['B_DIAL_STATUS'] ?? '');
                    if (str_contains($bSt, 'busy')) $statusName = 'Busy';
                    elseif (str_contains($bSt, 'no') || str_contains($bSt, 'unanswered')) $statusName = 'No Answer';
                    else $statusName = 'Connected';
                } else {
                    $statusName = 'Dialed';
                }
            }

            $badgeColor = 'blue';
            $sLower = strtolower($statusName);
            if (str_contains($sLower, 'connect')) $badgeColor = 'green';
            elseif (str_contains($sLower, 'follow')) $badgeColor = 'amber';
            elseif (str_contains($sLower, 'busy') || str_contains($sLower, 'fail') || str_contains($sLower, 'no')) $badgeColor = 'red';

            $timeAgo = Carbon::parse($log->created_at)->diffForHumans();

            return [
                'id' => $log->id,
                'phone' => $phone,
                'phone_number' => $phone,
                'name' => $name ?: $phone,
                'lead_name' => $name ?: $phone,
                'display_title' => $name ? $name : $phone,
                'status' => $statusName,
                'badge_color' => $badgeColor,
                'badge_class' => $badgeColor,
                'time_ago' => $timeAgo,
                'duration' => \App\Helpers\Helper::formatDuration($dur)
            ];
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $activities,
            'activities' => $activities
        ]);
    }

    /**
     * Quick Action: Add note
     */
    public function quickNote(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthenticated'], 401);

        $note = trim((string)$request->input('note', ''));
        $callId = $request->input('call_id');

        if (empty($note)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Note cannot be empty'], 422);
        }

        $lead = $this->quickActionLead($request);

        $formattedNote = "[" . Carbon::now()->format('d-M-Y H:i') . " by " . $user->name . "]: " . $note;

        if ($lead) {
            $lead->remarks = !empty($lead->remarks) ? ($formattedNote . "\n" . $lead->remarks) : $formattedNote;
            $lead->last_actioned_by = $user->id;
            $lead->last_actioned_date = Carbon::now();
            $lead->save();
        }

        if (!empty($callId)) {
            $dLog = DialerLog::find($callId);
            if ($dLog) {
                $dLog->comments = !empty($dLog->comments) ? ($dLog->comments . " | " . $note) : $note;
                $dLog->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Note saved successfully'
        ]);
    }

    /**
     * Quick Action: Follow-up
     */
    public function quickFollowup(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthenticated'], 401);

        [$dt, $error] = $this->followupFromRequest($request, false);
        if ($error || !$dt) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => $error ?: 'Please select follow-up date and time'], 422);
        }

        $lead = $this->quickActionLead($request);
        // Follow-ups are lead records: never report success when there is no lead to attach it to
        if (!$lead) {
            return response()->json(['status' => 'error', 'success' => false,
                'message' => 'No lead found for this number. Add it as a lead first to schedule a follow-up.'], 422);
        }

        $role = $user->permissionsGroup->name ?? '';
        if (in_array($role, ['Telecaller', 'Sales Person'], true)) {
            $owner = $role === 'Telecaller' ? $lead->assigned_to : $lead->sales_person_id;
            if (!empty($owner) && (int)$owner !== (int)$user->id && (int)$lead->is_public !== 1) {
                return response()->json(['status' => 'error', 'success' => false, 'message' => 'This lead is assigned to another agent.'], 403);
            }
        }

        $remarks = trim((string)$request->input('remarks', ''));
        DB::transaction(fn () => $lead->scheduleFollowup($dt, $user, $remarks));

        return response()->json([
            'status' => 'success',
            'success' => true,
            'lead_id' => $lead->id,
            'follow_up_on' => $dt->format('Y-m-d H:i:s'),
            'message' => 'Follow-up saved for ' . $lead->name . ' on ' . $dt->format('d-M-Y h:i A'),
        ]);
    }

    /**
     * Quick Action: Add Tag
     */
    public function quickTag(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthenticated'], 401);

        $tag = trim((string)$request->input('tag', ''));

        if (empty($tag)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Tag cannot be empty'], 422);
        }

        $lead = $this->quickActionLead($request);

        if ($lead) {
            $existing = array_filter(array_map('trim', explode(',', (string)$lead->tags)));
            if (!in_array($tag, $existing)) {
                $existing[] = $tag;
                $lead->tags = implode(', ', $existing);
                $lead->save();
            }
            return response()->json([
                'status' => 'success',
                'success' => true,
                'tags' => $lead->tags,
                'message' => "Tag '{$tag}' added successfully"
            ]);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'tags' => $tag,
            'message' => "Tag '{$tag}' assigned"
        ]);
    }

    /**
     * Quick Action: Phone History
     */
    public function phoneHistory(Request $request)
    {
        $phone = $request->input('phone') ?: $request->input('mobile');
        $leadId = $request->input('lead_id');

        if (empty($phone) && empty($leadId)) {
            return response()->json(['status' => 'success', 'success' => true, 'data' => [], 'logs' => [], 'count' => 0]);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
        $clean10 = strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        $query = DB::table('dialer_logs')
            ->leftJoin('users', 'users.id', '=', 'dialer_logs.created_by')
            ->leftJoin('statuses', 'statuses.id', '=', 'dialer_logs.status_id')
            ->select('dialer_logs.*', 'users.name as agent_name', 'statuses.name as status_name')
            ->where(function($q) use ($cleanPhone, $clean10) {
                if (!empty($cleanPhone)) {
                    $q->where('dialer_logs.phone_number', 'like', "%{$clean10}%");
                }
            })
            ->orderBy('dialer_logs.id', 'desc')
            ->take(20);

        $logs = $query->get()->map(function($row) {
            $timeTaken = (int)($row->time_taken ?? ($row->duration ?? 0));
            $formattedTime = Carbon::parse($row->created_at)->format('d-M-Y H:i');
            return [
                'id' => $row->id,
                'created_at' => $formattedTime,
                'date' => $formattedTime,
                'agent_name' => $row->agent_name ?: 'Agent',
                'agent' => $row->agent_name ?: 'Agent',
                'status' => $row->status_name ?: ($timeTaken > 0 ? 'Connected' : 'Dialed'),
                'duration' => \App\Helpers\Helper::formatDuration($timeTaken > 0 ? $timeTaken : ($row->duration ?? 0)),
                'disposition' => $row->status_name ?: '—',
                'comments' => $row->comments ?: $row->feedback,
                'recording_url' => $row->click_recording_url ?: ($row->file_path ? asset($row->file_path) : null)
            ];
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'phone' => $phone,
            'count' => count($logs),
            'data' => $logs,
            'logs' => $logs
        ]);
    }
}