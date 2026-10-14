<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\SmsGateway;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
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

        $SmsGateways = SmsGateway::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.sms.list", compact("SmsGateways", "GeneralWebmasterSections"));
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

        return view("dashboard.sms.create", compact("GeneralWebmasterSections"));
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
            'name' => 'required|unique:sms_gateways',
            'method' => 'required',
            'gateway_api_url' => 'required',
            'mobile_number_param' => 'required',
            'content_param' => 'required',
        ]);

        $SmsGateway = new SmsGateway;
        $SmsGateway->name = $request->name;
        $SmsGateway->method = $request->method;
        $SmsGateway->gateway_api_url = $request->gateway_api_url;
        $SmsGateway->mobile_number_param = $request->mobile_number_param;
        $SmsGateway->content_param = $request->content_param;
        $SmsGateway->status = ($request->active_status) ? "1" : "0";
        $SmsGateway->created_by = Auth::user()->id;
        $SmsGateway->save();

        return redirect()->action('Dashboard\SmsController@index')->with('doneMessage', __('backend.addDone'));
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

        $SmsGateway = SmsGateway::find($id);

        if (!empty($SmsGateway)) {
            return view("dashboard.sms.edit", compact("SmsGateway", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\SmsController@index');
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
        $SmsGateway = SmsGateway::find($id);
        if(!empty($SmsGateway))
        {
            if ($request->name != $SmsGateway->name) {

                $this->validate($request, [
                    'name' => 'required|unique:sms_gateways',
                ]);
            }
            $this->validate($request, [
                'method' => 'required',
                'gateway_api_url' => 'required',
                'mobile_number_param' => 'required',
                'content_param' => 'required',
            ]);

            //if ($id != 1) {
            $SmsGateway->name = $request->name;
            $SmsGateway->method = $request->method;
            $SmsGateway->gateway_api_url = $request->gateway_api_url;
            $SmsGateway->mobile_number_param = $request->mobile_number_param;
            $SmsGateway->content_param = $request->content_param;
            $SmsGateway->status = ($request->active_status) ? "1" : "0";
            $SmsGateway->save();
            return redirect()->action('Dashboard\SmsController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\SmsController@index');
        }
    }

}
