<?php

namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Models\WebmasterSection;
use Auth;
use File;
use Helper;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Check Permissions
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !optional(Auth::user()->permissionsGroup)->settings_status) {
                return redirect()->route('NoPermission');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        return view('dashboard.settings.files_manager', compact("GeneralWebmasterSections"));
    }

    public function manager(Request $request)
    {
        return view('dashboard.settings.file_manager');
    }
}
