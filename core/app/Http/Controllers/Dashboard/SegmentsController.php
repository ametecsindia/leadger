<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Segment;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class SegmentsController extends Controller
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

        $Segments = Segment::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION', 10));
        
        return view("dashboard.segments.list", compact("Segments", "GeneralWebmasterSections"));
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

        return view("dashboard.segments.create", compact("GeneralWebmasterSections"));
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
            'name' => 'required|unique:segments,name',
        ], [
            'name.required' => 'Segment Name is required.',
            'name.unique' => 'A segment with this name already exists.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $Segment = new Segment;
        $Segment->name = trim($request->name);
        $Segment->description = $request->description;
        $Segment->status = 1;
        $Segment->created_by = Auth::user()->id ?? 1;
        $Segment->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => __('backend.addDone'), 'segment' => $Segment]);
        }

        return redirect()->action('Dashboard\SegmentsController@index')->with('doneMessage', __('backend.addDone'));
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

        $Segment = Segment::find($id);

        if (!empty($Segment)) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'segment' => $Segment]);
            }
            return view("dashboard.segments.edit", compact("Segment", "GeneralWebmasterSections"));
        } else {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Segment not found'], 404);
            }
            return redirect()->action('Dashboard\SegmentsController@index');
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
        $Segment = Segment::find($id);
        if (!empty($Segment)) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:segments,name,' . $id,
            ], [
                'name.required' => 'Segment Name is required.',
                'name.unique' => 'A segment with this name already exists.',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $Segment->name = trim($request->name);
            $Segment->description = $request->description;
            $Segment->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('backend.saveDone'), 'segment' => $Segment]);
            }

            return redirect()->action('Dashboard\SegmentsController@index')->with('doneMessage', __('backend.saveDone'));
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Segment not found'], 404);
            }
            return redirect()->action('Dashboard\SegmentsController@index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request = null)
    {
        $req = $request ?? request();
        if (!\App\Helpers\Helper::canDelete()) {
            if ($req->ajax() || $req->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }
        $Segment = Segment::find($id);
        if (!empty($Segment)) {
            $Segment->delete();
            if ($req->ajax() || $req->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('backend.deleteDone')]);
            }
            return redirect()->action('Dashboard\SegmentsController@index')->with('doneMessage', __('backend.deleteDone'));
        } else {
            if ($req->ajax() || $req->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Segment not found'], 404);
            }
            return redirect()->action('Dashboard\SegmentsController@index');
        }
    }


}

