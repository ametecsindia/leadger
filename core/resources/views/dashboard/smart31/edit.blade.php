@extends('dashboard.layouts.master')
@section('title', 'Group: ' . $groupData->group_name . ' - LeadGER')

@push('after-styles')
<style>
/* ==========================================================================
   LeadGER Corporate Design System - Group Edit Page
   ========================================================================== */

.lg-group-edit-page {
    padding: 24px 30px 48px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #0F172A;
    box-sizing: border-box;
}

/* 1. Header Card */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-header-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.1);
}

.lg-header-text {
    display: flex;
    flex-direction: column;
}

.lg-breadcrumb {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--lg-accent);
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lg-breadcrumb a {
    color: #64748B;
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-breadcrumb a:hover {
    color: var(--lg-accent);
}

.lg-breadcrumb-sep {
    color: #94A3B8;
}

.lg-page-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.25;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.lg-group-name-badge {
    color: var(--lg-accent);
    font-weight: 600;
}

.lg-page-subtitle {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

/* 2. Main Assign Groups Card */
.lg-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
    overflow: hidden;
    margin-bottom: 24px;
}

.lg-card-header-bar {
    padding: 18px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #FFFFFF;
}

.lg-card-title-group h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-card-title-group p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748B;
}

.lg-card-body {
    padding: 24px;
}

/* Form Styles */
.lg-form-group {
    margin-bottom: 20px;
}

.lg-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.lg-required {
    color: #EF4444;
}

.lg-multiselect {
    width: 100%;
    min-height: 200px;
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13.5px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-multiselect:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-multiselect option {
    padding: 9px 14px;
    border-radius: 6px;
    margin-bottom: 3px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
}

.lg-multiselect option:checked {
    background-color: #EFF6FF !important;
    color: var(--lg-accent) !important;
    font-weight: 600;
}

.lg-form-help {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #64748B;
    margin-top: 8px;
}

.lg-form-help svg {
    color: #94A3B8;
    flex-shrink: 0;
}

/* Selected Badges Section */
.lg-selected-preview-wrap {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 14px 18px;
    margin-top: 16px;
}

.lg-preview-header {
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-preview-count {
    font-size: 11.5px;
    font-weight: 700;
    background: #EFF6FF;
    color: var(--lg-accent);
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid #DBEAFE;
}

.lg-selected-pills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 28px;
    align-items: center;
}

.lg-team-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    color: #1E293B;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
}

.lg-team-pill .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--lg-accent);
}

.lg-no-teams-note {
    font-size: 12px;
    color: #94A3B8;
    font-style: italic;
}

/* Card Footer & Action Buttons */
.lg-card-footer {
    padding: 16px 24px;
    border-top: 1px solid #F1F5F9;
    background: #FAFAFC;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

.lg-btn {
    height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    white-space: nowrap;
    box-sizing: border-box;
    line-height: 1;
}

.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.2);
}

.lg-btn-primary:hover {
    background: var(--lg-accent-hover);
    border-color: var(--lg-accent-hover);
    color: #FFFFFF !important;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
    transform: translateY(-1px);
}

.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155 !important;
    border: 1px solid #CBD5E1;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.lg-btn-secondary:hover {
    background: #F8FAFC;
    color: #0F172A !important;
    border-color: #94A3B8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .lg-group-edit-page {
        padding: 16px 16px 32px 16px;
    }

    .lg-header-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .lg-card-footer {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .lg-btn {
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="lg-group-edit-page">

    <!-- 1. LEADGER PAGE HEADER -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="lg-header-text">
                <div class="lg-breadcrumb">
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a>
                    <span class="lg-breadcrumb-sep">/</span>
                    <a href="{{ route('obdDid') }}">DID</a>
                    <span class="lg-breadcrumb-sep">/</span>
                    <span>Edit: {{ $groupData->group_name }}</span>
                </div>
                <h1 class="lg-page-title">
                    <span>Group:</span>
                    <span class="lg-group-name-badge">{{ $groupData->group_name }}</span>
                </h1>
                <div class="lg-page-subtitle">Manage group assignments and calling access.</div>
            </div>
        </div>

        <div>
            <a href="{{ route('obdDid') }}" class="lg-btn lg-btn-secondary" title="Return to DID Directory">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Back to DID</span>
            </a>
        </div>
    </div>

    <!-- 2. MAIN ASSIGN GROUPS CARD -->
    <div class="lg-main-card">
        <div class="lg-card-header-bar">
            <div class="lg-card-title-group">
                <h2>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Assign Groups</span>
                </h2>
                <p>Select team groups authorized to use calling channels and virtual numbers under this group.</p>
            </div>
        </div>

        <form action="{{ route('smart31GroupUpdate') }}" method="POST" id="groupEditForm">
            @csrf
            <input type="hidden" name="group_name" value="{{ $groupData->group_name }}">

            <div class="lg-card-body">
                <div class="lg-form-group">
                    <label for="assigned_groups" class="lg-form-label">
                        Team Groups <span class="lg-required">*</span>
                    </label>
                    <select name="assigned_groups[]" id="assigned_groups" class="lg-multiselect" multiple size="7" required onchange="updateSelectedPreview()">
                        @foreach($teams as $id => $name)
                            <option value="{{ $id }}" {{ in_array($id, $assignedIds) ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="lg-form-help">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <span>Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) while clicking to select or deselect multiple team groups.</span>
                    </div>

                    <!-- Selected Teams Preview Box -->
                    <div class="lg-selected-preview-wrap">
                        <div class="lg-preview-header">
                            <span>Currently Assigned Groups</span>
                            <span class="lg-preview-count" id="selectedTeamsCount">0 selected</span>
                        </div>
                        <div class="lg-selected-pills-container" id="selectedTeamsPills">
                            <span class="lg-no-teams-note">No team groups selected.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. ACTION BUTTONS -->
            <div class="lg-card-footer">
                <a href="{{ route('obdDid') }}" class="lg-btn lg-btn-secondary" title="Cancel changes and return">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span>Cancel</span>
                </a>
                <button type="submit" class="lg-btn lg-btn-primary" title="Save updated group assignments">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span>Update</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('after-scripts')
<script>
function updateSelectedPreview() {
    const select = document.getElementById('assigned_groups');
    const container = document.getElementById('selectedTeamsPills');
    const countBadge = document.getElementById('selectedTeamsCount');
    if (!select || !container || !countBadge) return;

    const selectedOptions = Array.from(select.selectedOptions);
    countBadge.innerText = `${selectedOptions.length} selected`;

    if (selectedOptions.length === 0) {
        container.innerHTML = '<span class="lg-no-teams-note">No team groups selected.</span>';
        return;
    }

    container.innerHTML = '';
    selectedOptions.forEach(opt => {
        const pill = document.createElement('span');
        pill.className = 'lg-team-pill';
        pill.innerHTML = `<span class="dot"></span><span>${opt.text.trim()}</span>`;
        container.appendChild(pill);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateSelectedPreview();
});
</script>
@endpush
