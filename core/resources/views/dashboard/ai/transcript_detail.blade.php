@extends('dashboard.layouts.master')
@section('title', 'Call Transcript - #' . $analysis->call_id)

@push("after-styles")
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Page Canvas: Light cool blue-gray background matching AI Analysis page */
.lg-transcript-page {
    padding: 20px 24px 40px 24px;
    background-color: #F8FAFD;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
}

/* 1. Compact Page Header Card */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 16px;
    flex-wrap: wrap;
}
.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.lg-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 19px;
    line-height: 1.25;
}
.lg-header-subtitle {
    color: #64748B;
    font-size: 13px;
    margin-top: 2px;
}
.lg-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 16px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-btn-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
    text-decoration: none;
}
.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 18px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-btn-primary:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
    text-decoration: none;
}

/* 2. Call Information Summary Card */
.lg-info-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 16px;
}
.lg-info-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
}
@media (max-width: 991px) {
    .lg-info-grid { grid-template-columns: repeat(3, 1fr); gap: 14px; }
}
@media (max-width: 575px) {
    .lg-info-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
.lg-info-item {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.lg-info-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.lg-info-val {
    font-size: 14px;
    font-weight: 600;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lg-call-link {
    color: var(--lg-accent);
    text-decoration: none;
    font-weight: 700;
}
.lg-call-link:hover {
    text-decoration: underline;
}

/* 3. Transcript Summary Bar */
.lg-summary-bar {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.lg-summary-metrics {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.lg-summary-stat {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #334155;
}
.lg-summary-stat-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
.icon-blue { background: #EFF6FF; color: var(--lg-accent); }
.icon-cyan { background: #ECFEFF; color: #0891B2; }
.icon-green { background: #ECFDF5; color: #059669; }
.icon-purple { background: #F5F3FF; color: #7C3AED; }

.lg-summary-stat-val {
    font-weight: 700;
    color: #0F172A;
}

/* Status Badges */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}
.status-completed { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
.status-processing { background: #DBEAFE; color: var(--lg-accent-hover); border: 1px solid #BFDBFE; }
.status-pending { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
.status-failed { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

/* 4. Two-Column Layout (Main Transcript + Side Summary) */
.lg-content-grid {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 991px) {
    .lg-content-grid {
        grid-template-columns: 1fr;
    }
}

/* Main Transcript Card */
.lg-transcript-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.lg-transcript-header {
    padding: 16px 20px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: #FFFFFF;
}
.lg-transcript-title-wrap {
    display: flex;
    flex-direction: column;
}
.lg-transcript-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-transcript-subtitle {
    font-size: 12px;
    color: #64748B;
    margin-top: 2px;
}

/* Transcript Toolbar Actions */
.lg-transcript-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.lg-transcript-search-wrap {
    position: relative;
    width: 190px;
}
.lg-transcript-search-input {
    width: 100%;
    height: 34px;
    padding: 0 10px 0 30px;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    font-size: 12px;
    color: #334155;
    outline: none;
    transition: all 0.15s ease;
}
.lg-transcript-search-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 2px rgba(23,105,255,0.15);
}
.lg-search-icon-inside {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #94A3B8;
    pointer-events: none;
}
.lg-btn-tool {
    height: 34px;
    padding: 0 12px;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    background: #FFFFFF;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.lg-btn-tool:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

/* Chat Stream Container */
.lg-transcript-body {
    padding: 24px;
    background: #FAFBFD;
    max-height: 640px;
    overflow-y: auto;
}
.lg-chat-stream {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Individual Message Row */
.lg-msg-row {
    display: flex;
    gap: 12px;
    width: 100%;
}
.lg-msg-row.is-agent {
    justify-content: flex-start;
}
.lg-msg-row.is-customer {
    justify-content: flex-end;
}

/* Small Avatar Circle */
.lg-msg-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 2px;
}
.avatar-agent {
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #DBEAFE;
}
.avatar-customer {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #BBF7D0;
}

/* Message Content Box */
.lg-msg-bubble {
    max-width: 76%;
    border-radius: 12px;
    padding: 12px 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    position: relative;
}
.lg-msg-bubble.is-agent {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-left: 4px solid var(--lg-accent);
}
.lg-msg-bubble.is-customer {
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    border-right: 4px solid #10B981;
}

/* Author and Time Header */
.lg-msg-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 4px;
}
.lg-msg-author {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lg-msg-author.is-agent { color: var(--lg-accent); }
.lg-msg-author.is-customer { color: #059669; }

.lg-msg-role-tag {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 10px;
    text-transform: uppercase;
}
.role-tag-agent { background: #EFF6FF; color: var(--lg-accent); }
.role-tag-customer { background: #ECFDF5; color: #059669; }

.lg-msg-time {
    font-size: 11px;
    color: #94A3B8;
}
.lg-msg-text {
    font-size: 13.5px;
    line-height: 1.55;
    color: #1E293B;
    word-break: break-word;
}

/* Side Column Cards */
.lg-side-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
    margin-bottom: 20px;
}
.lg-side-card-header {
    padding: 14px 18px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-side-card-body {
    padding: 18px;
}

.lg-side-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #F8FAFC;
    font-size: 13px;
}
.lg-side-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.lg-side-item:first-child {
    padding-top: 0;
}
.lg-side-label {
    color: #64748B;
    font-weight: 500;
}
.lg-side-val {
    color: #0F172A;
    font-weight: 600;
    text-align: right;
}

/* Quick Action Buttons */
.lg-btn-action-full {
    width: 100%;
    height: 40px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-btn-action-full:last-child {
    margin-bottom: 0;
}
.btn-action-primary {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
}
.btn-action-primary:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
    text-decoration: none;
}
.btn-action-secondary {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
}
.btn-action-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
    text-decoration: none;
}

/* Toast message for Copy */
.lg-toast-notice {
    display: none;
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0F172A;
    color: #FFFFFF;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 9999;
}

/* Empty state */
.lg-empty-transcript {
    text-align: center;
    padding: 48px 20px;
    color: #94A3B8;
}
.lg-empty-transcript i {
    font-size: 42px;
    color: #CBD5E1;
    margin-bottom: 12px;
}
</style>
@endpush

@section('content')
@php
    // Resolve dynamic information from existing models
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
        $user = \App\Models\User::find($callLog->created_by);
        if ($user) {
            $agentName = $user->name;
        }
    }

    // Safe derivation of dialogue turns and message counts
    $lines = explode("\n", $analysis->transcript ?: '');
    $agentMsgs = 0;
    $custMsgs = 0;
    $parsedDialogues = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '') {
            $isAgent = (bool) preg_match('/^Agent(\s*\([^)]*\))?:/i', $trimmed);
            $isCustomer = (bool) preg_match('/^Customer(\s*\([^)]*\))?:/i', $trimmed);
            
            if ($isAgent) $agentMsgs++;
            elseif ($isCustomer) $custMsgs++;
            else $agentMsgs++; // default fallback if unlabelled

            $cleanText = preg_replace('/^(Agent|Customer)(\s*\([^)]*\))?:\s*/i', '', $trimmed);
            
            $parsedDialogues[] = [
                'isAgent' => $isAgent || (!$isCustomer),
                'text' => $cleanText
            ];
        }
    }
    $totalTurns = $agentMsgs + $custMsgs;
    $durationMins = round(($analysis->audio_duration_seconds ?: ($callLog->duration ?? 0)) / 60, 1);
@endphp

<div class="lg-transcript-page">

    <!-- 1. PAGE HEADER CARD -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fas fa-file-waveform"></i>
            </div>
            <div>
                <h4 class="lg-header-title">
                    Call Transcript <span style="color: var(--lg-accent);">#{{ $analysis->call_id }}</span>
                </h4>
                <div class="lg-header-subtitle">Speech-to-text conversation transcript</div>
            </div>
        </div>
        <div class="lg-header-actions">
            <a href="{{ route('callTranscriptsList') }}" class="lg-btn-secondary" title="Return to Call Transcripts List">
                <i class="fas fa-arrow-left"></i> Back to Transcripts
            </a>
            <a href="{{ route('aiAnalysisDetail', $analysis->id) }}" class="lg-btn-primary" title="View Full AI Analysis">
                <i class="fas fa-brain"></i> View AI Analysis
            </a>
            <button type="button" class="lg-btn-secondary" onclick="exportTranscriptTxt()" title="Download text transcript">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- 2. CALL INFORMATION SUMMARY CARD -->
    <div class="lg-info-card">
        <div class="lg-info-grid">
            <!-- Call ID -->
            <div class="lg-info-item">
                <span class="lg-info-label">Call ID</span>
                <span class="lg-info-val">
                    @if(!empty($callLog->lead_id))
                        <a onclick="leadDetail('{{ $callLog->lead_id }}')" class="lg-call-link cursor-pointer" title="View lead {{ $callLog->lead_id }}">
                            #{{ $analysis->call_id }}
                        </a>
                    @else
                        #{{ $analysis->call_id }}
                    @endif
                </span>
            </div>

            <!-- Agent -->
            <div class="lg-info-item">
                <span class="lg-info-label">Agent</span>
                <span class="lg-info-val" title="{{ $agentName }}">{{ $agentName }}</span>
            </div>

            <!-- Customer -->
            <div class="lg-info-item">
                <span class="lg-info-label">Customer</span>
                <span class="lg-info-val" title="{{ $customerName }}">{{ $customerName }}</span>
            </div>

            <!-- Campaign -->
            <div class="lg-info-item">
                <span class="lg-info-label">Campaign</span>
                <span class="lg-info-val" title="{{ $campaignName }}">{{ $campaignName }}</span>
            </div>

            <!-- Duration -->
            <div class="lg-info-item">
                <span class="lg-info-label">Duration</span>
                <span class="lg-info-val">{{ $durationMins }} mins</span>
            </div>

            <!-- Call Date -->
            <div class="lg-info-item">
                <span class="lg-info-label">Call Date</span>
                <span class="lg-info-val" title="{{ date('d M Y H:i', strtotime($analysis->created_at)) }}">
                    {{ date('d M Y H:i', strtotime($analysis->created_at)) }}
                </span>
            </div>
        </div>
    </div>

    <!-- 3. TRANSCRIPT SUMMARY BAR -->
    <div class="lg-summary-bar">
        <div class="lg-summary-metrics">
            <div class="lg-summary-stat">
                <div class="lg-summary-stat-icon icon-blue">
                    <i class="fas fa-stopwatch"></i>
                </div>
                <div>
                    <span class="text-muted small">Duration:</span>
                    <span class="lg-summary-stat-val ms-1">{{ $durationMins }} mins</span>
                </div>
            </div>

            <div class="lg-summary-stat">
                <div class="lg-summary-stat-icon icon-cyan">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <span class="text-muted small">Dialogue Turns:</span>
                    <span class="lg-summary-stat-val ms-1">{{ $totalTurns }}</span>
                </div>
            </div>

            <div class="lg-summary-stat">
                <div class="lg-summary-stat-icon icon-blue">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <span class="text-muted small">Agent Messages:</span>
                    <span class="lg-summary-stat-val ms-1">{{ $agentMsgs }}</span>
                </div>
            </div>

            <div class="lg-summary-stat">
                <div class="lg-summary-stat-icon icon-green">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <span class="text-muted small">Customer Messages:</span>
                    <span class="lg-summary-stat-val ms-1">{{ $custMsgs }}</span>
                </div>
            </div>
        </div>

        <div>
            @php
                $status = strtolower($analysis->status ?: 'completed');
            @endphp
            @if($status === 'completed')
                <span class="lg-status-pill status-completed">
                    <i class="fas fa-circle-check"></i> Completed
                </span>
            @elseif($status === 'processing')
                <span class="lg-status-pill status-processing">
                    <i class="fas fa-spinner fa-spin"></i> Processing
                </span>
            @elseif($status === 'pending')
                <span class="lg-status-pill status-pending">
                    <i class="fas fa-clock"></i> Pending
                </span>
            @else
                <span class="lg-status-pill status-failed">
                    <i class="fas fa-circle-xmark"></i> {{ ucfirst($status) }}
                </span>
            @endif
        </div>
    </div>

    <!-- 4. TWO-COLUMN LAYOUT (Main Transcript on Left, Side Summary on Right) -->
    <div class="lg-content-grid">
        
        <!-- LEFT: MAIN CONVERSATION TRANSCRIPT CARD -->
        <div class="lg-transcript-card">
            <div class="lg-transcript-header">
                <div class="lg-transcript-title-wrap">
                    <h5 class="lg-transcript-title">
                        <i class="fas fa-comments" style="color: var(--lg-accent);"></i> Conversation Transcript
                    </h5>
                    <div class="lg-transcript-subtitle">Full speech-to-text conversation</div>
                </div>

                <!-- Transcript Toolbar -->
                <div class="lg-transcript-toolbar">
                    <div class="lg-transcript-search-wrap">
                        <i class="fas fa-search lg-search-icon-inside"></i>
                        <input type="text" id="transcriptSearchInput" class="lg-transcript-search-input" placeholder="Search transcript..." onkeyup="filterTranscriptMessages(this.value)">
                    </div>

                    <button type="button" class="lg-btn-tool" onclick="copyTranscriptToClipboard()" title="Copy entire transcript text">
                        <i class="fas fa-copy"></i> Copy
                    </button>

                    <button type="button" class="lg-btn-tool" onclick="exportTranscriptTxt()" title="Download text file">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="lg-transcript-body" id="transcriptDialogueContainer">
                @if(count($parsedDialogues) > 0)
                    <div class="lg-chat-stream" id="chatDialogueStream">
                        @foreach($parsedDialogues as $index => $msg)
                            <div class="lg-msg-row {{ $msg['isAgent'] ? 'is-agent' : 'is-customer' }}" data-message-index="{{ $index }}">
                                @if($msg['isAgent'])
                                    <div class="lg-msg-avatar avatar-agent" title="Agent: {{ $agentName }}">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                @endif

                                <div class="lg-msg-bubble {{ $msg['isAgent'] ? 'is-agent' : 'is-customer' }}">
                                    <div class="lg-msg-meta">
                                        <div class="lg-msg-author {{ $msg['isAgent'] ? 'is-agent' : 'is-customer' }}">
                                            <span>{{ $msg['isAgent'] ? 'Agent' : 'Customer' }}</span>
                                            <span class="lg-msg-role-tag {{ $msg['isAgent'] ? 'role-tag-agent' : 'role-tag-customer' }}">
                                                {{ $msg['isAgent'] ? $agentName : $customerName }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="lg-msg-text">
                                        {{ $msg['text'] }}
                                    </div>
                                </div>

                                @if(!$msg['isAgent'])
                                    <div class="lg-msg-avatar avatar-customer" title="Customer: {{ $customerName }}">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="lg-empty-transcript">
                        <i class="fas fa-file-waveform"></i>
                        <h6 style="color: #0F172A; font-weight: 700;">No transcript available</h6>
                        <p class="small m-0">The transcript for this call recording has not been generated.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- RIGHT: SIDE SUMMARY & QUICK ACTIONS -->
        <div>
            
            <!-- Side Summary: Call Information -->
            <div class="lg-side-card">
                <div class="lg-side-card-header">
                    <i class="fas fa-circle-info text-primary"></i> Call Information
                </div>
                <div class="lg-side-card-body">
                    <div class="lg-side-item">
                        <span class="lg-side-label">Call ID</span>
                        <span class="lg-side-val">#{{ $analysis->call_id }}</span>
                    </div>
                    <div class="lg-side-item">
                        <span class="lg-side-label">Agent</span>
                        <span class="lg-side-val">{{ $agentName }}</span>
                    </div>
                    <div class="lg-side-item">
                        <span class="lg-side-label">Customer</span>
                        <span class="lg-side-val">{{ $customerName }}</span>
                    </div>
                    <div class="lg-side-item">
                        <span class="lg-side-label">Campaign</span>
                        <span class="lg-side-val">{{ $campaignName }}</span>
                    </div>
                    <div class="lg-side-item">
                        <span class="lg-side-label">Duration</span>
                        <span class="lg-side-val">{{ $durationMins }} mins</span>
                    </div>
                    <div class="lg-side-item">
                        <span class="lg-side-label">Date</span>
                        <span class="lg-side-val">{{ date('d M Y, H:i', strtotime($analysis->created_at)) }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lg-side-card">
                <div class="lg-side-card-header">
                    <i class="fas fa-bolt text-warning" style="color: #F59E0B !important;"></i> Quick Actions
                </div>
                <div class="lg-side-card-body">
                    <a href="{{ route('aiAnalysisDetail', $analysis->id) }}" class="lg-btn-action-full btn-action-primary">
                        <i class="fas fa-brain"></i> View AI Analysis
                    </a>
                    <button type="button" onclick="copyTranscriptToClipboard()" class="lg-btn-action-full btn-action-secondary">
                        <i class="fas fa-copy"></i> Copy Transcript
                    </button>
                    <button type="button" onclick="exportTranscriptTxt()" class="lg-btn-action-full btn-action-secondary">
                        <i class="fas fa-download"></i> Export Transcript (.txt)
                    </button>
                    <a href="{{ route('callTranscriptsList') }}" class="lg-btn-action-full btn-action-secondary">
                        <i class="fas fa-list"></i> All Transcripts
                    </a>
                </div>
            </div>

            <!-- Mini AI Insight Card (If AI signals exist) -->
            @if(!empty($analysis->interest_level) || !empty($analysis->sentiment))
                <div class="lg-side-card">
                    <div class="lg-side-card-header">
                        <i class="fas fa-sparkles text-primary" style="color: var(--lg-accent) !important;"></i> AI Intelligence Signals
                    </div>
                    <div class="lg-side-card-body">
                        @if($analysis->interest_level)
                            <div class="lg-side-item">
                                <span class="lg-side-label">Customer Interest</span>
                                <span class="badge" style="background: #DCFCE7; color: #166534; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 12px;">
                                    {{ ucfirst(strtolower($analysis->interest_level)) }}
                                </span>
                            </div>
                        @endif
                        @if($analysis->sentiment)
                            <div class="lg-side-item">
                                <span class="lg-side-label">Sentiment</span>
                                <span class="badge" style="background: #DBEAFE; color: var(--lg-accent-hover); font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 12px;">
                                    {{ ucfirst(strtolower($analysis->sentiment)) }}
                                </span>
                            </div>
                        @endif
                        @if($analysis->intent)
                            <div class="lg-side-item">
                                <span class="lg-side-label">Intent</span>
                                <span style="font-weight: 600; font-size: 12.5px; color: #7C3AED;">
                                    {{ $analysis->intent }}
                                </span>
                            </div>
                        @endif

                        <div class="pt-3 mt-2 border-top text-center" style="border-color: #F1F5F9 !important;">
                            <a href="{{ route('aiAnalysisDetail', $analysis->id) }}" class="small font-weight-bold" style="color: var(--lg-accent); text-decoration: none;">
                                View Full Intelligence Report &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

</div>

<!-- Copy Feedback Toast Notification -->
<div id="copyToast" class="lg-toast-notice">
    <i class="fas fa-check-circle text-success me-2"></i> Transcript copied to clipboard!
</div>

@endsection

@push("after-scripts")
<script>
// Raw transcript text from server for copy/export
var rawTranscriptText = @json($analysis->transcript ?? '');

// Filter transcript dialogue messages by search query
function filterTranscriptMessages(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#chatDialogueStream .lg-msg-row');
    rows.forEach(function(row) {
        var text = (row.textContent || '').toLowerCase();
        if (text.indexOf(q) !== -1) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}

// Copy full transcript text to clipboard
function copyTranscriptToClipboard() {
    if (!rawTranscriptText) {
        alert('No transcript text available to copy.');
        return;
    }
    navigator.clipboard.writeText(rawTranscriptText).then(function() {
        showToast('Transcript copied to clipboard!');
    }).catch(function() {
        // Fallback for older browsers
        var textarea = document.createElement('textarea');
        textarea.value = rawTranscriptText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Transcript copied to clipboard!');
    });
}

// Export transcript as .txt file
function exportTranscriptTxt() {
    if (!rawTranscriptText) {
        alert('No transcript text available to export.');
        return;
    }
    var filename = 'transcript_call_' + '{{ $analysis->call_id }}' + '_' + '{{ date("Ymd_His") }}' + '.txt';
    var header = "=====================================================\n" +
                 "LEADGER CALL TRANSCRIPT - CALL #{{ $analysis->call_id }}\n" +
                 "Agent: {{ $agentName }}\n" +
                 "Customer: {{ $customerName }}\n" +
                 "Campaign: {{ $campaignName }}\n" +
                 "Duration: {{ $durationMins }} mins\n" +
                 "Date: {{ date('d M Y H:i', strtotime($analysis->created_at)) }}\n" +
                 "=====================================================\n\n";
    
    var blob = new Blob([header + rawTranscriptText], { type: 'text/plain;charset=utf-8' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Temporary feedback toast
function showToast(message) {
    var toast = document.getElementById('copyToast');
    if (!toast) return;
    toast.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> ' + message;
    toast.style.display = 'block';
    setTimeout(function() {
        toast.style.display = 'none';
    }, 2500);
}
</script>
@endpush
