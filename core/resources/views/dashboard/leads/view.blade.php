@extends('dashboard.layouts.master')
@section('title', ($Lead->name ?: 'Lead Details') . ' - LeadGER')

@push("after-styles")
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Page Canvas */
.lg-lead-details-canvas {
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    padding: 24px 32px 48px 32px;
    font-family: var(--lg-font-sans);
    color: #0F172A;
    font-size: 14px;
    line-height: 1.5;
}

/* Breadcrumb */
.lg-lead-crumbs {
    font-size: 13px;
    color: #64748B;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lg-lead-crumb-link {
    color: var(--lg-accent-hover);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.15s ease;
}
.lg-lead-crumb-link:hover {
    color: var(--lg-accent);
    text-decoration: underline;
}
.lg-lead-crumb-sep {
    color: #94A3B8;
}
.lg-lead-crumb-current {
    color: #64748B;
    font-weight: 500;
}

/* Header Row */
.lg-lead-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.lg-lead-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.lg-lead-avatar-tile {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #E6F6F4;
    border: 1px solid rgba(15, 118, 110, 0.2);
    color: var(--lg-accent-hover);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lg-lead-avatar-tile svg {
    width: 22px;
    height: 22px;
}
.lg-lead-title-box h1 {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    letter-spacing: -0.01em;
    line-height: 1.25;
}
.lg-lead-title-box p {
    font-size: 13px;
    color: #64748B;
    margin: 3px 0 0 0;
    font-weight: 500;
}

/* Header Buttons */
.lg-lead-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.lg-lead-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}
.lg-lead-btn-white {
    background: #FFFFFF;
    border-color: #E2E8F0;
    color: #334155;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.lg-lead-btn-white:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: #0F172A;
}
.lg-lead-btn-call {
    background: #059669;
    border-color: #059669;
    color: #FFFFFF;
    box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
}
.lg-lead-btn-call:hover {
    background: #047857;
    border-color: #047857;
    color: #FFFFFF;
}

/* White Panel Container */
.lg-lead-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.lg-lead-panel-mt {
    margin-top: 24px;
}

/* Tabs */
.lg-lead-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #E2E8F0;
    padding: 0 24px;
    overflow-x: auto;
}
.lg-lead-tab-link {
    padding: 14px 12px;
    font-size: 14px;
    font-weight: 600;
    color: #64748B;
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    transition: all 0.15s ease;
}
.lg-lead-tab-link:hover {
    color: var(--lg-accent-hover);
}
.lg-lead-tab-link.active {
    color: var(--lg-accent-hover);
    border-bottom-color: var(--lg-accent-hover);
}

/* Panel Body */
.lg-lead-panel-body {
    padding: 24px 28px 32px 28px;
}

/* Two Column Info Grid */
.lg-lead-info-columns {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 40px;
}
.lg-lead-info-col h2 {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 20px 0;
    letter-spacing: -0.01em;
}

/* Key Value Grid */
.lg-lead-kv-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
}
.lg-lead-kv-item {
    display: flex;
    flex-direction: column;
}
.lg-lead-kv-label {
    font-size: 12.5px;
    color: #64748B;
    font-weight: 500;
    margin-bottom: 3px;
}
.lg-lead-kv-value {
    font-size: 14.5px;
    color: #0F172A;
    font-weight: 700;
    word-break: break-word;
}
.lg-lead-kv-value.mono {
    font-family: 'IBM Plex Mono', ui-monospace, monospace;
    font-weight: 600;
    font-size: 14px;
}

/* Status Badges */
.lg-lead-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 24px;
    padding: 0 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.lg-lead-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.lg-lead-badge.ok {
    background: #ECFDF5;
    color: #047857;
    border: 1px solid #A7F3D0;
}
.lg-lead-badge.ok .lg-lead-badge-dot {
    background: #10B981;
}
.lg-lead-badge.warn {
    background: #FFFBEB;
    color: #B45309;
    border: 1px solid #FDE68A;
}
.lg-lead-badge.warn .lg-lead-badge-dot {
    background: #F59E0B;
}
.lg-lead-badge.live {
    background: #EFF6FF;
    color: var(--lg-accent-hover);
    border: 1px solid #BFDBFE;
}
.lg-lead-badge.live .lg-lead-badge-dot {
    background: var(--lg-accent);
}
.lg-lead-badge.bad {
    background: #FEF2F2;
    color: #B91C1C;
    border: 1px solid #FECACA;
}
.lg-lead-badge.bad .lg-lead-badge-dot {
    background: #EF4444;
}

/* Recent Calls Section Header */
.lg-recent-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    margin-top: 28px;
}
.lg-recent-title-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-recent-bar {
    width: 3px;
    height: 16px;
    border-radius: 2px;
    background: var(--lg-accent-hover);
    flex-shrink: 0;
}
.lg-recent-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}
.lg-btn-sm {
    min-height: 32px;
    padding: 0 12px;
    font-size: 13px;
    border-radius: 6px;
}

/* Tables */
.lg-table-wrap {
    overflow-x: auto;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.lg-custom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.lg-custom-table th {
    background: #F8FAFC;
    color: #64748B;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    padding: 12px 18px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}
.lg-custom-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
}
.lg-custom-table tbody tr:last-child td {
    border-bottom: 0;
}
.lg-custom-table tbody tr:hover td {
    background-color: #F8FAFC;
}
.lg-play-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    color: #0F172A;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.lg-play-btn:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
}

/* Audio Player Overlay */
#audioPlayerOverlay {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1050;
    background: #0F172A;
    color: #FFFFFF;
    border-radius: 12px;
    padding: 14px 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
    display: none;
    align-items: center;
    gap: 16px;
    max-width: 480px;
    border: 1px solid #334155;
}

/* Responsive */
@media (max-width: 991px) {
    .lg-lead-info-columns {
        grid-template-columns: minmax(0, 1fr);
        gap: 28px;
    }
    .lg-lead-details-canvas {
        padding: 16px;
    }
}
@media (max-width: 640px) {
    .lg-lead-kv-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
@endpush

@section('content')
<div class="lg-lead-details-canvas">

    <!-- 1. Breadcrumb -->
    <div class="lg-lead-crumbs">
        <a href="{{ !empty($Lead->campaign_id) ? route('campaignsView', $Lead->campaign_id) : route('leads') }}" class="lg-lead-crumb-link">Leads</a>
        <span class="lg-lead-crumb-sep">/</span>
        <span class="lg-lead-crumb-current">{{ $Lead->name ?: 'Lead Details' }}</span>
    </div>

    <!-- 2. Header Row -->
    <div class="lg-lead-header">
        <div class="lg-lead-header-left">
            <div class="lg-lead-avatar-tile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7.5" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div class="lg-lead-title-box">
                <h1>{{ $Lead->name ?: 'Unnamed Lead' }}</h1>
                <p>{{ $Lead->id }} · {{ $productName }} · {{ $Campaign ? $Campaign->name : 'General' }}</p>
            </div>
        </div>

        <div class="lg-lead-header-actions">
            <button type="button" class="lg-lead-btn lg-lead-btn-white" onclick="openScheduleFollowupModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Schedule follow-up</span>
            </button>

            <button type="button" class="lg-lead-btn lg-lead-btn-white" onclick="openReassignModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Reassign</span>
            </button>

            <button type="button" class="lg-lead-btn lg-lead-btn-call" onclick="initiateCall('{{ $Lead->phonenumber }}')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span>Call {{ $firstName }}</span>
            </button>
        </div>
    </div>

    <!-- 3. Main Details Container -->
    <section class="lg-lead-panel">
        <!-- Tabs Navigation -->
        <nav class="lg-lead-tabs" aria-label="Lead Details Tabs">
            <button type="button" class="lg-lead-tab-link active" id="tabBtnOverview" onclick="switchLeadTab('overview')">
                Overview
            </button>
            <button type="button" class="lg-lead-tab-link" id="tabBtnHistory" onclick="switchLeadTab('history')">
                Call history ({{ count($CallLogs) }})
            </button>
        </nav>

        <!-- Tab 1: Overview Panel -->
        <div class="lg-lead-panel-body" id="tabPaneOverview">
            <div class="lg-lead-info-columns">

                <!-- Left: Customer Information -->
                <div class="lg-lead-info-col">
                    <h2>Customer information</h2>
                    <div class="lg-lead-kv-grid">
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Name</span>
                            <span class="lg-lead-kv-value">{{ $Lead->name ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Mobile</span>
                            <span class="lg-lead-kv-value mono">{{ $Lead->phonenumber ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Alternate mobile</span>
                            <span class="lg-lead-kv-value mono">{{ $Lead->phonenumber_2 ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">City</span>
                            <span class="lg-lead-kv-value">{{ $Lead->city ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Email</span>
                            <span class="lg-lead-kv-value">{{ $Lead->email ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Product</span>
                            <span class="lg-lead-kv-value">{{ $productName ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Campaign</span>
                            <span class="lg-lead-kv-value">{{ $Campaign ? $Campaign->name : '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Assigned agent</span>
                            <span class="lg-lead-kv-value">{{ $assignedAgentName ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Lead source</span>
                            <span class="lg-lead-kv-value">{{ $Lead->source ?: '—' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Sales Information -->
                <div class="lg-lead-info-col">
                    <h2>Sales information</h2>
                    <div class="lg-lead-kv-grid">
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Current status</span>
                            <div class="lg-lead-kv-value">
                                <span class="lg-lead-badge {{ $statusBadgeClass }}">
                                    <span class="lg-lead-badge-dot"></span>
                                    <span>{{ $currentStatusName }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Disposition</span>
                            <span class="lg-lead-kv-value">{{ $dispositionName ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Sub-disposition</span>
                            <span class="lg-lead-kv-value">{{ $subDispositionName ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Last call</span>
                            <span class="lg-lead-kv-value">{{ $lastCallText ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Next follow-up</span>
                            <span class="lg-lead-kv-value">{{ $nextFollowupText ?: '—' }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Number of calls</span>
                            <span class="lg-lead-kv-value">{{ count($CallLogs) }}</span>
                        </div>
                        <div class="lg-lead-kv-item">
                            <span class="lg-lead-kv-label">Total talk time</span>
                            <span class="lg-lead-kv-value mono">{{ $totalTalkTimeText }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tab 2: Full Call History Panel -->
        <div class="lg-lead-panel-body" id="tabPaneHistory" style="display:none; padding: 0;">
            <div class="lg-table-wrap" style="border:0; border-radius:0; box-shadow:none;">
                <table class="lg-custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Agent</th>
                            <th>Duration</th>
                            <th>Disposition</th>
                            <th>Sub-disposition</th>
                            <th>Remarks</th>
                            <th class="text-right">Recording</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($CallLogs as $call)
                            @php
                                $cStatus = $call->status_name ?: 'Connected';
                                $csLower = strtolower($cStatus);
                                $cBadge = 'ok';
                                if (strpos($csLower, 'follow') !== false || strpos($csLower, 'pending') !== false) {
                                    $cBadge = 'warn';
                                } elseif (strpos($csLower, 'new') !== false || strpos($csLower, 'unactioned') !== false) {
                                    $cBadge = 'live';
                                } elseif (strpos($csLower, 'not') !== false || strpos($csLower, 'reject') !== false || strpos($csLower, 'dnd') !== false) {
                                    $cBadge = 'bad';
                                }
                                $durationFormatted = !empty($call->duration) && is_numeric($call->duration) ? sprintf('%02d:%02d', floor($call->duration / 60), $call->duration % 60) : '—';
                                $recUrl = $call->click_recording_url ?: (!empty($call->recording) ? asset('uploads/recordings/' . $call->recording) : '');
                            @endphp
                            <tr>
                                <td>{{ date('d-M-Y', strtotime($call->created_at)) }}</td>
                                <td>{{ date('h:i A', strtotime($call->created_at)) }}</td>
                                <td>{{ $call->called_by ?: '—' }}</td>
                                <td class="mono font-weight-bold">{{ $durationFormatted }}</td>
                                <td>
                                    <span class="lg-lead-badge {{ $cBadge }}">
                                        <span class="lg-lead-badge-dot"></span>
                                        {{ $cStatus }}
                                    </span>
                                </td>
                                <td>{{ $call->remarks ?: '—' }}</td>
                                <td style="max-width:280px; white-space:normal;">{{ $call->remarks ?: '—' }}</td>
                                <td class="text-right">
                                    @if(!empty($recUrl))
                                        <button type="button" class="lg-play-btn" onclick="playAudio('{{ $recUrl }}')">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                            Play
                                        </button>
                                    @else
                                        <span class="text-muted" style="font-size:12px;">No recording</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div style="padding:20px;">No call history records found for this lead.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <!-- 4. Recent Calls (Visible when Overview tab is active) -->
    <div id="sectionRecentCalls">
        <div class="lg-recent-header">
            <div class="lg-recent-title-wrap">
                <span class="lg-recent-bar"></span>
                <h2 class="lg-recent-title">Recent calls</h2>
            </div>
            <button type="button" class="lg-lead-btn lg-lead-btn-white lg-btn-sm" onclick="switchLeadTab('history')">
                Full history
            </button>
        </div>

        <div class="lg-table-wrap">
            <table class="lg-custom-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Agent</th>
                        <th>Duration</th>
                        <th>Disposition</th>
                        <th>Sub-disposition</th>
                        <th>Remarks</th>
                        <th class="text-right">Recording</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($CallLogs->take(3) as $call)
                        @php
                            $cStatus = $call->status_name ?: 'Connected';
                            $csLower = strtolower($cStatus);
                            $cBadge = 'ok';
                            if (strpos($csLower, 'follow') !== false || strpos($csLower, 'pending') !== false) {
                                $cBadge = 'warn';
                            } elseif (strpos($csLower, 'new') !== false || strpos($csLower, 'unactioned') !== false) {
                                $cBadge = 'live';
                            } elseif (strpos($csLower, 'not') !== false || strpos($csLower, 'reject') !== false || strpos($csLower, 'dnd') !== false) {
                                $cBadge = 'bad';
                            }
                            $durationFormatted = !empty($call->duration) && is_numeric($call->duration) ? sprintf('%02d:%02d', floor($call->duration / 60), $call->duration % 60) : '—';
                            $recUrl = $call->click_recording_url ?: (!empty($call->recording) ? asset('uploads/recordings/' . $call->recording) : '');
                        @endphp
                        <tr>
                            <td>{{ date('d-M-Y', strtotime($call->created_at)) }}</td>
                            <td>{{ date('h:i A', strtotime($call->created_at)) }}</td>
                            <td>{{ $call->called_by ?: '—' }}</td>
                            <td class="mono font-weight-bold">{{ $durationFormatted }}</td>
                            <td>
                                <span class="lg-lead-badge {{ $cBadge }}">
                                    <span class="lg-lead-badge-dot"></span>
                                    {{ $cStatus }}
                                </span>
                            </td>
                            <td>{{ $call->remarks ?: 'Product Explained' }}</td>
                            <td style="max-width:280px; white-space:normal;">{{ $call->remarks ?: '—' }}</td>
                            <td class="text-right">
                                @if(!empty($recUrl))
                                    <button type="button" class="lg-play-btn" onclick="playAudio('{{ $recUrl }}')">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                        </svg>
                                        Play
                                    </button>
                                @else
                                    <span class="text-muted" style="font-size:12px;">No recording</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No recent calls recorded for this lead.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Audio Player Floating Bar -->
<div id="audioPlayerOverlay">
    <div style="display:flex; align-items:center; gap:10px; flex:1;">
        <i class="fa fa-play-circle text-success" style="font-size:20px;"></i>
        <audio id="audioPlayerElem" controls style="height:36px; outline:none; max-width:320px;"></audio>
    </div>
    <button type="button" onclick="closeAudioPlayer()" style="background:transparent; border:0; color:#94A3B8; font-size:18px; cursor:pointer;">
        &times;
    </button>
</div>

<!-- Schedule Followup Modal -->
<div class="modal fade" id="modalScheduleFollowup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px; border:1px solid #E2E8F0; overflow:hidden;">
            <div class="modal-header" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; padding:16px 20px;">
                <h5 class="modal-title font-weight-bold" style="font-size:16px; color:#0F172A;">Schedule Follow-up</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('leadsUpdate', $Lead->id) }}" method="POST">
                @csrf
                <div class="modal-body" style="padding:20px;">
                    <div class="form-group mb-3">
                        <label style="font-size:13px; font-weight:600; color:#475569;">Follow-up Date & Time</label>
                        <input type="datetime-local" name="follow_up_on" class="form-control" style="border-radius:8px; border:1px solid #CBD5E1; height:40px;" value="{{ $Lead->follow_up_on ? date('Y-m-d\TH:i', strtotime($Lead->follow_up_on)) : '' }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:13px; font-weight:600; color:#475569;">Remarks / Note</label>
                        <textarea name="remarks" class="form-control" rows="3" style="border-radius:8px; border:1px solid #CBD5E1;" placeholder="Enter follow-up details...">{{ $Lead->remarks }}</textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background:#F8FAFC; border-top:1px solid #E2E8F0; padding:12px 20px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius:6px; font-size:13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--lg-accent-hover); border-color:var(--lg-accent-hover); border-radius:6px; font-size:13px;">Save Follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reassign Modal -->
<div class="modal fade" id="modalReassign" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px; border:1px solid #E2E8F0; overflow:hidden;">
            <div class="modal-header" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; padding:16px 20px;">
                <h5 class="modal-title font-weight-bold" style="font-size:16px; color:#0F172A;">Reassign Lead</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('leadsUpdate', $Lead->id) }}" method="POST">
                @csrf
                <div class="modal-body" style="padding:20px;">
                    <div class="form-group mb-0">
                        <label style="font-size:13px; font-weight:600; color:#475569;">Select Agent</label>
                        <select name="assigned_to" class="form-control" style="border-radius:8px; border:1px solid #CBD5E1; height:40px;">
                            <option value="">-- Unassigned --</option>
                            @foreach($Staff as $stf)
                                <option value="{{ $stf->id }}" {{ $Lead->assigned_to == $stf->id ? 'selected' : '' }}>{{ $stf->name }} ({{ $stf->email }})</option>
                            @endforeach
                            @foreach($SalesPersons as $sp)
                                <option value="{{ $sp->id }}" {{ $Lead->assigned_to == $sp->id ? 'selected' : '' }}>{{ $sp->name }} (Sales Person)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="background:#F8FAFC; border-top:1px solid #E2E8F0; padding:12px 20px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius:6px; font-size:13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--lg-accent-hover); border-color:var(--lg-accent-hover); border-radius:6px; font-size:13px;">Reassign Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push("after-scripts")
<script>
function switchLeadTab(tab) {
    if (tab === 'overview') {
        $('#tabBtnOverview').addClass('active');
        $('#tabBtnHistory').removeClass('active');
        $('#tabPaneOverview').show();
        $('#tabPaneHistory').hide();
        $('#sectionRecentCalls').show();
    } else if (tab === 'history') {
        $('#tabBtnOverview').removeClass('active');
        $('#tabBtnHistory').addClass('active');
        $('#tabPaneOverview').hide();
        $('#tabPaneHistory').show();
        $('#sectionRecentCalls').hide();
    }
}

function openScheduleFollowupModal() {
    $('#modalScheduleFollowup').modal('show');
}

function openReassignModal() {
    $('#modalReassign').modal('show');
}

function initiateCall(phone) {
    if (!phone) {
        toastr.warning("No phone number available for this lead.");
        return;
    }
    toastr.info("Initiating call to " + phone + "...");
    
    // Check if click2call or webrtc dialer is available on the page or server
    $.ajax({
        url: "{{ route('logCall') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            lead_id: "{{ $Lead->id }}",
            phonenumber: phone,
            call_type: "click2call"
        },
        success: function(resp) {
            if (typeof resp === 'string') {
                try { resp = JSON.parse(resp); } catch(e) {}
            }
            if (resp && resp.success === false) {
                toastr.warning(resp.message || "Call could not be placed");
                return;
            }
            toastr.success("Call connected to " + phone);
        },
        error: function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : null;
            if (msg) {
                toastr.warning(msg);
            } else {
                toastr.error("Failed to initiate call");
            }
        }
    });
}

function playAudio(url) {
    if (!url) return;
    var overlay = document.getElementById('audioPlayerOverlay');
    var player = document.getElementById('audioPlayerElem');
    player.src = url;
    overlay.style.display = 'flex';
    player.play();
}

function closeAudioPlayer() {
    var overlay = document.getElementById('audioPlayerOverlay');
    var player = document.getElementById('audioPlayerElem');
    player.pause();
    overlay.style.display = 'none';
}
</script>
@endpush
@endsection
