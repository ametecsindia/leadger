<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\ProductService;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class ProductsServicesController extends Controller
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
    public function index(Request $request)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $openModal = $request->get('open', null);
        $editProduct = null;
        if ($openModal === 'edit' && $request->has('id')) {
            $editProduct = ProductService::find($request->get('id'));
        }

        return view("dashboard.productservices.list", compact("GeneralWebmasterSections", "openModal", "editProduct"));
    }

    public function productServicesData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = $request->input('order.0.dir');
        $q = $request->input('find_q');
        $order = $columns[$request->input('order.0.column')]['data'];
        if ($order == "") {
            $order = "id";
        }
        
        $ProductServices = ProductService::select(['product_services.*']);

        if ($q != "") {
            $ProductServices->where('name', 'like', '%' . $q . '%');
        }

        $totalData = $ProductServices->count();
        $totalFiltered = $totalData;
//order, paginate
        $ProductServices = $ProductServices->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->orderBy("id", "asc")
            ->get();

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($ProductServices as $ProductService) {
                $nestedData = [];
                $nestedData['id'] = $ProductService->id;
                $nestedData['name'] = $ProductService->name;
                $nestedData['order'] = $ProductService->seq_order;
                $nestedData['description'] = $ProductService->description;
                $actionurl = route("productServicesEdit", ["id" => $ProductService->id]);
                $nestedData['options'] = <<<AAA
                        <div class="action-items">
                        <a href="$actionurl" data-id="{$ProductService->id}" data-name="{$ProductService->name}">
                                        <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i>
                                        </small>
                                    </a>
                        </div>
                        AAA;
                
                $data[] = $nestedData;
            }
        }
        $statics = [];
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => $statics
        );
        echo json_encode($json_data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('productServices', ['open' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $name = trim($request->name);

        if (empty($name)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product name is required.'
                ], 422);
            }
            return redirect()->back()->withErrors(['name' => 'Product name is required.'])->withInput();
        }

        $validator = Validator::make(['name' => $name], [
            'name' => 'required|unique:product_services,name',
        ], [
            'name.required' => 'Product name is required.',
            'name.unique' => 'This product name already exists.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first('name')
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $maxOrder = ProductService::max('seq_order') ?? 0;

        $ProductService = new ProductService;
        $ProductService->name = $name;
        $ProductService->seq_order = $request->seq_order ?? ($maxOrder + 1);
        $ProductService->description = null;
        $ProductService->status = 1;
        $ProductService->created_by = Auth::user()->id;
        $ProductService->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Product added successfully',
                'data' => $ProductService
            ]);
        }

        return redirect()->route('productServices')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ProductService = ProductService::find($id);
        if (!empty($ProductService)) {
            return redirect()->route('productServices', ['open' => 'edit', 'id' => $id]);
        } else {
            return redirect()->route('productServices');
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
        $ProductService = ProductService::find($id);
        if (!empty($ProductService)) {
            $name = trim($request->name);

            if (empty($name)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product name is required.'
                    ], 422);
                }
                return redirect()->back()->withErrors(['name' => 'Product name is required.'])->withInput();
            }

            if ($name != $ProductService->name) {
                $validator = Validator::make(['name' => $name], [
                    'name' => 'required|unique:product_services,name',
                ], [
                    'name.required' => 'Product name is required.',
                    'name.unique' => 'This product name already exists.',
                ]);

                if ($validator->fails()) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first('name')
                        ], 422);
                    }
                    return redirect()->back()->withErrors($validator)->withInput();
                }
            }

            $ProductService->name = $name;
            $ProductService->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product updated successfully',
                    'data' => $ProductService
                ]);
            }

            return redirect()->route('productServices')->with('doneMessage', __('backend.saveDone'));
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            }
            return redirect()->route('productServices');
        }
    }

    /**
     * Delete a product / service (Options -> Delete). Same permission as Segments: the role's delete flag (Admin /
     * Webmaster always). One still chosen on leads (leads.product_service holds a comma-separated list of ids) is not
     * deleted: those leads would lose it.
     */
    public function destroy($id, Request $request)
    {
        if (!\App\Helpers\Helper::canDelete()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
        }
        $ProductService = ProductService::find($id);
        if (empty($ProductService)) {
            return response()->json(['success' => false, 'message' => 'Product not found. It may already have been deleted.'], 404);
        }
        $tp = DB::getTablePrefix();
        $leads = DB::table('leads')->whereRaw("FIND_IN_SET(?, REPLACE({$tp}leads.product_service, ' ', ''))", [(string)$ProductService->id])->count();
        if ($leads > 0) {
            return response()->json(['success' => false, 'message' => "This product cannot be deleted: it is selected on {$leads} lead"
                . ($leads === 1 ? '' : 's') . '. Remove it from those leads first.'], 422);
        }
        $ProductService->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
    }

}
