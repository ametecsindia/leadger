<?php

// This class file to define all general functions

namespace App\Helpers;


use App;
use App\Models\AnalyticsPage;
use App\Models\AnalyticsVisitor;
use App\Models\Banner;
use App\Models\CallLog;
use App\Models\Country;
use App\Models\Event;
use App\Models\FirebaseLog;
use App\Models\Menu;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\Webmail;
use App\Models\Language;
use App\Models\WebmasterSection;
use App\Models\WebmasterSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Auth;
use GeoIP;
use Illuminate\Support\Facades\DB;

class Helper
{
    /**
     * A Lead ID as shown everywhere: digits only (no "#" / "LD"), clickable - opens the shared Lead Details popup
     * (layouts/lead-popup.blade.php). Anything that is not a plain lead id is returned escaped, not linked.
     */
    public static function leadIdLink($id): string
    {
        $id = trim((string)$id);
        if ($id === '' || !ctype_digit($id)) {
            return e($id);
        }
        return '<a href="javascript:void(0)" class="lg-lead-id-link" data-lead-id="' . $id . '" title="View lead ' . $id . '">' . $id . '</a>';
    }


    static function GeneralWebmasterSettings($var)
    {
        $_Loader_WebmasterSettings = \Session::get('_Loader_WebmasterSettings');
        if (empty($_Loader_WebmasterSettings)) {
            $_Loader_WebmasterSettings = WebmasterSetting::find(1);
            \Session()->put('_Loader_WebmasterSettings', $_Loader_WebmasterSettings);
        }
        return @$_Loader_WebmasterSettings->$var;
    }

    static function GeneralSiteSettings($var)
    {
        $_Loader_Web_Settings = \Session::get('_Loader_Web_Settings');
        if (empty($_Loader_Web_Settings)) {
            $_Loader_Web_Settings = Setting::find(1);
            \Session()->put('_Loader_Web_Settings', $_Loader_Web_Settings);
        }
        return @$_Loader_Web_Settings->$var;
    }

    /**
     * Convert any stored duration value to whole seconds.
     * Accepts integers, numeric strings ("125", "125.6"), "mm:ss" and "hh:mm:ss".
     * Anything empty, negative or unparseable returns 0.
     */
    static function durationToSeconds($value): int
    {
        if ($value === null || $value === '' || is_bool($value) || is_array($value) || is_object($value)) {
            return 0;
        }
        if (is_int($value) || is_float($value)) {
            return max(0, (int)round($value));
        }
        $value = trim((string)$value);
        if (is_numeric($value)) {
            return max(0, (int)round((float)$value));
        }
        if (preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/', $value, $m)) {
            return isset($m[3])
                ? (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3]
                : (int)$m[1] * 60 + (int)$m[2];
        }
        return 0;
    }

    /**
     * Format a duration as "mm:ss", or "h:mm:ss" for an hour or more. Invalid/empty -> "00:00".
     */
    static function formatDuration($value): string
    {
        $sec = self::durationToSeconds($value);
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
    }

    /**
     * Click2Call users allowed by the licence (settings.full_response data.c2c_channels); 0 = no limit set.
     */
    static function licensedClick2CallUsers(): int
    {
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $data = $settings && !empty($settings->full_response) ? json_decode($settings->full_response, true) : null;
        return (int)($data['data']['c2c_channels'] ?? 0);
    }

    /**
     * WebRTC users allowed by the licence (settings.full_response data.webrtc_channels); 0 = no limit set.
     */
    static function licensedWebRTCUsers(): int
    {
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $data = $settings && !empty($settings->full_response) ? json_decode($settings->full_response, true) : null;
        return (int)($data['data']['webrtc_channels'] ?? 0);
    }

    /**
     * One outcome label for a call, from its stored state and real talk time.
     * Talk time > 0 always means Connected (the provider's data wins over the browser's state).
     */
    static function callOutcome($callState, $talkSeconds): string
    {
        if (self::durationToSeconds($talkSeconds) > 0) {
            return 'Connected';
        }
        switch (strtolower(trim((string)$callState))) {
            case 'busy':
                return 'Busy';
            case 'no-answer':
            case 'no_answer':
            case 'noanswer':
                return 'No Answer';
            case 'failed':
            case 'cancelled':
            case 'canceled':
            case 'rejected':
                return 'Failed';
            case 'initiated':
            case 'ringing':
            case 'answered':
            case 'connected':
                return 'In Progress';
            default:
                return 'Not Connected';
        }
    }

    /**
     * Normalise a provider / gateway dial status ("NOANSWER", "BUSY", "ANSWER", ...) to our call_state.
     * Returns null when the status does not describe a final outcome.
     */
    static function callStateFromDialStatus(?string $status): ?string
    {
        $s = strtolower(preg_replace('/[^a-z]/i', '', (string)$status));
        if ($s === '') return null;
        if (str_contains($s, 'busy')) return 'busy';
        if (str_contains($s, 'noanswer') || str_contains($s, 'unanswer') || $s === 'notanswered' || $s === 'missed') return 'no-answer';
        if (str_contains($s, 'fail') || str_contains($s, 'cancel') || str_contains($s, 'reject') || str_contains($s, 'congestion') || str_contains($s, 'chanunavail')) return 'failed';
        if (str_contains($s, 'answer') || str_contains($s, 'connect') || $s === 'completed' || $s === 'success') return 'completed';
        return null;
    }

    /**
     * Check if global Delete Permission is enabled in Company Settings.
     * Stored in Permissions where name = 'Manager', column delete_status.
     *
     * @return bool
     */
    static function isDeletePermissionEnabled()
    {
        $managerPerm = \App\Models\Permissions::where('name', 'Manager')->first();
        return $managerPerm ? (bool)$managerPerm->delete_status : false;
    }

    /**
     * Check if current authenticated user is authorized to delete call recordings.
     * Rule:
     * 1. Delete Permission applies ONLY to call recordings (Recordings page).
     * 2. Allowed ONLY when BOTH are true:
     *    - Current authenticated user's role is Admin (Telecallers and Managers must NEVER delete).
     *    - Delete Permission is enabled in Company Settings.
     *
     * @return bool
     */
    static function canDeleteRecordings()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return false;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $userRole = @$user->permissionsGroup->name;

        // Telecallers and managers must never be able to delete recordings, even if the setting is enabled.
        if ($userRole !== 'Admin') {
            return false;
        }

        // Delete Permission setting must be enabled in Company Settings
        return self::isDeletePermissionEnabled();
    }

    /**
     * Check if current authenticated user is authorized to perform delete operations on other pages
     * (leads, campaigns, staff, logs, etc.).
     * The Company Settings Delete Permission applies ONLY to call recordings and does NOT block other pages.
     *
     * @return bool
     */
    static function canDelete()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return false;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $userRole = @$user->permissionsGroup->name;

        if (in_array($userRole, ['Admin', 'Webmaster'])) {
            return true;
        }

        return (bool) (@$user->permissionsGroup->delete_status);
    }

    // Get Events Alerts
    static function eventsAlerts()
    {
        $_Loader_Events = \Session::get('_Loader_Events');
        if (empty($_Loader_Events)) {
            if (@Auth::user()->permissionsGroup->view_status) {
                $_Loader_Events = Event::where('created_by', '=', Auth::user()->id)->where('start_date', '>=', date('Y-m-d H:i:s'))->orderby('start_date', 'asc')->limit(10)->get();
            } else {
                $_Loader_Events = Event::where('start_date', '>=', date('Y-m-d H:i:s'))->orderby('start_date', 'asc')->limit(10)->get();
            }
            \Session()->put('_Loader_Events', $_Loader_Events);
        }
        return $_Loader_Events;
    }

    // Get Webmails Alerts
    static function webmailsAlerts()
    {
        //List of all Webmails
        if (@Auth::user()->permissionsGroup->view_status) {
            $Webmails = Webmail::where('created_by', '=', Auth::user()->id)->orderby('id', 'desc')->where('status', '=',
                0)
                ->where('cat_id', '=', 0)->limit(4)->get();
        } else {
            $Webmails = Webmail::orderby('id', 'desc')->where('status', '=', 0)
                ->where('cat_id', '=', 0)->limit(4)->get();
        }

        return $Webmails;
    }

    // Get Webmails Alerts
    static function webmailsNewCount()
    {
        //List of all Webmails
        if (@Auth::user()->permissionsGroup->view_status) {
            $Webmails = Webmail::where('created_by', '=', Auth::user()->id)->orderby('id', 'desc')->where('status', '=',
                0)->where('cat_id', '=', 0)->get();
        } else {
            $Webmails = Webmail::orderby('id', 'desc')->where('status', '=', 0)->where('cat_id', '=', 0)->get();
        }
        return count($Webmails);
    }

    // Banners array List
    static function BannersList($BannersSettingsId)
    {
        return Banner::where('section_id', $BannersSettingsId)->where('status', 1)->orderby('row_no', 'asc')->get();
    }

    // Menu array List
    static function MenuList($GroupId)
    {
        return Menu::where('father_id', $GroupId)->where('status', 1)->orderby('row_no', 'asc')->get();
    }

    // detect browser
    static function getBrowser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent)) {
            return 'Unknown';
        }

        // check if IE 8 - 11+
        preg_match('/Trident\/(.*)/', $user_agent, $matches);
        if ($matches) {
            $version = intval($matches[1]) + 4;     // Trident 4 for IE8, 5 for IE9, etc
            return 'Internet Explorer ' . ($version < 11 ? $version : $version);
        }

        preg_match('/MSIE (.*)/', $user_agent, $matches);
        if ($matches) {
            return 'Internet Explorer ' . intval($matches[1]);
        }

        // check if Firefox, Opera, Chrome, Safari
        foreach (array('Firefox', 'OPR', 'Chrome', 'Safari') as $browser) {
            preg_match('/' . $browser . '/', $user_agent, $matches);
            if ($matches) {
                return str_replace('OPR', 'Opera',
                    $browser);   // we don't care about the version, because this is a modern browser that updates itself unlike IE
            }
        }
        return 'Unknown';
    }

    // detect OS
    static function getOS()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent)) {
            return 'Unknown';
        }

        $os_platform = "unknown";

        $os_array = array(
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        );

        foreach ($os_array as $regex => $value) {

            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }

        }

        return $os_platform;

    }

    // Visitors Data
    static function SaveVisitorInfo($PageTitle)
    {
        if (env('GEOIP_STATUS', false)) {
            $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
            $current_page_full_link = "http://$http_host$request_uri";
            $page_load_time = defined('LARAVEL_START') ? round((microtime(true) - LARAVEL_START), 8) : 0;

            // Check is it already saved today to visitors?
            $SavedVisitor = AnalyticsVisitor::where('ip', '=', $visitor_ip)->where('date', '=', date('Y-m-d'))->first();
            if (empty($SavedVisitor)) {

                // New to analyticsVisitors
                try {
                    $visitor_local_ip_details = AnalyticsVisitor::where('ip', $visitor_ip)->first();
                    if (!empty($visitor_local_ip_details)) {
                        $visitor_city = $visitor_local_ip_details->city;
                        $visitor_region = $visitor_local_ip_details->region;
                        $visitor_country_code = $visitor_local_ip_details->country_code;
                        $visitor_country = $visitor_local_ip_details->country;
                        $visitor_loc_0 = $visitor_local_ip_details->location_cor1;
                        $visitor_loc_1 = $visitor_local_ip_details->location_cor2;
                        $visitor_org = $visitor_local_ip_details->org;
                        $visitor_hostname = $visitor_local_ip_details->hostname;
                    } else {

                        $visitor_ip_details = [];
                        try {
                            $visitor_ip_details = GeoIP($visitor_ip);
                        } catch (\Exception $e) {

                        }

                        $visitor_city = @$visitor_ip_details->city;
                        if ($visitor_city == "") {
                            $visitor_city = "unknown";
                        }
                        $visitor_region = @$visitor_ip_details->state_name;
                        if ($visitor_region == "") {
                            $visitor_region = "unknown";
                        }
                        $visitor_country_code = @$visitor_ip_details->iso_code;
                        if ($visitor_country_code == "") {
                            $visitor_country_code = "unknown";
                        }
                        $visitor_country = @$visitor_ip_details->country;
                        if ($visitor_country == "") {
                            $visitor_country = "unknown";
                        }

                        $visitor_loc_0 = @$visitor_ip_details->lat;
                        if ($visitor_loc_0 == "") {
                            $visitor_loc_0 = "unknown";
                        }
                        $visitor_loc_1 = @$visitor_ip_details->lon;
                        if ($visitor_loc_1 == "") {
                            $visitor_loc_1 = "unknown";
                        }

                        $visitor_org = @$visitor_ip_details->timezone;
                        if ($visitor_org == "") {
                            $visitor_org = "unknown";
                        }
                        $visitor_hostname = @$visitor_ip_details->continent;
                        if ($visitor_hostname == "") {
                            $visitor_hostname = "unknown";
                        }
                    }
                } catch (Exception $e) {
                    $visitor_city = "unknown";
                    $visitor_region = "unknown";
                    $visitor_country_code = "unknown";
                    $visitor_country = "unknown";
                    $visitor_loc_0 = "unknown";
                    $visitor_loc_1 = "unknown";
                    $visitor_org = "unknown";
                    $visitor_hostname = "unknown";
                }

                $visitor_referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "unknown";
                $visitor_browser = Helper::getBrowser();
                $visitor_os = Helper::getOS();
                $visitor_screen_res = "unknown";

                // Start saving to database
                $Visitor = new AnalyticsVisitor;
                $Visitor->ip = $visitor_ip;
                $Visitor->city = $visitor_city;
                $Visitor->country_code = $visitor_country_code;
                $Visitor->country = $visitor_country;
                $Visitor->region = $visitor_region;
                $Visitor->location_cor1 = $visitor_loc_0;
                $Visitor->location_cor2 = $visitor_loc_1;
                $Visitor->os = $visitor_os;
                $Visitor->browser = $visitor_browser;
                $Visitor->resolution = $visitor_screen_res;
                $Visitor->referrer = $visitor_referrer;
                $Visitor->hostname = $visitor_hostname;
                $Visitor->org = $visitor_org;
                $Visitor->date = date('Y-m-d');
                $Visitor->time = date('H:i:s');
                $Visitor->save();

                // Start saving page info to database
                $VisitedPage = new AnalyticsPage;
                $VisitedPage->visitor_id = $Visitor->id;
                $VisitedPage->ip = $visitor_ip;
                $VisitedPage->title = $PageTitle;
                $VisitedPage->name = "unknown";
                $VisitedPage->query = $current_page_full_link;
                $VisitedPage->load_time = $page_load_time;
                $VisitedPage->date = date('Y-m-d');
                $VisitedPage->time = date('H:i:s');
                $VisitedPage->save();


            } else {
                // Already Saved to analyticsVisitors
                // Check if page saved
                $Savedpage = AnalyticsPage::where('visitor_id', '=', $SavedVisitor->id)->where('ip', '=',
                    $visitor_ip)->where('date', '=', date('Y-m-d'))->where('query', '=', $current_page_full_link)->first();
                if (empty($Savedpage)) {
                    $VisitedPage = new AnalyticsPage;
                    $VisitedPage->visitor_id = $SavedVisitor->id;
                    $VisitedPage->ip = $visitor_ip;
                    $VisitedPage->title = $PageTitle;
                    $VisitedPage->name = "unknown";
                    $VisitedPage->query = $current_page_full_link;
                    $VisitedPage->load_time = $page_load_time;
                    $VisitedPage->date = date('Y-m-d');
                    $VisitedPage->time = date('H:i:s');
                    $VisitedPage->save();
                }

            }
        }
    }

    // Videos Check Functions

    static function Get_youtube_video_id($url)
    {
        if (preg_match('/youtu\.be/i', $url) || preg_match('/youtube\.com\/watch/i', $url)) {
            $pattern = '/^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/';
            preg_match($pattern, $url, $matches);
            if (count($matches) && strlen($matches[7]) == 11) {
                return $matches[7];
            }
        }

        return '';
    }

    static function Get_vimeo_video_id($url)
    {
        if (preg_match('/vimeo\.com/i', $url)) {
            $pattern = '/\/\/(www\.)?vimeo.com\/(\d+)($|\/)/';
            preg_match($pattern, $url, $matches);
            if (count($matches)) {
                return $matches[2];
            }
        }

        return '';
    }

    // Social Share links
    static function SocialShare($social, $title)
    {
        $shareLink = "";
        $URL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        switch ($social) {
            case "facebook":
                $shareLink = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($URL);
                break;
            case "twitter":
                $shareLink = "https://twitter.com/intent/tweet?text=$title&url=" . urlencode($URL);
                break;
            case "linkedin":
                $shareLink = "http://www.linkedin.com/shareArticle?mini=true&url=" . urlencode($URL) . "&title=$title";
                break;
            case "tumblr":
                $shareLink = "http://www.tumblr.com/share/link?url=" . urlencode($URL);
                break;
        }

        return $shareLink;
    }

    static function GetIcon($path, $file)
    {
        $ext = strrchr($file, ".");
        $ext = strtolower($ext);
        $icon = "<i class=\"fa fa-file-o\"></i>";
        if ($ext == ".pdf") {
            $icon = "<i class=\"fa fa-file-pdf-o\" style='color: red;font-size: 20px'></i>";
        }
        if ($ext == '.png' or $ext == '.jpg' or $ext == '.jpeg' or $ext == '.gif') {
            $icon = "<img src='$path/$file' style='width: auto;height: 20px' title=''>";
        }
        if ($ext == ".xls" or $ext == '.xlsx') {
            $icon = "<i class=\"fa fa-file-excel-o\" style='color: green;font-size: 20px'></i>";
        }
        if ($ext == ".ppt" or $ext == '.pptx' or $ext == '.pptm') {
            $icon = "<i class=\"fa fa-file-powerpoint-o\" style='color: #1066E7;font-size: 20px'></i>";
        }
        if ($ext == ".doc" or $ext == '.docx') {
            $icon = "<i class=\"fa fa-file-word-o\" style='color: #0EA8DD;font-size: 20px'></i>";
        }
        if ($ext == ".zip" or $ext == '.rar') {
            $icon = "<i class=\"fa fa-file-zip-o\" style='color: #C8841D;font-size: 20px'></i>";
        }
        if ($ext == ".txt" or $ext == '.rtf') {
            $icon = "<i class=\"fa fa-file-text-o\" style='color: #7573AA;font-size: 20px'></i>";
        }
        if ($ext == ".mp3" or $ext == '.wav') {
            $icon = "<i class=\"fa fa-file-audio-o\" style='color: #8EA657;font-size: 20px'></i>";
        }
        if ($ext == ".mp4" or $ext == '.avi') {
            $icon = "<i class=\"fa fa-file-video-o\" style='color: #D30789;font-size: 20px'></i>";
        }
        return $icon;

    }

    static function URLSlug($url, $type = "", $id = 0, $num = 0)
    {
        $Check_SEO_st = true;

        $seo_url_slug = Str::slug($url, '-');

        $ReservedURLs = array(
            "home",
            "about",
            "privacy",
            "terms",
            "contact",
            "search",
            "comment",
            "order",
            "sitemap"
        );


        if ($type == "section" && $id > 0) {
            // .. ..  Webmaster Sections
            if (count(Helper::languagesList()) > 0) {
                $check_WebmasterSection = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_WebmasterSection = WebmasterSection::where([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    } else {
                        $check_WebmasterSection = $check_WebmasterSection->orWhere([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    }
                    $i++;
                }
                $check_WebmasterSection_count = $check_WebmasterSection->count();
                if ($check_WebmasterSection_count > 0) {
                    $Check_SEO_st = false;
                }
            }
        } else {
            // .. ..  Webmaster Sections
            if (count(Helper::languagesList()) > 0) {
                $check_WebmasterSection = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_WebmasterSection = WebmasterSection::where('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    } else {
                        $check_WebmasterSection = $check_WebmasterSection->orWhere('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    }
                    $i++;
                }
                $check_WebmasterSection_count = $check_WebmasterSection->count();
                if ($check_WebmasterSection_count > 0) {
                    $Check_SEO_st = false;
                }
            }

        }

        if ($type == "category" && $id > 0) {
            // .. ..  Sections
            if (count(Helper::languagesList()) > 0) {
                $check_Section = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_Section = Section::where([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    } else {
                        $check_Section = $check_Section->orWhere([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    }
                    $i++;
                }
                $check_Section_count = $check_Section->count();
                if ($check_Section_count > 0) {
                    $Check_SEO_st = false;
                }
            }
        } else {
            // .. ..  Sections
            if (count(Helper::languagesList()) > 0) {
                $check_Section = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_Section = Section::where('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    } else {
                        $check_Section = $check_Section->orWhere('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    }
                    $i++;
                }
                $check_Section_count = $check_Section->count();
                if ($check_Section_count > 0) {
                    $Check_SEO_st = false;
                }
            }
        }

        if ($type == "topic" && $id > 0) {
            // .. ..  Topics
            if (count(Helper::languagesList()) > 0) {
                $check_Topic = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_Topic = Topic::where([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    } else {
                        $check_Topic = $check_Topic->orWhere([['seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug], ['id', '!=', $id]]);
                    }
                    $i++;
                }
                $check_Topic_count = $check_Topic->count();
                if ($check_Topic_count > 0) {
                    $Check_SEO_st = false;
                }
            }
        } else {
            // .. ..  Topics
            if (count(Helper::languagesList()) > 0) {
                $check_Topic = [];
                $i = 0;
                foreach (Helper::languagesList() as $ActiveLanguage) {
                    if ($i == 0) {
                        $check_Topic = Topic::where('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    } else {
                        $check_Topic = $check_Topic->orWhere('seo_url_slug_' . $ActiveLanguage->code, $seo_url_slug);
                    }
                    $i++;
                }
                $check_Topic_count = $check_Topic->count();
                if ($check_Topic_count > 0) {
                    $Check_SEO_st = false;
                }
            }
        }

        if (in_array($seo_url_slug, $ReservedURLs)) {
            $Check_SEO_st = false;
        }

        if ($seo_url_slug == "") {
            $Check_SEO_st = true;
        }

        if ($Check_SEO_st) {
            return $seo_url_slug;
        } else {
            $url = preg_replace('/-' . $num . '$/', '', $url);
            $num++;
            $url = $url . "-" . $num;
            return Helper::URLSlug($url, $type, $id, $num);
        }
    }

    static function currentLanguage()
    {
        $locale = App::getLocale();
        if (\Session::has('lang')) {
            $locale = \Session::get('lang');
        }
        $_Loader_Languages = \Session::get('_Loader_Languages');
        if (empty($_Loader_Languages)) {
            $_Loader_Languages = Language::all();
            \Session()->put('_Loader_Languages', $_Loader_Languages);
        }
        $Language = $_Loader_Languages->first(function ($item) use ($locale) {
            return $item->code == $locale;
        });
        if (empty($Language)) {
            $Language = Language::where("code", env('DEFAULT_LANGUAGE', 'en'))->first();
        }
        return $Language;
    }

    static function LangFromCode($code)
    {
        return Language::where("code", $code)->first();
    }

    static function languagesList()
    {
        $_Loader_Languages = \Session::get('_Loader_Languages');
        if (empty($_Loader_Languages)) {
            $_Loader_Languages = Language::all();
            \Session()->put('_Loader_Languages', $_Loader_Languages);
        }
        return $_Loader_Languages->where("status", true);
    }

    static function languageName($Language)
    {
        $language_title = "<span class='label light text-dark lang-label'>";
        if (!empty($Language)) {
            if ($Language->icon != "") {
                $language_title .= "<img src=\"" . asset('assets/dashboard/images/flags/' . $Language->icon . '.svg') . "\" alt=\"\">";
            }
            $language_title .= " <small>" . $Language->title . "</small></span>";
        }
        return $language_title;
    }

    static function sectionURL($id, $lang = "")
    {
        $section_url = "";
        try {
            $title_var = "title_" . @Helper::currentLanguage()->code;
            $title_var2 = "title_" . env('DEFAULT_LANGUAGE');
            if ($lang == "") {
                $lang = @Helper::currentLanguage()->code;
            }
            $_Loader_WebmasterSections = \Session::get('_Loader_WebmasterSections');
            if (empty($_Loader_WebmasterSections)) {
                $_Loader_WebmasterSections = WebmasterSection::all();
                \Session()->put('_Loader_WebmasterSections', $_Loader_WebmasterSections);
            }
            $WebmasterSection = $_Loader_WebmasterSections->first(function ($item) use ($id) {
                return $item->id == $id;
            });

            if (!empty($WebmasterSection)) {
                if ($WebmasterSection->$title_var != "") {
                    $title = $WebmasterSection->$title_var;
                } else {
                    $title = $WebmasterSection->$title_var2;
                }

                if ($WebmasterSection->{'seo_url_slug_' . $lang} != "") {
                    $slug = $WebmasterSection->{'seo_url_slug_' . $lang};
                } else {
                    $slug = $WebmasterSection->{'seo_url_slug_' . env('DEFAULT_LANGUAGE')};
                }
                if ($slug == "") {
                    $slug = Str::slug($title, '-');
                }
                if (Helper::GeneralWebmasterSettings("links_status")) {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $section_url = url($lang . "/" . $slug);
                    } else {
                        $section_url = url($slug);
                    }
                } else {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $section_url = url($lang . "/" . $slug);
                    } else {
                        $section_url = url($slug);
                    }
                }
            }
        } catch (\Exception $e) {

        }
        return $section_url;
    }

    static function categoryURL($id, $lang = "")
    {
        $category_url = "";
        try {
            $title_var = "title_" . @Helper::currentLanguage()->code;
            $title_var2 = "title_" . env('DEFAULT_LANGUAGE');
            if ($lang == "") {
                $lang = @Helper::currentLanguage()->code;
            }
            $Category = Section::find($id);
            if (!empty($Category)) {
                if ($Category->$title_var != "") {
                    $title = $Category->$title_var;
                } else {
                    $title = $Category->$title_var2;
                }
                if ($Category->{'seo_url_slug_' . $lang} != "") {
                    $slug = $Category->{'seo_url_slug_' . $lang};
                } else {
                    $slug = $Category->{'seo_url_slug_' . env('DEFAULT_LANGUAGE')};
                }
                if ($slug == "") {
                    $slug = Str::slug($title, '-');
                }

                $WebmasterSection_slug = "NULL";
                $WebmasterSection = $Category->WebmasterSection;
                if (!empty($WebmasterSection)) {
                    if ($WebmasterSection->$title_var != "") {
                        $S_title = $WebmasterSection->$title_var;
                    } else {
                        $S_title = $WebmasterSection->$title_var2;
                    }

                    if ($WebmasterSection->{'seo_url_slug_' . $lang} != "") {
                        $WebmasterSection_slug = $WebmasterSection->{'seo_url_slug_' . $lang};
                    } else {
                        $WebmasterSection_slug = $WebmasterSection->{'seo_url_slug_' . env('DEFAULT_LANGUAGE')};
                    }
                    if ($WebmasterSection_slug == "") {
                        $WebmasterSection_slug = Str::slug($S_title, '-');
                    }
                }
                if (Helper::GeneralWebmasterSettings("links_status")) {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $category_url = url($lang . "/" . $slug);
                    } else {
                        $category_url = url($slug);
                    }
                } else {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $category_url = route('FrontendTopicsByCatWithLang', ["lang" => $lang, "section" => $WebmasterSection_slug, "cat" => $Category->id]);
                    } else {
                        $category_url = route('FrontendTopicsByCat', ["section" => $WebmasterSection_slug, "cat" => $Category->id]);
                    }
                }
            }

        } catch (\Exception $e) {

        }
        return $category_url;
    }

    static function topicURL($id, $lang = "")
    {
        $topic_url = "";
        try {
            $title_var = "title_" . @Helper::currentLanguage()->code;
            $title_var2 = "title_" . env('DEFAULT_LANGUAGE');
            if ($lang == "") {
                $lang = @Helper::currentLanguage()->code;
            }
            $Topic = Topic::find($id);
            if (!empty($Topic)) {
                if ($Topic->$title_var != "") {
                    $title = $Topic->$title_var;
                } else {
                    $title = $Topic->$title_var2;
                }
                if ($Topic->{'seo_url_slug_' . $lang} != "") {
                    $slug = $Topic->{'seo_url_slug_' . $lang};
                } else {
                    $slug = $Topic->{'seo_url_slug_' . env('DEFAULT_LANGUAGE')};
                }
                if ($slug == "") {
                    $slug = Str::slug($title, '-');
                }

                $WebmasterSection_slug = "NULL";
                $WebmasterSection = $Topic->WebmasterSection;
                if (!empty($WebmasterSection)) {
                    if ($WebmasterSection->$title_var != "") {
                        $S_title = $WebmasterSection->$title_var;
                    } else {
                        $S_title = $WebmasterSection->$title_var2;
                    }
                    if ($WebmasterSection->{'seo_url_slug_' . $lang} != "") {
                        $WebmasterSection_slug = $WebmasterSection->{'seo_url_slug_' . $lang};
                    } else {
                        $WebmasterSection_slug = $WebmasterSection->{'seo_url_slug_' . env('DEFAULT_LANGUAGE')};
                    }
                    if ($WebmasterSection_slug == "") {
                        $WebmasterSection_slug = Str::slug($S_title, '-');
                    }
                }
                if (Helper::GeneralWebmasterSettings("links_status")) {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $topic_url = url($lang . "/" . $slug);
                    } else {
                        $topic_url = url($slug);
                    }
                } else {
                    if ($lang != env('DEFAULT_LANGUAGE')) {
                        $topic_url = route('FrontendTopicByLang', ["lang" => $lang, "section" => $WebmasterSection_slug, "id" => $Topic->id]);
                    } else {
                        $topic_url = route('FrontendTopic', ["section" => $WebmasterSection_slug, "id" => $Topic->id]);
                    }
                }
            }
        } catch (\Exception $e) {

        }
        return $topic_url;
    }

    static function formatDate($date = "")
    {
        if ($date != "") {
            $format = env("DATE_FORMAT", "Y-m-d");
            return date($format, strtotime($date));
        }
        return "";
    }

    static function dateForDB($date = "", $withTime = 0)
    {
        if ($date != "") {
            try {
                return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d'); 
            } catch (\Exception $e) {
                return $date;
            }
        }
        return "";
    }
    

    static function jsDateFormat()
    {
        $format = env("DATE_FORMAT", "Y-m-d");
        $format = str_replace("Y", "YYYY", $format);
        $format = str_replace("m", "MM", $format);
        $format = str_replace("d", "DD", $format);
        return $format;
    }

    static function WebmasterSection($Id)
    {
        return WebmasterSection::find($Id);
    }
    static function SectionCategories($Id)
    {
        return Section::where('webmaster_id', '=', $Id)->where('father_id', '=',
            '0')->orderby('row_no', 'asc')->get();
    }
    
    static function getAddress($latitude, $longitude)
    {
        $address = '';
        if(env("GOOGLE_MAPS_KEY","") !="")
        {
            //google map api url
            $url = "https://maps.google.com/maps/api/geocode/json?latlng=$latitude,$longitude&key=".env("GOOGLE_MAPS_KEY");

            // send http request
            $geocode = file_get_contents($url);
            $json = json_decode($geocode);
            if(!empty($json->results[0]->formatted_address))
                $address = $json->results[0]->formatted_address;
        }
        return $address;
    }
    
    static function getDistance($source, $destination)
    {
        $data = ['distance' => 0, 'duration' => 0];
        if(env('demo_mode') != 'on')
        {
            //google map api url
            $url = "https://maps.google.com/maps/api/distancematrix/json?origins=$source&destinations=$destination&key=".env("GOOGLE_MAPS_KEY");

            // send http request
            $geocode = file_get_contents($url);
            $json = json_decode($geocode);
            if(isset($json->rows[0]->elements[0]->distance))
            {
                $data = ['distance' => $json->rows[0]->elements[0]->distance->value,
                        'duration' => round($json->rows[0]->elements[0]->duration->value/60,2)];
            }
            else
            {
                $data = ['distance' => 0, 'duration' => 0];
            }
        }
        return $data;
    }
    public static function recursiveScan($dir)
    {
        $tree = glob(rtrim($dir, '/') . '/*');
        $files = [];
        if (is_array($tree)) {
            foreach ($tree as $file) {
                if (is_dir($file)) {
                    $files[] = self::recursiveScan($file);
                } elseif (is_file($file)) {
                    $files[] = $file;
                }
            }
            
            return Arr::flatten($files);
        }
    }

    public static function uploadCallRecordings($filename = '', $userId = '')
    {

        $path = env('ftp_upload_path');
        $uploaded_path = env('recordings_upload');
        if ($filename == '') {
            $files = self::recursiveScan($path);
        } else {
            $files[] = $filename;
        }
        if ($userId == '') {
            $userId = "FTP";
        }
        // dd($files);
        try {

            foreach ($files as $file) {
                $fileinfo = pathinfo($file);
                if ($fileinfo['extension'] == "wav") {

//                    $moilenumber = explode("_", $fileinfo['filename'])[1];
                    // $moilenumber = substr(explode("-", $fileinfo['filename'])[2],2,10);
                    $moilenumber = substr(explode("_", $fileinfo['filename'])[3],0,10);
                    // dd($moilenumber);
                    if(strlen($moilenumber) < 10 )
                    {
                        continue;
                    }
                    // dd(strlen($moilenumber), $moilenumber);
                    $moilenumber = preg_replace('/[^0-9]/', '', $moilenumber);
                    $moilenumber = substr($moilenumber,-10);
                    // dd($moilenumber);
                    if ($moilenumber != "") {
                        $mobile = (String) $moilenumber;
                        $CallLog = CallLog::where('phonenumber', $mobile)
                                ->where('call_type', '!=', 'click2call')
                                ->orderBy('id', 'desc')->first();
                        $lead = \App\Models\Lead::find($CallLog->lead_id);
                        // dd($mobile);
                        if(is_dir($uploaded_path . "\\" . $lead->campaign_id) === false)
                        {
                            mkdir($uploaded_path . "\\" . $lead->campaign_id);
                        }
                        $despath = $uploaded_path . "\\" . $lead->campaign_id. "\\" . $fileinfo['basename'];
                        if ($filename == "") {
                            if (copy($file, $despath)) {
                                if (unlink($file)) {
                                    true;
                                }
                            }
                        }

                        $copyfiles[] = $file;
                        if (!is_null($CallLog)) {
                        
                            $CallLog->recording = $fileinfo['basename'];
                            $CallLog->save();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return true;


    }
    
    public static function sendMail($to, $subject, $body)
    {
        $WebmasterSetting = WebmasterSetting::find(1);
        if(!empty($WebmasterSetting))
        {
            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            // Settings
            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8';

            $mail->Host       = $WebmasterSetting->mail_host;    // SMTP server example
            $mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->SMTPSecure = $WebmasterSetting->mail_encryption;
            $mail->Port       = $WebmasterSetting->mail_port;                    // set the SMTP port for the GMAIL server
            $mail->Username   = $WebmasterSetting->mail_username;            // SMTP account username example
            $mail->Password   = $WebmasterSetting->mail_password;            // SMTP account password example

            // Content
            $mail->setFrom($WebmasterSetting->mail_username);   
            $mail->addAddress($to);

            $mail->isHTML(true);                       // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            return $mail->send();
        }
        return false;
    }
    
    public static function sendSms($lead_id, $phoneNumber, $content) 
    {
        if(!empty(Auth::user()->sms_gateway_id))
        {
            $SmsGateway = App\Models\SmsGateway::find(Auth::user()->sms_gateway_id);
            if (!empty($SmsGateway)) {
                $arrResponse = [];
                $url = $SmsGateway->gateway_api_url . '&' . $SmsGateway->mobile_number_param . '=' . $phoneNumber . '&' . $SmsGateway->content_param . '=' . $content;
                $objCurl = \curl_init();
                curl_setopt_array($objCurl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => ($SmsGateway->method == 'GET' ? 0 : 1),
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ]);
                $arrResponse['success'] = curl_exec($objCurl);
                $arrResponse['error'] = curl_error($objCurl);
                curl_close($objCurl);

                $SmsHistory = new \App\Models\SmsHistory;
                $SmsHistory->sms_gateway_id = Auth::user()->sms_gateway_id;
                $SmsHistory->lead_id = $lead_id;
                $SmsHistory->phone_number = $phoneNumber;
                $SmsHistory->sms_content = $content;
                $SmsHistory->response = json_encode($arrResponse);
                $SmsHistory->sent_by = Auth::user()->id;
                $SmsHistory->save();

                return $arrResponse;
            }
            else
            {
                $arrResponse['success'] = false;
                $arrResponse['error'] = "No SMS Gateway";
                return $arrResponse;
            }
        }
        else
        {
                $arrResponse['success'] = false;
                $arrResponse['error'] = "No SMS Gateway";
                return $arrResponse;
        }
    }

    public static function uploadClick2CallRecordings()
    {
        $logs = DB::select("SELECT * FROM leadger_call_logs WHERE recording IS NULL AND click_recording_url IS NOT NULL and duration is not null order by id");
        foreach($logs as $log)
        {
            $click_response = json_decode($log->click_response, true);
            if(empty($click_response['RecordVoice']))
            {
                continue;
            }
            $file_name = Carbon::parse($log->created_at)->format('Ymd_His') . '_' . $log->phonenumber . '.wav';


           $lead = \App\Models\Lead::find($log->lead_id);

			if (!$lead) {
				\Log::error("Lead not found for lead_id: " . $log->lead_id);
				continue; // Skip this iteration if lead is missing
			}

			$uploaded_path = env('recordings_upload');

            $settings = DB::table('settings')
            ->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))
            ->first();
            $full_response = json_decode($settings->full_response, true);
            
            if (!isset($full_response['data']['crm_id'])) {
                \Log::error("CRM ID is not available");
                continue;
            }
            
            $crm_id = $full_response['data']['crm_id'];
           
            $campaign_id = $lead->campaign_id;
            
           $folderPath = public_path('uploads/' . $crm_id . '/' . $campaign_id);

            
            // Create nested folders if they don�t exist
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777, true); // recursive = true
            }
            
            $despath = $folderPath . "\\". $file_name;
            

            $fp = fopen ($despath, 'w+');//This is the file where we save the information
            $ch = \curl_init($click_response['RecordVoice']);//Here is the file we are downloading
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $CallLog = CallLog::find($log->id);
            $CallLog->recording = $file_name;
            $CallLog->save();
        }

        return true;
    }
    
    public static function number_masking($number, $setting = false)
    {
        if(!$setting || in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']) || empty($number))
            return $number;
        return substr($number, 0, 1).'XXXXXXX'.substr($number, -2);
    }

    public static function sendFCM($fields) {

        $url = 'https://fcm.googleapis.com/fcm/send';

        $jsonData = json_encode ( $fields );

        $headers = array (
                'Authorization: key=' . env('firebase_key'),
                'Content-Type: application/json'
        );

        $ch = \curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $jsonData );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec ( $ch );

        curl_close ( $ch );
        
        $firebase_logs = new FirebaseLog;
        $firebase_logs->user_id = $fields['data']['userId'];
        $firebase_logs->fcm_key = $fields['to'];
        $firebase_logs->mobile_number = $fields['data']['mobileNumber'];
        $firebase_logs->lead_id = $fields['data']['leadId'];
        $firebase_logs->payload = $jsonData;
        $firebase_logs->response = $result;
        $firebase_logs->type = 'dialer';
        $firebase_logs->save();
		//dd($result);
    }
    
    /**
     * Click2Call through the provider. The number's daily attempt is counted here, at the moment the call is
     * requested (one atomic check-and-reserve per number and day), and given back when the provider does not
     * start the call - so blocked calls and set-up errors never use up the number's daily limit.
     */
    public static function click2call($callfields, $calllogId)
    {
        $targetNumber = $callfields['b_party_no'] ?? ($callfields['customer_number'] ?? null);
        if ($targetNumber) {
            $check = \App\Services\CallingRestrictionService::canPlaceOutboundCall($targetNumber, \Illuminate\Support\Facades\Auth::user(), 'c2c_gateway', true);
            if (!$check['allowed']) {
                return json_encode([
                    'success' => false,
                    'error' => true,
                    'message' => $check['message'],
                    'reason' => $check['reason'] ?? 'restriction',
                ]);
            }
        }
        $raw = self::click2callProviderRequest($callfields, $calllogId);
        if ($targetNumber && empty(json_decode((string)$raw, true)['success'])) {
            \App\Services\CallingRestrictionService::releaseAttempt($targetNumber);
        }
        return $raw;
    }

    private static function click2callProviderRequest($callfields, $calllogId)
    {
        // Step 1: Authentication Request
        // $fields = array(
        //     "username" => \App\Helpers\TcfgHelper::click2call_username,
        //     "password" => \App\Helpers\TcfgHelper::click2call_password
        // );
        // $json = json_encode($fields);
        // $ch = \curl_init();
    
        // curl_setopt($ch, CURLOPT_URL, "https://cts.myvi.in:8443/Cpaas/api/clicktocall/AuthToken");
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/json',
        //     'Content-Length: ' . strlen($json)
        // ));
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
        // $server_output = curl_exec($ch);
        // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $errors = curl_error($ch);
        // curl_close($ch);
        
        // // Step 2: Handle Authentication Response
        // if (empty($errors)) {
        //     $response = json_decode($server_output, true);
        //     //\Log::info('AuthToken Response:', ['response' => $response]);  // Log the Auth Token response
    
        //     if (isset($response['idToken'])) {
        //         // Step 3: Call Initiation Request
        //         $json = json_encode($callfields);
        //         $ch = \curl_init();
    
        //         curl_setopt($ch, CURLOPT_URL, "https://cts.myvi.in:8443/Cpaas/api/v1/clicktocall/initiate-call");
        //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //         curl_setopt($ch, CURLOPT_POST, 1);
        //         curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //             'Content-Type: application/json',
        //         ));
        //         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
        //         $server_output1 = curl_exec($ch);
        //         $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //         $errors = curl_error($ch);
        //         curl_close($ch);
    
        //         // Step 4: Handle Call Initiation Response
        //         if (empty($errors) ) {
        //             $response = json_decode($server_output1, true);
        //             \Log::info('Initiate Call Response:', ['response' => $response]);  // Log the initiate call response
    
        //             // Temporary Success Check
        //             if (json_last_error() === JSON_ERROR_NONE) { 
        //                 // Treat as success if there's no explicit error in the response
                   
                        
        //                 if (!isset($response['error']) && !isset($response['errors'])) {
        //                     return json_encode([
        //                         'call_log_id' => $calllogId, 
        //                         'success' => true, 
        //                         'message' => 'Call initiated successfully', 
        //                         'response' => $response
        //                     ]);
        //                 } 
        //                 else 
        //                 {
        //                     return json_encode([
        //                         'success' => false, 
        //                         'error' => true, 
        //                         'message' => 'Error indicated in response', 
        //                         'response' => $response
        //                     ]);
        //                 }
        //             } else {
        //                 return json_encode([
        //                     'success' => false, 
        //                     'error' => true, 
        //                     'message' => 'Invalid JSON response from initiate-call', 
        //                     'raw_response' => $server_output1
        //                 ]);
        //             }
        //         } 
        //         else 
        //         {
        //             return json_encode([
        //                 'success' => false, 
        //                 'error' => true, 
        //                 'message' => 'Click2Call initiate-call request failed', 
        //                 'curl_error' => $errors, 
        //                 'httpcode' => $httpcode
        //             ]);
        //         }
        //     } 
        //     else 
        //     {
        //         return json_encode([
        //             'success' => false, 
        //             'error' => true, 
        //             'message' => 'AuthToken request failed', 
        //             'response' => $response
        //         ]);
        //     }
        // } 
        // else 
        // {
        //     return json_encode([
        //         'success' => false, 
        //         'error' => true, 
        //         'message' => 'Click2Call AuthToken request failed', 
        //         'curl_error' => $errors, 
        //         'httpcode' => $httpcode
        //     ]);
        // }


                // (daily attempt limit: checked and counted in click2call() above)

                // Ensure recording is enabled on provider gateway for all outbound Click2Call calls
                $callfields['record'] = 1;
                $callfields['recording'] = 1;
                $callfields['record_call'] = 1;

                $json = json_encode($callfields);
                $ch = \curl_init();
    
                curl_setopt($ch, CURLOPT_URL, "https://sip.telozar.com/api/webrtc/c2c_call");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                ));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
                $server_output1 = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $errors = curl_error($ch);
                curl_close($ch);
    
                // Step 4: Handle Call Initiation Response
                if (empty($errors) ) {
                    $response = json_decode($server_output1, true);
                    \Log::info('Initiate Call Response:', ['response' => $response]);  // Log the initiate call response
    
                    // Temporary Success Check
                    if (json_last_error() === JSON_ERROR_NONE) { 
                        // Treat as success if there's no explicit error in the response
                   
                        
                        if (!isset($response['error']) && !isset($response['errors'])) {
                            $providerCallId = $response['call_id'] ?? $response['callId'] ?? $response['uuid'] ?? ($response['response']['call_id'] ?? ($callfields['ref_id'] ?? null));
                            
                            // Save provider_call_id on dialer_log or call_log if found
                            if ($calllogId && $providerCallId) {
                                try {
                                    $dLog = \App\Models\DialerLog::find($calllogId);
                                    if ($dLog) {
                                        $dLog->provider_call_id = $providerCallId;
                                        $dLog->save();
                                    }
                                } catch (\Exception $e) {}
                                try {
                                    $cLog = \App\Models\CallLog::find($calllogId);
                                    if ($cLog) {
                                        $cLog->provider_call_id = $providerCallId;
                                        $cLog->save();
                                    }
                                } catch (\Exception $e) {}
                            }

                            return json_encode([
                                'call_log_id' => $calllogId, 
                                'success' => true, 
                                'message' => 'Call initiated successfully', 
                                'provider_call_id' => $providerCallId,
                                'response' => $response
                            ]);
                        } 
                        else 
                        {
                            return json_encode([
                                'success' => false, 
                                'error' => true, 
                                'message' => 'Error indicated in response', 
                                'response' => $response
                            ]);
                        }
                    } else {
                        return json_encode([
                            'success' => false, 
                            'error' => true, 
                            'message' => 'Invalid JSON response from initiate-call', 
                            'raw_response' => $server_output1
                        ]);
                    }
                } else {
                    \Log::error('Initiate Call cURL error:', ['error' => $errors, 'httpcode' => $httpcode]);
                    return json_encode([
                        'success' => false,
                        'error' => true,
                        'message' => 'Click2Call gateway connection failed: ' . $errors,
                        'httpcode' => $httpcode
                    ]);
                }
    }
    
    
    
    
    public static function dailyValidations()
    {
        require_once(base_path().'/vendor/ezywing/Helper.php');
        $response = \License\Helpers\Helper::LicenseValidate(env('license'));
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        $did = \App\Models\ObdDID::first();
        if($did) {
            $voucher = App\Models\ObdCreditsHistory::find($did->voucher_id);
            if($voucher) {
                \License\Helpers\Helper::didVoucherDailyCheck($voucher->voucher_code, $full_response['data']['crm_id']);
            } else {
                \App\Models\ObdDID::query()->delete();
            }

        $crm_id = $full_response['data']['crm_id'] ?? null;
        if ($crm_id) {
            try {
                    $url = "https://lc.ametecsindia.com/api/smart31-details/" . $crm_id; // update base URL

                    // --- CURL Call ---
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $response = curl_exec($ch);

                    if (curl_errno($ch)) {
                        throw new \Exception("cURL Error: " . curl_error($ch));
                    }

                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 200 && $response) {
                        $data = json_decode($response);

                    if (!empty($data->vmn_details)) {
                        foreach ($data->vmn_details as $group) {
                            $exists = DB::table('smart31_vmn_numbers')
                                ->where('vmn', $group->vmn)
                                ->whereDate('usage_date', $group->usage_date)
                                ->exists();

                            if ($exists) {
                        // Update existing record
                                \Log::info('smart31 for this date already exists');       
                            } else {
                        // Insert new record
                                $previous = DB::table('smart31_vmn_numbers')
                                    ->where('group_name', $group->group_name)
                                    ->first();

                                $assigned_groups = $previous->assigned_groups ?? null;

                                DB::table('smart31_vmn_numbers')->insert([
                                    'vmn'        => $group->vmn,
                                    'usage_date' => $group->usage_date,
                                    'webrtc'     => $group->webrtc,
                                    'c2c'        => $group->c2c,
                                    'obd'        => $group->obd,
                                    'verified'   => $group->verified,
                                    'group_id'   => $group->group_id,
                                    'type'       => $group->type ?? null,
                                    'created_at' => $group->created_at ?? now(),
                                    'updated_at' => $group->updated_at ?? now(),
                                    'group_name' => $group->group_name,
                                    'assigned_groups' => $group->assigned_groups ,
                                ]);
                            }
                        }
                    }
                    } else {
                        \Log::warning("VMN fetch failed: HTTP $httpCode for CRM $crm_id, Response: $response");
                    }
                } catch (\Exception $e) {
                    \Log::error("VMN fetch failed for CRM {$crm_id}: " . $e->getMessage());
                }
            }

        }

        $setting = Setting::first();
        $setting->daily_check = date('Y-m-d');
        $setting->save();
        
        return $response;
    }

    /**
     * Get the standard Sample CSV content for Leads and Campaign uploads.
     * Matches the structure and sample data used by Campaign Hub / Upload Leads.
     *
     * @return string
     */
    static function getSampleLeadCsvContent()
    {
        return "NAME,DESIGNATION,PHONE-NUMBER,PHONE-NUMBER2,COMPANY,DESCRIPTION,COUNTRY,PIN CODE,CITY,STATE,ADDRESS,EMAIL,WEBSITE,LEAD VALUE,POSITION,TAGS,USER_ID\r\n" .
            "Rajesh,,9154936351,9154936351,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\r\n" .
            "Sai kiran,,9154118359,9154118359,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\r\n" .
            "Madhu,,9154936355,9154936355,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\r\n" .
            "Vinitha ,,9121018749,9121018749,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\r\n" .
            "Ashish,,9154167650,9154167650,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\r\n" .
            "Sravani,,7386566623,,Ametecs,,India,500015,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\r\n" .
            "Akshitha,,7386566634,,Ametecs,,India,500016,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\r\n" .
            "Feroz,,7386566637,,Ametecs,,India,500017,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,";
    }

    /**
     * Return an HTTP response to download the standard sample CSV file.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    static function downloadSampleLeadCsv($filename = 'sample_leads.csv')
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        return response(self::getSampleLeadCsvContent(), 200, $headers);
    }
}

?>
