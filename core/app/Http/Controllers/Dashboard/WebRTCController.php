<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\Common;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\ImageUpload;
use App\Models\Lead;
use App\Models\Review;
use Carbon\Carbon;
use App\Models\DialerLog;
use Auth;
use File;
use App\Models\Click2CallLog;
use App\Models\WebmasterSection;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\DialerLogs;
use Illuminate\Support\Facades\Log;
use App\Classes\Reply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class WebRTCController extends Controller
{
    public function loginSmartPing(Request $request)
    {
        try {
            $debugId = uniqid('smartping_');
            \Log::debug("[$debugId] Starting SmartPing login process");

            if (!auth()->check()) {
                \Log::error("[$debugId] SmartPing login attempt by unauthenticated user");
                abort(401, 'Unauthenticated');
            }

            $user = auth()->user();
            
            \Log::debug("[$debugId] SmartPing login initiated for user ID: {$user->id}");

            if (empty($user->smartping_user_id) || empty($user->smartping_password)) {
                \Log::error("[$debugId] Missing SmartPing credentials for user ID: {$user->id}");
                abort(400, 'SmartPing credentials not configured for this user');
            }

            // Initialize cURL session for SSO
            $ssoUrl = 'https://smartcloud.smartping.io/api/sso-login';
            $ch = curl_init($ssoUrl);
            
            $payload = json_encode([
                'username' => (string)$user->smartping_user_id,
                'password' => $user->smartping_password,
            ]);

            $cookieFile = tempnam(sys_get_temp_dir(), 'smartping_cookie_'.$debugId);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
            ]);

            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                unlink($cookieFile);
                \Log::error("[$debugId] cURL error: {$error}");
                abort(500, 'SSO request failed');
            }

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            
            curl_close($ch);

            $data = json_decode($body, true);
            if (!$data || !isset($data['redirect_url'])) {
                \Log::error("[$debugId] Invalid response from SmartPing", ['body' => $body]);
                abort(500, 'Invalid response from SmartPing');
            }

            // Parse token from redirect URL
            $query = parse_url($data['redirect_url'], PHP_URL_QUERY);
            parse_str($query, $params);
            $token = $params['token'] ?? null;

            if (!$token) {
                \Log::error("[$debugId] No token in redirect URL", ['redirect_url' => $data['redirect_url']]);
                abort(500, 'No authentication token received');
            }

            // Return both URL and token
            return response()->json([
                'redirect_url' => $data['redirect_url'],
                'token' => $token
            ]);

        } catch (\Exception $e) {
            \Log::error("SmartPing login error: " . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
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
        $callCheck = \App\Services\CallingRestrictionService::canPlaceOutboundCall($request->phonenumber, Auth::user(), $request->call_type, $request->call_type !== 'webrtc');   // WebRTC: counted by webrtcCall
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

            if(!isset($full_response['data']['crm_id']))
            {
                return json_encode(['success' => false, 'error' => true, 'message' => 'CRM ID is not available']);
            }


        $Lead = Lead::find($request->lead_id);
       
        $CallLog = new CallLog;
        $CallLog->phonenumber = $request->phonenumber;
        $CallLog->call_type = $request->call_type;
        $CallLog->lead_id = $request->lead_id;
        $CallLog->campaign_id = $Lead->campaign_id ?? ($campaign->id ?? null);
        $CallLog->call_source = 'campaign';
        $CallLog->call_state = 'initiated';
        $CallLog->start_time = Carbon::now();
        $CallLog->current_status = $Lead->status;
        $CallLog->duration = 0;
        
        $CallLog->verified = 0;
        if($request->call_type == 'webrtc')
        {
            $did = DB::select("select * from leadger_obd_did where virtual_number = '".$request->vmn."'");
            $CallLog->vmn_used = $request->vmn;
            $CallLog->verified = '1';
        }

        $CallLog->created_by = Auth::user()->id;
        $CallLog->created_at = Carbon::now();
        $CallLog->save();
        $referenceId = $request->lead_id.'_M'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified;
        $CallLog->reference_id = $referenceId;
        $CallLog->provider_call_id = $referenceId;
        $CallLog->save();

   
            if($request->default_wvmn == 'true')
            {
                $userModel = User::find(Auth::user()->id);
                $userModel->webrtc_default_number = $request->vmn;
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
                "agent_id" => Auth::user()->smartping_user_id,
                "customer_number" => $request->phonenumber,
                "ref_id" => $request->lead_id.'_M'.$CallLog->id.'_'.$full_response['data']['crm_id'].'_'.(Auth::user()->id).'_'.$CallLog->verified,
                "record" => 1,
                "recording" => 1,
                "record_call" => 1,
            );
           $response=$this->webrtcCall($callfields, $CallLog);
        $responseContent = $response->getContent(); // get the JSON string
        $api_response = json_decode($responseContent, true); // decode to array

        if (!($api_response['success'] ?? false)) 
            return $response;

        $providerCallId = $api_response['response']['call_id'] ?? ($api_response['call_id'] ?? ($api_response['message'] ?? $CallLog->reference_id));
        $CallLog->provider_call_id = $providerCallId;
        $CallLog->save();

       return response()->json(['call_log_id' => $CallLog->id, 
                         'success' => true,
                         'provider_call_id' => $providerCallId,
                         'sip_id'       => $this->sipValue($api_response, 'agent_id', Auth::user()->smartping_user_id),
                        'sip_ip'       => $this->sipDomain($api_response),
                        'sip_ws'       => $this->sipValue($api_response, 'websocket', config('services.sip.websocket')),
                        'sip_port'     => '7443',
                        'gateway_prefix' => $api_response['response']['gateway_prefix'] ?? '',
                        'call_id'      => $providerCallId,
                        'ref_id'       => $api_response['response']['ref_id'] ?? '',
                        'did'          => $api_response['response']['did'] ?? '',
                        'gateway'      => $api_response['response']['sip_gateway'] ?? '',
                        'headers'      => $api_response['response']['headers'] ?? '',
                        'sip_password' => Auth::user()->smartping_password
                        ]);


        
    }
     public function webrtcdialer(Request $request)
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
        $callCheck = \App\Services\CallingRestrictionService::canPlaceOutboundCall($request->mobile, $user, 'webrtc', false);   // counted by webrtcdialerCall
        if (!$callCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $callCheck['message'],
                'reason' => $callCheck['reason'] ?? 'restriction'
            ], $callCheck['code'] ?? 403);
        }

        // The VMN must be configured for WebRTC (Add Voucher / Smart31) and not expired
        $vmnCheck = \App\Services\VmnService::check($user, $request->vmn, 'webrtc');
        if (!$vmnCheck['ok']) {
            return response()->json(['success' => false, 'message' => $vmnCheck['message']], 422);
        }

        $uuid = (string)Str::uuid();
        $uuid = substr($uuid, 0, 8); 
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        if(!isset($full_response['data']['crm_id']))
        {
            return response()->json(['success' => false, 'message' => 'CRM ID is not available'], 422);
        }

        $did = DB::select("select * from leadger_obd_did where virtual_number = '".$request->vmn."'");
        $user = Auth::user();
        if($request->default_vmn == 'true')
        {
            // $userModel = User::find(Auth::user()->id);
            $user->c2c_default_number = $request->vmn;
            $user->save();
        }
        
        $cleanMobile = preg_replace('/[^0-9]/', '', $request->mobile);
        if (strlen($cleanMobile) == 12 && str_starts_with($cleanMobile, '91')) {
            $cleanMobile = substr($cleanMobile, 2);
        }
        $targetMobile = $cleanMobile ?: $request->mobile;

        $isCampaignCall = ($request->input('source') === 'campaign' || $request->input('tab') === 'campaign');

        if ($isCampaignCall) {
            $leadId = !empty($request->lead_id) ? $request->lead_id : null;
            $campaignId = !empty($request->campaign_id) ? $request->campaign_id : null;
        } else {
            // For manual calls, campaign_id and lead_id are null (or linked only if the dialed number matches an existing lead)
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

        $crmId = $full_response['data']['crm_id'];
        $referenceId = $uuid . '_d_' . $crmId . '_' . $user->id . '_';

        try {
            if ($isCampaignCall) {
                $callLog = new \App\Models\CallLog();
                $callLog->lead_id = $leadId;
                $callLog->campaign_id = $campaignId;
                $callLog->phonenumber = $targetMobile;
                $callLog->call_type = 'webrtc';
                $callLog->call_source = 'campaign';
                $callLog->call_state = 'initiated';
                $callLog->created_by = $user->id;
                $callLog->vmn_used = $request->vmn;
                $callLog->reference_id = $referenceId;
                $callLog->provider_call_id = $referenceId;
                $callLog->verified = 1;
                $callLog->duration = 0;
                $callLog->start_time = \Carbon\Carbon::now();
                $callLog->created_at = \Carbon\Carbon::now();
                $callLog->save();


                $activeRecord = $callLog;
            } else {
                $log = new DialerLog();
                $log->phone_number = $targetMobile;
                $log->created_by = $user->id;
                $log->lead_id = $leadId ?: null; // manual call: no campaign (DialerLog enforces this)
                $log->time_taken = 0;
                $log->duration = '00:00';
                $log->uuid = $uuid . '_d_' . $crmId . '_' . $user->id . '_';
                $log->vmn_used = $request->vmn;
                $log->call_type = 'webrtc';
                $log->call_source = 'manual';
                $log->verified = 1;
                $log->call_state = 'initiated';
                $log->start_time = \Carbon\Carbon::now();
                $log->reference_id = $referenceId;
                $log->provider_call_id = $referenceId;
                $log->save();


                $activeRecord = $log;
            }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('[WebRTC] Failed creating initial call record: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);
                return response()->json(['success' => false, 'message' => 'Failed to initialize call record: ' . $e->getMessage()], 500);
            }

            Click2CallLog::create([
                'uuid' => $uuid,
                'reference_id' => $referenceId,
                'user_id' => $user->id,
                'vmn' => $request->vmn,
            ]);

            $callfields = array(
                "did" => $request->vmn,
                "agent_id" => Auth::user()->smartping_user_id,
                "customer_number" => $request->mobile,
                "ref_id" => $referenceId,
                "record" => 1,
                "recording" => 1,
                "record_call" => 1,
            );

            $response = $this->webrtcdialerCall($callfields, $activeRecord);
            $responseContent = $response->getContent();
            $api_response = json_decode($responseContent, true);

            if (!($api_response['success'] ?? false)) {
                \Illuminate\Support\Facades\Log::error('[WebRTC] Gateway call initiation error', ['resp' => $api_response]);
                return $response;
            }

            $providerCallId = $api_response['response']['call_id'] ?? ($api_response['call_id'] ?? ($api_response['message'] ?? $activeRecord->reference_id));
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
                'sip_id' => $this->sipValue($api_response, 'agent_id', Auth::user()->smartping_user_id),
                'sip_ip' => $this->sipDomain($api_response),
                'sip_ws' => $this->sipValue($api_response, 'websocket', config('services.sip.websocket')),
                'sip_port' => '7443',
                'gateway_prefix' => $api_response['response']['gateway_prefix'] ?? '',
                'call_id' => $providerCallId,
                'ref_id' => $api_response['response']['ref_id'] ?? $referenceId,
                'did' => $api_response['response']['did'] ?? $request->vmn,
                'gateway' => $api_response['response']['sip_gateway'] ?? '',
                'headers' => $api_response['response']['headers'] ?? '',
                'sip_password' => Auth::user()->smartping_password ?? ''
            ]);
        }
    // The gateway's value, or the fallback when it sends none (an empty SIP domain breaks the softphone: "missing or invalid host")
    private function sipValue(?array $api_response, string $key, $fallback): string
    {
        $value = trim((string) ($api_response['response'][$key] ?? ''));
        return $value !== '' ? $value : trim((string) $fallback);
    }

    // SIP host only: the gateway may send "wss://host:7443" or "host:port"
    private function sipDomain(?array $api_response): string
    {
        $domain = $this->sipValue($api_response, 'domain', config('services.sip.domain'));
        $domain = preg_replace('#^[a-z]+://#i', '', $domain);
        return preg_replace('#[:/].*$#', '', $domain);
    }

        public function webrtcdialerCall(array $payload, $Log)
    {
        $payload['record'] = 1;
        $payload['recording'] = 1;
        $payload['record_call'] = 1;

        // The number's daily attempt is counted here, when the call is requested; given back if it does not start
        $targetNumber = $payload['customer_number'] ?? null;
        if ($targetNumber) {
            $check = \App\Services\CallingRestrictionService::canPlaceOutboundCall($targetNumber, Auth::user(), 'webrtc_gateway', true);
            if (!$check['allowed']) {
                return response()->json([
                    'success' => false,
                    'error' => true,
                    'message' => $check['message'],
                    'reason' => $check['reason'] ?? 'restriction',
                ], $check['code'] ?? 403);
            }
        }

        $ch = curl_init("https://sip.telozar.com/api/voip/webrtc_call");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        // IMPORTANT
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);

        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $curlError  = curl_error($ch);

        curl_close($ch);

        $api_response = json_decode($response, true);
        if ($targetNumber && ($curlError || $httpCode < 200 || $httpCode >= 300)) {
            \App\Services\CallingRestrictionService::releaseAttempt($targetNumber);   // the call did not start
        }
   
        if ($httpCode == 200 && isset($api_response['message'])) {
            $Log->webrtc_call_id = $api_response['message'];
        }
        $providerCallId = $api_response['response']['call_id'] ?? ($api_response['call_id'] ?? ($api_response['message'] ?? $Log->reference_id));
        $Log->provider_call_id = $providerCallId;
        $Log->save();

        return response()->json([
            'call_log_id' => $Log->id, 
            'success' => true, 
            'provider_call_id' => $providerCallId,
            'message' => 'WebRTC Call initiated successfully', 
            'response' =>  $api_response
        ]);
    }

    public function webrtcCall(array $payload, CallLog $callLog)
    {
        $payload['record'] = 1;
        $payload['recording'] = 1;
        $payload['record_call'] = 1;

        // The number's daily attempt is counted here, when the call is requested; given back if it does not start
        $targetNumber = $payload['customer_number'] ?? null;
        if ($targetNumber) {
            $check = \App\Services\CallingRestrictionService::canPlaceOutboundCall($targetNumber, Auth::user(), 'webrtc_gateway', true);
            if (!$check['allowed']) {
                return response()->json([
                    'success' => false,
                    'error' => true,
                    'message' => $check['message'],
                    'reason' => $check['reason'] ?? 'restriction',
                ], $check['code'] ?? 403);
            }
        }

        $ch = curl_init("https://sip.telozar.com/api/voip/webrtc_call");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            // IMPORTANT
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);

            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $curlError  = curl_error($ch);

            curl_close($ch);

    $api_response = json_decode($response, true);
    if ($targetNumber && ($curlError || $httpCode < 200 || $httpCode >= 300)) {
        \App\Services\CallingRestrictionService::releaseAttempt($targetNumber);   // the call did not start
    }

    if ($httpCode == 200 && isset($api_response['message'])) {
        $callLog->webrtc_call_id = $api_response['message'];
    }
    $providerCallId = $api_response['response']['call_id'] ?? ($api_response['call_id'] ?? ($api_response['message'] ?? $callLog->reference_id));
    $callLog->provider_call_id = $providerCallId;
    $callLog->save();

    return response()->json([
                         'call_log_id' => $callLog->id, 
                                'success' => true, 
                                'provider_call_id' => $providerCallId,
                                'message' => 'WebRTC Call initiated successfully', 
                                'response' =>  $api_response
    ]);
    }
   
  public function createSmartpingUser($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->smartping_user_id && $user->smartping_password) {
            return response()->json(['status' => 'error', 'message' => 'SmartPing user already exists'], 400);
        }

        $name = $user->first_name . " " . $user->last_name;
        $mobile = $user->contact_number;
        $password = $user->password; // ?? careful: this is hashed. You may need a plain password field if API requires plain text
        $companyId = "ametec";
        $email = $user->email;

        try {
            $postData = [
                'full_name'     => $name,
                'mobile_number' => $mobile,
                'password'      => $password, 
                'company_id'    => $companyId
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://smartcloud.smartping.io/api/add-agent');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $apiResponse = curl_exec($ch);

            if (curl_errno($ch)) {
                \Log::error('SmartPing API error', ['error' => curl_error($ch)]);
                return response()->json(['status' => 'error', 'message' => 'SmartPing API connection failed']);
            }

            curl_close($ch);

            $responseData = json_decode($apiResponse, true);

            if (isset($responseData['user_id']) && isset($responseData['password'])) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'smartping_user_id' => $responseData['user_id'],
                        'smartping_password' => $responseData['password']
                    ]);

                return response()->json(['status' => 'success', 'message' => 'SmartPing user created successfully']);
            } else {
                \Log::warning('Unexpected SmartPing response', ['response' => $apiResponse]);
                return response()->json(['status' => 'error', 'message' => 'Invalid SmartPing response']);
            }

        } catch (\Exception $e) {
            \Log::error('SmartPing API exception', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'SmartPing API exception']);
        }
    }
    public function download(Request $request)
    {
        $url  = $request->query('url');   // original file URL
        $name = $request->query('name');  // desired download name

        if (!$url) {
            abort(400, "Missing file URL");
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data === false || $httpCode !== 200) {
            abort(404, "Recording not found");
        }

        // if no name given, fallback to original
        if (empty($name)) {
            $name = basename(parse_url($url, PHP_URL_PATH));
        }

        return response($data, 200)
            ->header('Content-Type', 'audio/wav')
            ->header('Content-Disposition', 'attachment; filename="'.$name.'"');
    }

     public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        [$webrtcChannels, $licensewebrtcChannels, $channelExpiry] = $this->channelCounts();
        if($webrtcChannels > $licensewebrtcChannels && $licensewebrtcChannels > 0)
        {
            $webrtcChannels = 0;
            DB::Statement("update ".env('DB_TABLE_PREFIX')."users set webrtc_channel = 0 ");
        }
        
        return view("dashboard.webrtc.list", compact("GeneralWebmasterSections", "webrtcChannels", "licensewebrtcChannels", "channelExpiry"));
    }

    public function webrtcData(Request $request)
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
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."users
where webrtc_channel = 1 ";

        if ($q != "") {
            $sql .= " and (name like '%".$q."%' 
                        or click_agent_number like '%".$q."%'  )";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Click2Call = DB::select("SELECT name,click_agent_number,id,webrtc_channel
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");
        
        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Click2Call as $row) {
                $userObj = User::find($row->id);
                $roleName = @$userObj->permissionsGroup->name ?? 'TeleCaller';

                $nestedData = [];
                $nestedData['name'] = '<div class="lg-user-flex"><div class="lg-user-icon-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><span style="font-weight: 600; color: #0F172A;">'.e($row->name).'</span></div>';
                $nestedData['role'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">'.e($roleName).'</span>';
                $nestedData['channel'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">Assigned</span>';
                $nestedData['click_agent_number'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">'.e($row->click_agent_number ?: '-').'</span>';

                $actionurl = route("webrtcEdit", ["id" => $row->id]);
                if ($row->webrtc_channel == 1) {
                    $nestedData['options'] = '
                    <div class="lg-action-btn-group">
                        <a href="'.$actionurl.'" class="lg-action-btn edit" data-id="'.$row->id.'" data-user-id="'.$row->id.'" data-name="'.e($row->name).'" data-number="'.e($row->click_agent_number).'" onclick="if(typeof lgOpenEditChannelModal===\'function\'){lgOpenEditChannelModal('.$row->id.', \'webrtc\', this); return false;}" title="Edit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <form method="POST" action="'.route("webrtcToggle", ["id"=>$row->id]).'" style="display:inline-block; margin:0;">
                            '.csrf_field().'
                            <input type="hidden" name="webrtc_channel" value="0">
                            <button type="submit" class="lg-action-btn delete" title="Turn OFF / Remove WebRTC" onclick="return confirm(\'Are you sure you want to turn off this WebRTC channel?\')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        </form>
                    </div>';
                } else {
                    $nestedData['options'] = '
                    <div class="lg-action-btn-group">
                        <a href="'.$actionurl.'" class="lg-action-btn edit" data-id="'.$row->id.'" data-user-id="'.$row->id.'" data-name="'.e($row->name).'" data-number="'.e($row->click_agent_number).'" onclick="if(typeof lgOpenEditChannelModal===\'function\'){lgOpenEditChannelModal('.$row->id.', \'webrtc\', this); return false;}" title="Edit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <form method="POST" action="'.route("webrtcToggle", ["id"=>$row->id]).'" style="display:inline-block; margin:0;">
                            '.csrf_field().'
                            <input type="hidden" name="webrtc_channel" value="1">
                            <button type="submit" class="lg-action-btn edit" title="Turn ON Channel">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </button>
                        </form>
                    </div>';
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
    public function webrtcToggle(Request $request, $id)
{
    $user = User::findOrFail($id);

    // WebRTC only (the licensed WebRTC channel limit applies when turning on); Click2Call is not touched
    $error = $user->setWebRTC((int)$request->input('webrtc_channel', 0) === 1);
    if ($error) {
        return redirect()->back()->with('errorMessage', $error);
    }
    $user->save();

    return redirect()->back()->with('doneMessage', 'WebRTC status updated successfully.');
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * WebRTC channels in use, licensed channels and expiry, as shown in the stat cards on the WebRTC page.
     *
     * @return array{0:int,1:int,2:string} [used, licensed, expiry Y/m/d]
     */
    private function channelCounts(): array
    {
        $used = User::where('webrtc_channel', 1)->count();
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $licensed = 20;
        $expiry = '2026/09/30';
        if ($settings && !empty($settings->full_response)) {
            $full_reponse = json_decode($settings->full_response, true);
            if (is_array($full_reponse) && isset($full_reponse['data'])) {
                if (array_key_exists('webrtc_channels', $full_reponse['data'])) {
                    $licensed = (int)$full_reponse['data']['webrtc_channels'];
                }
                if (!empty($full_reponse['data']['expiry'])) {
                    $expiry = date('Y/m/d', strtotime($full_reponse['data']['expiry']));
                }
            }
        }
        return [$used, $licensed, $expiry];
    }

    /**
     * Data for the "Add WebRTC Channel" popup on the WebRTC page: the channel counts, the users who do not have
     * WebRTC yet, and the agent numbers already in use (for the duplicate check). Saving goes through store().
     */
    public function create()
    {
        [$used, $licensed, $expiry] = $this->channelCounts();

        $users = User::where(fn ($q) => $q->where('webrtc_channel', 0)->orWhereNull('webrtc_channel'))
            ->orderBy('name')->get(['id', 'name', 'click_agent_number', 'c2c_channel'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'number' => (string)$u->click_agent_number,
                'c2c' => (int)$u->c2c_channel === 1,
            ]);

        $numbers = User::whereNotNull('click_agent_number')->where('click_agent_number', '!=', '')
            ->get(['id', 'name', 'click_agent_number'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'number' => substr(preg_replace('/[^0-9]/', '', $u->click_agent_number), -10)])
            ->filter(fn ($n) => $n['number'] !== '')->values();

        return response()->json([
            'status' => 'success',
            'total' => $licensed,
            'used' => $used,
            'available' => max(0, $licensed - $used),
            'expiry' => $expiry,
            'users' => $users,
            'numbers' => $numbers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required',
            'click_agent_number' => 'nullable|string',
            'webrtc_toggle' => 'nullable',
        ], [
            'user.required' => 'Please select a user.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                    'message' => $validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::find($request->user);
        if (!$user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }
            return redirect()->action('Dashboard\WebRTCController@index')->with('errorMessage', 'User not found');
        }

        // Bulk Assign: turn WebRTC on for the existing users whose mobile / agent number is in the list
        if ($request->boolean('bulk')) {
            return $this->bulkAssign($request);
        }

        // Turn WebRTC on (licensed WebRTC channel limit) without touching the user's Click2Call settings
        $error = $user->setWebRTC(true);
        if ($error) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $error], 422);
            }
            return redirect()->route('webrtc')->with('errorMessage', $error);
        }

        // users.click_agent_number is the phone Click2Call rings: a Click2Call user keeps theirs;
        // otherwise the agent number entered here is stored (digits only)
        $agentNumber = preg_replace('/[^0-9]/', '', (string)$request->input('click_agent_number', ''));
        if ($agentNumber !== '' && (int)$user->c2c_channel !== 1) {
            $user->click_agent_number = $agentNumber;
        }
        $user->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'WebRTC updated',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'click_agent_number' => $user->click_agent_number,
                ]
            ]);
        }

        return redirect()->action('Dashboard\WebRTCController@index')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Bulk Assign WebRTC: the numbers (comma / space / new-line separated) are matched to existing users by
     * mobile or agent number, and WebRTC is turned on for each match. Click2Call settings are not changed.
     */
    private function bulkAssign(Request $request)
    {
        $numbers = collect(preg_split('/[\s,;]+/', (string)$request->input('click_agent_number', '')))
            ->map(fn ($n) => substr(preg_replace('/[^0-9]/', '', $n), -10))
            ->filter(fn ($n) => strlen($n) === 10)
            ->unique()->values();

        $enabled = [];
        $failed = [];
        foreach ($numbers as $num) {
            $matches = User::where(function ($q) use ($num) {
                $q->where('mobile', 'like', "%{$num}")->orWhere('click_agent_number', 'like', "%{$num}");
            })->get();
            if ($matches->isEmpty()) {
                $failed[] = "{$num}: no user with this number";
                continue;
            }
            foreach ($matches as $u) {
                $error = $u->setWebRTC(true);
                if ($error) {
                    $failed[] = "{$u->name}: {$error}";
                    continue;
                }
                $u->save();
                $enabled[$u->id] = $u->name;
            }
        }

        $message = count($enabled) . ' user(s) enabled for WebRTC' . ($enabled ? ': ' . implode(', ', $enabled) : '') . '.'
            . ($failed ? ' Not enabled - ' . implode('; ', $failed) . '.' : '');
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => $enabled ? 'success' : 'error', 'message' => $message], $enabled ? 200 : 422);
        }
        return redirect()->route('webrtc')->with($enabled ? 'doneMessage' : 'errorMessage', $message);
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

        $User = User::find($id);

        if (!$User) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }
            return redirect()->action('Dashboard\WebRTCController@index');
        }

        if (request()->ajax() || request()->wantsJson()) {
            $allUsers = User::select('id', 'name')->orderBy('name', 'asc')->get();
            return response()->json([
                'status' => 'success',
                'user' => [
                    'id' => $User->id,
                    'name' => $User->name,
                    'click_agent_number' => $User->click_agent_number,
                ],
                'users' => $allUsers
            ]);
        }

        $Users = User::where('id', $id)->get();

        return view("dashboard.webrtc.edit", compact("Users", "User", "GeneralWebmasterSections"));
    }

    /**
     * Delete a created resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        if (!\App\Helpers\Helper::canDelete()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        $User = User::find($id);
        if ($User) {
            $User->webrtc_channel = 0;
            $User->save();
        }

        return redirect()->action('Dashboard\WebRTCController@index')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
public function smart31GroupEdit($group)
{
    $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

    $groupData = DB::table('smart31_vmn_numbers')
        ->where('group_name', $group)
        ->first();

    if (!$groupData) {
        return redirect()->back()->with('error', 'Group not found');
    }

    $teams = DB::table('teams')->pluck('name', 'id'); // id => name
    $assignedIds = $groupData->assigned_groups ? explode(',', $groupData->assigned_groups) : [];

    return view('dashboard.smart31.edit', compact(
        "GeneralWebmasterSections",
        'groupData',
        'teams',
        'assignedIds'
    ));
}

    public function getSmart31Details($group)
    {
    
        $numbers = DB::table('smart31_vmn_numbers')
            ->where('group_name', $group)
            ->select('vmn', 'usage_date', 'obd', 'c2c', 'webrtc', 'verified')
            
            ->get();
       

        return response()->json($numbers);
    }
public function smart31GroupUpdate(Request $request)
{
    $request->validate([
        'group_name' => 'required|string',
        'assigned_groups' => 'nullable', // can be string or array
    ]);

    // Fetch existing assigned_groups from DB
    $existing = DB::table('smart31_vmn_numbers')
        ->where('group_name', $request->group_name)
        ->value('assigned_groups'); // e.g., "1,2,3"

    // Convert existing string to array
    $existingIds = $existing ? explode(',', $existing) : [];

    // Convert new input to array if it's string, else use as-is
    $newIds = [];
    if ($request->has('assigned_groups')) {
        if (is_array($request->assigned_groups)) {
            $newIds = $request->assigned_groups;
        } else {
            $newIds = explode(',', $request->assigned_groups);
        }
    }

    // Merge and remove duplicates
    $mergedIds = array_unique(array_merge($existingIds, $newIds));
    $mergedString = implode(',', $mergedIds);

    // Update DB
    DB::table('smart31_vmn_numbers')
        ->where('group_name', $request->group_name)
        ->update([
            'assigned_groups' => $mergedString,
            'updated_at' => now(),
        ]);

    return redirect()->route('obdDid')->with('success', 'Smart31 group updated successfully.');
}
public function getVmn(Request $request)
{
    $today = Carbon::today()->toDateString();
    $userId = auth()->id();

    // Get all group_ids of logged-in user
    $userTeamIds = DB::table('users')
        ->where('id', $userId)
        ->value('team_id'); // single string like "1,2,3"

    $userGroupIds = array_filter(explode(',', $userTeamIds));
  
    // Query VMN where assigned_groups contains any of the user's groups
    $vmnNumber = DB::table('smart31_vmn_numbers')
        ->where('usage_date','=', $today)
        ->where('c2c', '=', '1')

        ->where('webrtc', '=', '0')
     	
  
        ->where(function($query) use ($userGroupIds) {
            foreach($userGroupIds as $groupId) {
                $query->orWhereRaw("FIND_IN_SET(?, assigned_groups)", [$groupId]);
            }
        })
    
        ->value('vmn');

    return response()->json(['vmn' => $vmnNumber]);
}
public function getwVmn(Request $request)
{
	
    $today = Carbon::today()->toDateString();
   
    $userId = auth()->id();
   
    $userTeamIds = DB::table('users')
        ->where('id', $userId)
        ->value('team_id'); // single string like "1,2,3"

    $userGroupIds = array_filter(explode(',', $userTeamIds));
    
    $vmnNumber = DB::table('smart31_vmn_numbers')
        ->where('usage_date','=', $today)
        ->where('c2c', '=', '0')

        ->where('webrtc', '=', '1')


        ->where(function($query) use ($userGroupIds) {
            foreach($userGroupIds as $groupId) {
                $query->orWhereRaw("FIND_IN_SET(?, assigned_groups)", [$groupId]);
            }
        })
        
        ->value('vmn');
    
    return response()->json(['wvmn' => $vmnNumber]);
}
public function ssoLogout(Request $request)
{
    try {

        $user = auth()->user();

        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if($user->smartping_user_id){

            $username = (string) $user->smartping_user_id;

            $payload = json_encode([
                'username' => $username
            ]);

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://smartcloud.smartping.io/api/sso-logout",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ]
            ]);

            $response = curl_exec($curl);
            curl_close($curl);
        }

        return response()->json([
            'success' => true
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


}