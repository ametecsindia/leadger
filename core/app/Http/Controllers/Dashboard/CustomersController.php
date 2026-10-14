<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\AllocationHistory;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\ImageUpload;
use App\Models\Lead;
use App\Models\Review;
use App\Models\Status;
use App\Models\Substatus;
use App\Models\TrackingHistory;
use App\Models\ProductService;
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

class CustomersController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        $id = 'Deal';

        return view("dashboard.customers.list", compact("GeneralWebmasterSections", "id"));
    }
}
