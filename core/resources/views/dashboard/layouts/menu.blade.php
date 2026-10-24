<?php
// Current Full URL
$fullPagePath = Request::url();
$envAdminCharCount = strlen(env('BACKEND_PATH')) + 1;
$urlAfterRoot = substr($fullPagePath, strpos($fullPagePath, env('BACKEND_PATH')) + $envAdminCharCount);

use App\Models\Lead;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\CompanyAiSetting;

// AI Setting check for sidebar
try {
    $companyAiSetting = CompanyAiSetting::company();
    $isAiEnabledInMenu = $companyAiSetting ? (bool)$companyAiSetting->ai_enabled : true;
} catch (\Exception $e) {
    $isAiEnabledInMenu = true;
}

if (!isset($totalLeads)) {
    try { $totalLeads = Lead::count(); } catch (\Exception $e) { $totalLeads = 0; }
}
if (!isset($pendingFollowupsCount)) {
    try { $pendingFollowupsCount = Lead::openFollowups()->count(); } catch (\Exception $e) { $pendingFollowupsCount = 0; }
}
if (!isset($activeCampaignsCount)) {
    try { $activeCampaignsCount = Campaign::where('status', 1)->count(); } catch (\Exception $e) { $activeCampaignsCount = 0; }
}

/* ==========================================================================
   STRICT EXCLUSIVE SINGLE-SECTION ACTIVE ALGORITHM
   Guarantees EXACTLY ONE top-level sidebar menu item is active at a time.
   ========================================================================== */
$activeSection = '';

if ($urlAfterRoot == '' || $urlAfterRoot == 'home' || strpos($urlAfterRoot, 'dashboard') !== false) {
    $activeSection = 'dashboard';
} elseif (strpos($urlAfterRoot, 'recording') !== false || strpos($urlAfterRoot, 'ai-analysis') !== false || strpos($urlAfterRoot, 'call-transcript') !== false || strpos($urlAfterRoot, 'ai-call-intelligence') !== false || strpos($urlAfterRoot, 'rmi-analysis') !== false) {
    $activeSection = 'recordings';
} elseif (strpos($urlAfterRoot, 'dialer') !== false || strpos($urlAfterRoot, 'flexidial') !== false) {
    $activeSection = 'calling';
} elseif (strpos($urlAfterRoot, 'call-report') !== false || strpos($urlAfterRoot, 'callReport') !== false || strpos($urlAfterRoot, 'campaignStatus') !== false || strpos($urlAfterRoot, 'agentPerformance') !== false || strpos($urlAfterRoot, 'userLogs') !== false || strpos($urlAfterRoot, 'reports') !== false) {
    $activeSection = 'reports';
} elseif (strpos($urlAfterRoot, 'companies') !== false || strpos($urlAfterRoot, 'calling-settings') !== false || strpos($urlAfterRoot, 'disposition') !== false || strpos($urlAfterRoot, 'setting') !== false || strpos($urlAfterRoot, 'webmaster') !== false || strpos($urlAfterRoot, 'users/' . (Auth::user()->id ?? 1) . '/edit') !== false) {
    $activeSection = 'configuration';
} elseif (strpos($urlAfterRoot, 'sources') !== false || strpos($urlAfterRoot, 'segments') !== false || strpos($urlAfterRoot, 'productServices') !== false || strpos($urlAfterRoot, 'statuses') !== false || strpos($urlAfterRoot, 'substatuses') !== false) {
    $activeSection = 'masterdata';
} elseif (strpos($urlAfterRoot, 'click2Call') !== false || strpos($urlAfterRoot, 'click2call') !== false || strpos($urlAfterRoot, 'webrtc') !== false || strpos($urlAfterRoot, 'call-channels') !== false || strpos($urlAfterRoot, 'users') !== false || strpos($urlAfterRoot, 'team') !== false) {
    $activeSection = 'people';
} elseif (strpos($urlAfterRoot, 'campaign') !== false || strpos($urlAfterRoot, 'obd') !== false || strpos($urlAlertRoot ?? '', 'did') !== false || strpos($urlAfterRoot, 'did') !== false) {
    $activeSection = 'campaigns';
} elseif (strpos($urlAfterRoot, 'leads') !== false || strpos($urlAfterRoot, 'responsed') !== false) {
    $activeSection = 'leads';
}

// Single-source boolean flags (Guaranteed mutually exclusive)
$isDashboardActive      = ($activeSection === 'dashboard');
$isLeadsActive          = ($activeSection === 'leads');
$isCampaignsActive      = ($activeSection === 'campaigns');
$isCallingActive        = ($activeSection === 'calling');
$isReportsActive        = ($activeSection === 'reports');
$isRecordingsActive     = ($activeSection === 'recordings');
$isConfigurationActive  = ($activeSection === 'configuration');

// Master Data Active Flags & Routes
$isMasterDataActive     = ($activeSection === 'masterdata');
$isLeadSetupRoute       = (strpos($urlAfterRoot, 'sources') !== false || strpos($urlAfterRoot, 'segments') !== false || strpos($urlAfterRoot, 'productServices') !== false);
$isLeadStatusRoute      = (strpos($urlAfterRoot, 'statuses') !== false || strpos($urlAfterRoot, 'substatuses') !== false);
$isLeadSetupActive      = ($isMasterDataActive && $isLeadSetupRoute);
$isLeadStatusActive     = ($isMasterDataActive && $isLeadStatusRoute);
$isPeopleActive         = ($activeSection === 'people');

// Master Data Permission Checks
$userGroup = Auth::user()->permissionsGroup ?? null;
$canViewProducts   = true;
$canViewSources    = true;
$canViewSegments   = true;
$canViewStatus     = true;
$canViewSubstatus  = true;

if ($userGroup) {
    if (isset($userGroup->products_status) && !$userGroup->products_status) {
        $canViewProducts = false;
    }
    if (isset($userGroup->sources_status) && !$userGroup->sources_status) {
        $canViewSources = false;
    }
    if (isset($userGroup->segments_status) && !$userGroup->segments_status) {
        $canViewSegments = false;
    }
    if (isset($userGroup->statuses_status) && !$userGroup->statuses_status) {
        $canViewStatus = false;
    }
    if (isset($userGroup->substatuses_status) && !$userGroup->substatuses_status) {
        $canViewSubstatus = false;
    }
}

$canViewLeadSetup  = ($canViewProducts || $canViewSources || $canViewSegments);
$canViewLeadStatus = $canViewStatus; // substatuses are managed inside the Status popup (no separate menu)
$canViewMasterData = ($canViewLeadSetup || $canViewLeadStatus);

try {
    $totalLeads = \App\Models\Lead::count();
    $pendingFollowupsCount = \App\Models\Lead::openFollowups()->count();
    $activeCampaignsCount = \App\Models\Campaign::where('status', 1)->count();
    if ($activeCampaignsCount == 0) {
        $activeCampaignsCount = \App\Models\Campaign::count();
    }
} catch (\Exception $e) {
    $totalLeads = 0;
    $pendingFollowupsCount = 0;
    $activeCampaignsCount = 0;
}
?>

<div id="aside" class="app-aside nav-expand sidebar" style="position: fixed; top: 0; left: 0; bottom: 0; width: 240px; height: 100vh; z-index: 1050; background: #071426 !important; overflow: hidden; display: flex; flex-direction: column;">
    <div class="left navside lg-sidebar-inner" layout="column" style="height: 100%; flex: 1 1 0%; min-height: 0; display: flex; flex-direction: column; overflow: hidden; background: #071426 !important; box-sizing: border-box; width: 100%;">
        
        <!-- FIXED TOP: LeadGER Logo (Above Dashboard) -->
        <div class="sidebar-header sidebar-brand lg-sidebar-brand" style="height: 60px; min-height: 60px; max-height: 60px; display: flex; align-items: center; justify-content: flex-start; padding: 0 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); background: #071426; flex-shrink: 0; box-sizing: border-box; width: 100%;">
            <a href="{{ route('adminHome') }}" style="display: flex; align-items: center; text-decoration: none; width: 100%; height: 100%;">
                <img src="{{ asset('assets/dashboard/images/leadger-logo-sidebar.png') }}" alt="LeadGER - Generate, Engage &amp; Retain" style="height: 57px; width: auto; max-width: 100%; object-fit: contain; display: block;">
            </a>
        </div>

        <!-- SCROLLABLE AREA: Navigation List -->
        <div class="sidebar-nav sidebar-navigation lg-sidebar-navigation" style="background: #071426 !important;">
            <ul class="lg-nav-list">
            
            <!-- 1. DASHBOARD -->
            <li class="lg-nav-item">
                <a href="{{ route('adminHome') }}" class="lg-menu-row {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    <span class="lg-menu-label">Dashboard</span>
                </a>
            </li>

            <!-- 2. LEADS -->
            <li class="lg-nav-item has-sub {{ $isLeadsActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isLeadsActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                    </span>
                    <span class="lg-menu-label">Leads</span>
                    <span class="lg-nav-badge" style="background: rgba(255,255,255,0.18); color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; margin-left: auto; margin-right: 6px;">{{ $pendingFollowupsCount ?? 0 }}</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('leadsCreate') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'leads/create') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span class="lg-sub-text">Upload Leads</span>
                        </div>
                    </a>
                    <a href="{{ route('leads') }}" class="lg-sub-link {{ ((strpos($urlAfterRoot, 'leads') !== false || $urlAfterRoot == 'leads') && strpos($urlAfterRoot, 'leads/create') === false && !Request::get('status')) ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </span>
                            <span class="lg-sub-text">Lead List</span>
                        </div>
                        <span style="font-size: 11px; font-weight: 700; color: #94A3B8; margin-left: auto; white-space: nowrap; flex-shrink: 0;">{{ number_format($totalLeads ?? 0) }}</span>
                    </a>

                    <a href="{{ route('leads') }}?filter=followup" class="lg-sub-link {{ Request::get('filter') == 'followup' ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="16" cy="16" r="5"/><polyline points="16 14 16 16 18 16"/></svg>
                            </span>
                            <span class="lg-sub-text">Follow-ups</span>
                        </div>
                        <span style="font-size: 11px; font-weight: 700; color: #38BDF8; margin-left: auto; white-space: nowrap; flex-shrink: 0;">{{ $pendingFollowupsCount ?? 0 }}</span>
                    </a>
                </div>
            </li>

            <!-- 3. CAMPAIGNS -->
            <li class="lg-nav-item has-sub {{ $isCampaignsActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isCampaignsActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                    </span>
                    <span class="lg-menu-label">Campaign Hub</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('campaigns') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'campaign') !== false && strpos($urlAfterRoot, 'campaigns/create') === false && strpos($urlAfterRoot, 'campaigns/transfer-accounts') === false && strpos($urlAfterRoot, 'campaigns/auto-distribute') === false && strpos($urlAfterRoot, 'campaignStatus') === false) ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </span>
                            <span class="lg-sub-text">Campaign List</span>
                        </div>
                        <span style="font-size: 10.5px; font-weight: 700; color: #F59E0B; margin-left: auto; white-space: nowrap; flex-shrink: 0;">{{ $activeCampaignsCount ?? 0 }} active</span>
                    </a>
                    <a href="{{ route('campaignsCreate') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'campaigns/create') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            </span>
                            <span class="lg-sub-text">Create Campaign</span>
                        </div>
                    </a>
                    <a href="{{ route('campaignsTransferAccounts') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'campaigns/transfer-accounts') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 4 21 8 17 12"/><line x1="3" y1="8" x2="21" y2="8"/><polyline points="7 20 3 16 7 12"/><line x1="21" y1="16" x2="3" y2="16"/></svg>
                            </span>
                            <span class="lg-sub-text">Transfer Leads</span>
                        </div>
                    </a>
                    <a href="{{ route('campaignsAutoDistribute') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'campaigns/auto-distribute') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                            </span>
                            <span class="lg-sub-text">Auto Distribute</span>
                        </div>
                    </a>
                    <a href="{{ route('obdDid') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'did') !== false) ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <span class="lg-sub-text">Add Voucher</span>
                        </div>
                    </a>
                </div>
            </li>

            <!-- 4. CALLING -->
            <li class="lg-nav-item has-sub {{ $isCallingActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isCallingActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v-3a9 9 0 0 1 18 0v3"/><path d="M21 15a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-5H5a2 2 0 0 0-2 2z"/></svg>
                    </span>
                    <span class="lg-menu-label">Calling</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('dialer') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'dialer') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <span class="lg-sub-text">Dialing</span>
                        </div>
                    </a>
                </div>
            </li>

            <!-- 5. CONFIGURATION -->
            <li class="lg-nav-item has-sub {{ $isConfigurationActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isConfigurationActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    <span class="lg-menu-label">Configuration</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('callingSettings') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'calling-settings') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <span class="lg-sub-text">Call Setup</span>
                        </div>
                    </a>
                    <a href="{{ route('usersEdit', Auth::user()->id ?? 1) }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'users/' . (Auth::user()->id ?? 1) . '/edit') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <span class="lg-sub-text">Profile</span>
                        </div>
                    </a>
                    @if (\App\Support\Tenancy::isSuperAdmin(Auth::user()))
                    <a href="{{ route('companies') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'companies') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="3" y1="13" x2="21" y2="13"/></svg>
                            </span>
                            <span class="lg-sub-text">Companies</span>
                        </div>
                    </a>
                    @endif
                </div>
            </li>

            <!-- 6. MASTER DATA (Lead Setup + Lead Status) -->
            @if ($canViewMasterData)
            <li class="lg-nav-item has-sub {{ $isMasterDataActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isMasterDataActive ? 'active' : '' }}"
                     aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}"
                     role="button" tabindex="0">
                    <span class="lg-menu-icon">
                        <!-- Database / Master Data icon -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    </span>
                    <span class="lg-menu-label">Master Data</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <!-- 6a. LEAD SETUP (Products, Sources, Segments) -->
                    @if ($canViewLeadSetup)
                    <div class="lg-nested-channel-group {{ $isLeadSetupRoute ? 'open' : '' }}" id="lgSidebarLeadSetupGroup">
                        <div class="lg-sub-link lg-nested-channel-header {{ $isLeadSetupActive ? 'active-parent' : '' }}" 
                             id="lgSidebarLeadSetupHeader"
                             onclick="toggleSidebarLeadSetup(event)"
                             role="button" tabindex="0">
                            <div class="lg-sub-left">
                                <span class="lg-sub-icon">
                                    <!-- Settings / Gear circle icon -->
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                </span>
                                <span class="lg-sub-text">Lead Setup</span>
                            </div>
                            <span class="lg-nested-arrow" id="lgSidebarLeadSetupArrow">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </div>

                        <!-- Nested Items: Products, Sources, Segments -->
                        <div class="lg-nested-channel-list" id="lgSidebarLeadSetupList">
                            @if ($canViewProducts)
                            <a href="{{ route('productServices') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'productServices') !== false ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                    </span>
                                    <span class="lg-sub-text">Products</span>
                                </div>
                            </a>
                            @endif

                            @if ($canViewSources)
                            <a href="{{ route('sources') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'sources') !== false ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                                    </span>
                                    <span class="lg-sub-text">Sources</span>
                                </div>
                            </a>
                            @endif

                            @if ($canViewSegments)
                            <a href="{{ route('segments') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'segments') !== false ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                                    </span>
                                    <span class="lg-sub-text">Segments</span>
                                </div>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- 6b. LEAD STATUS (Status, Substatus) -->
                    @if ($canViewLeadStatus)
                    <div class="lg-nested-channel-group {{ $isLeadStatusRoute ? 'open' : '' }}" id="lgSidebarLeadStatusGroup">
                        <div class="lg-sub-link lg-nested-channel-header {{ $isLeadStatusActive ? 'active-parent' : '' }}" 
                             id="lgSidebarLeadStatusHeader"
                             onclick="toggleSidebarLeadStatus(event)"
                             role="button" tabindex="0">
                            <div class="lg-sub-left">
                                <span class="lg-sub-icon">
                                    <!-- Check-circle icon -->
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </span>
                                <span class="lg-sub-text">Lead Status</span>
                            </div>
                            <span class="lg-nested-arrow" id="lgSidebarLeadStatusArrow">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </div>

                        <!-- Nested Items: Status (substatuses are managed in the Status popup) -->
                        <div class="lg-nested-channel-list" id="lgSidebarLeadStatusList">
                            @if ($canViewStatus)
                            <a href="{{ route('statuses') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'statuses') !== false && strpos($urlAfterRoot, 'substatuses') === false) ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <!-- List icon -->
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                    </span>
                                    <span class="lg-sub-text">Status</span>
                                </div>
                            </a>
                            @endif

                        </div>
                    </div>
                    @endif
                </div>
            </li>
            @endif

            <!-- 7. PEOPLE -->
            <li class="lg-nav-item has-sub {{ $isPeopleActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isPeopleActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <span class="lg-menu-label">People</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('users') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'users') !== false && strpos($urlAfterRoot, 'users/' . (Auth::user()->id ?? 1) . '/edit') === false && strpos($urlAfterRoot, 'team') === false) ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                            </span>
                            <span class="lg-sub-text">Staff</span>
                        </div>
                    </a>
                    <a href="{{ route('teams') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'team') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span class="lg-sub-text">Teams</span>
                        </div>
                    </a>
                    <!-- Call channels nested expandable menu -->
                    @php
                        $isCallChannelsRoute = (
                            strpos($urlAfterRoot, 'call-channels') !== false ||
                            strpos($urlAfterRoot, 'webrtc') !== false ||
                            strpos($urlAfterRoot, 'click2call') !== false ||
                            strpos($urlAfterRoot, 'click2Call') !== false
                        );
                    @endphp
                    <div class="lg-nested-channel-group {{ $isCallChannelsRoute ? 'open' : '' }}" id="lgSidebarCallChannelsGroup">
                        <div class="lg-sub-link lg-nested-channel-header {{ strpos($urlAfterRoot, 'call-channels') !== false ? 'active' : '' }}" 
                             id="lgSidebarCallChannelsHeader"
                             onclick="toggleSidebarCallChannels(event)">
                            <div class="lg-sub-left">
                                <span class="lg-sub-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                                </span>
                                <span class="lg-sub-text">Call channels</span>
                            </div>
                            <span class="lg-nested-arrow" id="lgSidebarChannelsArrow">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </div>

                        <!-- Nested Submenu Channels: Clean Direct Navigation Links -->
                        <div class="lg-nested-channel-list" id="lgSidebarChannelList">
                            <!-- 1. WebRTC Navigation Link -->
                            <a href="{{ route('webrtc') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'webrtc') !== false ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                                    </span>
                                    <span class="lg-sub-text">WebRTC</span>
                                </div>
                            </a>

                            <!-- 2. Click2Call Navigation Link -->
                            <a href="{{ route('click2Call') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'click2Call') !== false || strpos($urlAfterRoot, 'click2call') !== false) ? 'active' : '' }}">
                                <div class="lg-sub-left">
                                    <span class="lg-sub-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    </span>
                                    <span class="lg-sub-text">Click2Call</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </li>

            <!-- 8. REPORTS -->
            <li class="lg-nav-item has-sub {{ $isReportsActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isReportsActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    </span>
                    <span class="lg-menu-label">Reports</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('callReport') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'call-report') !== false || strpos($urlAfterRoot, 'callReport') !== false) ? 'active' : '' }}" title="Call Report">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </span>
                            <span class="lg-sub-text">Call Report</span>
                        </div>
                    </a>
                    <a href="{{ route('callLogs') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'callLogs') !== false || strpos($urlAfterRoot, 'call-history') !== false) ? 'active' : '' }}" title="Call Logs">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </span>
                            <span class="lg-sub-text">Call Logs</span>
                        </div>
                    </a>
                    <a href="{{ route('agentPerformance') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'agentPerformance') !== false || strpos($urlAfterRoot, 'userLogs') !== false) ? 'active' : '' }}" title="Agent Performance">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><path d="M19 11l1.5 1.5L23 9"/></svg>
                            </span>
                            <span class="lg-sub-text">Agent Performance</span>
                        </div>
                    </a>
                    <a href="{{ route('campaignStatus') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'campaignStatus') !== false ? 'active' : '' }}" title="Campaigns">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                            </span>
                            <span class="lg-sub-text">Campaigns</span>
                        </div>
                    </a>
                    @if((int)(Auth::user()->permissionsGroup->inbound_calls_status ?? 0))
                    <a href="{{ route('inboundCalls') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'inbound-calls') !== false ? 'active' : '' }}" title="Inbound Calls">
                        <div class="lg-sub-left">
                            <span class="lg-sub-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 2 16 8 22 8"/><line x1="22" y1="2" x2="16" y2="8"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <span class="lg-sub-text">Inbound Calls</span>
                        </div>
                    </a>
                    @endif
                </div>
            </li>

            <!-- 9. RECORDINGS -->
            <li class="lg-nav-item has-sub {{ $isRecordingsActive ? 'open' : '' }}">
                <div class="lg-menu-row lg-menu-toggle {{ $isRecordingsActive ? 'active' : '' }}">
                    <span class="lg-menu-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v4M8 21h8"/></svg>
                    </span>
                    <span class="lg-menu-label">Recordings</span>
                    <span class="lg-arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </div>
                <div class="lg-submenu">
                    <a href="{{ route('recordings') }}" class="lg-sub-link {{ (strpos($urlAfterRoot, 'recording') !== false && strpos($urlAfterRoot, 'rmi-analysis') === false) ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-text">Call Recordings</span>
                        </div>
                    </a>
                    <a href="{{ route('rmiAnalysis') }}" class="lg-sub-link {{ strpos($urlAfterRoot, 'rmi-analysis') !== false ? 'active' : '' }}">
                        <div class="lg-sub-left">
                            <span class="lg-sub-text">RMI Analysis</span>
                        </div>
                    </a>
                </div>
            </li>

            </ul>
        </div>

        <!-- FIXED FOOTER -->
        <div class="sidebar-footer lg-sidebar-footer" style="background: #071426 !important;">
            <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.05em;"><span style="color: #FFFFFF;">Lead</span><span style="color: var(--lg-accent);">GER</span></div>
            <div style="font-size: 9px; color: #64748B; margin-top: 2px;">Generate, Engage &amp; Retain</div>
        </div>

    </div>
</div>

<!-- Interactive Expand/Collapse Logic -->
<script>
function initSidebarNav() {
    const navToggles = document.querySelectorAll('.lg-menu-toggle');
    const navContainer = document.querySelector('.sidebar-nav') || document.querySelector('.sidebar-navigation');

    navToggles.forEach(toggle => {
        if (toggle.getAttribute('data-nav-inited') === 'true') return;
        toggle.setAttribute('data-nav-inited', 'true');
        
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const parentItem = this.closest('.lg-nav-item');
            if (parentItem) {
                const isOpening = !parentItem.classList.contains('open');
                parentItem.classList.toggle('open');

                if (isOpening && navContainer) {
                    setTimeout(() => {
                        const submenu = parentItem.querySelector('.lg-submenu');
                        if (submenu) {
                            const containerRect = navContainer.getBoundingClientRect();
                            const submenuRect = submenu.getBoundingClientRect();

                            if (submenuRect.bottom > containerRect.bottom) {
                                const scrollDistance = submenuRect.bottom - containerRect.bottom + 16;
                                navContainer.scrollBy({
                                    top: scrollDistance,
                                    behavior: 'smooth'
                                });
                            }
                        }
                    }, 120);
                }
            }
        });
    });
}

function toggleSidebarAi(btn) {
    const toggle = btn.querySelector('.lg-ai-toggle');
    if (toggle) {
        toggle.classList.toggle('on');
    }
}

function toggleSidebarLeadSetup(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const group = document.getElementById('lgSidebarLeadSetupGroup');
    if (group) {
        group.classList.toggle('open');
    }
}

function toggleSidebarLeadStatus(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const group = document.getElementById('lgSidebarLeadStatusGroup');
    if (group) {
        group.classList.toggle('open');
    }
}

function toggleSidebarCallChannels(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const group = document.getElementById('lgSidebarCallChannelsGroup');
    if (group) {
        group.classList.toggle('open');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarNav);
} else {
    initSidebarNav();
}
</script>
