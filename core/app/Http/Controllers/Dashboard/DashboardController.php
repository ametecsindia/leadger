<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsPage;
use App\Models\AnalyticsVisitor;
use App\Models\Contact;
use App\Models\Event;
use App\Http\Requests;
use App\Models\Section;
use App\Models\Topic;
use App\Models\Webmail;
use App\Models\WebmasterSection;
use App\Models\Lead;
use App\Models\Campaign;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Status;
use App\Models\User;
use App\Models\UserActiveUsage;
use App\Services\CallingRestrictionService;
use Carbon\Carbon;
use Auth;
use DB;
use Helper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        if (@Auth::user()->permissionsGroup->view_status) {
            //List of all Webmails
            $Webmails = Webmail::where('created_by', '=', Auth::user()->id)->orderby('id', 'desc')
                ->where('cat_id', '=', 0)->limit(4)->get();

            //List of Events
            $Events = Event::where('created_by', '=', Auth::user()->id)->where('start_date', '>=',
                date('Y-m-d 00:00:00'))->orderby('start_date', 'asc')->limit(5)->get();


            //List of all contacts
            $Contacts = Contact::where('created_by', '=', Auth::user()->id)->orderby('id', 'desc')->limit(5)->get();
        } else {
            //List of all Webmails
            $Webmails = Webmail::orderby('id', 'desc')
                ->where('cat_id', '=', 0)->limit(4)->get();

            //List of Events
            $Events = Event::where('start_date', '>=',
                date('Y-m-d 00:00:00'))->orderby('start_date', 'asc')->limit(5)->get();


            //List of all contacts
            $Contacts = Contact::orderby('id', 'desc')->limit(5)->get();
        }
        // Analytics
        $TodayVisitors = AnalyticsVisitor::where('date', date('Y-m-d'))->count();
        $TodayPages = AnalyticsPage::where('date', date('Y-m-d'))->count();

        // Last 7 Days
        $daterangepicker_start = date('Y-m-d', strtotime('-6 day'));
        $daterangepicker_end = date('Y-m-d');
        $stat = "date";

        $Last7DaysVisitors = array();

        $AnalyticsVisitors = AnalyticsVisitor::select("*")->select($stat)->where('date', '>=', $daterangepicker_start)
            ->where('date', '<=', $daterangepicker_end)
            ->groupBy($stat)
            ->orderBy($stat, 'asc')
            ->get();
        $ix = 0;
        foreach ($AnalyticsVisitors as $AnalyticsV) {

            $TotalV = AnalyticsVisitor::where("$stat", $AnalyticsV->$stat)
                ->where('date', '>=', $daterangepicker_start)
                ->where('date', '<=', $daterangepicker_end)->count();

            $AllVArray = AnalyticsVisitor::select('id')->where("$stat", $AnalyticsV->$stat)
                ->where('date', '>=', $daterangepicker_start)
                ->where('date', '<=', $daterangepicker_end)
                ->get()
                ->toArray();


            $TotalP = AnalyticsPage::whereIn("visitor_id", $AllVArray)->count();

            $newdata = array(
                'name' => $AnalyticsV->$stat,
                'visits' => $TotalV,
                'pages' => $TotalP
            );
            array_push($Last7DaysVisitors, $newdata);
            $ix++;
        }

        // Today By Country
        $date_today = date('Y-m-d');
        $stat = "country";

        $TodayByCountry = array();

        $AnalyticsVisitors = AnalyticsVisitor::select("*")->select($stat)->where('date', $date_today)
            ->groupBy($stat)
            ->orderBy($stat, 'asc')
            ->get();
        $ix = 0;
        foreach ($AnalyticsVisitors as $AnalyticsV) {

            $FST = AnalyticsVisitor::where("$stat", $AnalyticsV->$stat)
                ->where('date', $date_today)->orderby("id","desc")->first();

            $TotalV = AnalyticsVisitor::where("$stat", $AnalyticsV->$stat)
                ->where('date', $date_today)->count();

            $AllVArray = AnalyticsVisitor::select('id')->where("$stat", $AnalyticsV->$stat)
                ->where('date', $date_today)
                ->get()
                ->toArray();

            $TotalP = AnalyticsPage::whereIn("visitor_id", $AllVArray)->count();

            $newdata = array(
                'name' => $AnalyticsV->$stat,
                'code' => substr($FST->country_code, 0, 2),
                'visits' => $TotalV,
                'pages' => $TotalP
            );
            array_push($TodayByCountry, $newdata);
            $ix++;
        }
        usort($TodayByCountry, function ($a, $b) {
            return $b['visits'] - $a['visits'];
        });


        // Today By Browser
        $date_today = date('Y-m-d');
        $stat = "browser";

        $TodayByBrowsers = array();

        $AnalyticsVisitors = AnalyticsVisitor::select("*")->select($stat)->where('date', '>=', $daterangepicker_start)
            ->where('date', '<=', $daterangepicker_end)
            ->groupBy($stat)
            ->orderBy($stat, 'asc')
            ->get();
        $ix = 0;
        foreach ($AnalyticsVisitors as $AnalyticsV) {

            $TotalV = AnalyticsVisitor::where("$stat", $AnalyticsV->$stat)
                ->where('date', '>=', $daterangepicker_start)
                ->where('date', '<=', $daterangepicker_end)->count();

            $newdata = array(
                'name' => $AnalyticsV->$stat,
                'visits' => $TotalV
            );
            array_push($TodayByBrowsers, $newdata);
            $ix++;
        }
        usort($TodayByBrowsers, function ($a, $b) {
            return $b['visits'] - $a['visits'];
        });
        $TodayByBrowser1 = "";
        $TodayByBrowser1_val = 0;
        $TodayByBrowser2 = "Other Browsers";
        $TodayByBrowser2_val = 0;
        $ix = 0;
        $emptyB = 0;
        foreach ($TodayByBrowsers as $TodayByBrowser) {
            $emptyBi = 0;
            if ($emptyB == 0) {
                $emptyBi = $ix;
            }
            if ($ix == $emptyBi) {
                $ix2 = 0;
                foreach ($TodayByBrowser as $key => $val) {
                    if ($ix2 == 0) {
                        $TodayByBrowser1 = $val;
                        if ($TodayByBrowser1 != "") {
                            $emptyB = 1;
                        }
                    }
                    if ($ix2 == 1) {
                        $TodayByBrowser1_val = $val;
                    }
                    $ix2++;
                }
            } else {
                $ixx2 = 0;
                foreach ($TodayByBrowser as $key => $val) {
                    if ($ixx2 == 1) {
                        $TodayByBrowser2_val += $val;
                    }
                    $ixx2++;
                }
            }
            $ix++;
        }

        // Visitor Rate today
        $day_date = date('Y-m-d');
        $TodayVisitorsRate = "";
        $fsla = "";
        for ($ii = 0; $ii < 24; $ii = $ii + 2) {
            if ($ii != 0) {
                $fsla = ", ";
            }
            $stepis = $ii + 2;
            $timeis1 = sprintf("%02d", $ii).":00:00";
            $timeis2 = sprintf("%02d", $stepis).":00:00";
            $TotalV = AnalyticsVisitor::where('date', $day_date)
                ->where('time', '>=', $timeis1)
                ->where('time', '<', $timeis2)
                ->count();
            $TodayVisitorsRate = $TodayVisitorsRate . $fsla . "[$ii,$TotalV]";
        }

        // Tele Sales Stats & Funnel Data (Pure real queries, 0 when empty)
        $totalLeadsCount = Lead::count();
        $newLeadsCount = Lead::where('status', 1)->count();
        $assignedLeadsCount = Lead::whereNotNull('assigned_to')->where('assigned_to', '>', 0)->count();

        // Contacted (picked up / call logged)
        $contactedLeadsCount = CallLog::whereNotNull('lead_id')->distinct('lead_id')->count('lead_id');
        if ($contactedLeadsCount == 0) {
            $contactedLeadsCount = Lead::whereIn('status', [2, 3, 4, 5, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21])
                ->orWhereNotNull('last_actioned_date')
                ->count();
        }

        // Interested Leads (Status 21 or interested)
        $interestedLeadsCount = Lead::whereIn('status', [3, 21])->count();

        // Follow ups due
        $followUpsDueCount = Lead::openFollowups()->count();

        // Conversions Won
        $conversionsWonCount = Lead::whereIn('status', [1, 2, 6, 15])->count();

        // Calling stats for today & yesterday
        $tz = CallingRestrictionService::getTimezone();
        $todayDate = Carbon::today($tz)->format('Y-m-d');
        $yesterdayDate = Carbon::yesterday($tz)->format('Y-m-d');
        
        $dialerCallsToday = DialerLog::whereDate('created_at', $todayDate)->where('call_source', 'manual')->count();
        $dialerConnectedToday = DialerLog::whereDate('created_at', $todayDate)
            ->where('call_source', 'manual')
            ->where(function($q) {
                $q->whereIn('call_state', ['completed', 'answered'])
                  ->orWhere('time_taken', '>', 0)
                  ->orWhere(function($sq) {
                      $sq->whereNotNull('duration')->whereNotIn('duration', ['00:00', '0', '']);
                  });
            })->count();

        $callLogsToday = CallLog::whereDate('created_at', $todayDate)->where('call_source', 'campaign')->count();
        $callLogsConnToday = CallLog::whereDate('created_at', $todayDate)->where('call_source', 'campaign')->where('duration', '>', 0)->count();

        $callsTodayCount = $callLogsToday + $dialerCallsToday;
        $callsConnectedTodayCount = $callLogsConnToday + $dialerConnectedToday;

        $callsYesterdayCount = CallLog::whereDate('created_at', $yesterdayDate)->count()
            + DialerLog::whereDate('created_at', $yesterdayDate)->count();

        $followUpsNextHourCount = Lead::openFollowups()
            ->whereBetween('follow_up_on', [Carbon::now($tz), Carbon::now($tz)->addHour()])
            ->count();

        $leadsWorkedTodayCount = CallLog::whereDate('created_at', $todayDate)->whereNotNull('lead_id')->distinct('lead_id')->count('lead_id')
            + DialerLog::whereDate('created_at', $todayDate)->whereNotNull('lead_id')->distinct('lead_id')->count('lead_id');

        // Active Campaigns Table (100% real database query)
        $activeCampaigns = Campaign::where('status', '1')->orWhere('status', 1)->take(5)->get()->map(function($camp) {
            $leadsCount = Lead::where('campaign_id', $camp->id)->count();
            $contacted = Lead::where('campaign_id', $camp->id)->where(function($q) {
                $q->whereIn('status', [2, 3, 4, 5, 14, 15, 16, 21])->orWhereNotNull('last_actioned_date');
            })->count();
            $pct = $leadsCount > 0 ? min(100, round(($contacted / $leadsCount) * 100)) : 0;
            $agentsCount = Lead::where('campaign_id', $camp->id)->whereNotNull('assigned_to')->where('assigned_to', '!=', '')->distinct('assigned_to')->count('assigned_to');
            $conversionsWon = Lead::where('campaign_id', $camp->id)->whereIn('status', [1, 2, 6, 15])->count();
            return [
                'id' => $camp->id,
                'name' => $camp->name,
                'progress_pct' => $pct,
                'target' => $leadsCount,
                'completed' => $contacted,
                'remaining' => max(0, $leadsCount - $contacted),
                'agents_count' => $agentsCount,
                'conversions_count' => $conversionsWon,
                'status' => 'Active',
            ];
        });

        // Recent Call Activity - combine recent calls from CallLog (campaign) and DialerLog (manual)
        $recentCl = CallLog::where('call_source', 'campaign')->orderBy('id', 'desc')->take(6)->get()->map(function($call) {
            $agent = User::find($call->created_by);
            $agentName = $agent ? $agent->name : 'Telecaller #' . $call->created_by;
            $durSec = $call->duration ? $call->duration : 0;
            $durStr = \App\Helpers\Helper::formatDuration($durSec);
            $st = $call->duration > 0 ? 'Connected' : ($call->call_state ? ucfirst($call->call_state) : 'Completed');
            return [
                'id' => $call->id,
                'created_at_ts' => strtotime($call->created_at),
                'time' => date('H:i:s', strtotime($call->created_at)),
                'agent' => $agentName,
                'number' => $call->phonenumber,
                'duration' => $durStr,
                'status' => $st,
                'type' => $call->call_type ? $call->call_type : 'Click2Call',
            ];
        });

        $recentDl = DialerLog::where('call_source', 'manual')->orderBy('id', 'desc')->take(6)->get()->map(function($call) {
            $agent = User::find($call->created_by);
            $agentName = $agent ? $agent->name : 'Telecaller #' . $call->created_by;
            $durSec = (int)($call->time_taken ?? 0);
            $durStr = \App\Helpers\Helper::formatDuration($durSec > 0 ? $durSec : $call->duration);
            $isConnected = $durSec > 0 || in_array($call->call_state, ['completed', 'answered']);
            $st = $isConnected ? 'Connected' : ucfirst($call->call_state ?: 'Completed');
            return [
                'id' => $call->id,
                'created_at_ts' => strtotime($call->created_at),
                'time' => date('H:i:s', strtotime($call->created_at)),
                'agent' => $agentName,
                'number' => $call->phone_number,
                'duration' => $durStr,
                'status' => $st,
                'type' => $call->call_type ? ucfirst($call->call_type) : 'Click2Call',
            ];
        });

        $recentCallLogs = $recentCl->concat($recentDl)->sortByDesc('created_at_ts')->take(6)->values()->map(function($item) {
            unset($item['created_at_ts']);
            return $item;
        });

        // Live Telecaller Activity Floor: only real staff who are logged in / active today
        $prefix = DB::getTablePrefix();
        $activeRows = DB::select("
            SELECT l1.user_id
            FROM {$prefix}user_logs l1
            INNER JOIN (
                SELECT user_id, MAX(id) AS max_id
                FROM {$prefix}user_logs
                WHERE DATE(login_date) = '{$todayDate}'
                GROUP BY user_id
            ) l2 ON l1.id = l2.max_id
            WHERE l1.login_date IS NOT NULL AND l1.logout_date IS NULL
        ");
        $activeUserIds = array_map(function($r) { return $r->user_id; }, $activeRows);

        $activeStaff = !empty($activeUserIds) ? User::whereIn('id', $activeUserIds)->where('status', 1)->get() : collect([]);

        $telecallerFloor = [];
        foreach ($activeStaff as $user) {
            $userCampCalls = CallLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)->count();
            $userManualCalls = DialerLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)->count();
            $userCallsCount = $userCampCalls + $userManualCalls;

            $userCampConn = CallLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)->where('duration', '>', 0)->count();
            $userManualConn = DialerLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)
                ->where(function($q) {
                    $q->whereIn('call_state', ['completed', 'answered'])
                      ->orWhere('time_taken', '>', 0)
                      ->orWhere(function($sq) {
                          $sq->whereNotNull('duration')->whereNotIn('duration', ['00:00', '0', '']);
                      });
                })->count();
            $userConnCount = $userCampConn + $userManualConn;

            $campTalk = (int) CallLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)->sum('duration');
            $manualTalk = (int) DialerLog::where('created_by', $user->id)->whereDate('created_at', $todayDate)->sum('time_taken');
            $userTalkTime = $campTalk + $manualTalk;

            $userFollowUps = Lead::where('assigned_to', $user->id)->openFollowups()->count();
            $userWon = Lead::where('assigned_to', $user->id)->whereIn('status', [1, 2, 6, 15])->count();
            $assignedLead = Lead::where('assigned_to', $user->id)->orderBy('id', 'desc')->first();
            $assignedLeadStr = $assignedLead ? ($assignedLead->name . ($assignedLead->company ? ' · ' . $assignedLead->company : '')) : '—';

            // Active call detection
            $recentActiveCall = DialerLog::where('created_by', $user->id)
                ->where('created_at', '>=', Carbon::now($tz)->subMinutes(3))
                ->whereIn('call_state', ['calling', 'in_progress', 'active'])
                ->orderBy('id', 'desc')
                ->first();

            $status = $recentActiveCall ? 'call' : 'ready';
            $since = $recentActiveCall ? (strtotime($recentActiveCall->created_at) * 1000) : null;

            $telecallerFloor[] = [
                $user->name,
                $status,
                $assignedLeadStr,
                $userCallsCount,
                $userConnCount,
                $userTalkTime,
                $userFollowUps,
                $userWon,
                0, // idle_time
                $since,
            ];
        }

        $onlineAgentsCount = count($telecallerFloor);
        $onCallNowCount = count(array_filter($telecallerFloor, function($a) { return $a[1] === 'call'; }));

        return view('dashboard.home',
            compact("GeneralWebmasterSections", "Webmails", "Events", "Contacts", "TodayVisitors", "TodayPages",
                "Last7DaysVisitors", "TodayByCountry", "TodayByBrowser1", "TodayByBrowser1_val", "TodayByBrowser2",
                "TodayByBrowser2_val", "TodayVisitorsRate", "totalLeadsCount", "newLeadsCount", "assignedLeadsCount",
                "contactedLeadsCount", "interestedLeadsCount", "followUpsDueCount", "conversionsWonCount",
                "callsTodayCount", "callsConnectedTodayCount", "callsYesterdayCount", "followUpsNextHourCount",
                "leadsWorkedTodayCount", "activeCampaigns", "telecallerFloor", "onlineAgentsCount",
                "onCallNowCount", "recentCallLogs"));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        $search_word = "";
        $active_tab = 0;
        return view('dashboard.search', compact("GeneralWebmasterSections", "search_word", "active_tab"));
    }


    /**
     * Search resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function find(Request $request)
    {
        //
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $active_tab = 0;
        if ($request->q != "") {
            if (@Auth::user()->permissionsGroup->view_status) {
                //find Contacts
                $Contacts = Contact::where('created_by', '=', Auth::user()->id)->where('first_name', 'like',
                    '%' . $request->q . '%')
                    ->orwhere('last_name', 'like', '%' . $request->q . '%')
                    ->orwhere('company', 'like', '%' . $request->q . '%')
                    ->orwhere('city', 'like', '%' . $request->q . '%')
                    ->orwhere('notes', 'like', '%' . $request->q . '%')
                    ->orwhere('phone', '=', $request->q)
                    ->orwhere('email', '=', $request->q)
                    ->orderby('id', 'desc')->get();

                //find Webmails
                $Webmails = Webmail::where('created_by', '=', Auth::user()->id)->where('title', 'like',
                    '%' . $request->q . '%')
                    ->orwhere('from_name', 'like', '%' . $request->q . '%')
                    ->orwhere('from_email', 'like', '%' . $request->q . '%')
                    ->orwhere('from_phone', 'like', '%' . $request->q . '%')
                    ->orwhere('to_email', 'like', '%' . $request->q . '%')
                    ->orwhere('to_name', 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();

                //find Events
                $Events = Event::where('created_by', '=', Auth::user()->id)->where('title', 'like',
                    '%' . $request->q . '%')
                    ->orwhere('details', 'like', '%' . $request->q . '%')
                    ->orderby('start_date', 'desc')->get();

                //find Topics
                $Topics = Topic::where('created_by', '=', Auth::user()->id)->where('title_' . Helper::currentLanguage()->code, 'like',
                    '%' . $request->q . '%')
                    ->orwhere('seo_title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();

                //find Sections
                $Sections = Section::where('created_by', '=', Auth::user()->id)->where('title_' . Helper::currentLanguage()->code, 'like',
                    '%' . $request->q . '%')
                    ->orwhere('seo_title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();
            } else {
                //find Contacts
                $Contacts = Contact::where('first_name', 'like', '%' . $request->q . '%')
                    ->orwhere('last_name', 'like', '%' . $request->q . '%')
                    ->orwhere('company', 'like', '%' . $request->q . '%')
                    ->orwhere('city', 'like', '%' . $request->q . '%')
                    ->orwhere('notes', 'like', '%' . $request->q . '%')
                    ->orwhere('phone', '=', $request->q)
                    ->orwhere('email', '=', $request->q)
                    ->orderby('id', 'desc')->get();

                //find Webmails
                $Webmails = Webmail::where('title', 'like', '%' . $request->q . '%')
                    ->orwhere('from_name', 'like', '%' . $request->q . '%')
                    ->orwhere('from_email', 'like', '%' . $request->q . '%')
                    ->orwhere('from_phone', 'like', '%' . $request->q . '%')
                    ->orwhere('to_email', 'like', '%' . $request->q . '%')
                    ->orwhere('to_name', 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();

                //find Events
                $Events = Event::where('title', 'like', '%' . $request->q . '%')
                    ->orwhere('details', 'like', '%' . $request->q . '%')
                    ->orderby('start_date', 'desc')->get();

                //find Topics
                $Topics = Topic::where('title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orwhere('seo_title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();

                //find Sections
                $Sections = Section::where('title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orwhere('seo_title_' . Helper::currentLanguage()->code, 'like', '%' . $request->q . '%')
                    ->orderby('id', 'desc')->get();

            }
            if (count($Webmails) > 0) {
                $active_tab = 5;
            }
            if (count($Events) > 0) {
                $active_tab = 4;
            }
            if (count($Contacts) > 0) {
                $active_tab = 3;
            }
            if (count($Sections) > 0) {
                $active_tab = 2;
            }
            if (count($Topics) > 0) {
                $active_tab = 1;
            }

        } else {
            return redirect()->action('Dashboard\DashboardController@search');
        }
        $search_word = $request->q;

        return view("dashboard.search",
            compact("GeneralWebmasterSections", "search_word", "Webmails", "Contacts", "Events", "Topics", "Sections",
                "active_tab"));
    }
    
    public function dashboard()
    {
        echo "Dashboard";
    }

}
