<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\User;
use App\Models\CallLog;
use App\Models\IncomingVmnLogs;
use App\Models\Lead;
use App\Models\Status;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class RecordingsController extends Controller
{



     public function recordings()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Campaigns = \App\Models\Campaign::orderBy('name', 'asc')->get();
        $Users = User::orderBy('name', 'asc')->get();
        $telecallerPermission = Permissions::where('name', 'Telecaller')->first();

        $Telecallers = collect();
        if ($telecallerPermission) {
            $Telecallers = User::where('permissions_id', $telecallerPermission->id)->get();
        }

        $canTranscribe = self::canRequestTranscript();
        $autoTranscript = \App\Models\CompanyAiSetting::company()->autoTranscriptEnabled();
        $sttConfigured = (new \App\Services\SarvamAiService())->isConfigured();

        return view("dashboard.recordings.recordings", compact("GeneralWebmasterSections", "Campaigns", "Telecallers", "Users", "canTranscribe", "autoTranscript", "sttConfigured"));
    }
     
     public function recordingData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start', 0);
        $columns = $request->input('columns');
        $dir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        // Filter-bar Search and the table's Search box; both apply when both are filled
        $searchTerms = array_values(array_unique(array_filter([trim((string)$request->input('find_q')), trim((string)$request->input('search.value'))], 'strlen')));
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $find_campaign = $request->input('find_campaign');
        $find_source = $request->input('find_source');
        $telecaller_id = $request->input('telecaller_id');
        $ai_status = $request->input('ai_status');
        
        $orderIndex = $request->input('order.0.column');
        $orderKey = isset($columns[$orderIndex]['data']) ? $columns[$orderIndex]['data'] : 'created_at';

        $orderMap = [
            'checkbox' => 'log_id',
            'id' => 'lead_id',
            'source_type' => 'type_sort',
            'campaign_name' => 'campaign_name',
            'called_by' => 'called_by',
            'phonenumber' => 'phonenumber',
            'recording' => 'file_name',
            'duration' => 'duration',
            'created_at' => 'created_at',
            'ai_analysis' => 'ai_status',
        ];

        $order = isset($orderMap[$orderKey]) ? $orderMap[$orderKey] : 'created_at';

        $tp = env('DB_TABLE_PREFIX', DB::getTablePrefix());
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';
        $userTeamIds = array_filter(array_map('intval', explode(',', $user->team_id ?? '')));
        $userId = $user->id;

        // 1. Call Logs (Campaign Calling)
        $callLogs = DB::table('call_logs')
            ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
            ->leftJoin('campaigns', function($join) {
                $join->on('campaigns.id', '=', 'call_logs.campaign_id')
                     ->orWhere(function($q) {
                         $q->whereNull('call_logs.campaign_id')
                           ->whereColumn('campaigns.id', 'leads.campaign_id');
                     });
            })
            ->join('users', 'users.id', '=', 'call_logs.created_by')
            ->leftJoin('call_ai_analyses', 'call_ai_analyses.call_id', '=', 'call_logs.id')
            ->leftJoin('recordings', 'recordings.id', '=', 'call_logs.recording_id')
            ->where('call_logs.call_source', 'campaign')
            ->where(function($q) {
                $q->where(function($sq) {
                    $sq->whereNotNull('call_logs.recording')->where('call_logs.recording', '!=', '');
                })->orWhere(function($sq) {
                    $sq->whereNotNull('call_logs.click_recording_url')->where('call_logs.click_recording_url', '!=', '');
                })->orWhereNotNull('call_logs.recording_id');
            })
            // Recordings deleted by an Admin are hidden (the call itself stays in its logs)
            ->where(function($q) {
                $q->whereNull('recordings.id')->orWhere('recordings.download_status', '!=', 'deleted');
            });

        if (!empty($userTeamIds) && !in_array($userRole, ['Super Admin', 'Admin', 'Webmaster'])) {
            $callLogs->where(function($q) use ($userTeamIds) {
                $q->whereIn('campaigns.team_id', $userTeamIds)->orWhereNull('campaigns.team_id');
            });
        }

        if ($userRole == 'Telecaller') {
            $callLogs->where(function ($query) use ($userId) {
                $query->where('leads.assigned_to', '=', $userId)
                    ->orWhere('leads.is_public', '=', '1')
                    ->orWhere('call_logs.created_by', '=', $userId);
            });
        } elseif ($userRole == 'Sales Person') {
            $callLogs->where(function ($query) use ($userId) {
                $query->where('leads.sales_person_id', '=', $userId)
                    ->orWhere('leads.is_public', '=', '1')
                    ->orWhere('call_logs.created_by', '=', $userId);
            });
        }

        foreach ($searchTerms as $q) {
            $callLogs->where(function ($query) use ($q) {
                $query->where('call_logs.id', '=', $q)
                    ->orWhere('call_logs.lead_id', '=', $q)
                    ->orWhere('call_logs.phonenumber', 'like', '%' . $q . '%')
                    ->orWhere('leads.name', 'like', '%' . $q . '%')
                    ->orWhere('users.name', 'like', '%' . $q . '%')
                    ->orWhere('campaigns.name', 'like', '%' . $q . '%')
                    ->orWhere('call_logs.recording', 'like', '%' . $q . '%')
                    ->orWhere('recordings.file_path', 'like', '%' . $q . '%');
            });
        }

        if (!empty($find_campaign)) {
            $callLogs->where('campaigns.id', $find_campaign);
        }
        if (!empty($telecaller_id)) {
            $telecallerIds = is_array($telecaller_id) ? $telecaller_id : explode(',', (string)$telecaller_id);
            $telecallerIds = array_filter(array_map('intval', $telecallerIds));
            if (!empty($telecallerIds)) {
                $callLogs->whereIn('call_logs.created_by', $telecallerIds);
            }
        }
        if (!empty($from_date) && !empty($to_date)) {
            $callLogs->whereBetween('call_logs.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
        }
        if (!empty($ai_status)) {
            if ($ai_status == 'none' || $ai_status == 'No Analysis') {
                $callLogs->where(function($sq) {
                    $sq->whereNull('call_ai_analyses.id')->orWhere('call_ai_analyses.status', 'none');
                });
            } else {
                $callLogs->where('call_ai_analyses.status', $ai_status);
            }
        }

        $callLogs->select([
            'call_logs.id as log_id',
            'call_logs.lead_id as lead_id',
            'leads.name as customer_name',
            DB::raw("'Campaign Calling' as source_type"),
            DB::raw(self::callMethodSortSql("{$tp}call_logs") . " as type_sort"),
            DB::raw("0 as source_code"),
            'campaigns.name as campaign_name',
            'campaigns.id as campaign_id',
            'users.name as called_by',
            'call_logs.phonenumber as phonenumber',
            DB::raw("COALESCE(NULLIF({$tp}recordings.file_path, ''), NULLIF({$tp}call_logs.recording, ''), NULLIF({$tp}recordings.recording_url, ''), NULLIF({$tp}call_logs.click_recording_url, '')) as file_name"),
            DB::raw("COALESCE(NULLIF({$tp}call_logs.duration, 0), {$tp}recordings.duration, 0) as duration"),
            DB::raw("CAST(NULL AS CHAR) as duration_text"),
            DB::raw(self::callMethodSql("{$tp}call_logs") . " as call_type"),
            'call_logs.created_at as created_at',
            'campaigns.number_masking as number_masking',
            DB::raw("COALESCE({$tp}call_ai_analyses.status, 'none') as ai_status"),
            DB::raw("{$tp}call_ai_analyses.id as ai_analysis_id"),
            DB::raw("{$tp}call_ai_analyses.error_message as ai_error"),
            DB::raw("{$tp}recordings.download_status as rec_status")
        ]);

        // 2. Manual dial calls (Dialing, dialer_logs)
        $dialerLogs = DB::table('dialer_logs')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'dialer_logs.campaign_id')
            ->join('users', 'users.id', '=', 'dialer_logs.created_by')
            ->leftJoin('recordings', 'recordings.id', '=', 'dialer_logs.recording_id')
            ->leftJoin('leads', 'leads.id', '=', DB::raw("COALESCE({$tp}dialer_logs.lead_id, {$tp}recordings.lead_id)"))
            ->leftJoin('call_ai_analyses', function($join) use ($tp) {
                $join->on('call_ai_analyses.recording_id', '=', DB::raw("CONCAT('dialer_', {$tp}dialer_logs.id)"));
            })
            ->where('dialer_logs.call_source', 'manual')
            ->where(function($q) {
                $q->where(function($sq) {
                    $sq->whereNotNull('dialer_logs.file_path')->where('dialer_logs.file_path', '!=', '');
                })->orWhere(function($sq) {
                    $sq->whereNotNull('dialer_logs.click_recording_url')->where('dialer_logs.click_recording_url', '!=', '');
                })->orWhereNotNull('dialer_logs.recording_id');
            })
            // Recordings deleted by an Admin are hidden (the call itself stays in its logs)
            ->where(function($q) {
                $q->whereNull('recordings.id')->orWhere('recordings.download_status', '!=', 'deleted');
            });

        if (!empty($userTeamIds) && !in_array($userRole, ['Super Admin', 'Admin', 'Webmaster'])) {
            $dialerLogs->where(function($q) use ($userTeamIds) {
                $q->whereIn('campaigns.team_id', $userTeamIds)->orWhereNull('dialer_logs.campaign_id');
            });
        }

        if (in_array($userRole, ['Telecaller', 'Sales Person'])) {
            $dialerLogs->where('dialer_logs.created_by', $userId);
        }

        foreach ($searchTerms as $q) {
            $dialerLogs->where(function ($query) use ($q) {
                $query->where('dialer_logs.id', '=', $q)
                    ->orWhere('leads.id', '=', $q)
                    ->orWhere('dialer_logs.phone_number', 'like', '%' . $q . '%')
                    ->orWhere('users.name', 'like', '%' . $q . '%')
                    ->orWhere('leads.name', 'like', '%' . $q . '%')
                    ->orWhere('campaigns.name', 'like', '%' . $q . '%')
                    ->orWhere('dialer_logs.file_path', 'like', '%' . $q . '%')
                    ->orWhere('dialer_logs.click_recording_url', 'like', '%' . $q . '%')
                    ->orWhere('recordings.file_path', 'like', '%' . $q . '%')
                    ->orWhere(fn ($m) => $m->whereNull('dialer_logs.campaign_id')->whereRaw("'manual dial' like ?", ['%' . strtolower($q) . '%']));
            });
        }

        if (!empty($find_campaign)) {
            $dialerLogs->where('dialer_logs.campaign_id', $find_campaign);
        }
        if (!empty($telecaller_id)) {
            $telecallerIds = is_array($telecaller_id) ? $telecaller_id : explode(',', (string)$telecaller_id);
            $telecallerIds = array_filter(array_map('intval', $telecallerIds));
            if (!empty($telecallerIds)) {
                $dialerLogs->whereIn('dialer_logs.created_by', $telecallerIds);
            }
        }
        if (!empty($from_date) && !empty($to_date)) {
            $dialerLogs->whereBetween('dialer_logs.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
        }
        if (!empty($ai_status)) {
            if ($ai_status == 'none' || $ai_status == 'No Analysis') {
                $dialerLogs->where(function($sq) {
                    $sq->whereNull('call_ai_analyses.id')->orWhere('call_ai_analyses.status', 'none');
                });
            } else {
                $dialerLogs->where('call_ai_analyses.status', $ai_status);
            }
        }

        $dialerLogs->select([
            'dialer_logs.id as log_id',
            'leads.id as lead_id',
            DB::raw("COALESCE({$tp}leads.name, '—') as customer_name"),
            DB::raw("'Dialing' as source_type"),
            DB::raw(self::callMethodSortSql("{$tp}dialer_logs") . " as type_sort"),
            DB::raw("2 as source_code"),
            DB::raw("COALESCE({$tp}campaigns.name, 'Manual Dial') as campaign_name"),
            'campaigns.id as campaign_id',
            'users.name as called_by',
            'dialer_logs.phone_number as phonenumber',
            DB::raw("COALESCE(NULLIF({$tp}recordings.file_path, ''), NULLIF({$tp}dialer_logs.file_path, ''), NULLIF({$tp}recordings.recording_url, ''), NULLIF({$tp}dialer_logs.click_recording_url, '')) as file_name"),
            // time_taken is the talk time in seconds; dialer_logs.duration is an "mm:ss" string (used only as fallback in PHP)
            DB::raw("COALESCE(NULLIF({$tp}dialer_logs.time_taken, 0), {$tp}recordings.duration, 0) as duration"),
            DB::raw("{$tp}dialer_logs.duration as duration_text"),
            DB::raw(self::callMethodSql("{$tp}dialer_logs") . " as call_type"),
            'dialer_logs.created_at as created_at',
            DB::raw("COALESCE({$tp}campaigns.number_masking, 0) as number_masking"),
            DB::raw("COALESCE({$tp}call_ai_analyses.status, 'none') as ai_status"),
            DB::raw("{$tp}call_ai_analyses.id as ai_analysis_id"),
            DB::raw("{$tp}call_ai_analyses.error_message as ai_error"),
            DB::raw("{$tp}recordings.download_status as rec_status")
        ]);

        // 3. Standalone Recordings (Direct Telephony)
        $orphanRecordings = DB::table('recordings')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'recordings.campaign_id')
            ->leftJoin('users', 'users.id', '=', 'recordings.user_id')
            ->leftJoin('leads', 'leads.id', '=', 'recordings.lead_id')
            ->leftJoin('call_ai_analyses', function($join) use ($tp) {
                $join->on('call_ai_analyses.recording_id', '=', DB::raw("CONCAT('rec_', {$tp}recordings.id)"));
            })
            ->whereNull('recordings.dialer_log_id')
            ->whereNull('recordings.call_log_id')
            ->where('recordings.download_status', '!=', 'deleted')
            ->where(function($q) {
                $q->whereNotNull('recordings.file_path')->orWhereNotNull('recordings.recording_url');
            });

        if (!empty($userTeamIds) && !in_array($userRole, ['Super Admin', 'Admin', 'Webmaster'])) {
            $orphanRecordings->where(function($q) use ($userTeamIds) {
                $q->whereIn('campaigns.team_id', $userTeamIds)->orWhereNull('recordings.campaign_id');
            });
        }
        if (in_array($userRole, ['Telecaller', 'Sales Person'])) {
            $orphanRecordings->where('recordings.user_id', $userId);
        }
        foreach ($searchTerms as $q) {
            $orphanRecordings->where(function ($query) use ($q) {
                $query->where('recordings.id', '=', $q)
                    ->orWhere('recordings.lead_id', '=', $q)
                    ->orWhere('recordings.phone_number', 'like', '%' . $q . '%')
                    ->orWhere('users.name', 'like', '%' . $q . '%')
                    ->orWhere('campaigns.name', 'like', '%' . $q . '%')
                    ->orWhere('recordings.file_path', 'like', '%' . $q . '%')
                    ->orWhere('recordings.recording_url', 'like', '%' . $q . '%');
            });
        }
        if (!empty($find_campaign)) {
            $orphanRecordings->where('recordings.campaign_id', $find_campaign);
        }
        if (!empty($telecaller_id)) {
            $telecallerIds = is_array($telecaller_id) ? $telecaller_id : explode(',', (string)$telecaller_id);
            $telecallerIds = array_filter(array_map('intval', $telecallerIds));
            if (!empty($telecallerIds)) {
                $orphanRecordings->whereIn('recordings.user_id', $telecallerIds);
            }
        }
        if (!empty($from_date) && !empty($to_date)) {
            $orphanRecordings->whereBetween('recordings.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
        }
        if (!empty($ai_status)) {
            if ($ai_status == 'none' || $ai_status == 'No Analysis') {
                $orphanRecordings->where(function($sq) {
                    $sq->whereNull('call_ai_analyses.id')->orWhere('call_ai_analyses.status', 'none');
                });
            } else {
                $orphanRecordings->where('call_ai_analyses.status', $ai_status);
            }
        }

        $orphanRecordings->select([
            'recordings.id as log_id',
            'recordings.lead_id as lead_id',
            'leads.name as customer_name',
            DB::raw("'Direct Telephony' as source_type"),
            DB::raw("'~' as type_sort"),   // no call record: method unknown, sorts last
            DB::raw("3 as source_code"),
            DB::raw("COALESCE({$tp}campaigns.name, '—') as campaign_name"),
            'campaigns.id as campaign_id',
            DB::raw("COALESCE({$tp}users.name, 'System') as called_by"),
            'recordings.phone_number as phonenumber',
            DB::raw("COALESCE({$tp}recordings.file_path, {$tp}recordings.recording_url) as file_name"),
            DB::raw("COALESCE({$tp}recordings.duration, 0) as duration"),
            DB::raw("CAST(NULL AS CHAR) as duration_text"),
            DB::raw("CAST(NULL AS CHAR) as call_type"),
            'recordings.created_at as created_at',
            DB::raw("COALESCE({$tp}campaigns.number_masking, 0) as number_masking"),
            DB::raw("COALESCE({$tp}call_ai_analyses.status, 'none') as ai_status"),
            DB::raw("{$tp}call_ai_analyses.id as ai_analysis_id"),
            DB::raw("{$tp}call_ai_analyses.error_message as ai_error"),
            DB::raw("{$tp}recordings.download_status as rec_status")
        ]);

        // Combine queries based on find_source filter
        if ($find_source == 'Campaign Calling') {
            $subQuery = $callLogs;
        } elseif ($find_source == 'Dialing') {
            $subQuery = $dialerLogs;
        } else {
            $combined = $callLogs->unionAll($dialerLogs)->unionAll($orphanRecordings);
            $subQuery = DB::table(DB::raw("({$combined->toSql()}) as combined_recordings"))
                ->mergeBindings($combined);
        }

        $totalData = $subQuery->count();
        $totalFiltered = $totalData;

        if ($limit == -1 || strtolower((string)$limit) === 'all') {
            $limit = $totalData > 0 ? $totalData : 10;
        }

        $records = $subQuery->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->get();

        $companySettings = \App\Models\CompanyAiSetting::company();
        $autoTranscript = $companySettings->autoTranscriptEnabled();
        $sttReady = (new \App\Services\SarvamAiService())->isConfigured();   // this company has a valid Sarvam key
        $canTranscribe = self::canRequestTranscript();
        $transcribeTip = $autoTranscript
            ? 'Transcribe this recording'
            : 'Enable AI Transcript in Company Settings for automatic transcripts';

        $canDeleteRec = \App\Helpers\Helper::canDeleteRecordings();
        $data = array();
        if ($totalFiltered > 0) {
            foreach ($records as $row) {
                $nestedData = [];
                $cbKey = "{$row->source_code}_{$row->log_id}";
                $st = strtolower($row->ai_status ?? 'none');
                $recStatus = strtolower((string)($row->rec_status ?? ''));

                // Duration in seconds: call record talk time first, recording row second, "mm:ss" text last
                $durationSec = (int)$row->duration;
                if ($durationSec <= 0 && !empty($row->duration_text)) {
                    $durationSec = \App\Helpers\Helper::durationToSeconds($row->duration_text);
                }

                // Real audio available? (never a placeholder; "awaiting" rows have no file yet)
                $fileName = (string)($row->file_name ?? '');
                $isPlaceholder = $fileName === '' || stripos($fileName, 'leadger_demo_call') !== false || str_starts_with(ltrim($fileName), '{');
                // While the local copy downloads, the provider's recording link already plays and downloads
                // (downloadRecordings redirects to it), so only a call with no recording link yet is still waiting
                $isDownloading = in_array($recStatus, ['pending', 'downloading'], true);
                $hasProviderUrl = (bool)filter_var($fileName, FILTER_VALIDATE_URL);
                $isProcessing = $recStatus === 'awaiting' || ($isDownloading && !$hasProviderUrl);
                $isUnavailable = $recStatus === 'unavailable';
                $hasAudio = !$isPlaceholder && !$isProcessing && !$isUnavailable;
                // Transcription sends the local audio file to Sarvam, so it waits for the download to finish
                $isPreparingAudio = $hasAudio && $isDownloading;
                $canTranscribeRow = $canTranscribe && $hasAudio && !$isPreparingAudio && $durationSec > 0;

                // 0. Row Checkbox (bulk "Transcribe selected")
                $nestedData['log_id'] = $row->log_id;
                $nestedData['source_code'] = $row->source_code;
                $nestedData['checkbox'] = "<div class='text-center' style='padding-top:2px;'>
                    <input type='checkbox' class='lg-recording-row-cb' value='{$cbKey}' data-key='{$cbKey}' data-id='{$row->log_id}' data-source='{$row->source_code}' data-duration='{$durationSec}' data-status='{$st}' data-caller='" . e($row->called_by) . "' " . (!$canTranscribeRow ? 'disabled title="' . ($isPreparingAudio ? 'Audio is being saved to the server; try again in a few seconds' : ($hasAudio ? 'Transcript not available for your role' : 'No recording audio yet')) . '"' : '') . " onchange='lgOnRowSelectChange()'>
                </div>";

                // 1. Lead ID (the call's lead; calls without a lead show a dash, never the recording's own id)
                if (!empty($row->lead_id)) {
                    $leadTip = 'View lead ' . $row->lead_id . (!empty($row->customer_name) ? ' (' . e($row->customer_name) . ')' : '');
                    $nestedData['id'] = "<a href='javascript:void(0)' onclick='leadDetail(\"{$row->lead_id}\")' class='lg-lead-id-pill' title='{$leadTip}'><span>{$row->lead_id}</span></a>";
                } else {
                    $nestedData['id'] = "<span class='text-muted' title='No lead is linked to this call'>—</span>";
                }

                // 2. Type: how the call was placed (Click2Call / WebRTC ...), for campaign and manual calls alike
                [$methodLabel, $methodClass] = self::callMethod($row->call_type);
                $nestedData['source_type'] = $methodLabel === null
                    ? "<span class='lg-source-badge other' title='The calling method was not recorded for this call'>—</span>"
                    : "<span class='lg-source-badge {$methodClass}'>" . e($methodLabel) . "</span>";

                // 3. Campaign Name
                $campName = !empty($row->campaign_name) ? e($row->campaign_name) : '—';
                $nestedData['campaign_name'] = "<span class='lg-campaign-text' title='{$campName}'>{$campName}</span>";

                // 4. Employee Name
                $caller = !empty($row->called_by) ? e($row->called_by) : 'Unassigned';
                $nestedData['called_by'] = $caller;

                // 5. Mobile
                $maskedPhone = \App\Helpers\Helper::number_masking($row->phonenumber, $row->number_masking ?? 0);
                $nestedData['phonenumber'] = $maskedPhone;

                // 6. File Name (real file, or the recording's processing state)
                // Recording status: Waiting for recording (provider has not delivered / file still downloading) ->
                // Ready (the audio file exists; playable and downloadable) | Not available (provider never delivered)
                if ($isProcessing || ($isPlaceholder && !$isUnavailable)) {
                    $waitTip = $recStatus === 'awaiting' ? 'The call provider has not delivered the recording yet' : 'The recording is being downloaded from the call provider';
                    $nestedData['recording'] = "<span class='lg-status-badge blue' title='{$waitTip}'><i class='fas fa-spinner fa-spin' style='font-size:10px;'></i> Waiting for recording</span>";
                } elseif ($isUnavailable) {
                    $nestedData['recording'] = "<span class='lg-status-badge gray' title='The call provider did not deliver a recording for this call'>Not available</span>";
                } else {
                    $baseName = e(basename(parse_url($fileName, PHP_URL_PATH) ?: $fileName));
                    $nestedData['recording'] = "<span class='lg-status-badge green' title='Recording ready to play and download' style='margin-right:6px;'><span class='dot'></span>Ready</span><span title='{$baseName}'>{$baseName}</span>";
                }

                // 7. Duration (seconds + formatted; the JS column renders duration_formatted)
                $nestedData['duration'] = $durationSec;
                $nestedData['duration_seconds'] = $durationSec;
                $nestedData['duration_formatted'] = \App\Helpers\Helper::formatDuration($durationSec);

                // 8. Created Date
                $nestedData['created_at'] = date('d-M-Y h:i A', strtotime($row->created_at));

                // 9. AI Analysis column: status + one-click Transcribe / Retry
                $rowArgs = "{$row->log_id}, {$row->source_code}";
                $aiError = e(\Illuminate\Support\Str::limit((string)($row->ai_error ?? ''), 160));
                if ($st === 'completed') {
                    $ai = "<span class='lg-status-badge green' role='button' style='cursor:pointer;' onclick='openTranscriptModal({$rowArgs})' title='View transcript'><span class='dot'></span>Completed</span>";
                } elseif ($st === 'processing') {
                    $ai = "<span class='lg-status-badge blue' title='Sarvam AI is converting the call audio to text'><i class='fas fa-spinner fa-spin' style='font-size:10px;'></i> Transcribing</span>";
                } elseif ($st === 'waiting_key') {
                    // the company's Sarvam key is missing / invalid: queued transcripts wait for a valid key
                    $ai = "<span class='lg-status-badge amber' title='" . e($sttReady ? 'Resuming with the new key' : \App\Services\SarvamKeyService::notSetUpMessage()) . "'><span class='dot'></span>Waiting for API key</span>";
                } elseif ($st === 'pending' || $st === 'queued') {
                    $ai = "<span class='lg-status-badge amber' title='Waiting for the transcription worker'><span class='dot'></span>Pending</span>";
                } elseif ($st === 'failed') {
                    $ai = "<div><span class='lg-status-badge gray text-danger' title='{$aiError}'><span class='dot' style='background:#EF4444;'></span>Transcription failed</span>";
                    if ($aiError !== '') {
                        $ai .= "<div class='lg-ai-error' title='{$aiError}'>{$aiError}</div>";
                    }
                    if ($canTranscribeRow) {
                        $ai .= "<button type='button' class='lg-transcribe-btn retry' onclick='lgTranscribeOne({$rowArgs}, this)' title='Run the transcript again'><i class='fas fa-redo-alt'></i> Retry</button>";
                    }
                    $ai .= "</div>";
                } elseif ($isPreparingAudio && $canTranscribe) {
                    $ai = "<span class='lg-status-badge blue' title='The recording is being saved to the server; Transcribe is available in a few seconds'><i class='fas fa-spinner fa-spin' style='font-size:10px;'></i> Preparing audio</span>";
                } elseif ($canTranscribeRow && !$sttReady) {
                    $ai = "<span class='lg-status-badge gray' title='" . e(\App\Services\SarvamKeyService::notSetUpMessage()) . "'><span class='dot'></span>Not set up</span>";
                } elseif ($canTranscribeRow) {
                    $ai = "<button type='button' class='lg-transcribe-btn' onclick='lgTranscribeOne({$rowArgs}, this)' title='" . e($transcribeTip) . "' data-toggle='tooltip'><i class='fas fa-file-alt'></i> Transcribe</button>";
                } elseif (!$hasAudio) {
                    $ai = "<span class='lg-status-badge gray' title='Transcription starts once the recording audio has arrived'><span class='dot'></span>Waiting for recording</span>";
                } else {
                    $ai = "<span class='lg-status-badge gray'><span class='dot'></span>" . ($autoTranscript ? 'Not transcribed' : 'Not requested') . "</span>";
                }
                $nestedData['ai_analysis'] = "<div class='lg-ai-cell' data-key='{$cbKey}'>{$ai}</div>";

                // 10. Actions Column
                $downloadRoute = route('downloadRecordings', [$row->log_id, $row->source_code]);
                $parsedDownload = parse_url($downloadRoute);
                $audioPath = $parsedDownload['path'] ?? ('/admin/recordings/download/' . $row->log_id . '/' . $row->source_code);

                // Download / Play buttons: Download / Play (shared player) on the protected
                // downloadRecordings route; Delete only for an Admin with Delete Permission (rechecked on the server).
                $attrs = "data-id='{$row->log_id}' data-source='{$row->source_code}' data-url='" . e($audioPath) . "'"
                    . " data-agent='" . e($row->called_by) . "' data-number='" . e($maskedPhone) . "'"
                    . " data-date='" . e(date('d-M-Y h:i A', strtotime($row->created_at))) . "'"
                    . " data-duration='" . e(\App\Helpers\Helper::formatDuration($durationSec)) . "'";
                $off = $hasAudio ? '' : " disabled style='opacity:.45;cursor:not-allowed;' title='Recording not available yet'";
                $actions = "<div class='lg-dl-actions'>"
                    . "<button type='button' class='lg-dl-act lg-dl-act-download' {$attrs} onclick='lgDlDownload(this)'" . ($off ?: " title='Download recording'") . " aria-label='Download recording'><i class='fas fa-download'></i></button>"
                    . "<button type='button' class='lg-dl-act lg-dl-act-play' {$attrs} onclick='lgDlPlay(this)'" . ($off ?: " title='Play recording'") . " aria-label='Play recording'><i class='fas fa-play'></i></button>";

                // View Transcript: the transcript when completed, otherwise its live status (Pending / Transcribing / failed + Retry)
                if ($st === 'completed' || $hasAudio || in_array($st, ['pending', 'queued', 'processing', 'failed', 'waiting_key'], true)) {
                    $done = $st === 'completed';
                    $actions .= "<button type='button' class='lg-dl-act' onclick='openTranscriptModal({$rowArgs})' title='View transcript' aria-label='View transcript' style='" . ($done ? 'color:#059669;border-color:#A7F3D0;' : 'color:#64748B;') . "'><i class='fas fa-file-alt'></i></button>";
                }

                // AI Analysis detail page (if completed)
                if (!empty($row->ai_analysis_id) && $st === 'completed' && $companySettings->ai_enabled) {
                    $detailPath = parse_url(route('aiAnalysisDetail', $row->ai_analysis_id), PHP_URL_PATH);
                    $actions .= "<a href='{$detailPath}' class='lg-dl-act' title='View AI analysis' aria-label='View AI analysis' style='color:#7C3AED;border-color:#DDD6FE;text-decoration:none;'><i class='fas fa-brain'></i></a>";
                }

                if ($canDeleteRec) {
                    $actions .= "<button type='button' class='lg-dl-act lg-dl-act-delete' onclick='deleteRecording({$row->log_id}, {$row->source_code})' title='Delete recording' aria-label='Delete recording'><i class='fas fa-trash-alt'></i></button>";
                }

                $actions .= "</div>";

                $nestedData['play'] = $actions;

                $data[] = $nestedData;
            }
        }
        
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "statics" => []
        );
        return response()->json($json_data);
    }

    /**
     * Calling method stored on the call record (call_logs / dialer_logs.call_type, written when the call is placed).
     * Legacy rows without it: a WebRTC call id means WebRTC, a Click2Call provider response means Click2Call.
     */
    private static function callMethodSql(string $table): string
    {
        return "COALESCE(NULLIF({$table}.call_type, ''), CASE WHEN {$table}.webrtc_call_id IS NOT NULL AND {$table}.webrtc_call_id <> '' THEN 'webrtc'"
            . " WHEN {$table}.click_response IS NOT NULL AND {$table}.click_response <> '' THEN 'click2call' END)";
    }

    /** Sort key for the Type column: the displayed label (unknown methods last). */
    private static function callMethodSortSql(string $table): string
    {
        $m = self::callMethodSql($table);
        return "CASE WHEN {$m} LIKE '%webrtc%' THEN 'WebRTC' WHEN {$m} IN ('click2call', 'c2c', 'flexidial') THEN 'Click2Call' ELSE COALESCE({$m}, '~') END";
    }

    /**
     * [label, badge class] for a stored call_type. FlexiDial dials through Click2Call; every WebRTC variant
     * (webrtc, webrtc-flexi, webrtc_flexidial) is WebRTC. Null when the method is not recorded.
     */
    public static function callMethod(?string $callType): array
    {
        $v = strtolower(trim((string)$callType));
        if ($v === '') return [null, 'other'];
        if (str_contains($v, 'webrtc')) return ['WebRTC', 'webrtc'];
        if (in_array($v, ['click2call', 'c2c', 'flexidial'], true)) return ['Click2Call', 'click2call'];
        return [['sip' => 'SIP', 'phone' => 'Mobile'][$v] ?? ucfirst($v), 'other'];
    }

    /**
     * Admins and managers may run a transcript manually (even when automatic AI Transcript is off).
     * Everyone else sees transcript status read-only.
     */
    public static function canRequestTranscript(): bool
    {
        $role = Auth::user()->permissionsGroup->name ?? '';
        return in_array($role, ['Super Admin', 'Admin', 'Webmaster', 'Manager'], true);
    }

    /**
     * Same visibility rules as the recordings list. Returns an error message, or null when allowed.
     */
    private function rowAccessError(int $sourceCode, int $logId): ?string
    {
        $user = Auth::user();
        $userRole = $user->permissionsGroup->name ?? '';
        $userTeamIds = array_filter(array_map('intval', explode(',', $user->team_id ?? '')));
        $userId = $user->id;
        $isAdmin = in_array($userRole, ['Super Admin', 'Admin', 'Webmaster'], true);

        if ($sourceCode == 0) {
            $row = DB::table('call_logs')
                ->leftJoin('leads', 'leads.id', '=', 'call_logs.lead_id')
                ->leftJoin('campaigns', 'campaigns.id', '=', DB::raw('COALESCE(' . DB::getTablePrefix() . 'call_logs.campaign_id, ' . DB::getTablePrefix() . 'leads.campaign_id)'))
                ->where('call_logs.id', $logId)
                ->select(['call_logs.created_by', 'leads.assigned_to', 'leads.sales_person_id', 'leads.is_public', 'campaigns.team_id as campaign_team_id'])
                ->first();
            if (!$row) return 'Recording call log not found.';
            if (!$isAdmin && !empty($userTeamIds) && !empty($row->campaign_team_id) && !in_array((int)$row->campaign_team_id, $userTeamIds)) {
                return 'Unauthorized access to this recording.';
            }
            if ($userRole == 'Telecaller' && $row->assigned_to != $userId && $row->is_public != '1' && $row->created_by != $userId) {
                return 'Unauthorized access to this recording.';
            }
            if ($userRole == 'Sales Person' && $row->sales_person_id != $userId && $row->is_public != '1' && $row->created_by != $userId) {
                return 'Unauthorized access to this recording.';
            }
            return null;
        }

        if ($sourceCode == 2) {
            $row = DB::table('dialer_logs')
                ->leftJoin('campaigns', 'campaigns.id', '=', 'dialer_logs.campaign_id')
                ->where('dialer_logs.id', $logId)
                ->select(['dialer_logs.created_by', 'campaigns.team_id as campaign_team_id'])
                ->first();
            if (!$row) return 'Dialing log record not found.';
        } else {
            $row = DB::table('recordings')
                ->leftJoin('campaigns', 'campaigns.id', '=', 'recordings.campaign_id')
                ->where('recordings.id', $logId)
                ->select(['recordings.user_id as created_by', 'campaigns.team_id as campaign_team_id'])
                ->first();
            if (!$row) return 'Recording not found.';
        }
        if (!$isAdmin && !empty($userTeamIds) && !empty($row->campaign_team_id) && !in_array((int)$row->campaign_team_id, $userTeamIds)) {
            return 'Unauthorized access to this recording.';
        }
        if (in_array($userRole, ['Telecaller', 'Sales Person']) && $row->created_by != $userId) {
            return 'Unauthorized access to this recording.';
        }
        return null;
    }

    /**
     * GET transcript popup data: call details + transcript / status for one recording row.
     */
    public function getTranscript($id, $source_code)
    {
        if (!Auth::user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $logId = (int)$id;
        $sourceCode = (int)$source_code;

        if ($err = $this->rowAccessError($sourceCode, $logId)) {
            return response()->json(['status' => 'error', 'message' => $err], str_starts_with($err, 'Unauthorized') ? 403 : 404);
        }

        $service = new \App\Services\TranscriptionBillingService();
        $rec = $service->getRecordingDetails($sourceCode, $logId);
        if (!$rec) {
            return response()->json(['status' => 'error', 'message' => 'Recording not found.'], 404);
        }

        $meta = [
            'log_id' => $logId,
            'source_code' => $sourceCode,
            'source_type' => $rec['source_type'],
            'customer_name' => $rec['customer_name'],
            'agent_name' => $rec['agent_name'],
            'campaign_name' => $rec['campaign_name'],
            'phonenumber' => $rec['phonenumber'],
            'duration' => $rec['duration'],
            'duration_formatted' => \App\Helpers\Helper::formatDuration($rec['duration']),
            'created_at' => $rec['created_at'] ? date('Y-m-d H:i:s', strtotime($rec['created_at'])) : '',
            'file_name' => $rec['audio_path'] ? basename($rec['audio_path']) : '',
            // Same-origin relative link (like the row player): an absolute URL built from a different host
            // (APP_URL 192.168.0.128 vs. localhost) would not carry the login cookie and the audio would not load.
            'audio_url' => $rec['audio_path'] ? parse_url(route('downloadRecordings', [$logId, $sourceCode]), PHP_URL_PATH) . '?play=1' : null,
            'can_transcribe' => self::canRequestTranscript() && !$rec['audio_issue'],
            'audio_issue' => $rec['audio_issue'],
        ];

        $analysis = \App\Models\CallAiAnalysis::findFor($sourceCode, $logId);

        // Still running: queued or processing, and for how long. A job whose queue worker stopped is reported as
        // failed (with Retry) instead of "processing" forever.
        $progress = null;
        if ($analysis && in_array($analysis->status, ['pending', 'processing'], true)) {
            $job = \App\Models\TranscriptionJob::where('call_id', $logId)->where('source_code', $sourceCode)->latest('id')->first();
            if ($job && $job->isStalled()) {
                $analysis->status = 'failed';
                $analysis->error_message = 'Transcription stopped because the background worker was restarted. Press Retry to run it again.';
            } elseif ($job) {
                $progress = [
                    'state' => $job->status === 'PROCESSING' ? 'processing' : 'queued',
                    'seconds' => max(0, now()->getTimestamp() - $job->created_at->getTimestamp()),
                    'duration' => (int)$job->duration_seconds,
                ];
            }
        }

        if (!$analysis || $analysis->status !== 'completed') {
            return response()->json([
                'status' => $analysis ? $analysis->status : 'none',
                'message' => $analysis && $analysis->status === 'failed'
                    ? ($analysis->error_message ?: 'Transcription failed.')
                    : ($analysis && $analysis->status === 'waiting_key' ? 'Waiting for a valid Sarvam API key. ' . \App\Services\SarvamKeyService::notSetUpMessage()
                    : ($analysis ? 'Transcript is being processed.'
                    : ((new \App\Services\SarvamAiService())->isConfigured() ? 'This recording has not been transcribed yet.' : \App\Services\SarvamKeyService::notSetUpMessage()))),
                'meta' => $meta,
                'progress' => $progress,
                'analysis' => [
                    'status' => $analysis ? $analysis->status : 'none',
                    'error_message' => $analysis ? $analysis->error_message : null,
                ]
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'meta' => $meta,
            'analysis' => [
                'id' => $analysis->id,
                'status' => $analysis->status,
                'error_message' => $analysis->error_message,
                'sarvam_request_id' => $analysis->sarvam_request_id,
                'transcript' => $analysis->transcript,
                // English conversation, speaker by speaker (null for transcripts made before it existed)
                'conversation' => $analysis->conversation,
                'transcript_english' => $analysis->transcript_english,
                'speaker_roles' => $analysis->speaker_roles,
                'source_language' => $analysis->source_language,
                'analysis_source' => $analysis->analysis_source,
                'summary' => $analysis->summary,
                'interest_level' => $analysis->interest_level,
                'sentiment' => $analysis->sentiment,
                'intent' => $analysis->intent,
                'key_points' => $analysis->key_points,
                'next_action' => $analysis->next_action,
                'auto_disposition' => $analysis->auto_disposition,
                'follow_up_date' => $analysis->follow_up_date ? date('Y-m-d H:i', strtotime($analysis->follow_up_date)) : null,
                'emotion_analysis' => $analysis->emotion_analysis,
                'quality_flags' => $analysis->quality_flags,
                'ai_cost' => $analysis->ai_cost,
            ]
        ]);
    }

    /**
     * Who is the agent in a transcript's conversation, as confirmed by a user listening to the call
     * (a mono recording carries no reliable speaker identity, so this is never guessed).
     * agent = "S1" | "S2" | "" (clear). Same access rules as viewing the transcript.
     */
    public function saveSpeakerRoles(Request $request, $id, $source_code)
    {
        $logId = (int)$id;
        $sourceCode = (int)$source_code;
        if ($err = $this->rowAccessError($sourceCode, $logId)) {
            return response()->json(['success' => false, 'message' => $err], str_starts_with($err, 'Unauthorized') ? 403 : 404);
        }
        $analysis = \App\Models\CallAiAnalysis::findFor($sourceCode, $logId);
        if (!$analysis || empty($analysis->conversation)) {
            return response()->json(['success' => false, 'message' => 'This recording has no speaker-wise conversation yet.'], 422);
        }
        $speakers = array_values(array_unique(array_column($analysis->conversation, 'speaker')));
        $agent = (string)$request->input('agent', '');
        if ($agent !== '' && !in_array($agent, $speakers, true)) {
            return response()->json(['success' => false, 'message' => 'Unknown speaker.'], 422);
        }
        if ($agent === '') {
            $analysis->speaker_roles = null;
        } else {
            $roles = [];
            foreach ($speakers as $s) {
                // with exactly two speakers the other one is the customer; with more, only the agent is named
                $roles[$s] = $s === $agent ? 'agent' : (count($speakers) === 2 ? 'customer' : null);
            }
            $analysis->speaker_roles = array_filter($roles);
        }
        $analysis->save();
        Log::info("[Transcript] Speaker roles for {$analysis->recording_id} set by user #" . Auth::id() . ': ' . json_encode($analysis->speaker_roles));
        return response()->json(['success' => true, 'speaker_roles' => $analysis->speaker_roles]);
    }

    /**
     * POST /reports/recordings/transcription/estimate
     * Cost preview for the bulk "Transcribe selected" confirmation.
     */
    public function estimateTranscriptionCost(Request $request)
    {
        if (!Auth::user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }
        if (!self::canRequestTranscript()) {
            return response()->json(['status' => 'error', 'message' => 'Only admins and managers can request transcripts.'], 403);
        }

        $recordingKeys = \App\Services\TranscriptionBillingService::normalizeKeys($request->input('recordings', $request->input('recording_ids', [])));
        if (empty($recordingKeys)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one call recording to transcribe.'], 400);
        }

        $service = new \App\Services\TranscriptionBillingService();
        $estimate = $service->calculateEstimate($recordingKeys, 1);

        if ($estimate['recording_count'] === 0) {
            return response()->json([
                'status' => 'error',
                'message' => $estimate['skipped'][0]['reason'] ?? 'None of the selected recordings can be transcribed.',
            ], 422);
        }

        return response()->json(['status' => 'success', 'data' => $estimate] + $estimate);
    }

    /**
     * POST /reports/recordings/transcription/jobs
     * Queue transcripts for one or more recordings. Returns at once with a batch id;
     * the page polls getBatchStatus until the queue worker finishes.
     */
    public function startTranscriptionJobs(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }
        if (!self::canRequestTranscript()) {
            return response()->json(['status' => 'error', 'message' => 'Only admins and managers can request transcripts.'], 403);
        }

        $recordingKeys = \App\Services\TranscriptionBillingService::normalizeKeys($request->input('recordings', $request->input('recording_ids', [])));
        if (empty($recordingKeys)) {
            return response()->json(['status' => 'error', 'message' => 'No recordings specified for transcription.'], 400);
        }

        foreach ($recordingKeys as $key) {
            [$sc, $lid] = array_map('intval', explode('_', $key));
            if ($err = $this->rowAccessError($sc, $lid)) {
                return response()->json(['status' => 'error', 'message' => $err], 403);
            }
        }

        $service = new \App\Services\TranscriptionBillingService();

        try {
            $batch = $service->createTranscriptionJobs($recordingKeys, 1, $user->id, true);
            $service->dispatchBatch($batch['batch_id']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $status = $service->getBatchStatus($batch['batch_id']);

        return response()->json([
            'status' => 'success',
            'data' => $status,
            'batch_id' => $batch['batch_id'],
            'total' => $status['total'],
            'skipped' => $batch['skipped'],
            'is_finished' => $status['is_finished'],
            'message' => "Transcript started for {$status['total']} recording(s)."
                . (count($batch['skipped']) ? ' ' . count($batch['skipped']) . ' skipped: ' . $batch['skipped'][0]['reason'] : ''),
        ]);
    }

    /**
     * GET /reports/recordings/transcription/status/{batch_id}
     * Polling endpoint for batch job completion.
     */
    public function getBatchStatus($batchId)
    {
        if (!Auth::user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $service = new \App\Services\TranscriptionBillingService();
        $status = $service->getBatchStatus((string)$batchId);

        if ($status['status'] === 'not_found') {
            return response()->json(['status' => 'error', 'message' => 'Batch not found.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $status] + $status);
    }

    /**
     * POST retry: queue the transcript again for one recording.
     */
    public function retryTranscript(Request $request, $id, $source_code)
    {
        $request->merge(['recordings' => [(int)$source_code . '_' . (int)$id]]);
        return $this->startTranscriptionJobs($request);
    }

    /**
     * Delete ONE call recording. Allowed only for the Admin role AND when Delete Permission is enabled
     * in Company Settings; otherwise 403 (also when the URL is called directly).
     * The call log / dialer log, lead and reports are kept - only the audio and its reference are removed.
     */
    public function destroy($id, $source_code = 0)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (!\App\Helpers\Helper::canDeleteRecordings()) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to delete recordings. Only the Admin can, and only when Delete Permission is enabled in Company Settings.'], 403);
        }

        $sourceCode = (int)$source_code;
        if (!in_array($sourceCode, [0, 2, 3], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid recording.'], 422);
        }

        $result = \App\Services\CallRecordingService::deleteRecording($sourceCode, (int)$id, $user->id);
        return response()->json(['success' => $result['ok'], 'message' => $result['message']], $result['code']);
    }

    /**
     * Bulk delete call recordings (same rules as destroy, applied to each selected recording).
     */
    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (!\App\Helpers\Helper::canDeleteRecordings()) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to delete recordings. Only the Admin can, and only when Delete Permission is enabled in Company Settings.'], 403);
        }

        $deleted = 0;
        $failed = [];
        foreach ((array)$request->input('items', []) as $item) {
            $id = (int)($item['id'] ?? 0);
            $sourceCode = (int)($item['source_code'] ?? 0);
            if ($id <= 0 || !in_array($sourceCode, [0, 2, 3], true)) {
                continue;
            }
            $result = \App\Services\CallRecordingService::deleteRecording($sourceCode, $id, $user->id);
            $result['ok'] ? $deleted++ : ($failed[] = "#{$id}: {$result['message']}");
        }

        return response()->json([
            'success' => $deleted > 0 && empty($failed),
            'deleted' => $deleted,
            'failed' => $failed,
            'message' => "{$deleted} recording(s) deleted." . ($failed ? ' ' . count($failed) . ' could not be deleted: ' . $failed[0] : ''),
        ], $deleted > 0 || empty($failed) ? 200 : 500);
    }

    /** Temporary bulk-download ZIPs live here until they are sent (or for at most an hour). */
    private const ZIP_DIR = 'app/tmp/recording-zips';

    /**
     * Bulk download, step 1: put the selected recordings (same visibility rules as the list) into one ZIP.
     * Files keep their original names and formats; recordings are never changed or deleted.
     * Returns a one-time token for step 2 and the list of recordings that could not be included.
     */
    public function bulkDownload(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (!class_exists(\ZipArchive::class)) {
            return response()->json(['success' => false, 'message' => 'ZIP support (PHP zip extension) is not available on the server.'], 500);
        }

        $items = [];
        foreach ((array)$request->input('items', []) as $item) {
            $id = (int)($item['id'] ?? 0);
            $sourceCode = (int)($item['source_code'] ?? 0);
            if ($id > 0 && in_array($sourceCode, [0, 2, 3], true)) {
                $items[$sourceCode . '_' . $id] = [$sourceCode, $id];
            }
        }
        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Please select at least one recording to download.'], 422);
        }
        if (count($items) > 500) {
            return response()->json(['success' => false, 'message' => 'Please select at most 500 recordings per download.'], 422);
        }

        $dir = \App\Support\Tenancy::path(storage_path(self::ZIP_DIR));
        File::ensureDirectoryExists($dir);
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $old) {
            if (@filemtime($old) < time() - 3600) {
                @unlink($old);   // left over from a download that was never collected
            }
        }

        $token = bin2hex(random_bytes(20));
        $zipPath = $dir . DIRECTORY_SEPARATOR . $token . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['success' => false, 'message' => 'The ZIP file could not be created.'], 500);
        }

        $added = 0;
        $names = [];
        $missing = [];
        $denied = 0;
        foreach ($items as [$sourceCode, $id]) {
            $accessError = $this->rowAccessError($sourceCode, $id);
            if ($accessError !== null) {
                if (str_starts_with($accessError, 'Unauthorized')) {
                    $denied++;
                } else {
                    $missing[] = "#{$id}: " . rtrim(lcfirst($accessError), '.');   // row does not exist
                }
                continue;
            }
            $audio = $this->recordingAudioForZip($sourceCode, $id);
            if (!empty($audio['error'])) {
                $missing[] = $audio['label'] . ': ' . $audio['error'];
                continue;
            }
            $name = $this->uniqueZipName($audio['name'], $names);
            $ok = isset($audio['path'])
                ? $zip->addFile($audio['path'], $name)
                : $zip->addFromString($name, $audio['contents']);
            if ($ok) {
                $zip->setCompressionName($name, \ZipArchive::CM_STORE);   // audio is already compressed
                $names[strtolower($name)] = true;
                $added++;
            } else {
                $missing[] = $audio['label'] . ': could not be added to the ZIP';
            }
        }
        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            $message = $denied && !$missing
                ? 'You do not have access to the selected recordings.'
                : 'None of the selected recordings could be downloaded. ' . implode(' ', array_slice($missing, 0, 3));
            return response()->json(['success' => false, 'message' => trim($message), 'missing' => $missing, 'denied' => $denied], $denied && !$missing ? 403 : 404);
        }

        session()->put('recording_zips.' . $token, [
            'user_id' => $user->id,
            'file' => $zipPath,
            'name' => 'LeadGER_Recordings_' . now()->format('Y-m-d') . '.zip',
        ]);

        $skipped = count($missing) + $denied;
        Log::info("AUDIT LOG: User ID {$user->id} prepared a bulk download of {$added} call recording(s)" . ($skipped ? ", {$skipped} skipped" : ''));
        return response()->json([
            'success' => true,
            'download_url' => route('recordingsBulkDownloadFile', $token),
            'added' => $added,
            'missing' => $missing,
            'denied' => $denied,
            'message' => "{$added} recording(s) added to the ZIP."
                . ($missing ? ' ' . count($missing) . ' could not be included: ' . implode('; ', array_slice($missing, 0, 3)) . (count($missing) > 3 ? '; ...' : '') . '.' : '')
                . ($denied ? " {$denied} skipped (no access)." : ''),
        ]);
    }

    /**
     * Bulk download, step 2: send the prepared ZIP once, only to the user who prepared it, then delete it.
     */
    public function bulkDownloadFile($token)
    {
        $user = Auth::user();
        if (!$user || !is_string($token) || !preg_match('/^[a-f0-9]{40}$/', $token)) {
            abort(404);
        }
        $entry = session()->pull('recording_zips.' . $token);
        if (!$entry || (int)$entry['user_id'] !== (int)$user->id || !is_file($entry['file'])) {
            abort(404, 'This download has expired. Please select the recordings and click Download Selected again.');
        }
        return response()->download($entry['file'], $entry['name'], ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /**
     * The audio for one recording row: a local file inside the recordings folders (found the same way the single
     * download does, including files whose stored path still points at an old install folder), or else the
     * provider's copy. Returns ['label', 'name', 'path'|'contents'] or ['label', 'error'].
     */
    private function recordingAudioForZip(int $sourceCode, int $id): array
    {
        $label = ($sourceCode == 2 ? 'Dialing' : ($sourceCode == 3 ? 'Recording' : 'Campaign call')) . " #{$id}";
        $callLog = $dialerLog = $recording = null;
        if ($sourceCode == 2) {
            $dialerLog = \App\Models\DialerLog::find($id);
            $recording = $dialerLog && $dialerLog->recording_id ? \App\Models\Recording::find($dialerLog->recording_id) : null;
            if (!$dialerLog) return ['label' => $label, 'error' => 'recording not found'];
        } elseif ($sourceCode == 3) {
            $recording = \App\Models\Recording::whereNull('call_log_id')->whereNull('dialer_log_id')->find($id);
            if (!$recording) return ['label' => $label, 'error' => 'recording not found'];
        } else {
            $callLog = CallLog::find($id);
            $recording = $callLog && $callLog->recording_id ? \App\Models\Recording::find($callLog->recording_id) : null;
            if (!$callLog) return ['label' => $label, 'error' => 'recording not found'];
        }
        if ($recording && $recording->download_status === 'deleted') {
            return ['label' => $label, 'error' => 'this recording was deleted'];
        }

        $stored = array_values(array_filter([
            $recording->file_path ?? null,
            $dialerLog->file_path ?? null,
            $callLog->file_path ?? null,
            $callLog->recording ?? null,
        ], fn ($p) => is_string($p) && trim($p) !== '' && !preg_match('#^https?://#i', $p)));
        $details = (new \App\Services\TranscriptionBillingService())->getRecordingDetails($sourceCode, $id);

        // Only real audio inside the recordings folders is ever read (no path from the database can reach other files)
        $candidates = array_filter([$details['audio_path'] ?? null]);
        $uploadBase = rtrim((string)env('recordings_upload'), '/\\');
        foreach ($stored as $p) {
            $base = basename(str_replace('\\', '/', $p));
            array_push($candidates, $p, \App\Support\Tenancy::path(storage_path('app/recordings')) . DIRECTORY_SEPARATOR . $base, $uploadBase ? $uploadBase . '/' . $base : null);
        }
        foreach (array_filter($candidates) as $candidate) {
            $real = \App\Services\CallRecordingService::realAudioPath($candidate);
            if ($real && \App\Services\CallRecordingService::isInsideRecordingsFolder($real)) {
                $real = realpath($real);
                return ['label' => $label, 'name' => basename($real), 'path' => $real];
            }
        }

        // Not on this server: fetch the provider's copy (the single download redirects to the same URL)
        $url = null;
        foreach ([$recording->recording_url ?? null, $dialerLog->click_recording_url ?? null, $callLog->click_recording_url ?? null, $callLog->recording ?? null] as $u) {
            if ($url = \App\Services\CallRecordingService::validRecordingUrl($u)) break;
        }
        if ($url) {
            try {
                // Certificate checking stays on, using the CA bundle shipped with the app when PHP has none configured
                $caBundle = public_path('ssl/cacert.pem');
                $http = \Illuminate\Support\Facades\Http::timeout(30);
                if (is_file($caBundle)) {
                    $http = $http->withOptions(['verify' => $caBundle]);
                }
                $res = $http->get($url);
                $body = $res->successful() ? $res->body() : '';
                $type = strtolower((string)$res->header('Content-Type'));
                if ($body !== '' && strlen($body) <= 200 * 1024 * 1024 && !str_contains($type, 'text/html')) {
                    $name = $stored ? basename(str_replace('\\', '/', $stored[0])) : basename((string)parse_url($url, PHP_URL_PATH));
                    return ['label' => $label, 'name' => $name ?: "recording_{$sourceCode}_{$id}.wav", 'contents' => $body];
                }
                Log::warning("[RecordingZip] {$label}: provider returned HTTP {$res->status()} for {$url}");
            } catch (\Throwable $e) {
                Log::warning("[RecordingZip] {$label}: provider download failed: " . $e->getMessage());
            }
            return ['label' => $label, 'error' => 'audio file is missing on the server and could not be fetched from the provider'];
        }
        return ['label' => $label, 'error' => 'audio file is missing'];
    }

    /** A safe ZIP entry name (original file name kept), made unique with _2, _3 ... when two files share a name. */
    private function uniqueZipName(string $name, array $taken): string
    {
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename(str_replace('\\', '/', $name)));
        $name = ltrim($name, '.') ?: 'recording.wav';
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $stem = $ext !== '' ? substr($name, 0, -(strlen($ext) + 1)) : $name;
        $candidate = $name;
        for ($i = 2; isset($taken[strtolower($candidate)]); $i++) {
            $candidate = $stem . '_' . $i . ($ext !== '' ? '.' . $ext : '');
        }
        return $candidate;
    }
}