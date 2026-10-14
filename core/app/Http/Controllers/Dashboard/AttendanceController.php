<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\User;
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

class AttendanceController extends Controller
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

        $res = DB::select("select * from ".env('DB_TABLE_PREFIX')."tracking_logs where user_id = ".Auth::user()->id." and date(created_at)>='".date('Y-m-01')."' order by created_at desc");
        
        return view("dashboard.attendance.list", compact("res", "GeneralWebmasterSections"));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function onDuty()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $users = [];
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $users = User::whereIn('team_id',explode(',',Auth::user()->team_id))->get();
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $users = User::where('id', Auth::user()->id)->get();
        }
        else
        {
            $users = User::all();
        }
        $permissions = Permissions::all();

        return view("dashboard.attendance.onDuty", compact("GeneralWebmasterSections", "users", "permissions"));
    }

    public function onDutyData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }

        $date = $request->input('date');
        $user = $request->input('user');
        $permission = $request->input('permission');
        $condition= '';
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $condition = " and ".env('DB_TABLE_PREFIX')."users.team_id in (".Auth::user()->team_id.") ";
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $condition = " and ".env('DB_TABLE_PREFIX')."users.id = ".Auth::user()->id." ";
        }

        if ($user != "") {
            $condition .= " and ".env('DB_TABLE_PREFIX')."tracking_logs.user_id = '".$user."' ";
        }
        if ($permission != "") {
            $condition .= " and ".env('DB_TABLE_PREFIX')."users.permission_id = '".$permission."' ";
        }
        $sql = " FROM
  (SELECT 
    ".env('DB_TABLE_PREFIX')."tracking_logs.user_id,
    MIN(start_at) AS start_at,
    MAX(end_at) AS end_at
  FROM
    ".env('DB_TABLE_PREFIX')."tracking_logs 
    INNER JOIN ".env('DB_TABLE_PREFIX')."users
    ON ".env('DB_TABLE_PREFIX')."tracking_logs.user_id = ".env('DB_TABLE_PREFIX')."users.id
    INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
    ON ".env('DB_TABLE_PREFIX')."users.permissions_id = ".env('DB_TABLE_PREFIX')."permissions.id
  WHERE DATE(start_at) = '".$date."' 
$condition
  GROUP BY ".env('DB_TABLE_PREFIX')."tracking_logs.user_id) a 
  INNER JOIN ".env('DB_TABLE_PREFIX')."users u 
    ON a.user_id = u.id 
  INNER JOIN ".env('DB_TABLE_PREFIX')."shifts 
    ON ".env('DB_TABLE_PREFIX')."shifts.id = u.shift_id 
  INNER JOIN ".env('DB_TABLE_PREFIX')."permissions 
    ON ".env('DB_TABLE_PREFIX')."permissions.id = u.permissions_id ";

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $res = DB::select("SELECT 
  u.id,
  u.name,
  ".env('DB_TABLE_PREFIX')."permissions.name AS role,
  start_time,end_time,
  start_at,
  end_at
                    ".$sql." order by $order $dir,u.id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['id'] = $row->id;
                $nestedData['name'] = $row->name;
                $nestedData['role'] = $row->role;
                $nestedData['shift'] = $row->start_time.' - '.$row->end_time;
                $nestedData['start_at'] = $row->start_at;
                $nestedData['end_at'] = $row->end_at;
                
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
    public function offDuty()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $users = [];
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $users = User::whereIn('team_id',explode(',',Auth::user()->team_id))->get();
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $users = User::where('id', Auth::user()->id)->get();
        }
        else
        {
            $users = User::all();
        }
        $permissions = Permissions::all();

        return view("dashboard.attendance.offDuty", compact("GeneralWebmasterSections", "users", "permissions"));
    }

    public function offDutyData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }

        $date = $request->input('date');
        $user = $request->input('user');
        $permission = $request->input('permission');
        $condition= '';
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $condition = " and u.team_id in (".Auth::user()->team_id.") ";
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $condition = " and u.id = ".Auth::user()->id." ";
        }

        if ($user != "") {
            $condition .= " and u.id = '".$user."' ";
        }
        if ($permission != "") {
            $condition .= " and u.permission_id = '".$permission."' ";
        }
        $sql = " FROM
".env('DB_TABLE_PREFIX')."users u 
  INNER JOIN ".env('DB_TABLE_PREFIX')."shifts 
    ON ".env('DB_TABLE_PREFIX')."shifts.id = u.shift_id 
  INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
    ON ".env('DB_TABLE_PREFIX')."permissions.id = u.permissions_id 
WHERE u.id NOT IN (SELECT user_id FROM ".env('DB_TABLE_PREFIX')."tracking_logs WHERE DATE(start_at) = '".$date."')
$condition ";

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $res = DB::select("SELECT 
  u.id,
  u.name,start_time,end_time,
  ".env('DB_TABLE_PREFIX')."permissions.name AS role
                    ".$sql." order by $order $dir,u.id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['id'] = $row->id;
                $nestedData['name'] = $row->name;
                $nestedData['role'] = $row->role;
                $nestedData['shift'] = $row->start_time.' - '.$row->end_time;
                $nestedData['options'] = '';
                if($date == date('Y-m-d'))
                {
                    $punchurl = route('punchUser',[$row->id]);
                    $nestedData['options'] = <<<AAA
                            <div class="action-items">
                            <a href="$punchurl"><small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Punch">&#xe90d;</i></small></a>
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
    
    public function punchUser($id)
    {
        $tracking = DB::select("select * from ".env('DB_TABLE_PREFIX')."tracking_logs where user_id = ".$id." and date(created_at) = current_date");
        if(empty($tracking)) {
            $logs = new TrackingLog;
            $logs->user_id = $id;
            $logs->start_at = date('Y-m-d H:i:s');
            $logs->created_by = Auth::user()->id;
            
            $logs->save();
        } else {
            DB::update(DB::raw("UPDATE ".env('DB_TABLE_PREFIX')."tracking_logs SET end_at='".date('Y-m-d H:i:s')."'
                    WHERE user_id = '".$id."' and date(created_at)= current_date "));
        }
        return redirect()->action('Dashboard\AttendanceController@offDuty')->with('doneMessage', 'Punched Successfully');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function history()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $users = [];
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $users = User::whereIn('team_id',explode(',',Auth::user()->team_id))->get();
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $users = User::where('id', Auth::user()->id)->get();
        }
        else
        {
            $users = User::all();
        }

        return view("dashboard.attendance.history", compact("GeneralWebmasterSections", "users"));
    }

    public function historyData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }

        $date = $request->input('date');
        $user = $request->input('user');
        $permission = $request->input('permission');
        $condition= '';
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $condition = " and u.team_id in (".Auth::user()->team_id.") ";
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $condition = " and u.id = ".Auth::user()->id." ";
        }

        if ($user != "") {
            $condition .= " and a.user_id = '".$user."' ";
        }
        $sql = " FROM
  ".env('DB_TABLE_PREFIX')."tracking_logs a
  INNER JOIN ".env('DB_TABLE_PREFIX')."users u 
    ON a.user_id = u.id 
  INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
    ON ".env('DB_TABLE_PREFIX')."permissions.id = u.permissions_id
    WHERE DATE(start_at) = '$date' 
$condition ";

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $res = DB::select("SELECT 
  u.id,
  u.name,
  ".env('DB_TABLE_PREFIX')."permissions.name AS role,
  start_at,
  end_at,
  a.lat,
  a.long,
  a.address,
  a.logout_lat,
  a.logout_long,
  a.logout_address
                    ".$sql." order by $order $dir,u.id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($res as $row) {
                $nestedData = [];
                $nestedData['id'] = $row->id;
                $nestedData['name'] = $row->name;
                $nestedData['role'] = $row->role;
                $nestedData['start_at'] = $row->start_at;
                $nestedData['end_at'] = $row->end_at;
                $nestedData['address'] = $row->address;
                $nestedData['logout_address'] = $row->logout_address;
                $nestedData['login_latlong'] = $row->address;
                $nestedData['logout_latlong'] = $row->logout_address;
                
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
    
    public function report(Request $request)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $users = [];
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $users = User::whereIn('team_id',explode(',',Auth::user()->team_id))->get();
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $users = User::where('id', Auth::user()->id)->get();
        }
        else
        {
            $users = User::all();
        }
        
        $sql = "SELECT ";
        $days = !isset($request->my) ? date('t') : date('t',strtotime($request->my));
        $ym = !isset($request->my) ? date('Y-m') : date('Y-m',strtotime($request->my));
        for($i=1;$i<=$days;$i++)
        {
            $sql .= "coalesce(GROUP_CONCAT(CASE WHEN DATE(t.created_at) = '".$ym.'-'.$i."' THEN CONCAT(TIME(start_at),' - ',COALESCE(TIME(end_at),'')) END),'') AS day$i, 
                    coalesce(GROUP_CONCAT(CASE WHEN DATE(t.created_at) = '".$ym.'-'.$i."' THEN lat END),'') AS lat$i, ";
        }
        $sql .= " ".env('DB_TABLE_PREFIX')."users.id,".env('DB_TABLE_PREFIX')."permissions.name AS role,".env('DB_TABLE_PREFIX')."users.name,start_time,end_time 
FROM ".env('DB_TABLE_PREFIX')."users
INNER JOIN ".env('DB_TABLE_PREFIX')."shifts
ON ".env('DB_TABLE_PREFIX')."shifts.id = ".env('DB_TABLE_PREFIX')."users.shift_id
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
ON ".env('DB_TABLE_PREFIX')."users.permissions_id = ".env('DB_TABLE_PREFIX')."permissions.id
LEFT JOIN ".env('DB_TABLE_PREFIX')."tracking_logs t ON ".env('DB_TABLE_PREFIX')."users.id = t.user_id
    where 1=1 
";
        if(in_array(Auth::user()->permissionsGroup->name,['Manager','Team Leader']))
        {
            $sql .= " and ".env('DB_TABLE_PREFIX')."users.team_id in (".Auth::user()->team_id.")";
        }
        else if(in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))
        {
            $sql .= " and ".env('DB_TABLE_PREFIX')."users.id = ".Auth::user()->id." ";
        }

        $staff_id = '';
        if(isset($request->staff_id) && $request->staff_id!= '') {
            $staff_id = $request->staff_id;
            $sql .= " and ".env('DB_TABLE_PREFIX')."users.id = $request->staff_id ";
        }
        $sql .= "GROUP BY ".env('DB_TABLE_PREFIX')."users.id order by name";
        $res = DB::select($sql);

        return view("dashboard.attendance.report", compact("GeneralWebmasterSections", "users", "res", "ym", "staff_id", "days"));
    }
}
