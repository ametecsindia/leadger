<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Source;
use App\Models\Lead;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class SourcesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // Role permissions (add / edit / delete flags) enforced on the server, not only in the UI
        $this->middleware('lg.permission:add')->only(['create', 'store']);
        $this->middleware('lg.permission:edit')->only(['edit', 'update']);
        $this->middleware('lg.permission:delete')->only(['destroy']);
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

        $Sources = Source::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.sources.list", compact("Sources", "GeneralWebmasterSections"));
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

        return view("dashboard.sources.create", compact("GeneralWebmasterSections"));
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
            'name' => 'required|unique:sources',
        ]);

        $Source = new Source;
        $Source->name = $request->name;
        $Source->description = $request->description;
        $Source->status = 1;
        $Source->created_by = Auth::user()->id;
        $Source->save();

        return redirect()->action('Dashboard\SourcesController@index')->with('doneMessage', __('backend.addDone'));
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

        $Source = Source::find($id);

        if (!empty($Source)) {
            $Sources = Source::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
            $editSource = $Source;
            return view("dashboard.sources.list", compact("Sources", "GeneralWebmasterSections", "editSource"));
        } else {
            return redirect()->action('Dashboard\SourcesController@index');
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
        $Source = Source::find($id);
        if(!empty($Source))
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required' . ($request->name != $Source->name ? '|unique:sources,name' : ''),
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

            $Source->name = $request->name;
            $Source->description = $request->description;
            $Source->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('backend.saveDone'),
                    'source' => [
                        'id' => $Source->id,
                        'name' => $Source->name,
                        'description' => $Source->description,
                    ]
                ]);
            }

            return redirect()->action('Dashboard\SourcesController@index')->with('doneMessage', __('backend.saveDone'));
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Source not found'
                ], 404);
            }
            return redirect()->action('Dashboard\SourcesController@index');
        }
    }

    /**
     * Delete a source (Options -> Delete). Same permission as Segments: the role's delete flag (Admin / Webmaster
     * always). A source still used by campaigns is not deleted: those campaigns would lose their source.
     */
    public function destroy($id, Request $request)
    {
        if (!\App\Helpers\Helper::canDelete()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
        }
        $Source = Source::find($id);
        if (empty($Source)) {
            return response()->json(['success' => false, 'message' => 'Source not found. It may already have been deleted.'], 404);
        }
        $campaigns = DB::table('campaigns')->where('source_id', $Source->id)->count();
        if ($campaigns > 0) {
            return response()->json(['success' => false, 'message' => "This source cannot be deleted: it is used by {$campaigns} campaign"
                . ($campaigns === 1 ? '' : 's') . '. Change their source first.'], 422);
        }
        $Source->delete();
        return response()->json(['success' => true, 'message' => 'Source deleted successfully.']);
    }

}
