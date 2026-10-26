@extends('dashboard.layouts.master')
@section('title', 'Profile Settings - LeadGER')
@section('content')

<style>
/* LeadGER Corporate Modern Profile Settings Page Styling */
.lg-edit-user-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px;
    min-height: auto;
}

.lg-header-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-badge {
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

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-title-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 400;
}

/* User Summary Top Card */
.lg-user-summary-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px 24px 0 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.lg-user-summary-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 18px;
}

.lg-user-summary-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-user-avatar-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--lg-accent);
    color: #FFFFFF;
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.25);
    overflow: hidden;
}

.lg-user-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lg-user-summary-name {
    font-size: 19px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 2px 0;
    line-height: 1.2;
}

.lg-user-summary-email {
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
    margin: 0;
}

.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
}

.lg-status-pill.active {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}

.lg-status-pill.inactive {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #CBD5E1;
}

.lg-status-pill .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

/* Nav Subtabs Bar */
.lg-settings-nav-tabs {
    display: flex;
    align-items: center;
    gap: 6px;
    border-top: 1px solid #F1F5F9;
    padding-top: 10px;
    padding-bottom: 2px;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.lg-nav-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px 8px 0 0;
    font-size: 13.5px;
    font-weight: 600;
    color: #64748B;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
    white-space: nowrap;
    outline: none;
    box-sizing: border-box;
}

.lg-nav-tab-item:hover {
    color: var(--lg-accent);
    background: #EFF6FF;
}

.lg-nav-tab-item.active {
    color: var(--lg-accent);
    background: #EFF6FF;
    border-bottom: 2px solid var(--lg-accent);
}

.lg-nav-tab-item svg {
    flex-shrink: 0;
}

/* Tab Container Panes: Only active tab is displayed */
.lg-tab-pane {
    display: none;
}

.lg-tab-pane.active {
    display: block;
    animation: lgFadeInTab 0.2s ease-in-out;
}

@keyframes lgFadeInTab {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Sections & Cards */
.lg-card-section {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.lg-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F1F5F9;
}

.lg-section-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Form Grid & Inputs */
.lg-field-group {
    margin-bottom: 18px;
}

.lg-field-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 6px;
    display: block;
}

.lg-input {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-input:focus {
    background: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* Custom Dropdown Styling */
.lg-custom-dropdown-wrap {
    position: relative;
    width: 100%;
}

.lg-custom-dropdown-trigger {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    background-color: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    outline: none;
}

.lg-custom-dropdown-trigger:hover,
.lg-custom-dropdown-trigger:focus,
.lg-custom-dropdown-wrap.open .lg-custom-dropdown-trigger {
    background-color: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
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
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-menu {
    display: block;
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

/* Modern Profile Upload Dropzone Box */
.lg-upload-dropzone {
    border: 2px dashed #CBD5E1;
    background: #F8FAFC;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.lg-upload-dropzone:hover {
    border-color: var(--lg-accent);
    background: #EFF6FF;
}

.lg-upload-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.lg-upload-text {
    font-size: 13.5px;
    color: #475569;
    font-weight: 500;
}

.lg-upload-text strong {
    color: var(--lg-accent);
    cursor: pointer;
}

.lg-upload-subtext {
    font-size: 11.5px;
    color: #94A3B8;
    margin-top: 4px;
}

/* Buttons */
.lg-btn-group {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 10px;
}

.lg-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    height: 42px;
    padding: 0 24px;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-save:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-btn-work-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    height: 40px;
    padding: 0 20px;
    min-width: 95px;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-work-save:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 22px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 700;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155 !important;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-cancel:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
}

.lg-btn-outline-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FFFFFF;
    color: var(--lg-accent) !important;
    border: 1.5px solid var(--lg-accent);
    font-size: 13.5px;
    font-weight: 600;
    height: 40px;
    padding: 0 18px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-outline-primary:hover {
    background: #EFF6FF;
}

.lg-btn-danger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #EF4444;
    color: #FFFFFF !important;
    border: none;
    font-size: 13.5px;
    font-weight: 600;
    height: 40px;
    padding: 0 18px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    transition: all 0.15s ease;
}

.lg-btn-danger:hover {
    background: #DC2626;
}

/* License Info Grid Styling */
.lg-license-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.lg-license-info-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 20px;
}

.lg-license-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #E2E8F0;
}

.lg-license-row:last-child {
    border-bottom: none;
}

.lg-license-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
}

.lg-license-value {
    font-size: 13.5px;
    font-weight: 700;
    color: #0F172A;
}

.lg-license-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    margin: 2px;
}

.lg-license-key-box {
    background: #1E293B;
    color: #38BDF8;
    padding: 10px 14px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    word-break: break-all;
}

@media (max-width: 991px) {
    .lg-grid-3col, .lg-grid-2col, .lg-license-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Shift Management Styling */
.lg-link-btn {
    background: transparent;
    border: none;
    color: var(--lg-accent);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 4px;
    border-radius: 4px;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-link-btn:hover {
    color: #0F52CC;
    text-decoration: underline;
}

.lg-btn-action-primary {
    white-space: nowrap;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
}
.lg-btn-action-primary:hover {
    background: #0F52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.3);
    transform: translateY(-1px);
}

.lg-time-input-container {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.lg-time-input-container svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748B;
    pointer-events: none;
}
.lg-time-input {
    padding-left: 36px !important;
    cursor: pointer;
    font-size: 13.5px !important;
    font-weight: 500 !important;
    color: #0F172A !important;
}
.lg-time-input::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.65;
    transition: opacity 0.15s ease;
}
.lg-time-input::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

/* ==========================================================================
   LEADGER SHIFT MODALS OVERLAY (CLEAN, BRIGHT, ELEVATED ABOVE BACKDROP)
   ========================================================================== */
.modal.lg-modal:not(.show):not(.in) {
    display: none !important;
    pointer-events: none !important;
}

body > .modal-backdrop.show,
body > .modal-backdrop.in,
.modal-backdrop.show,
.modal-backdrop.in {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 99990 !important;
    background-color: rgba(15, 23, 42, 0.6) !important;
    opacity: 1 !important;
    display: block !important;
    pointer-events: auto !important;
}

body > #createShiftModal.modal.show,
body > #manageShiftsModal.modal.show,
body > #editShiftModal.modal.show,
#createShiftModal.modal.show,
#manageShiftsModal.modal.show,
#editShiftModal.modal.show,
body > #createShiftModal.modal.in,
body > #manageShiftsModal.modal.in,
body > #editShiftModal.modal.in,
#createShiftModal.modal.in,
#manageShiftsModal.modal.in,
#editShiftModal.modal.in {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    z-index: 100000 !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    pointer-events: auto !important;
}

#createShiftModal .modal-dialog,
#manageShiftsModal .modal-dialog,
#editShiftModal .modal-dialog {
    margin: auto !important;
    z-index: 100001 !important;
    width: 100% !important;
    pointer-events: auto !important;
}

#createShiftModal .modal-content,
#manageShiftsModal .modal-content,
#editShiftModal .modal-content {
    background: #FFFFFF !important;
    border-radius: 16px !important;
    border: 1px solid #E2E8F0 !important;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(15, 23, 42, 0.05) !important;
    overflow: hidden !important;
    position: relative !important;
    z-index: 100002 !important;
}

#createShiftModal .modal-header,
#manageShiftsModal .modal-header,
#editShiftModal .modal-header {
    background: #FAFAFC !important;
    border-bottom: 1px solid #F1F5F9 !important;
    padding: 20px 24px !important;
}

#createShiftModal .modal-body,
#manageShiftsModal .modal-body,
#editShiftModal .modal-body {
    background: #FFFFFF !important;
    padding: 24px !important;
}

#createShiftModal .modal-footer,
#manageShiftsModal .modal-footer,
#editShiftModal .modal-footer {
    background: #FAFAFC !important;
    border-top: 1px solid #F1F5F9 !important;
    padding: 16px 24px !important;
}

#createShiftModal input.lg-input,
#editShiftModal input.lg-input {
    background: #FFFFFF !important;
    color: #0F172A !important;
    border: 1px solid #CBD5E1 !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

#createShiftModal input.lg-input:focus,
#editShiftModal input.lg-input:focus {
    border-color: var(--lg-accent) !important;
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.15) !important;
    outline: none !important;
}

/* ==========================================================================
   LeadGER Role Management Modals (Edit Role & Delete Confirmation)
   Styled consistently with Edit Product Modal
   ========================================================================== */
.lg-role-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}

.lg-role-modal-overlay.active {
    display: flex !important;
}

.lg-role-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
    overflow: hidden;
    animation: lgRoleModalZoom 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    margin: auto;
    display: flex;
    flex-direction: column;
}

@keyframes lgRoleModalZoom {
    from {
        opacity: 0;
        transform: scale(0.96) translateY(6px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes lgSpin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}


.lg-role-modal-header {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.lg-role-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-role-modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-role-modal-icon-badge.danger {
    background: #FEF2F2;
    border-color: #FEE2E2;
}

.lg-role-modal-title {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-role-modal-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 2px 0 0 0;
    font-weight: 500;
}

.lg-role-modal-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    outline: none;
}

.lg-role-modal-close-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
    border-color: #CBD5E1;
}

.lg-role-modal-body {
    padding: 22px 24px;
    background: #FFFFFF;
    box-sizing: border-box;
}

.lg-role-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.lg-role-modal-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.lg-role-modal-input {
    width: 100%;
    height: 44px;
    padding: 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-role-modal-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-role-meta-hint {
    font-size: 12px;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

.lg-role-meta-dot {
    color: #CBD5E1;
    font-size: 10px;
}

.lg-role-code-pill {
    background: #F1F5F9;
    color: var(--lg-accent);
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 11.5px;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.lg-role-inline-error {
    font-size: 12.5px;
    color: #DC2626;
    font-weight: 500;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.lg-role-delete-warning-box {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    border-radius: 8px;
    padding: 10px 12px;
    color: #991B1B;
    font-size: 12.5px;
    line-height: 1.4;
    margin-top: 10px;
}

.lg-role-modal-footer {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}

.lg-btn-role-cancel {
    height: 38px;
    padding: 0 18px;
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.lg-btn-role-cancel:hover {
    background: #F8FAFC;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-btn-role-save {
    height: 38px;
    padding: 0 20px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.18);
}

.lg-btn-role-save:hover {
    background: var(--lg-accent-hover);
    border-color: var(--lg-accent-hover);
}

.lg-btn-role-save:disabled,
.lg-btn-role-delete:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.lg-btn-role-delete {
    height: 38px;
    padding: 0 18px;
    background: #DC2626;
    color: #FFFFFF;
    border: 1px solid #DC2626;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.18);
}

.lg-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}

.lg-grid-phone-logo {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    align-items: start;
    margin-bottom: 24px;
}

@media (max-width: 991px) {
    .lg-grid-3col,
    .lg-grid-phone-logo {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
}
</style>

@php
    $nameParts = explode(' ', trim($Users->name), 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    $selectedLoc = $WorkLocations->firstWhere('id', $Users->work_location_id);
    $addressVal = $selectedLoc ? ($selectedLoc->name == 'No Work Location' ? '' : $selectedLoc->name) : '';
    $defaultTab = session('active_tab', 'profile');
    $lgAdminTabs = \App\Support\RoleAccess::isFullAccess(Auth::user());
    if (!$lgAdminTabs) { $defaultTab = 'profile'; }
@endphp

<div class="lg-edit-user-wrapper">

    <!-- TOAST NOTIFICATION -->
    <div id="lg-toast" style="display: none; position: fixed; top: 24px; right: 24px; z-index: 99999; background: #059669; color: #FFFFFF; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 13.5px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2); align-items: center; gap: 10px;">
        <svg id="lg-toast-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span id="lg-toast-message">Changes saved successfully</span>
    </div>

    <!-- 1. PAGE HEADER -->
    <div class="lg-header-box">
        <div class="lg-header-left">
            <div class="lg-header-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <h1 class="lg-title-main">Profile Settings</h1>
                <p class="lg-title-sub">Manage user profile, work configuration, roles, and product license settings</p>
            </div>
        </div>
    </div>

    <!-- 2. TOP USER PROFILE HEADER CARD WITH SECTION SWITCHER TABS -->
    <div class="lg-user-summary-card">
        <div class="lg-user-summary-top">
            <div class="lg-user-summary-left">
                <div class="lg-user-avatar-circle">
                    @if($Users->photo != "")
                        <img src="{{ asset('uploads/users/'.$Users->photo) }}" alt="{{ $Users->name }}">
                    @else
                        {{ strtoupper(substr($Users->name, 0, 2)) }}
                    @endif
                </div>
                <div>
                    <h3 class="lg-user-summary-name">{{ $Users->name }}</h3>
                    <p class="lg-user-summary-email">{{ $Users->email }}</p>
                    <div class="lg-status-pill {{ $Users->status ? 'active' : 'inactive' }}">
                        <span class="dot"></span>
                        <span>{{ $Users->status ? 'Active' : 'Inactive' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Switcher Tabs -->
        <div class="lg-settings-nav-tabs" role="tablist">
            <button type="button" class="lg-nav-tab-item active" data-tab="tab-profile" role="tab" aria-selected="true">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Profile</span>
            </button>
            @if($lgAdminTabs)
            <button type="button" class="lg-nav-tab-item" data-tab="tab-company-settings" role="tab" aria-selected="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Company Settings</span>
            </button>
            <button type="button" class="lg-nav-tab-item" data-tab="tab-work-config" role="tab" aria-selected="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Work Configuration</span>
            </button>
            <button type="button" class="lg-nav-tab-item" data-tab="tab-roles-permissions" role="tab" aria-selected="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Roles &amp; Permissions</span>
            </button>
            <button type="button" class="lg-nav-tab-item" data-tab="tab-license-info" role="tab" aria-selected="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>License Info</span>
            </button>
            @endif
        </div>
    </div>

    <!-- MAIN FORM: WRAPS PROFILE & COMPANY SETTINGS CONTAINERS -->
    {{ Form::open(['route' => ['usersUpdate', $Users->id], 'method' => 'POST', 'files' => true, 'id' => 'profileMainForm']) }}

    <!-- Hidden inputs for backend requirements -->
    <input type="hidden" name="active_tab" id="form_active_tab" value="{{ $defaultTab }}">
    <input type="hidden" name="team_id" value="{{ $Users->team_id }}">
    <input type="hidden" name="permissions_id" id="permissions_id" value="{{ $Users->permissions_id }}">
    <input type="hidden" name="role_id" value="{{ $Users->role_id }}">
    <input type="hidden" name="sms_gateway_id" value="{{ $Users->sms_gateway_id }}">
    <input type="hidden" name="email_setting_id" value="{{ $Users->email_setting_id }}">
    {!! Form::hidden('status', $Users->status) !!}

    <!-- ============================================== -->
    <!-- TAB PANE 1: EDIT PROFILE SETTINGS CONTAINER    -->
    <!-- ============================================== -->
    <div class="lg-tab-pane active" id="tab-profile" role="tabpanel">
        <div class="lg-card-section" id="editProfileCard">
            <div class="lg-section-header">
                <div class="lg-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <span>Edit Profile Settings</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;" class="lg-grid-2col">
                <!-- First Name * -->
                <div class="lg-field-group">
                    <label for="first_name" class="lg-field-label">Your First Name <span style="color: #EF4444;">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="lg-input" placeholder="Enter first name" value="{{ old('first_name', $firstName) }}" required>
                </div>

                <!-- Last Name * -->
                <div class="lg-field-group">
                    <label for="last_name" class="lg-field-label">Your Last Name <span style="color: #EF4444;">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="lg-input" placeholder="Enter last name" value="{{ old('last_name', $lastName) }}" required>
                </div>

                <!-- Email * -->
                <div class="lg-field-group">
                    <label for="email" class="lg-field-label">Your Email <span style="color: #EF4444;">*</span></label>
                    {!! Form::email('email', $Users->email, array('placeholder' => 'Enter login email', 'class' => 'lg-input', 'id' => 'email', 'required' => '')) !!}
                </div>

                <!-- Phone * -->
                <div class="lg-field-group">
                    <label for="mobile" class="lg-field-label">Your Phone <span style="color: #EF4444;">*</span></label>
                    {!! Form::tel('mobile', $Users->mobile, array('placeholder' => 'Enter phone number', 'class' => 'lg-input', 'id' => 'mobile', 'required' => '')) !!}
                </div>

                <!-- Address * -->
                <div class="lg-field-group" style="grid-column: span 2;">
                    <label for="work_location_name" class="lg-field-label">Address <span style="color: #EF4444;">*</span></label>
                    <input type="text" name="work_location_name" id="work_location_name" class="lg-input" placeholder="Enter address" value="{{ old('work_location_name', $addressVal) }}" required>
                </div>

                <!-- Profile Image Upload Dropzone -->
                <div class="lg-field-group" style="grid-column: span 2;">
                    <label class="lg-field-label">Profile Image</label>

                    @if($Users->photo != "")
                        <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 12px;" id="user_photo">
                            <img src="{{ asset('uploads/users/'.$Users->photo) }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid #CBD5E1;">
                            <a target="_blank" href="{{ asset('uploads/users/'.$Users->photo) }}" style="font-size: 13px; color: var(--lg-accent); font-weight: 600; text-decoration: underline;">
                                View current photo ({{ $Users->photo }})
                            </a>
                            <button type="button" onclick="document.getElementById('user_photo').style.display='none';document.getElementById('photo_delete').value='1';document.getElementById('undo').style.display='inline-flex';" class="btn btn-sm btn-default" style="font-size: 11.5px; padding: 4px 10px; border-radius: 6px; border: 1px solid #CBD5E1; background: #FFF; color: #EF4444;">
                                {!! __('backend.delete') !!}
                            </button>
                        </div>
                        <div id="undo" style="display: none; margin-bottom: 12px;">
                            <button type="button" onclick="document.getElementById('user_photo').style.display='flex';document.getElementById('photo_delete').value='0';document.getElementById('undo').style.display='none';" class="btn btn-sm btn-default" style="font-size: 11.5px; padding: 4px 10px; border-radius: 6px; border: 1px solid #CBD5E1; background: #FFF;">
                                {!! __('backend.undoDelete') !!}
                            </button>
                        </div>
                        {!! Form::hidden('photo_delete', '0', array('id' => 'photo_delete')) !!}
                    @endif

                    <div class="lg-upload-dropzone" onclick="document.getElementById('photo').click();">
                        <div class="lg-upload-icon-circle">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="lg-upload-text">
                            <strong>Click to upload</strong> or drag and drop
                        </div>
                        <div class="lg-upload-subtext">
                            PNG, JPG, JPEG (Max 2MB)
                        </div>
                        <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="if(this.files[0]){ $('.lg-upload-text').html('Selected: <strong>' + this.files[0].name + '</strong>'); }">
                    </div>
                </div>
            </div>

            <!-- Action Buttons for Profile Settings Form -->
            <div class="lg-btn-group">
                <a href="{{ route('users') }}" class="lg-btn-cancel">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Cancel</span>
                </a>
                <button type="submit" class="lg-btn-save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </div>

    {{ Form::close() }}

    {{-- Company Settings, Work Configuration, Roles & Permissions, License Info: Admin only (config/permissions.php) --}}
    @if($lgAdminTabs)
    <!-- ============================================== -->
    <!-- TAB PANE 2: COMPANY SETTINGS CONTAINER         -->
    <!-- ============================================== -->
    <div class="lg-tab-pane" id="tab-company-settings" role="tabpanel">
        <form id="companySettingsForm" enctype="multipart/form-data" onsubmit="event.preventDefault(); saveCompanySettings();">
            @csrf
            <div class="lg-card-section" id="companySettingsCard">
                <!-- Section Title 1: Company Details -->
                <div class="lg-section-header">
                    <div class="lg-section-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l8-4v18"/>
                            <path d="M19 21V11l-6-4"/>
                            <path d="M9 9h1"/>
                            <path d="M9 13h1"/>
                            <path d="M9 17h1"/>
                        </svg>
                    </div>
                    <span>Company Details</span>
                </div>

                <!-- Row 1: Company Name | Company Address | Company Email -->
                <div class="lg-grid-3col">
                    <!-- Company Name (required) -->
                    <div class="lg-field-group">
                        <label for="company_name" class="lg-field-label">Company Name <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="company_name" id="company_name" class="lg-input" placeholder="Enter company name" value="{{ old('company_name', $setting->site_title_en ?? '') }}" required>
                        <div class="lg-field-error" id="err_company_name" style="color: #EF4444; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                    </div>

                    <!-- Company Address (same height as other inputs) -->
                    <div class="lg-field-group">
                        <label for="company_address" class="lg-field-label">Company Address</label>
                        <input type="text" name="company_address" id="company_address" class="lg-input" placeholder="Enter company address" value="{{ old('company_address', $setting->contact_t1_en ?? '') }}">
                        <div class="lg-field-error" id="err_company_address" style="color: #EF4444; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                    </div>

                    <!-- Company Email (validated) -->
                    <div class="lg-field-group">
                        <label for="company_email" class="lg-field-label">Company Email</label>
                        <input type="email" name="company_email" id="company_email" class="lg-input" placeholder="Enter company email" value="{{ old('company_email', $setting->contact_t6 ?? '') }}">
                        <div class="lg-field-error" id="err_company_email" style="color: #EF4444; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                    </div>
                </div>

                <!-- Row 2: Company Phone | Company Logo -->
                <div class="lg-grid-phone-logo">
                    <!-- Company Phone (validated) -->
                    <div class="lg-field-group">
                        <label for="company_phone" class="lg-field-label">Company Phone</label>
                        <input type="text" name="company_phone" id="company_phone" class="lg-input" placeholder="e.g. +91 40 48584822" value="{{ old('company_phone', $setting->contact_t3 ?? '') }}">
                        <div class="lg-field-error" id="err_company_phone" style="color: #EF4444; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                    </div>

                    <!-- Company Logo (upload max 2MB with preview, choose/change, remove) -->
                    <div class="lg-field-group">
                        <label class="lg-field-label">Company Logo</label>
                        <div class="lg-logo-upload-wrap" style="display: flex; align-items: center; gap: 14px; background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 10px; padding: 4px 12px; min-height: 42px; box-sizing: border-box;">
                            <!-- Preview Box -->
                            <div id="companyLogoPreviewContainer" style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 6px; border: 1px solid #E2E8F0; background: #FFFFFF; overflow: hidden; flex-shrink: 0;">
                                @if(!empty($setting->style_logo_en))
                                    <img id="companyLogoImg" src="{{ asset('uploads/settings/'.$setting->style_logo_en) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                                    <div id="companyLogoPlaceholder" style="color: #94A3B8; display: none; align-items: center; justify-content: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                @else
                                    <div id="companyLogoPlaceholder" style="color: #94A3B8; display: flex; align-items: center; justify-content: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                    <img id="companyLogoImg" src="" alt="Logo" style="display: none; width: 100%; height: 100%; object-fit: contain;">
                                @endif
                            </div>

                            <!-- File Name / Status -->
                            <div style="flex: 1; min-width: 120px; overflow: hidden;">
                                <div id="companyLogoFileName" style="font-size: 12px; font-weight: 600; color: #1E293B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ !empty($setting->style_logo_en) ? $setting->style_logo_en : 'No logo uploaded' }}">
                                    {{ !empty($setting->style_logo_en) ? $setting->style_logo_en : 'No logo uploaded' }}
                                </div>
                                <div style="font-size: 10.5px; color: #64748B;">
                                    PNG, JPG, JPEG, SVG, WEBP (Max 2 MB)
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <input type="file" id="company_logo_input" name="company_logo" accept=".png,.jpg,.jpeg,.svg,.webp" style="display: none;">
                                <input type="hidden" name="remove_logo" id="company_remove_logo" value="0">
                                
                                <button type="button" class="btn btn-sm" id="btnChooseCompanyLogo" onclick="document.getElementById('company_logo_input').click();" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 5px 10px; border-radius: 6px; border: 1px solid #CBD5E1; background: #FFFFFF; color: #1E293B; cursor: pointer; transition: all 0.15s ease;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span id="btnChooseLogoText">{{ !empty($setting->style_logo_en) ? 'Change' : 'Choose file' }}</span>
                                </button>

                                <button type="button" class="btn btn-sm" id="btnRemoveCompanyLogo" onclick="removeCompanyLogo()" style="{{ !empty($setting->style_logo_en) ? 'display: inline-flex;' : 'display: none;' }} align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 5px 8px; border-radius: 6px; border: 1px solid #FCA5A5; background: #FEF2F2; color: #EF4444; cursor: pointer; transition: all 0.15s ease;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    <span>Remove</span>
                                </button>
                            </div>
                        </div>
                        <div class="lg-field-error" id="err_company_logo" style="color: #EF4444; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                    </div>
                </div>

                <!-- Separator Line -->
                <hr style="border: none; border-top: 1px solid #F1F5F9; margin: 24px 0 20px 0;">

                <!-- Section Title 2: Options -->
                <div class="lg-section-header" style="border-bottom: none; margin-bottom: 16px; padding-bottom: 0;">
                    <div class="lg-section-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <span>Options</span>
                </div>

                <!-- Three Checkbox Options in a row -->
                <div style="display: flex; align-items: flex-start; gap: 40px; flex-wrap: wrap; margin-bottom: 24px;">
                    <!-- 1. RMI Report Enabled -->
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-size: 14px; font-weight: 600; color: #1E293B;">
                        <input type="checkbox" name="rmi_report_enabled" value="1" id="rmi_report_enabled" {{ (isset($rmiReportEnabled) && $rmiReportEnabled) ? 'checked' : '' }} style="width: 17px; height: 17px; accent-color: var(--lg-accent); cursor: pointer; border-radius: 4px; margin: 0; vertical-align: middle;">
                        <span>RMI Report Enabled</span>
                    </label>

                    <!-- 2. Delete Permission with Helper Line -->
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        @if(@Auth::user()->permissionsGroup && @Auth::user()->permissionsGroup->name === 'Admin')
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-size: 14px; font-weight: 600; color: #1E293B;">
                                <input type="checkbox" name="manager_delete_permission" value="1" id="manager_delete_permission" {{ (isset($managerDeletePermission) && $managerDeletePermission) ? 'checked' : '' }} style="width: 17px; height: 17px; accent-color: var(--lg-accent); cursor: pointer; border-radius: 4px; margin: 0; vertical-align: middle;">
                                <span>Delete Permission</span>
                            </label>
                        @else
                            <label style="display: flex; align-items: center; gap: 10px; cursor: not-allowed; margin: 0; font-size: 14px; font-weight: 600; color: #64748B;" title="Admin Controlled: Only Administrators can modify this setting">
                                <input type="checkbox" id="manager_delete_permission" {{ (isset($managerDeletePermission) && $managerDeletePermission) ? 'checked' : '' }} disabled style="width: 17px; height: 17px; accent-color: #94A3B8; cursor: not-allowed; border-radius: 4px; margin: 0; opacity: 0.7; vertical-align: middle;">
                                <span style="color: #64748B;">Delete Permission</span>
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Admin Controlled
                                </span>
                            </label>
                        @endif
                        <div style="font-size: 12px; color: #64748B; margin-left: 27px; font-weight: 400;">
                            Allows admin to delete call recordings only.
                        </div>
                    </div>

                    <!-- 3. AI Transcript -->
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-size: 14px; font-weight: 600; color: #1E293B;">
                        <input type="hidden" name="ai_settings_present" value="1"><input type="checkbox" name="ai_enabled" value="1" id="ai_enabled" {{ (isset($aiSettings) && $aiSettings->ai_enabled) ? 'checked' : '' }} style="width: 17px; height: 17px; accent-color: var(--lg-accent); cursor: pointer; border-radius: 4px; margin: 0; vertical-align: middle;">
                        <span>AI Transcript</span>
                    </label>
                </div>

                <!-- Save Changes Button (Bottom Right) -->
                <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 24px;">
                    <button type="button" class="lg-btn-save" id="btnSaveCompanySettings" onclick="saveCompanySettings()">
                        <svg id="saveCompanySpinner" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: none; animation: lgSpin 0.75s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <svg id="saveCompanyIcon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="saveCompanyText">Save Changes</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================== -->
    <!-- TAB PANE 3: WORK CONFIGURATION CONTAINER       -->
    <!-- ============================================== -->
    <div class="lg-tab-pane" id="tab-work-config" role="tabpanel">
        <div class="lg-card-section" id="workConfigCard">
            <div class="lg-section-header">
                <div class="lg-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </div>
                <span>Work Configuration</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; position: relative;" class="lg-grid-2col">
                <!-- Work Location Manual Text Input -->
                <div class="lg-field-group">
                    <label for="wc_work_location_name" class="lg-field-label">Work Location</label>
                    <input type="text" id="wc_work_location_name" class="lg-input" placeholder="Enter work location" value="{{ old('work_location_name', $addressVal) }}">
                </div>

                <!-- Shift Field with Create Shift and Manage Shifts -->
                <div class="lg-field-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label for="shift_id" class="lg-field-label" style="margin-bottom: 0;">Shift</label>
                        <button type="button" id="btnOpenManageShiftsModal" class="lg-link-btn" title="Manage all shifts">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                            <span>Manage Shifts</span>
                        </button>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div class="lg-custom-dropdown-wrap" data-select-id="shift_id" style="flex: 1; min-width: 240px;">
                            <input type="hidden" name="shift_id" id="shift_id" value="{{ $Users->shift_id ?? 1 }}">
                            <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                                @php
                                    $userShiftId = $Users->shift_id ?? 1;
                                    $activeShift = isset($Shifts) ? $Shifts->firstWhere('id', $userShiftId) : null;
                                    if (!$activeShift && isset($Shifts) && count($Shifts) > 0) {
                                        $activeShift = $Shifts->first();
                                    }
                                    $activeLabel = $activeShift 
                                        ? ($activeShift->name . ' – ' . date('h:i A', strtotime($activeShift->start_time)) . ' – ' . date('h:i A', strtotime($activeShift->end_time)))
                                        : 'Select Shift';
                                @endphp
                                <span class="lg-custom-dropdown-label">{{ $activeLabel }}</span>
                                <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                            <div class="lg-custom-dropdown-menu" id="shiftDropdownMenu" role="listbox">
                                @if(isset($Shifts) && count($Shifts) > 0)
                                    @foreach($Shifts as $s)
                                        @php
                                            $sLabel = $s->name . ' – ' . date('h:i A', strtotime($s->start_time)) . ' – ' . date('h:i A', strtotime($s->end_time));
                                            $isSelected = ($Users->shift_id ?? 1) == $s->id;
                                        @endphp
                                        <div class="lg-custom-dropdown-option {{ $isSelected ? 'selected' : '' }}" data-val="{{ $s->id }}" role="option">
                                            <span>{{ $sLabel }}</span>
                                            @if($isSelected)
                                                <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="lg-custom-dropdown-option selected" data-val="1" role="option">
                                        <span>Regular – 09:00 AM – 06:00 PM</span>
                                        <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- + Create Shift Button -->
                        <button type="button" id="btnOpenCreateShiftModal" class="lg-btn-action-primary" title="Create a new shift">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            <span>Create Shift</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Save Button for Work Configuration -->
            <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 16px;">
                <button type="button" id="btnSaveWorkConfig" class="lg-btn-work-save">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- TAB PANE 4: ADD ROLE / ROLE MASTER CONTAINER   -->
    <!-- ============================================== -->
    <div class="lg-tab-pane" id="tab-roles-permissions" role="tabpanel">
        <div class="lg-card-section" id="addRoleCard">
            <div class="lg-section-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="lg-section-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <span>Roles &amp; Permissions</span>
                </div>
                <button type="button" id="btnToggleNewRoleForm" class="lg-btn-outline-primary" style="height: 34px; padding: 0 14px; font-size: 12.5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add New Role</span>
                </button>
            </div>

            <!-- Role Master Directory Table (filled from the roles list, see lgRolesLoad) -->
            <div style="margin-bottom: 20px;">
                <div class="lg-rp-dir-head">
                    <span>Role Master Directory</span>
                    <span class="lg-rp-dir-total">Total Roles: <strong id="totalRolesCount">{{ count($Permissions) }}</strong></span>
                </div>
                <div class="lg-rp-dir-wrap">
                    <table class="lg-rp-dir" id="rolesMasterTable">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px;">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Description</th>
                                <th scope="col" style="width: 80px;">Users</th>
                                <th scope="col" style="width: 140px;">Permissions</th>
                                <th scope="col" style="width: 140px;">Created At</th>
                                <th scope="col" style="width: 130px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="rolesTableBody">
                            <tr><td colspan="7" class="lg-rp-dir-empty">Loading roles...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- TAB PANE 5: LICENSE INFO CONTAINER             -->
    <!-- ============================================== -->
    <div class="lg-tab-pane" id="tab-license-info" role="tabpanel">
        <div class="lg-card-section" id="licenseInfoCard">
            <div class="lg-section-header">
                <div class="lg-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <span>License Info</span>
            </div>

            <div id="licenseContentBox">
                @if(isset($licenseData) && !empty($licenseData))
                    <div class="lg-license-grid">
                        <div class="lg-license-info-card">
                            <div class="lg-license-row">
                                <span class="lg-license-label">Licensed To</span>
                                <span class="lg-license-value">{{ $licenseData['mac'] ?? $licenseData['ip_address'] ?? $licenseData['domain'] ?? 'N/A' }}</span>
                            </div>
                            <div class="lg-license-row">
                                <span class="lg-license-label">Licensed Based On</span>
                                <span class="lg-license-value">{{ $licenseData['license_base'] ?? 'mac' }}</span>
                            </div>
                            <div class="lg-license-row">
                                <span class="lg-license-label">Valid Till</span>
                                <span class="lg-license-value" style="color: #059669;">{{ $licenseData['expiry'] ?? 'N/A' }}</span>
                            </div>
                            <div class="lg-license-row">
                                <span class="lg-license-label">Features</span>
                                <div>
                                    @if(!empty($licenseData['features']))
                                        @foreach(explode(',', $licenseData['features']) as $feat)
                                            <span class="lg-license-badge">{{ trim($feat) }}</span>
                                        @endforeach
                                    @else
                                        <span class="lg-license-value">N/A</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="lg-license-info-card">
                            <div class="lg-license-row">
                                <span class="lg-license-label">No. of Users</span>
                                <span class="lg-license-value">{{ $licenseData['user_count'] ?? 'N/A' }}</span>
                            </div>
                            <div class="lg-license-row">
                                <span class="lg-license-label">No. Of Lead Records</span>
                                <span class="lg-license-value">{{ number_format($licenseData['lead_count'] ?? 0) }}</span>
                            </div>
                            <div class="lg-license-row" style="flex-direction: column; align-items: flex-start; gap: 6px;">
                                <span class="lg-license-label">License Key</span>
                                <div class="lg-license-key-box" style="width: 100%; box-sizing: border-box;">
                                    <span>{{ $licenseKey ?: ($licenseData['license_key'] ?? 'N/A') }}</span>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $licenseKey ?: ($licenseData['license_key'] ?? '') }}'); showLgToast('License Key copied to clipboard!');" title="Copy Key" style="background: none; border: none; color: #38BDF8; cursor: pointer; padding: 0 0 0 8px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- License Action Buttons -->
                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-top: 20px;">
                        <button type="button" id="btnSyncDid" class="lg-btn-outline-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            <span>Sync Did</span>
                        </button>
                        <button type="button" id="btnDeleteLicense" class="lg-btn-danger">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            <span>Delete License</span>
                        </button>
                    </div>
                    {{-- Delete License confirmation (styles: .lg-sv-modal in the Sarvam card) --}}
                    <div class="lg-sv-modal" id="lgDeleteLicenseModal" role="alertdialog" aria-modal="true" aria-labelledby="lgDeleteLicenseTitle" hidden>
                        <div class="lg-sv-modal-card">
                            <h4 id="lgDeleteLicenseTitle">Delete this license?</h4>
                            <p>The active license is removed and LeadGER stops working until a license is activated again.</p>
                            <div style="display:flex;justify-content:flex-end;gap:10px">
                                <button type="button" class="lg-btn-outline-primary" id="lgDeleteLicenseCancel">Cancel</button>
                                <button type="button" class="lg-btn-danger" id="lgDeleteLicenseConfirm"><span>Delete License</span></button>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Empty State / Add License Form -->
                    <div style="background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <h4 style="font-size: 15px; font-weight: 700; color: #92400E; margin: 0 0 4px 0;">No Active License Configured</h4>
                        <p style="font-size: 13px; color: #B45309; margin: 0;">Please activate a valid LeadGER license key to enable system operations.</p>
                    </div>

                    <div id="addLicenseFormBox" style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px;">
                        <div style="font-size: 14px; font-weight: 700; color: #0F172A; margin-bottom: 14px;">Store / Activate New License</div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;" class="lg-grid-3col">
                            <div class="lg-field-group" style="margin-bottom: 0;">
                                <label class="lg-field-label">Admin Email <span style="color: #EF4444;">*</span></label>
                                <input type="email" id="lic_email" class="lg-input" placeholder="admin@leadger.com" value="{{ $Users->email }}">
                            </div>
                            <div class="lg-field-group" style="margin-bottom: 0;">
                                <label class="lg-field-label">Password <span style="color: #EF4444;">*</span></label>
                                <input type="password" id="lic_password" class="lg-input" placeholder="Enter admin password">
                            </div>
                            <div class="lg-field-group" style="margin-bottom: 0;">
                                <label class="lg-field-label">License Key <span style="color: #EF4444;">*</span></label>
                                <input type="text" id="lic_key" class="lg-input" placeholder="Enter product license key">
                            </div>
                        </div>
                        <div id="lic_form_error" style="color: #EF4444; font-size: 12.5px; font-weight: 500; margin-top: 10px; display: none;"></div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                            <button type="button" id="btnActivateNewLicense" class="lg-btn-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                                <span>Activate License</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Transcription · Sarvam AI: this company's own API key and transcription settings --}}
        @include('dashboard.users.partials.sarvam-key-card')
    </div>
    @endif

</div>

<!-- CREATE NEW SHIFT MODAL -->
<div class="modal fade lg-modal" id="createShiftModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h5 class="modal-title" style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">Create New Shift</h5>
                    <div style="font-size: 12.5px; color: #64748B; margin-top: 2px;">Define custom shift timings and working hours.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 22px; line-height: 1; color: #64748B; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 22px 24px; background: #FFFFFF;">
                <div id="create_shift_error" style="display:none; background: #FEF2F2; border: 1px solid #FECACA; padding: 10px 14px; border-radius: 8px; color: #DC2626; font-size: 13px; font-weight: 500; margin-bottom: 16px;"></div>

                <div class="lg-form-group" style="margin-bottom: 16px;">
                    <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Shift Name <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="text" id="new_shift_name" class="lg-input" placeholder="e.g. Morning Shift" required autocomplete="off" style="width: 100%; height: 42px; padding: 0 14px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="lg-form-group" style="margin-bottom: 0;">
                        <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                            Start Time <span style="color: #EF4444;">*</span>
                        </label>
                        <div class="lg-time-input-container">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <input type="time" id="new_shift_start_time" class="lg-input lg-time-input" value="09:00" required style="width: 100%; height: 42px; padding: 0 12px 0 36px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="lg-form-group" style="margin-bottom: 0;">
                        <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                            End Time <span style="color: #EF4444;">*</span>
                        </label>
                        <div class="lg-time-input-container">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <input type="time" id="new_shift_end_time" class="lg-input lg-time-input" value="18:00" required style="width: 100%; height: 42px; padding: 0 12px 0 36px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #64748B; margin-top: 10px; display: flex; align-items: center; gap: 5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Click the clock / field to select hour, minute, and AM/PM. Overnight shifts (e.g. 10:00 PM to 06:00 AM) are supported.</span>
                </div>
            </div>
            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <button type="button" class="lg-btn-cancel" data-dismiss="modal" style="height: 38px; padding: 0 16px; font-size: 13px;">Cancel</button>
                <button type="button" id="btnSubmitCreateShift" class="lg-btn-save" style="height: 38px; padding: 0 18px; font-size: 13px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Save Shift</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MANAGE SHIFTS MODAL -->
<div class="modal fade lg-modal" id="manageShiftsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 640px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h5 class="modal-title" style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">Manage Shifts</h5>
                    <div style="font-size: 12.5px; color: #64748B; margin-top: 2px;">View, edit, or remove configured work shifts.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 22px; line-height: 1; color: #64748B; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px 24px; background: #FFFFFF;">
                <div id="manage_shifts_table_wrap" style="max-height: 360px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC; color: #475569; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.04em;">
                                <th style="padding: 10px 12px; text-align: left;">Shift Name</th>
                                <th style="padding: 10px 12px; text-align: left;">Start Time</th>
                                <th style="padding: 10px 12px; text-align: left;">End Time</th>
                                <th style="padding: 10px 12px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="manageShiftsTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: space-between;">
                <button type="button" id="btnManageOpenCreate" class="lg-link-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Add Another Shift</span>
                </button>
                <button type="button" class="lg-btn-cancel" data-dismiss="modal" style="height: 38px; padding: 0 16px; font-size: 13px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SHIFT MODAL -->
<div class="modal fade lg-modal" id="editShiftModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h5 class="modal-title" style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">Edit Shift</h5>
                    <div style="font-size: 12.5px; color: #64748B; margin-top: 2px;">Update shift name or working hours.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 22px; line-height: 1; color: #64748B; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 22px 24px; background: #FFFFFF;">
                <input type="hidden" id="edit_shift_id" value="">
                <div id="edit_shift_error" style="display:none; background: #FEF2F2; border: 1px solid #FECACA; padding: 10px 14px; border-radius: 8px; color: #DC2626; font-size: 13px; font-weight: 500; margin-bottom: 16px;"></div>

                <div class="lg-form-group" style="margin-bottom: 16px;">
                    <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Shift Name <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="text" id="edit_shift_name" class="lg-input" placeholder="e.g. Morning Shift" required autocomplete="off" style="width: 100%; height: 42px; padding: 0 14px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="lg-form-group" style="margin-bottom: 0;">
                        <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                            Start Time <span style="color: #EF4444;">*</span>
                        </label>
                        <div class="lg-time-input-container">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <input type="time" id="edit_shift_start_time" class="lg-input lg-time-input" required style="width: 100%; height: 42px; padding: 0 12px 0 36px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="lg-form-group" style="margin-bottom: 0;">
                        <label class="lg-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                            End Time <span style="color: #EF4444;">*</span>
                        </label>
                        <div class="lg-time-input-container">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <input type="time" id="edit_shift_end_time" class="lg-input lg-time-input" required style="width: 100%; height: 42px; padding: 0 12px 0 36px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13.5px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #64748B; margin-top: 10px; display: flex; align-items: center; gap: 5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Click the clock / field to select hour, minute, and AM/PM. Overnight shifts are supported.</span>
                </div>
            </div>
            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid #F1F5F9; background: #FAFAFC; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <button type="button" class="lg-btn-cancel" data-dismiss="modal" style="height: 38px; padding: 0 16px; font-size: 13px;">Cancel</button>
                <button type="button" id="btnSubmitEditShift" class="lg-btn-save" style="height: 38px; padding: 0 18px; font-size: 13px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Update Shift</span>
                </button>
            </div>
        </div>
    </div>
</div>

@if($lgAdminTabs)
<!-- ============================================== -->
<!-- CREATE / EDIT ROLE MODAL (Permission Menu)     -->
<!-- ============================================== -->
<style>
/* Create Role popup and Role Master Directory: theme variables (original, slate, green, dark) */
#lgRoleModal .lg-role-modal-wrapper, #deleteRoleModalOverlay .lg-role-modal-wrapper { background: var(--t-bg-card, #FFFFFF); border-color: var(--t-border, #E2E8F0); }
#lgRoleModal .lg-role-modal-header, #lgRoleModal .lg-role-modal-footer,
#deleteRoleModalOverlay .lg-role-modal-header, #deleteRoleModalOverlay .lg-role-modal-body, #deleteRoleModalOverlay .lg-role-modal-footer { background: var(--t-bg-card, #FFFFFF); border-color: var(--t-border, #E2E8F0); }
#lgRoleModal .lg-role-modal-title, #deleteRoleModalOverlay .lg-role-modal-title { color: var(--t-text, #0F172A); }
#lgRoleModal .lg-role-modal-subtitle { color: var(--t-text-muted, #64748B); }
#lgRoleModal .lg-role-modal-close-btn { background: var(--t-bg-alt, #F1F5F9); border-color: var(--t-border, #E2E8F0); color: var(--t-text-muted, #64748B); }
#lgRoleModal .lg-role-modal-label { color: var(--t-text-body, #334155); }
html[data-theme="dark"] #lgRoleModal .lg-role-modal-label, html[data-theme="dark"] #lgRoleModal .lg-rp-act,
html[data-theme="dark"] #lgRoleModal .lg-rp-row-label, html[data-theme="dark"] #lgRoleModal .lg-rp-all { color: var(--t-text) !important; }
#lgRoleModal .lg-role-modal-input, #lgRoleModal .lg-rp-search { background-color: var(--t-bg-input, #FFFFFF) !important; border-color: var(--t-border-strong, #CBD5E1) !important; color: var(--t-text, #0F172A) !important; }
#lgRoleModal .lg-btn-role-cancel { background: var(--t-bg-card, #FFFFFF); color: var(--t-text-body, #475569); border-color: var(--t-border-strong, #CBD5E1); }
.lg-role-modal-close-btn:focus-visible, .lg-btn-role-cancel:focus-visible, .lg-btn-role-save:focus-visible, .lg-rp-link:focus-visible,
.lg-rp-dir-btn:focus-visible { outline: 2px solid var(--t-accent, #1464F4); outline-offset: 2px; }

#lgRoleModal.lg-role-modal-overlay { transition: opacity .16s ease; }
#lgRoleModal .lg-role-modal-wrapper.lg-rp-wrapper { max-width: 820px; max-height: calc(100vh - 32px); }
.lg-rp-body { padding: 18px 24px 22px; overflow-y: auto; flex: 1 1 auto; min-height: 0; background: var(--t-bg-card, #FFFFFF); }
.lg-rp-note { display: flex; gap: 8px; align-items: center; padding: 10px 12px; border-radius: 10px; margin-bottom: 14px; font-size: 13px; font-weight: 600;
    background: var(--t-accent-soft, #EFF6FF); color: var(--t-accent, #1464F4); border: 1px solid var(--t-accent-border, #BFDBFE); }
.lg-rp-fields { display: grid; grid-template-columns: 1fr 1.4fr; gap: 16px; margin-bottom: 18px; }
.lg-rp-textarea { height: 64px; padding: 10px 14px; resize: vertical; min-height: 44px; line-height: 1.4; }
.lg-rp-hintrow { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; color: var(--t-text-muted, #64748B); }
.lg-rp-hintrow code { background: var(--t-bg-alt, #F1F5F9); color: var(--t-accent, #1464F4); padding: 1px 6px; border-radius: 4px; font-size: 11.5px; }
.lg-rp-perm-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; padding-top: 14px;
    border-top: 1px solid var(--t-border, #E2E8F0); margin-bottom: 12px; }
.lg-rp-h { font-size: 12px; font-weight: 800; letter-spacing: .08em; color: var(--t-text, #0F172A); }
.lg-rp-count { font-size: 12.5px; color: var(--t-text-muted, #64748B); margin-top: 2px; }
.lg-rp-count strong { color: var(--t-accent, #1464F4); }
.lg-rp-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.lg-rp-search { height: 34px; width: 200px; padding: 0 10px 0 30px; border-radius: 8px; font-size: 13px; outline: none;
    border: 1px solid var(--t-border-strong, #CBD5E1); background: var(--t-bg-input, #FFFFFF) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2.2' stroke-linecap='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 10px center;
    color: var(--t-text, #0F172A); }
.lg-rp-search:focus { border-color: var(--t-accent, #1464F4); box-shadow: var(--lg-shadow-focus, 0 0 0 3px rgba(20,100,244,.15)); }
.lg-rp-link { background: none; border: 0; padding: 2px 0; font-size: 12.5px; font-weight: 600; color: var(--t-accent, #1464F4); cursor: pointer; }
.lg-rp-link:hover { text-decoration: underline; }
.lg-rp-link:disabled { color: var(--t-text-muted, #94A3B8); cursor: not-allowed; text-decoration: none; }
.lg-rp-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; align-items: start; }
.lg-rp-card { border: 1px solid var(--t-border, #E2E8F0); border-radius: 12px; background: var(--t-bg-card, #FFFFFF); overflow: hidden; }
.lg-rp-card-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; background: var(--t-bg-alt, #F8FAFC);
    border-bottom: 1px solid var(--t-border, #E2E8F0); }
.lg-rp-card-title { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 700; color: var(--t-text, #0F172A); margin: 0; }
.lg-rp-card-title svg { flex: 0 0 auto; color: var(--t-accent, #1464F4); }
.lg-rp-all { display: inline-flex !important; align-items: center; gap: 0; font-size: 11px; font-weight: 700; letter-spacing: .05em; color: var(--t-text-muted, #64748B);
    margin: 0; cursor: pointer; text-transform: uppercase; }
.lg-rp-row { padding: 9px 12px; border-top: 1px solid var(--t-border, #F1F5F9); }
.lg-rp-row:first-of-type { border-top: 0; }
.lg-rp-row-label { font-size: 12.5px; font-weight: 600; color: var(--t-text-body, #334155); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.lg-rp-row-hint { font-size: 11.5px; font-weight: 500; color: var(--t-text-muted, #64748B); margin: -3px 0 6px; }
.lg-rp-lock { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 700; color: var(--t-warning, #B45309);
    background: color-mix(in srgb, var(--t-warning, #D97706) 12%, transparent); padding: 1px 6px; border-radius: 999px; }
.lg-rp-acts { display: flex; flex-wrap: wrap; gap: 6px 14px; }
.lg-rp-act { display: inline-flex !important; align-items: center; gap: 0; margin: 0; font-size: 11px; font-weight: 700; letter-spacing: .05em;
    color: var(--t-text-body, #475569); cursor: pointer; user-select: none; }
.lg-rp-act input, .lg-rp-all input { width: 15px; height: 15px; margin: 0 6px 0 0 !important; flex: 0 0 auto; position: static; accent-color: var(--t-accent-fill, #1464F4); cursor: pointer; }
.lg-rp-act.is-locked, .lg-rp-act.is-locked input { cursor: not-allowed; opacity: .55; }
.lg-rp-act input:focus-visible, .lg-rp-all input:focus-visible { outline: 2px solid var(--t-accent, #1464F4); outline-offset: 2px; }
.lg-rp-empty { padding: 26px 10px; text-align: center; color: var(--t-text-muted, #64748B); font-size: 13px; }
.lg-rp-discard { display: flex; align-items: center; gap: 10px; margin-right: auto; font-size: 13px; font-weight: 600; color: var(--t-danger, #DC2626); }
.lg-rp-discard[hidden], .lg-rp-note[hidden], .lg-rp-empty[hidden] { display: none; }
#lgRoleModal .lg-role-modal-footer { flex-wrap: wrap; }

/* Role Master Directory */
.lg-rp-dir-head { font-size: 13px; font-weight: 700; color: var(--t-text-body, #334155); margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
.lg-rp-dir-total { font-weight: 500; color: var(--t-text-muted, #64748B); font-size: 12px; }
.lg-rp-dir-wrap { overflow-x: auto; border: 1px solid var(--t-border, #E2E8F0); border-radius: 10px; }
.lg-rp-dir { width: 100%; font-size: 13px; border-collapse: collapse; text-align: left; }
.lg-rp-dir th { padding: 10px 14px; background: var(--t-bg-alt, #F8FAFC); border-bottom: 1px solid var(--t-border, #E2E8F0); color: var(--t-text-body, #475569);
    font-weight: 600; white-space: nowrap; }
.lg-rp-dir td { padding: 10px 14px; border-bottom: 1px solid var(--t-border, #F1F5F9); color: var(--t-text, #1E293B); vertical-align: middle; }
.lg-rp-dir tr:last-child td { border-bottom: 0; }
.lg-rp-dir .muted { color: var(--t-text-muted, #64748B); }
.lg-rp-dir code { background: var(--t-bg-alt, #F1F5F9); color: var(--t-accent, #1464F4); padding: 1px 6px; border-radius: 4px; font-size: 11.5px; }
.lg-rp-dir-empty { text-align: center; color: var(--t-text-muted, #64748B); padding: 18px !important; }
.lg-rp-badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 1px 7px; border-radius: 999px; margin-left: 6px;
    background: var(--t-accent-soft, #EFF6FF); color: var(--t-accent, #1464F4); border: 1px solid var(--t-accent-border, #BFDBFE); vertical-align: 1px; }
.lg-rp-dir-actions { display: flex; justify-content: flex-end; gap: 6px; }
.lg-rp-dir-btn { border: 1px solid var(--t-border-strong, #CBD5E1); background: var(--t-bg-card, #FFFFFF); border-radius: 6px; padding: 4px 8px; cursor: pointer;
    color: var(--t-text-body, #334155); line-height: 1; }
.lg-rp-dir-btn:hover { border-color: var(--t-accent, #1464F4); color: var(--t-accent, #1464F4); }
.lg-rp-dir-btn.danger { color: var(--t-danger, #EF4444); border-color: color-mix(in srgb, var(--t-danger, #EF4444) 35%, transparent); }

@media (max-width: 760px) {
    .lg-rp-grid, .lg-rp-fields { grid-template-columns: 1fr; }
    #lgRoleModal .lg-role-modal-wrapper.lg-rp-wrapper { max-width: 100%; max-height: calc(100vh - 16px); }
    #lgRoleModal.lg-role-modal-overlay { padding: 8px; }
    .lg-rp-body { padding: 14px 16px 18px; }
    .lg-rp-search { width: 100%; }
    .lg-rp-tools { width: 100%; }
}
@media (prefers-reduced-motion: reduce) {
    #lgRoleModal .lg-role-modal-wrapper { animation: none; }
}
</style>

<div class="lg-role-modal-overlay" id="lgRoleModal" aria-hidden="true">
    <div class="lg-role-modal-wrapper lg-rp-wrapper" role="dialog" aria-modal="true" aria-labelledby="lgRoleModalTitle" aria-describedby="lgRoleModalSub">
        <!-- Header (stays on top) -->
        <div class="lg-role-modal-header">
            <div class="lg-role-modal-header-left">
                <div class="lg-role-modal-icon-badge">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>
                    </svg>
                </div>
                <div>
                    <h3 class="lg-role-modal-title" id="lgRoleModalTitle">Create Role</h3>
                    <p class="lg-role-modal-subtitle" id="lgRoleModalSub">Choose the menus and actions this role can use</p>
                </div>
            </div>
            <button type="button" class="lg-role-modal-close-btn" id="lgRoleModalClose" aria-label="Close">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Body (scrolls) -->
        <div class="lg-rp-body">
            <div class="lg-rp-note" id="lgRpAdminNote" hidden>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span id="lgRpAdminNoteText">Admin has full access. This role cannot be changed.</span>
            </div>

            <div class="lg-rp-fields">
                <div class="lg-role-form-group">
                    <label for="lg_rp_role_title" class="lg-role-modal-label">Role name <span style="color: #EF4444;" aria-hidden="true">*</span></label>
                    {{-- not "name"/"email": keeps the browser from filling a saved login into it --}}
                    <input type="text" id="lg_rp_role_title" name="lg_rp_role_title" class="lg-role-modal-input" placeholder="e.g. Sales Manager"
                           maxlength="50" autocomplete="off" autocapitalize="words" spellcheck="false" data-lpignore="true" data-1p-ignore="true"
                           data-form-type="other" aria-required="true" aria-describedby="lgRpSlugHint lgRpNameErr">
                    <div class="lg-rp-hintrow" id="lgRpSlugHint"><span>Role key: <code id="lgRpSlug">-</code></span><span id="lgRpNameCount">0 / 50</span></div>
                    <div id="lgRpNameErr" class="lg-role-inline-error" role="alert" hidden></div>
                </div>
                <div class="lg-role-form-group">
                    <label for="lg_rp_role_about" class="lg-role-modal-label">Description <span class="lg-rp-count" style="font-weight: 500;">(optional)</span></label>
                    <textarea id="lg_rp_role_about" name="lg_rp_role_about" class="lg-role-modal-input lg-rp-textarea" maxlength="200" rows="2"
                              placeholder="What this role is for" autocomplete="off" data-lpignore="true" data-1p-ignore="true" aria-describedby="lgRpAboutCount lgRpAboutErr"></textarea>
                    <div class="lg-rp-hintrow"><span></span><span id="lgRpAboutCount">0 / 200</span></div>
                    <div id="lgRpAboutErr" class="lg-role-inline-error" role="alert" hidden></div>
                </div>
            </div>

            <div class="lg-rp-perm-head">
                <div>
                    <div class="lg-rp-h" id="lgRpPermTitle">PERMISSION MENU</div>
                    <div class="lg-rp-count" id="lgRpCount" aria-live="polite"><strong>0</strong> permissions selected</div>
                </div>
                <div class="lg-rp-tools">
                    <input type="search" id="lgRpSearch" class="lg-rp-search" placeholder="Search menu..." aria-label="Search menu" autocomplete="off" data-lpignore="true">
                    <button type="button" class="lg-rp-link" id="lgRpSelectAll">Select all</button>
                    <button type="button" class="lg-rp-link" id="lgRpClearAll">Clear all</button>
                </div>
            </div>
            <div id="lgRpPermErr" class="lg-role-inline-error" role="alert" hidden style="margin-bottom: 10px;"></div>
            <div class="lg-rp-grid" id="lgRpGrid" role="group" aria-labelledby="lgRpPermTitle"></div>
            <div class="lg-rp-empty" id="lgRpEmpty" hidden>No menu matches your search.</div>
        </div>

        <!-- Footer (stays at the bottom) -->
        <div class="lg-role-modal-footer">
            <div class="lg-rp-discard" id="lgRpDiscard" hidden role="alert">
                <span>Discard changes?</span>
                <button type="button" class="lg-btn-role-cancel" id="lgRpKeep" style="height: 32px; padding: 0 12px;">Keep editing</button>
                <button type="button" class="lg-btn-role-delete" id="lgRpDiscardYes" style="height: 32px; padding: 0 12px;">Discard</button>
            </div>
            <button type="button" class="lg-btn-role-cancel" id="lgRpCancel">Cancel</button>
            <button type="button" class="lg-btn-role-save" id="lgRpSave" disabled>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>Save role</span>
            </button>
        </div>
    </div>
</div>
@endif

<!-- ============================================== -->
<!-- DELETE ROLE CONFIRMATION MODAL                 -->
<!-- ============================================== -->
<div class="lg-role-modal-overlay" id="deleteRoleModalOverlay">
    <div class="lg-role-modal-wrapper" id="deleteRoleModalWrapper" style="max-width: 440px;" role="dialog" aria-modal="true" aria-labelledby="deleteRoleModalTitle">
        <!-- Header -->
        <div class="lg-role-modal-header">
            <div class="lg-role-modal-header-left">
                <div class="lg-role-modal-icon-badge danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="lg-role-modal-title" id="deleteRoleModalTitle">Delete role?</h3>
                    <p class="lg-role-modal-subtitle">Permanent action</p>
                </div>
            </div>
            <button type="button" class="lg-role-modal-close-btn" id="btnDeleteRoleClose" aria-label="Close modal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="lg-role-modal-body">
            <input type="hidden" id="delete_role_id" value="">
            <input type="hidden" id="delete_role_name_val" value="">
            <p style="font-size: 14px; color: #334155; line-height: 1.5; margin: 0 0 10px 0;">
                Are you sure you want to delete the role <strong id="delete_role_name_display" style="color: #0F172A;"></strong>?
            </p>
            <div class="lg-role-delete-warning-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Users assigned to this role must be reassigned first. This action cannot be undone.</span>
            </div>
            <div id="delete_role_inline_error" class="lg-role-inline-error" style="display: none; margin-top: 10px;"></div>
        </div>

        <!-- Footer -->
        <div class="lg-role-modal-footer">
            <button type="button" class="lg-btn-role-cancel" id="btnDeleteRoleCancel">Cancel</button>
            <button type="button" class="lg-btn-role-delete" id="btnDeleteRoleSubmit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                <span>Delete</span>
            </button>
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        // Move modals directly to body so they are never trapped by nested stacking contexts/backdrop
        $('#createShiftModal, #manageShiftsModal, #editShiftModal, #lgRoleModal, #deleteRoleModalOverlay').appendTo('body');

        // Tab switching function: shows ONLY the selected tab container and hides all others
        function switchTab(tabId) {
            if (!$('#' + tabId).length) {
                tabId = 'tab-profile';
            }

            // 1. Update navigation button styles
            $('.lg-nav-tab-item').removeClass('active').attr('aria-selected', 'false');
            $('.lg-nav-tab-item[data-tab="' + tabId + '"]').addClass('active').attr('aria-selected', 'true');

            // 2. Hide all tab containers and reveal only the active container
            $('.lg-tab-pane').removeClass('active').hide();
            $('#' + tabId).addClass('active').show();

            // 3. Keep form hidden input in sync for persistence
            const tabSlug = tabId.replace('tab-', '');
            $('#form_active_tab').val(tabSlug);

            // 4. Update URL hash without causing a page jump or reload
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabSlug);
            } else {
                location.hash = tabSlug;
            }
        }

        // Click handler for tab navigation buttons
        $(document).on('click', '.lg-nav-tab-item', function(e) {
            e.preventDefault();
            const targetTab = $(this).data('tab');
            switchTab(targetTab);
        });

        // Initialize active tab from URL hash or default
        const hash = window.location.hash.replace('#', '');
        if (hash && $('#tab-' + hash).length) {
            switchTab('tab-' + hash);
        } else {
            const initialTab = $('#form_active_tab').val() || 'profile';
            switchTab('tab-' + initialTab);
        }

        // Custom Dropdown Interactive Handling
        $(document).on('click', '.lg-custom-dropdown-trigger', function (e) {
            e.stopPropagation();
            const $wrap = $(this).closest('.lg-custom-dropdown-wrap');
            const isOpen = $wrap.hasClass('open');

            $('.lg-custom-dropdown-wrap').not($wrap).removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');

            if (isOpen) {
                $wrap.removeClass('open');
                $(this).attr('aria-expanded', 'false');
            } else {
                $wrap.addClass('open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        $(document).on('click', '.lg-custom-dropdown-option', function (e) {
            e.stopPropagation();
            const $option = $(this);
            const $wrap = $option.closest('.lg-custom-dropdown-wrap');
            const selectId = $wrap.data('select-id');
            const val = $option.data('val');
            const text = $.trim($option.find('span').text() || $option.text());

            $('#' + selectId).val(val).trigger('change');
            $wrap.find('.lg-custom-dropdown-label').text(text);
            $wrap.find('.lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
            $option.addClass('selected');
            if (val && !$option.find('.lg-option-check').length) {
                $option.append('<svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
            }
            $wrap.removeClass('open');
            $wrap.find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        });

        $(document).on('click', function () {
            $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        });

        // =========================================================================
        // ROLES & PERMISSIONS: directory, Create / Edit / Duplicate Role popup, delete (Admin only)
        // The permission list comes from config/permissions.php; the server checks everything again.
        // =========================================================================
        @if($lgAdminTabs)
        (function () {
            var MENU = @json(\App\Support\RoleAccess::forPopup());
            var URL_ROLES = "{{ route('rolesIndex') }}";
            var CSRF = "{{ csrf_token() }}";
            var roles = [];
            var state = { mode: 'create', id: null, duplicateOf: null, readOnly: false, snapshot: '', opener: null, busy: false };
            var $modal = $('#lgRoleModal'), $grid = $('#lgRpGrid'), $name = $('#lg_rp_role_title'), $about = $('#lg_rp_role_about');

            function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
            function slugify(s) {
                return String(s || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
                    .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || '';
            }
            function uniqueSlug(name) {
                var base = slugify(name), slug = base, n = 2;
                if (!base) return '-';
                var taken = roles.filter(function (r) { return r.id !== state.id; }).map(function (r) { return r.slug; });
                while (taken.indexOf(slug) !== -1) { slug = base + '_' + n++; }
                return slug;
            }
            function ajax(method, url, data) {
                return $.ajax({ url: url, type: method, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    contentType: 'application/json', data: data ? JSON.stringify(data) : undefined, dataType: 'json' });
            }
            function errMsg(xhr, fallback) {
                return (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : fallback;
            }

            // ---------- Role Master Directory ----------
            var ICON_EDIT = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
            var ICON_VIEW = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            var ICON_COPY = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            var SVG = function (paths, size) { return '<svg width="' + (size || 15) + '" height="' + (size || 15) + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + '</svg>'; };
            var ICON_LOCK = SVG('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 11);
            // the group icons of the sidebar (config/permissions.php "icon")
            var GROUP_ICONS = {
                home: SVG('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'),
                leads: SVG('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/>'),
                megaphone: SVG('<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'),
                headset: SVG('<path d="M3 14v-3a9 9 0 0 1 18 0v3"/><path d="M21 15a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-5H5a2 2 0 0 0-2 2z"/>'),
                settings: SVG('<circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/>'),
                database: SVG('<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'),
                people: SVG('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
                chart: SVG('<path d="M18 20V10M12 20V4M6 20v-6"/>'),
                mic: SVG('<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v4M8 21h8"/>'),
                folder: SVG('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>')
            };
            var ICON_DEL = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

            function renderRoles() {
                $('#totalRolesCount').text(roles.length);
                if (!roles.length) {
                    $('#rolesTableBody').html('<tr><td colspan="7" class="lg-rp-dir-empty">No roles yet.</td></tr>');
                    return;
                }
                $('#rolesTableBody').html(roles.map(function (r) {
                    var perms = r.is_system ? 'All permissions' : (r.permissions_count + (r.permissions_count === 1 ? ' permission' : ' permissions'));
                    return '<tr data-role-id="' + r.id + '">'
                        + '<td style="font-weight:600;">#' + r.id + '</td>'
                        + '<td><span style="font-weight:600;">' + esc(r.name) + '</span>' + (r.is_system ? '<span class="lg-rp-badge">System</span>' : '')
                        + '<div><code>' + esc(r.slug) + '</code></div></td>'
                        + '<td class="muted">' + (r.description ? esc(r.description) : '-') + '</td>'
                        + '<td>' + r.users_count + '</td>'
                        + '<td>' + perms + '</td>'
                        + '<td class="muted">' + esc(r.created_at) + '</td>'
                        + '<td><div class="lg-rp-dir-actions">'
                        + '<button type="button" class="lg-rp-dir-btn" data-act="edit" data-id="' + r.id + '" title="' + (r.is_system ? 'View role' : 'Edit role') + '" aria-label="' + (r.is_system ? 'View' : 'Edit') + ' role ' + esc(r.name) + '">' + (r.is_system ? ICON_VIEW : ICON_EDIT) + '</button>'
                        + '<button type="button" class="lg-rp-dir-btn" data-act="duplicate" data-id="' + r.id + '" title="Duplicate role" aria-label="Duplicate role ' + esc(r.name) + '">' + ICON_COPY + '</button>'
                        + (r.deletable ? '<button type="button" class="lg-rp-dir-btn danger" data-act="delete" data-id="' + r.id + '" title="Delete role" aria-label="Delete role ' + esc(r.name) + '">' + ICON_DEL + '</button>' : '')
                        + '</div></td></tr>';
                }).join(''));
            }
            function loadRoles() {
                return ajax('GET', URL_ROLES).done(function (res) { roles = res.roles || []; renderRoles(); })
                    .fail(function (xhr) {
                        $('#rolesTableBody').html('<tr><td colspan="7" class="lg-rp-dir-empty">' + esc(errMsg(xhr, 'The roles could not be loaded.')) + '</td></tr>');
                    });
            }
            function findRole(id) { return roles.filter(function (r) { return r.id === id; })[0]; }

            // ---------- Permission Menu ----------
            function buildGrid() {
                $grid.html(MENU.map(function (g) {
                    var single = g.items.length === 1 && g.items[0].label === g.label;
                    var rows = g.items.map(function (it) {
                        var acts = it.actions.map(function (a) {
                            var id = 'lgrp_' + a.key.replace(/\./g, '_');
                            return '<label class="lg-rp-act' + (a.admin_only ? ' is-locked' : '') + '" for="' + id + '"' + (a.admin_only ? ' title="Admin only"' : '') + '>'
                                + '<input type="checkbox" id="' + id + '" class="lg-rp-cb" value="' + a.key + '" data-item="' + it.key + '" data-action="' + a.action + '"'
                                + (a.admin_only ? ' data-locked="1" disabled' : '')
                                + ' aria-label="' + esc(it.label + ': ' + a.label + (a.admin_only ? ' (Admin only)' : '')) + '">'
                                + '<span>' + esc(a.label.toUpperCase()) + '</span></label>';
                        }).join('');
                        var lock = it.admin_only ? ' <span class="lg-rp-lock" title="Admin only">' + ICON_LOCK + ' Admin only</span>' : '';
                        var oneLock = !it.admin_only && it.actions.some(function (a) { return a.admin_only; })
                            ? ' <span class="lg-rp-lock" title="Admin only">' + ICON_LOCK + ' Delete: Admin only</span>' : '';
                        return '<div class="lg-rp-row" data-item="' + it.key + '" data-search="' + esc((g.label + ' ' + it.label).toLowerCase()) + '">'
                            + (single && !lock ? '' : '<div class="lg-rp-row-label">' + esc(it.label) + lock + oneLock + '</div>')
                            + (single && oneLock ? '<div class="lg-rp-row-label">' + oneLock + '</div>' : '')
                            + (it.hint ? '<div class="lg-rp-row-hint">' + esc(it.hint) + '</div>' : '')
                            + '<div class="lg-rp-acts">' + acts + '</div></div>';
                    }).join('');
                    var gid = 'lgrp_g_' + g.key;
                    return '<section class="lg-rp-card" data-group="' + g.key + '" data-search="' + esc(g.label.toLowerCase()) + '" aria-labelledby="' + gid + '_t">'
                        + '<div class="lg-rp-card-head"><h4 class="lg-rp-card-title" id="' + gid + '_t">' + (GROUP_ICONS[g.icon] || GROUP_ICONS.folder) + '' + esc(g.label) + '</h4>'
                        + '<label class="lg-rp-all" for="' + gid + '"><input type="checkbox" id="' + gid + '" class="lg-rp-group-cb" data-group="' + g.key + '" aria-label="Select all in ' + esc(g.label) + '"> All</label></div>'
                        + rows + '</section>';
                }).join(''));
            }
            function boxes() { return $grid.find('.lg-rp-cb'); }
            function editable() { return boxes().not('[data-locked]'); }
            function selectedKeys() { return editable().filter(':checked').map(function () { return this.value; }).get(); }
            function itemBox($item, action) { return $grid.find('.lg-rp-cb[data-item="' + $item + '"][data-action="' + action + '"]'); }

            function refreshCounts() {
                var n = state.readOnly ? boxes().length : selectedKeys().length;
                $('#lgRpCount').html('<strong>' + n + '</strong> ' + (n === 1 ? 'permission' : 'permissions') + ' selected');
                $grid.find('.lg-rp-card').each(function () {
                    var $c = $(this), $b = $c.find('.lg-rp-cb').not('[data-locked]'), on = $b.filter(':checked').length;
                    var g = $c.find('.lg-rp-group-cb')[0];
                    g.checked = $b.length > 0 && on === $b.length;
                    g.indeterminate = on > 0 && on < $b.length;
                    g.disabled = state.readOnly || $b.length === 0;
                });
                var nameOk = $.trim($name.val()).length > 0;
                $('#lgRpSave').prop('disabled', !nameOk || state.busy);
            }
            // CREATE / EDIT / DELETE / EXPORT ... tick VIEW of the same item; unticking VIEW clears the item
            $grid.on('change', '.lg-rp-cb', function () {
                var item = $(this).data('item'), action = $(this).data('action');
                if (action === 'view' && !this.checked) {
                    $grid.find('.lg-rp-cb[data-item="' + item + '"]').not('[data-locked]').prop('checked', false);
                } else if (action !== 'view' && this.checked) {
                    itemBox(item, 'view').not('[data-locked]').prop('checked', true);
                }
                refreshCounts();
            });
            $grid.on('change', '.lg-rp-group-cb', function () {
                $grid.find('.lg-rp-card[data-group="' + $(this).data('group') + '"] .lg-rp-cb').not('[data-locked]').prop('checked', this.checked);
                refreshCounts();
            });
            $('#lgRpSelectAll').on('click', function () { editable().prop('checked', true); refreshCounts(); });
            $('#lgRpClearAll').on('click', function () { editable().prop('checked', false); refreshCounts(); });
            $('#lgRpSearch').on('input', function () {
                var q = $.trim(this.value).toLowerCase(), any = false;
                $grid.find('.lg-rp-card').each(function () {
                    var $c = $(this), groupHit = !q || String($c.data('search')).indexOf(q) !== -1, shown = 0;
                    $c.find('.lg-rp-row').each(function () {
                        var hit = groupHit || String($(this).data('search')).indexOf(q) !== -1;
                        $(this).toggle(hit);
                        if (hit) shown++;
                    });
                    $c.toggle(shown > 0);
                    if (shown) any = true;
                });
                $('#lgRpEmpty').prop('hidden', any);
            });

            // ---------- Fields ----------
            function refreshName() {
                var v = $name.val();
                $('#lgRpSlug').text(uniqueSlug(v));
                $('#lgRpNameCount').text(v.length + ' / 50');
                $('#lgRpNameErr').prop('hidden', true).text('');
                refreshCounts();
            }
            $name.on('input', refreshName);
            $about.on('input', function () { $('#lgRpAboutCount').text(this.value.length + ' / 200'); $('#lgRpAboutErr').prop('hidden', true); });

            function showErr(sel, msg) { $(sel).text(msg).prop('hidden', !msg); }
            function clearErrs() { ['#lgRpNameErr', '#lgRpAboutErr', '#lgRpPermErr'].forEach(function (s) { showErr(s, ''); }); }
            function current() { return JSON.stringify([$.trim($name.val()), $.trim($about.val()), selectedKeys()]); }

            // ---------- Open / close ----------
            function open(mode, role, keys) {
                state.mode = mode; state.id = mode === 'edit' ? role.id : null;
                state.duplicateOf = mode === 'duplicate' ? role.id : null;
                state.readOnly = mode === 'edit' && role.is_system;
                state.opener = document.activeElement;
                clearErrs();
                $('#lgRpDiscard').prop('hidden', true);
                $('#lgRpSearch').val('').trigger('input');
                $('#lgRoleModalTitle').text(mode === 'edit' ? 'Edit Role' : 'Create Role');
                $('#lgRoleModalSub').text(state.readOnly ? 'System role' : (mode === 'duplicate' ? 'A copy of "' + role.name + '": change what you need' : 'Choose the menus and actions this role can use'));
                var name = mode === 'edit' ? role.name : (mode === 'duplicate' ? ('Copy of ' + role.name).slice(0, 50) : '');
                $name.val(name).prop('readonly', state.readOnly);
                $about.val(role && mode !== 'create' ? (role.description || '') : '').prop('readonly', state.readOnly);
                $('#lgRpAboutCount').text($about.val().length + ' / 200');
                boxes().each(function () {
                    var locked = !!$(this).attr('data-locked');
                    this.checked = state.readOnly ? true : (!locked && keys.indexOf(this.value) !== -1);
                    this.disabled = state.readOnly || locked;
                });
                $('#lgRpAdminNote').prop('hidden', !state.readOnly);
                $('#lgRpSave').toggle(!state.readOnly);
                $('#lgRpSelectAll, #lgRpClearAll').prop('disabled', state.readOnly);
                $('#lgRpCancel').text(state.readOnly ? 'Close' : 'Cancel');
                refreshName();
                state.snapshot = current();
                if ($modal.parent().is(':not(body)')) $modal.appendTo('body');
                $modal.addClass('active').attr('aria-hidden', 'false');
                $('body').css('overflow', 'hidden');
                setTimeout(function () { (state.readOnly ? $('#lgRoleModalClose') : $name).trigger('focus'); }, 30);
            }
            function close(force) {
                if (!force && !state.readOnly && current() !== state.snapshot) {
                    $('#lgRpDiscard').prop('hidden', false);
                    $('#lgRpKeep').trigger('focus');
                    return;
                }
                $modal.removeClass('active').attr('aria-hidden', 'true');
                $('body').css('overflow', '');
                if (state.opener && document.body.contains(state.opener)) state.opener.focus();
            }
            $('#lgRoleModalClose, #lgRpCancel').on('click', function () { close(false); });
            $('#lgRpKeep').on('click', function () { $('#lgRpDiscard').prop('hidden', true); $name.trigger('focus'); });
            $('#lgRpDiscardYes').on('click', function () { close(true); });
            $modal.on('mousedown', function (e) { if (e.target === this) close(false); });
            $modal.on('keydown', function (e) {
                if (e.key === 'Escape') { e.preventDefault(); close(false); return; }
                if (e.key !== 'Tab') return;
                // keep the focus inside the popup
                var $f = $modal.find('button, input, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible:not(:disabled)');
                if (!$f.length) return;
                var first = $f[0], last = $f[$f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            });
            $name.on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#lgRpSave').trigger('click'); } });

            // ---------- Save ----------
            $('#lgRpSave').on('click', function () {
                if (state.readOnly || state.busy) return;
                clearErrs();
                var name = $.trim($name.val());
                if (name.length < 2 || name.length > 50) {
                    showErr('#lgRpNameErr', name.length < 2 ? 'The role name needs at least 2 characters.' : 'The role name can have at most 50 characters.');
                    $name.trigger('focus');
                    return;
                }
                var body = { name: name, description: $.trim($about.val()), permissions: selectedKeys() };
                if (state.duplicateOf) body.duplicate_of = state.duplicateOf;
                var $btn = $(this), html = $btn.html();
                state.busy = true;
                $btn.prop('disabled', true).html('<span>Saving...</span>');
                ajax(state.mode === 'edit' ? 'PUT' : 'POST', state.mode === 'edit' ? URL_ROLES + '/' + state.id : URL_ROLES, body)
                    .done(function (res) {
                        showLgToast(state.mode === 'edit' ? 'Role saved' : 'Role created');
                        close(true);
                        loadRoles();
                    })
                    .fail(function (xhr) {
                        var e = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                        if (e.name) showErr('#lgRpNameErr', e.name[0]);
                        if (e.description) showErr('#lgRpAboutErr', e.description[0]);
                        if (e.permissions) showErr('#lgRpPermErr', e.permissions.join(' '));
                        showLgToast(errMsg(xhr, 'The role could not be saved.'), true);
                    })
                    .always(function () { state.busy = false; $btn.html(html); refreshCounts(); });
            });

            // ---------- Directory actions ----------
            $('#btnToggleNewRoleForm').on('click', function (e) { e.preventDefault(); open('create', null, []); });
            $('#rolesTableBody').on('click', '.lg-rp-dir-btn', function () {
                var id = parseInt($(this).data('id'), 10), act = $(this).data('act'), role = findRole(id), $b = $(this);
                if (!role) return;
                if (act === 'delete') { openDelete(role); return; }
                $b.prop('disabled', true);
                ajax('GET', URL_ROLES + '/' + id).done(function (res) {
                    open(act === 'duplicate' ? 'duplicate' : 'edit', res.role, res.role.is_system ? [] : (res.role.permissions || []));
                }).fail(function (xhr) { showLgToast(errMsg(xhr, 'The role could not be opened.'), true); })
                  .always(function () { $b.prop('disabled', false); });
            });

            // ---------- Delete (confirmation popup) ----------
            var $del = $('#deleteRoleModalOverlay'), delRole = null, delOpener = null;
            function openDelete(role) {
                delRole = role; delOpener = document.activeElement;
                $('#delete_role_name_display').text(role.name);
                $('#delete_role_inline_error').hide().text('');
                var blocked = role.users_count > 0;
                $del.find('.lg-role-delete-warning-box span').text(blocked
                    ? 'Reassign these ' + role.users_count + ' user(s) first. A role with users cannot be deleted.'
                    : 'This role and its permissions are removed. This action cannot be undone.');
                $('#btnDeleteRoleSubmit').prop('disabled', blocked);
                if ($del.parent().is(':not(body)')) $del.appendTo('body');
                $del.addClass('active');
                setTimeout(function () { $(blocked ? '#btnDeleteRoleCancel' : '#btnDeleteRoleSubmit').trigger('focus'); }, 30);
            }
            function closeDelete() { $del.removeClass('active'); if (delOpener && document.body.contains(delOpener)) delOpener.focus(); }
            $('#btnDeleteRoleClose, #btnDeleteRoleCancel').on('click', closeDelete);
            $del.on('mousedown', function (e) { if (e.target === this) closeDelete(); });
            $del.on('keydown', function (e) { if (e.key === 'Escape') { e.preventDefault(); closeDelete(); } });
            $('#btnDeleteRoleSubmit').on('click', function () {
                if (!delRole) return;
                var $btn = $(this), html = $btn.html();
                $btn.prop('disabled', true).html('<span>Deleting...</span>');
                ajax('DELETE', URL_ROLES + '/' + delRole.id).done(function () {
                    showLgToast('Role deleted');
                    closeDelete();
                    loadRoles();
                }).fail(function (xhr) {
                    var msg = errMsg(xhr, 'The role could not be deleted.');
                    $('#delete_role_inline_error').text(msg).show();
                    showLgToast(msg, true);
                }).always(function () { $btn.prop('disabled', false).html(html); });
            });

            buildGrid();
            loadRoles();
        })();
        @endif


        // Save Work Configuration AJAX
        $('#btnSaveWorkConfig').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span>Saving...</span>');

            const workLocName = $('#wc_work_location_name').val();
            const shiftId = $('#shift_id').val();

            $.ajax({
                url: "{{ route('usersUpdateWorkConfig', $Users->id) }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    work_location_name: workLocName,
                    shift_id: shiftId
                },
                success: function (response) {
                    $btn.prop('disabled', false).html(originalHtml);
                    if (response.status === 'success') {
                        showLgToast(response.message || 'Work configuration saved successfully');
                    } else {
                        alert(response.message || 'Error saving work configuration');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                    alert('An error occurred while saving work configuration.');
                }
            });
        });

        // =========================================================================
        // Shift Management & Creation AJAX Handlers
        // =========================================================================

        // Open Create Shift Modal
        $(document).on('click', '#btnOpenCreateShiftModal, #btnManageOpenCreate', function (e) {
            e.preventDefault();
            $('#manageShiftsModal').modal('hide');
            $('#new_shift_name').val('');
            $('#new_shift_start_time').val('09:00');
            $('#new_shift_end_time').val('18:00');
            $('#create_shift_error').hide().text('');
            $('#createShiftModal').modal('show');
            setTimeout(function () {
                $('#new_shift_name').focus();
            }, 300);
        });

        // Submit Create Shift
        $('#btnSubmitCreateShift').on('click', function (e) {
            e.preventDefault();
            const name = $.trim($('#new_shift_name').val());
            const startTime = $.trim($('#new_shift_start_time').val());
            const endTime = $.trim($('#new_shift_end_time').val());

            if (!name) {
                $('#create_shift_error').text('Shift Name is required.').show();
                $('#new_shift_name').focus();
                return;
            }
            if (!startTime) {
                $('#create_shift_error').text('Start Time is required.').show();
                $('#new_shift_start_time').focus();
                return;
            }
            if (!endTime) {
                $('#create_shift_error').text('End Time is required.').show();
                $('#new_shift_end_time').focus();
                return;
            }
            if (startTime === endTime) {
                $('#create_shift_error').text('Start Time and End Time cannot be identical.').show();
                return;
            }

            $('#create_shift_error').hide();
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span>Saving...</span>');

            $.ajax({
                url: "{{ route('shiftsAjaxStore') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    start_time: startTime,
                    end_time: endTime
                },
                success: function (response) {
                    $btn.prop('disabled', false).html(originalHtml);
                    if (response.status === 'success') {
                        $('#createShiftModal').modal('hide');

                        const shift = response.shift;
                        const label = shift.formatted_label;
                        const newId = shift.id;

                        // Add option to shift dropdown menu & select it
                        $('#shiftDropdownMenu .lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
                        const newOptHtml = `
                            <div class="lg-custom-dropdown-option selected" data-val="${newId}" role="option">
                                <span>${label}</span>
                                <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        `;
                        $('#shiftDropdownMenu').append(newOptHtml);

                        // Update hidden input & trigger label
                        $('#shift_id').val(newId).trigger('change');
                        $('.lg-custom-dropdown-wrap[data-select-id="shift_id"] .lg-custom-dropdown-label').text(label);

                        showLgToast(response.message || 'Shift created successfully!');
                    } else {
                        $('#create_shift_error').text(response.message || 'Error creating shift').show();
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    let msg = 'Failed to create shift.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#create_shift_error').text(msg).show();
                }
            });
        });

        // Function to refresh Manage Shifts table
        function loadManageShiftsTable() {
            $('#manageShiftsTableBody').html('<tr><td colspan="4" style="text-align:center; padding: 20px; color: #64748B;">Loading shifts...</td></tr>');
            $.ajax({
                url: "{{ route('shiftsAjaxList') }}",
                type: 'GET',
                success: function (res) {
                    if (res.status === 'success' && res.shifts) {
                        let html = '';
                        res.shifts.forEach(function (s) {
                            html += `
                                <tr data-shift-id="${s.id}" style="border-bottom: 1px solid #F1F5F9;">
                                    <td style="padding: 10px 12px; font-weight: 600; color: #1E293B;" class="shift-cell-name">${s.name}</td>
                                    <td style="padding: 10px 12px; color: #475569;" class="shift-cell-start">${s.start_time_formatted}</td>
                                    <td style="padding: 10px 12px; color: #475569;" class="shift-cell-end">${s.end_time_formatted}</td>
                                    <td style="padding: 10px 12px; text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                            <button type="button" class="btn-edit-shift" data-id="${s.id}" data-name="${s.name}" data-start="${s.start_time}" data-end="${s.end_time}" title="Edit Shift" style="border: 1px solid #CBD5E1; background: #fff; border-radius: 6px; padding: 4px 8px; cursor: pointer; color: #334155;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            ${s.id != 1 ? `
                                            <button type="button" class="btn-delete-shift" data-id="${s.id}" data-name="${s.name}" title="Delete Shift" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: 6px; padding: 4px 8px; cursor: pointer; color: #EF4444;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            </button>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#manageShiftsTableBody').html(html);
                    }
                },
                error: function () {
                    $('#manageShiftsTableBody').html('<tr><td colspan="4" style="text-align:center; padding: 20px; color: #EF4444;">Failed to load shifts.</td></tr>');
                }
            });
        }

        // Open Manage Shifts Modal
        $(document).on('click', '#btnOpenManageShiftsModal', function (e) {
            e.preventDefault();
            loadManageShiftsTable();
            $('#manageShiftsModal').modal('show');
        });

        // Trigger native time picker when clicking anywhere on a time input field
        $(document).on('click', '.lg-time-input', function () {
            if (typeof this.showPicker === 'function') {
                try { this.showPicker(); } catch (err) {}
            }
        });

        // Edit Shift: Open Edit Shift Modal
        $(document).on('click', '.btn-edit-shift', function (e) {
            e.preventDefault();
            const shiftId = $(this).data('id');
            const name = $(this).data('name');
            const start = $(this).data('start');
            const end = $(this).data('end');

            $('#edit_shift_id').val(shiftId);
            $('#edit_shift_name').val(name);
            $('#edit_shift_start_time').val(start);
            $('#edit_shift_end_time').val(end);
            $('#edit_shift_error').hide().text('');

            $('#manageShiftsModal').modal('hide');
            $('#editShiftModal').modal('show');
            setTimeout(function() {
                $('#edit_shift_name').focus();
            }, 300);
        });

        // Submit Edit Shift
        $('#btnSubmitEditShift').on('click', function (e) {
            e.preventDefault();
            const shiftId = $('#edit_shift_id').val();
            const name = $.trim($('#edit_shift_name').val());
            const startTime = $.trim($('#edit_shift_start_time').val());
            const endTime = $.trim($('#edit_shift_end_time').val());

            if (!name) {
                $('#edit_shift_error').text('Shift Name is required.').show();
                $('#edit_shift_name').focus();
                return;
            }
            if (!startTime) {
                $('#edit_shift_error').text('Start Time is required.').show();
                $('#edit_shift_start_time').focus();
                return;
            }
            if (!endTime) {
                $('#edit_shift_error').text('End Time is required.').show();
                $('#edit_shift_end_time').focus();
                return;
            }
            if (startTime === endTime) {
                $('#edit_shift_error').text('Start Time and End Time cannot be identical.').show();
                return;
            }

            $('#edit_shift_error').hide();
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span>Updating...</span>');

            $.ajax({
                url: '/admin/shifts/' + shiftId + '/ajax-update',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    start_time: startTime,
                    end_time: endTime
                },
                success: function (res) {
                    $btn.prop('disabled', false).html(originalHtml);
                    if (res.status === 'success') {
                        $('#editShiftModal').modal('hide');
                        showLgToast('Shift updated successfully!');

                        // Refresh Manage Shifts table if user reopens it
                        loadManageShiftsTable();

                        // Update option in dropdown
                        const $opt = $('#shiftDropdownMenu .lg-custom-dropdown-option[data-val="' + shiftId + '"]');
                        if ($opt.length) {
                            $opt.find('span').text(res.shift.formatted_label);
                            if ($('#shift_id').val() == shiftId) {
                                $('.lg-custom-dropdown-wrap[data-select-id="shift_id"] .lg-custom-dropdown-label').text(res.shift.formatted_label);
                            }
                        }
                    } else {
                        $('#edit_shift_error').text(res.message || 'Failed to update shift.').show();
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    let msg = 'Failed to update shift.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#edit_shift_error').text(msg).show();
                }
            });
        });

        // Delete Shift in Manage Shifts Modal
        $(document).on('click', '.btn-delete-shift', function (e) {
            e.preventDefault();
            const shiftId = $(this).data('id');
            const shiftName = $(this).data('name');

            if (confirm('Are you sure you want to delete shift "' + shiftName + '"?')) {
                $.ajax({
                    url: '/admin/shifts/' + shiftId + '/ajax-delete',
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            showLgToast('Shift deleted successfully.');
                            loadManageShiftsTable();

                            // Remove from dropdown
                            $('#shiftDropdownMenu .lg-custom-dropdown-option[data-val="' + shiftId + '"]').remove();

                            // If this shift was active, reset to default (1)
                            if ($('#shift_id').val() == shiftId) {
                                $('#shift_id').val(1).trigger('change');
                                const defLabel = $('#shiftDropdownMenu .lg-custom-dropdown-option[data-val="1"]').text() || 'Regular – 09:00 AM – 06:00 PM';
                                $('.lg-custom-dropdown-wrap[data-select-id="shift_id"] .lg-custom-dropdown-label').text(defLabel.trim());
                                $('#shiftDropdownMenu .lg-custom-dropdown-option[data-val="1"]').addClass('selected');
                            }
                        } else {
                            alert(res.message || 'Cannot delete shift.');
                        }
                    },
                    error: function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error deleting shift.';
                        alert(msg);
                    }
                });
            }
        });

        // Sync DID: the server reports each step (DIDs checked / Smart31 VMNs); shown as a toast, never an alert
        $(document).on('click', '#btnSyncDid', function(e) {
            e.preventDefault();
            const $btn = $(this);
            if ($btn.prop('disabled')) return;
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="lg-sv-spin" aria-hidden="true"></span><span>Syncing...</span>');

            $.ajax({
                url: "{{ route('usersSyncDid') }}",
                type: 'POST',
                dataType: 'json',
                data: { _token: "{{ csrf_token() }}" },
                success: function(response) {
                    $btn.prop('disabled', false).html(originalHtml);
                    showLgToast(response.message || 'DID sync completed.', response.status !== 'success');
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message
                        : (xhr.status === 419 ? 'Your session has expired. Reload the page.' : 'DID sync failed. Please try again.');
                    showLgToast(msg, true);
                }
            });
        });

        // Delete License: confirmation dialog, then the server clears the license
        const $licModal = $('#lgDeleteLicenseModal');
        function closeLicenseModal() { $licModal.prop('hidden', true); $('#btnDeleteLicense').trigger('focus'); }
        $(document).on('click', '#btnDeleteLicense', function(e) {
            e.preventDefault();
            $licModal.prop('hidden', false);
            $('#lgDeleteLicenseCancel').trigger('focus');
        });
        $(document).on('click', '#lgDeleteLicenseCancel', closeLicenseModal);
        $licModal.on('click', function (e) { if (e.target === this) closeLicenseModal(); });
        $(document).on('keydown', function (e) { if (e.key === 'Escape' && !$licModal.prop('hidden')) closeLicenseModal(); });
        $(document).on('click', '#lgDeleteLicenseConfirm', function () {
            const $btn = $(this);
            if ($btn.prop('disabled')) return;
            $btn.prop('disabled', true).html('<span class="lg-sv-spin" aria-hidden="true"></span><span>Deleting...</span>');
            $('#btnDeleteLicense').prop('disabled', true);
            $.ajax({
                url: "{{ route('usersDeleteLicense') }}",
                type: 'POST',
                dataType: 'json',
                data: { _token: "{{ csrf_token() }}", confirm: 'DELETE' },
                success: function(response) {
                    $licModal.prop('hidden', true);
                    showLgToast(response.message || 'License deleted.');
                    setTimeout(function () { window.location.reload(); }, 1200);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<span>Delete License</span>');
                    $('#btnDeleteLicense').prop('disabled', false);
                    $licModal.prop('hidden', true);
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message
                        : (xhr.status === 419 ? 'Your session has expired. Reload the page.' : 'The license could not be deleted. Please try again.');
                    showLgToast(msg, true);
                }
            });
        });

        // Activate / Store New License Handler
        $(document).on('click', '#btnActivateNewLicense', function(e) {
            e.preventDefault();
            const email = $.trim($('#lic_email').val());
            const password = $.trim($('#lic_password').val());
            const licenseKey = $.trim($('#lic_key').val());

            if (!email || !password || !licenseKey) {
                $('#lic_form_error').text('Please fill in Admin Email, Password, and License Key.').show();
                return;
            }

            $('#lic_form_error').hide();
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span>Activating...</span>');

            $.ajax({
                url: "{{ route('adminLicenseActivate') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    email: email,
                    password: password,
                    license: licenseKey
                },
                success: function(response) {
                    $btn.prop('disabled', false).html(originalHtml);
                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                    } catch(err) {}

                    if (response.hasOwnProperty('success')) {
                        showLgToast('License activated successfully!');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        $('#lic_form_error').text(response.error || 'License activation failed.').show();
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    let err = 'An error occurred during license activation.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    }
                    $('#lic_form_error').text(err).show();
                }
            });
        });

        function showLgToast(message, isError = false) {
            $('#lg-toast-message').text(message);
            if (isError) {
                $('#lg-toast').css('background', '#DC2626');
                $('#lg-toast-icon').html('<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>');
            } else {
                $('#lg-toast').css('background', '#059669');
                $('#lg-toast-icon').html('<polyline points="20 6 9 17 4 12"></polyline>');
            }
            $('#lg-toast').stop(true, true).css('display', 'flex').hide().fadeIn(200);
            clearTimeout(window.lgToastTimer);
            window.lgToastTimer = setTimeout(function () {
                $('#lg-toast').fadeOut(300);
            }, 3500);
        }
        window.showLgToast = showLgToast;

        // Company Logo Change Handler
        $('#company_logo_input').on('change', function () {
            const file = this.files[0];
            $('#err_company_logo').hide().text('');
            if (!file) return;

            // Validate size (max 2 MB = 2 * 1024 * 1024 bytes)
            if (file.size > 2 * 1024 * 1024) {
                $('#err_company_logo').text('Company logo must not exceed 2 MB.').show();
                $(this).val('');
                return;
            }

            // Validate extension
            const validExts = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
            const ext = file.name.split('.').pop().toLowerCase();
            if (!validExts.includes(ext)) {
                $('#err_company_logo').text('Allowed logo formats are PNG, JPG, JPEG, SVG, WEBP.').show();
                $(this).val('');
                return;
            }

            $('#companyLogoFileName').text(file.name).attr('title', file.name);
            $('#btnChooseLogoText').text('Change');
            $('#btnRemoveCompanyLogo').css('display', 'inline-flex');
            $('#company_remove_logo').val('0');

            // Image Preview via FileReader
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#companyLogoImg').attr('src', e.target.result).show();
                $('#companyLogoPlaceholder').hide();
            };
            reader.readAsDataURL(file);
        });

        // Company Logo Remove Handler
        window.removeCompanyLogo = function () {
            $('#company_logo_input').val('');
            $('#company_remove_logo').val('1');
            $('#companyLogoImg').attr('src', '').hide();
            $('#companyLogoPlaceholder').css('display', 'flex').show();
            $('#companyLogoFileName').text('No logo uploaded').attr('title', 'No logo uploaded');
            $('#btnChooseLogoText').text('Choose file');
            $('#btnRemoveCompanyLogo').hide();
            $('#err_company_logo').hide().text('');
        };

        // Save Company Settings AJAX
        window.saveCompanySettings = function () {
            $('.lg-field-error').hide().text('');

            const name = $.trim($('#company_name').val());
            const address = $.trim($('#company_address').val());
            const email = $.trim($('#company_email').val());
            const phone = $.trim($('#company_phone').val());

            // 1. Client-side Validation: Required Name
            if (!name) {
                $('#err_company_name').text('Company Name is required.').show();
                $('#company_name').focus();
                showLgToast('Company Name is required.', true);
                return;
            }

            // 2. Client-side Validation: Email
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#err_company_email').text('Please enter a valid email address.').show();
                $('#company_email').focus();
                showLgToast('Please enter a valid email address.', true);
                return;
            }

            // 3. Client-side Validation: Phone
            if (phone && !/^[0-9+\s\-()]{6,30}$/.test(phone)) {
                $('#err_company_phone').text('Please enter a valid phone number (digits, +, -, spaces only).').show();
                $('#company_phone').focus();
                showLgToast('Please enter a valid phone number.', true);
                return;
            }

            // Button loading state & prevent double clicks
            const $btn = $('#btnSaveCompanySettings');
            $btn.prop('disabled', true);
            $('#saveCompanySpinner').show();
            $('#saveCompanyIcon').hide();
            $('#saveCompanyText').text('Saving...');

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('company_name', name);
            formData.append('company_address', address);
            formData.append('company_email', email);
            formData.append('company_phone', phone);
            formData.append('remove_logo', $('#company_remove_logo').val());

            const logoFile = $('#company_logo_input')[0].files[0];
            if (logoFile) {
                formData.append('company_logo', logoFile);
            }

            if ($('#rmi_report_enabled').is(':checked')) {
                formData.append('rmi_report_enabled', '1');
            }

            @if(@Auth::user()->permissionsGroup && @Auth::user()->permissionsGroup->name === 'Admin')
                if ($('#manager_delete_permission').is(':checked')) {
                    formData.append('manager_delete_permission', '1');
                }
            @endif

            if ($('#ai_enabled').is(':checked')) {
                formData.append('ai_enabled', '1');
            }

            $.ajax({
                url: "{{ route('companySettingsUpdate') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (res) {
                    $btn.prop('disabled', false);
                    $('#saveCompanySpinner').hide();
                    $('#saveCompanyIcon').show();
                    $('#saveCompanyText').text('Save Changes');

                    if (res.success) {
                        showLgToast(res.message || 'Company settings saved');

                        if (res.data) {
                            $('#company_name').val(res.data.company_name);
                            $('#company_address').val(res.data.company_address);
                            $('#company_email').val(res.data.company_email);
                            $('#company_phone').val(res.data.company_phone);
                            $('#company_remove_logo').val('0');
                            $('#company_logo_input').val('');

                            if (res.data.has_logo && res.data.logo_url) {
                                $('#companyLogoImg').attr('src', res.data.logo_url).show();
                                $('#companyLogoPlaceholder').hide();
                                $('#companyLogoFileName').text(res.data.style_logo_en).attr('title', res.data.style_logo_en);
                                $('#btnChooseLogoText').text('Change');
                                $('#btnRemoveCompanyLogo').css('display', 'inline-flex').show();
                            } else {
                                $('#companyLogoImg').attr('src', '').hide();
                                $('#companyLogoPlaceholder').css('display', 'flex').show();
                                $('#companyLogoFileName').text('No logo uploaded').attr('title', 'No logo uploaded');
                                $('#btnChooseLogoText').text('Choose file');
                                $('#btnRemoveCompanyLogo').hide();
                            }
                        }
                    } else {
                        showLgToast(res.message || 'Failed to save company settings.', true);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    $('#saveCompanySpinner').hide();
                    $('#saveCompanyIcon').show();
                    $('#saveCompanyText').text('Save Changes');

                    let errMessage = 'An error occurred while saving company settings.';
                    if (xhr.status === 422 && xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.company_name) {
                                $('#err_company_name').text(errors.company_name[0]).show();
                            }
                            if (errors.company_address) {
                                $('#err_company_address').text(errors.company_address[0]).show();
                            }
                            if (errors.company_email) {
                                $('#err_company_email').text(errors.company_email[0]).show();
                            }
                            if (errors.company_phone) {
                                $('#err_company_phone').text(errors.company_phone[0]).show();
                            }
                            if (errors.company_logo) {
                                $('#err_company_logo').text(errors.company_logo[0]).show();
                            }
                        }
                        errMessage = xhr.responseJSON.message || errMessage;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed.message) errMessage = parsed.message;
                        } catch(e) {}
                    }

                    showLgToast(errMessage, true);
                }
            });
        };
    });
</script>
@endpush
