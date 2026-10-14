<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
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

class WorkLocationsController extends Controller
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

        $WorkLocations = WorkLocation::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.worklocations.list", compact("WorkLocations", "GeneralWebmasterSections"));
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
        
        return view("dashboard.worklocations.create", compact("GeneralWebmasterSections"));
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
            'name' => 'required|unique:work_locations',
            'lat' => 'required',
            'long' => 'required',
            'radius' => 'required'
        ]);

        $WorkLocation = new WorkLocation;
        $WorkLocation->name = $request->name;
        $WorkLocation->lat = $request->lat;
        $WorkLocation->long = $request->long;
        $WorkLocation->radius = $request->radius;
        $WorkLocation->save();

        return redirect()->action('Dashboard\WorkLocationsController@index')->with('doneMessage', __('backend.addDone'));
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

        $WorkLocation = WorkLocation::find($id);

        if (!empty($WorkLocation)) {
            return view("dashboard.worklocations.edit", compact("WorkLocation", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\WorkLocationsController@index');
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
        $WorkLocation = WorkLocation::find($id);
        if(!empty($WorkLocation) && $id != 1)
        {
            if ($request->name != $WorkLocation->name) {

                $this->validate($request, [
                    'name' => 'required|unique:work_locations',
                ]);
            }
            $this->validate($request, [
                'lat' => 'required',
                'long' => 'required',
                'radius' => 'required'
            ]);

            //if ($id != 1) {
            $WorkLocation->name = $request->name;
            $WorkLocation->lat = $request->lat;
            $WorkLocation->long = $request->long;
            $WorkLocation->radius = $request->radius;
            $WorkLocation->save();
            return redirect()->action('Dashboard\WorkLocationsController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\WorkLocationsController@index');
        }
    }

}
