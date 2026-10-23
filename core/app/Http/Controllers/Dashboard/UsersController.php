<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\User;
use App\Models\Team;
use App\Models\Payout;
use App\Models\WebmasterSection;
use App\Models\WorkLocation;
use App\Models\Shift;
use App\Models\SmsGateway;
use App\Models\EmailSetting;
use App\Models\UserActiveUsage;
use App\Models\Setting;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UsersController extends Controller
{

    private $uploadPath = "uploads/users/";

    // Define Default Variables

    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Admin color themes a user can pick (header palette button); "original" is the default look. */
    public const THEMES = ['original', 'slate', 'green', 'dark'];

    /**
     * Save the logged-in user's theme (any role may choose their own). POST {theme: original|slate|green|dark}
     */
    public function saveTheme(Request $request)
    {
        $theme = (string)$request->input('theme');
        if (!in_array($theme, self::THEMES, true)) {
            return response()->json(['success' => false, 'message' => 'Unknown theme.'], 422);
        }
        User::where('id', Auth::id())->update(['theme' => $theme]);
        return response()->json(['success' => true, 'theme' => $theme]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('adminHome');
        }

        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        if (@Auth::user()->permissionsGroup->view_status) {
            $Users = User::where('created_by', '=', Auth::user()->id)->orwhere('id', '=', Auth::user()->id)->orderby('id',
                'asc')->paginate(env('BACKEND_PAGINATION'));
            $Permissions = Permissions::where('created_by', '=', Auth::user()->id)->orderby('id', 'asc')->get();
        } else {
            $Users = User::orderby('id', 'asc')->paginate(env('BACKEND_PAGINATION'));
            $Permissions = Permissions::orderby('id', 'asc')->get();
        }
        $Roles = Payout::orderby('id', 'asc')->get();
        $Teams = Team::orderby('name', 'asc')->get();
        $WorkLocations = WorkLocation::orderby('name', 'asc')->get();
        $Shifts = Shift::orderby('name', 'asc')->get();
        $SmsGateways = SmsGateway::orderby('name', 'asc')->get();
        $EmailSettings = EmailSetting::orderby('name', 'asc')->get();
        $telecallerPermission = $Permissions->where('name', 'Telecaller')->first();
        $telecallerPermissionId = $telecallerPermission ? $telecallerPermission->id : null;

        return view("dashboard.users.list", compact("Users", "Permissions", "GeneralWebmasterSections", "Teams", "WorkLocations", "Shifts", "Roles", "SmsGateways", "EmailSettings", "telecallerPermissionId"));
    }

    public function userGroups()
    {
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('adminHome');
        }

        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        if (@Auth::user()->permissionsGroup->view_status) {
            $Permissions = Permissions::where('created_by', '=', Auth::user()->id)->orderby('id', 'asc')->get();
        } else {
            $Permissions = Permissions::orderby('id', 'asc')->get();
        }

        return view("dashboard.permissions.list", compact("Permissions", "GeneralWebmasterSections"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }

        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        $Permissions = Permissions::orderby('id', 'asc')->get();
        $Roles=Payout::orderby('id', 'asc')->get();
        $Teams = Team::orderby('name', 'asc')->get();
        $WorkLocations = WorkLocation::orderby('name', 'asc')->get();
        $Shifts = Shift::orderby('name', 'asc')->get();
        $SmsGateways = SmsGateway::orderby('name', 'asc')->get();
        $EmailSettings = EmailSetting::orderby('name', 'asc')->get();
        $telecallerPermission = $Permissions->where('name', 'Telecaller')->first();
        // Pass null if the permission doesn't exist
        $telecallerPermissionId = $telecallerPermission ? $telecallerPermission->id : null;

        return view("dashboard.users.create", compact("GeneralWebmasterSections", "Permissions", "Teams", "WorkLocations", "Shifts","Roles", "SmsGateways", "EmailSettings","telecallerPermissionId"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        
        $Setting = DB::select("SELECT FROM_BASE64(AES_DECRYPT(user_count, 'MyNicotex@1')) as user_count FROM leadger_settings");
        $Users = User::where('status', '1')->get();
        if(count($Users) >= $Setting[0]->user_count)
        {
            return redirect()->action('Dashboard\UsersController@index')->with('errorMessage', 'License Users limit exceeded');
        }

        if ($request->has('team_id')) {
            $teamInput = $request->team_id;
            if (is_string($teamInput)) {
                $teamInput = array_filter(explode(',', $teamInput), 'strlen');
            } elseif (!is_array($teamInput)) {
                $teamInput = [$teamInput];
            }
            $request->merge(['team_id' => $teamInput]);
        }

        // Accept common formatting (spaces, dashes, brackets) but store only digits with an optional leading +
        if ($request->filled('mobile')) {
            $request->merge(['mobile' => preg_replace('/[\s\-()]/', '', (string)$request->mobile)]);
        }
        $this->validate($request, [
            'photo' => 'mimes:png,jpeg,jpg,gif,svg',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::min(6)],
            'permissions_id' => 'required',
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
            'team_id' => 'required|array|min:1',
            'team_id.*' => 'integer|exists:teams,id',
            'work_location_id' => 'required',
            'shift_id' => 'required',
        ], ['mobile.regex' => 'Enter a valid mobile number: 10 to 15 digits, optionally starting with +.']);


        // Start of Upload Files
        $formFileName = "photo";
        $fileFinalName_ar = "";
        if ($request->$formFileName != "") {
            $fileFinalName_ar = time() . rand(1111,
                    9999) . '.' . $request->file($formFileName)->getClientOriginalExtension();
            $path = $this->getUploadPath();
            $request->file($formFileName)->move($path, $fileFinalName_ar);
        }
      
        $User = new User;
        $User->name = $request->name;
        $User->email = $request->email;
        $User->password = bcrypt($request->password);
        $User->permissions_id = $request->permissions_id;

        $selectedTeamIds = is_array($request->team_id) ? array_map('intval', array_filter($request->team_id)) : [(int)$request->team_id];
        $selectedTeamIds = array_values(array_unique($selectedTeamIds));
        $User->team_id = implode(',', $selectedTeamIds);

        $User->mobile = $request->mobile;
        $User->work_location_id = $request->work_location_id;
        $User->shift_id = $request->shift_id;
        $User->sms_gateway_id = $request->sms_gateway_id ?? 1;
        $User->email_setting_id = $request->email_setting_id ?? 1;
        $User->photo = $fileFinalName_ar;
        $User->connect_email = $request->connect_email;
        $User->connect_password = $request->connect_password;
        $User->status = 1;
        $User->role_id = $request->role_id;
        $User->obd_credits = 0;
        $User->created_by = Auth::user()->id;
        if ($request->has('languages_known')) {
            $User->languages_known = $request->languages_known;
        }
        $User->account_number = $request->account_number;
        $User->ifsc_code = $request->ifsc_code;
        $User->branch_name = $request->branch_name;
        $User->upi_id = $request->upi_id;
        $User->save();

        // Sync many-to-many teams pivot
        $User->teams()->sync($selectedTeamIds);

        // Calling Method in Add Staff (Click2Call and/or WebRTC) -> c2c_channel + click_agent_number and webrtc_channel,
        // which the Click2Call / WebRTC / Call Channels pages and the Calling Workspace read (there is no calling_method column)
        $methods = (array)$request->input('calling_method', []);
        $c2cError = null;
        if (in_array('click2call', $methods, true)) {
            $c2cError = $User->setClick2Call(true, $request->mobile);
        }
        $webrtcError = in_array('webrtc', $methods, true) ? $User->setWebRTC(true) : null;
        if ($User->isDirty(['c2c_channel', 'click_agent_number', 'webrtc_channel'])) {
            $User->save();
        }

        DB::statement("UPDATE leadger_users SET obd_credits = AES_ENCRYPT(TO_BASE64 (0), 'MyNicotex@1') where id = ".$User->id);
     
       
            try {
                $email=$request->email;
                $companyId = config('services.smartping.company_id');
                $postData = [
                    'full_name' => $request->name,
                    'mobile_number' => $request->mobile,
                    'password' => $request->password,
                    'company_id' => $companyId
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
                }

                curl_close($ch);

                $responseData = json_decode($apiResponse, true);

                if (isset($responseData['user_id']) && isset($responseData['password'])) {
                    DB::table('users')
                        ->where('email', $email)
                        ->update([
                            'smartping_user_id' => $responseData['user_id'],
                            'smartping_password' => $responseData['password']
                        ]);
                } else {
                    \Log::warning('Unexpected SmartPing response', ['response' => $apiResponse]);
                }
            } catch (\Exception $e) {
                \Log::error('SmartPing API exception', ['message' => $e->getMessage()]);
            }
      
        $redirect = redirect()->action('Dashboard\UsersController@index')->with('doneMessage', __('backend.addDone'));
        $channelErrors = array_filter([
            $c2cError ? 'Click2Call was not enabled: ' . $c2cError : null,
            $webrtcError ? 'WebRTC was not enabled: ' . $webrtcError : null,
        ]);
        return $channelErrors ? $redirect->with('errorMessage', 'User saved, but ' . implode(' ', $channelErrors)) : $redirect;
    }

    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = Config::get('app.APP_URL') . $uploadPath;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status && @Auth::user()->id != $id) {
            return redirect()->route('NoPermission');
        }
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        $Permissions = Permissions::orderby('id', 'asc')->get();

        if (@Auth::user()->permissionsGroup->view_status) {
            $Users = User::where('created_by', '=', Auth::user()->id)->orwhere('id', '=', Auth::user()->id)->find($id);
        } else {
            $Users = User::find($id);
        }
        
        $Teams = Team::orderby('name', 'asc')->get();
        $Roles = Payout::orderby('id', 'asc')->get();
        $WorkLocations = WorkLocation::orderby('name', 'asc')->get();
        $Shifts = Shift::orderby('name', 'asc')->get();
        $SmsGateways = SmsGateway::orderby('name', 'asc')->get();
        $EmailSettings = EmailSetting::orderby('name', 'asc')->get();

        $aiSettings = \App\Models\CompanyAiSetting::company();

        $managerDeletePermission = Helper::isDeletePermissionEnabled();

        $setting = Setting::first();
        $rmiReportEnabled = $setting ? (bool) ($setting->rmi_report_enabled ?? 1) : true;

        $licenseData = null;
        $licenseKey = env('license', '');
        try {
            $settingsTable = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            if ($settingsTable && !empty($settingsTable->full_response)) {
                $json = json_decode($settingsTable->full_response, true);
                if (isset($json['data'])) {
                    $licenseData = $json['data'];
                }
            }
        } catch (\Exception $e) {
            $licenseData = null;
        }

        if (!empty($Users)) {
            return view("dashboard.users.edit", compact("Users", "Permissions", "GeneralWebmasterSections","Roles", "Teams", "WorkLocations", "Shifts", "SmsGateways", "EmailSettings", "aiSettings", "managerDeletePermission", "rmiReportEnabled", "setting", "licenseData", "licenseKey"));
        } else {
            return redirect()->action('Dashboard\UsersController@index');
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
        if ($request->has('first_name') || $request->has('last_name')) {
            $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
            if (!empty($fullName)) {
                $request->merge(['name' => $fullName]);
            }
        }

        // Check Permissions
        // Accept common formatting (spaces, dashes, brackets) but store only digits with an optional leading +
        if ($request->filled('mobile')) {
            $request->merge(['mobile' => preg_replace('/[\s\-()]/', '', (string)$request->mobile)]);
        }
        $this->validate($request, [
          
            'name' => 'required',
            'email' => 'required|email',
            'password' => ['nullable'],
           
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
        
           // ✅ New Validation Rule
            // 'account_number' => 'required',
            // 'ifsc_code'=>'required',
            // 'branch_name'=>'required'
           
        ], ['mobile.regex' => 'Enter a valid mobile number: 10 to 15 digits, optionally starting with +.']);
       
        if (!@Auth::user()->permissionsGroup->settings_status && @Auth::user()->id != $id) {
            return redirect()->route('NoPermission');
        }
        $User = User::find($id);
        
	    if (is_null($User->smartping_user_id) && is_null($User->smartping_password)) {
            
            $name=$request->name;
            $mobile=$request->mobile;
            $password=$request->password;
            $companyId = config('services.smartping.company_id');
            $email=$request->email;
            try {
                $postData = [
                    'full_name' => $name,
                    'mobile_number' => $mobile,
                    'password' => $password,
                    'company_id' => $companyId
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
                }

                curl_close($ch);

                $responseData = json_decode($apiResponse, true);

                if (isset($responseData['user_id']) && isset($responseData['password'])) {
                    DB::table('users')
                        ->where('email', $email)
                        ->update([
                            'smartping_user_id' => $responseData['user_id'],
                            'smartping_password' => $responseData['password']
                        ]);
                } else {
                    \Log::warning('Unexpected SmartPing response', ['response' => $apiResponse]);
                }
            } catch (\Exception $e) {
                \Log::error('SmartPing API exception', ['message' => $e->getMessage()]);
            }
        }
        if (!empty($User)) {

            if ($request->has('team_id')) {
                $teamInput = $request->team_id;
                if (is_string($teamInput)) {
                    $teamInput = array_filter(explode(',', $teamInput), 'strlen');
                } elseif (!is_array($teamInput)) {
                    $teamInput = [$teamInput];
                }
                $request->merge(['team_id' => $teamInput]);
            }

            $this->validate($request, [
                'photo' => 'mimes:png,jpeg,jpg,gif,svg',
                'name' => 'required',
                'permissions_id' => 'required',
                'team_id' => 'nullable|array',
                'team_id.*' => 'integer|exists:teams,id',
                'shift_id' => 'required'
            ]);

            if ($request->email != $User->email) {
                $this->validate($request, [
                    'email' => 'required|email|unique:users',
                ]);
            }
            // Start of Upload Files
            $formFileName = "photo";
            $fileFinalName_ar = "";
            if ($request->$formFileName != "") {
                $fileFinalName_ar = time() . rand(1111,
                        9999) . '.' . $request->file($formFileName)->getClientOriginalExtension();
                $path = $this->getUploadPath();
                $request->file($formFileName)->move($path, $fileFinalName_ar);
            }
            // End of Upload Files

            //if ($id != 1) {
            $User->name = $request->name;
            $User->email = $request->email;
            if ($request->password != "") {
                $User->password = bcrypt($request->password);
            }
            $User->role_id = $request->role_id;
            $User->permissions_id = $request->permissions_id;
            if ($request->has('team_id')) {
                $selectedTeamIds = is_array($request->team_id) ? array_map('intval', array_filter($request->team_id)) : [];
                $selectedTeamIds = array_values(array_unique($selectedTeamIds));
                $User->team_id = implode(',', $selectedTeamIds);
            }
            
            $work_location_val = trim($request->work_location_name ?? $request->work_location ?? $request->work_location_id ?? '');
            if ($work_location_val !== '') {
                if (is_numeric($work_location_val) && WorkLocation::find($work_location_val)) {
                    $User->work_location_id = (int)$work_location_val;
                } else {
                    $workLoc = WorkLocation::where('name', $work_location_val)->first();
                    if (!$workLoc) {
                        $workLoc = new WorkLocation();
                        $workLoc->name = $work_location_val;
                        $workLoc->lat = '0';
                        $workLoc->long = '0';
                        $workLoc->radius = '0';
                        $workLoc->save();
                    }
                    $User->work_location_id = $workLoc->id;
                }
            } else {
                $User->work_location_id = 1;
            }

            $User->shift_id = $request->shift_id;
            if ($request->has('sms_gateway_id')) {
                $User->sms_gateway_id = $request->sms_gateway_id;
            }
            if ($request->has('email_setting_id')) {
                $User->email_setting_id = $request->email_setting_id;
            }
            if ($request->has('languages_known')) {
                $User->languages_known = $request->languages_known;
            }
            if ($request->has('account_number')) {
                $User->account_number = $request->account_number;
            }
            if ($request->has('ifsc_code')) {
                $User->ifsc_code = $request->ifsc_code;
            }
            if ($request->has('branch_name')) {
                $User->branch_name = $request->branch_name;
            }
            if ($request->has('upi_id')) {
                $User->upi_id = $request->upi_id;
            }
            //}
            if ($request->photo_delete == 1) {
                // Delete a User file
                if ($User->photo != "") {
                    File::delete($this->getUploadPath() . $User->photo);
                }

                $User->photo = "";
            }
            if ($fileFinalName_ar != "") {
                // Delete a User file
                if ($User->photo != "") {
                    File::delete($this->getUploadPath() . $User->photo);
                }

                $User->photo = $fileFinalName_ar;
            }

            $User->connect_email = $request->connect_email;
            if ($request->connect_password != "") {
                $User->connect_password = $request->connect_password;
            }

            if ($request->has('status') && $request->status !== null) {
                $User->status = $request->status;
            }
            // users.mobile = the staff member's contact number (shown on the Staff page).
            // users.click_agent_number = the Click2Call calling number (the phone the provider rings).
            // The calling number follows the contact number when it was the same number (or empty);
            // a different calling number set on the Click2Call page is kept.
            $digits = fn ($v) => preg_replace('/[^0-9]/', '', (string)$v);
            $callingFollowsMobile = $digits($User->click_agent_number) === '' || $digits($User->click_agent_number) === $digits($User->getOriginal('mobile'));
            $User->mobile = $request->mobile;

            $c2cError = null;
            $webrtcError = null;
            if ($request->has('calling_method_present')) {
                // Click2Call and WebRTC are independent checkboxes; a user may have one, both or neither
                $methods = (array)$request->input('calling_method', []);
                $c2cError = $User->setClick2Call(in_array('click2call', $methods, true), $callingFollowsMobile ? $request->mobile : null);
                $webrtcError = $User->setWebRTC(in_array('webrtc', $methods, true));
            } elseif ((int)$User->c2c_channel === 1 && $callingFollowsMobile) {
                $User->click_agent_number = $digits($request->mobile);
            }

            $User->updated_by = Auth::user()->id;
            $User->save();

            // Sync many-to-many teams pivot if team_id was submitted
            if ($request->has('team_id')) {
                $User->teams()->sync($selectedTeamIds);
            }

            // Save the company-wide AI Transcript setting only when that section of the form was submitted
            if ($request->has('ai_settings_present')) {
                $aiSettings = \App\Models\CompanyAiSetting::company();
                $aiSettings->ai_enabled = $request->has('ai_enabled');
                $aiSettings->save();
            }

            $isAdmin = (@Auth::user()->permissionsGroup && @Auth::user()->permissionsGroup->name === 'Admin');

            // 1. Server-side Authorization: Delete Permission modification check
            // Non-admin roles (TeleCaller, Manager, Webmaster, Limited User, etc.) MUST NOT be allowed to modify Delete Permission!
            if ($request->has('manager_delete_permission') && !$isAdmin) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized: Only Administrators can modify Delete Permission.'], 403);
                }
                abort(403, 'Unauthorized: Only Administrators can modify Delete Permission.');
            }

            // Save Company Settings: RMI Report (Admin or settings_status)
            if (@Auth::user()->permissionsGroup->settings_status || $isAdmin) {
                $setting = Setting::first();
                if (!empty($setting)) {
                    $setting->rmi_report_enabled = $request->has('rmi_report_enabled') ? 1 : 0;
                    $setting->save();
                    \Session()->forget('_Loader_Web_Settings');
                }
            }

            // 2. Delete Permission is saved only by Company Settings (updateCompanySettings). This profile
            //    form never contains the checkbox, so writing it here switched the permission off every
            //    time an Admin saved a staff profile.

            $redirect = (Auth::user()->id == $id)
                ? redirect()->route('usersEdit', $id)->with('doneMessage', __('backend.saveDone'))
                : redirect()->route('users')->with('doneMessage', __('backend.saveDone'));
            $channelErrors = array_filter([
                $c2cError ? 'Click2Call was not enabled: ' . $c2cError : null,
                $webrtcError ? 'WebRTC was not enabled: ' . $webrtcError : null,
            ]);
            return $channelErrors ? $redirect->with('errorMessage', 'Saved, but ' . implode(' ', $channelErrors)) : $redirect;
        } else {
            return redirect()->route('users')->with('errorMessage', __('backend.userNotFound'));
        }
    }

    /**
     * Update Work Configuration section via AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWorkConfig(Request $request, $id)
    {
        $User = User::find($id);
        if (empty($User)) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $shift_id = $request->shift_id;
        $work_location_val = trim($request->work_location_name ?? $request->work_location ?? $request->work_location_id ?? '');

        if ($work_location_val !== '') {
            if (is_numeric($work_location_val) && WorkLocation::find($work_location_val)) {
                $User->work_location_id = (int)$work_location_val;
            } else {
                $workLoc = WorkLocation::where('name', $work_location_val)->first();
                if (!$workLoc) {
                    $workLoc = new WorkLocation();
                    $workLoc->name = $work_location_val;
                    $workLoc->lat = '0';
                    $workLoc->long = '0';
                    $workLoc->radius = '0';
                    $workLoc->save();
                }
                $User->work_location_id = $workLoc->id;
            }
        } else {
            $User->work_location_id = 1;
        }

        if (!empty($shift_id)) {
            $User->shift_id = $shift_id;
        }

        $User->updated_by = Auth::user()->id;
        $User->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Work configuration saved successfully'
        ]);
    }

    /**
     * Update Company Settings via AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompanySettings(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $isAdmin = isset($user->permissionsGroup) && ($user->permissionsGroup->name === 'Admin');

        // PART 4 RULE 1: Non-admin users cannot change Delete Permission. Reject with 403.
        if ($request->has('delete_permission_attempted') && !$isAdmin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only Administrators can modify Delete Permission.'], 403);
        }
        if ($request->has('manager_delete_permission') && !$isAdmin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only Administrators can modify Delete Permission.'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'company_name' => 'required|string|max:191',
            'company_address' => 'nullable|string|max:500',
            'company_email' => 'nullable|email|max:191',
            'company_phone' => 'nullable|string|max:30|regex:/^[0-9+\s\-()]{6,30}$/',
            'company_logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ], [
            'company_name.required' => 'Company Name is required.',
            'company_email.email' => 'Please enter a valid email address.',
            'company_phone.regex' => 'Please enter a valid phone number (digits, +, -, spaces only).',
            'company_logo.file' => 'The uploaded file must be a valid file.',
            'company_logo.mimes' => 'Allowed logo formats are PNG, JPG, JPEG, SVG, WEBP.',
            'company_logo.max' => 'Company logo must not exceed 2 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = Setting::first();
            if (!$setting) {
                $setting = new Setting();
            }

            $setting->site_title_en = trim($request->company_name);
            $setting->contact_t1_en = trim($request->company_address ?? '');
            $setting->contact_t6 = trim($request->company_email ?? '');
            $setting->contact_t3 = trim($request->company_phone ?? '');

            // Handle logo remove
            if ($request->input('remove_logo') == '1') {
                if ($setting->style_logo_en) {
                    $oldPath = public_path('uploads/settings/' . $setting->style_logo_en);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                    $setting->style_logo_en = null;
                }
            }

            // Handle logo upload
            if ($request->hasFile('company_logo')) {
                $file = $request->file('company_logo');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $destPath = public_path('uploads/settings');
                if (!file_exists($destPath)) {
                    @mkdir($destPath, 0777, true);
                }
                if ($setting->style_logo_en) {
                    $oldPath = public_path('uploads/settings/' . $setting->style_logo_en);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $file->move($destPath, $filename);
                $setting->style_logo_en = $filename;
            }

            $setting->rmi_report_enabled = $request->has('rmi_report_enabled') ? 1 : 0;
            $setting->save();
            \Session()->forget('_Loader_Web_Settings');

            // Delete Permission: Strictly Admin only
            if ($isAdmin) {
                $managerPermission = Permissions::where('name', 'Manager')->first();
                if (!empty($managerPermission)) {
                    $managerPermission->delete_status = $request->has('manager_delete_permission') ? 1 : 0;
                    $managerPermission->save();
                }
            }

            // AI Transcript
            $aiSettings = \App\Models\CompanyAiSetting::company();
            $aiSettings->ai_enabled = $request->has('ai_enabled') ? true : false;
            $aiSettings->save();

            $logoUrl = $setting->style_logo_en ? asset('uploads/settings/' . $setting->style_logo_en) : '';

            return response()->json([
                'success' => true,
                'message' => 'Company settings saved',
                'data' => [
                    'company_name' => $setting->site_title_en,
                    'company_address' => $setting->contact_t1_en,
                    'company_email' => $setting->contact_t6,
                    'company_phone' => $setting->contact_t3,
                    'style_logo_en' => $setting->style_logo_en,
                    'logo_url' => $logoUrl,
                    'has_logo' => !empty($setting->style_logo_en),
                    'rmi_report_enabled' => (bool)$setting->rmi_report_enabled,
                    'manager_delete_permission' => \App\Helpers\Helper::isDeletePermissionEnabled(),
                    'ai_enabled' => (bool)$aiSettings->ai_enabled,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving company settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving company settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new Role dynamically via AJAX for the Add Role section.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickStoreRole(Request $request)
    {
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $displayName = trim($request->display_name ?? '');
        $roleName = trim($request->role_name ?? $request->name ?? '');

        if (empty($displayName) || empty($roleName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Display Name and Role Name are required.'
            ], 422);
        }

        $finalName = $displayName;

        // Check if role name already exists
        $existing = Permissions::where('name', $finalName)->first();
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role "' . $finalName . '" already exists. Please enter a unique role name.'
            ], 422);
        }

        $permission = new Permissions();
        $permission->name = $finalName;
        $permission->view_status = 1;
        $permission->add_status = 1;
        $permission->edit_status = 1;
        $permission->delete_status = 0;
        $permission->active_status = 1;
        $permission->status = 1;
        $permission->created_by = Auth::user()->id ?? 1;
        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Role "' . $permission->name . '" created successfully',
            'role' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'role_name' => \Illuminate\Support\Str::slug($permission->name, '_'),
                'created_at' => $permission->created_at ? $permission->created_at->format('Y-m-d H:i') : date('Y-m-d H:i')
            ]
        ]);
    }

    /**
     * Update an existing Role name via AJAX.
     */
    public function ajaxUpdateRoleName(Request $request, $id)
    {
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $name = trim($request->name ?? '');
        if (empty($name)) {
            return response()->json(['status' => 'error', 'message' => 'Role name is required.'], 422);
        }

        $existing = Permissions::where('name', $name)->where('id', '!=', $id)->first();
        if ($existing) {
            return response()->json(['status' => 'error', 'message' => 'Role name "' . $name . '" already exists.'], 422);
        }

        $permission = Permissions::find($id);
        if (!$permission) {
            return response()->json(['status' => 'error', 'message' => 'Role not found.'], 404);
        }

        $permission->name = $name;
        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully.',
            'role' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'role_name' => \Illuminate\Support\Str::slug($permission->name, '_')
            ]
        ]);
    }

    /**
     * Safely delete a Role via AJAX after checking user relationships.
     */
    public function ajaxDeleteRole($id)
    {
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $permission = Permissions::find($id);
        if (!$permission) {
            return response()->json(['status' => 'error', 'message' => 'Role not found.'], 404);
        }

        if (in_array($id, [1, 4])) {
            return response()->json(['status' => 'error', 'message' => 'System default role "' . $permission->name . '" cannot be deleted.'], 422);
        }

        $userCount = User::where('permissions_id', $id)->count();
        if ($userCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete role "' . $permission->name . '" because ' . $userCount . ' user(s) are currently assigned to it. Please reassign those users first.'
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully.',
            'id' => $id
        ]);
    }

    /**
     * Get JSON list of all roles for AJAX refreshes.
     */
    public function ajaxGetRolesList()
    {
        $roles = Permissions::orderBy('id', 'asc')->get()->map(function($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'role_name' => \Illuminate\Support\Str::slug($r->name, '_'),
                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : 'N/A',
                'users_count' => User::where('permissions_id', $r->id)->count()
            ];
        });

        return response()->json(['status' => 'success', 'roles' => $roles]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check Delete Authorization
        if (!Helper::canDelete()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        $User = User::find($id);
        if (!empty($User) && $id != 1 && $id != Auth::user()->id) {
            // Delete a User photo
            if ($User->photo != "") {
                File::delete($this->getUploadPath() . $User->photo);
            }

            $User->teams()->detach();
            $User->delete();
            return redirect()->action('Dashboard\UsersController@index')->with('doneMessage', __('backend.deleteDone'));
        } else {
            return redirect()->action('Dashboard\UsersController@index')->with('errorMessage', 'User cannot be deleted.');
        }
    }


    /**
     * Update all selected resources in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param buttonNames , array $ids[]
     * @return \Illuminate\Http\Response
     */
    public function updateAll(Request $request)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        if ($request->ids != "") {
            if ($request->action == "activate") {
                User::wherein('id', $request->ids)
                    ->update(['status' => 1]);

            } elseif ($request->action == "block") {
                User::wherein('id', $request->ids)->where('id', '!=', 1)
                    ->update(['status' => 0]);

            } elseif ($request->action == "delete") {
                if (!Helper::canDelete()) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
                    }
                    return redirect()->route('NoPermission');
                }
                // Delete User photo
                $Users = User::wherein('id', $request->ids)->where('id', '!=', 1)->get();
                foreach ($Users as $User) {
                    if ($User->photo != "") {
                        File::delete($this->getUploadPath() . $User->photo);
                    }
                }

                User::wherein('id', $request->ids)->where('id', "!=", 1)
                    ->delete();

            }
        }
        return redirect()->action('Dashboard\UsersController@index')->with('doneMessage', __('backend.saveDone'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function permissions_create()
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.permissions.create", compact("GeneralWebmasterSections"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function permissions_store(Request $request)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        $this->validate($request, [
            'name' => 'required'
        ]);

        $data_sections_values = "";
        if (@$request->data_sections != "") {
            foreach ($request->data_sections as $key => $val) {
                $data_sections_values = $val . "," . $data_sections_values;
            }
            $data_sections_values = substr($data_sections_values, 0, -1);
        }

        $Permissions = new Permissions;
        $Permissions->name = $request->name;
        $Permissions->view_status = ($request->view_status) ? "1" : "0";
        $Permissions->add_status = ($request->add_status) ? "1" : "0";
        $Permissions->edit_status = ($request->edit_status) ? "1" : "0";
        $Permissions->delete_status = ($request->delete_status) ? "1" : "0";
        $Permissions->active_status = ($request->active_status) ? "1" : "0";
        $Permissions->analytics_status = ($request->analytics_status) ? "1" : "0";
        $Permissions->inbox_status = ($request->inbox_status) ? "1" : "0";
        $Permissions->newsletter_status = ($request->newsletter_status) ? "1" : "0";
        $Permissions->calendar_status = ($request->calendar_status) ? "1" : "0";
        $Permissions->banners_status = ($request->banners_status) ? "1" : "0";
        $Permissions->inbound_calls_status = ($request->inbound_calls_status) ? "1" : "0";
        $Permissions->settings_status = ($request->settings_status) ? "1" : "0";
        $Permissions->webmaster_status = ($request->webmaster_status) ? "1" : "0";
        $Permissions->data_sections = $data_sections_values;
        $Permissions->home_status = 0;
        $Permissions->status = true;
        $Permissions->save();

        return redirect()->action('Dashboard\UsersController@index')->with('doneMessage', __('backend.addDone'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function permissions_edit($id)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        if (@Auth::user()->permissionsGroup->view_status) {
            $Permissions = Permissions::where('created_by', '=', Auth::user()->id)->find($id);
        } else {
            $Permissions = Permissions::find($id);
        }
        if (!empty($Permissions)) {
            return view("dashboard.permissions.edit", compact("Permissions", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\UsersController@index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function permissions_update(Request $request, $id)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        $Permissions = Permissions::find($id);
        if (!empty($Permissions)) {
            // Guard Manager Delete Permission: Strictly Admin only
            $isAdmin = (@Auth::user()->permissionsGroup && @Auth::user()->permissionsGroup->name === 'Admin');
            if ($Permissions->name === 'Manager' && !$isAdmin) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized: Only Administrators can modify Delete Permission.'], 403);
                }
                abort(403, 'Unauthorized: Only Administrators can modify Delete Permission.');
            }


            $this->validate($request, [
                'name' => 'required'
            ]);

            $data_sections_values = "";
            if (@$request->data_sections != "") {
                foreach ($request->data_sections as $key => $val) {
                    $data_sections_values = $val . "," . $data_sections_values;
                }
                $data_sections_values = substr($data_sections_values, 0, -1);
            }

            $Permissions->name = $request->name;
            $Permissions->view_status = ($request->view_status) ? "1" : "0";
            $Permissions->add_status = ($request->add_status) ? "1" : "0";
            $Permissions->edit_status = ($request->edit_status) ? "1" : "0";
            $Permissions->delete_status = ($request->delete_status) ? "1" : "0";
            $Permissions->active_status = ($request->active_status) ? "1" : "0";
            $Permissions->analytics_status = ($request->analytics_status) ? "1" : "0";
            $Permissions->inbox_status = ($request->inbox_status) ? "1" : "0";
            $Permissions->newsletter_status = ($request->newsletter_status) ? "1" : "0";
            $Permissions->calendar_status = ($request->calendar_status) ? "1" : "0";
            $Permissions->banners_status = ($request->banners_status) ? "1" : "0";
            $Permissions->inbound_calls_status = ($request->inbound_calls_status) ? "1" : "0";
            if ($id != 1) {
                $Permissions->settings_status = ($request->settings_status) ? "1" : "0";
                $Permissions->webmaster_status = ($request->webmaster_status) ? "1" : "0";
            }
            $Permissions->data_sections = $data_sections_values;

            $Permissions->status = $request->status;
            $Permissions->save();
            return redirect()->action('Dashboard\UsersController@permissions_edit', $id)->with('doneMessage',
                __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\UsersController@index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function permissions_destroy($id)
    {
        // Check Permissions
        if (!@Auth::user()->permissionsGroup->settings_status) {
            return redirect()->route('NoPermission');
        }
        if (@Auth::user()->permissionsGroup->view_status) {
            $Permissions = Permissions::where('created_by', '=', Auth::user()->id)->find($id);
        } else {
            $Permissions = Permissions::find($id);
        }
        if (!empty($Permissions) && $id != 1) {
            User::where('permissions_id', $id)->delete();
            $Permissions->delete();
            return redirect()->action('Dashboard\UsersController@index')->with('doneMessage', __('backend.deleteDone'));
        } else {
            return redirect()->action('Dashboard\UsersController@index');
        }
    }


    public function update_custom_home(Request $request, $id)
    {
        //
        $Permissions = Permissions::find($id);
        if (!empty($Permissions)) {
            $Permissions->home_status = ($request->home_status) ? "1" : "0";

            foreach (Helper::languagesList() as $ActiveLanguage) {
                if ($ActiveLanguage->box_status) {
                    $Permissions->{"home_details_" . $ActiveLanguage->code} = $request->{"home_details_" . $ActiveLanguage->code};
                }
            }

            $saved_home_links = [];
            if ($Permissions->home_links != "") {
                try {
                    $saved_home_links = json_decode($Permissions->home_links);
                } catch (\Exception $e) {
                    $saved_home_links = [];
                }
            }

            $home_links = [];
            try {
                if (count($saved_home_links) > 0) {
                    foreach ($saved_home_links as $key => $saved_home_link) {
                        $row_no_val = "row_no_" . $saved_home_link->btn_id;
                        $link_details = [
                            "btn_id" => @$saved_home_link->btn_id,
                            "btn_link" => @$saved_home_link->btn_link,
                            "btn_class" => @$saved_home_link->btn_class,
                            "btn_order" => $request->$row_no_val,
                            "btn_target" => @$saved_home_link->btn_target,
                        ];

                        foreach (Helper::languagesList() as $ActiveLanguage) {
                            if ($ActiveLanguage->box_status) {
                                $link_details["btn_title_" . $ActiveLanguage->code] = @$saved_home_link->{"btn_title_" . $ActiveLanguage->code};
                            }
                        }
                        $home_links[] = $link_details;
                    }

                    usort($home_links, function ($a, $b) {
                        return $a['btn_order'] <=> $b['btn_order'];
                    });
                }

                $home_links = json_encode($home_links);
                $Permissions->home_links = $home_links;
            } catch (\Exception $e) {

            }

            $Permissions->save();
            return redirect()->action('Dashboard\UsersController@permissions_edit', $id)->with('doneMessage',
                __('backend.saveDone'))->with('tab', "home");
        } else {
            return redirect()->action('Dashboard\UsersController@index');
        }
    }


    public function links_store(Request $request)
    {
        //
        $Permissions = Permissions::find($request->permission_id);
        if (!empty($Permissions)) {
            if ($request->link == "") {
                return response()->json(['stat' => 'error', 'error' => [__('backend.completeLinkDetails')]]);
            }
            //
            $saved_home_links = [];
            if ($Permissions->home_links != "") {
                try {
                    $saved_home_links = json_decode($Permissions->home_links);
                } catch (\Exception $e) {
                    $saved_home_links = [];
                }
            }
            $home_links = [];
            try {
                $row_no = 1;
                if (!empty($saved_home_links) > 0) {
                    foreach ($saved_home_links as $key => $saved_home_link) {
                        $link_details = [
                            "btn_id" => @$saved_home_link->btn_id,
                            "btn_link" => @$saved_home_link->btn_link,
                            "btn_class" => @$saved_home_link->btn_class,
                            "btn_order" => @$saved_home_link->btn_order,
                            "btn_target" => @$saved_home_link->btn_target,
                        ];

                        foreach (Helper::languagesList() as $ActiveLanguage) {
                            if ($ActiveLanguage->box_status) {
                                $link_details["btn_title_" . $ActiveLanguage->code] = @$saved_home_link->{"btn_title_" . $ActiveLanguage->code};
                            }
                        }
                        $home_links[] = $link_details;
                        $row_no = @$saved_home_link->btn_order + 1;
                    }
                }

                $new_link_details = [
                    "btn_id" => uniqid(),
                    "btn_link" => $request->link,
                    "btn_class" => $request->btn_class,
                    "btn_order" => $row_no,
                    "btn_target" => ($request->target) ? 1 : 0,
                ];

                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($ActiveLanguage->box_status) {
                        $new_link_details["btn_title_" . $ActiveLanguage->code] = $request->{"btn_title_" . $ActiveLanguage->code};
                    }
                }
                $home_links[] = $new_link_details;

                $home_links = json_encode($home_links);
                $Permissions->home_links = $home_links;
                $Permissions->save();

                return response()->json(['stat' => 'success']);
            } catch (\Exception $e) {
            }
        }

        return response()->json(['stat' => 'error', 'error' => [__('backend.error')]]);
    }

    public function links_destroy($id = "", $p_id = 0)
    {
        if ($id != "") {
            $Permissions = Permissions::find($p_id);
            if (!empty($Permissions)) {

                if ($Permissions->home_links != "") {
                    try {
                        $saved_home_links = json_decode($Permissions->home_links);
                    } catch (\Exception $e) {
                        $saved_home_links = [];
                    }
                }
                try {
                    $home_links = [];
                    if (count($saved_home_links) > 0) {
                        foreach ($saved_home_links as $key => $saved_home_link) {
                            if ($saved_home_link->btn_id != $id) {

                                $link_details = [
                                    "btn_id" => @$saved_home_link->btn_id,
                                    "btn_link" => @$saved_home_link->btn_link,
                                    "btn_class" => @$saved_home_link->btn_class,
                                    "btn_order" => @$saved_home_link->btn_order,
                                    "btn_target" => @$saved_home_link->btn_target,
                                ];

                                foreach (Helper::languagesList() as $ActiveLanguage) {
                                    if ($ActiveLanguage->box_status) {
                                        $link_details["btn_title_" . $ActiveLanguage->code] = @$saved_home_link->{"btn_title_" . $ActiveLanguage->code};
                                    }
                                }
                                $home_links[] = $link_details;
                            }
                        }
                    }


                    $home_links = json_encode($home_links);
                    $Permissions->home_links = $home_links;
                    $Permissions->save();

                    return response()->json(['stat' => 'success']);
                } catch (\Exception $e) {
                }
            }
        }
        return response()->json(['stat' => 'error', 'error' => [trans('backLang.erorr')]]);
    }

    public function links_list($p_id = 0)
    {
        $saved_home_links = [];
        $Permissions = Permissions::find($p_id);
        if (!empty($Permissions)) {

            if ($Permissions->home_links != "") {
                try {
                    $saved_home_links = json_decode($Permissions->home_links);
                } catch (\Exception $e) {
                    $saved_home_links = [];
                }
            }
            $home_links = [];
            try {
                if (count($saved_home_links) > 0) {
                    foreach ($saved_home_links as $key => $saved_home_page_button) {
                        $link_details = [
                            "btn_id" => $saved_home_page_button->btn_id,
                            "btn_link" => $saved_home_page_button->btn_link,
                            "btn_class" => $saved_home_page_button->btn_class,
                            "btn_order" => $saved_home_page_button->btn_order,
                            "btn_target" => @$saved_home_page_button->btn_target,
                        ];
                        foreach (Helper::languagesList() as $ActiveLanguage) {
                            if ($ActiveLanguage->box_status) {
                                $link_details["btn_title_" . $ActiveLanguage->code] = $saved_home_page_button->{"btn_title_" . $ActiveLanguage->code};
                            }
                        }
                        $home_links[] = $link_details;
                    }
                    usort($home_links, function ($a, $b) {
                        return $a['btn_order'] <=> $b['btn_order'];
                    });
                }

                $home_links = json_encode($home_links);
                $home_links = json_decode($home_links);
            } catch (\Exception $e) {

            }
        }
        return view("dashboard.permissions.home.links.list", compact("Permissions", "home_links"));
    }

    public function links_edit($id = "", $p_id = 0)
    {
        $saved_home_links = [];
        $home_page_button = [];
        $Permissions = Permissions::find($p_id);
        if (!empty($Permissions)) {

            if ($Permissions->home_links != "") {
                try {
                    $saved_home_links = json_decode($Permissions->home_links);
                } catch (\Exception $e) {
                    $saved_home_links = [];
                }
            }
            try {
                if (count($saved_home_links) > 0) {
                    foreach ($saved_home_links as $key => $saved_home_link) {
                        if ($id == $saved_home_link->btn_id) {
                            $home_page_button = [
                                "btn_id" => @$saved_home_link->btn_id,
                                "btn_link" => @$saved_home_link->btn_link,
                                "btn_class" => @$saved_home_link->btn_class,
                                "btn_order" => @$saved_home_link->btn_order,
                                "btn_target" => @$saved_home_link->btn_target,
                            ];

                            foreach (Helper::languagesList() as $ActiveLanguage) {
                                if ($ActiveLanguage->box_status) {
                                    $home_page_button["btn_title_" . $ActiveLanguage->code] = @$saved_home_link->{"btn_title_" . $ActiveLanguage->code};
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {

            }
        }
        return view("dashboard.permissions.home.links.edit", compact("home_page_button", "Permissions"));
    }

    public function links_update(Request $request)
    {
        //
        $Permissions = Permissions::find($request->p_id);
        if (!empty($Permissions)) {
            if ($request->link == "") {
                return response()->json(['stat' => 'error', 'error' => [__('backend.completeLinkDetails')]]);
            }

            //
            if ($Permissions->home_links != "") {
                try {
                    $saved_home_links = json_decode($Permissions->home_links);
                } catch (\Exception $e) {
                    $saved_home_links = [];
                }
            }
            $home_links = [];
            try {
                $row_no = 1;
                if (!empty($saved_home_links) > 0) {
                    foreach ($saved_home_links as $key => $saved_home_link) {

                        if ($request->id == @$saved_home_link->btn_id) {


                            $new_link_details = [
                                "btn_id" => uniqid(),
                                "btn_link" => $request->link,
                                "btn_class" => $request->btn_class,
                                "btn_order" => $row_no,
                                "btn_target" => ($request->target) ? 1 : 0,
                            ];

                            foreach (Helper::languagesList() as $ActiveLanguage) {
                                if ($ActiveLanguage->box_status) {
                                    $new_link_details["btn_title_" . $ActiveLanguage->code] = $request->{"btn_title_" . $ActiveLanguage->code};
                                }
                            }
                            $home_links[] = $new_link_details;
                            $row_no++;

                        } else {
                            $link_details = [
                                "btn_id" => @$saved_home_link->btn_id,
                                "btn_link" => @$saved_home_link->btn_link,
                                "btn_class" => @$saved_home_link->btn_class,
                                "btn_order" => @$saved_home_link->btn_order,
                                "btn_target" => @$saved_home_link->btn_target,
                            ];

                            foreach (Helper::languagesList() as $ActiveLanguage) {
                                if ($ActiveLanguage->box_status) {
                                    $link_details["btn_title_" . $ActiveLanguage->code] = @$saved_home_link->{"btn_title_" . $ActiveLanguage->code};
                                }
                            }
                            $home_links[] = $link_details;
                            $row_no = $saved_home_link->btn_order + 1;
                        }

                    }
                }

                $home_links = json_encode($home_links);
                $Permissions->home_links = $home_links;
                $Permissions->save();

                return response()->json(['stat' => 'success']);
            } catch (\Exception $e) {
            }
        }

        return response()->json(['stat' => 'error', 'error' => [__('backend.error')]]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function teams()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Teams = \App\Models\Team::orderby('id', 'asc')->paginate(env('BACKEND_PAGINATION'));

        return view("dashboard.users.teams", compact("Teams","GeneralWebmasterSections"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createTeam()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.users.create_team", compact("GeneralWebmasterSections"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeTeam(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:teams',
        ]);

        $Team = new Team;
        $Team->name = $request->name;
        $Team->description = $request->description;
        $Team->save();
        $adminPermission = DB::table('permissions')->where('name', 'admin')->first();

        if ($adminPermission) {
            // Get all users who have this permission
            $users = DB::table('users')->where('permissions_id', $adminPermission->id)->get();
    
            foreach ($users as $user) {
                // Append the new team ID to the existing ones
                $existingTeams = $user->team_id ? explode(',', $user->team_id) : [];
                if (!in_array($Team->id, $existingTeams)) {
                    $existingTeams[] = $Team->id;
                }
    
                // Update users table
                DB::table('users')->where('id', $user->id)->update([
                    'team_id' => implode(',', $existingTeams)
                ]);
                $userModel = User::find($user->id);
                if ($userModel) {
                    $userModel->teams()->sync($existingTeams);
                }
            }
        }
        return redirect()->action('Dashboard\UsersController@teams')->with('doneMessage', __('backend.addDone'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function editTeam($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Teams = Team::find($id);
        if (!empty($Teams)) {
            return view("dashboard.users.edit_team", compact("Teams", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\UsersController@teams');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateTeam(Request $request, $id)
    {
        $targetTeamId = $request->input('team_id', $id);
        $Team = Team::find($targetTeamId) ?? Team::find($id);
        if (!empty($Team)) {

            if ($request->has('name') && $request->name != $Team->name) {
                $this->validate($request, [
                    'name' => 'required|unique:teams',
                ]);
                $Team->name = $request->name;
            }

            if ($request->has('description')) {
                $Team->description = $request->description;
            }
            $Team->save();

            // Update team member assignments
            if ($request->has('team_members_submitted')) {
                $selectedUserIds = array_filter((array) $request->input('team_members', []));
                $allUsers = \App\Models\User::all();
                foreach ($allUsers as $user) {
                    $userTeams = array_filter(explode(',', $user->team_id ?? ''));
                    if (in_array((string)$user->id, array_map('strval', $selectedUserIds))) {
                        if (!in_array((string)$Team->id, array_map('strval', $userTeams))) {
                            $userTeams[] = $Team->id;
                        }
                    } else {
                        $userTeams = array_diff($userTeams, [$Team->id]);
                    }
                    $uniqueUserTeams = array_values(array_unique($userTeams));
                    DB::table('users')->where('id', $user->id)->update([
                        'team_id' => implode(',', $uniqueUserTeams)
                    ]);
                    $user->teams()->sync($uniqueUserTeams);
                }
            }

            return redirect()->route('teams')->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->route('teams');
        }
    }

    public function getTeamMembers($id)
    {
        $Users = DB::select("SELECT * FROM leadger_users WHERE FIND_IN_SET('$id', team_id)");
        $html = '<table class="table table-bordered m-a-0"><thead><tr><th>Name</th></thead><tbody>';
        foreach($Users  as $User)
        {
            $html .= "<tr><td>".$User->name."</td></tr>";
        }
        echo $html .= "</tbody></table>";
    }

    public function destroyTeam($id)
    {
        if (!Helper::canDelete()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        $Team = Team::find($id);
        if (!empty($Team)) {
            $Team->delete();
            return redirect()->action('Dashboard\UsersController@teams')->with('doneMessage', __('backend.deleteDone'));
        } else {
            return redirect()->action('Dashboard\UsersController@teams');
        }
    }

    public function userActivity(Request $request)
    {
        if($request->status == 'active')
        {
            $model = new UserActiveUsage;
            $model->user_id = $request->user;
            $model->start = date('Y-m-d H:i:s');
            $model->save();
        }
        else
        {
            $model = UserActiveUsage::where('user_id', $request->user)
                    ->orderBy('start', 'desc')->first();
            if(!empty($model) && $model->duration == 0) {
                $model->end = date('Y-m-d H:i:s');
                $model->duration = strtotime($model->end)-strtotime($model->start);
                $model->save();
            }
        }
    }

    public function updateRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permissions_id' => 'required',
        ], [
            'permissions_id.required' => 'Please select a role.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first('permissions_id')
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $User = User::find($id);
        if (!empty($User)) {
            $User->permissions_id = $request->permissions_id;
            $User->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Role updated successfully'
                ]);
            }

            return redirect()->back()->with('doneMessage', 'Role updated successfully');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        return redirect()->back()->with('errorMessage', 'User not found');
    }

    /**
     * Sync DID for License via AJAX.
     */
    public function syncDid(Request $request)
    {
        if ($deny = $this->denyUnlessLicenseAdmin()) return $deny;
        try {
            $result = \App\Services\LicenseService::syncDids();
        } catch (\Throwable $e) {
            \Log::error('[SyncDid] ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'DID sync failed. Please try again.'], 500);
        }
        \Log::info('AUDIT LOG: Sync Did by user #' . Auth::id() . ': ' . $result['message']);
        return response()->json([
            'status' => $result['ok'] ? 'success' : 'warning',
            'message' => $result['message'],
            'steps' => $result['steps'],
        ], $result['ok'] || !empty($result['steps']) ? 200 : 422);
    }

    /**
     * Delete active license via AJAX (admin only).
     */
    public function deleteLicense(Request $request)
    {
        if ($deny = $this->denyUnlessLicenseAdmin()) return $deny;
        if ($request->input('confirm') !== 'DELETE') {
            return response()->json(['status' => 'error', 'message' => 'Confirm the deletion first.'], 422);
        }
        try {
            $result = \App\Services\LicenseService::deleteLicense();
        } catch (\Throwable $e) {
            \Log::error('[DeleteLicense] ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'The license could not be deleted. Please try again.'], 500);
        }
        \Log::info('AUDIT LOG: License deleted by user #' . Auth::id() . ($result['env_cleared'] ? ' (installation license key cleared)' : ''));
        return response()->json(['status' => 'success', 'message' => $result['message'], 'env_cleared' => $result['env_cleared']]);
    }

    /** License actions are for the Admin role only. */
    private function denyUnlessLicenseAdmin()
    {
        if (optional(optional(Auth::user())->permissionsGroup)->name !== 'Admin') {
            return response()->json(['status' => 'error', 'message' => 'Only an admin can manage the license.'], 403);
        }
        return null;
    }

    /**
     * Fetch current license info JSON for dynamic rendering (admin only: it includes the license key).
     */
    public function getLicenseInfo(Request $request)
    {
        if ($deny = $this->denyUnlessLicenseAdmin()) return $deny;
        $licenseData = null;
        $licenseKey = env('license', '');
        try {
            $settingsTable = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            if ($settingsTable && !empty($settingsTable->full_response)) {
                $json = json_decode($settingsTable->full_response, true);
                if (isset($json['data'])) {
                    $licenseData = $json['data'];
                }
            }
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'success',
            'has_license' => !empty($licenseData),
            'license_key' => $licenseKey ?: ($licenseData['license_key'] ?? ''),
            'license' => $licenseData
        ]);
    }
}

