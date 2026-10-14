<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\ImapSetting;
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

class ImapController extends Controller
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

        $ImapSettings = ImapSetting::select(['imap_settings.*', 'users.name as user_name'])
                ->join('users','imap_settings.user_id', 'users.id')->orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.imap.list", compact("ImapSettings", "GeneralWebmasterSections"));
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
        
        $Users = User::orderBy('name')->get();
        $folderOptions = "<option value='INBOX'>INBOX</option>";

        return view("dashboard.imap.create", compact("Users", "folderOptions", "GeneralWebmasterSections"));
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
            'user_id' => 'required',
            'username' => 'required',
            'host' => 'required',
            'password' => 'required',
            'folder' => 'required',
        ]);
        $whitelist_emails = '';
        if(!empty($request->whitelist_emails))
        {
            $arr = explode(',', $request->whitelist_emails);
            $arr = array_map('trim', $arr);
            $whitelist_emails = implode(',', $arr);
        }

        $ImapSetting = new ImapSetting;
        $ImapSetting->user_id = $request->user_id;
        $ImapSetting->username = $request->username;
        $ImapSetting->host = $request->host;
        $ImapSetting->port = $request->port;
        $ImapSetting->password = $request->password;
        $ImapSetting->encryption = $request->encryption;
        $ImapSetting->folder = $request->folder;
        $ImapSetting->whitelist_emails = $whitelist_emails;
        $ImapSetting->status = ($request->active_status) ? "1" : "0";
        $ImapSetting->save();

        return redirect()->action('Dashboard\ImapController@index')->with('doneMessage', __('backend.addDone'));
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
        
        $Users = User::orderBy('name')->get();
        $ImapSetting = ImapSetting::find($id);
        $folderOptions = "<option value='INBOX'>INBOX</option>";
        if(empty($ImapSetting))
        {
            $ImapSetting = new ImapSetting;
        }
        else if($ImapSetting->status == 1)
        {
            $folders = [];
            @$inbox=imap_open("{".$ImapSetting->host.":".$ImapSetting->port."/".$ImapSetting->encryption."}".$ImapSetting->folder."", $ImapSetting->username, $ImapSetting->password);
            if(!empty($inbox))
            {
                $folders = imap_list($inbox, "{".$ImapSetting->host.":".$ImapSetting->port."/".$ImapSetting->encryption."}", "*");
                imap_close($inbox);
            }
            $folderOptions = "";
            foreach($folders as $folder)
            {
                $fn = str_replace("{".$ImapSetting->host.":".$ImapSetting->port."/".$ImapSetting->encryption."}", '', $folder);
                $folderOptions .= "<option value='$fn' ".($ImapSetting->folder == $fn ? 'selected' : '').">$fn</option>";
            }
        }
        
        return view("dashboard.imap.edit", compact("ImapSetting", "folderOptions", "Users",  "GeneralWebmasterSections"));
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
        $ImapSetting = ImapSetting::find($id);
        if(!empty($ImapSetting))
        {
            $this->validate($request, [
                'user_id' => 'required',
                'username' => 'required',
                'host' => 'required',
                'password' => 'required',
                'folder' => 'required',
            ]);
            $whitelist_emails = '';
            if(!empty($request->whitelist_emails))
            {
                $arr = explode(',', $request->whitelist_emails);
                $arr = array_map('trim', $arr);
                $whitelist_emails = implode(',', $arr);
            }

            $ImapSetting->user_id = $request->user_id;
            $ImapSetting->username = $request->username;
            $ImapSetting->host = $request->host;
            $ImapSetting->port = $request->port;
            $ImapSetting->password = $request->password;
            $ImapSetting->encryption = $request->encryption;
            $ImapSetting->folder = $request->folder;
            $ImapSetting->whitelist_emails = $whitelist_emails;
            $ImapSetting->status = ($request->active_status) ? "1" : "0";
            $ImapSetting->save();
            return redirect()->action('Dashboard\ImapController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\ImapController@index');
        }
    }
    
    public function fetchFolders(Request $request)
    {
        @$inbox=imap_open("{".$request->host.":".$request->port."/".$request->encryption."}".$request->folder."", $request->username, $request->password);
//			print_r(imap_errors());die;
        if(!empty($inbox))
        {
            $folders = imap_list($inbox, "{".$request->host.":".$request->port."/".$request->encryption."}", "*");
            imap_close($inbox);
        }
        $html = "";
        foreach($folders as $folder)
        {
            $fn = str_replace("{".$request->host.":".$request->port."/".$request->encryption."}", '', $folder);
            $html .= "<option value='$fn'>$fn</option>";
        }
        echo $html;
    }

}
