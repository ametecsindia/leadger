<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyAiSetting;
use App\Models\CallAiAnalysis;
use App\Models\AiUsageLog;
use App\Models\CallLog;
use App\Models\WebmasterSection;
use App\Models\User;
use App\Models\Permissions;
use Auth;
use DB;

class AiCallIntelligenceController extends Controller
{
    /**
     * Get or initialize company AI settings.
     */
    private function getCompanySettings()
    {
        return CompanyAiSetting::company();
    }

    /**
     * Display Company AI & Call Intelligence Settings UI.
     */
    public function aiSettings()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        // Calculate current month statistics
        $companyId = $settings->company_id;
        $totalAnalyzed = CallAiAnalysis::where('company_id', $companyId)->where('status', 'completed')->count();
        $totalDurationSec = CallAiAnalysis::where('company_id', $companyId)->where('status', 'completed')->sum('audio_duration_seconds');
        $totalCost = AiUsageLog::where('company_id', $companyId)->sum('usage_amount');

        $totalDurationMins = round($totalDurationSec / 60, 1);

        return view('dashboard.settings.ai', compact(
            'GeneralWebmasterSections',
            'settings',
            'totalAnalyzed',
            'totalDurationMins',
            'totalCost'
        ));
    }

    /**
     * Update Company AI Settings.
     */
    public function updateAiSettings(Request $request)
    {
        // Only Admin users can change settings
        $userPermission = Auth::user()->permissionsGroup->name ?? '';
        if (in_array($userPermission, ['Telecaller', 'Sales Person'])) {
            return redirect()->back()->with('errorMessage', 'Unauthorized access. Only Company Admin users can modify AI settings.');
        }

        $settings = $this->getCompanySettings();

        $settings->ai_enabled = $request->has('ai_enabled') ? (bool)$request->input('ai_enabled') : false;
        $settings->provider = $request->input('provider', 'Sarvam AI');
        $settings->auto_analysis_enabled = $request->input('analysis_trigger') === 'auto';
        
        $settings->transcript_enabled = $request->has('transcript_enabled');
        $settings->summary_enabled = $request->has('summary_enabled');
        $settings->interest_enabled = $request->has('interest_enabled');
        $settings->sentiment_enabled = $request->has('sentiment_enabled');
        $settings->intent_enabled = $request->has('intent_enabled');
        $settings->key_points_enabled = $request->has('key_points_enabled');
        $settings->next_action_enabled = $request->has('next_action_enabled');
        $settings->auto_disposition_enabled = $request->has('auto_disposition_enabled');
        $settings->billing_mode = $request->input('billing_mode', 'Usage Based');
        if ($request->has('price_per_unit')) {
            $settings->price_per_unit = max(0.0001, (float)$request->input('price_per_unit'));
        }
        $settings->pricing_unit = $request->input('pricing_unit', 'minute');
        $settings->currency = $request->input('currency', 'INR');
        $settings->client_billing_enabled = $request->has('client_billing_enabled') ? true : false;
        $settings->save();

        return redirect()->back()->with('doneMessage', 'AI Call Intelligence settings updated successfully!');
    }

    /**
     * Display AI Analysis List Page.
     */
    public function aiAnalysisList(Request $request)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        if (!$settings->ai_enabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'AI Call Intelligence feature is currently disabled.');
        }

        $Telecallers = User::orderBy('name', 'asc')->get();
        $Campaigns = \App\Models\Campaign::orderBy('name', 'asc')->get();

        $companyId = $settings->company_id;
        $totalCalls = CallLog::count();
        $analyzedCalls = CallAiAnalysis::where('status', 'completed')->count();
        $pendingCalls = CallAiAnalysis::whereIn('status', ['pending', 'processing'])->count();
        $avgDurationSec = CallAiAnalysis::avg('audio_duration_seconds');
        if (!$avgDurationSec) {
            $avgDurationSec = CallLog::avg('duration') ?: 0;
        }
        $avgDurationMins = round($avgDurationSec / 60, 1);

        $records = CallAiAnalysis::select([
                'call_ai_analyses.*',
                'call_logs.phonenumber',
                'call_logs.lead_id',
                'call_logs.duration',
                'users.name as agent_name',
                'leads.name as customer_name',
                'campaigns.name as campaign_name'
            ])
            ->join('call_logs', 'call_logs.id', '=', 'call_ai_analyses.call_id')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'leads.campaign_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
            ->orderBy('call_ai_analyses.created_at', 'desc')
            ->get();

        return view('dashboard.ai.analysis_list', compact(
            'GeneralWebmasterSections',
            'settings',
            'Telecallers',
            'Campaigns',
            'totalCalls',
            'analyzedCalls',
            'pendingCalls',
            'avgDurationMins',
            'records'
        ));
    }

    /**
     * DataTables AJAX Endpoint for AI Analysis List.
     */
    public function aiAnalysisData(Request $request)
    {
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $search = $request->input('search.value');
        $sentimentFilter = $request->input('sentiment');
        $interestFilter = $request->input('interest');
        $statusFilter = $request->input('status');
        $usersFilter = $request->input('users') ?: $request->input('agent');
        $campaignFilter = $request->input('campaign');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = CallAiAnalysis::select([
                'call_ai_analyses.*',
                'call_logs.phonenumber',
                'call_logs.lead_id',
                'call_logs.duration',
                'users.name as agent_name',
                'leads.name as customer_name',
                'campaigns.name as campaign_name'
            ])
            ->join('call_logs', 'call_logs.id', '=', 'call_ai_analyses.call_id')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'leads.campaign_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('call_ai_analyses.call_id', 'like', "%{$search}%")
                  ->orWhere('leads.name', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%")
                  ->orWhere('call_logs.phonenumber', 'like', "%{$search}%")
                  ->orWhere('call_ai_analyses.intent', 'like', "%{$search}%");
            });
        }

        if (!empty($sentimentFilter)) {
            $query->where('call_ai_analyses.sentiment', $sentimentFilter);
        }

        if (!empty($interestFilter)) {
            $query->where('call_ai_analyses.interest_level', $interestFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('call_ai_analyses.status', $statusFilter);
        }

        if (!empty($usersFilter)) {
            if (is_array($usersFilter)) {
                $query->whereIn('call_logs.created_by', array_filter($usersFilter));
            } else {
                $query->where('call_logs.created_by', $usersFilter);
            }
        }

        if (!empty($campaignFilter)) {
            $query->where('leads.campaign_id', $campaignFilter);
        }

        if (!empty($fromDate)) {
            $query->whereDate('call_ai_analyses.created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('call_ai_analyses.created_at', '<=', $toDate);
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        if ($limit != -1) {
            $query->offset($start)->limit($limit);
        }

        $records = $query->orderBy('call_ai_analyses.created_at', 'desc')->get();

        $data = [];
        foreach ($records as $item) {
            $row = [];
            $row['call_id'] = "<a onclick=\"leadDetail('{$item->lead_id}')\" class='font-weight-bold cursor-pointer' style='color: #1769FF; text-decoration: none;'>#{$item->call_id}</a>";
            $row['agent'] = "<span class='font-weight-bold text-dark'>" . ($item->agent_name ?: 'System User') . "</span>";
            $row['customer'] = "<span class='text-dark'>" . ($item->customer_name ?: ($item->phonenumber ?: 'N/A')) . "</span>";
            $row['duration'] = round(($item->audio_duration_seconds ?: $item->duration ?: 0) / 60, 1) . ' mins';
            
            // Interest Badge
            $intBadgeClass = match(strtoupper($item->interest_level ?? '')) {
                'HIGH' => 'background: #DCFCE7; color: #166534;',
                'MEDIUM' => 'background: #DBEAFE; color: #1E40AF;',
                'LOW' => 'background: #FFEDD5; color: #9A3412;',
                default => 'background: #F1F5F9; color: #64748B;'
            };
            $row['interest'] = "<span class='badge' style='font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px; {$intBadgeClass}'>" . ucfirst(strtolower($item->interest_level ?: 'High')) . "</span>";

            // Sentiment Badge
            $sentBadgeClass = match(strtoupper($item->sentiment ?? '')) {
                'POSITIVE' => 'background: #DCFCE7; color: #166534;',
                'NEUTRAL' => 'background: #F1F5F9; color: #475569;',
                'NEGATIVE' => 'background: #FEE2E2; color: #991B1B;',
                default => 'background: #DCFCE7; color: #166534;'
            };
            $row['sentiment'] = "<span class='badge' style='font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px; {$sentBadgeClass}'>" . ucfirst(strtolower($item->sentiment ?: 'Positive')) . "</span>";

            $row['intent'] = "<span class='small text-muted'>" . ($item->intent ?: 'Product Inquiry') . "</span>";
            
            // Status badge
            $statusBadge = match($item->status) {
                'completed' => "<span style='color: #059669; font-weight: 600; font-size: 12px;'><i class='fas fa-check me-1'></i> Completed</span>",
                'processing' => "<span style='color: #2563EB; font-weight: 600; font-size: 12px;'><i class='fas fa-spinner fa-spin me-1'></i> Processing</span>",
                'pending' => "<span style='color: #D97706; font-weight: 600; font-size: 12px;'><i class='fas fa-clock me-1'></i> Pending</span>",
                'failed' => "<span style='color: #DC2626; font-weight: 600; font-size: 12px;'><i class='fas fa-times me-1'></i> Failed</span>",
                default => "<span style='color: #059669; font-weight: 600; font-size: 12px;'><i class='fas fa-check me-1'></i> Completed</span>"
            };
            $row['status'] = $statusBadge;

            $row['date'] = "<span class='small text-muted'>" . date('d Mar Y H:i', strtotime($item->created_at)) . "</span>";

            // Action: [ View Transcript ]
            $transcriptUrl = route('callTranscriptDetail', $item->id);
            $actionBtn = "<a href='{$transcriptUrl}' class='btn btn-sm' style='background: #1769FF; color: #FFFFFF; font-size: 12px; font-weight: 600; padding: 5px 14px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;'><i class='fas fa-eye'></i> View Transcript</a>";

            $row['actions'] = $actionBtn;
            $data[] = $row;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data
        ]);
    }

    /**
     * Export AI Analysis CSV with current filters.
     */
    public function aiAnalysisExport(Request $request)
    {
        $sentimentFilter = $request->input('sentiment');
        $interestFilter = $request->input('interest');
        $statusFilter = $request->input('status');
        $usersFilter = $request->input('users') ?: $request->input('agent');
        $campaignFilter = $request->input('campaign');

        $query = CallAiAnalysis::select([
                'call_ai_analyses.*',
                'call_logs.phonenumber',
                'call_logs.lead_id',
                'call_logs.duration',
                'users.name as agent_name',
                'leads.name as customer_name',
                'campaigns.name as campaign_name'
            ])
            ->join('call_logs', 'call_logs.id', '=', 'call_ai_analyses.call_id')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'leads.campaign_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by');

        if (!empty($sentimentFilter)) {
            $query->where('call_ai_analyses.sentiment', $sentimentFilter);
        }

        if (!empty($interestFilter)) {
            $query->where('call_ai_analyses.interest_level', $interestFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('call_ai_analyses.status', $statusFilter);
        }

        if (!empty($usersFilter)) {
            if (is_array($usersFilter)) {
                $query->whereIn('call_logs.created_by', array_filter($usersFilter));
            } else {
                $query->where('call_logs.created_by', $usersFilter);
            }
        }

        if (!empty($campaignFilter)) {
            $query->where('leads.campaign_id', $campaignFilter);
        }

        $records = $query->orderBy('call_ai_analyses.created_at', 'desc')->get();

        $csvFileName = 'ai_call_analysis_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Call ID', 'User', 'Customer', 'Duration (mins)', 'Interest', 'Sentiment', 'Intent', 'Status', 'Date']);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->call_id,
                    $row->agent_name ?: 'System',
                    $row->customer_name ?: ($row->phonenumber ?: 'N/A'),
                    round(($row->audio_duration_seconds ?: $row->duration ?: 0) / 60, 1),
                    $row->interest_level ?: 'PENDING',
                    $row->sentiment ?: 'NEUTRAL',
                    $row->intent ?: 'Product Enquiry',
                    $row->status,
                    date('Y-m-d H:i', strtotime($row->created_at))
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Single AI Analysis View Details Page.
     */
    public function aiAnalysisDetail($id)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        if (!$settings->ai_enabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'AI Call Intelligence feature is currently disabled.');
        }

        $analysis = CallAiAnalysis::findOrFail($id);
        $callLog = CallLog::find($analysis->call_id);
        
        $customerName = 'Unknown Lead';
        $agentName = 'Telecaller';
        $campaignName = 'Default Campaign';

        if ($callLog) {
            $lead = \App\Models\Lead::find($callLog->lead_id);
            if ($lead) {
                $customerName = $lead->name ?: $callLog->phonenumber;
                $campaign = \App\Models\Campaign::find($lead->campaign_id);
                if ($campaign) {
                    $campaignName = $campaign->name;
                }
            }
            $user = User::find($callLog->created_by);
            if ($user) {
                $agentName = $user->name;
            }
        }

        return view('dashboard.ai.analysis_detail', compact(
            'GeneralWebmasterSections',
            'settings',
            'analysis',
            'callLog',
            'customerName',
            'agentName',
            'campaignName'
        ));
    }

    /**
     * Call Transcripts List Page.
     */
    public function callTranscriptsList(Request $request)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        if (!$settings->ai_enabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'AI Call Intelligence feature is currently disabled.');
        }

        $Users = User::orderBy('name', 'asc')->get();
        $Campaigns = \App\Models\Campaign::orderBy('name', 'asc')->get();

        $selectedUserIds = [];
        if ($request->filled('users')) {
            $rawUsers = $request->input('users');
            if (is_array($rawUsers)) {
                $selectedUserIds = array_filter($rawUsers);
            } else {
                $selectedUserIds = array_filter(explode(',', $rawUsers));
            }
        }

        $campaignFilter = $request->input('campaign');
        $interestFilter = $request->input('interest');
        $sentimentFilter = $request->input('sentiment');
        $statusFilter = $request->input('status');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $query = CallAiAnalysis::select([
                'call_ai_analyses.*',
                'call_logs.phonenumber',
                'call_logs.duration',
                'call_logs.created_by',
                'users.name as agent_name'
            ])
            ->join('call_logs', 'call_logs.id', '=', 'call_ai_analyses.call_id')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
            ->whereNotNull('call_ai_analyses.transcript')
            ->where('call_ai_analyses.transcript', '!=', '');

        if (!empty($selectedUserIds) && !in_array('all', $selectedUserIds)) {
            $query->whereIn('call_logs.created_by', $selectedUserIds);
        }

        if (!empty($campaignFilter)) {
            $query->where('leads.campaign_id', $campaignFilter);
        }

        if (!empty($interestFilter)) {
            $query->where('call_ai_analyses.interest_level', $interestFilter);
        }

        if (!empty($sentimentFilter)) {
            $query->where('call_ai_analyses.sentiment', $sentimentFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('call_ai_analyses.status', $statusFilter);
        }

        $transcripts = $query->orderBy('call_ai_analyses.created_at', 'desc')->paginate($perPage);
        $transcripts->appends($request->all());

        return view('dashboard.ai.transcript_list', compact(
            'GeneralWebmasterSections',
            'settings',
            'transcripts',
            'Users',
            'Campaigns',
            'selectedUserIds',
            'campaignFilter',
            'interestFilter',
            'sentimentFilter',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Export Call Transcripts CSV.
     */
    public function callTranscriptsExport(Request $request)
    {
        $selectedUserIds = [];
        if ($request->filled('users')) {
            $rawUsers = $request->input('users');
            if (is_array($rawUsers)) {
                $selectedUserIds = array_filter($rawUsers);
            } else {
                $selectedUserIds = array_filter(explode(',', $rawUsers));
            }
        }

        $campaignFilter = $request->input('campaign');
        $interestFilter = $request->input('interest');
        $sentimentFilter = $request->input('sentiment');
        $statusFilter = $request->input('status');

        $query = CallAiAnalysis::select([
                'call_ai_analyses.*',
                'call_logs.phonenumber',
                'call_logs.duration',
                'call_logs.created_by',
                'users.name as agent_name'
            ])
            ->join('call_logs', 'call_logs.id', '=', 'call_ai_analyses.call_id')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by')
            ->whereNotNull('call_ai_analyses.transcript')
            ->where('call_ai_analyses.transcript', '!=', '');

        if (!empty($selectedUserIds) && !in_array('all', $selectedUserIds)) {
            $query->whereIn('call_logs.created_by', $selectedUserIds);
        }

        if (!empty($campaignFilter)) {
            $query->where('leads.campaign_id', $campaignFilter);
        }

        if (!empty($interestFilter)) {
            $query->where('call_ai_analyses.interest_level', $interestFilter);
        }

        if (!empty($sentimentFilter)) {
            $query->where('call_ai_analyses.sentiment', $sentimentFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('call_ai_analyses.status', $statusFilter);
        }

        $records = $query->orderBy('call_ai_analyses.created_at', 'desc')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=call_transcripts_" . date('Y-m-d_H-i') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Call ID', 'User', 'Duration (mins)', 'Status', 'Date', 'Transcript']);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->call_id,
                    $row->agent_name ?: 'System',
                    round(($row->audio_duration_seconds ?: $row->duration ?: 0) / 60, 1),
                    $row->status,
                    date('Y-m-d H:i', strtotime($row->created_at)),
                    $row->transcript
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Call Transcript Single View Page.
     */
    public function callTranscriptDetail($id)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        if (!$settings->ai_enabled) {
            return redirect()->route('adminHome')->with('errorMessage', 'AI Call Intelligence feature is currently disabled.');
        }

        $analysis = CallAiAnalysis::findOrFail($id);
        $callLog = CallLog::find($analysis->call_id);

        return view('dashboard.ai.transcript_detail', compact(
            'GeneralWebmasterSections',
            'settings',
            'analysis',
            'callLog'
        ));
    }

    /**
     * AI Usage & Billing Page.
     */
    public function aiUsage(Request $request)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $settings = $this->getCompanySettings();

        $companyId = $settings->company_id;

        // Stat metrics for current month
        $totalCalls = AiUsageLog::where('company_id', $companyId)->count();
        $totalSeconds = AiUsageLog::where('company_id', $companyId)->sum('audio_duration_seconds');
        $totalUsageCost = AiUsageLog::where('company_id', $companyId)->sum('usage_amount');

        $totalMinutes = round($totalSeconds / 60, 1);

        $telecallerPermission = Permissions::where('name', 'Telecaller')->first();
        $Telecallers = collect();
        if ($telecallerPermission) {
            $Telecallers = User::where('permissions_id', $telecallerPermission->id)->get();
        }

        return view('dashboard.settings.ai_usage', compact(
            'GeneralWebmasterSections',
            'settings',
            'totalCalls',
            'totalMinutes',
            'totalUsageCost',
            'Telecallers'
        ));
    }

    /**
     * DataTables AJAX Endpoint for AI Usage Logs.
     */
    public function aiUsageData(Request $request)
    {
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $search = $request->input('search.value');
        $statusFilter = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = AiUsageLog::select([
                'ai_usage_logs.*',
                'call_logs.phonenumber',
                'call_logs.lead_id',
                'users.name as agent_name'
            ])
            ->leftJoin('call_logs', 'call_logs.id', '=', 'ai_usage_logs.call_id')
            ->leftJoin('users', 'users.id', '=', 'call_logs.created_by');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('ai_usage_logs.call_id', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%")
                  ->orWhere('call_logs.phonenumber', 'like', "%{$search}%")
                  ->orWhere('ai_usage_logs.provider', 'like', "%{$search}%");
            });
        }

        if (!empty($statusFilter)) {
            $query->where('ai_usage_logs.status', $statusFilter);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('ai_usage_logs.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        if ($limit != -1) {
            $query->offset($start)->limit($limit);
        }

        $records = $query->orderBy('ai_usage_logs.created_at', 'desc')->get();

        $data = [];
        foreach ($records as $item) {
            $row = [];
            $row['date'] = date('Y-m-d H:i:s', strtotime($item->created_at));
            $row['call_id'] = "#" . ($item->call_id ?: 'N/A');
            $row['agent'] = $item->agent_name ?: 'System Agent';
            $row['duration'] = round(($item->audio_duration_seconds ?: 0) / 60, 1) . ' mins (' . $item->audio_duration_seconds . 's)';
            $row['service'] = $item->service ?: 'Sarvam Speech-to-Text';
            $row['usage'] = "₹" . number_format($item->usage_amount, 4) . ' ' . $item->currency;
            
            $statusBadge = ($item->status === 'completed') 
                ? "<span class='badge bg-success-lt text-success fw-bold'><i class='fas fa-check-circle me-1'></i> Success</span>"
                : "<span class='badge bg-danger-lt text-danger fw-bold'><i class='fas fa-times-circle me-1'></i> Failed</span>";
            $row['status'] = $statusBadge;

            $data[] = $row;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data
        ]);
    }

    /**
     * Retry AI Analysis for jobs using Sarvam AI.
     */
    public function aiAnalysisRetry($id)
    {
        $analysis = CallAiAnalysis::findOrFail($id);
        
        $callLog = CallLog::find($analysis->call_id);
        $dialerLog = null;
        if (!$callLog) {
            $dialerId = ($analysis->call_id > 9000000) ? ($analysis->call_id - 9000000) : $analysis->call_id;
            $dialerLog = DB::table('dialer_logs')->where('id', $dialerId)->first();
        }

        $sourceCode = $dialerLog ? 2 : 0;
        $logId = $dialerLog ? (int)$dialerLog->id : (int)$analysis->call_id;

        // Same pipeline as the Recordings page: real stored audio only, processed on the queue worker
        try {
            $service = new \App\Services\TranscriptionBillingService();
            $batch = $service->createTranscriptionJobs(["{$sourceCode}_{$logId}"], 1, Auth::id(), true);
            $service->dispatchBatch($batch['batch_id']);
        } catch (\Exception $e) {
            return redirect()->back()->with('errorMessage', 'Transcript retry not started: ' . $e->getMessage());
        }

        return redirect()->back()->with('doneMessage', "Transcript for call #{$logId} queued. It will show Completed when processing finishes.");
    }

    /**
     * Test Sarvam AI API connection.
     */
    public function testSarvamConnection(Request $request)
    {
        $service = new \App\Services\SarvamAiService();
        if (!$service->isConfigured()) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Services\SarvamAiService::NOT_CONFIGURED_MESSAGE
            ], 400);
        }

        $fallbackFile = $service->getFallbackAudioFile();
        if (!$fallbackFile) {
            return response()->json([
                'status' => 'error',
                'message' => 'No audio sample file found on server to test Sarvam AI.'
            ], 404);
        }

        $startTime = microtime(true);
        $result = $service->transcribeAudioFile($fallbackFile);
        $latencyMs = round((microtime(true) - $startTime) * 1000);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connected to Sarvam AI successfully!',
                'request_id' => $result['request_id'] ?? '',
                'language_code' => $result['language_code'] ?? 'en-IN',
                'latency_ms' => $latencyMs,
                'sample_transcript' => $result['transcript'] ?? ''
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['error'] ?? 'Connection to Sarvam AI failed.'
        ], 500);
    }
}
