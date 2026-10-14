<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Shift;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class ShiftsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // Role permissions (add / edit / delete flags) enforced on the server, not only in the UI
        $this->middleware('lg.permission:add')->only(['create', 'store', 'ajaxStore']);
        $this->middleware('lg.permission:edit')->only(['edit', 'update', 'ajaxUpdate']);
        $this->middleware('lg.permission:delete')->only(['ajaxDelete']);
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

        $Shifts = Shift::orderby("id", "asc")->paginate(env('BACKEND_PAGINATION'));
        
        return view("dashboard.shifts.list", compact("Shifts", "GeneralWebmasterSections"));
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

        return view("dashboard.shifts.create", compact("GeneralWebmasterSections"));
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
            'name' => 'required|unique:shifts',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $Shift = new Shift;
        $Shift->name = $request->name;
        $Shift->start_time = date('H:i',strtotime($request->start_time));
        $Shift->end_time = date('H:i',strtotime($request->end_time));
        $Shift->save();

        return redirect()->action('Dashboard\ShiftsController@index')->with('doneMessage', __('backend.addDone'));
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

        $Shift = Shift::find($id);

        if (!empty($Shift)) {
            return view("dashboard.shifts.edit", compact("Shift", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\ShiftsController@index');
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
        $Shift = Shift::find($id);
        if(!empty($Shift))
        {
            if ($request->name != $Shift->name) {

                $this->validate($request, [
                    'name' => 'required|unique:shifts',
                ]);
            }
            $this->validate($request, [
                'start_time' => 'required',
                'end_time' => 'required',
            ]);

            //if ($id != 1) {
            $Shift->name = $request->name;
            $Shift->start_time = date('H:i',strtotime($request->start_time));
            $Shift->end_time = date('H:i',strtotime($request->end_time));
            $Shift->save();
            return redirect()->action('Dashboard\ShiftsController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\ShiftsController@index');
        }
    }

    /**
     * Get JSON list of all shifts.
     */
    public function ajaxList()
    {
        $shifts = Shift::orderBy('id', 'asc')->get()->map(function($s) {
            $startFormatted = $s->start_time ? date('h:i A', strtotime($s->start_time)) : '';
            $endFormatted = $s->end_time ? date('h:i A', strtotime($s->end_time)) : '';
            return [
                'id' => $s->id,
                'name' => $s->name,
                'start_time' => $s->start_time ? date('H:i', strtotime($s->start_time)) : '',
                'end_time' => $s->end_time ? date('H:i', strtotime($s->end_time)) : '',
                'start_time_formatted' => $startFormatted,
                'end_time_formatted' => $endFormatted,
                'formatted_label' => $s->name . ($startFormatted && $endFormatted ? ' – ' . $startFormatted . ' – ' . $endFormatted : '')
            ];
        });

        return response()->json(['status' => 'success', 'shifts' => $shifts]);
    }

    /**
     * Store a new shift via AJAX.
     */
    public function ajaxStore(Request $request)
    {
        $name = trim($request->name ?? '');
        $startTime = trim($request->start_time ?? '');
        $endTime = trim($request->end_time ?? '');

        if (empty($name)) {
            return response()->json(['status' => 'error', 'message' => 'Shift Name is required.'], 422);
        }
        if (empty($startTime)) {
            return response()->json(['status' => 'error', 'message' => 'Start Time is required.'], 422);
        }
        if (empty($endTime)) {
            return response()->json(['status' => 'error', 'message' => 'End Time is required.'], 422);
        }
        if ($startTime === $endTime) {
            return response()->json(['status' => 'error', 'message' => 'Start Time and End Time cannot be the same.'], 422);
        }

        $existing = Shift::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return response()->json(['status' => 'error', 'message' => 'A shift named "' . $name . '" already exists.'], 422);
        }

        $shift = new Shift();
        $shift->name = $name;
        $shift->start_time = date('H:i', strtotime($startTime));
        $shift->end_time = date('H:i', strtotime($endTime));
        $shift->save();

        $startFormatted = date('h:i A', strtotime($shift->start_time));
        $endFormatted = date('h:i A', strtotime($shift->end_time));

        return response()->json([
            'status' => 'success',
            'message' => 'Shift created successfully.',
            'shift' => [
                'id' => $shift->id,
                'name' => $shift->name,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'start_time_formatted' => $startFormatted,
                'end_time_formatted' => $endFormatted,
                'formatted_label' => $shift->name . ' – ' . $startFormatted . ' – ' . $endFormatted
            ]
        ]);
    }

    /**
     * Update an existing shift via AJAX.
     */
    public function ajaxUpdate(Request $request, $id)
    {
        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json(['status' => 'error', 'message' => 'Shift not found.'], 404);
        }

        $name = trim($request->name ?? '');
        $startTime = trim($request->start_time ?? '');
        $endTime = trim($request->end_time ?? '');

        if (empty($name)) {
            return response()->json(['status' => 'error', 'message' => 'Shift Name is required.'], 422);
        }
        if (empty($startTime)) {
            return response()->json(['status' => 'error', 'message' => 'Start Time is required.'], 422);
        }
        if (empty($endTime)) {
            return response()->json(['status' => 'error', 'message' => 'End Time is required.'], 422);
        }
        if ($startTime === $endTime) {
            return response()->json(['status' => 'error', 'message' => 'Start Time and End Time cannot be the same.'], 422);
        }

        $existing = Shift::whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->first();
        if ($existing) {
            return response()->json(['status' => 'error', 'message' => 'A shift named "' . $name . '" already exists.'], 422);
        }

        $shift->name = $name;
        $shift->start_time = date('H:i', strtotime($startTime));
        $shift->end_time = date('H:i', strtotime($endTime));
        $shift->save();

        $startFormatted = date('h:i A', strtotime($shift->start_time));
        $endFormatted = date('h:i A', strtotime($shift->end_time));

        return response()->json([
            'status' => 'success',
            'message' => 'Shift updated successfully.',
            'shift' => [
                'id' => $shift->id,
                'name' => $shift->name,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'start_time_formatted' => $startFormatted,
                'end_time_formatted' => $endFormatted,
                'formatted_label' => $shift->name . ' – ' . $startFormatted . ' – ' . $endFormatted
            ]
        ]);
    }

    /**
     * Delete a shift via AJAX.
     */
    public function ajaxDelete($id)
    {
        if ($id == 1) {
            return response()->json(['status' => 'error', 'message' => 'Default system shift cannot be deleted.'], 403);
        }

        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json(['status' => 'error', 'message' => 'Shift not found.'], 404);
        }

        // Reassign any users who had this shift to default shift (1)
        \App\Models\User::where('shift_id', $id)->update(['shift_id' => 1]);

        $shift->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shift deleted successfully.'
        ]);
    }

}
