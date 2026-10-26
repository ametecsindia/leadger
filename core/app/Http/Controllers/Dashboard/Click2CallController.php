<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class Click2CallController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $c2cChannels = count(DB::Select("select * FROM ".env('DB_TABLE_PREFIX')."users where c2c_channel = 1"));
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $licenseC2cChannels = 50; // Default to 50 if missing in settings
        $channelExpiry = '2026/09/30';
        if ($settings && !empty($settings->full_response)) {
            $full_reponse = json_decode($settings->full_response, true);
            if(is_array($full_reponse) && isset($full_reponse['data']))
            {
                if(array_key_exists('c2c_channels',$full_reponse['data']))
                {
                    $licenseC2cChannels = (int)$full_reponse['data']['c2c_channels'];
                }
                if(!empty($full_reponse['data']['expiry']))
                {
                    $channelExpiry = date('Y/m/d', strtotime($full_reponse['data']['expiry']));
                }
            }
        }
        // (Removed: opening this page used to switch Click2Call OFF for every user when the enabled count
        //  was above the licence. The limit is enforced when enabling a user: User::setClick2Call.)
        
        $Users = DB::select("select * from ".env('DB_TABLE_PREFIX')."users where c2c_channel = 0");
        $AssignedUsers = User::where('c2c_channel', 1)->get();

        return view("dashboard.click2call.list", compact("GeneralWebmasterSections", "c2cChannels", "licenseC2cChannels", "Users", "AssignedUsers", "channelExpiry"));
    }

    public function callingSettings()
    {
        // role permissions configuration.call_setup.* (config/permissions.php, CheckPermission middleware)

        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = DB::table('settings')->where('id', 1)->first();

        // Calling hours formatted in 24-hour format HH:mm
        $calling_hours_from = '09:30';
        $calling_hours_to = '19:30';

        if ($settings) {
            if (!empty($settings->calling_hours_from)) {
                $calling_hours_from = substr(trim($settings->calling_hours_from), 0, 5);
            } elseif (!empty($settings->caller_login)) {
                $calling_hours_from = substr(trim($settings->caller_login), 0, 5);
            }

            if (!empty($settings->calling_hours_to)) {
                $calling_hours_to = substr(trim($settings->calling_hours_to), 0, 5);
            } elseif (!empty($settings->caller_logout)) {
                $calling_hours_to = substr(trim($settings->caller_logout), 0, 5);
            }
        }

        return view("dashboard.settings.callingSettings", compact(
            "GeneralWebmasterSections",
            "settings",
            "calling_hours_from",
            "calling_hours_to"
        ));
    }

    public function updateCallingSettings(Request $request)
    {
        // role permissions configuration.call_setup.* (config/permissions.php, CheckPermission middleware)

        $validator = Validator::make($request->all(), [
            'calling_hours_from' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
            'calling_hours_to' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
            'daily_call_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'default_dialer_type' => ['nullable', 'string', 'max:50'],
            'auto_recording' => ['nullable', 'in:0,1'],
            'dialer_delay' => ['nullable', 'integer', 'min:0', 'max:120'],
            'wrapup_time' => ['nullable', 'integer', 'min:0', 'max:600'],
        ], [
            'calling_hours_from.required' => 'Calling hours from (start time) is required.',
            'calling_hours_from.regex' => 'Calling hours from must be a valid 24-hour time (HH:MM).',
            'calling_hours_to.required' => 'Calling hours to (end time) is required.',
            'calling_hours_to.regex' => 'Calling hours to must be a valid 24-hour time (HH:MM).',
            'daily_call_limit.required' => 'Maximum retry of a particular mobile number in a day is required.',
            'daily_call_limit.integer' => 'Maximum retry of a particular mobile number in a day must be a whole number.',
            'daily_call_limit.min' => 'Maximum retry of a particular mobile number in a day must be at least 1.',
            'daily_call_limit.max' => 'Maximum retry of a particular mobile number in a day cannot exceed 1000.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $from = trim($request->calling_hours_from);
        $to = trim($request->calling_hours_to);

        // Validate "from" and "to" are not the same (overnight windows where to < from are supported)
        if ($from === $to) {
            return redirect()->back()
                ->withErrors(['calling_hours_to' => 'Calling hours "from" and "to" times cannot be the same.'])
                ->withInput();
        }

        $dailyLimit = (int)$request->daily_call_limit;

        $updateData = [
            'calling_hours_from' => $from,
            'calling_hours_to' => $to,
            'daily_call_limit' => $dailyLimit,
            'updated_at' => now(),
        ];

        if ($request->has('default_dialer_type')) {
            $updateData['active_call_channel'] = $request->default_dialer_type;
        }
        if ($request->has('auto_recording')) {
            $updateData['auto_recording'] = (int)$request->auto_recording;
        }
        if ($request->has('dialer_delay')) {
            $updateData['dialer_delay'] = (int)$request->dialer_delay;
        }
        if ($request->has('wrapup_time')) {
            $updateData['wrapup_time'] = (int)$request->wrapup_time;
        }

        DB::table('settings')->where('id', 1)->update($updateData);

        // Clear settings cache immediately so changes apply to the next call with no restart
        \App\Services\CallingRestrictionService::clearSettingsCache();
        \Illuminate\Support\Facades\Cache::forget('_Loader_Web_Settings');
        if (session()->has('_Loader_Web_Settings')) {
            session()->forget('_Loader_Web_Settings');
        }

        return redirect()->back()->with('doneMessage', 'Calling settings updated successfully!');
    }

    public function click2CallData(Request $request)
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
where c2c_channel = 1 ";

        if ($q != "") {
            $sql .= " and (name like '%".$q."%' 
                        or click_agent_number like '%".$q."%'  )";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Click2Call = DB::select("SELECT name,click_agent_number,id
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Click2Call as $row) {
                $userObj = User::find($row->id);
                $roleName = @$userObj->permissionsGroup->name ?? 'TeleCaller';
                $initials = strtoupper(substr($row->name ?? 'U', 0, 2));

                $nestedData = [];
                $nestedData['name'] = '<div class="lg-user-flex"><div class="lg-user-icon-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><span style="font-weight: 600; color: #0F172A;">'.e($row->name).'</span></div>';
                $nestedData['role'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">'.e($roleName).'</span>';
                $nestedData['channel'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">Assigned</span>';
                $nestedData['click_agent_number'] = '<span style="font-size: 13px; color: #475569; font-weight: 500;">'.e($row->click_agent_number).'</span>';
                
                $actionurl = route("click2CallEdit", ["id" => $row->id]);
                $deleteurl = route("click2CallDelete", ["id" => $row->id]);
                
                $nestedData['options'] = '
                    <div class="lg-action-btn-group">
                        <a href="'.$actionurl.'" class="lg-action-btn edit" data-id="'.$row->id.'" data-user-id="'.$row->id.'" data-name="'.e($row->name).'" data-number="'.e($row->click_agent_number).'" onclick="if(typeof lgOpenEditChannelModal===\'function\'){lgOpenEditChannelModal('.$row->id.', \'click2Call\', this); return false;}" title="Edit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                        <a href="'.$deleteurl.'" class="lg-action-btn delete" onclick="return confirm(\'Are you sure you want to delete this Click2Call channel?\')" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg></a>
                    </div>';
                
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Users = DB::select("select * from ".env('DB_TABLE_PREFIX')."users where c2c_channel = 0");

        return view("dashboard.click2call.create", compact("GeneralWebmasterSections", "Users"));
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
            'click_agent_number' => 'required',
        ], [
            'user.required' => 'Please select a user.',
            'click_agent_number.required' => 'Agent number is required.',
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

        $User = User::find($request->user);
        if (!$User) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }
            return redirect()->action('Dashboard\Click2CallController@index')->with('errorMessage', 'User not found');
        }

        // Same rule as Staff create/edit: licence limit + agent number (User::setClick2Call)
        if ($error = $User->setClick2Call(true, $request->click_agent_number)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $error], 422);
            }
            return redirect()->action('Dashboard\\Click2CallController@index')->with('errorMessage', $error);
        }
        $User->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Click2Call updated',
                'user' => [
                    'id' => $User->id,
                    'name' => $User->name,
                    'click_agent_number' => $User->click_agent_number,
                ]
            ]);
        }

        return redirect()->action('Dashboard\Click2CallController@index')->with('doneMessage', __('backend.addDone'));
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
            return redirect()->action('Dashboard\Click2CallController@index');
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

        return view("dashboard.click2call.edit", compact("Users", "User", "GeneralWebmasterSections"));
    }

    /**
     * Delete a created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        if (!\App\Helpers\Helper::canDelete('people.click2call')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        $User = User::find($id);
        $User->c2c_channel = 0;
        $User->click_agent_number = '';
        $User->save();

        return redirect()->action('Dashboard\Click2CallController@index')->with('doneMessage', __('backend.addDone'));
    }

}
