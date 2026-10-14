<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\ClientType;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class ClientTypesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // Role permissions (add / edit / delete flags) enforced on the server, not only in the UI
        $this->middleware('lg.permission:add')->only(['create', 'store']);
        $this->middleware('lg.permission:edit')->only(['edit', 'update']);
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
        
        return view("dashboard.clienttypes.list", compact("GeneralWebmasterSections"));
    }

    public function productServicesData(Request $request)
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
        
        $ClientTypes = ClientType::select(['client_types.*']);

        if ($q != "") {
            $ClientTypes->where('name', 'like', '%' . $q . '%');
        }

        $totalData = $ClientTypes->count();
        $totalFiltered = $totalData;
//order, paginate
        $ClientTypes = $ClientTypes->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->orderBy("id", "asc")
            ->get();

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($ClientTypes as $ClientType) {
                $nestedData = [];
                $nestedData['name'] = $ClientType->name;
                
                $nestedData['description'] = $ClientType->description;
                $actionurl = route("clientTypesEdit",["id"=>$ClientType->id]);
                $nestedData['options'] = <<<AAA
                        <div class="action-items">
                        <a 
                                       href="$actionurl">
                                        <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i>
                                        </small>
                                    </a>
                        AAA;
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
        
        $ClientType = DB::select("select count(*) as cnt from ".env('DB_TABLE_PREFIX')."client_types");
        $Count = $ClientType[0]->cnt + 1;

        return view("dashboard.clienttypes.create", compact("GeneralWebmasterSections", "Count"));
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
            'name' => 'required|unique:client_types,name',
        ]);

        $ClientType = new ClientType;
        $ClientType->name = $request->name;
        
        $ClientType->description = $request->description;
        $ClientType->status = 1;
        $ClientType->created_by = Auth::user()->id;
        $ClientType->save();

        return redirect()->action('Dashboard\ClientTypesController@index')->with('doneMessage', __('backend.addDone'));
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

        $clientType = ClientType::find($id);
        $clientTypeCnt = DB::select("select count(*) as cnt from ".env('DB_TABLE_PREFIX')."client_types");
        $Count = $clientTypeCnt[0]->cnt;

        if (!empty($clientType)) {
            return view("dashboard.clienttypes.edit", compact("clientType", "Count", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\ClientTypesController@index');
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
        $ClientType = ClientType::find($id);
        if(!empty($ClientType))
        {
            if ($request->name != $ClientType->name) {


                $this->validate($request, [
                    'name' => 'required|unique:client_types,name',
                ]);
            }

            //if ($id != 1) {
            $ClientType->name = $request->name;
            
            $ClientType->description = $request->description;
            $ClientType->save();
            return redirect()->action('Dashboard\ClientTypesController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\ClientTypesController@index');
        }
    }

}
