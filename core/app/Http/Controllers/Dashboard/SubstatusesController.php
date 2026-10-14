<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Status;
use App\Models\Substatus;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class SubstatusesController extends Controller
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

        $Substatuses = Substatus::select(['substatuses.*', 'statuses.name as status_name'])->join('statuses','statuses.id', 'substatuses.status_id')->orderby("substatuses.seq_order", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        $Statuses = Status::orderby('name', 'asc')->get();
        $Substatuscnt = DB::select("select count(*) as cnt from ".env('DB_TABLE_PREFIX')."substatuses");
        $Count = !empty($Substatuscnt) ? $Substatuscnt[0]->cnt : 10;

        return view("dashboard.substatuses.list", compact("Substatuses", "GeneralWebmasterSections", "Statuses", "Count"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('substatuses', ['create' => 1]);
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
            'name' => 'required|unique:substatuses,name',
            'status_id' => 'required|exists:statuses,id',
            'seq_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $Substatus = new Substatus;
        $Substatus->name = $request->name;
        $Substatus->status_id = $request->status_id;
        $Substatus->seq_order = $request->seq_order ?? 0;
        $Substatus->description = $request->description;
        $Substatus->status = 1;
        $Substatus->created_by = Auth::user()->id;
        $Substatus->save();

        $parentStatus = Status::find($Substatus->status_id);
        $statusName = $parentStatus ? $parentStatus->name : '';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('backend.addDone'),
                'substatus' => [
                    'id' => $Substatus->id,
                    'name' => $Substatus->name,
                    'status_id' => $Substatus->status_id,
                    'status_name' => $statusName,
                    'seq_order' => $Substatus->seq_order,
                    'description' => $Substatus->description,
                ]
            ]);
        }

        return redirect()->action('Dashboard\SubstatusesController@index')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $Substatus = Substatus::find($id);
        if (!empty($Substatus)) {
            return redirect()->route('substatuses', ['edit' => $id]);
        } else {
            return redirect()->action('Dashboard\SubstatusesController@index');
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
        $Substatus = Substatus::find($id);
        if(!empty($Substatus))
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required' . ($request->name != $Substatus->name ? '|unique:substatuses,name' : ''),
                'status_id' => 'required|exists:statuses,id',
                'seq_order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $Substatus->name = $request->name;
            $Substatus->status_id = $request->status_id;
            if ($request->has('seq_order') && $request->seq_order !== null && $request->seq_order !== '') {
                $Substatus->seq_order = $request->seq_order;
            }
            if ($request->has('description')) {
                $Substatus->description = $request->description;
            }
            $Substatus->save();

            $parentStatus = Status::find($Substatus->status_id);
            $statusName = $parentStatus ? $parentStatus->name : '';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('backend.saveDone'),
                    'substatus' => [
                        'id' => $Substatus->id,
                        'name' => $Substatus->name,
                        'status_id' => $Substatus->status_id,
                        'status_name' => $statusName,
                        'seq_order' => $Substatus->seq_order,
                        'description' => $Substatus->description,
                    ]
                ]);
            }

            return redirect()->action('Dashboard\SubstatusesController@index')->with('doneMessage', __('backend.saveDone'));
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Substatus not found'
                ], 404);
            }
            return redirect()->action('Dashboard\SubstatusesController@index');
        }
    }

}
