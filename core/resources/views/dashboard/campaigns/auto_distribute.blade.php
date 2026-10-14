@extends('dashboard.layouts.master')
@section('title', 'Auto Distribute - LeadGER')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Corporate Modern Design System - Auto Distribute Page
   ========================================================================== */

.lg-auto-dist-wrapper {
    padding: 24px 30px 40px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #0F172A;
}

/* Page Header */
.lg-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(20, 100, 244, 0.1);
    color: var(--lg-accent);
    border: 1px solid rgba(20, 100, 244, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.12);
}

.lg-page-title {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-page-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin-top: 3px;
    margin-bottom: 0;
    font-weight: 500;
}

/* Alert Notification Banner */
.lg-alert-banner {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

.lg-alert-banner.warning {
    background: #FFFBEB;
    border: 1px solid #FCD34D;
    color: #B45309;
}

.lg-alert-banner.success {
    background: #ECFDF5;
    border: 1px solid #6EE7B7;
    color: #047857;
}

.lg-alert-banner.error {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    color: #B91C1C;
}

/* Single White Container / Card */
.lg-single-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 28px 32px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    max-width: 100%;
}

/* Field Group & Labels */
.lg-field-group {
    margin-bottom: 24px;
}

.lg-field-label {
    display: block;
    font-size: 13.5px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
    user-select: none;
}

/* Custom Campaign Dropdown */
.lg-campaign-field-wrap,
.lg-portfolio-field-wrap {
    width: 100%;
    max-width: 440px;
}

.lg-custom-dropdown-wrap {
    position: relative;
    width: 100%;
}

.lg-custom-dropdown-trigger {
    width: 100%;
    height: 44px;
    padding: 0 14px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    background-color: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.lg-custom-dropdown-trigger:hover,
.lg-custom-dropdown-trigger:focus,
.lg-custom-dropdown-wrap.open .lg-custom-dropdown-trigger {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-custom-dropdown-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 8px;
    flex: 1;
    text-align: left;
}

.lg-custom-dropdown-arrow {
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-left: 8px;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-arrow {
    transform: rotate(180deg);
    stroke: var(--lg-accent);
}

.lg-custom-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    z-index: 1050;
    max-height: 280px;
    overflow-y: auto;
    padding: 6px;
}

.lg-custom-dropdown-menu::-webkit-scrollbar {
    width: 6px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-track {
    background: #F8FAFC;
    border-radius: 4px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-menu {
    display: block;
}

.lg-custom-dropdown-search-box {
    padding: 4px 6px 8px 6px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 4px;
}

.lg-custom-dropdown-search {
    width: 100%;
    height: 34px;
    padding: 0 10px;
    font-size: 13px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    outline: none;
    background: #F8FAFC;
}

.lg-custom-dropdown-search:focus {
    border-color: var(--lg-accent);
    background: #FFFFFF;
}

.lg-custom-dropdown-option {
    padding: 9px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-custom-dropdown-option:hover {
    background-color: #F8FAFC;
    color: var(--lg-accent);
}

.lg-custom-dropdown-option.selected {
    background-color: #EFF6FF;
    color: var(--lg-accent);
    font-weight: 600;
}

/* Custom Square Checkboxes (16-18px) */
.lg-custom-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 17px;
    height: 17px;
    border: 1.5px solid #CBD5E1;
    border-radius: 4px;
    background-color: #FFFFFF;
    cursor: pointer;
    display: inline-grid;
    place-content: center;
    margin: 0;
    transition: all 0.15s ease;
    flex-shrink: 0;
    outline: none;
    vertical-align: middle;
}

.lg-custom-checkbox:hover {
    border-color: var(--lg-accent);
}

.lg-custom-checkbox:checked {
    background-color: var(--lg-accent);
    border-color: var(--lg-accent);
}

.lg-custom-checkbox:checked::after {
    content: "";
    width: 5px;
    height: 9px;
    border: solid #FFFFFF;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-top: -2px;
}

.lg-custom-checkbox:indeterminate {
    background-color: var(--lg-accent);
    border-color: var(--lg-accent);
}

.lg-custom-checkbox:indeterminate::after {
    content: "";
    width: 9px;
    height: 2px;
    background-color: #FFFFFF;
    border-radius: 1px;
}

.lg-custom-checkbox:focus-visible {
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.2);
}

/* Staff Selection Area */
.lg-staff-section {
    margin-top: 10px;
}

.lg-select-all-container {
    padding-bottom: 14px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 18px;
}

.lg-staff-checkbox-item {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
    margin: 0;
    padding: 6px 0;
    transition: color 0.15s ease;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
}

.lg-staff-checkbox-item:hover .lg-staff-name {
    color: var(--lg-accent);
}

.lg-staff-checkbox-item input:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.lg-staff-checkbox-item input:disabled ~ .lg-staff-name {
    color: #94A3B8;
    cursor: not-allowed;
}

.lg-staff-name {
    font-size: 14px;
    font-weight: 500;
    color: #1E293B;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

/* Clean Multi-Column Grid Layout: 3-column desktop, 2-column tablet, 1-column mobile */
.lg-staff-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    row-gap: 14px;
    column-gap: 24px;
    min-height: 60px;
}

@media (max-width: 991px) {
    .lg-staff-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .lg-staff-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

.lg-staff-empty-msg {
    grid-column: 1 / -1;
    color: #94A3B8;
    font-size: 13.5px;
    padding: 16px 0;
    font-style: italic;
}

/* Card Bottom Footer & Save Button */
.lg-card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #F1F5F9;
}

.lg-btn-save {
    background: var(--lg-accent);
    color: #FFFFFF;
    font-size: 14px;
    font-weight: 600;
    height: 42px;
    padding: 0 28px;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    user-select: none;
}

.lg-btn-save:hover:not(:disabled) {
    background: #0F52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.35);
}

.lg-btn-save:active:not(:disabled) {
    transform: translateY(1px);
}

.lg-btn-save:disabled {
    background: #94A3B8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 0.7;
}

.lg-btn-save .spinner {
    display: none;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #FFFFFF;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.lg-btn-save.loading .spinner {
    display: inline-block;
}

.lg-btn-save.loading .save-icon {
    display: none;
}
</style>
@endpush

@section('content')
<div class="lg-auto-dist-wrapper">

    <!-- 1. PAGE TITLE & HEADER -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-header-icon-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"></polyline>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <polyline points="7 23 3 19 7 15"></polyline>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </div>
            <div>
                <h1 class="lg-page-title">Auto Distribute</h1>
                <p class="lg-page-subtitle">Automatically distribute campaign leads across selected agents.</p>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION ALERT BANNER -->
    <div id="statusAlertBox" style="display: none;"></div>

    <!-- 2. MAIN CONTENT — SINGLE CONTAINER -->
    <div class="lg-single-card">

        <!-- Campaign Field -->
        <div class="lg-field-group">
            <div class="lg-campaign-field-wrap">
                <label class="lg-field-label">Campaign</label>
                <div class="lg-custom-dropdown-wrap" id="portfolioDropdownWrap">
                    <input type="hidden" name="campaign_id" id="selectedPortfolioId" value="{{ $selectedCampaignId ?? '' }}">
                    <div class="lg-custom-dropdown-trigger" id="portfolioDropdownTrigger" tabindex="0">
                        <span class="lg-custom-dropdown-label" id="portfolioDropdownLabel">
                            {{ $selectedCampaign ? $selectedCampaign->name : '-- Select Campaign --' }}
                        </span>
                        <svg class="lg-custom-dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu" id="portfolioDropdownMenu">
                        <div class="lg-custom-dropdown-search-box">
                            <input type="text" class="lg-custom-dropdown-search" id="portfolioSearchInput" placeholder="Search campaign..." autocomplete="off">
                        </div>
                        <div id="portfolioOptionsList">
                            @foreach($Campaigns as $camp)
                                <div class="lg-custom-dropdown-option {{ (isset($selectedCampaignId) && $selectedCampaignId == $camp->id) ? 'selected' : '' }}" data-value="{{ $camp->id }}" data-label="{{ $camp->name }}">
                                    <span>{{ $camp->name }}</span>
                                    @if(isset($selectedCampaignId) && $selectedCampaignId == $camp->id)
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff List Section -->
        <div class="lg-staff-section">
            <!-- Select All Checkbox -->
            <div class="lg-select-all-container">
                <label class="lg-staff-checkbox-item" for="chkSelectAllStaff" id="selectAllLabel">
                    <input type="checkbox" id="chkSelectAllStaff" class="lg-custom-checkbox" {{ (!isset($selectedCampaign) || !isset($campaignUsers) || $campaignUsers->count() === 0) ? 'disabled' : '' }}>
                    <span class="lg-staff-name" style="font-weight: 600; color: #0F172A;">Select All</span>
                </label>
            </div>

            <!-- Individual Staff Checkbox Grid (3 Columns Desktop, 2 Tablet, 1 Mobile) -->
            <div class="lg-staff-grid" id="staffGridContainer">
                @if(isset($selectedCampaign) && $selectedCampaign)
                    @if(isset($campaignUsers) && $campaignUsers->count() > 0)
                        @foreach($campaignUsers as $u)
                            <label class="lg-staff-checkbox-item" for="chkStaff_{{ $u->id }}">
                                <input type="checkbox" id="chkStaff_{{ $u->id }}" class="lg-custom-checkbox lg-staff-checkbox" value="{{ $u->id }}" data-name="{{ $u->name }}">
                                <span class="lg-staff-name" title="{{ $u->name }}">{{ $u->name }}</span>
                            </label>
                        @endforeach
                    @else
                        <div class="lg-staff-empty-msg">No members in this campaign</div>
                    @endif
                @else
                    <div class="lg-staff-empty-msg">Select a campaign to see its members</div>
                @endif
            </div>
        </div>

        <!-- 3. SAVE BUTTON -->
        <div class="lg-card-footer">
            <button type="button" class="lg-btn-save" id="btnSaveAutoDistribute">
                <span class="spinner"></span>
                <svg class="save-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Save</span>
            </button>
        </div>

    </div>

</div>
@endsection

@push("after-scripts")
<script>
document.addEventListener('DOMContentLoaded', function () {
    const portfolioWrap = document.getElementById('portfolioDropdownWrap');
    const portfolioTrigger = document.getElementById('portfolioDropdownTrigger');
    const portfolioLabel = document.getElementById('portfolioDropdownLabel');
    const portfolioInput = document.getElementById('selectedPortfolioId');
    const portfolioMenu = document.getElementById('portfolioDropdownMenu');
    const portfolioSearch = document.getElementById('portfolioSearchInput');
    const portfolioOptions = document.querySelectorAll('.lg-custom-dropdown-option');
    const staffGrid = document.getElementById('staffGridContainer');
    const chkSelectAll = document.getElementById('chkSelectAllStaff');
    const btnSave = document.getElementById('btnSaveAutoDistribute');
    const alertBox = document.getElementById('statusAlertBox');

    const detailsUrlBase = "{{ url('/admin/campaigns') }}";
    const executeUrl = "{{ route('campaignsAutoDistributeExecute') }}";
    const csrfToken = "{{ csrf_token() }}";

    // -------------------------------------------------------------
    // Alert Notification Helper
    // -------------------------------------------------------------
    function showAlert(type, message) {
        if (!message) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
            return;
        }

        let iconSvg = '';
        if (type === 'warning') {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
        } else if (type === 'success') {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        } else {
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        }

        alertBox.className = 'lg-alert-banner ' + type;
        alertBox.innerHTML = `${iconSvg}<span>${message}</span>`;
        alertBox.style.display = 'flex';

        // Auto scroll to top if needed
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // -------------------------------------------------------------
    // Dropdown Logic
    // -------------------------------------------------------------
    portfolioTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = portfolioWrap.classList.contains('open');
        portfolioWrap.classList.toggle('open', !isOpen);
        if (!isOpen) {
            portfolioSearch.value = '';
            filterDropdownOptions('');
            setTimeout(() => portfolioSearch.focus(), 50);
        }
    });

    document.addEventListener('click', function (e) {
        if (!portfolioWrap.contains(e.target)) {
            portfolioWrap.classList.remove('open');
        }
    });

    portfolioSearch.addEventListener('input', function (e) {
        filterDropdownOptions(e.target.value.toLowerCase().trim());
    });

    function filterDropdownOptions(query) {
        document.querySelectorAll('#portfolioOptionsList .lg-custom-dropdown-option').forEach(opt => {
            const label = (opt.getAttribute('data-label') || '').toLowerCase();
            opt.style.display = (!query || label.includes(query)) ? 'flex' : 'none';
        });
    }

    function selectPortfolioOption(optElem) {
        const val = optElem.getAttribute('data-value');
        const label = optElem.getAttribute('data-label');

        document.querySelectorAll('#portfolioOptionsList .lg-custom-dropdown-option').forEach(o => {
            o.classList.remove('selected');
            const svg = o.querySelector('svg');
            if (svg) svg.remove();
        });

        optElem.classList.add('selected');
        optElem.insertAdjacentHTML('beforeend', `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`);

        portfolioInput.value = val;
        portfolioLabel.textContent = label;
        portfolioWrap.classList.remove('open');

        // Reset Select All immediately and clear ticked members
        chkSelectAll.checked = false;
        chkSelectAll.indeterminate = false;
        chkSelectAll.disabled = true;

        // Fetch details & show only this campaign's members
        loadPortfolioStaffDetails(val);
    }

    portfolioOptions.forEach(opt => {
        opt.addEventListener('click', function (e) {
            e.stopPropagation();
            selectPortfolioOption(this);
        });
    });

    // -------------------------------------------------------------
    // Checkbox Management & Select All State
    // -------------------------------------------------------------
    function getStaffCheckboxes() {
        return Array.from(document.querySelectorAll('.lg-staff-checkbox'));
    }

    function updateSelectAllState() {
        const checkboxes = getStaffCheckboxes();
        if (checkboxes.length === 0) {
            chkSelectAll.checked = false;
            chkSelectAll.indeterminate = false;
            chkSelectAll.disabled = true;
            return;
        }

        chkSelectAll.disabled = false;
        const checkedCount = checkboxes.filter(cb => cb.checked).length;

        if (checkedCount === 0) {
            chkSelectAll.checked = false;
            chkSelectAll.indeterminate = false;
        } else if (checkedCount === checkboxes.length) {
            chkSelectAll.checked = true;
            chkSelectAll.indeterminate = false;
        } else {
            chkSelectAll.checked = false;
            chkSelectAll.indeterminate = true;
        }
    }

    // Toggle All when clicking Select All - ticks only the members currently shown
    chkSelectAll.addEventListener('change', function () {
        const isChecked = this.checked;
        this.indeterminate = false;
        getStaffCheckboxes().forEach(cb => {
            cb.checked = isChecked;
        });
    });

    // Delegate changes on individual staff checkboxes
    staffGrid.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('lg-staff-checkbox')) {
            updateSelectAllState();
        }
    });

    // Initial check of Select All state on page load
    updateSelectAllState();

    // -------------------------------------------------------------
    // Load Campaign Staff via AJAX
    // -------------------------------------------------------------
    function loadPortfolioStaffDetails(campaignId) {
        if (!campaignId) {
            staffGrid.innerHTML = '<div class="lg-staff-empty-msg">Select a campaign to see its members</div>';
            chkSelectAll.checked = false;
            chkSelectAll.indeterminate = false;
            chkSelectAll.disabled = true;
            return;
        }

        showAlert(null, '');

        fetch(`${detailsUrlBase}/${campaignId}/auto-distribute-details`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                const teamUsers = res.data.team_users || [];

                if (teamUsers.length > 0) {
                    let html = '';
                    teamUsers.forEach(u => {
                        html += `
                            <label class="lg-staff-checkbox-item" for="chkStaff_${u.id}">
                                <input type="checkbox" id="chkStaff_${u.id}" class="lg-custom-checkbox lg-staff-checkbox" value="${u.id}" data-name="${u.name}">
                                <span class="lg-staff-name" title="${u.name}">${u.name}</span>
                            </label>
                        `;
                    });
                    staffGrid.innerHTML = html;
                    chkSelectAll.disabled = false;
                    chkSelectAll.checked = false;
                    chkSelectAll.indeterminate = false;
                } else {
                    staffGrid.innerHTML = '<div class="lg-staff-empty-msg">No members in this campaign</div>';
                    chkSelectAll.disabled = true;
                    chkSelectAll.checked = false;
                    chkSelectAll.indeterminate = false;
                }
                updateSelectAllState();
            } else {
                staffGrid.innerHTML = '<div class="lg-staff-empty-msg">No members in this campaign</div>';
                chkSelectAll.disabled = true;
                chkSelectAll.checked = false;
                chkSelectAll.indeterminate = false;
            }
        })
        .catch(err => {
            console.error('Error fetching campaign details:', err);
            staffGrid.innerHTML = '<div class="lg-staff-empty-msg">Failed to load campaign members. Please try again.</div>';
            chkSelectAll.disabled = true;
            chkSelectAll.checked = false;
            chkSelectAll.indeterminate = false;
        });
    }

    // -------------------------------------------------------------
    // Save Auto Distribute Policy
    // -------------------------------------------------------------
    btnSave.addEventListener('click', function () {
        showAlert(null, '');

        const selectedCampaign = portfolioInput.value.trim();
        const selectedStaffCheckboxes = getStaffCheckboxes().filter(cb => cb.checked);
        const selectedAgentIds = selectedStaffCheckboxes.map(cb => parseInt(cb.value, 10));

        // Validation 1: Campaign must be selected
        if (!selectedCampaign) {
            showAlert('warning', 'Please select a campaign.');
            return;
        }

        // Validation 2: At least one staff/executive must be selected
        if (selectedAgentIds.length === 0) {
            showAlert('warning', 'Please select at least one executive.');
            return;
        }

        // Start loading
        btnSave.disabled = true;
        btnSave.classList.add('loading');

        fetch(executeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                campaign_id: selectedCampaign,
                agent_ids: selectedAgentIds
            })
        })
        .then(res => res.json())
        .then(data => {
            btnSave.disabled = false;
            btnSave.classList.remove('loading');

            if (data.status === 'success') {
                showAlert('success', data.message || 'Auto distribute policy saved successfully.');
            } else {
                showAlert('error', data.message || 'Failed to save auto distribute policy.');
            }
        })
        .catch(err => {
            btnSave.disabled = false;
            btnSave.classList.remove('loading');
            showAlert('error', 'A network error occurred while saving. Please try again.');
            console.error('Save error:', err);
        });
    });

});
</script>
@endpush
