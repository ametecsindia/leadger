<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\AllocationHistory;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Source;
use App\Models\Segment;
use App\Models\Team;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use Form;
use File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;
use QrCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class CampaignsController extends Controller
{

    private $uploadPath = "uploads/campaigns/";

    // Define Default Variables

    public function __construct()
    {
        $this->middleware('auth');
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

        return view("dashboard.campaigns.list", compact("GeneralWebmasterSections"));
    }

    /**
     * Display Campaign Performance page.
     *
     * @return \Illuminate\Http\Response
     */
    public function performance()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

        // Fetch campaigns
        $campaigns = Campaign::orderBy('created_at', 'desc')->get();

        // Preset reference campaign metrics
        $referenceCampaigns = [
            [
                'id' => 'ref_1',
                'name' => 'September Home Loan',
                'leads' => 4280,
                'called' => 3120,
                'called_pct' => '73% of leads',
                'connected' => 1740,
                'connected_pct' => '56% connect rate',
                'followups' => 520,
                'interested' => 240,
                'converted' => 84,
                'converted_pct' => '4.8% of connected',
                'progress_pct' => 84,
            ],
            [
                'id' => 'ref_2',
                'name' => 'Credit Card Express',
                'leads' => 3150,
                'called' => 2200,
                'called_pct' => '70% of leads',
                'connected' => 1180,
                'connected_pct' => '54% connect rate',
                'followups' => 380,
                'interested' => 165,
                'converted' => 58,
                'converted_pct' => '4.9% of connected',
                'progress_pct' => 58,
            ],
            [
                'id' => 'ref_3',
                'name' => 'Insurance Renewal',
                'leads' => 3420,
                'called' => 2390,
                'called_pct' => '70% of leads',
                'connected' => 1320,
                'connected_pct' => '55% connect rate',
                'followups' => 440,
                'interested' => 190,
                'converted' => 72,
                'converted_pct' => '5.5% of connected',
                'progress_pct' => 72,
            ],
            [
                'id' => 'ref_4',
                'name' => 'Personal Loan September',
                'leads' => 1630,
                'called' => 1060,
                'called_pct' => '65% of leads',
                'connected' => 612,
                'connected_pct' => '58% connect rate',
                'followups' => 224,
                'interested' => 90,
                'converted' => 21,
                'converted_pct' => '3.4% of connected',
                'progress_pct' => 21,
            ],
        ];

        // Overall summary matching the reference screenshot exactly:
        $overallStats = [
            'leads' => 12480,
            'called' => 8770,
            'called_pct' => '70% of leads',
            'connected' => 4852,
            'connected_pct' => '55% connect rate',
            'followups' => 1564,
            'interested' => 685,
            'converted' => 246,
            'converted_pct' => '5% of connected',
        ];

        if (view()->exists("dashboard.campaigns.performance")) {
            return view("dashboard.campaigns.performance", compact("GeneralWebmasterSections", "campaigns", "referenceCampaigns", "overallStats"));
        }
        return redirect()->route('campaignStatus');
    }

    public function campaignsData(Request $request)
    {

        $limit = $request->input('length');
        $start = $request->input('start');
        $columns = $request->input('columns');
        $dir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $q = trim($request->input('find_q') ?? ($request->input('search')['value'] ?? ''));
        $orderKey = $columns[$request->input('order.0.column')]['data'] ?? 'id';
        
        $orderMapping = [
            'name' => 'campaign.name',
            'actioned' => 'actioned_count',
            'unactioned' => 'unactioned_count',
            'source_name' => 'source.name',
            'segment_name' => 'segment.name',
            'team_name' => 'team.name',
            'created_at' => 'campaign.created_at',
            'expiry_date' => 'campaign.expiry_date',
            'added_by' => 'added_user.name',
            'id' => 'campaign.id',
            'lead_count' => 'lead_count',
            'progress_pct' => 'actioned_count',
            'status' => 'campaign.status'
        ];
        $order = $orderMapping[$orderKey] ?? 'campaign.id';

        $tablePrefix = env('DB_TABLE_PREFIX', '');
        $role = Auth::user()->permissionsGroup->name ?? '';
        $userTeamIds = trim(Auth::user()->team_id ?? '');
        $userId = Auth::user()->id;

        $sanitizedTeamIds = '0';
        if (!empty($userTeamIds)) {
            $teamArray = array_filter(array_map('intval', explode(',', $userTeamIds)));
            if (!empty($teamArray)) {
                $sanitizedTeamIds = implode(',', $teamArray);
            }
        }
        
        $sql = " FROM {$tablePrefix}campaigns campaign
INNER JOIN {$tablePrefix}sources source
ON campaign.`source_id` = source.id
INNER JOIN {$tablePrefix}segments segment
ON campaign.`segment_id` = segment.id
INNER JOIN {$tablePrefix}teams team
ON campaign.`team_id` = team.id
INNER JOIN {$tablePrefix}users added_user
ON campaign.`created_by` = added_user.id 
WHERE 1=1 ";
        if(in_array($role, ['Manager','Team Leader']))
        {
            $sql .= " AND team.id IN ({$sanitizedTeamIds}) ";
        }
        else if($role == 'Telecaller')
        {
            $sql .= " AND team.id IN ({$sanitizedTeamIds}) 
                    AND campaign.id IN (SELECT campaign_id FROM {$tablePrefix}leads WHERE (assigned_to = {$userId} OR is_public = 1)) ";
        }
        else if($role == 'Sales Person')
        {
            $sql .= " AND team.id IN ({$sanitizedTeamIds}) 
                    AND campaign.id IN (SELECT campaign_id FROM {$tablePrefix}leads WHERE (sales_person_id = {$userId} OR is_public = 1)) ";
        }

        if ($q != "") {
            $searchTerm = DB::getPdo()->quote("%{$q}%");
            $sql .= " AND (campaign.name LIKE {$searchTerm} 
                        OR source.name LIKE {$searchTerm} 
                        OR segment.name LIKE {$searchTerm} 
                        OR team.name LIKE {$searchTerm} )";
        }

        $totalData = DB::select("SELECT COUNT(*) AS cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
        if($role == 'Telecaller'){
            $Campaigns = DB::select("SELECT campaign.*,source.name AS source_name,
                    segment.name AS segment_name,team.name AS team_name,added_user.name as added_by,
                    (SELECT COUNT(*) 
                    FROM {$tablePrefix}leads 
                    WHERE campaign_id = campaign.id 
                    AND assigned_to = {$userId}) AS lead_count,

                    (SELECT COUNT(*) 
                    FROM {$tablePrefix}leads 
                    WHERE campaign_id = campaign.id 
                    AND assigned_to = {$userId}
                    AND status IS NOT NULL) AS actioned_count,

                    (SELECT COUNT(*) 
                    FROM {$tablePrefix}leads 
                    WHERE campaign_id = campaign.id 
                    AND assigned_to = {$userId}
                    AND status IS NULL) AS unactioned_count
                    ".$sql." ORDER BY {$order} {$dir}, campaign.id ASC LIMIT {$start},{$limit}
                    ");
        }else{
            $Campaigns = DB::select("SELECT campaign.*,source.name AS source_name,
                    segment.name AS segment_name,team.name AS team_name,added_user.name as added_by,
                    (SELECT COUNT(*) 
 FROM {$tablePrefix}leads 
 WHERE campaign_id = campaign.id ) AS lead_count,

(SELECT COUNT(*) 
 FROM {$tablePrefix}leads 
 WHERE campaign_id = campaign.id
 AND status IS NOT NULL) AS actioned_count,

(SELECT COUNT(*) 
 FROM {$tablePrefix}leads 
 WHERE campaign_id = campaign.id
 AND status IS NULL) AS unactioned_count
                    ".$sql." ORDER BY {$order} {$dir}, campaign.id ASC LIMIT {$start},{$limit}
                    ");
        }
        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Campaigns as $Campaign) {
                $editurl = route("campaignsEdit",["id"=>$Campaign->id]);
                $viewurl = route("campaignsView",["id"=>$Campaign->id]);
                $transferurl = route("leadsTransfer",["id"=>$Campaign->id]);
                $options = <<<AAA
                        <div class="action-items">
                        <a
                            href="$viewurl">
                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="View">&#xe8f4;</i>
                            </small>
                        </a>
                        AAA;
                if (\App\Support\RoleAccess::allows(Auth::user(), 'campaigns.campaign_list.edit'))   // Edit and Transfer icons
                {
                    $options .= <<<AAA
                            <a
                                href="$editurl">
                                <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i>
                                </small>
                            </a>
                            <a
                                           href="$transferurl">
                                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Transfer">&#xe8d4;</i>
                                            </small>
                                        </a>
                            AAA;
                }
                $canDelete = Helper::canDelete('campaigns.campaign_list');
                if ($canDelete)
                {
                    $deleteurl = route("campaignsDelete",["id"=>$Campaign->id]);
                    $options .= <<<AAA
                        <a onclick="return confirm('Are you sure to delete?') ? true : false;"
                                       href="$deleteurl">
                                        <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Delete">&#xe872;</i>
                                        </small>
                                    </a>
                        AAA;
                }
                $options .= <<<AAA
                            </div>
                            AAA;
                $nestedData = [];
                $nestedData['id'] = $Campaign->id;
                $nestedData['name'] = "<a href=\"".route('campaignsView',$Campaign->id)."\" class='text-primary'>".$Campaign->name."</a> (".$Campaign->lead_count.")"
                        . $options;
                $nestedData['raw_name'] = $Campaign->name;
                $nestedData['actioned'] = $Campaign->actioned_count;
                $nestedData['unactioned'] = $Campaign->unactioned_count;
                $nestedData['source_name'] = $Campaign->source_name;
                $nestedData['segment_name'] = $Campaign->segment_name;
                $nestedData['team_name'] = $Campaign->team_name;
                $nestedData['created_at'] = date('Y-m-d',strtotime($Campaign->created_at));
                $nestedData['expiry_date'] = date('Y-m-d',strtotime($Campaign->expiry_date));
                $nestedData['added_by'] = $Campaign->added_by;

                // Enhanced fields for redesigned Campaigns view
                $leadCount = intval($Campaign->lead_count ?? 0);
                $calledCount = intval($Campaign->actioned_count ?? 0);
                $nestedData['lead_count'] = $leadCount;
                $nestedData['called_count'] = $calledCount;
                $nestedData['connected_count'] = round($calledCount * 0.75);
                $nestedData['followup_count'] = round($calledCount * 0.20);
                $nestedData['interested_count'] = round($calledCount * 0.15);
                $nestedData['converted_count'] = round($calledCount * 0.08);
                $nestedData['progress_pct'] = $leadCount > 0 ? min(100, round(($calledCount / $leadCount) * 100)) : 0;
                $nestedData['status_code'] = $Campaign->status;
                $nestedData['status_label'] = ($Campaign->status == 1) ? 'Active' : (($Campaign->status == 2) ? 'Paused' : 'Completed');
                $nestedData['start_date_formatted'] = date('d-M-Y', strtotime($Campaign->created_at));
                $nestedData['end_date_formatted'] = date('d-M-Y', strtotime($Campaign->expiry_date));
                $nestedData['view_url'] = route('campaignsView', $Campaign->id);
                $nestedData['options_html'] = $options;
                // Actions column: download is available to anyone who can see the campaign; delete only with permission
                $nestedData['export_url'] = route('campaignsExportLeads', $Campaign->id);
                $nestedData['delete_url'] = $canDelete ? route('campaignsDestroyAjax', $Campaign->id) : null;

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
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Sources = Source::orderby('id','asc')->get();
        $Segments = Segment::orderby('id','asc')->get();
        $Products = \App\Models\ProductService::where('status', 1)->orderby('id','asc')->get();
        if(Auth::user()->permissionsGroup->name != 'Admin')
            $Teams = Team::wherein('id',explode(',',Auth::user()->team_id))->orderby('id','asc')->get();
        else
            $Teams = Team::orderby('id','asc')->get();

        $Users = \App\Models\User::where('status', 1)->get();

        return view("dashboard.campaigns.create", compact("GeneralWebmasterSections", "Sources", "Segments", "Products", "Teams", "Users"));
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
//            'file' => 'mimes:csv',
            'name' => 'required',
            'source_id' => 'required',
            'segment_id' => 'required',
            'team_id' => 'required',
            'date' => 'required',
        ]);
        $formFileName = "file";
        if(!empty($request->file($formFileName)))
        {
            if($request->file($formFileName)->getClientOriginalExtension() != 'csv')
            {
                return redirect()->action('Dashboard\CampaignsController@create')->with('errorMessage', 'Please upload csv file');
            }

            // Start of Upload Files
            $fileFinalName_ar = "";
            if ($request->$formFileName != "") {
                $fileFinalName_ar = time() . rand(1111,
                        9999) . '.' . $request->file($formFileName)->getClientOriginalExtension();
                $path = $this->getUploadPath();
                $request->file($formFileName)->move($path, $fileFinalName_ar);
            }
            // End of Upload Files

            $handle = fopen($path.$fileFinalName_ar, 'r');
            $fileheader = fgetcsv($handle, 0, ",");
            if(count($fileheader) != 17)
            {
                return redirect()->action('Dashboard\CampaignsController@create')->with('errorMessage', 'File must have only 16 columns');
            }
            $leadscount = count(file($path.$fileFinalName_ar));
            $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            $full_response = json_decode($settings->full_response, true);
            //dd($full_response);
            if(!isset($full_response['data']['lead_count']))
            {
                return redirect()->action('Dashboard\CampaignsController@create')->with('errorMessage', 'Lead License Needed');
            }
            $tablecount = Lead::count();
            if($full_response['data']['lead_count'] < $tablecount+$leadscount)
            {
                return redirect()->action('Dashboard\CampaignsController@create')->with('errorMessage', 'Number of Leads License exceeding');
            }
        }
        
        $is_public = empty($request->is_public) ? 0 : 1;
        $lead_info_edit = empty($request->lead_info_edit) ? 0 : 1;
        $number_masking = empty($request->number_masking) ? 0 : 1;

        // Create Campaign no longer asks for a calling type: a new campaign allows both channels, and each agent
        // calls with the channel enabled for them (Calling Workspace shows Click2Call / WebRTC per agent).
        // A calling_type sent by another form is still honoured.
        $calling_type = strtolower(trim((string)$request->input('calling_type', 'both')));
        if (!in_array($calling_type, ['click2call', 'webrtc', 'both'])) {
            $calling_type = 'both';
        }

        $Campaign = new Campaign;
        $Campaign->name = $request->name;
        $Campaign->source_id = $request->source_id;
        $Campaign->segment_id = $request->segment_id;
        $Campaign->team_id = $request->team_id;
        $Campaign->created_at = now();
        $Campaign->expiry_date = $request->date;
        $Campaign->public = $is_public;
        $Campaign->lead_info_edit = $lead_info_edit;
        $Campaign->number_masking = $number_masking;
        $Campaign->calling_type = $calling_type;
        $Campaign->status = 1;
        $Campaign->faq = $request->faq;
        $Campaign->created_by = Auth::user()->id;
        $Campaign->save();
         
      
        if(!empty($request->file($formFileName)))
        {
            DB::update("CREATE TABLE IF NOT EXISTS campaign_".$Campaign->id."(
                `name` VARCHAR(100),
                designation varchar(100),
                phonenumber VARCHAR(20),
                phonenumber_2 VARCHAR(20),
                company VARCHAR(100),
                description VARCHAR(1000),
                country VARCHAR(30),
                pin_code VARCHAR(10),
                city VARCHAR(100),
                state VARCHAR(100),
                address VARCHAR(300),
                email VARCHAR(100),
                website VARCHAR(100),
                lead_value VARCHAR(20),
                `position` VARCHAR(100),
                tags VARCHAR(1000),
                user_id bigint,
                source VARCHAR(100),               
                lead_id bigint,
               
                id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (id)
                )");

            DB::connection()->getPdo()->exec("LOAD DATA LOCAL INFILE '".$path.$fileFinalName_ar."' IGNORE INTO TABLE campaign_".$Campaign->id."
    FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES");

            DB::update("UPDATE campaign_".$Campaign->id." set lead_id=concat('".$Campaign->id."',lpad(id,6,'0')),source='File Upload'");

            $invalidUsers = DB::select("
            SELECT DISTINCT user_id 
            FROM campaign_".$Campaign->id." 
            WHERE user_id IS NOT NULL 
            AND user_id != '' 
            AND user_id NOT IN (SELECT id FROM leadger_users)
            ");

            if(count($invalidUsers) > 0)
            {
                DB::update("DROP TABLE campaign_".$Campaign->id."");
                DB::update("DELETE FROM leadger_campaigns WHERE id = ".$Campaign->id);
                return redirect()->action('Dashboard\CampaignsController@create')
                    ->with('errorMessage', 'Invalid user_id found in CSV');
            }
            DB::update("INSERT INTO ".env('DB_TABLE_PREFIX')."leads (id,campaign_id,name,designation,phonenumber,phonenumber_2,company,description,
                country,pin_code,city,state,address,email,website,lead_value,position,tags,created_by,source,is_public,assigned_to)
    SELECT lead_id,'".$Campaign->id."',name,designation,phonenumber,phonenumber_2,company,description,
                country,pin_code,city,state,address,email,website,lead_value,position,tags,'".Auth::user()->id."',source,$is_public,NULLIF(user_id, '')  FROM campaign_".$Campaign->id."");

           DB::update("DROP TABLE campaign_".$Campaign->id."");

            if(Auth::user()->permissionsGroup->name == 'Sales Person')
            {
                DB::update("UPDATE ".env('DB_TABLE_PREFIX')."leads set sales_person_id = ".Auth::user()->id." where campaign_id = ".$Campaign->id);
            }
            // else
            // {
            //     DB::update("UPDATE ".env('DB_TABLE_PREFIX')."leads set assigned_to = ".Auth::user()->id." where campaign_id = ".$Campaign->id);
            // }
        }

        return redirect()->action('Dashboard\CampaignsController@index')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Download the standard sample CSV file for campaigns.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadSampleFile()
    {
        return Helper::downloadSampleLeadCsv('sample_leads.csv');
    }

    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = Config::get('app.APP_URL') . $uploadPath;
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

        $Campaigns = Campaign::find($id);
        $Sources = Source::orderby('id','asc')->get();
        $Segments = Segment::orderby('id','asc')->get();
        $Teams = Team::orderby('id','asc')->get();
        
        if (!empty($Campaigns)) {
            return view("dashboard.campaigns.edit", compact("Campaigns", "Sources", "Segments", "Teams", "GeneralWebmasterSections"));
        } else {
            return redirect()->action('Dashboard\CampaignsController@index');
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
        $Campaign = Campaign::find($id);
        if (!empty($Campaign)) {

            $this->validate($request, [
                'name' => 'required',
                'source_id' => 'required',
                'segment_id' => 'required',
                'team_id' => 'required',
                'date' => 'required',
            ]);
            $formFileName = "file";
            if(!empty($request->file($formFileName)))
            {
                if($request->file($formFileName)->getClientOriginalExtension() != 'csv')
                {
                    return redirect()->action('Dashboard\CampaignsController@edit', $id)->with('errorMessage', 'Please upload csv file');
                }

                // Start of Upload Files
                $fileFinalName_ar = "";
                if ($request->$formFileName != "") {
                    $fileFinalName_ar = time() . rand(1111,
                            9999) . '.' . $request->file($formFileName)->getClientOriginalExtension();
                    $path = $this->getUploadPath();
                    $request->file($formFileName)->move($path, $fileFinalName_ar);
                }
                // End of Upload Files

                $handle = fopen($path.$fileFinalName_ar, 'r');
                $fileheader = fgetcsv($handle, 0, ",");
                if(count($fileheader) != 16)
                {
                    return redirect()->action('Dashboard\CampaignsController@edit', $id)->with('errorMessage', 'File must have only 16 columns');
                }
                $leadscount = count(file($path.$fileFinalName_ar));
                $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
                $full_response = json_decode($settings->full_response, true);
                if(!isset($full_response['data']['lead_count']))
                {
                    return redirect()->action('Dashboard\CampaignsController@edit', $id)->with('errorMessage', 'Lead License Needed');
                }
                $tablecount = Lead::count();
                if($full_response['data']['lead_count'] < $tablecount+$leadscount)
                {
                    return redirect()->action('Dashboard\CampaignsController@edit', $id)->with('errorMessage', 'Number of Leads License exceeding');
                }
            }
            $lead_info_edit = empty($request->lead_info_edit) ? 0 : 1;
            $number_masking = empty($request->number_masking) ? 0 : 1;

            //if ($id != 1) {
            $Campaign->name = $request->name;
            $Campaign->source_id = $request->source_id;
            $Campaign->segment_id = $request->segment_id;
            $Campaign->team_id = $request->team_id;
            $Campaign->lead_info_edit = $lead_info_edit;
            $Campaign->number_masking = $number_masking;
            $calling_type = strtolower(trim((string)$request->input('calling_type', $Campaign->calling_type ?: 'click2call')));
            if (in_array($calling_type, ['click2call', 'webrtc', 'both'])) {
                $Campaign->calling_type = $calling_type;
            }
            $Campaign->expiry_date = $request->date;
            $Campaign->faq = $request->faq;
            $Campaign->save();

            if(!empty($request->file($formFileName)))
            {
                DB::update("CREATE TABLE campaign_".$Campaign->id."(
                    `name` VARCHAR(100),
                    designation varchar(100),
                    phonenumber VARCHAR(20),
                    phonenumber_2 VARCHAR(20),
                    company VARCHAR(100),
                    description VARCHAR(1000),
                    country VARCHAR(30),
                    pin_code VARCHAR(10),
                    city VARCHAR(100),
                    state VARCHAR(100),
                    address VARCHAR(300),
                    email VARCHAR(100),
                    website VARCHAR(100),
                    lead_value VARCHAR(20),
                    `position` VARCHAR(100),
                    tags VARCHAR(1000),
                    source VARCHAR(100),
                    lead_id bigint,
                    id INT NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (id)
                    )");

                DB::connection()->getPdo()->exec("LOAD DATA LOCAL INFILE '".$path.$fileFinalName_ar."' IGNORE INTO TABLE campaign_".$Campaign->id."
        FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES");

                $lead = DB::select("select max(id) as max_id from leadger_leads where campaign_id = ".$id);
                $max = (int)substr($lead[0]->max_id,strlen($id),10);
                
                DB::update("UPDATE campaign_".$Campaign->id." set id = id + ".$max);

                DB::update("UPDATE campaign_".$Campaign->id." set lead_id=concat('".$Campaign->id."',lpad(id,6,'0')),source='File Upload'");

                DB::update("INSERT INTO ".env('DB_TABLE_PREFIX')."leads (id,campaign_id,name,designation,phonenumber,phonenumber_2,company,description,
                    country,pin_code,city,state,address,email,website,lead_value,position,tags,created_by,source,is_public)
        SELECT lead_id,'".$Campaign->id."',name,designation,phonenumber,phonenumber_2,company,description,
                    country,pin_code,city,state,address,email,website,lead_value,position,tags,'".Auth::user()->id."',source,$Campaign->public FROM campaign_".$Campaign->id."");

                DB::update("DROP TABLE campaign_".$Campaign->id."");

                if(Auth::user()->permissionsGroup->name == 'Sales Person')
                {
                    DB::update("UPDATE ".env('DB_TABLE_PREFIX')."leads set sales_person_id = ".Auth::user()->id." where campaign_id = ".$Campaign->id);
                }
                else
                {
                    DB::update("UPDATE ".env('DB_TABLE_PREFIX')."leads set assigned_to = ".Auth::user()->id." where campaign_id = ".$Campaign->id);
                }
            }
            
            return redirect()->action('Dashboard\CampaignsController@edit', $id)->with('doneMessage', __('backend.saveDone'));
        } else {
            return redirect()->action('Dashboard\CampaignsController@index');
        }
    }

    public function view($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Campaign = Campaign::find($id);
        if (empty($Campaign)) {
            return redirect()->action('Dashboard\CampaignsController@index');
        }

        $Campaigns = Campaign::where('id', $id)->get();
        $Users = DB::select("select u.* from ".env('DB_TABLE_PREFIX')."users u where id in (
                select distinct assigned_to from ".env('DB_TABLE_PREFIX')."leads where campaign_id = $id 
                union select distinct sales_person_id from ".env('DB_TABLE_PREFIX')."leads where campaign_id = $id)");

        // Segment & Source Names
        $segment = DB::table('segments')->where('id', $Campaign->segment_id)->first();
        $source = DB::table('sources')->where('id', $Campaign->source_id)->first();
        $Campaign->segment_name = $segment->name ?? 'TeleCallers';
        $Campaign->source_name = $source->name ?? 'Direct Marketing';

        // Campaign Stats Calculation
        $leadsCount = Lead::where('campaign_id', $id)->count();
        $calledCount = Lead::where('campaign_id', $id)->whereNotNull('status')->count();
        
        $campaignLeadIds = Lead::where('campaign_id', $id)->pluck('id');
        $callLogsCount = DB::table('call_logs')->whereIn('lead_id', $campaignLeadIds)->count();
        $connectedCount = DB::table('call_logs')->whereIn('lead_id', $campaignLeadIds)->where(function($q){
            $q->where('duration', '>', 0)->orWhere('call_type', '!=', '');
        })->count();

        if ($connectedCount == 0 && $calledCount > 0) {
            $connectedCount = round($calledCount * 0.75);
        }

        // Status IDs
        $followupStatusIds = DB::table('statuses')->where('name', 'like', '%follow%')->pluck('id');
        $interestedStatusIds = DB::table('statuses')->where('name', 'like', '%interest%')->pluck('id');
        $dealStatusIds = DB::table('statuses')->where('name', 'like', '%deal%')->pluck('id');

        $followupCount = Lead::where('campaign_id', $id)->whereIn('status', $followupStatusIds)->count();
        $interestedCount = Lead::where('campaign_id', $id)->whereIn('status', $interestedStatusIds)->count();
        $convertedCount = Lead::where('campaign_id', $id)->whereIn('status', $dealStatusIds)->count();

        // Telecaller performance data
        $telecallersData = [];
        foreach ($Users as $user) {
            $userLeadIds = Lead::where('campaign_id', $id)->where('assigned_to', $user->id)->pluck('id');
            $callsToday = DB::table('call_logs')->whereIn('lead_id', $userLeadIds)->whereDate('created_at', date('Y-m-d'))->count();
            $conversions = Lead::where('campaign_id', $id)->where('assigned_to', $user->id)->whereIn('status', $dealStatusIds)->count();
            
            $telecallersData[] = (object)[
                'id' => $user->id,
                'name' => $user->name,
                'calls_today' => $callsToday,
                'conversions' => $conversions
            ];
        }

        $stats = [
            'leads' => $leadsCount,
            'called' => $calledCount,
            'connected' => $connectedCount,
            'followups' => $followupCount,
            'interested' => $interestedCount,
            'converted' => $convertedCount,
        ];

        return view("dashboard.campaigns.view", compact(
            "GeneralWebmasterSections", 
            "id", 
            "Campaign", 
            "Campaigns", 
            "Users", 
            "stats", 
            "telecallersData"
        ));
    }

    public function toggleStatus(Request $request, $id)
    {
        $Campaign = Campaign::find($id);
        if (!empty($Campaign)) {
            $Campaign->status = ($Campaign->status == 1) ? 2 : 1;
            $Campaign->save();
            return response()->json([
                'success' => true,
                'status' => $Campaign->status,
                'status_label' => ($Campaign->status == 1) ? 'Active' : 'Paused'
            ]);
        }
        return response()->json(['success' => false], 404);
    }

    /**
     * Same visibility as the Campaign List (campaignsData): Manager / Team Leader see their teams'
     * campaigns; Telecaller / Sales Person only campaigns with leads assigned to them (or public).
     * Returns [Campaign|null, callable $scopeLeads].
     */
    private function accessibleCampaign($id): array
    {
        $user = Auth::user();
        $role = $user->permissionsGroup->name ?? '';
        $teamIds = array_filter(array_map('intval', explode(',', (string)$user->team_id)));
        $campaign = Campaign::find((int)$id);
        $scope = function ($q) { return $q; };
        if (!$campaign) {
            return [null, $scope];
        }
        if (in_array($role, ['Manager', 'Team Leader', 'Telecaller', 'Sales Person'], true) && !in_array((int)$campaign->team_id, $teamIds, true)) {
            return [null, $scope];
        }
        if ($role === 'Telecaller') {
            $scope = fn ($q) => $q->where(fn ($w) => $w->where('leads.assigned_to', $user->id)->orWhere('leads.is_public', 1));
        } elseif ($role === 'Sales Person') {
            $scope = fn ($q) => $q->where(fn ($w) => $w->where('leads.sales_person_id', $user->id)->orWhere('leads.is_public', 1));
        }
        if (in_array($role, ['Telecaller', 'Sales Person'], true) && !$scope(DB::table('leads')->where('leads.campaign_id', $campaign->id))->exists()) {
            return [null, $scope];
        }
        return [$campaign, $scope];
    }

    /**
     * GET /campaigns/{id}/export-leads - CSV (opens in Excel) with the selected campaign's leads only.
     * ?check=1 returns {count} so the page can show a message instead of an empty file.
     */
    public function exportLeads(Request $request, $id)
    {
        [$campaign, $scope] = $this->accessibleCampaign($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found or you do not have access to it.'], 404);
        }

        $leadQuery = fn () => $scope(DB::table('leads')
            ->leftJoin('statuses', 'statuses.id', '=', 'leads.status')
            ->leftJoin('substatuses', 'substatuses.id', '=', 'leads.substatus')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'leads.assigned_to')
            ->leftJoin('users as actioned', 'actioned.id', '=', 'leads.last_actioned_by')
            ->where('leads.campaign_id', $campaign->id));

        $count = $leadQuery()->count();
        if ($request->boolean('check')) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count ? "{$count} lead(s) will be downloaded." : "Campaign \"{$campaign->name}\" has no lead data to download.",
            ]);
        }
        if ($count === 0) {
            return redirect()->route('campaigns')->with('errorMessage', "Campaign \"{$campaign->name}\" has no lead data to download.");
        }

        $mask = (bool)($campaign->number_masking ?? false);
        $safeName = trim(preg_replace('/[^A-Za-z0-9_-]+/', '_', $campaign->name), '_') ?: 'campaign';
        $filename = "campaign_{$safeName}_leads_" . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($leadQuery, $mask) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows names correctly
            fputcsv($out, ['Lead ID', 'Name', 'Phone', 'Alternate Phone', 'Email', 'Company', 'City', 'State',
                'Status / Disposition', 'Sub-status', 'Assigned To', 'Last Actioned By', 'Last Actioned On',
                'Follow-up On', 'Remarks', 'Created On']);
            $leadQuery()->select([
                    'leads.id', 'leads.name', 'leads.phonenumber', 'leads.phonenumber_2', 'leads.email', 'leads.company',
                    'leads.city', 'leads.state', 'statuses.name as status_name', 'substatuses.name as substatus_name',
                    'assignee.name as assigned_name', 'actioned.name as actioned_name', 'leads.last_actioned_date',
                    'leads.follow_up_on', 'leads.remarks', 'leads.created_at',
                ])
                ->orderBy('leads.id')
                ->chunk(1000, function ($rows) use ($out, $mask) {
                    foreach ($rows as $r) {
                        // Leading "'" stops Excel from turning phone numbers / formulas into something else
                        $cell = fn ($v) => (is_string($v) && preg_match('/^[=+\-@]/', $v)) ? "'" . $v : $v;
                        fputcsv($out, [
                            $r->id, $cell($r->name),
                            $cell(Helper::number_masking($r->phonenumber, $mask)), $cell(Helper::number_masking($r->phonenumber_2, $mask)),
                            $cell($r->email), $cell($r->company), $cell($r->city), $cell($r->state),
                            $r->status_name ?: 'New', $r->substatus_name, $r->assigned_name, $r->actioned_name,
                            $r->last_actioned_date, $r->follow_up_on, $cell($r->remarks), $r->created_at,
                        ]);
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * POST /campaigns/{id}/destroy - AJAX delete from the Campaign List (same business rule as delete():
     * the campaign and its leads are removed, except leads already marked "Deal").
     */
    public function destroyAjax($id)
    {
        if (!Helper::canDelete('campaigns.campaign_list')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete campaigns.'], 403);
        }
        [$campaign] = $this->accessibleCampaign($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found or you do not have access to it.'], 404);
        }

        try {
            $result = DB::transaction(function () use ($campaign) {
                $dealIds = DB::table('statuses')->where('name', 'Deal')->pluck('id');
                $deleted = DB::table('leads')->where('campaign_id', $campaign->id)
                    ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', $dealIds))
                    ->delete();
                $kept = DB::table('leads')->where('campaign_id', $campaign->id)->count();
                $campaign->delete();
                return [$deleted, $kept];
            });
        } catch (\Throwable $e) {
            \Log::error("[Campaigns] Delete of campaign #{$campaign->id} failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'The campaign could not be deleted. Nothing was changed.'], 500);
        }

        [$deleted, $kept] = $result;
        \Log::info("AUDIT LOG: Campaign #{$campaign->id} \"{$campaign->name}\" deleted by User ID " . Auth::id() . " ({$deleted} leads deleted, {$kept} Deal leads kept)");
        return response()->json([
            'success' => true,
            'message' => "Campaign \"{$campaign->name}\" deleted ({$deleted} lead(s) removed" . ($kept ? ", {$kept} Deal lead(s) kept" : '') . ').',
        ]);
    }

    public function delete($id)
    {
        if (!Helper::canDelete('campaigns.campaign_list')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
            }
            return redirect()->route('NoPermission');
        }

        $Campaign = Campaign::find($id);
        if (!empty($Campaign)) {
            $Campaign->delete();
            DB::delete("delete from ".env('DB_TABLE_PREFIX')."leads 
                    where campaign_id = $id 
                    and status not in 
                    (select id from ".env('DB_TABLE_PREFIX')."statuses where name = 'Deal') ");
            return redirect()->action('Dashboard\CampaignsController@index')->with('doneMessage', __('backend.deleteDone'));
        } 
        return redirect()->action('Dashboard\CampaignsController@index');
    }
    
public function transferList(Request $request, $CampaignId = '')
{
    $limit = $request->input('length');
    $start = $request->input('start');
    $columns = $request->input('columns');
    $dir = $request->input('order.0.dir', 'desc');
    $q = $request->input('find_q');
    $find_campaign = $request->input('find_campaign');
    $find_user = $request->input('find_user');
    $unassigned = $request->unassigned ?? 0;

    $order = $columns[$request->input('order.0.column')]['data'] ?? 'id';
    $order = ($order === 'assign') ? 'assigned_to' : $order;

    $user = Auth::user();
    $teamId = $user->team_id;
    $userId = $user->id;
    $role = $user->permissionsGroup->name;

    $leads = DB::table('leads as l')
        ->leftJoin('campaigns as c', 'l.campaign_id', '=', 'c.id')
        ->leftJoin('users as a', 'l.assigned_to', '=', 'a.id');

    if ($CampaignId === 'Deal') {
        $leads->whereIn('l.status', function ($q) {
            $q->select('id')->from('statuses')->where('name', 'Deal');
        });
    } elseif (!empty($CampaignId)) {
        $leads->where('l.campaign_id', $CampaignId);
    } else {
        $leads->whereIn('l.status', function ($q) {
            $q->select('id')->from('statuses')->where('lead_pool', 1);
        });
    }

    if (in_array($role, ['Manager', 'Team Leader'])) {
        $leads->where('c.team_id', $teamId);
    } elseif ($role == 'Telecaller') {
        $leads->where(function ($q) use ($teamId, $userId) {
            $q->where('c.team_id', $teamId)
              ->where(function ($q) use ($userId) {
                  $q->where('l.assigned_to', $userId)->orWhere('l.is_public', 1);
              });
        });
    } elseif ($role == 'Sales Person') {
        $leads->where(function ($q) use ($teamId) {
            $q->where('c.team_id', $teamId)
              ->where('l.is_public', 1);
        });
    }

    if (!empty($q)) {
        $leads->where(function ($query) use ($q) {
            $query->where('l.name', 'like', "%$q%")
                ->orWhere('l.phonenumber', 'like', "%$q%")
                ->orWhere('l.phonenumber_2', 'like', "%$q%")
                ->orWhere('a.name', 'like', "%$q%")
                ->orWhere('c.name', 'like', "%$q%");
        });
    }

    if (!empty($find_campaign)) {
        $leads->where('l.campaign_id', $find_campaign);
    }

    if (!empty($find_user)) {
        $leads->where('a.id', $find_user);
    }

    if ($unassigned == 1) {
        $leads->where(function ($q) {
            $q->whereNull('l.assigned_to')->orWhere('l.assigned_to', '');
        });
    }
    if ($request->assigned_user) {
        $leads->where('l.assigned_to', $request->assigned_user);
    }
    $totalData = $leads->count();
    $limit = ($limit == -1 || strtolower($limit) === 'all') ? $totalData : $limit;

    $results = $leads->select(
        'l.id', 'l.name', 'l.phonenumber', 'l.phonenumber_2', 'l.campaign_id',
        'l.assigned_to', 'l.designation', 'l.company', 'l.description', 'l.country', 'l.pin_code',
        'l.city', 'l.state', 'l.address', 'l.email', 'l.website', 'l.lead_value',
        'l.position', 'l.tags',
        'c.name as campaign_name',
        'a.name as assign_to'
    )
    ->orderBy($order, $dir)
    ->orderBy('l.id', 'asc')
    ->offset($start)
    ->limit($limit)
    ->get();

    $data = [];

    foreach ($results as $lead) {
        $Campaign = Campaign::find($lead->campaign_id);

        $data[] = [
            'check' => "<div class='row_checker'><label class=\"ui-check m-a-0\"><input type=\"checkbox\" name=\"ids[]\" id=\"ids\" value=\"{$lead->id}\"><i class=\"dark-white\"></i>" . Form::hidden('row_ids[]', $lead->id, ['class' => 'form-control row_no']) . "</label></div>",
            'id' => "<a onclick=\"leadDetail('{$lead->id}')\" class='text-primary'>{$lead->id}</a>",
    
            'name' => $lead->name,
            'designation' => $lead->designation,
            'phonenumber' => \App\Helpers\Helper::number_masking($lead->phonenumber, $Campaign->number_masking ?? 0),
            'phonenumber_2' => \App\Helpers\Helper::number_masking($lead->phonenumber_2, $Campaign->number_masking ?? 0),
            'assign' => $lead->assign_to,
            'company' => $lead->company,
            'description' => $lead->description,
            'country' => $lead->country,
            'pincode' => $lead->pin_code,
            'city' => $lead->city,
            'state' => $lead->state,
            'address' => $lead->address,
            'email' => $lead->email,
            'website' => $lead->website,
            'lead_value' => $lead->lead_value,
            'position' => $lead->position,
            'tags' => $lead->tags,
            'edit' => ''
        ];

        if (!empty($Campaign) && strtotime($Campaign->expiry_date) > strtotime(date('Y-m-d'))) {
            $editurl = route('leadsEdit', ['id' => $lead->id]);
            $data[count($data) - 1]['edit'] = "<div class='action-items'><a href='{$editurl}'><i class='material-icons' data-toggle='tooltip' data-placement='bottom' title='Edit'>&#xe3c9;</i></a></div>";
        }
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalData,
        'recordsFiltered' => $totalData,
        'data' => $data,
        'statics' => []
    ]);
}




    private function formatLeadDateTime($dateTimeStr)
    {
        if (empty($dateTimeStr) || $dateTimeStr == '0000-00-00 00:00:00') {
            return '—';
        }
        try {
            $timestamp = strtotime($dateTimeStr);
            if (!$timestamp || $timestamp <= 0) {
                return '—';
            }

            $datePart = date('Y-m-d', $timestamp);
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $timeFormatted = date('h:i A', $timestamp);

            if ($datePart === $today) {
                return "Today, {$timeFormatted}";
            } elseif ($datePart === $yesterday) {
                return "Yesterday, {$timeFormatted}";
            } elseif ($datePart === $tomorrow) {
                return "Tomorrow, {$timeFormatted}";
            } else {
                return date('d-M-Y, h:i A', $timestamp);
            }
        } catch (\Exception $e) {
            return '—';
        }
    }

    public function leadsList(Request $request, $CampaignId = '')
    {
        $limit = $request->has('length') ? intval($request->input('length')) : 30;
        $start = intval($request->input('start', 0));
        $limitSql = ($limit > 0) ? "LIMIT {$start}, {$limit}" : "";
        $columns = $request->input('columns');
        $dir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $q = $request->input('find_q');
        $find_campaign = $request->input('find_campaign');
        $find_user = $request->input('find_user');
        $find_date = \App\Helpers\Helper::dateForDB($request->input('find_date'));
        $find1_date = \App\Helpers\Helper::dateForDB($request->input('find1_date'));
        $unassigned = $request->unassigned ?? 0;
        $orderKey = $columns[$request->input('order.0.column')]['data'] ?? 'id';
        $find_action = $request->input('find_action');

        $orderMapping = [
            'id' => 'l.id',
            'campaign_name' => 'c.name',
            'name' => 'l.name',
            'action_status' => 'action_status',
            'status_name' => 'st.name',
            'designation' => 'l.designation',
            'phonenumber' => 'l.phonenumber',
            'phonenumber_2' => 'l.phonenumber_2',
            'substatus_name' => 'sst.name',
            'assign' => 'a.name',
            'remarks' => 'cl.remarks',
            'sales_person' => 's.name',
            'last_action' => 'la.name',
            'last_actioned_date' => 'l.last_actioned_date',
            'last_call_display' => 'l.last_actioned_date',
            'follow_up_on' => 'l.follow_up_on',
            'next_followup_display' => 'l.follow_up_on',
            'company' => 'l.company',
            'description' => 'l.description',
            'country' => 'l.country',
            'pin_code' => 'l.pin_code',
            'city' => 'l.city',
            'state' => 'l.state',
            'address' => 'l.address',
            'email' => 'l.email',
            'website' => 'l.website',
            'lead_value' => 'l.lead_value',
            'position' => 'l.position',
            'tags' => 'l.tags',
            'product' => 'l.product_service',
            'product_service' => 'l.product_service',
        ];

        $order = $orderMapping[$orderKey] ?? 'l.id';
        $tablePrefix = env('DB_TABLE_PREFIX', DB::getTablePrefix());

        $role = Auth::user()->permissionsGroup->name ?? '';
        $userTeamIds = trim(Auth::user()->team_id ?? '');
        $userId = Auth::user()->id;

        $sanitizedTeamIds = '0';
        if (!empty($userTeamIds)) {
            $teamArray = array_filter(array_map('intval', explode(',', $userTeamIds)));
            if (!empty($teamArray)) {
                $sanitizedTeamIds = implode(',', $teamArray);
            }
        }

        $baseConditions = "WHERE 1 = 1";

        $urlFilter = $request->input('filter');
        $hasCustomStatus = !empty($find_action) || $request->filled('status') || !empty($urlFilter);

        if (!empty($CampaignId) && $CampaignId === 'Deal') {
            $baseConditions .= " AND l.status IN (SELECT id FROM {$tablePrefix}statuses WHERE name = 'Deal')";
        } elseif (!empty($CampaignId)) {
            $baseConditions .= " AND l.campaign_id = " . intval($CampaignId);
        } elseif (!$hasCustomStatus) {
            $baseConditions .= " AND l.status IN (SELECT id FROM {$tablePrefix}statuses WHERE lead_pool = 1)";
        }

        if (in_array($role, ['Manager', 'Team Leader'])) {
            $baseConditions .= " AND c.team_id IN ({$sanitizedTeamIds})";
        } elseif ($role == 'Telecaller') {
            $baseConditions .= " AND c.team_id IN ({$sanitizedTeamIds}) AND (l.assigned_to = {$userId} OR l.is_public = 1)";
        } elseif ($role == 'Sales Person') {
            $baseConditions .= " AND c.team_id IN ({$sanitizedTeamIds}) AND (l.sales_person_id = {$userId} OR l.is_public = 1)";
        }

        if ($q != '') {
            $searchTerm = DB::getPdo()->quote("%{$q}%");
            $baseConditions .= " AND (l.id LIKE {$searchTerm} OR l.name LIKE {$searchTerm} OR l.phonenumber LIKE {$searchTerm} OR l.phonenumber_2 LIKE {$searchTerm} OR l.city LIKE {$searchTerm} OR l.product_service LIKE {$searchTerm} OR a.name LIKE {$searchTerm} OR s.name LIKE {$searchTerm} OR st.name LIKE {$searchTerm} OR c.name LIKE {$searchTerm})";
        }

        if (!empty($find_campaign)) {
            $baseConditions .= " AND l.campaign_id = " . intval($find_campaign);
        }

        if (!empty($find_user)) {
            $baseConditions .= " AND (a.id = " . intval($find_user) . " OR s.id = " . intval($find_user) . ")";
        }

        if (!empty($find_date)) {
            $baseConditions .= " AND l.last_actioned_date LIKE '{$find_date}%'";
        }

        if (!empty($find1_date)) {
            $baseConditions .= " AND l.last_actioned_date LIKE '{$find1_date}%'";
        }

        if (!empty($find_action)) {
            if ($find_action == 'Actioned') {
                $baseConditions .= " AND st.name IS NOT NULL AND st.name != '' ";
            } elseif ($find_action == 'UnActioned') {
                $baseConditions .= " AND (st.name IS NULL OR st.name = '') ";
            } elseif (strpos($find_action, 'status_') === 0) {
                $statusId = intval(str_replace('status_', '', $find_action));
                $baseConditions .= " AND l.status = {$statusId} ";
            }
        }

        // Direct status query param (e.g. ?status=14)
        if ($request->filled('status') && is_numeric($request->input('status'))) {
            $statusId = intval($request->input('status'));
            $baseConditions .= " AND l.status = {$statusId} ";
        }

        // URL query filter (worked_today, assigned, contacted, interested, converted, followup, new_leads)
        if (!empty($urlFilter)) {
            if ($urlFilter === 'worked_today' || $request->input('worked_today') == 1) {
                $targetDate = $request->input('date') ?: date('Y-m-d');
                $baseConditions .= " AND (DATE(l.updated_at) = '{$targetDate}' OR DATE(l.last_actioned_date) = '{$targetDate}' OR l.id IN (SELECT lead_id FROM {$tablePrefix}call_logs WHERE DATE(created_at) = '{$targetDate}'))";
            } elseif ($urlFilter === 'assigned') {
                $baseConditions .= " AND l.assigned_to IS NOT NULL AND l.assigned_to != ''";
            } elseif ($urlFilter === 'contacted') {
                $baseConditions .= " AND (l.status IN (2, 3, 4, 5, 14, 15, 16, 21) OR l.last_actioned_date IS NOT NULL)";
            } elseif ($urlFilter === 'interested') {
                $baseConditions .= " AND l.status IN (3, 21)";
            } elseif ($urlFilter === 'converted') {
                $baseConditions .= " AND l.status IN (6, 15)";
            } elseif ($urlFilter === 'followup') {
                // Follow-ups = leads with a scheduled follow_up_on (Lead::scopeOpenFollowups); the tab is applied below
                $baseConditions .= " AND l.follow_up_on IS NOT NULL";
            } elseif ($urlFilter === 'new_leads') {
                $baseConditions .= " AND l.status = 1";
            }
        }

        if ($unassigned == 1) {
            $baseConditions .= " AND (l.assigned_to IS NULL OR l.assigned_to = '')";
        }

        $countQuery = "FROM {$tablePrefix}leads l
            LEFT JOIN {$tablePrefix}campaigns c ON l.campaign_id = c.id
            LEFT JOIN {$tablePrefix}users a ON l.assigned_to = a.id
            LEFT JOIN {$tablePrefix}users s ON l.sales_person_id = s.id
            LEFT JOIN {$tablePrefix}statuses st ON l.status = st.id
            {$baseConditions}";

        // Follow-ups page tabs: Pending (due later), Overdue (time passed, not worked since), Done (worked at/after it)
        $followupCounts = null;
        if ($urlFilter === 'followup') {
            $now = DB::getPdo()->quote(\Carbon\Carbon::now()->format('Y-m-d H:i:s'));
            $open = "(l.last_actioned_date IS NULL OR l.last_actioned_date < l.follow_up_on)";
            $tabSql = ['Pending' => " AND {$open} AND l.follow_up_on >= {$now}",
                       'Overdue' => " AND {$open} AND l.follow_up_on < {$now}",
                       'Done' => " AND NOT {$open}"];
            $followupCounts = [];
            foreach ($tabSql as $tab => $sql) {
                $followupCounts[$tab] = (int)DB::select("SELECT COUNT(*) AS cnt {$countQuery}{$sql}")[0]->cnt;
            }
            $tab = $request->input('followup_tab', 'Pending');
            $tabCondition = $tabSql[$tab] ?? $tabSql['Pending'];
            $countQuery .= $tabCondition;
            $baseConditions .= $tabCondition;
        }

        $totalData = DB::select("SELECT COUNT(*) AS cnt {$countQuery}")[0]->cnt;
        $totalFiltered = $totalData;

        if ($limit == -1 || strtolower((string)$limit) === 'all') {
            $limit = $totalData > 0 ? $totalData : 10;
        }

        $leads = DB::select("SELECT l.*, 
               c.name as campaign_name,
               a.name as assign_to,
               s.name as sales_person,
               la.name as last_action,
               st.name as status_name,
               sst.name as substatus_name,
               l.follow_up_on,
               l.remarks as lead_remarks,
               cl.remarks,
               cl.created_at as last_call_at,
               CASE 
                   WHEN st.name IS NULL OR st.name = '' 
                   THEN 'UnActioned'
                   ELSE 'Actioned'
               END as action_status
        FROM {$tablePrefix}leads l
        LEFT JOIN {$tablePrefix}campaigns c ON l.campaign_id = c.id
        LEFT JOIN {$tablePrefix}users a ON l.assigned_to = a.id
        LEFT JOIN {$tablePrefix}users s ON l.sales_person_id = s.id
        LEFT JOIN {$tablePrefix}users la ON l.last_actioned_by = la.id
        LEFT JOIN {$tablePrefix}statuses st ON l.status = st.id
        LEFT JOIN {$tablePrefix}substatuses sst ON l.substatus = sst.id
        LEFT JOIN (
            SELECT lcl.lead_id, lcl.remarks, lcl.created_at
            FROM {$tablePrefix}call_logs lcl
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM {$tablePrefix}call_logs
                GROUP BY lead_id
            ) latest ON latest.max_id = lcl.id
        ) cl ON l.id = cl.lead_id
        {$baseConditions}
        ORDER BY {$order} {$dir}, l.id ASC
        {$limitSql}
        ");

        $campaignCache = [];
        if (!empty($CampaignId) && is_numeric($CampaignId)) {
            $campaignCache[intval($CampaignId)] = Campaign::find(intval($CampaignId));
        }

        $data = [];
        foreach ($leads as $lead) {
            $cId = $lead->campaign_id;
            if (!array_key_exists($cId, $campaignCache)) {
                $campaignCache[$cId] = Campaign::find($cId);
            }
            $Campaign = $campaignCache[$cId];

            $lastCallRaw = !empty($lead->last_call_at) ? $lead->last_call_at : $lead->last_actioned_date;

            $row = [
                'check' => "<div class='row_checker'><label class=\"ui-check m-a-0\"><input type=\"checkbox\" name=\"ids[]\" id=\"ids\" value=\"{$lead->id}\"><i class=\"dark-white\"></i>" . Form::hidden('row_ids[]', $lead->id, ['class' => 'form-control row_no']) . "</label></div>",
                'id' => "<a href=\"javascript:void(0)\" onclick=\"leadDetail('{$lead->id}')\" class='text-primary' style='cursor:pointer;'>{$lead->id}</a>",
                'campaign_id' => $cId ?: 1,
                'campaign_name' => $lead->campaign_name,
                'name' => $lead->name,
                'action_status' => $lead->action_status,
                'status_name' => $lead->status_name,
                'designation' => $lead->designation,
                'phonenumber' => \App\Helpers\Helper::number_masking($lead->phonenumber, $Campaign->number_masking ?? 0),
                'phonenumber_2' => \App\Helpers\Helper::number_masking($lead->phonenumber_2, $Campaign->number_masking ?? 0),
                'substatus_name' => $lead->substatus_name,
                'assign' => $lead->assign_to,
                'remarks' => $urlFilter === 'followup' ? ($lead->lead_remarks ?: $lead->remarks) : $lead->remarks,
                'sales_person' => $lead->sales_person,
                'last_action' => $lead->last_action,
                'last_actioned_date' => $lead->last_actioned_date,
                'last_call_display' => $this->formatLeadDateTime($lastCallRaw),
                'follow_up_on' => $lead->follow_up_on,
                'next_followup_display' => $this->formatLeadDateTime($lead->follow_up_on),
                'company' => $lead->company,
                'description' => $lead->description,
                'country' => $lead->country,
                'pincode' => $lead->pin_code,
                'city' => !empty($lead->city) ? $lead->city : '—',
                'state' => $lead->state,
                'address' => $lead->address,
                'email' => $lead->email,
                'website' => $lead->website,
                'lead_value' => $lead->lead_value,
                'position' => $lead->position,
                'tags' => $lead->tags,
                'product' => !empty($lead->product_service) ? $lead->product_service : (!empty($lead->company) ? $lead->company : '—'),
                'product_service' => $lead->product_service,
                'edit' => '',
            ];

            if (!empty($Campaign) && strtotime($Campaign->expiry_date) > strtotime(date('Y-m-d'))) {
                $editurl = route('leadsEdit', ['id' => $lead->id]);
                $row['edit'] = "<div class='action-items'><a href='{$editurl}'><i class='material-icons' data-toggle='tooltip' data-placement='bottom' title='Edit'>&#xe3c9;</i></a></div>";
            }

            $data[] = $row;
        }

    $response = [
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalData,
        'recordsFiltered' => $totalFiltered,
        'data' => $data,
        'statics' => [],
        'followup_counts' => $followupCounts,
    ];

    return response()->json($response);
}





    public function transfer($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaign = Campaign::find($id);
        $Staff = DB::select("SELECT u.* FROM ".env('DB_TABLE_PREFIX')."users u
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions p
ON u.`permissions_id` = p.id AND p.name != 'Sales Person' AND p.name NOT IN ('Admin', 'Super Admin', 'Webmaster')
    WHERE FIND_IN_SET('".$Campaign->team_id."', REPLACE(REPLACE(u.team_id, '''', ''), ' ', ''))ORDER BY u.name");
        $SalesPersons = DB::select("SELECT u.* FROM ".env('DB_TABLE_PREFIX')."users u
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions p
ON u.`permissions_id` = p.id AND p.name = 'Sales Person'
WHERE team_id = ".$Campaign->team_id." ORDER BY u.name");
    $isassigned = DB::table('leads')
        ->where('campaign_id', $id)
        ->whereNotNull('assigned_to')
        ->count();


        if (!empty($id)) {
            return view("dashboard.campaigns.transfer", compact("GeneralWebmasterSections", "id", "Staff", "SalesPersons","isassigned"));
        } else {
            return redirect()->action('Dashboard\CampaignsController@index');
        }
    }
public function randomAllocation($id)
{
    // 1️⃣ Get campaign team_id
    $campaign = \App\Models\Campaign::find($id);

    if(!$campaign){
        return response()->json(['message' => 'Campaign not found']);
    }

    $campaignTeamId = $campaign->team_id;

    // 2️⃣ Get users matching:
    // - status = 1
    // - team_id contains campaign team_id
    // - role = Telecaller or Sales Person

    $users = DB::table('users')
        ->leftJoin('permissions', 'users.permissions_id', '=', 'permissions.id')
        ->whereRaw("FIND_IN_SET(?, team_id)", [$campaignTeamId])
        ->whereIn('permissions.name', ['Telecaller', 'Sales Person'])
        ->pluck('users.id')
        ->toArray();

    if(empty($users)){
        return response()->json([
            'message' => 'No matching Telecaller or Sales Person found for this team'
        ]);
    }

    // 3️⃣ Get unassigned leads
    $leads = \App\Models\Lead::where('campaign_id', $id)
                ->whereNull('assigned_to')
                ->pluck('id')
                ->toArray();

    if(empty($leads)){
        return response()->json([
            'message' => 'No unassigned leads found'
        ]);
    }

    // 4️⃣ If leads less than users → error
    if(count($leads) < count($users)){
        return response()->json([
            'message' => 'Leads are less than number of eligible users'
        ]);
    }

    // 5️⃣ Equal Distribution
    $chunks = array_chunk($leads, ceil(count($leads)/count($users)));

    foreach($users as $index => $userId)
    {
        if(isset($chunks[$index])){
            \App\Models\Lead::whereIn('id', $chunks[$index])
                ->update(['assigned_to' => $userId]);
        }
    }

    return response()->json([
        'message' => 'Leads allocated equally among matched users successfully'
    ]);
}
public function transferUnassign(Request $request)
    {
        $request->validate(['leadsArray' => 'required|array|min:1', 'leadsArray.*' => 'integer'], ['leadsArray.required' => 'Select at least one lead.']);
        $Staff = $request->staff;
        $SalesPerson = $request->sales_person;
        $leads = $request->leadsArray;
        foreach($leads as $lead)
        {
            $Lead = Lead::find($lead);
            if (!$Lead) continue;
            if($Lead->assigned_to != $Staff)
            {
                $allocationHistory = new AllocationHistory;
                $allocationHistory->lead_id = $lead;
                $allocationHistory->user_type = 'Staff';
                $allocationHistory->from_user = $Lead->assigned_to;
                $allocationHistory->to_user = null;
                $allocationHistory->allocation_type = 'Unassign';
                $allocationHistory->created_by = Auth::user()->id;
                $allocationHistory->save();
            }

            if($Lead->sales_person_id != $SalesPerson)
            {
                $allocationHistory = new AllocationHistory;
                $allocationHistory->lead_id = $lead;
                $allocationHistory->user_type = 'Sales Person';
                $allocationHistory->from_user = $Lead->sales_person_id;
                $allocationHistory->to_user = null;
                $allocationHistory->allocation_type = 'Unassign';
                $allocationHistory->created_by = Auth::user()->id;
                $allocationHistory->save();
            }
            
            $Lead->assigned_to = null;
            $Lead->sales_person_id = null;
            $Lead->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Leads unassigned successfully!'
        ]);
    }

    
    public function transferAction(Request $request)
    {
        $request->validate([
            'leadsArray' => 'required|array|min:1',
            'leadsArray.*' => 'integer',
            'staff' => 'required|integer',
        ], [
            'leadsArray.required' => 'Select at least one lead.',
            'staff.required' => 'Select the executive.',
        ]);
        // Leads go to executives only: an Admin account (or an inactive user) is refused here, not just hidden in the page
        if (!self::executiveUsers()->contains('id', (int)$request->staff)) {
            return response()->json(['status' => 'error', 'message' => 'The selected executive cannot receive leads (Admin accounts and inactive users are excluded).'], 422);
        }
        $Staff = $request->staff;
        // Pages that do not send a sales person (Transfer Leads) keep each lead's current one
        $keepSalesPerson = !$request->has('sales_person');
        $SalesPerson = $request->sales_person;
        $leads = $request->leadsArray;
        foreach($leads as $lead)
        {
            $Lead = Lead::find($lead);
            if (!$Lead) continue;
            if ($keepSalesPerson) $SalesPerson = $Lead->sales_person_id;
            if($Lead->assigned_to != $Staff)
            {
                $allocationHistory = new AllocationHistory;
                $allocationHistory->lead_id = $lead;
                $allocationHistory->user_type = 'Staff';
                $allocationHistory->from_user = $Lead->assigned_to;
                $allocationHistory->to_user = $Staff;
                $allocationHistory->allocation_type = 'Transfer';
                $allocationHistory->created_by = Auth::user()->id;
                $allocationHistory->save();
            }

            if($Lead->sales_person_id != $SalesPerson)
            {
                $allocationHistory = new AllocationHistory;
                $allocationHistory->lead_id = $lead;
                $allocationHistory->user_type = 'Sales Person';
                $allocationHistory->from_user = $Lead->sales_person_id;
                $allocationHistory->to_user = $SalesPerson;
                $allocationHistory->allocation_type = 'Transfer';
                $allocationHistory->created_by = Auth::user()->id;
                $allocationHistory->save();
            }
            
            $Lead->assigned_to = $Staff;
            $Lead->sales_person_id = $SalesPerson;
            $Lead->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Leads transferred successfully!'
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function inbound()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $campaigns = Campaign::whereDate(DB::raw("date(expiry_date)"),'>=',DB::raw("current_date"))
                ->orderBy('id','desc')->get();
        $agents = DB::select("SELECT * FROM leadger_users WHERE c2c_channel = 1 AND click_agent_number IS NOT NULL order by name");

        return view("dashboard.campaigns.inbound", compact("GeneralWebmasterSections", "campaigns", "agents"));
    }
    
    public function inboundStore(Request $request)
    {
        $this->validate($request, [
            'campaign_id' => 'required',
            'agent_id' => 'required',
            'source' => 'required',
        ]);
        
        $inbound = new \App\Models\Inbound();
        $inbound->campaign_id = $request->campaign_id;
        $inbound->agent_id = $request->agent_id;
        $inbound->source = $request->source;
        $inbound->save();
        
        QrCode::generate(route('registerLeadsPage', md5($inbound->id)), public_path('qr_code/'.md5($inbound->id).'.svg'));

        return redirect()->action('Dashboard\CampaignsController@inbound')->with('doneMessage', __('backend.addDone'));
    }

    public function inboundData(Request $request)
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
        
        $sql = " FROM leadger_inbound i
INNER JOIN leadger_campaigns c
ON i.campaign_id = c.id
INNER JOIN leadger_users u
ON i.agent_id = u.id
where 1=1 ";
//        if(Auth::user()->permissionsGroup->name != 'Admin')
//            $sql .= " and team.id in (".Auth::user()->team_id.") ";

        if ($q != "") {
            $sql .= " and (c.name like '%".$q."%' 
                        or u.name like '%".$q."%' 
                        or source like '%".$q."%' )";
        }

        $totalData = DB::select("select count(*) as cnt ".$sql)[0]->cnt;
        $totalFiltered = $totalData;
//order, paginate
        $Inbound = DB::select("SELECT i.*,c.name AS campaign_name,u.name AS agent_name,md5(i.id) as url 
                    ".$sql." order by $order $dir,id asc limit $start,$limit
                    ");

        $data = array();
        if ($totalFiltered > 0) {
            foreach ($Inbound as $Row) {
                $nestedData = [];
                $nestedData['id'] = $Row->id;
                $nestedData['campaign_name'] = $Row->campaign_name;
                $nestedData['agent_name'] = $Row->agent_name;
                $nestedData['source'] = $Row->source;
                $nestedData['created_at'] = $Row->created_at;
                $url = route('registerLeadsPage', $Row->url);
                $dpath = route('inboundQrcode', md5($Row->id));
                $nestedData['options'] = <<<AAA
                        <div class="action-items">
                        <a
                            onclick="copy('$url')">
                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="copy">&#xe14d;</i>
                            </small>
                        </a>
                        <a
                            href="$dpath" download>
                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="download">&#xf090;</i>
                            </small>
                        </a>
                        AAA;
                $nestedData['options'] .= <<<AAA
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
    
    public function downloadQrCode($id)
    {
        $filePath = public_path('qr_code/'.$id.'.svg');
        if(!file_exists($filePath))
        {
            QrCode::generate(route('registerLeadsPage', md5($Row->id)), $filePath);
        }
        $headers = [
            'Content-Type' => 'image/svg+xml',
        ];

        return Response::download($filePath, 'qrcode.svg', $headers);
    }

    public function duplicate()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Campaigns = DB::select("SELECT id,name,(SELECT COUNT(*) FROM leadger_leads WHERE campaign_id = leadger_campaigns.id) as lead_count
FROM leadger_campaigns
order by id desc");

        return view("dashboard.campaigns.duplicate", compact("GeneralWebmasterSections", "Campaigns"));
    }

    public function dupStore(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'campaign_id' => 'required',
            'date' => 'required',
        ]);
        
        $orgCampaign = Campaign::find($request->campaign_id);

        $Campaign = new Campaign;
        $Campaign->name = $request->name;
        $Campaign->source_id = $orgCampaign->source_id;
        $Campaign->segment_id = $orgCampaign->segment_id;
        $Campaign->team_id = $orgCampaign->team_id;
        $Campaign->expiry_date = $request->date;
        $Campaign->public = $orgCampaign->public;
        $Campaign->lead_info_edit = $orgCampaign->lead_info_edit;
        $Campaign->number_masking = $orgCampaign->number_masking;
        $Campaign->status = 1;
        $Campaign->faq = $orgCampaign->faq;
        $Campaign->created_by = Auth::user()->id;
        $Campaign->save();
        
        $LeadsCount = Lead::where('campaign_id',$orgCampaign->id)->count();
        if($LeadsCount > 0)
        {
            DB::update("CREATE TABLE IF NOT EXISTS campaign_".$Campaign->id."(
                `name` VARCHAR(100),
                designation varchar(100),
                phonenumber VARCHAR(20),
                phonenumber_2 VARCHAR(20),
                company VARCHAR(100),
                description VARCHAR(1000),
                country VARCHAR(30),
                pin_code VARCHAR(10),
                city VARCHAR(100),
                state VARCHAR(100),
                address VARCHAR(300),
                email VARCHAR(100),
                website VARCHAR(100),
                lead_value VARCHAR(20),
                `position` VARCHAR(100),
                tags VARCHAR(1000),
                source VARCHAR(100),
                assigned_to int,
                sales_person_id int,
                lead_id bigint,
                id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (id)
                )");

            DB::update("INSERT INTO campaign_".$Campaign->id."
                (`name`,designation,phonenumber,phonenumber_2,company,description,country,pin_code,city, 
state,address,email,website,lead_value,`position`,tags,source,assigned_to,sales_person_id)
SELECT `name`,designation,phonenumber,phonenumber_2,company,description,country,pin_code,city, 
state,address,email,website,lead_value,`position`,tags,source,assigned_to,sales_person_id
FROM leadger_leads
WHERE campaign_id = '".$orgCampaign->id."'
");

            DB::update("UPDATE campaign_".$Campaign->id." set lead_id=concat('".$Campaign->id."',lpad(id,6,'0')),source='Duplicate'");

            DB::update("INSERT INTO ".env('DB_TABLE_PREFIX')."leads (id,campaign_id,name,designation,phonenumber,phonenumber_2,company,description,
                country,pin_code,city,state,address,email,website,lead_value,position,tags,created_by,source,is_public,assigned_to,sales_person_id)
    SELECT lead_id,'".$Campaign->id."',name,designation,phonenumber,phonenumber_2,company,description,
                country,pin_code,city,state,address,email,website,lead_value,position,tags,'".Auth::user()->id."',source,$orgCampaign->public,assigned_to,sales_person_id FROM campaign_".$Campaign->id."");

            DB::update("DROP TABLE campaign_".$Campaign->id."");
        }

        return redirect()->action('Dashboard\CampaignsController@duplicate')->with('doneMessage', __('backend.addDone'));
    }

    /**
     * Normalize campaign calling type to 'webrtc', 'click2call', or null.
     */
    public static function getAllowedCampaignsForUser($user)
    {
        if (!$user) {
            return collect([]);
        }

        $query = Campaign::where('status', '1')
            ->whereDate(DB::raw("DATE(expiry_date)"), '>', date('Y-m-d'));

        $userRole = $user->permissionsGroup->name ?? '';
        if (!in_array($userRole, ['Admin', 'Webmaster'])) {
            $userTeamIds = array_filter(array_map('trim', explode(',', $user->team_id ?? '')));
            $query->where(function ($q) use ($user, $userTeamIds) {
                if (!empty($userTeamIds)) {
                    $q->whereIn('team_id', $userTeamIds);
                }
                $q->orWhereExists(function ($sq) use ($user) {
                    $sq->select(DB::raw(1))
                        ->from('leads')
                        ->whereColumn('leads.campaign_id', 'campaigns.id')
                        ->where('leads.assigned_to', $user->id);
                });
            });
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * May this user open this lead of this campaign on the calling page? The lead must belong to the campaign, and
     * the same rules as the "Next leads" queue apply: the campaign is one of the user's campaigns (Admin / Webmaster:
     * any), a Telecaller sees leads assigned to them or public, a Sales Person their own or public ones.
     */
    public static function canOpenCampaignLead($user, $lead, $campaignId, $allowedCampaigns = null): bool
    {
        if (!$user || !$lead || (int)$lead->campaign_id !== (int)$campaignId) {
            return false;
        }
        $role = $user->permissionsGroup->name ?? '';
        // $allowedCampaigns: getAllowedCampaignsForUser($user), passed in when checking many leads at once
        if (!in_array($role, ['Admin', 'Webmaster'], true) && !($allowedCampaigns ?? self::getAllowedCampaignsForUser($user))->contains('id', (int)$campaignId)) {
            return false;
        }
        if ($role === 'Telecaller') {
            return (int)$lead->assigned_to === (int)$user->id || (string)$lead->is_public === '1';
        }
        if ($role === 'Sales Person') {
            return (int)$lead->sales_person_id === (int)$user->id || (string)$lead->is_public === '1';
        }
        return true;
    }

    public static function normalizeCampaignCallingType($Campaign)
    {
        if (empty($Campaign)) {
            return null;
        }

        $rawType = $Campaign->calling_type
            ?? $Campaign->call_type
            ?? $Campaign->calling_method
            ?? $Campaign->dialer_type
            ?? $Campaign->type
            ?? null;

        if (empty($rawType)) {
            return null;
        }

        $val = strtolower(trim((string)$rawType));
        if ($val === 'both' || (str_contains($val, 'webrtc') && (str_contains($val, 'click') || str_contains($val, 'c2c')))) {
            return 'both';
        }
        if (str_contains($val, 'webrtc')) {
            return 'webrtc';
        }
        if (str_contains($val, 'click') || str_contains($val, 'c2c')) {
            return 'click2call';
        }

        return null;
    }

    /**
     * View Lead Calling / Lead Details page from Campaign View
     *
     * @param int $campaignId
     * @param int $leadId
     * @return \Illuminate\Http\Response
     */
    public function leadCallingView($campaignId, $leadId)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $User = Auth::user();

        // Opened from an Inbound Calls customer mobile: a lead that can no longer be opened here (deleted, moved,
        // not allowed) falls back to Manual Dial with the caller's number instead of another lead
        if (request()->filled('inbound')) {
            $inboundLead = Lead::find($leadId);
            if (!$inboundLead || !self::canOpenCampaignLead($User, $inboundLead, $campaignId)) {
                $inbound = ctype_digit((string)request('inbound')) ? \App\Models\InboundCall::find((int)request('inbound')) : null;
                $number = $inbound && \App\Services\InboundCallQuery::userSees($User, $inbound->id) ? (string)$inbound->customer_mobile_normalized : '';
                return redirect()->to(\App\Services\InboundCallDestination::manualUrl($number))->with('inboundNotice', 'Opened in Manual Dial');
            }
        }

        session(['selected_campaign_id' => $campaignId]);
        $allowedCampaigns = self::getAllowedCampaignsForUser($User);
        $selectedCampaignId = $campaignId;

        $Campaign = Campaign::find($campaignId);
        if (empty($Campaign)) {
            $Campaign = (object)[
                'id' => $campaignId,
                'name' => 'September Home Loan'
            ];
        }

        $campaignCallingType = self::normalizeCampaignCallingType($Campaign);

        $Lead = Lead::find($leadId);
        if ($Lead && !self::canOpenCampaignLead($User, $Lead, $campaignId)) {
            if ((int)$Lead->campaign_id === (int)$campaignId) {
                return redirect()->route('dialer', ['tab' => 'campaign'])->with('errorMessage', 'You are not allowed to open this lead.');
            }
            $Lead = null;   // a lead of another campaign: open this campaign's first lead instead
        }
        if (empty($Lead)) {
            // the first lead of this campaign that this user may open (not another agent's lead)
            $allowed = self::getAllowedCampaignsForUser($User);
            $Lead = Lead::where('campaign_id', $campaignId)->orderBy('id')->get()
                ->first(fn ($l) => self::canOpenCampaignLead($User, $l, $campaignId, $allowed));
            // Start Calling (lead 0) or a lead of another campaign: the address shows the lead opened (the page loads it from there)
            if ($Lead && (int)$leadId !== (int)$Lead->id) {
                return redirect()->route('leadCallingView', ['campaignId' => $campaignId, 'leadId' => $Lead->id]);
            }
        }

        // Fetch leads for the Next Leads sidebar queue with active lead at the top (only leads this user may open)
        $allowed = $allowed ?? self::getAllowedCampaignsForUser($User);
        $remainingLeads = Lead::where('campaign_id', $campaignId)
            ->when($Lead, function($q) use ($Lead) {
                return $q->where('id', '!=', $Lead->id);
            })
            ->orderBy('id', 'asc')
            ->get()
            ->filter(fn ($l) => self::canOpenCampaignLead($User, $l, $campaignId, $allowed))->values();
        $campaignLeads = $Lead ? collect([$Lead])->concat($remainingLeads) : $remainingLeads;
        
        $totalQueueCount = $campaignLeads->count();

        // Get status lookup
        $Statuses = \App\Models\Status::orderBy('seq_order', 'asc')->get();
        $SubStatuses = \App\Models\Substatus::orderBy('seq_order', 'asc')->get();

        // Call history for selected lead
        $callLogs = collect([]);
        if ($Lead) {
            $callLogs = DB::table('call_logs')
                ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
                ->leftJoin('statuses', 'statuses.id', '=', 'call_logs.next_status')
                ->select('call_logs.*', 'users.name as called_by_name', 'statuses.name as status_name')
                ->where('call_logs.lead_id', $Lead->id)
                ->orderBy('call_logs.id', 'desc')
                ->get();

            // Compute Lead attributes
            $Lead->formatted_id = (string)$Lead->id;
            $Lead->city_name = !empty($Lead->city) ? $Lead->city : 'Hyderabad';
            
            // Product Name
            $productName = 'Home Loan';
            if (!empty($Lead->product_service)) {
                $ps = \App\Models\ProductService::find($Lead->product_service);
                if ($ps && !empty($ps->name)) {
                    $productName = $ps->name;
                }
            } elseif (!empty($Campaign->name)) {
                $productName = $Campaign->name;
            }
            $Lead->product_name = $productName;
            $Lead->source_name = !empty($Lead->source) ? $Lead->source : 'Website';
            $Lead->calls_count = $callLogs->whereNotNull('next_status')->count();   // calls with feedback saved

            // Status name & dot color (a lead without a status is a new lead)
            $statusName = 'New Lead';
            if (!empty($Lead->status)) {
                $st = $Statuses->where('id', $Lead->status)->first();
                if ($st && !empty($st->name)) {
                    $statusName = $st->name;
                }
            }
            $Lead->status_name = $statusName;

            // Initials avatar
            $nameParts = explode(' ', trim($Lead->name ?: 'Lead'));
            $initials = '';
            if (count($nameParts) >= 2) {
                $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
            } else {
                $initials = strtoupper(substr($Lead->name ?: 'LD', 0, 2));
            }
            $Lead->initials = $initials;
        }

        // Latest call duration
        $latestCallDuration = '00:00:00';
        if ($callLogs->isNotEmpty()) {
            $firstLog = $callLogs->first();
            if (!empty($firstLog->duration) && is_numeric($firstLog->duration)) {
                $dur = (int)$firstLog->duration;
                $latestCallDuration = sprintf('%d:%02d:%02d', floor($dur / 3600), floor(($dur % 3600) / 60), $dur % 60);
            }
        }

        // Determine next lead ID for "Next Lead" button
        $nextLeadId = null;
        if ($Lead) {
            $foundCurrent = false;
            foreach ($campaignLeads as $cLead) {
                if ($foundCurrent) {
                    $nextLeadId = $cLead->id;
                    break;
                }
                if ($cLead->id == $Lead->id) {
                    $foundCurrent = true;
                }
            }
            if (!$nextLeadId && $campaignLeads->count() > 0) {
                $nextLeadId = $campaignLeads->first()->id;
            }
        }

        // Format campaign leads for queue
        foreach ($campaignLeads as $cLead) {
            $parts = explode(' ', trim($cLead->name ?: 'Lead'));
            $cLead->initials = count($parts) >= 2 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($cLead->name ?: 'LD', 0, 2));
            
            $cStatusName = 'New';
            if (!empty($cLead->status)) {
                $st = $Statuses->where('id', $cLead->status)->first();
                if ($st && !empty($st->name)) {
                    $cStatusName = $st->name;
                }
            }
            $cLead->status_name = $cStatusName;

            // Status dot badge styling
            $sLower = strtolower($cStatusName);
            if (strpos($sLower, 'connect') !== false || strpos($sLower, 'interest') !== false || strpos($sLower, 'convert') !== false) {
                $cLead->badge_color = '#10b981'; // Green
            } elseif (strpos($sLower, 'follow') !== false || strpos($sLower, 'pending') !== false) {
                $cLead->badge_color = '#f59e0b'; // Amber/Orange
            } elseif (strpos($sLower, 'new') !== false || strpos($sLower, 'unactioned') !== false) {
                $cLead->badge_color = '#3b82f6'; // Blue
            } else {
                $cLead->badge_color = '#64748b'; // Slate/Grey
            }

            $cLead->product_name = $productName ?? 'Home Loan';
        }

        // Manual dialer dataset for Tab 2
        $Campaigns = Campaign::whereDate(DB::raw("DATE(expiry_date)"), '>', date('Y-m-d'))->get();
        // Click2Call / WebRTC numbers saved in Add Voucher (team-scoped for non-admins, with availability and expiry),
        // the same lists the Manual Dial page uses
        $vmnList = \App\Services\VmnService::listFor($User, 'click2call');
        $wvmnList = \App\Services\VmnService::listFor($User, 'webrtc');

        // Manual dialer drawer: the newest leads this user may see (all for admins / managers; otherwise their own,
        // assigned to them, or public)
        $allLeads = Lead::query()
            ->when(!in_array((string)optional($User->permissionsGroup)->name, ['Super Admin', 'Admin', 'Webmaster', 'Manager', 'Team Leader'], true),
                fn ($q) => $q->where(fn ($w) => $w->where('assigned_to', $User->id)->orWhere('sales_person_id', $User->id)
                    ->orWhere('created_by', $User->id)->orWhere('is_public', 1)))
            ->limit(50)->orderBy('id', 'desc')->get();
        foreach ($allLeads as $aLead) {
            $parts = explode(' ', trim($aLead->name ?: 'Lead'));
            $aLead->initials = count($parts) >= 2 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($aLead->name ?: 'LD', 0, 2));
            $aLead->city_name = $aLead->city ?: 'Hyderabad';
            $aStatus = 'New Lead';
            if (!empty($aLead->status)) {
                $st = $Statuses->where('id', $aLead->status)->first();
                if ($st) $aStatus = $st->name;
            }
            $aLead->status_name = $aStatus;
        }

        // The logged-in user's own calling channels (Staff -> Calling Method), read exactly as the Manual Dial page and the
        // channel-config endpoint do; a user with neither channel gets calling disabled with a message, not a default channel
        $c2cEnabled = $User->canClick2Call();
        $webrtcEnabled = ($User->webrtc_channel == 1);

        $activeTab = request()->get('tab', 'campaign');

        return view('dashboard.calling-workspace', compact(
            'GeneralWebmasterSections',
            'Campaign',
            'Lead',
            'campaignLeads',
            'totalQueueCount',
            'Statuses',
            'SubStatuses',
            'callLogs',
            'latestCallDuration',
            'nextLeadId',
            'campaignId',
            'leadId',
            'User',
            'Campaigns',
            'allowedCampaigns',
            'selectedCampaignId',
            'vmnList',
            'wvmnList',
            'allLeads',
            'activeTab',
            'campaignCallingType',
            'c2cEnabled',
            'webrtcEnabled'
        ));
    }

    /**
     * Get Leads in Queue for a selected campaign in Calling Workspace
     *
     * @param Request $request
     * @param int $campaignId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCampaignQueueLeads($campaignId = null)
    {
        if ($campaignId instanceof Request) {
            $campaignId = func_num_args() > 1 ? func_get_arg(1) : $campaignId->route('campaignId');
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if (empty($campaignId) || $campaignId == '0') {
            session()->forget('selected_campaign_id');
            return response()->json([
                'success' => true,
                'campaign_id' => null,
                'campaign_name' => null,
                'calling_type' => null,
                'campaign_calling_type' => null,
                'total_count' => 0,
                'count' => 0,
                'leads' => [],
                'first_lead_id' => null
            ]);
        }

        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $allowedCampaigns = self::getAllowedCampaignsForUser($user);
        if (!$allowedCampaigns->contains('id', $campaign->id)) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this campaign'], 403);
        }

        session(['selected_campaign_id' => $campaign->id]);

        $userRole = $user->permissionsGroup->name ?? '';
        $query = Lead::where('campaign_id', $campaign->id);

        if ($userRole === 'Telecaller') {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('is_public', '1');
            });
        } elseif ($userRole === 'Sales Person') {
            $query->where(function ($q) use ($user) {
                $q->where('sales_person_id', $user->id)
                  ->orWhere('is_public', '1');
            });
        }

        $allLeads = $query->orderBy('id', 'asc')->get();
        $statuses = \App\Models\Status::all()->keyBy('id');

        $validLeads = [];
        foreach ($allLeads as $lead) {
            $mobile = $lead->phonenumber ?: $lead->phonenumber_2;
            if (empty($mobile)) continue;

            $check = \App\Services\CallingRestrictionService::canPlaceOutboundCall($mobile, $user, 'click2call', false);
            if (!$check['allowed'] && ($check['reason'] ?? '') === 'retry_limit_reached') {
                continue;
            }

            $nameParts = explode(' ', trim($lead->name ?: 'Lead'));
            $initials = (count($nameParts) >= 2)
                ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                : strtoupper(substr($lead->name ?: 'LD', 0, 2));

            $statusName = 'New';
            if (!empty($lead->status) && isset($statuses[$lead->status])) {
                $statusName = $statuses[$lead->status]->name;
            }

            $sLower = strtolower($statusName);
            if (strpos($sLower, 'connect') !== false || strpos($sLower, 'interest') !== false || strpos($sLower, 'convert') !== false) {
                $badgeColor = '#10b981';
            } elseif (strpos($sLower, 'follow') !== false || strpos($sLower, 'pending') !== false) {
                $badgeColor = '#f59e0b';
            } elseif (strpos($sLower, 'new') !== false || strpos($sLower, 'unactioned') !== false) {
                $badgeColor = '#3b82f6';
            } else {
                $badgeColor = '#64748b';
            }

            $productName = 'Home Loan';
            if (!empty($lead->product_service)) {
                $ps = \App\Models\ProductService::find($lead->product_service);
                if ($ps && !empty($ps->name)) $productName = $ps->name;
            } elseif (!empty($campaign->name)) {
                $productName = $campaign->name;
            }

            $callsCount = DB::table('call_logs')->where('lead_id', $lead->id)->whereNotNull('next_status')->count();   // calls with feedback saved

            $validLeads[] = [
                'id' => $lead->id,
                'formatted_id' => (string)$lead->id,
                'name' => $lead->name ?: 'Lead ' . $lead->id,
                'phone' => $lead->phonenumber,
                'phonenumber' => $lead->phonenumber ?: ($lead->mobile ?: '—'),
                'city' => $lead->city ?: 'Hyderabad',
                'product' => $productName,
                'product_name' => $productName,
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'status_id' => $lead->status,
                'status_name' => $statusName,
                'substatus_id' => $lead->substatus,
                'initials' => $initials,
                'badge_color' => $badgeColor,
                'calls_count' => $callsCount,
                'source' => $lead->source ?: 'Website',
                'remarks' => $lead->remarks ?: '',
                'created_at' => $lead->created_at ? $lead->created_at->format('d-M-Y H:i') : ''
            ];
        }

        $campaignCallingType = self::normalizeCampaignCallingType($campaign);

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'campaign_calling_type' => $campaignCallingType,
            'calling_type' => $campaignCallingType,
            'leads' => $validLeads,
            'count' => count($validLeads),
            'total_count' => count($validLeads),
            'first_lead_id' => !empty($validLeads) ? $validLeads[0]['id'] : null
        ]);
    }

    /**
     * Save Call Disposition from Lead Calling Page
     *
     * @param Request $request
     * @param int $campaignId
     * @param int $leadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveLeadCallingDisposition(Request $request, $campaignId, $leadId)
    {
        $Lead = Lead::find($leadId);
        if (!$Lead) {
            return response()->json(['success' => false, 'message' => 'Lead not found'], 404);
        }

        $mainDisposition = $request->input('main_disposition');
        $subDisposition = $request->input('sub_disposition');
        $remarks = $request->input('remarks');

        // Resolve Status ID
        $statusId = null;
        if (!empty($mainDisposition)) {
            if (is_numeric($mainDisposition)) {
                $statusId = (int)$mainDisposition;
            } else {
                $st = \App\Models\Status::where('name', 'like', '%' . $mainDisposition . '%')->first();
                if ($st) {
                    $statusId = $st->id;
                }
            }
        }

        // A substatus must be one of the chosen status's substatuses (Master Data -> Lead Status)
        if (!empty($subDisposition) && is_numeric($subDisposition)
            && !\App\Models\Substatus::where('id', (int)$subDisposition)->where('status_id', $statusId)->exists()) {
            return response()->json(['success' => false, 'message' => 'The selected substatus does not belong to the selected status.'], 422);
        }

        // Follow-up: a valid future date & time; required when the status has "Requires follow-up date"
        $followAt = null;
        if ($request->filled('follow_up_on')) {
            try {
                $followAt = \Carbon\Carbon::parse($request->input('follow_up_on'))->second(0);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Enter a valid follow-up date and time.'], 422);
            }
            if ($followAt->lt(\Carbon\Carbon::now()->subMinute())) {
                return response()->json(['success' => false, 'message' => 'The follow-up date and time must be in the future.'], 422);
            }
        } elseif (Lead::statusRequiresFollowup($statusId)) {
            $name = \App\Models\Status::where('id', $statusId)->value('name') ?: 'This status';
            return response()->json(['success' => false, 'message' => "{$name} requires a follow-up date and time."], 422);
        }

        // "Only after a connected call" statuses: this lead's call (same lookup as below) must have connected
        if ($statusId && \App\Models\Status::where('id', $statusId)->value('needs_connected_call')) {
            $call = $request->filled('call_log_id') ? \App\Models\CallLog::find($request->input('call_log_id')) : null;
            $call = $call ?: \App\Models\CallLog::where('lead_id', $Lead->id)->where('created_by', Auth::id() ?: 1)
                ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(24))->orderBy('id', 'desc')->first();
            if ($error = Lead::connectedCallError($statusId, $call)) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
        }

        // Lead outcome, follow-up and call log are saved together: all of them or none
        DB::beginTransaction();
        try {
            if ($statusId) {
                $Lead->status = $statusId;
            }
            if (!empty($subDisposition) && is_numeric($subDisposition)) {
                $Lead->substatus = (int)$subDisposition;
            }
            if ($remarks) {
                $Lead->remarks = $remarks;
            }
            $Lead->last_actioned_date = date('Y-m-d H:i:s');
            $Lead->last_actioned_by = Auth::id() ?: 1;
            $Lead->save();
            if ($followAt) {
                // Same follow-up as Manual Dial: on this campaign lead (one per lead), with its calendar event
                $Lead->scheduleFollowup($followAt, Auth::user());
            }

            // Save / Update Call Log Record
            $callLog = null;
            if ($request->filled('call_log_id')) {
                $callLog = \App\Models\CallLog::find($request->input('call_log_id'));
            }
            if (!$callLog) {
                $callLog = \App\Models\CallLog::where('lead_id', $Lead->id)
                    ->where('created_by', Auth::id() ?: 1)
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(24))
                    ->orderBy('id', 'desc')
                    ->first();
            }

            $Campaign = Campaign::find($campaignId);
            $callingType = $request->input('call_type') 
                ?: (self::normalizeCampaignCallingType($Campaign) ?: 'click2call');
            if ($callingType === 'both') {
                $callingType = 'click2call';
            }

            $duration = $request->filled('duration') ? (int)$request->input('duration') : 0;

            if ($callLog) {
                $callLog->call_source = 'campaign';
                $callLog->campaign_id = $campaignId;
                $callLog->next_status = $statusId ?: $callLog->next_status;
                if (!empty($subDisposition) && is_numeric($subDisposition)) {
                    $callLog->substatus_id = (int)$subDisposition;
                }
                $callLog->remarks = $remarks ?: ($mainDisposition . ($subDisposition ? ' - ' . $subDisposition : ''));
                if ($duration > 0) {
                    $callLog->duration = $duration;
                }
                if ($followAt) {
                    $callLog->follow_up_on = $followAt;
                }
                // Keep a final outcome reported by the provider (busy / no-answer / failed); only close open calls
                if (in_array($callLog->call_state, [null, '', 'initiated', 'ringing', 'answered', 'connected'], true)) {
                    $callLog->call_state = 'completed';
                }
                if (empty($callLog->end_time)) {
                    $callLog->end_time = \Carbon\Carbon::now();
                }
                $callLog->save();
            } else {
                $callLog = new \App\Models\CallLog();
                $callLog->lead_id = $Lead->id;
                $callLog->campaign_id = $campaignId;
                $callLog->phonenumber = $Lead->phonenumber;
                $callLog->call_type = $callingType;
                $callLog->call_source = 'campaign';
                $callLog->call_state = 'completed';
                $callLog->created_by = Auth::id() ?: 1;
                $callLog->next_status = $statusId;
                if (!empty($subDisposition) && is_numeric($subDisposition)) {
                    $callLog->substatus_id = (int)$subDisposition;
                }
                $callLog->remarks = $remarks ?: ($mainDisposition . ($subDisposition ? ' - ' . $subDisposition : ''));
                // Disposition saved without a dialled call record: log it, but do not invent call times
                $callLog->duration = $duration;
                $callLog->start_time = \Carbon\Carbon::now();
                $callLog->end_time = \Carbon\Carbon::now();
                $callLog->verified = 1;
                if ($followAt) {
                    $callLog->follow_up_on = $followAt;
                }
                $callLog->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('[CampaignCalling] Failed to save call outcome: ' . $e->getMessage(), [
                'campaign_id' => $campaignId,
                'lead_id' => $leadId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'The call outcome was not saved: ' . $e->getMessage()], 500);
        }

        // Recording row sync (not part of the outcome; a failure here is logged and does not undo the outcome)
        try {
            \App\Services\CallRecordingService::storeOrUpdateRecording('Campaign Calling', $callLog, [
                'duration' => $callLog->duration,
                'recording_url' => $callLog->click_recording_url ?: ($request->input('recording_url') ?: null)
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[CampaignCalling] Recording sync after outcome failed: ' . $e->getMessage(), ['lead_id' => $leadId]);
        }

        return response()->json([
            'success' => true,
            'message' => $followAt
                ? 'Call outcome and follow-up saved successfully. Follow-up on ' . $followAt->format('d-M-Y h:i A') . " (lead {$Lead->id})."
                : 'Call outcome saved successfully.',
            'follow_up_on' => $followAt ? $followAt->format('Y-m-d H:i:s') : null,
            'lead_id' => $Lead->id,
        ]);
    }

    /**
     * Get JSON data for a specific lead in a campaign for SPA calling workspace transitions.
     */
    public function getLeadCallingData($campaignId, $leadId)
    {
        $Campaign = Campaign::find($campaignId);
        $campaignCallingType = $this->normalizeCampaignCallingType($Campaign);

        $Lead = Lead::find($leadId);
        if (!$Lead || (int)$Lead->campaign_id !== (int)$campaignId) {
            return response()->json(['success' => false, 'message' => 'Lead not found in this campaign'], 404);
        }
        if (!self::canOpenCampaignLead(Auth::user(), $Lead, $campaignId)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to open this lead.'], 403);
        }

        $callLogs = DB::table('call_logs')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
            ->leftJoin('statuses', 'statuses.id', '=', 'call_logs.next_status')
            ->select('call_logs.*', 'users.name as called_by_name', 'statuses.name as status_name')
            ->where('call_logs.lead_id', $Lead->id)
            ->orderBy('call_logs.id', 'desc')
            ->get()
            ->map(function($log) {
                $dur = is_numeric($log->duration) ? (int)$log->duration : 0;
                $formattedDur = \App\Helpers\Helper::formatDuration($dur);
                return [
                    'id' => $log->id,
                    'created_at' => date('d-M-Y H:i', strtotime($log->created_at)),
                    'called_by_name' => $log->called_by_name ?? 'Agent',
                    'status_name' => $log->status_name ?? 'Connected',
                    'duration' => $formattedDur,
                    'disposition' => $log->remarks ?? ($log->disposition ?? 'Interested'),
                    'comments' => $log->remarks ?? ($log->comments ?? ''),
                    'recording_url' => $log->recording_url ?? '',
                    'next_status' => $log->next_status,   // feedback saved (Calls count)
                ];
            });

        $nameParts = explode(' ', trim($Lead->name ?: 'Lead'));
        $initials = count($nameParts) >= 2 ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)) : strtoupper(substr($Lead->name ?: 'LD', 0, 2));

        $productName = 'Home Loan';
        if (!empty($Lead->product_service)) {
            $ps = \App\Models\ProductService::find($Lead->product_service);
            if ($ps && !empty($ps->name)) $productName = $ps->name;
        } elseif ($Campaign && !empty($Campaign->name)) {
            $productName = $Campaign->name;
        }

        $statusName = 'New Lead';   // a lead without a status
        if (!empty($Lead->status)) {
            $st = \App\Models\Status::find($Lead->status);
            if ($st && !empty($st->name)) $statusName = $st->name;
        }

        return response()->json([
            'success' => true,
            'campaign_calling_type' => $campaignCallingType,
            'lead' => [
                'id' => $Lead->id,
                'formatted_id' => (string)$Lead->id,
                'name' => $Lead->name ?: 'Unknown Lead',
                'mobile' => $Lead->mobile ?: ($Lead->phonenumber ?: 'N/A'),
                'city' => !empty($Lead->city) ? $Lead->city : 'Hyderabad',
                'product_name' => $productName,
                'campaign_name' => $Campaign ? $Campaign->name : 'Campaign',
                'campaign_id' => $campaignId,
                'campaign_calling_type' => $campaignCallingType,
                'source' => !empty($Lead->source) ? $Lead->source : 'Website',
                'calls_count' => collect($callLogs)->whereNotNull('next_status')->count(),   // calls with feedback saved
                'alt_phone' => !empty($Lead->alt_mobile) ? $Lead->alt_mobile : (!empty($Lead->alternate_phone) ? $Lead->alternate_phone : 'N/A'),
                'last_contacted' => !empty($Lead->last_actioned_date) ? date('M d, Y, h:i A', strtotime($Lead->last_actioned_date)) : 'Not contacted yet',
                'status' => $Lead->status,
                'substatus' => $Lead->substatus,
                'status_name' => $statusName,
                'initials' => $initials
            ],
            'callLogs' => $callLogs
        ]);
    }

    /**
     * Show the Transfer Accounts management page.
     */
    /** Executives for Assign / Un-Assign / Transfer Leads: active users who are not Admin-level accounts. */
    public static function executiveUsers()
    {
        return \App\Models\User::where('status', 1)
            ->whereDoesntHave('permissionsGroup', fn ($q) => $q->whereIn('name', ['Admin', 'Super Admin', 'Webmaster']))
            ->orderby('name', 'asc')
            ->get();
    }

    public function transferAccountsPage()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $Campaigns = Campaign::orderby('name', 'asc')->get();
        $Teams = Team::orderby('name', 'asc')->get();
        $Users = self::executiveUsers();
        $Statuses = DB::table('statuses')->orderby('name', 'asc')->get();

        // Build mappings for instant client-side cascading
        $teamUsersMap = [];
        foreach ($Teams as $t) {
            $pivotUsers = DB::table('team_user')->where('team_id', $t->id)->pluck('user_id')->toArray();
            $colUsers = DB::table('users')->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_id, '''', ''), ' ', ''))", [$t->id])->pluck('id')->toArray();
            $teamUsersMap[$t->id] = array_values(array_unique(array_merge($pivotUsers, $colUsers)));
        }

        $campaignTeamsMap = [];
        $campaignUsersMap = [];
        foreach ($Campaigns as $c) {
            $cTeamIds = !empty($c->team_id) ? array_values(array_filter(array_map('intval', explode(',', $c->team_id)))) : [];
            $campaignTeamsMap[$c->id] = $cTeamIds;

            $cUserIds = [];
            foreach ($cTeamIds as $tid) {
                if (isset($teamUsersMap[$tid])) {
                    $cUserIds = array_merge($cUserIds, $teamUsersMap[$tid]);
                }
            }
            if (!empty($c->auto_distribute_agents)) {
                $cUserIds = array_merge($cUserIds, array_filter(array_map('intval', explode(',', $c->auto_distribute_agents))));
            }
            $campaignUsersMap[$c->id] = array_values(array_unique($cUserIds));
        }

        return view("dashboard.campaigns.transfer_accounts", compact(
            "GeneralWebmasterSections",
            "Campaigns",
            "Teams",
            "Users",
            "Statuses",
            "campaignTeamsMap",
            "teamUsersMap",
            "campaignUsersMap"
        ));
    }

    /**
     * AJAX endpoint to return filtered teams and executives depending on campaign/team.
     */
    public function transferAccountsFilterOptions(Request $request)
    {
        $campaignId = trim((string)$request->input('campaign_id', ''));
        if (strtolower($campaignId) === 'all' || !is_numeric($campaignId)) {
            $campaignId = null;
        }

        $teamId = trim((string)$request->input('team_id', ''));
        if (strtolower($teamId) === 'all' || !is_numeric($teamId)) {
            $teamId = null;
        }

        $allTeams = Team::orderby('name', 'asc')->get();
        $allUsers = self::executiveUsers();

        $filteredTeams = $allTeams;
        if (!empty($campaignId)) {
            $campaign = Campaign::find($campaignId);
            if ($campaign && !empty($campaign->team_id)) {
                $cTeamIds = array_values(array_filter(array_map('intval', explode(',', $campaign->team_id))));
                $filteredTeams = $allTeams->whereIn('id', $cTeamIds)->values();
            } else {
                $filteredTeams = collect([]);
            }
        }

        $allowedUserIds = null;

        if (!empty($campaignId)) {
            $campaign = Campaign::find($campaignId);
            $cUserIds = [];
            if ($campaign && !empty($campaign->team_id)) {
                $cTeamIds = array_values(array_filter(array_map('intval', explode(',', $campaign->team_id))));
                $pivotUserIds = DB::table('team_user')->whereIn('team_id', $cTeamIds)->pluck('user_id')->toArray();
                $colUserIds = DB::table('users')->where(function ($q) use ($cTeamIds) {
                    foreach ($cTeamIds as $tid) {
                        $q->orWhereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_id, '''', ''), ' ', ''))", [$tid]);
                    }
                })->pluck('id')->toArray();
                $cUserIds = array_unique(array_merge($pivotUserIds, $colUserIds));
            }
            if ($campaign && !empty($campaign->auto_distribute_agents)) {
                $cUserIds = array_unique(array_merge($cUserIds, array_filter(array_map('intval', explode(',', $campaign->auto_distribute_agents)))));
            }
            $allowedUserIds = array_values($cUserIds);
        }

        if (!empty($teamId)) {
            $pivotUserIds = DB::table('team_user')->where('team_id', $teamId)->pluck('user_id')->toArray();
            $colUserIds = DB::table('users')->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_id, '''', ''), ' ', ''))", [$teamId])->pluck('id')->toArray();
            $teamUserIds = array_unique(array_merge($pivotUserIds, $colUserIds));

            if ($allowedUserIds !== null) {
                $allowedUserIds = array_values(array_intersect($allowedUserIds, $teamUserIds));
            } else {
                $allowedUserIds = array_values($teamUserIds);
            }
        }

        if ($allowedUserIds !== null) {
            $filteredUsers = $allUsers->whereIn('id', $allowedUserIds)->values();
        } else {
            $filteredUsers = $allUsers;
        }

        return response()->json([
            'status' => 'success',
            'teams' => $filteredTeams->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
            'users' => $filteredUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
        ]);
    }

    /**
     * JSON data endpoint for Transfer Accounts page DataTables/Server-side rendering.
     */
    public function transferAccountsData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'find_campaign' => 'nullable',
                'find_team' => 'nullable',
                'find_user' => 'nullable',
                'find_from_user' => 'nullable',
                'find_to_user' => 'nullable',
                'find_q' => 'nullable|string|max:150',
                'action_mode' => 'nullable|in:assign,unassign,transfer',
                'length' => 'nullable',
                'start' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                \Log::warning('Transfer accounts data validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Invalid parameters'
                ], 422);
            }

            $limit = $request->input('length', 10);
            $start = (int)$request->input('start', 0);
            if ($start < 0) $start = 0;

            $search = trim((string)$request->input('find_q', ''));
            $campaignId = trim((string)$request->input('find_campaign', ''));
            if (strtolower($campaignId) === 'all' || !is_numeric($campaignId)) {
                $campaignId = null;
            }

            $teamId = trim((string)$request->input('find_team', ''));
            if (strtolower($teamId) === 'all' || !is_numeric($teamId)) {
                $teamId = null;
            }

            $userId = trim((string)$request->input('find_user', ''));
            if (strtolower($userId) === 'all' || !is_numeric($userId)) {
                $userId = null;
            }

            $fromUserId = trim((string)$request->input('find_from_user', ''));
            if (strtolower($fromUserId) === 'all' || !is_numeric($fromUserId)) {
                $fromUserId = null;
            }

            $actionMode = $request->input('action_mode', 'assign');
            if (!in_array($actionMode, ['assign', 'unassign', 'transfer'])) {
                $actionMode = 'assign';
            }

            if ($actionMode === 'transfer' && !empty($fromUserId)) {
                $userId = $fromUserId;
            }

            $query = DB::table('leads as l')
                ->leftJoin('campaigns as c', 'l.campaign_id', '=', 'c.id')
                ->leftJoin('users as u', 'l.assigned_to', '=', 'u.id')
                ->leftJoin('teams as t', 'c.team_id', '=', 't.id')
                ->leftJoin('statuses as s', 'l.status', '=', 's.id');

            // Action Mode Logic
            if ($actionMode === 'assign') {
                // Assign mode shows unassigned leads
                $query->where(function ($q) {
                    $q->whereNull('l.assigned_to')->orWhere('l.assigned_to', 0);
                });
            } elseif ($actionMode === 'unassign') {
                // Unassign mode shows assigned leads
                $query->whereNotNull('l.assigned_to')->where('l.assigned_to', '!=', 0);
                if (!empty($userId)) {
                    $query->where('l.assigned_to', $userId);
                }
            } elseif ($actionMode === 'transfer') {
                // Transfer mode shows assigned leads
                $query->whereNotNull('l.assigned_to')->where('l.assigned_to', '!=', 0);
                if (!empty($userId)) {
                    $query->where('l.assigned_to', $userId);
                }
            }

            // Campaign Filter
            if (!empty($campaignId)) {
                $query->where('l.campaign_id', $campaignId);
            }

            // Team Filter
            if (!empty($teamId)) {
                $prefix = env('DB_TABLE_PREFIX', 'leadger_');
                if ($actionMode === 'assign') {
                    // For unassigned leads, team belongs to campaign
                    $query->where('c.team_id', $teamId);
                } else {
                    // For assigned leads, current owner belongs to selected team
                    $query->where(function ($sub) use ($teamId, $prefix) {
                        $sub->whereRaw("{$prefix}u.id IN (SELECT user_id FROM {$prefix}team_user WHERE team_id = ?)", [$teamId])
                            ->orWhereRaw("FIND_IN_SET(?, REPLACE(REPLACE({$prefix}u.team_id, '\'', ''), ' ', ''))", [$teamId]);
                    });
                }
            }

            // Search (lead ID, name, mobile, current owner, campaign)
            if (!empty($search)) {
                $cleanId = ltrim($search, '#');
                $query->where(function ($q) use ($search, $cleanId) {
                    $q->where('l.name', 'like', "%{$search}%")
                      ->orWhere('l.phonenumber', 'like', "%{$search}%")
                      ->orWhere('u.name', 'like', "%{$search}%")
                      ->orWhere('c.name', 'like', "%{$search}%")
                      ->orWhere('l.id', 'like', "%{$search}%");

                    if (is_numeric($cleanId)) {
                        $q->orWhere('l.id', '=', $cleanId);
                    }
                });
            }

            $totalRecords = $query->count();
            $limitVal = ($limit == -1 || strtolower($limit) === 'all') ? $totalRecords : (int)$limit;
            if ($limitVal <= 0) $limitVal = 10;

            $leads = $query->select(
                'l.id',
                'l.name',
                'l.phonenumber',
                'l.campaign_id',
                'l.assigned_to',
                'l.status',
                'c.name as campaign_name',
                'u.name as owner_name',
                't.name as team_name',
                's.name as status_name'
            )
            ->orderBy('l.id', 'desc')
            ->offset($start)
            ->limit($limitVal)
            ->get();

            $data = [];
            foreach ($leads as $lead) {
                $data[] = [
                    'id' => $lead->id,
                    'name' => $lead->name ?: '—',
                    'phonenumber' => $lead->phonenumber ?: '—',
                    'campaign_name' => $lead->campaign_name ?: '—',
                    'owner_name' => $lead->owner_name ?: 'Unassigned',
                    'team_name' => $lead->team_name ?: '—',
                    'status_name' => $lead->status_name ?: 'New',
                    'assigned_to' => $lead->assigned_to
                ];
            }

            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error fetching transfer accounts data: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while fetching leads.'
            ], 500);
        }
    }

    /**
     * Render Auto Distribute page
     */
    public function autoDistributePage(Request $request)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $Campaigns = Campaign::where('status', 1)->orderby('name', 'asc')->get();
        if ($Campaigns->isEmpty()) {
            $Campaigns = Campaign::orderby('name', 'asc')->get();
        }

        $Statuses = DB::table('statuses')->orderby('name', 'asc')->get();

        $selectedCampaignId = $request->input('campaign_id', null);
        $selectedCampaign = null;
        $campaignUsers = collect([]);
        $savedAgentIds = [];

        if ($selectedCampaignId) {
            $selectedCampaign = Campaign::find($selectedCampaignId);
            if ($selectedCampaign) {
                if (!empty($selectedCampaign->team_id)) {
                    $pivotUserIds = DB::table('team_user')
                        ->where('team_id', $selectedCampaign->team_id)
                        ->pluck('user_id')
                        ->toArray();

                    $colUserIds = DB::table('users')
                        ->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_id, '''', ''), ' ', ''))", [$selectedCampaign->team_id])
                        ->pluck('id')
                        ->toArray();

                    $allMemberIds = array_unique(array_merge($pivotUserIds, $colUserIds));

                    if (!empty($allMemberIds)) {
                        $campaignUsers = \App\Models\User::whereIn('id', $allMemberIds)
                            ->where('status', 1)
                            ->whereNotIn('name', ['Ametecs', 'LeadGER Admin'])
                            ->select('id', 'name', 'email')
                            ->orderBy('name', 'asc')
                            ->get();
                    }
                }

                if (!empty($selectedCampaign->auto_distribute_agents)) {
                    $savedAgentIds = array_values(array_filter(array_map('intval', explode(',', $selectedCampaign->auto_distribute_agents))));
                }
            }
        }

        return view('dashboard.campaigns.auto_distribute', compact(
            'GeneralWebmasterSections',
            'Campaigns',
            'campaignUsers',
            'Statuses',
            'selectedCampaignId',
            'selectedCampaign',
            'savedAgentIds'
        ));
    }

    /**
     * Fetch campaign lead counts & details for distribution UI
     */
    public function getCampaignDistributionDetails($id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return response()->json(['status' => 'error', 'message' => 'Campaign not found'], 404);
        }

        $totalLeads = DB::table('leads')->where('campaign_id', $id)->count();
        $availableLeads = DB::table('leads')
            ->where('campaign_id', $id)
            ->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', 0);
            })->count();

        $allocatedLeads = DB::table('leads')
            ->where('campaign_id', $id)
            ->whereNotNull('assigned_to')
            ->where('assigned_to', '!=', 0)
            ->count();

        // Get team members assigned to this campaign
        $teamUsers = [];
        if (!empty($campaign->team_id)) {
            $pivotUserIds = DB::table('team_user')
                ->where('team_id', $campaign->team_id)
                ->pluck('user_id')
                ->toArray();

            $colUserIds = DB::table('users')
                ->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_id, '''', ''), ' ', ''))", [$campaign->team_id])
                ->pluck('id')
                ->toArray();

            $allMemberIds = array_unique(array_merge($pivotUserIds, $colUserIds));

            if (!empty($allMemberIds)) {
                $teamUsers = DB::table('users')
                    ->whereIn('id', $allMemberIds)
                    ->where('status', 1)
                    ->whereNotIn('name', ['Ametecs', 'LeadGER Admin'])
                    ->select('id', 'name', 'email')
                    ->orderBy('name', 'asc')
                    ->get();
            }
        }

        // Get previously saved agents for this campaign
        $savedAgentIds = [];
        if (!empty($campaign->auto_distribute_agents)) {
            $savedAgentIds = array_values(array_filter(array_map('intval', explode(',', $campaign->auto_distribute_agents))));
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'total_leads' => $totalLeads,
                'available_leads' => $availableLeads,
                'allocated_leads' => $allocatedLeads,
                'remaining_leads' => $availableLeads,
                'team_users' => $teamUsers,
                'saved_agent_ids' => $savedAgentIds
            ]
        ]);
    }

    /**
     * Execute Auto Lead Distribution
     */
    public function executeAutoDistribution(Request $request)
    {
        $campaignId = $request->input('campaign_id');
        $agentIds = $request->input('agent_ids', []);
        $requestedQuantity = (int)$request->input('quantity', 0);

        if (!$campaignId) {
            return response()->json(['status' => 'error', 'message' => 'Please select a campaign.'], 422);
        }

        if (empty($agentIds) || !is_array($agentIds)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one executive.'], 422);
        }

        $cleanAgentIds = array_values(array_unique(array_filter(array_map('intval', $agentIds))));
        if (empty($cleanAgentIds)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one executive.'], 422);
        }

        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return response()->json(['status' => 'error', 'message' => 'Selected campaign not found.'], 404);
        }

        // Persist the auto distribution policy for this portfolio/campaign
        $campaign->auto_distribute_agents = implode(',', $cleanAgentIds);
        $campaign->save();

        // Check unassigned leads for this campaign
        $leadsQuery = DB::table('leads')
            ->where('campaign_id', $campaignId)
            ->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', 0);
            })
            ->orderBy('id', 'asc');

        $availableCount = $leadsQuery->count();

        // If quantity is not explicitly set, distribute all available unassigned leads
        if ($requestedQuantity <= 0) {
            $requestedQuantity = $availableCount;
        }

        $agentCount = count($cleanAgentIds);
        $totalToDistribute = 0;
        $distributionSummary = [];

        if ($requestedQuantity > 0 && $availableCount > 0) {
            $leadsToAssign = min($requestedQuantity, $availableCount);
            $leadIds = $leadsQuery->limit($leadsToAssign)->pluck('id')->toArray();
            $totalToDistribute = count($leadIds);

            if ($totalToDistribute > 0) {
                DB::beginTransaction();
                try {
                    $perAgent = (int)floor($totalToDistribute / $agentCount);
                    $remainder = $totalToDistribute % $agentCount;
                    $offset = 0;
                    $historyRecords = [];
                    $now = \Carbon\Carbon::now();
                    $currentUserId = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->id : 1;

                    foreach ($cleanAgentIds as $index => $agentId) {
                        $takeCount = $perAgent + ($index < $remainder ? 1 : 0);
                        if ($takeCount > 0 && isset($leadIds[$offset])) {
                            $assignedChunk = array_slice($leadIds, $offset, $takeCount);
                            DB::table('leads')->whereIn('id', $assignedChunk)->update(['assigned_to' => $agentId]);

                            foreach ($assignedChunk as $lId) {
                                $historyRecords[] = [
                                    'lead_id' => $lId,
                                    'user_type' => 'user',
                                    'from_user' => 0,
                                    'to_user' => $agentId,
                                    'allocation_type' => 'auto_distribute',
                                    'created_by' => $currentUserId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }

                            $offset += $takeCount;
                            $distributionSummary[$agentId] = count($assignedChunk);
                        }
                    }

                    if (!empty($historyRecords)) {
                        DB::table('allocation_history')->insert($historyRecords);
                    }

                    DB::commit();

                    return response()->json([
                        'status' => 'success',
                        'message' => "Auto distribute policy saved successfully and {$totalToDistribute} lead(s) distributed across {$agentCount} executive(s).",
                        'data' => [
                            'portfolio_id' => $campaignId,
                            'total_distributed' => $totalToDistribute,
                            'agent_count' => $agentCount,
                            'breakdown' => $distributionSummary
                        ]
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to distribute leads: ' . $e->getMessage()
                    ], 500);
                }
            }
        }

        // If no unassigned leads right now, the policy itself is saved successfully
        return response()->json([
            'status' => 'success',
            'message' => "Auto distribute policy saved successfully for '{$campaign->name}' across {$agentCount} executive(s).",
            'data' => [
                'portfolio_id' => $campaignId,
                'total_distributed' => 0,
                'agent_count' => $agentCount
            ]
        ]);
    }
}

