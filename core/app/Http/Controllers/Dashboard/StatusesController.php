<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Status;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class StatusesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // Role permissions (add / edit / delete flags) enforced on the server, not only in the UI
        // Create / Edit / Delete: role permissions master_data.* (config/permissions.php, CheckPermission middleware)
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dispositionSettings()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Statuses = Status::with('substatuses')->orderby("seq_order", "asc")->orderby("id", "asc")->get();

        return view("dashboard.statuses.dispositionSettings", compact("Statuses", "GeneralWebmasterSections"));
    }

    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Statuses = Status::with('substatuses')->orderby("seq_order", "asc")->orderby("id", "asc")->get();
        
        return view("dashboard.statuses.list", compact("Statuses", "GeneralWebmasterSections"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Add Status is the shared popup on the Status page (same form as Edit Status)
        return redirect()->route('statuses', ['add' => 1]);
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
            'name' => 'required|max:255|unique:statuses,name',
            'description' => 'nullable|string|max:2000',
            'substatuses' => 'nullable|array',
            'substatuses.*' => 'nullable|string|max:255',
        ], ['name.unique' => 'A status with this name already exists.']);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $Status = DB::transaction(function () use ($request) {
            $Status = new Status;
            $Status->name = trim($request->name);
            $Status->seq_order = (int)Status::max('seq_order') + 1;
            $Status->description = $request->description;
            $Status->lead_pool = empty($request->lead_pool) ? 0 : 1;
            $Status->status = 1;
            $Status->created_by = Auth::user()->id;
            $Status->save();
            $this->syncSubstatuses($Status, (array)$request->input('substatuses', []), []);
            return $Status;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status added successfully.', 'status' => $this->statusPayload($Status)]);
        }
        return redirect()->action('Dashboard\StatusesController@index')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $Status = Status::find($id);
        if (!empty($Status)) {
            return redirect()->route('statuses', ['edit' => $id]);
        } else {
            return redirect()->action('Dashboard\StatusesController@index');
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
        $Status = Status::find($id);
        if(!empty($Status))
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255' . (trim((string)$request->name) != $Status->name ? '|unique:statuses,name' : ''),
                'seq_order' => 'nullable|integer',
                'description' => 'nullable|string|max:2000',
                'substatuses' => 'nullable|array',
                'substatuses.*' => 'nullable|string|max:255',
                'remove_substatus_ids' => 'nullable|array',
            ], ['name.unique' => 'A status with this name already exists.']);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $notes = DB::transaction(function () use ($request, $Status) {
                $Status->name = trim($request->name);
                if ($request->has('seq_order') && $request->seq_order !== null && $request->seq_order !== '') {
                    $Status->seq_order = $request->seq_order;
                }
                $Status->description = $request->description;
                if ($request->has('lead_pool')) {
                    $Status->lead_pool = empty($request->lead_pool) ? 0 : 1;
                }
                $Status->save();
                return $this->syncSubstatuses($Status, (array)$request->input('substatuses', []), (array)$request->input('remove_substatus_ids', []));
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $notes ? 'Status updated. ' . implode(' ', $notes) : 'Status updated successfully.',
                    'status' => $this->statusPayload($Status),
                ]);
            }

            return redirect()->action('Dashboard\StatusesController@index')->with('doneMessage', __('backend.saveDone'));
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status not found'
                ], 404);
            }
            return redirect()->action('Dashboard\StatusesController@index');
        }
    }

    /**
     * Add the given substatus names to a status (case-insensitive duplicates skipped) and remove the
     * selected ones. A substatus still used by leads or call logs is kept, so no record loses its value;
     * removing needs the same delete permission as before. Returns notes for the user.
     */
    private function syncSubstatuses(Status $Status, array $names, array $removeIds): array
    {
        $notes = [];
        $removeIds = array_filter(array_map('intval', $removeIds));
        if ($removeIds) {
            if (!\App\Helpers\Helper::canDelete('master_data.statuses')) {
                $notes[] = 'Substatuses were not removed: delete operations are restricted to Admin.';
            } else {
                foreach (\App\Models\Substatus::where('status_id', $Status->id)->whereIn('id', $removeIds)->get() as $sub) {
                    $used = DB::table('leads')->where('substatus', $sub->id)->count()
                        + DB::table('call_logs')->where('substatus_id', $sub->id)->count()
                        + DB::table('dialer_logs')->where('substatus_id', $sub->id)->count();
                    if ($used > 0) {
                        $notes[] = "\"{$sub->name}\" is used by {$used} lead/call record(s) and was kept.";
                        continue;
                    }
                    $sub->delete();
                }
            }
        }

        $existing = \App\Models\Substatus::where('status_id', $Status->id)->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();
        $order = (int)\App\Models\Substatus::where('status_id', $Status->id)->max('seq_order');
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '' || in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }
            $sub = new \App\Models\Substatus();
            $sub->status_id = $Status->id;
            $sub->name = $name;
            $sub->seq_order = ++$order;
            $sub->status = 1;
            $sub->created_by = Auth::id() ?? 1;
            $sub->save();
            $existing[] = mb_strtolower($name);
        }
        return $notes;
    }

    private function statusPayload(Status $Status): array
    {
        return [
            'id' => $Status->id,
            'name' => $Status->name,
            'seq_order' => $Status->seq_order,
            'description' => $Status->description,
            'substatuses' => $Status->substatuses()->get(['id', 'name'])->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values(),
        ];
    }

    public function ajaxStoreSubstatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status_id' => 'required',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $exists = \App\Models\Substatus::where('status_id', $request->status_id)->where('name', trim($request->name))->first();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Sub-disposition already exists.'], 422);
        }

        $sub = new \App\Models\Substatus();
        $sub->status_id = $request->status_id;
        $sub->name = trim($request->name);
        $sub->seq_order = \App\Models\Substatus::where('status_id', $request->status_id)->count() + 1;
        $sub->status = 1;
        $sub->created_by = Auth::id() ?? 1;
        $sub->save();

        return response()->json([
            'success' => true,
            'message' => 'Sub-disposition added successfully.',
            'substatus' => [
                'id' => $sub->id,
                'name' => $sub->name,
                'status_id' => $sub->status_id
            ]
        ]);
    }

    public function ajaxDeleteSubstatus(Request $request)
    {
        if (!\App\Helpers\Helper::canDelete('master_data.statuses')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
        }
        $sub = \App\Models\Substatus::find($request->id);
        if ($sub) {
            $sub->delete();
            return response()->json(['success' => true, 'message' => 'Sub-disposition removed.']);
        }
        return response()->json(['success' => false, 'message' => 'Sub-disposition not found.'], 404);
    }

    /** "Only after a connected call": the status cannot be saved for a call that did not connect. */
    public function ajaxToggleConnected(Request $request)
    {
        $status = Status::find($request->status_id);
        if (!$status) {
            return response()->json(['success' => false, 'message' => 'Status not found.'], 404);
        }
        $status->needs_connected_call = $request->boolean('needs_connected_call');
        $status->save();
        return response()->json(['success' => true, 'message' => 'Connected-call requirement updated.']);
    }

    public function ajaxToggleFollowup(Request $request)
    {
        $status = Status::find($request->status_id);
        if ($status) {
            $status->lead_pool = $request->requires_followup ? 1 : 0;
            $status->save();
            return response()->json(['success' => true, 'message' => 'Follow-up requirement updated.']);
        }
        return response()->json(['success' => false, 'message' => 'Status not found.'], 404);
    }

}

