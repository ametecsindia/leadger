@php
    $total = isset($totalChannels) && $totalChannels !== '' && $totalChannels !== null ? (int)$totalChannels : 0;
    $used = isset($usedChannels) && $usedChannels !== '' && $usedChannels !== null ? (int)$usedChannels : 0;
    $available = isset($availableChannels) && $availableChannels !== '' && $availableChannels !== null
        ? (int)$availableChannels
        : max(0, $total - $used);
    $expiry = !empty($expiryDate) ? $expiryDate : 'N/A';
    if ($expiry !== 'N/A') {
        $time = strtotime(str_replace('/', '-', $expiry));
        if ($time) {
            $expiry = date('Y/m/d', $time);
        }
    }
@endphp

<!-- CHANNEL SUMMARY CARDS (SHARED COMPONENT) -->
<div class="channel-stats channel-stats-wrapper">
    <div class="channel-stats-grid">
        <!-- 1. TOTAL CHANNELS -->
        <div class="stat-card stat-total lg-ch-card lg-ch-total">
            <div class="stat-content lg-ch-content">
                <span class="stat-label lg-ch-label">TOTAL CHANNELS</span>
                <span class="stat-value lg-ch-value">{{ $total }}</span>
            </div>
            <div class="stat-icon-box lg-ch-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </div>
        </div>

        <!-- 2. USED CHANNELS -->
        <div class="stat-card stat-used lg-ch-card lg-ch-used">
            <div class="stat-content lg-ch-content">
                <span class="stat-label lg-ch-label">USED CHANNELS</span>
                <span class="stat-value lg-ch-value">{{ $used }}</span>
            </div>
            <div class="stat-icon-box lg-ch-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>

        <!-- 3. AVAILABLE CHANNELS -->
        <div class="stat-card stat-available lg-ch-card lg-ch-available">
            <div class="stat-content lg-ch-content">
                <span class="stat-label lg-ch-label">AVAILABLE CHANNELS</span>
                <span class="stat-value lg-ch-value">{{ $available }}</span>
            </div>
            <div class="stat-icon-box lg-ch-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>

        <!-- 4. EXPIRY -->
        <div class="stat-card stat-expiry lg-ch-card lg-ch-expiry">
            <div class="stat-content lg-ch-content">
                <span class="stat-label lg-ch-label">EXPIRY</span>
                <span class="stat-value stat-date lg-ch-value lg-ch-date">{{ $expiry }}</span>
            </div>
            <div class="stat-icon-box lg-ch-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   CHANNEL SUMMARY CARDS – SCOPED STYLES
   ========================================================================== */
.channel-stats.channel-stats-wrapper {
    width: 100%;
    margin-bottom: 24px;
    box-sizing: border-box;
}

.channel-stats .channel-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 992px) {
    .channel-stats .channel-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .channel-stats .channel-stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Base card styling */
.channel-stats .stat-card {
    border-radius: 14px !important;
    padding: 16px 20px !important;
    min-height: 100px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
    user-select: none;
    border: none !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
}

.channel-stats .stat-card:hover {
    transform: translateY(-2px);
}

.channel-stats .stat-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    color: inherit !important;
}

.channel-stats .stat-label {
    font-size: 13px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin-bottom: 6px !important;
    line-height: 1.2 !important;
    color: inherit !important;
    opacity: 0.95;
    white-space: nowrap;
}

.channel-stats .stat-value {
    font-size: 34px !important;
    font-weight: 800 !important;
    line-height: 1.1 !important;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
    color: inherit !important;
}

.channel-stats .stat-date {
    font-size: 26px !important;
    letter-spacing: -0.5px;
    white-space: nowrap;
}

@media (min-width: 1400px) {
    .channel-stats .stat-date {
        font-size: 30px !important;
    }
}

.channel-stats .stat-icon-box {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0;
    margin-left: 12px;
}

.channel-stats .stat-icon-box svg {
    color: inherit !important;
    stroke: currentColor !important;
}

/* 1. TOTAL CHANNELS: Blue (#4f86f7 to #3b73ee), white text */
.channel-stats .stat-card.stat-total {
    background-color: #4f86f7 !important;
    background: linear-gradient(135deg, #4f86f7 0%, #3b73ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 24px rgba(79, 134, 247, 0.35) !important;
}
.channel-stats .stat-card.stat-total:hover {
    box-shadow: 0 12px 28px rgba(79, 134, 247, 0.45) !important;
}
.channel-stats .stat-card.stat-total .stat-icon-box {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
}

/* 2. USED CHANNELS: Green (#2ec58c to #22b47c), white text */
.channel-stats .stat-card.stat-used {
    background-color: #2ec58c !important;
    background: linear-gradient(135deg, #2ec58c 0%, #22b47c 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 24px rgba(46, 197, 140, 0.35) !important;
}
.channel-stats .stat-card.stat-used:hover {
    box-shadow: 0 12px 28px rgba(46, 197, 140, 0.45) !important;
}
.channel-stats .stat-card.stat-used .stat-icon-box {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
}

/* 3. AVAILABLE CHANNELS: Orange (#f5a83a to #f2992a), dark text #2b1a00 */
.channel-stats .stat-card.stat-available {
    background-color: #f5a83a !important;
    background: linear-gradient(135deg, #f5a83a 0%, #f2992a 100%) !important;
    color: #2b1a00 !important;
    box-shadow: 0 8px 24px rgba(245, 168, 58, 0.35) !important;
}
.channel-stats .stat-card.stat-available:hover {
    box-shadow: 0 12px 28px rgba(245, 168, 58, 0.45) !important;
}
.channel-stats .stat-card.stat-available .stat-icon-box {
    background: rgba(0, 0, 0, 0.12) !important;
    color: #2b1a00 !important;
}

/* 4. EXPIRY: Purple (#9b84ff to #8a72f5), white text */
.channel-stats .stat-card.stat-expiry {
    background-color: #9b84ff !important;
    background: linear-gradient(135deg, #9b84ff 0%, #8a72f5 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 24px rgba(155, 132, 255, 0.35) !important;
}
.channel-stats .stat-card.stat-expiry:hover {
    box-shadow: 0 12px 28px rgba(155, 132, 255, 0.45) !important;
}
.channel-stats .stat-card.stat-expiry .stat-icon-box {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
}

/* Ensure all child text/icons inherit respective card colors */
.channel-stats .stat-card * {
    color: inherit;
}
</style>
