<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Classes\Reply;
use App\Models\MobileDevice;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use Form;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class MobileDevicesController extends Controller
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
        // Check Permissions
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !optional(Auth::user()->permissionsGroup)->settings_status) {
                return redirect()->route('adminHome');
            }
            return $next($request);
        });

        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view("dashboard.mobiledevices.list", compact("GeneralWebmasterSections"));
    }

    public function mobileDevicesData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $q = $request->input('find_q');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $sql = " FROM ".env('DB_TABLE_PREFIX')."mobile_devices md
INNER JOIN ".env('DB_TABLE_PREFIX')."users u
ON md.user_id = u.id ";

        if ($q != "") {
            $sql .= " where (device_id like '%".$q."%' 
                        or u.name like '%".$q."%' )";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $MobileDevices = DB::select("SELECT md.*,u.name AS name
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($MobileDevices as $MobileDevice) {
                $nestedData = [];
                $nestedData['device_id'] = $MobileDevice->device_id;
                $nestedData['name'] = $MobileDevice->name;
                if($MobileDevice->status)
                {
                    $actionurl = route("updateMobileDevice",["id"=>$MobileDevice->id,"status"=>0]);
                    $nestedData['options'] = <<<AAA
                            <div class="action-items">
                            <a href="$actionurl"><small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="De-Activate">&#xe5c9;</i></small></a>
                            AAA;
                }
                else
                {
                    $actionurl = route("updateMobileDevice",["id"=>$MobileDevice->id,"status"=>1]);
                    $nestedData['options'] = <<<AAA
                            <div class="action-items">
                            <a href="$actionurl"><small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Activate">&#xe86c;</i></small></a>
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

    public function update($id, $status)
    {
        $MobileDevice = MobileDevice::find($id);
        $MobileDevice->status = $status;
        $MobileDevice->save();
        return redirect()->action('Dashboard\MobileDevicesController@index')->with('doneMessage', $status ? 'Actived Sucessfully': 'De-Activated Successfuly');
    }
}
