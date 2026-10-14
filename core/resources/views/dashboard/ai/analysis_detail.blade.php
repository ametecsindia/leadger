@extends('dashboard.layouts.master')
@section('title', 'Call #' . $analysis->call_id . ' - AI Analysis')

@push("after-styles")
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.lg-detail-page {
    padding: 20px 24px 40px 24px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
}

/* 1. Unobtrusive Back Nav Bar (No Large Header) */
.lg-detail-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 12px;
    flex-wrap: wrap;
}
.lg-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    padding: 7px 14px;
    transition: all 0.15s ease;
}
.lg-btn-back:hover {
    color: #0F172A;
    background: #F1F5F9;
    border-color: #94A3B8;
    text-decoration: none;
}
.lg-topbar-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-meta-badge {
    font-size: 12px;
    font-weight: 600;
    color: var(--lg-accent);
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    padding: 5px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
}

/* Status Tags */
.lg-status-tag {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
}
.status-completed { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
.status-processing { background: #DBEAFE; color: var(--lg-accent-hover); border: 1px solid #BFDBFE; }
.status-pending { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
.status-failed { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

/* 2. Call Overview Card */
.lg-overview-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 20px;
}
.lg-overview-card-header {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lg-overview-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
}
@media (max-width: 991px) {
    .lg-overview-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }
}
@media (max-width: 575px) {
    .lg-overview-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
.lg-overview-item {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.lg-overview-label {
    font-size: 11px;
    font-weight: 600;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.lg-overview-val {
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
}
.lg-call-link:hover {
    text-decoration: underline;
}

/* 3. Insight Cards (3 Columns) */
.lg-insights-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 767px) {
    .lg-insights-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
.lg-insight-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.lg-insight-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.lg-insight-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.lg-insight-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.lg-insight-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    width: fit-content;
}
.lg-insight-icon-ghost {
    font-size: 26px;
    color: #CBD5E1;
    opacity: 0.6;
}

/* Badges */
.badge-interest-high { background: #DCFCE7; color: #15803D; }
.badge-interest-med { background: #DBEAFE; color: var(--lg-accent-hover); }
.badge-interest-low { background: #FFEDD5; color: #C2410C; }

.badge-sent-pos { background: #DCFCE7; color: #15803D; }
.badge-sent-neu { background: #F1F5F9; color: #475569; }
.badge-sent-neg { background: #FEE2E2; color: #B91C1C; }

.badge-intent { background: #F5F3FF; color: #7C3AED; }

/* 4. Two-Column Layout (Summary & Action Items) */
.lg-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 991px) {
    .lg-content-grid {
        grid-template-columns: 1fr;
    }
}
.lg-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.lg-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.lg-card-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-card-body {
    padding: 20px;
}

/* Executive Summary Card Styling */
.lg-summary-text {
    font-size: 14px;
    line-height: 1.65;
    color: #334155;
    margin-bottom: 20px;
}
.lg-section-subtitle {
    font-size: 13px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lg-key-points-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 14px 16px;
}
.lg-key-points-content {
    font-family: inherit;
    font-size: 13px;
    color: #334155;
    line-height: 1.6;
    margin: 0;
    white-space: pre-wrap;
}

/* Action Items Card Styling */
.lg-action-item {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 12px;
}
.lg-action-item:last-child {
    margin-bottom: 0;
}
.lg-action-item-blue { border-left: 4px solid var(--lg-accent); }
.lg-action-item-cyan { border-left: 4px solid #06B6D4; }
.lg-action-item-green { border-left: 4px solid #10B981; }
.lg-action-item-purple { border-left: 4px solid #8B5CF6; }

.lg-action-item-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.lg-action-item-val {
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    line-height: 1.4;
}

/* 5. Conversation Transcript Chat Section */
.lg-transcript-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.lg-transcript-body {
    padding: 24px;
    background: #FAFBFD;
    max-height: 520px;
    overflow-y: auto;
}
.lg-chat-stream {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.lg-chat-bubble-wrap {
    display: flex;
    width: 100%;
}
.lg-chat-bubble-wrap.is-agent {
    justify-content: flex-start;
}
.lg-chat-bubble-wrap.is-customer {
    justify-content: flex-end;
}
.lg-chat-bubble {
    max-width: 78%;
    border-radius: 12px;
    padding: 14px 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.lg-chat-bubble.is-agent {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-left: 4px solid var(--lg-accent);
}
.lg-chat-bubble.is-customer {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-right: 4px solid #10B981;
}
.lg-chat-author {
    font-size: 11.5px;
    font-weight: 700;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lg-chat-author.is-agent { color: var(--lg-accent); }
.lg-chat-author.is-customer { color: #059669; }

.lg-chat-text {
    font-size: 13.5px;
    line-height: 1.55;
    color: #1E293B;
}

/* Empty transcript */
.lg-transcript-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94A3B8;
}
.lg-transcript-empty i {
    font-size: 36px;
    color: #CBD5E1;
    margin-bottom: 10px;
}
</style>
@endpush

@section('content')
<div class="lg-detail-page">

    <!-- 1. TOP BAR METADATA -->
    <div class="lg-detail-topbar" style="justify-content: flex-end;">
        <div class="lg-topbar-meta">
            <span class="lg-meta-badge">
                <i class="fas fa-phone me-1"></i> Call #{{ $analysis->call_id }}
            </span>
            @if($analysis->status === 'completed')
                <span class="lg-status-tag status-completed">
                    <i class="fas fa-circle-check me-1"></i> Analysis Completed
                </span>
            @elseif($analysis->status === 'processing')
                <span class="lg-status-tag status-processing">
                    <i class="fas fa-spinner fa-spin me-1"></i> Processing
                </span>
            @elseif($analysis->status === 'pending')
                <span class="lg-status-tag status-pending">
                    <i class="fas fa-clock me-1"></i> Pending
                </span>
            @else
                <span class="lg-status-tag status-failed">
                    <i class="fas fa-circle-xmark me-1"></i> {{ ucfirst($analysis->status) }}
                </span>
            @endif

            @if($analysis->status === 'failed')
                <a href="{{ route('aiAnalysisRetry', $analysis->id) }}" class="btn btn-sm btn-warning text-dark py-1 px-2" style="font-size: 12px; font-weight: 600; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-sync-alt me-1"></i> Retry
                </a>
            @endif
        </div>
    </div>

    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px;">
            <i class="fas fa-circle-check me-2"></i>{{ session('doneMessage') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 2. CALL OVERVIEW SECTION (Compact horizontal card with equal-width blocks) -->
    <div class="lg-overview-card">
        <div class="lg-overview-card-header">
            <i class="fas fa-circle-info text-primary"></i> Call Overview
        </div>
        <div class="lg-overview-grid">
            <!-- Call ID -->
            <div class="lg-overview-item">
                <span class="lg-overview-label">Call ID</span>
                <span class="lg-overview-val">
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
            <div class="lg-overview-item">
                <span class="lg-overview-label">Agent</span>
                <span class="lg-overview-val" title="{{ $agentName }}">{{ $agentName }}</span>
            </div>

            <!-- Customer -->
            <div class="lg-overview-item">
                <span class="lg-overview-label">Customer</span>
                <span class="lg-overview-val" title="{{ $customerName }}">{{ $customerName }}</span>
            </div>

            <!-- Campaign -->
            <div class="lg-overview-item">
                <span class="lg-overview-label">Campaign</span>
                <span class="lg-overview-val" title="{{ $campaignName }}">{{ $campaignName }}</span>
            </div>

            <!-- Duration -->
            <div class="lg-overview-item">
                <span class="lg-overview-label">Duration</span>
                <span class="lg-overview-val">
                    {{ round(($analysis->audio_duration_seconds ?: $callLog->duration ?: 0) / 60, 1) }} mins
                </span>
            </div>

            <!-- Call Date -->
            <div class="lg-overview-item">
                <span class="lg-overview-label">Call Date</span>
                <span class="lg-overview-val" title="{{ date('d M Y, H:i', strtotime($analysis->created_at)) }}">
                    {{ date('d M Y, H:i', strtotime($analysis->created_at)) }}
                </span>
            </div>
        </div>
    </div>

    <!-- 3. THREE COMPACT INSIGHT CARDS -->
    <div class="lg-insights-grid">
        
        <!-- Insight 1: Customer Interest -->
        @php
            $intLevel = strtoupper($analysis->interest_level ?? 'HIGH');
            $intClass = match($intLevel) {
                'HIGH' => 'badge-interest-high',
                'MEDIUM' => 'badge-interest-med',
                'LOW' => 'badge-interest-low',
                default => 'badge-interest-high'
            };
            $intIcon = match($intLevel) {
                'HIGH' => 'fa-fire',
                'MEDIUM' => 'fa-gauge-simple',
                'LOW' => 'fa-arrow-down',
                default => 'fa-fire'
            };
        @endphp
        <div class="lg-insight-card">
            <div class="lg-insight-info">
                <span class="lg-insight-label">Customer Interest</span>
                <span class="lg-insight-badge {{ $intClass }}">
                    <i class="fas {{ $intIcon }}"></i> {{ ucfirst(strtolower($analysis->interest_level ?: 'High')) }}
                </span>
            </div>
            <i class="fas fa-chart-line lg-insight-icon-ghost"></i>
        </div>

        <!-- Insight 2: Sentiment Analysis -->
        @php
            $sentLevel = strtoupper($analysis->sentiment ?? 'POSITIVE');
            $sentClass = match($sentLevel) {
                'POSITIVE' => 'badge-sent-pos',
                'NEUTRAL' => 'badge-sent-neu',
                'NEGATIVE' => 'badge-sent-neg',
                default => 'badge-sent-pos'
            };
            $sentIcon = match($sentLevel) {
                'POSITIVE' => 'fa-face-smile',
                'NEUTRAL' => 'fa-face-meh',
                'NEGATIVE' => 'badge-sent-neg',
                default => 'fa-face-smile'
            };
        @endphp
        <div class="lg-insight-card">
            <div class="lg-insight-info">
                <span class="lg-insight-label">Sentiment Analysis</span>
                <span class="lg-insight-badge {{ $sentClass }}">
                    <i class="fas {{ $sentIcon }}"></i> {{ ucfirst(strtolower($analysis->sentiment ?: 'Positive')) }}
                </span>
            </div>
            <i class="fas fa-heart-pulse lg-insight-icon-ghost"></i>
        </div>

        <!-- Insight 3: Detected Intent -->
        <div class="lg-insight-card">
            <div class="lg-insight-info">
                <span class="lg-insight-label">Detected Intent</span>
                <span class="lg-insight-badge badge-intent">
                    <i class="fas fa-bullseye"></i> {{ $analysis->intent ?: 'Product Enquiry' }}
                </span>
            </div>
            <i class="fas fa-lightbulb lg-insight-icon-ghost"></i>
        </div>

    </div>

    <!-- 4. TWO-COLUMN LAYOUT: EXECUTIVE SUMMARY & ACTION ITEMS -->
    <div class="lg-content-grid">
        
        <!-- Left / Main Column: Executive Call Summary -->
        <div class="lg-card">
            <div class="lg-card-header">
                <h6 class="lg-card-title">
                    <i class="fas fa-file-lines text-primary"></i> Executive Call Summary
                </h6>
            </div>
            <div class="lg-card-body">
                <p class="lg-summary-text">
                    {{ $analysis->summary ?: 'No summary generated for this call recording.' }}
                </p>

                <!-- Key Discussion Points (Existing Analysis Data) -->
                @if($analysis->key_points)
                    <div class="mt-4">
                        <h6 class="lg-section-subtitle">
                            <i class="fas fa-list-check text-primary"></i> Key Discussion Points
                        </h6>
                        <div class="lg-key-points-box">
                            <pre class="lg-key-points-content">{{ $analysis->key_points }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right / Side Column: Action Items & Next Steps -->
        <div class="lg-card">
            <div class="lg-card-header">
                <h6 class="lg-card-title">
                    <i class="fas fa-clipboard-check" style="color: #D97706;"></i> Action Items & Next Steps
                </h6>
            </div>
            <div class="lg-card-body">
                
                <!-- Recommended Next Action -->
                <div class="lg-action-item lg-action-item-blue">
                    <div class="lg-action-item-label">Recommended Next Action</div>
                    <div class="lg-action-item-val">
                        {{ $analysis->next_action ?: 'Follow-up with customer regarding inquiry.' }}
                    </div>
                </div>

                <!-- Follow-up Date & Info -->
                <div class="lg-action-item lg-action-item-cyan">
                    <div class="lg-action-item-label">Follow-up Information</div>
                    <div class="lg-action-item-val">
                        @if($analysis->follow_up_date)
                            <i class="far fa-calendar-alt text-info me-1"></i>
                            Yes (Tomorrow 3:00 PM)
                        @else
                            <span class="text-muted" style="font-weight: 500;">No scheduled follow-up</span>
                        @endif
                    </div>
                </div>

                <!-- Automatic Disposition -->
                <div class="lg-action-item lg-action-item-green">
                    <div class="lg-action-item-label">Automatic Disposition</div>
                    <div class="lg-action-item-val" style="color: #059669;">
                        <i class="fas fa-tag me-1"></i>
                        {{ $analysis->auto_disposition ?: 'Interested / Follow-up' }}
                    </div>
                </div>

                <!-- Emotion Analysis -->
                @if(!empty($analysis->emotion_analysis))
                    <div class="lg-action-item" style="border-left: 4px solid #7C3AED;">
                        <div class="lg-action-item-label" style="color: #7C3AED;">
                            <i class="fas fa-heart-pulse me-1"></i> Emotion Analysis
                        </div>
                        <div class="lg-action-item-val" style="font-size: 13px; font-weight: 500; color: #334155;">
                            {{ $analysis->emotion_analysis }}
                        </div>
                    </div>
                @endif

                <!-- Quality Flags -->
                @if(!empty($analysis->quality_flags))
                    <div class="lg-action-item" style="border-left: 4px solid #059669;">
                        <div class="lg-action-item-label" style="color: #059669;">
                            <i class="fas fa-shield-alt me-1"></i> Quality Flags
                        </div>
                        <div class="lg-action-item-val" style="font-size: 13px; font-weight: 500; color: #334155;">
                            {{ $analysis->quality_flags }}
                        </div>
                    </div>
                @endif

                <!-- Call Analytics Details -->
                <div class="lg-action-item lg-action-item-purple">
                    <div class="lg-action-item-label">Analysis Metadata</div>
                    <div class="lg-action-item-val" style="font-size: 12.5px; color: #475569; font-weight: 500;">
                        <div><i class="fas fa-stopwatch me-1"></i> Audio Duration: {{ $analysis->audio_duration_seconds ?: ($callLog->duration ?: 0) }}s</div>
                        @if($analysis->ai_cost)
                            <div class="mt-1"><i class="fas fa-coins me-1"></i> AI Compute: ₹{{ number_format($analysis->ai_cost, 4) }}</div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- 5. CONVERSATION TRANSCRIPT (Chat-style visually distinct bubbles) -->
    <div class="lg-transcript-card">
        <div class="lg-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="lg-card-title">
                <i class="fas fa-comments text-primary"></i> Conversation Transcript
            </h6>
            <div class="d-flex align-items-center gap-3">
                @if(!empty($callLog))
                    <div class="d-flex align-items-center gap-2">
                        <audio id="detail-call-audio" preload="none" src="{{ route('downloadRecordings', [$callLog->id, 0]) }}?play=1"></audio>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleDetailAudio()" id="btn-detail-play" style="font-size: 12px; font-weight: 600; border-radius: 6px; padding: 4px 12px;">
                            <i class="fas fa-play me-1" id="icon-detail-play"></i> Play Call
                        </button>
                        <a href="{{ route('downloadRecordings', [$callLog->id, 0]) }}" class="btn btn-sm btn-outline-secondary" download style="font-size: 12px; font-weight: 600; border-radius: 6px; padding: 4px 12px;">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    </div>
                @endif
                <span class="text-muted small">
                    <i class="fas fa-microphone-lines me-1"></i> AI Speech-to-Text
                </span>
            </div>
        </div>
        <div class="lg-transcript-body">
            @if(!empty($analysis->transcript))
                @php
                    $transcriptTurns = [];
                    $currentSpeaker = null;
                    $currentText = [];
                    foreach(explode("\n", $analysis->transcript) as $rawLine) {
                        $trimmed = trim($rawLine);
                        if ($trimmed === '') continue;
                        if (preg_match('/^(Agent|Telecaller)(\s*\([^)]*\))?:\s*(.*)$/i', $trimmed, $m)) {
                            if ($currentSpeaker !== null && !empty($currentText)) {
                                $transcriptTurns[] = ['speaker' => $currentSpeaker, 'text' => implode(' ', $currentText)];
                            }
                            $currentSpeaker = 'agent';
                            $currentText = !empty($m[3]) ? [$m[3]] : [];
                        } elseif (preg_match('/^(Customer|User|Lead)(\s*\([^)]*\))?:\s*(.*)$/i', $trimmed, $m)) {
                            if ($currentSpeaker !== null && !empty($currentText)) {
                                $transcriptTurns[] = ['speaker' => $currentSpeaker, 'text' => implode(' ', $currentText)];
                            }
                            $currentSpeaker = 'customer';
                            $currentText = !empty($m[3]) ? [$m[3]] : [];
                        } else {
                            if ($currentSpeaker !== null) {
                                $currentText[] = $trimmed;
                            } else {
                                $transcriptTurns[] = ['speaker' => 'agent', 'text' => $trimmed];
                            }
                        }
                    }
                    if ($currentSpeaker !== null && !empty($currentText)) {
                        $transcriptTurns[] = ['speaker' => $currentSpeaker, 'text' => implode(' ', $currentText)];
                    }
                @endphp
                <div class="lg-chat-stream">
                    @foreach($transcriptTurns as $turn)
                        @php
                            $isAgent = ($turn['speaker'] === 'agent');
                        @endphp
                        <div class="lg-chat-bubble-wrap {{ $isAgent ? 'is-agent' : 'is-customer' }}">
                            <div class="lg-chat-bubble {{ $isAgent ? 'is-agent' : 'is-customer' }}">
                                <div class="lg-chat-author {{ $isAgent ? 'is-agent' : 'is-customer' }}">
                                    @if($isAgent)
                                        <i class="fas fa-headset"></i> Agent ({{ $agentName }})
                                    @else
                                        <i class="fas fa-user"></i> Customer ({{ $customerName }})
                                    @endif
                                </div>
                                <div class="lg-chat-text">
                                    {{ $turn['text'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="lg-transcript-empty">
                    <i class="fas fa-file-audio"></i>
                    <h6>No transcript generated</h6>
                    <p class="small m-0">The transcript for this call recording is not available.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('after-scripts')
<script>
    function toggleDetailAudio() {
        var audio = document.getElementById('detail-call-audio');
        var icon = document.getElementById('icon-detail-play');
        var btn = document.getElementById('btn-detail-play');
        if (!audio) return;
        if (audio.paused) {
            audio.play();
            if (icon) icon.className = 'fas fa-pause me-1';
            if (btn) btn.innerHTML = '<i class="fas fa-pause me-1"></i> Pause Call';
        } else {
            audio.pause();
            if (icon) icon.className = 'fas fa-play me-1';
            if (btn) btn.innerHTML = '<i class="fas fa-play me-1"></i> Play Call';
        }
        audio.onended = function() {
            if (icon) icon.className = 'fas fa-play me-1';
            if (btn) btn.innerHTML = '<i class="fas fa-play me-1"></i> Play Call';
        };
    }
</script>
@endpush
