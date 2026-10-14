@extends('dashboard.layouts.master')
@section('title', 'System Settings')

@push("after-styles")
<style>
/* LeadGER Corporate System Settings Redesign */
.lg-settings-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px 30px;
    box-sizing: border-box;
    min-height: calc(100vh - 72px);
}

/* 1. Page Header with Icon & Breadcrumb */
.lg-header-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.lg-header-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    flex-shrink: 0;
}

.lg-header-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-breadcrumb-label {
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

/* 2. Main Settings Card Container */
.lg-settings-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

/* 3. Navigation Tabs Bar */
.lg-settings-tabs-bar {
    display: flex;
    align-items: center;
    gap: 32px;
    padding: 0 28px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
    overflow-x: auto;
}

.lg-tab-item {
    display: inline-flex;
    align-items: center;
    padding: 18px 0;
    font-size: 14px;
    font-weight: 500;
    color: #64748B;
    text-decoration: none !important;
    border-bottom: 3px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
    cursor: pointer;
}

.lg-tab-item:hover {
    color: var(--lg-accent);
}

.lg-tab-item.active {
    color: #0F172A;
    font-weight: 700;
    border-bottom-color: #0F172A;
}

/* 4. Form Body Area */
.lg-settings-body {
    padding: 32px 36px 36px 36px;
}

.lg-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px 36px;
    margin-bottom: 20px;
}

@media (max-width: 991px) {
    .lg-form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.lg-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-form-label {
    font-size: 13.5px;
    font-weight: 500;
    color: #475569;
    margin: 0;
}

.lg-form-control {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-form-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-form-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 14px center;
    cursor: pointer;
}

.lg-helper-text {
    font-size: 13px;
    color: #64748B;
    margin: 0 0 28px 0;
    line-height: 1.5;
}

/* 5. Buttons Section */
.lg-btn-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-btn-primary {
    height: 44px;
    padding: 0 24px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
}

.lg-btn-primary:hover {
    background: #0D52C9;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(20, 100, 244, 0.3);
}

.lg-btn-secondary {
    height: 44px;
    padding: 0 20px;
    background: #FFFFFF;
    color: #0F172A;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.lg-btn-secondary:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
}

.lg-toast-success {
    position: fixed;
    top: 24px;
    right: 24px;
    background: #10B981;
    color: #FFFFFF;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>
@endpush

@section('content')
<div class="lg-settings-wrapper">

    <!-- SUCCESS TOAST -->
    <div id="systemSettingsSuccessToast" class="lg-toast-success" style="display: none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>System settings saved successfully!</span>
    </div>

    <!-- 1. PAGE HEADER -->
    <div class="lg-header-row">
        <div class="lg-header-icon-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
        </div>
        <div class="lg-header-text-group">
            <span class="lg-breadcrumb-label">Settings</span>
            <h1 class="lg-title-main">System settings</h1>
        </div>
    </div>

    <!-- 2. MAIN SETTINGS CARD CONTAINER -->
    <div class="lg-settings-card">

        <!-- FORM BODY -->
        <div class="lg-settings-body">
            <form id="systemSettingsForm" method="POST" action="{{ route('webmasterSettingsUpdate') }}" onsubmit="return lgSaveSystemSettings(event)">
                @csrf
                <input type="hidden" name="active_tab" value="systemSettingsTab" />

                <!-- TWO COLUMN GRID -->
                <div class="lg-form-grid">
                    
                    <!-- LEFT COLUMN ITEM 1 -->
                    <div class="lg-form-group">
                        <label class="lg-form-label" for="lg_org_name">Organisation name</label>
                        <input type="text" 
                               name="site_title_en" 
                               id="lg_org_name" 
                               class="lg-form-control" 
                               value="{{ $WebmasterSetting->site_title_en ?? 'Ametecs India Pvt Ltd' }}" 
                               required>
                    </div>

                    <!-- RIGHT COLUMN ITEM 1 -->
                    <div class="lg-form-group">
                        <label class="lg-form-label" for="lg_timezone">Time zone</label>
                        <select name="timezone" id="lg_timezone" class="lg-form-control lg-form-select">
                            <option value="Asia/Kolkata (IST)" selected>Asia/Kolkata (IST)</option>
                            <option value="UTC">UTC (Coordinated Universal Time)</option>
                            <option value="America/New_York (EST)">America/New_York (EST)</option>
                            <option value="Europe/London (GMT)">Europe/London (GMT)</option>
                            <option value="Asia/Dubai (GST)">Asia/Dubai (GST)</option>
                            <option value="Asia/Singapore (SGT)">Asia/Singapore (SGT)</option>
                        </select>
                    </div>

                    <!-- LEFT COLUMN ITEM 2 -->
                    <div class="lg-form-group">
                        <label class="lg-form-label" for="lg_daily_target">Daily call target per telecaller</label>
                        <input type="number" 
                               name="daily_call_target" 
                               id="lg_daily_target" 
                               class="lg-form-control" 
                               value="200" 
                               required>
                    </div>

                    <!-- RIGHT COLUMN ITEM 2 -->
                    <div class="lg-form-group">
                        <label class="lg-form-label" for="lg_idle_grace">Idle grace between calls (seconds)</label>
                        <input type="number" 
                               name="idle_grace_time" 
                               id="lg_idle_grace" 
                               class="lg-form-control" 
                               value="60" 
                               required>
                    </div>

                </div>

                <!-- HELPER TEXT -->
                <p class="lg-helper-text">
                    The daily target drives the productivity %. The idle grace sets how long a gap between calls can be before it counts as idle.
                </p>

                <!-- BUTTONS ROW -->
                <div class="lg-btn-row">
                    <button type="submit" class="lg-btn-primary" id="lgBtnSaveSystemSettings">
                        Save system settings
                    </button>
                    
                    <button type="button" class="lg-btn-secondary" onclick="lgResetDemoData()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 4v6h-6"/>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                        <span>Reset demo data</span>
                    </button>
                </div>

            </form>
        </div>

    </div>

</div>
@endsection

@push("after-scripts")
    <script type="text/javascript">
        $(document).ready(function () {
            // Restore saved settings from localStorage if available
            try {
                const saved = localStorage.getItem('lg_system_settings');
                if (saved) {
                    const data = JSON.parse(saved);
                    if (data.org_name) $('#lg_org_name').val(data.org_name);
                    if (data.timezone) $('#lg_timezone').val(data.timezone);
                    if (data.daily_target) $('#lg_daily_target').val(data.daily_target);
                    if (data.idle_grace) $('#lg_idle_grace').val(data.idle_grace);
                }
            } catch (e) { console.log(e); }
        });

        function lgSaveSystemSettings(event) {
            const data = {
                org_name: $('#lg_org_name').val(),
                timezone: $('#lg_timezone').val(),
                daily_target: $('#lg_daily_target').val(),
                idle_grace: $('#lg_idle_grace').val()
            };
            try {
                localStorage.setItem('lg_system_settings', JSON.stringify(data));
            } catch(e) {}

            const $toast = $('#systemSettingsSuccessToast');
            $toast.fadeIn(200);
            setTimeout(function() {
                $toast.fadeOut(300);
            }, 3000);

            return true;
        }

        function lgResetDemoData() {
            if (confirm('Are you sure you want to reset demo data to initial values?')) {
                $('#lg_org_name').val('Ametecs India Pvt Ltd');
                $('#lg_timezone').val('Asia/Kolkata (IST)');
                $('#lg_daily_target').val('200');
                $('#lg_idle_grace').val('60');
                
                try {
                    localStorage.removeItem('lg_system_settings');
                } catch(e) {}

                alert('Demo data has been reset to default values.');
            }
        }
    </script>
@endpush