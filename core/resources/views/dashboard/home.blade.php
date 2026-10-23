@extends('dashboard.layouts.master')
@section('title', 'LeadGER')

@push('after-styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/custom.css') }}" type="text/css"/>
<style>
  .lg-template-wrap {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    min-height: auto;
    padding: 0;
    max-width: 100%;
    margin: 0 auto;
  }
  .tnum { font-variant-numeric: tabular-nums; }
  .lg-card-header {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .lg-card-header h1 {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
  }
  .lg-workflow-bar {
    margin-top: 4px;
    font-size: 13px;
    color: #64748B;
  }
  .lg-workflow-bar strong {
    color: #0F172A;
    letter-spacing: 0.04em;
  }
  .lg-workflow-bar .arrow {
    color: #94A3B8;
    margin: 0 4px;
  }
  .btn-outline-custom {
    height: 36px;
    border-radius: 6px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    padding: 0 14px;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .btn-outline-custom:hover { background: #F1F5F9; }
  .btn-primary-custom {
    height: 36px;
    border-radius: 6px;
    background: var(--lg-accent);
    border: none;
    padding: 0 14px;
    font-size: 13px;
    font-weight: 600;
    color: #FFFFFF;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(20,100,244,0.3);
    transition: all 0.15s ease;
  }
  .btn-primary-custom:hover { background: #0F52CC; }

  /* KPI Grid */
  .lg-kpis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }
  @media(min-width: 1200px) {
    .lg-kpis-grid { grid-template-columns: repeat(7, 1fr); }
  }
  .lg-kpi-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    text-decoration: none !important;
    color: inherit;
    cursor: pointer;
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    user-select: none;
    outline: none;
  }
  .lg-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
  }
  .lg-kpi-card:focus-visible {
    outline: 2px solid var(--lg-accent);
    outline-offset: 2px;
  }
  .lg-kpi-card.live {
    border-color: #BFDBFE;
    box-shadow: 0 0 0 1px rgba(20,100,244,0.1);
  }
  .lg-kpi-card.warn {
    background: #FFFBEB;
    border-color: #FDE68A;
  }
  .lg-kpi-card.ok {
    background: #ECFDF5;
    border-color: #A7F3D0;
  }
  .lg-kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
  }
  .lg-kpi-val {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1;
    margin: 10px 0 6px 0;
    color: #0F172A;
  }
  .lg-kpi-sub {
    font-size: 12.5px;
    color: #64748B;
    margin: 0;
    line-height: 1.3;
  }

  /* 2-column Grid */
  .lg-mid-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }
  @media (min-width: 1200px) {
    .lg-mid-grid { grid-template-columns: 1.35fr 1fr; }
  }
  .lg-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  }
  .lg-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .lg-panel-head h2 {
    font-size: 16px;
    font-weight: 600;
    color: #0F172A;
    margin: 0;
  }
  .lg-panel-head p {
    font-size: 13px;
    color: #64748B;
    margin: 2px 0 0 0;
  }

  /* Funnel */
  .lg-funnel-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
  }
  @media (min-width: 768px) {
    .lg-funnel-list { grid-template-columns: repeat(3, 1fr); }
  }
  @media (min-width: 992px) {
    .lg-funnel-list { grid-template-columns: repeat(6, 1fr); }
  }
  .lg-funnel-card {
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    padding: 16px 8px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none !important;
    color: inherit;
    cursor: pointer;
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    user-select: none;
    outline: none;
  }
  .lg-funnel-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
  }
  .lg-funnel-card:focus-visible {
    outline: 2px solid var(--lg-accent);
    outline-offset: 2px;
  }
  .lg-funnel-card.c-contacted {
    background: #EFF6FF;
    border-color: #BFDBFE;
  }
  .lg-funnel-card.c-interested {
    background: #ECFDF5;
    border-color: #A7F3D0;
  }
  .lg-funnel-card.c-followup {
    background: #FFFBEB;
    border-color: #FDE68A;
  }
  .lg-funnel-card.c-converted {
    background: #ECFDF5;
    border: 2px solid #10B981;
  }

  /* Campaign List */
  .lg-camps-list {
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
  }
  .lg-camps-list li {
    margin-bottom: 20px;
  }
  .lg-camps-list li:last-child { margin-bottom: 0; }
  .lg-progress-bar-bg {
    height: 8px;
    border-radius: 999px;
    background: #F1F5F9;
    overflow: hidden;
    margin: 8px 0;
  }
  .lg-progress-bar-fill {
    height: 100%;
    border-radius: 999px;
  }

  /* Telecaller Floor */
  .lg-floor-table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
    font-size: 13px;
  }
  .lg-floor-table th {
    background: #F8FAFC;
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    border-bottom: 1px solid #E2E8F0;
    text-align: left;
    position: sticky;
    top: 0;
    z-index: 2;
  }
  .lg-floor-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #E2E8F0;
    color: #0F172A;
    vertical-align: middle;
  }
  .lg-floor-table tr:hover td {
    background: #F8FAFC;
  }
</style>
@endpush

@section('content')

<?php
$totalLeadsCount = $totalLeadsCount ?? 0;
$newLeadsCount = $newLeadsCount ?? 0;
$assignedLeadsCount = $assignedLeadsCount ?? 0;
$contactedLeadsCount = $contactedLeadsCount ?? 0;
$interestedLeadsCount = $interestedLeadsCount ?? 0;
$followUpsDueCount = $followUpsDueCount ?? 0;
$conversionsWonCount = $conversionsWonCount ?? 0;
$callsTodayCount = $callsTodayCount ?? 0;
$callsConnectedTodayCount = $callsConnectedTodayCount ?? 0;
$callsYesterdayCount = $callsYesterdayCount ?? 0;
$followUpsNextHourCount = $followUpsNextHourCount ?? 0;
$leadsWorkedTodayCount = $leadsWorkedTodayCount ?? 0;
$onlineAgentsCount = $onlineAgentsCount ?? 0;
$onCallNowCount = $onCallNowCount ?? 0;
$activeCampaigns = $activeCampaigns ?? collect([]);
?>

<div class="lg-template-wrap">
    
    <!-- 1. Page Header (Template Design) -->
    <section class="lg-card-header">
        <div>
            <h1>Real-Time Lead Flow</h1>
            <p class="lg-workflow-bar">
                <strong>LEADS <span class="arrow">→</span> ALLOCATION <span class="arrow">→</span> CALLING <span class="arrow">→</span> FOLLOW-UP <span class="arrow">→</span> CONVERSION</strong>
            </p>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
            <span style="font-size: 13px; color: #64748B;">Last auto-sync: <span id="sync" style="font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: #0F172A;">{{ date('H:i:s') }}</span></span>
            <button id="btnExportShiftLog" class="btn-outline-custom" onclick="exportShiftLog()"><i class="fas fa-file-export" style="margin-right: 5px;"></i>Export Shift Log</button>
            <button class="btn-primary-custom" onclick="window.location='{{ route('lead_allocation') }}'">+ Quick Allocate Leads</button>
        </div>
    </section>

    <!-- 2. 7 KPI Cards (Exact Template Layout & Colors) -->
    <section class="lg-kpis-grid" aria-label="Today's key numbers">
        
        <!-- 1. Online Agents -->
        <a href="{{ route('agentPerformance') }}?filter=online&from_date={{ date('Y-m-d') }}&to_date={{ date('Y-m-d') }}" class="lg-kpi-card" tabindex="0" role="link" title="View online agents in Agent Performance Report">
            <div class="lg-kpi-top">
                <span>Online Agents</span>
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10B981;"></span>
            </div>
            <div class="lg-kpi-val tnum">{{ $onlineAgentsCount }}</div>
            <p class="lg-kpi-sub">of {{ \App\Models\User::where('status', 1)->count() }} total logged in</p>
        </a>

        <!-- 2. On Call Now -->
        <a href="#floor" id="cardOnCallNow" onclick="handleOnCallClick(event)" class="lg-kpi-card live" tabindex="0" role="link" title="Jump to Live Telecaller Activity Floor filtered to on-call agents">
            <div class="lg-kpi-top" style="color: var(--lg-accent); font-weight: 600;">
                <span>On Call Now</span>
                <span style="position: relative; display: flex; width: 12px; height: 12px;">
                    <span class="status-dot-pulse" style="position: absolute; width: 100%; height: 100%; border-radius: 50%; background: var(--lg-accent); opacity: 0.5;"></span>
                    <span style="width: 12px; height: 12px; border-radius: 50%; background: #EFF6FF; border: 1px solid #BFDBFE;"></span>
                </span>
            </div>
            <div id="cardOnCallVal" class="lg-kpi-val tnum" style="color: var(--lg-accent);">{{ $onCallNowCount }}</div>
            <p class="lg-kpi-sub" style="color: var(--lg-accent);">Live active calls</p>
        </a>

        <!-- 3. Calls Today -->
        <a href="{{ route('callReport') }}?from_date={{ date('Y-m-d') }}&to_date={{ date('Y-m-d') }}" class="lg-kpi-card" tabindex="0" role="link" title="View all calls today in Call Report">
            <div class="lg-kpi-top">
                <span>Calls Today</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
            </div>
            <div class="lg-kpi-val tnum">{{ number_format($callsTodayCount) }}</div>
            <p class="lg-kpi-sub">
                @if($callsYesterdayCount > 0)
                    @php $callDiff = round((($callsTodayCount - $callsYesterdayCount) / $callsYesterdayCount) * 100); @endphp
                    <span style="color: {{ $callDiff >= 0 ? '#047857' : '#B91C1C' }}; font-weight: 600;">{{ $callDiff >= 0 ? '↑' : '↓' }} {{ abs($callDiff) }}%</span> vs yesterday
                @else
                    <span style="color: #64748B;">0% vs yesterday</span>
                @endif
            </p>
        </a>

        <!-- 4. Connected -->
        <a href="{{ route('callReport') }}?from_date={{ date('Y-m-d') }}&to_date={{ date('Y-m-d') }}&status=Connected" class="lg-kpi-card" tabindex="0" role="link" title="View connected calls today in Call Report">
            <div class="lg-kpi-top">
                <span>Connected</span>
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10B981;"></span>
            </div>
            <div class="lg-kpi-val tnum">{{ number_format($callsConnectedTodayCount) }}</div>
            <p class="lg-kpi-sub">
                {{ $callsTodayCount > 0 ? round(($callsConnectedTodayCount / $callsTodayCount) * 100, 1) . '%' : '0%' }} connect rate
            </p>
        </a>

        <!-- 5. Leads Worked -->
        <a href="{{ route('leads') }}?filter=worked_today&date={{ date('Y-m-d') }}" class="lg-kpi-card" tabindex="0" role="link" title="View leads worked today in Leads list">
            <div class="lg-kpi-top">
                <span>Leads Worked</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
            </div>
            <div class="lg-kpi-val tnum">{{ number_format($leadsWorkedTodayCount) }}</div>
            <p class="lg-kpi-sub">Unique prospects</p>
        </a>

        <!-- 6. Follow-Ups Due -->
        <a href="{{ route('leads') }}?filter=followup" class="lg-kpi-card warn" tabindex="0" role="link" title="View scheduled follow-ups in Leads list">
            <div class="lg-kpi-top" style="color: #B45309; font-weight: 600;">
                <span>Follow-Ups Due</span>
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #F59E0B;"></span>
            </div>
            <div class="lg-kpi-val tnum" style="color: #B45309;">{{ $followUpsDueCount }}</div>
            <p class="lg-kpi-sub" style="color: #B45309;">{{ $followUpsNextHourCount }} scheduled in 1h</p>
        </a>

        <!-- 7. Conversions -->
        <a href="{{ route('leads') }}?filter=converted" class="lg-kpi-card ok" tabindex="0" role="link" title="View converted leads won">
            <div class="lg-kpi-top" style="color: #047857; font-weight: 600;">
                <span>Conversions</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
            </div>
            <div class="lg-kpi-val tnum" style="color: #047857;">{{ $conversionsWonCount }}</div>
            <p class="lg-kpi-sub" style="color: #047857;">
                {{ $callsConnectedTodayCount > 0 ? round(($conversionsWonCount / $callsConnectedTodayCount) * 100, 1) . '%' : '0%' }} of connected
            </p>
        </a>
    </section>

    <!-- 3. Funnel & Campaigns (Exact 2-Column Template Layout) -->
    <div class="lg-mid-grid">
        
        <!-- Left: Daily Tele Sales Conversion Funnel -->
        <section class="lg-panel">
            <div class="lg-panel-head">
                <div>
                    <h2>Daily Tele Sales Conversion Funnel</h2>
                    <p>Pipeline progression across all active queues today</p>
                </div>
                <span style="border-radius: 999px; border: 1px solid #BFDBFE; background: #EFF6FF; padding: 4px 12px; font-size: 12px; font-weight: 600; color: var(--lg-accent);">Shift Overview</span>
            </div>
            @php
                $totalBase = $totalLeadsCount > 0 ? $totalLeadsCount : 0;
            @endphp
            <ol class="lg-funnel-list">
                <li class="lg-funnel-card" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=new_leads&status=1'" title="View new leads in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: #64748B;">1. New Leads</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: #0F172A; margin-top: 6px;">{{ number_format($newLeadsCount) }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: #64748B; margin-top: 4px;">{{ $totalBase > 0 ? round(($newLeadsCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: #64748B;">Ingested</span>
                </li>
                <li class="lg-funnel-card" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=assigned'" title="View assigned leads in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: #64748B;">2. Assigned</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: #0F172A; margin-top: 6px;">{{ number_format($assignedLeadsCount) }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: #64748B; margin-top: 4px;">{{ $totalBase > 0 ? round(($assignedLeadsCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: #64748B;">Allocated</span>
                </li>
                <li class="lg-funnel-card c-contacted" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=contacted'" title="View contacted leads in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: var(--lg-accent);">3. Contacted</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: var(--lg-accent); margin-top: 6px;">{{ number_format($contactedLeadsCount) }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: var(--lg-accent); margin-top: 4px;">{{ $totalBase > 0 ? round(($contactedLeadsCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: var(--lg-accent);">Picked up</span>
                </li>
                <li class="lg-funnel-card c-interested" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=interested'" title="View interested leads in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: #047857;">4. Interested</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: #047857; margin-top: 6px;">{{ number_format($interestedLeadsCount) }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: #047857; margin-top: 4px;">{{ $totalBase > 0 ? round(($interestedLeadsCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: #047857;">Pitch OK</span>
                </li>
                <li class="lg-funnel-card c-followup" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=followup'" title="View follow-ups in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: #B45309;">5. Follow-Up</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: #B45309; margin-top: 6px;">{{ $followUpsDueCount }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: #B45309; margin-top: 4px;">{{ $totalBase > 0 ? round(($followUpsDueCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: #B45309;">Callback set</span>
                </li>
                <li class="lg-funnel-card c-converted" tabindex="0" role="link" onclick="window.location='{{ route('leads') }}?filter=converted'" title="View converted deals won in Leads list">
                    <span style="font-size: 13px; font-weight: 600; color: #047857;">6. Converted</span>
                    <span class="tnum" style="font-size: 22px; font-weight: 700; color: #047857; margin-top: 6px;">{{ $conversionsWonCount }}</span>
                    <span class="tnum" style="font-size: 12px; font-weight: 600; color: #047857; margin-top: 4px;">{{ $totalBase > 0 ? round(($conversionsWonCount / $totalBase) * 100, 1) . '%' : '0%' }}</span>
                    <span style="font-size: 12px; color: #047857;">Won deal</span>
                </li>
            </ol>
        </section>

        <!-- Right: Active Campaign Execution -->
        <section class="lg-panel">
            <div class="lg-panel-head">
                <div>
                    <h2>Active Campaign Execution</h2>
                    <p>Live progress towards campaign dial targets</p>
                </div>
                <a href="{{ route('campaigns') }}" style="font-size: 13px; font-weight: 600; color: var(--lg-accent); text-decoration: none;">View All →</a>
            </div>
            <ul class="lg-camps-list">
                @forelse($activeCampaigns as $camp)
                    <li>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span style="font-size: 14px; font-weight: 600; color: #0F172A;">{{ $camp['name'] }}</span>
                            <span style="font-family: 'IBM Plex Mono', monospace; font-size: 12px; color: #64748B;">{{ $camp['completed'] }} / {{ $camp['target'] }} leads ({{ $camp['progress_pct'] }}%)</span>
                        </div>
                        <div class="lg-progress-bar-bg">
                            <div class="lg-progress-bar-fill" style="width: {{ $camp['progress_pct'] }}%; background: {{ $camp['progress_pct'] >= 75 ? '#10B981' : ($camp['progress_pct'] >= 40 ? 'var(--lg-accent)' : '#F59E0B') }};"></div>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size: 13px;">
                            <span style="color: #64748B;">{{ $camp['agents_count'] }} {{ $camp['agents_count'] == 1 ? 'Agent' : 'Agents' }} Assigned</span>
                            <span style="color: #047857; font-weight: 600;">{{ $camp['conversions_count'] }} Conversions Won</span>
                        </div>
                    </li>
                @empty
                    <li style="text-align: center; color: #64748B; padding: 40px 0; font-size: 13px;">
                        No campaigns yet
                    </li>
                @endforelse
            </ul>
        </section>

    </div>

    <!-- 4. Live Telecaller Activity Floor (Exact Template Layout & Interactivity) -->
    <section id="floorSection" class="lg-panel" style="padding: 0; overflow: hidden; scroll-margin-top: 24px;">
        <div style="padding: 16px 20px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #E2E8F0;">
            <div>
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <h2 style="font-size: 16px; font-weight: 600; margin: 0; color: #0F172A;">Live Telecaller Activity Floor</h2>
                    <span style="display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 2px 10px; font-size: 12px; font-weight: 600; color: #047857;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10B981;"></span>
                        <span><span id="liveCount">{{ $onlineAgentsCount ?? 0 }}</span> Agents Live</span>
                    </span>
                </div>
                <p style="font-size: 13px; color: #64748B; margin: 3px 0 0 0;">Real-time status, active lead interaction and shift conversion stats</p>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <div class="lg-search-box" style="width: 220px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input id="q" type="search" class="lg-search-input-field" placeholder="Search agent name…">
                </div>
                <select id="st" style="height: 36px; border-radius: 6px; border: 1px solid #E2E8F0; background: #FFFFFF; padding: 0 12px; font-size: 13px; outline: none; cursor: pointer;">
                    <option value="">All Statuses</option>
                    <option value="call">On call</option>
                    <option value="wrap">Wrap-up</option>
                    <option value="ready">Ready</option>
                    <option value="break">On break</option>
                    <option value="off">Offline</option>
                </select>
            </div>
        </div>

        <div id="floorFilterChipWrap" style="display: none; padding: 12px 20px 0 20px;">
            <span style="display: inline-flex; align-items: center; gap: 8px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 999px; padding: 4px 12px; font-size: 12.5px; font-weight: 500; color: var(--lg-accent-hover);">
                <span id="floorFilterChipText">Filter: On Call Now · Live Active Calls</span>
                <button type="button" onclick="clearFloorFilter()" style="border: none; background: transparent; cursor: pointer; color: var(--lg-accent-hover); font-weight: 700; font-size: 15px; padding: 0; margin-left: 4px; line-height: 1;" title="Clear filter">&times;</button>
            </span>
        </div>

        <div style="max-height: 520px; overflow-x: auto;">
            <table class="lg-floor-table">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">Telecaller</th>
                        <th>Status</th>
                        <th>Current Active Lead</th>
                        <th style="text-align: right;">Calls</th>
                        <th style="text-align: right;">Connected</th>
                        <th style="text-align: right;">Talk Time</th>
                        <th style="text-align: right;">Follow-Ups</th>
                        <th style="text-align: right; padding-right: 20px;">Action</th>
                    </tr>
                </thead>
                <tbody id="floor"></tbody>
            </table>
            <p id="noRows" style="display: none; padding: 40px; text-align: center; font-size: 13px; color: #64748B; margin: 0;">No agents live</p>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #E2E8F0; background: #F8FAFC; padding: 12px 20px; font-size: 12px; color: #64748B;">
            <span id="rowCount"></span>
            <span>Live status and current lead stream directly from the SIP dialer engine.</span>
        </div>
    </section>

</div>

<script>
  const STATUS = {
    call:  ['On call',  'background:#EFF6FF; color:var(--lg-accent); border:1px solid #BFDBFE;', '#1464F4'],
    wrap:  ['Wrap-up',  'background:#FFFBEB; color:#B45309; border:1px solid #FDE68A;', '#F59E0B'],
    ready: ['Ready',    'background:#ECFDF5; color:#047857; border:1px solid #A7F3D0;', '#10B981'],
    break: ['On break', 'background:#F1F5F9; color:#64748B; border:1px solid #E2E8F0;', '#64748B'],
    off:   ['Offline',  'background:#F1F5F9; color:#64748B; border:1px solid #E2E8F0;', '#64748B'],
  };
  const now = Date.now();
  const AGENTS = @json($telecallerFloor ?? []);
  const fmtDur = s => { const h = Math.floor(s / 3600), m = Math.floor(s / 60) % 60; return h ? `${h}h ${String(m).padStart(2, '0')}m` : `${m}m`; };
  const clock = ms => { const s = Math.floor((Date.now() - ms) / 1000); return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`; };
  const initials = n => (n || '').split(' ').filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase();

  function renderFloor() {
    const qEl = document.getElementById('q');
    const stEl = document.getElementById('st');
    if (!qEl || !stEl) return;
    const q = qEl.value.trim().toLowerCase();
    const st = stEl.value;
    const rows = AGENTS.filter(a => (!q || (a[0] && a[0].toLowerCase().includes(q))) && (!st || a[1] === st));
    const floorEl = document.getElementById('floor');
    if (!floorEl) return;
    floorEl.innerHTML = rows.map(([name, s, lead, calls, conn, talk, fu, won, idle, since]) => {
      const statusMeta = STATUS[s] || STATUS['off'];
      const [label, pillStyle, dotColor] = statusMeta;
      const rate = calls ? Math.round(conn / calls * 100) : 0;
      const off = s === 'off';
      return `<tr style="${off ? 'color: #94A3B8;' : ''}">
        <td style="padding-left: 20px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <span style="position: relative; width: 32px; height: 32px; border-radius: 50%; background: #F1F5F9; display: grid; place-items: center; font-size: 12px; font-weight: 700; color: #64748B; flex-shrink: 0;">
              ${initials(name)}
              <span style="position: absolute; bottom: -1px; right: -1px; width: 9px; height: 9px; border-radius: 50%; background: ${dotColor}; box-shadow: 0 0 0 2px #FFFFFF;"></span>
            </span>
            <span style="font-weight: 600; color: ${off ? '#64748B' : '#0F172A'};">${name}</span>
          </div>
        </td>
        <td>
          <span style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 600; ${pillStyle}">
            ${s === 'call' && since ? `<span style="width: 6px; height: 6px; border-radius: 50%; background: var(--lg-accent);"></span>${label} <span class="tnum" style="font-family: 'IBM Plex Mono', monospace; margin-left: 2px;" data-since="${since}">${clock(since)}</span>` : label}
          </span>
        </td>
        <td style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: ${!lead || lead.startsWith('Next') || lead === '—' ? '#64748B' : '#0F172A'};">${lead || '—'}</td>
        <td class="tnum" style="text-align: right; font-weight: 600;">${calls}</td>
        <td class="tnum" style="text-align: right;">${conn} <span style="font-size: 12px; color: #64748B;">(${rate}%)</span></td>
        <td class="tnum" style="text-align: right;">${fmtDur(talk)}</td>
        <td class="tnum" style="text-align: right; ${fu >= 6 ? 'font-weight: 600; color: #B45309;' : ''}">${fu}</td>
        <td style="text-align: right; padding-right: 20px;">
          ${s === 'call'
            ? '<button onclick="alert(\'Listening into active call...\')" style="display: inline-flex; align-items: center; gap: 6px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: #FFFFFF; padding: 0 10px; font-size: 12px; font-weight: 600; color: var(--lg-accent); cursor: pointer;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 14v-3a9 9 0 0 1 18 0v3"/><path d="M21 15a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-5H5a2 2 0 0 0-2 2z"/></svg>Listen</button>'
            : '<button onclick="alert(\'Viewing telecaller profile...\')" style="height: 32px; border-radius: 6px; border: none; background: transparent; padding: 0 10px; font-size: 12px; font-weight: 600; color: var(--lg-accent); cursor: pointer;">View</button>'}
        </td>
      </tr>`;
    }).join('');
    const noRowsEl = document.getElementById('noRows');
    if (noRowsEl) {
        if (AGENTS.length === 0) {
            noRowsEl.textContent = 'No agents live';
            noRowsEl.style.display = 'block';
        } else if (rows.length === 0) {
            noRowsEl.textContent = 'No telecallers match these filters.';
            noRowsEl.style.display = 'block';
        } else {
            noRowsEl.style.display = 'none';
        }
    }
    const rowCountEl = document.getElementById('rowCount');
    if (rowCountEl) rowCountEl.textContent = `Showing ${rows.length} of ${AGENTS.length} telecallers`;

    // Chip state
    const chipWrap = document.getElementById('floorFilterChipWrap');
    const chipText = document.getElementById('floorFilterChipText');
    if (chipWrap && chipText) {
        if (st === 'call') {
            chipText.textContent = 'Filter: On Call Now · Live Active Calls (' + rows.length + ')';
            chipWrap.style.display = 'block';
        } else if (st) {
            const label = STATUS[st] ? STATUS[st][0] : st;
            chipText.textContent = 'Filter: ' + label + ' (' + rows.length + ')';
            chipWrap.style.display = 'block';
        } else {
            chipWrap.style.display = 'none';
        }
    }
  }

  function handleOnCallClick(e) {
    if (e && e.preventDefault) e.preventDefault();
    const stEl = document.getElementById('st');
    if (stEl) {
        stEl.value = 'call';
    }
    renderFloor();
    const floorSection = document.getElementById('floorSection');
    if (floorSection) {
        floorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function clearFloorFilter() {
    const stEl = document.getElementById('st');
    if (stEl) {
        stEl.value = '';
    }
    renderFloor();
  }

  function exportShiftLog() {
    const btn = document.getElementById('btnExportShiftLog');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>Exporting...';
    }

    const todayStr = '{{ date('Y-m-d') }}';
    const url = '{{ route('exportShiftLog') }}?date=' + encodeURIComponent(todayStr);

    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json();
            if (data.status === 'empty' || data.count === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.info(data.message || 'No shift data to export');
                } else {
                    console.info(data.message);
                }
                return;
            }
        }

        if (!response.ok) {
            throw new Error('Export request failed with status ' + response.status);
        }

        const blob = await response.blob();
        let fileName = 'shift-log-' + todayStr + '.csv';
        const disposition = response.headers.get('content-disposition');
        if (disposition && disposition.indexOf('filename=') !== -1) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches && matches[1]) {
                fileName = matches[1].replace(/['"]/g, '');
            }
        }

        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(blobUrl);
        a.remove();

        if (typeof toastr !== 'undefined') {
            toastr.success('Shift log exported successfully!');
        }
    })
    .catch(err => {
        console.error('Shift log export error:', err);
        if (typeof toastr !== 'undefined') {
            toastr.error('Failed to export shift log. Please try again.');
        }
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Sync On Call Now count to agents dataset
    const onCallCount = AGENTS.filter(a => a[1] === 'call').length;
    const cardValEl = document.getElementById('cardOnCallVal');
    if (cardValEl) cardValEl.textContent = onCallCount;

    const liveCountEl = document.getElementById('liveCount');
    if (liveCountEl) liveCountEl.textContent = AGENTS.filter(a => a[1] !== 'off').length;

    const qEl = document.getElementById('q');
    const stEl = document.getElementById('st');
    if (qEl) qEl.addEventListener('input', renderFloor);
    if (stEl) stEl.addEventListener('change', renderFloor);
    renderFloor();

    // Check URL hash or query param for on-call jump
    if (window.location.hash === '#floor' || new URLSearchParams(window.location.search).get('filter') === 'on_call') {
        handleOnCallClick();
    }

    // Keyboard navigation (Enter key on clickable cards)
    document.querySelectorAll('.lg-kpi-card, .lg-funnel-card').forEach(card => {
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                card.click();
            }
        });
    });
  });

  setInterval(() => {
    document.querySelectorAll('[data-since]').forEach(el => { el.textContent = clock(+el.dataset.since); });
    const d = new Date();
    if (d.getSeconds() % 10 === 0) {
      const syncEl = document.getElementById('sync');
      if (syncEl) syncEl.textContent = [d.getHours(), d.getMinutes(), d.getSeconds()].map(v => String(v).padStart(2, '0')).join(':');
    }
  }, 1000);
</script>
<script>
  // /admin#license-info -> the License Info tab of the Profile page (Sarvam API key, license)
  if (window.location.hash === '#license-info') {
    window.location.replace("{{ route('usersEdit', Auth::id()) }}#license-info");
  }
</script>
@endsection
