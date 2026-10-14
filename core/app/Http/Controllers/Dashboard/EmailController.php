<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\EmailSetting;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
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

        $EmailSettings = EmailSetting::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.email.list", compact("EmailSettings", "GeneralWebmasterSections"));
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

        return view("dashboard.email.create", compact("GeneralWebmasterSections"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->driver == 'smtp')
        {
            $this->validate($request, [
                'name' => 'required|unique:email_settings',
                'driver' => 'required',
                'from_email' => 'required',
                'host' => 'required',
                'port' => 'required',
                'username' => 'required',
                'password' => 'required',
            ]);
        }
        else
        {
            $this->validate($request, [
                'name' => 'required|unique:email_settings',
                'driver' => 'required',
                'from_email' => 'required',
            ]);
        }

        $EmailSetting = new EmailSetting;
        $EmailSetting->name = $request->name;
        $EmailSetting->driver = $request->driver;
        $EmailSetting->from_email = $request->from_email;
        $EmailSetting->host = $request->host;
        $EmailSetting->port = $request->port;
        $EmailSetting->username = $request->username;
        $EmailSetting->password = $request->password;
        $EmailSetting->status = ($request->active_status) ? "1" : "0";
        $EmailSetting->created_by = Auth::user()->id;
        $EmailSetting->save();

        return redirect()->action('Dashboard\EmailController@index')->with('doneMessage', __('backend.addDone'));
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

        $EmailSetting = EmailSetting::find($id);

        if (!empty($EmailSetting)) {
            return view("dashboard.email.edit", compact("EmailSetting", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\EmailController@index');
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
        $EmailSetting = EmailSetting::find($id);
        if(!empty($EmailSetting))
        {
            if ($request->name != $EmailSetting->name) {

                $this->validate($request, [
                    'name' => 'required|unique:email_settings',
                ]);
            }
            if($request->driver == 'smtp')
            {
                $this->validate($request, [
                    'driver' => 'required',
                    'from_email' => 'required',
                    'host' => 'required',
                    'port' => 'required',
                    'username' => 'required',
                    'password' => 'required',
                ]);
            }
            else
            {
                $this->validate($request, [
                    'driver' => 'required',
                    'from_email' => 'required',
                ]);
            }

            $EmailSetting->name = $request->name;
            $EmailSetting->driver = $request->driver;
            $EmailSetting->from_email = $request->from_email;
            $EmailSetting->host = $request->host;
            $EmailSetting->port = $request->port;
            $EmailSetting->username = $request->username;
            $EmailSetting->password = $request->password;
            $EmailSetting->status = ($request->active_status) ? "1" : "0";
            $EmailSetting->save();
            return redirect()->action('Dashboard\EmailController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\EmailController@index');
        }
    }

}
