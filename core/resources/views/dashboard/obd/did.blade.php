@extends('dashboard.layouts.master')
@section('title', 'DID - LeadGER')

@push('after-styles')
<style>
/* ==========================================================================
   LeadGER Corporate Design System - DID Management
   ========================================================================== */

.lg-did-page {
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
    margin-bottom: 18px;
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
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    flex-shrink: 0;
}

.lg-header-icon-box.sm {
    width: 38px;
    height: 38px;
    border-radius: 10px;
}

.lg-header-text {
    display: flex;
    flex-direction: column;
}

.lg-page-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 20px;
    line-height: 1.25;
    letter-spacing: -0.01em;
}

.lg-page-subtitle {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

/* 2. Voucher & Top Action Card */
.lg-voucher-action-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}

.lg-voucher-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-voucher-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    flex-shrink: 0;
}

.lg-voucher-form-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lg-voucher-label {
    font-size: 13px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1;
}

.lg-voucher-inline-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-voucher-input {
    height: 38px;
    width: 290px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13.5px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
    font-family: inherit;
}

.lg-voucher-input::placeholder {
    color: #94A3B8;
    font-size: 13.5px;
}

.lg-voucher-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-btn-voucher-apply {
    height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(20, 100, 244, 0.25);
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-voucher-apply:hover {
    background: #0F52CC;
    box-shadow: 0 3px 8px rgba(20, 100, 244, 0.35);
}

.lg-action-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lg-btn-action-outline {
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    background: #FFFFFF;
    color: #334155 !important;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-action-outline:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A !important;
}

.lg-btn-action-solid {
    height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 3px rgba(20, 100, 244, 0.25);
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-action-solid:hover {
    background: #0F52CC;
    box-shadow: 0 3px 8px rgba(20, 100, 244, 0.35);
}

/* 3. Table Cards */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}

.lg-card-header-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}

.lg-card-title-group {
    display: flex;
    flex-direction: column;
}

.lg-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-card-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 2px 0 0 0;
}

/* Controls Toolbar */
.lg-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 16px;
}

.lg-dt-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}

.lg-dt-select {
    height: 34px;
    padding: 0 10px;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    cursor: pointer;
}

.lg-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.lg-dt-btn {
    height: 34px;
    padding: 0 12px;
    border-radius: 6px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}

.lg-dt-btn:hover {
    background: #F8FAFC;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.lg-search-wrap svg {
    position: absolute;
    left: 10px;
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-input {
    height: 34px;
    width: 190px;
    padding: 0 10px 0 32px;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    font-size: 12.5px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-search-input:focus {
    border-color: var(--lg-accent);
    width: 220px;
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* Data Table */
.lg-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table.lg-did-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
    margin: 0;
}

.table.lg-did-table thead th {
    background: #FFFFFF;
    border-bottom: 1px solid #F1F5F9;
    border-top: none;
    color: #64748B;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 14px;
    text-align: left;
    white-space: nowrap;
}

.table.lg-did-table tbody td {
    border-bottom: 1px solid #F8FAFC;
    border-top: none;
    padding: 12px 14px;
    vertical-align: middle;
    color: #334155;
    background: #FFFFFF;
}

.table.lg-did-table tbody tr:hover td {
    background-color: #FAFAFC;
}

/* Pills & Badges */
.lg-vmn-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: var(--lg-accent-hover);
    font-weight: 700;
    font-size: 12.5px;
    padding: 4px 10px;
    border-radius: 8px;
    white-space: nowrap;
}

.lg-pill-yes {
    display: inline-block;
    background: #DCFCE7;
    color: #16A34A;
    border: 1px solid #BBF7D0;
    border-radius: 9999px;
    padding: 2px 10px;
    font-size: 11.5px;
    font-weight: 600;
    text-align: center;
    min-width: 44px;
}

.lg-pill-no {
    display: inline-block;
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #E2E8F0;
    border-radius: 9999px;
    padding: 2px 10px;
    font-size: 11.5px;
    font-weight: 600;
    text-align: center;
    min-width: 44px;
}

.lg-pill-usage {
    display: inline-block;
    background: #F8FAFC;
    color: #334155;
    border: 1px solid #E2E8F0;
    border-radius: 9999px;
    padding: 2px 10px;
    font-size: 11.5px;
    font-weight: 500;
    white-space: nowrap;
}

.lg-group-tag {
    display: inline-block;
    background: #F8FAFC;
    color: #334155;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 500;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.lg-selected-badge {
    background: #EFF6FF;
    color: var(--lg-accent);
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #DBEAFE;
}

/* Actions Buttons */
.lg-actions-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}

.lg-action-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    text-decoration: none !important;
}

.lg-action-btn:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-action-btn.danger {
    border-color: #FECACA;
    color: #EF4444;
}

.lg-action-btn.danger:hover {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #F87171;
}

/* Pagination Bar */
.lg-pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #F1F5F9;
    font-size: 12.5px;
    color: #64748B;
}

.lg-pagination {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lg-page-btn {
    height: 30px;
    min-width: 30px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.lg-page-btn:hover:not(:disabled) {
    background: #F8FAFC;
    color: #0F172A;
    border-color: #CBD5E1;
}

.lg-page-btn.active {
    background: var(--lg-accent);
    color: #FFFFFF;
    border-color: var(--lg-accent);
}

.lg-page-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* Modals & Backdrop Stacking */
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

.modal.lg-modal.show,
.modal.lg-modal.in,
body > .modal.lg-modal.show,
body > .modal.lg-modal.in {
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

.modal.lg-modal .modal-dialog {
    margin: auto !important;
    z-index: 100001 !important;
    width: 100% !important;
    max-width: 520px;
    pointer-events: auto !important;
}

.modal.lg-modal#smart31DynamicModal .modal-dialog {
    max-width: 800px !important;
}

.modal.lg-modal .modal-content {
    background: #FFFFFF !important;
    border-radius: 16px !important;
    border: 1px solid #E2E8F0 !important;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(15, 23, 42, 0.05) !important;
    overflow: hidden !important;
    position: relative !important;
    z-index: 100002 !important;
}

.modal.lg-modal .modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    background: #FAFAFC;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal.lg-modal .modal-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.modal.lg-modal .modal-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin-top: 2px;
}

.modal.lg-modal .modal-body {
    padding: 24px;
    background: #FFFFFF;
}

.modal.lg-modal .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #F1F5F9;
    background: #FAFAFC;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.lg-form-group {
    margin-bottom: 16px;
}

.lg-form-group:last-child {
    margin-bottom: 0;
}

.lg-form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.lg-form-control {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13.5px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.lg-form-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-form-select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    box-sizing: border-box;
}

.lg-form-select:focus {
    border-color: var(--lg-accent);
}

/* Empty State */
.lg-empty-container {
    padding: 48px 24px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.lg-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94A3B8;
    margin-bottom: 12px;
}

.lg-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 4px;
}

.lg-empty-subtitle {
    font-size: 13px;
    color: #64748B;
    max-width: 420px;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 900px) {
    .lg-voucher-action-card {
        flex-direction: column;
        align-items: stretch;
    }
    .lg-voucher-left {
        width: 100%;
    }
    .lg-voucher-inline-form {
        flex-wrap: wrap;
        width: 100%;
    }
    .lg-voucher-input {
        width: 100%;
    }
    .lg-action-right {
        justify-content: flex-start;
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="lg-did-page">

    <!-- 1. DID PAGE HEADER CARD -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </div>
            <div class="lg-header-text">
                <h1 class="lg-page-title">DID</h1>
                <div class="lg-page-subtitle">Configure Virtual Numbers (VMN), channel routing, and Smart31 groups.</div>
            </div>
        </div>
    </div>

    <!-- 2. VOUCHER & TOP ACTION CARD -->
    <div class="lg-voucher-action-card">
        <!-- Left: Voucher Code Box & Input Form -->
        <div class="lg-voucher-left">
            <div class="lg-voucher-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/>
                    <path d="M13 5v2"/><path d="M13 11v2"/><path d="M13 17v2"/>
                </svg>
            </div>
            <div class="lg-voucher-form-wrap">
                <label class="lg-voucher-label">Voucher Code</label>
                <form action="{{ route('obdDidAdd') }}" method="POST" class="lg-voucher-inline-form">
                    @csrf
                    <input type="text" name="voucher_code" class="lg-voucher-input" placeholder="Enter Voucher Code" required autocomplete="off">
                    <button type="submit" class="lg-btn-voucher-apply" title="Redeem voucher code">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Apply</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Bulk Edit & + Add DID -->
        <div class="lg-action-right">
            <button type="button" class="lg-btn-action-outline" onclick="openBulkEditModal()" title="Bulk edit selected virtual numbers">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>Bulk Edit</span>
            </button>
            <button type="button" class="lg-btn-action-solid" data-toggle="modal" data-target="#addDidModal" title="Register a new virtual number">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>+ Add DID</span>
            </button>
        </div>
    </div>

    <!-- 3. DID NUMBERS DIRECTORY TABLE CARD -->
    <div class="lg-table-card">
        <!-- Card Header -->
        <div class="lg-card-header-bar">
            <div class="lg-header-icon-box sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </div>
            <div class="lg-card-title-group">
                <h2 class="lg-card-title">DID Numbers Directory</h2>
                <p class="lg-card-subtitle">Virtual numbers mapped to call channels and team groups.</p>
            </div>
            <div id="selectionSummaryBadge" style="display:none; margin-left: auto;">
                <span class="lg-selected-badge"><span id="selectedCountNum">0</span> selected</span>
            </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="lg-table-toolbar">
            <!-- Left: Show Entries -->
            <div class="lg-dt-controls">
                <span>Show</span>
                <select id="didLengthSelect" class="lg-dt-select" onchange="changePageLength(this.value)">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>

            <!-- Right: Export Buttons & Search -->
            <div class="lg-toolbar-right">
                <button type="button" class="lg-dt-btn" onclick="exportDidTable('copy')" title="Copy to clipboard">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>Copy</span>
                </button>
                <button type="button" class="lg-dt-btn" onclick="exportDidTable('excel')" title="Export to Excel">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel</span>
                </button>
                <button type="button" class="lg-dt-btn" onclick="exportDidTable('csv')" title="Export to CSV">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>CSV</span>
                </button>

                <div class="lg-search-wrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="didSearchInput" class="lg-search-input" placeholder="Search DID..." onkeyup="filterDidTable(this.value)" autocomplete="off">
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="lg-table-responsive">
            <table class="table lg-did-table" id="didMasterTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="checkAllDids" class="lg-checkbox" onchange="toggleSelectAll(this)" title="Select all">
                        </th>
                        <th>VIRTUAL NUMBER</th>
                        <th>DATE</th>
                        <th>CLICK2CALL</th>
                        <th>WEBRTC</th>
                        <th>GROUPS</th>
                        <th>ADDED BY</th>
                        <th style="width: 90px; text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="didTableBody">
                    @forelse($Did as $Row)
                        <tr class="did-data-row" data-id="{{ $Row->id }}">
                            <td style="text-align: center;">
                                <input type="checkbox" class="lg-checkbox did-row-checkbox" value="{{ $Row->id }}" onchange="updateSelectedCount()">
                            </td>
                            <td>
                                <span class="lg-vmn-pill">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    {{ $Row->virtual_number }}
                                </span>
                            </td>
                            <td>
                                <span style="white-space: nowrap; font-size: 12.5px; color: #475569;">
                                    {{ !empty($Row->created_at) ? \Carbon\Carbon::parse($Row->created_at)->format('Y-m-d H:i') : '—' }}
                                </span>
                            </td>
                            <td>
                                @if($Row->click2call)
                                    <span class="lg-pill-yes">Yes</span>
                                @else
                                    <span class="lg-pill-no">No</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($Row->webrtc) && ($Row->webrtc == 1 || $Row->webrtc === true))
                                    <span class="lg-pill-yes">Yes</span>
                                @else
                                    <span class="lg-pill-no">No</span>
                                @endif
                            </td>
                            <td>
                                <span class="lg-group-tag" title="{{ $Row->team_names ?: 'No Groups' }}">
                                    {{ !empty($Row->team_names) ? $Row->team_names : '—' }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 12.5px; color: #475569;">
                                    {{ !empty($Row->added_by) ? $Row->added_by : 'Admin' }}
                                </span>
                            </td>
                            <td>
                                <div class="lg-actions-cell" style="justify-content: center;">
                                    <!-- Edit -->
                                    <button type="button" class="lg-action-btn" data-toggle="modal" data-target="#editModal{{ $Row->id }}" title="Edit DID">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <!-- Delete -->
                                    <button type="button" class="lg-action-btn danger" data-toggle="modal" data-target="#deleteModal{{ $Row->id }}" title="Delete DID">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-state-row">
                            <td colspan="8">
                                <div class="lg-empty-container">
                                    <div class="lg-empty-icon">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                    </div>
                                    <div class="lg-empty-title">No DID numbers registered yet</div>
                                    <div class="lg-empty-subtitle">Redeem a voucher above or click "+ Add DID" to allocate virtual numbers.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="lg-pagination-bar">
            <div id="didEntriesInfo">
                Showing 1 to {{ count($Did) }} of {{ count($Did) }} entries
            </div>
            <div class="lg-pagination" id="didPaginationContainer">
                <button type="button" class="lg-page-btn" id="prevPageBtn" onclick="navigatePage(-1)" disabled>&laquo; Previous</button>
                <button type="button" class="lg-page-btn active" id="pageNumberBtn">1</button>
                <button type="button" class="lg-page-btn" id="nextPageBtn" onclick="navigatePage(1)" disabled>Next &raquo;</button>
            </div>
        </div>
    </div>

    <!-- 4. SMART31 VMN GROUPS CARD -->
    <div class="lg-table-card">
        <div class="lg-card-header-bar" style="margin-bottom: 16px;">
            <div class="lg-header-icon-box sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="lg-card-title-group" style="display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;">
                <h2 class="lg-card-title">Smart31 VMN Groups</h2>
                <span class="lg-card-subtitle">Assigned pools for Click-to-Call and WebRTC telephony integrations.</span>
            </div>
        </div>

        <div class="lg-table-responsive">
            <table class="table lg-did-table" id="smart31Table" style="min-width: 600px;">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">SMART31 NAME</th>
                        <th>ASSIGNED GROUPS</th>
                        <th style="width: 120px; text-align: right; padding-right: 20px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($smartGroups as $group)
                        @php
                            $groupName = !empty($group->group_name) ? $group->group_name : 'Smart31 Pool';
                            $assignedTeamNames = [];
                            if (!empty($group->assigned_groups)) {
                                foreach(explode(',', $group->assigned_groups) as $tid) {
                                    $tid = trim($tid);
                                    if (isset($teams[$tid])) {
                                        $assignedTeamNames[] = $teams[$tid]->name;
                                    } elseif (!empty($tid)) {
                                        $assignedTeamNames[] = $tid;
                                    }
                                }
                            }
                            $assignedText = !empty($assignedTeamNames) ? implode(', ', $assignedTeamNames) : '—';
                        @endphp
                        <tr>
                            <td style="padding-left: 20px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: var(--lg-accent); display: inline-block;"></span>
                                    <strong style="color: #0F172A; font-size: 13.5px;">{{ $groupName }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="lg-group-tag" title="{{ $assignedText }}">{{ $assignedText }}</span>
                            </td>
                            <td style="text-align: right; padding-right: 20px;">
                                <div class="lg-actions-cell" style="justify-content: flex-end;">
                                    <!-- View Details Modal Trigger -->
                                    <button type="button" class="lg-action-btn" onclick="openSmart31Details('{{ $group->group_name }}')" title="View Smart31 Numbers">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <!-- Edit Link -->
                                    <a href="{{ route('smart31GroupEdit', ['group' => $group->group_name]) }}" class="lg-action-btn" title="Edit Assigned Groups">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="lg-empty-container" style="padding: 32px 20px;">
                                    <div class="lg-empty-title" style="font-size: 14px;">No Smart31 Groups Available</div>
                                    <div class="lg-empty-subtitle">Smart31 VMN groups will appear automatically once synced via voucher redemption.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ROW MODALS (Rendered outside table for valid DOM & clean stacking) -->
@foreach($Did as $Row)
    <!-- Edit DID Modal -->
    <div class="modal fade lg-modal" id="editModal{{ $Row->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('obdDidUpdate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $Row->id }}">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Edit DID: {{ $Row->virtual_number }}</h5>
                            <div class="modal-subtitle">Update assigned team groups for this virtual number.</div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: none; font-size: 22px; color: #64748B;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="lg-form-group">
                            <label class="lg-form-label">Virtual Number</label>
                            <input type="text" class="lg-form-control" value="{{ $Row->virtual_number }}" disabled>
                        </div>
                        <div class="lg-form-group">
                            <label class="lg-form-label">Assigned Groups / Teams</label>
                            <select name="team_id[]" class="lg-form-select" multiple size="4" required>
                                @php $selectedTeams = !empty($Row->team_id) ? explode(',', $Row->team_id) : []; @endphp
                                @foreach($teams as $t)
                                    <option value="{{ $t->id }}" {{ in_array($t->id, $selectedTeams) ? 'selected' : '' }}>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="font-size: 11.5px; color: #64748B; margin-top: 4px; display: block;">Hold Ctrl / Cmd to select multiple groups.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="lg-dt-btn" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="lg-btn-voucher-apply">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete DID Modal -->
    <div class="modal fade lg-modal" id="deleteModal{{ $Row->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('backend.confirmation') }}</h5>
                        <div class="modal-subtitle">Delete Virtual Number Record</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: none; font-size: 22px; color: #64748B;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 28px 24px;">
                    <div style="width: 52px; height: 52px; border-radius: 12px; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Are you sure you want to delete this DID?</div>
                    <div style="font-size: 13px; color: #64748B;">Virtual Number <strong>{{ $Row->virtual_number }}</strong> will be removed from your active routing table.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lg-dt-btn" data-dismiss="modal">{{ __('backend.no') }}</button>
                    <a href="{{ route('obdDidDelete', $Row->id) }}" class="lg-action-btn danger" style="width: auto; height: 36px; padding: 0 16px; font-weight: 600; font-size: 13px;">
                        {{ __('backend.yes') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- ADD DID MODAL -->
<div class="modal fade lg-modal" id="addDidModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('obdDidStore') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Add DID</h5>
                        <div class="modal-subtitle">Register a new Virtual Number and assign channel usage.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: none; font-size: 22px; color: #64748B;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="lg-form-group">
                        <label class="lg-form-label">Virtual Number *</label>
                        <input type="text" name="virtual_number" class="lg-form-control" placeholder="e.g. 918045681200" required autocomplete="off">
                    </div>

                    <div class="lg-form-group">
                        <label class="lg-form-label">Assign Groups / Teams *</label>
                        <select name="team_id[]" class="lg-form-select" multiple size="4" required>
                            @foreach($teams as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <small style="font-size: 11.5px; color: #64748B; margin-top: 4px; display: block;">Hold Ctrl / Cmd to select multiple groups.</small>
                    </div>

                    <div class="lg-form-group">
                        <label class="lg-form-label">Channel Functionality *</label>
                        <select name="call_purpose" class="lg-form-control" required>
                            <option value="both" selected>OBD & Click2call</option>
                            <option value="obd">Only OBD</option>
                            <option value="click2call">Only Click2call</option>
                            <option value="webrtc">WebRTC</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lg-dt-btn" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="lg-btn-voucher-apply">Save DID</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BULK EDIT MODAL -->
<div class="modal fade lg-modal" id="bulkEditModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('obdDidBulkUpdate') }}" method="POST" id="bulkEditForm">
                @csrf
                <input type="hidden" name="ids" id="bulkSelectedIds" value="">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Bulk Edit DIDs</h5>
                        <div class="modal-subtitle">Update settings for selected virtual numbers in bulk.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: none; font-size: 22px; color: #64748B;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 16px;">
                        <span class="lg-selected-badge" id="bulkModalCountBadge">0 DIDs selected</span>
                    </div>

                    <div class="lg-form-group">
                        <label class="lg-form-label">Bulk Action</label>
                        <select name="bulk_action" id="bulkActionSelect" class="lg-form-control" onchange="toggleBulkFields(this.value)">
                            <option value="update_channels" selected>Update Channel Functionality</option>
                            <option value="update_teams">Update Group Assignments</option>
                            <option value="delete">Delete Selected Numbers</option>
                        </select>
                    </div>

                    <div id="bulkUpdateFields">
                        <div class="lg-form-group">
                            <label class="lg-form-label">Channel Functionality</label>
                            <select name="bulk_call_purpose" class="lg-form-control">
                                <option value="no_change" selected>— Keep Current Setting —</option>
                                <option value="obd">Only OBD</option>
                                <option value="click2call">Only Click2call</option>
                                <option value="both">OBD & Click2call</option>
                                <option value="webrtc">WebRTC</option>
                            </select>
                        </div>

                        <div class="lg-form-group">
                            <label class="lg-form-label">Assign Groups / Teams (Optional)</label>
                            <select name="team_id[]" class="lg-form-select" multiple size="4">
                                @foreach($teams as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <small style="font-size: 11.5px; color: #64748B; margin-top: 4px; display: block;">Leave unselected if you do not want to alter team assignments.</small>
                        </div>
                    </div>

                    <div id="bulkDeleteWarning" style="display:none; background: #FEF2F2; border: 1px solid #FECACA; padding: 12px 14px; border-radius: 8px; color: #991B1B; font-size: 13px;">
                        <strong>Warning:</strong> You are about to permanently delete the selected DID records. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lg-dt-btn" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="lg-btn-voucher-apply" id="bulkSubmitBtn">Apply Bulk Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SMART31 VIEW DETAILS MODAL -->
<div class="modal fade lg-modal" id="smart31DynamicModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="smart31ModalTitle">Smart31 Pool Details</h5>
                    <div class="modal-subtitle">Active virtual numbers assigned under this Smart31 group.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: none; font-size: 22px; color: #64748B;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smart31ModalBody">
                <div style="text-align: center; padding: 30px; color: #64748B;">
                    <div style="margin-top: 8px;">Loading Smart31 numbers...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lg-dt-btn" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('after-scripts')
<script>
/* ==========================================================================
   LeadGER DID Table Logic: Pagination, Search, Export & Bulk Edit
   ========================================================================== */

let didCurrentPage = 1;
let didPageLength = 10;
let didSearchTerm = '';

function getVisibleRows() {
    const rows = Array.from(document.querySelectorAll('#didTableBody tr.did-data-row'));
    if (!didSearchTerm) return rows;
    const term = didSearchTerm.toLowerCase();
    return rows.filter(row => row.innerText.toLowerCase().includes(term));
}

function renderDidPagination() {
    const allRows = Array.from(document.querySelectorAll('#didTableBody tr.did-data-row'));
    const visibleRows = getVisibleRows();
    const totalVisible = visibleRows.length;
    const totalPages = Math.ceil(totalVisible / didPageLength) || 1;

    if (didCurrentPage > totalPages) didCurrentPage = totalPages;
    if (didCurrentPage < 1) didCurrentPage = 1;

    const startIdx = (didCurrentPage - 1) * didPageLength;
    const endIdx = startIdx + didPageLength;

    allRows.forEach(row => row.style.display = 'none');
    visibleRows.slice(startIdx, endIdx).forEach(row => row.style.display = '');

    // Check empty state
    let emptyRow = document.querySelector('#didTableBody tr.empty-state-row');
    if (totalVisible === 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-state-row';
            emptyRow.innerHTML = `
                <td colspan="8">
                    <div class="lg-empty-container">
                        <div class="lg-empty-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <div class="lg-empty-title">No matching DID records found</div>
                        <div class="lg-empty-subtitle">Try adjusting your search query or clear the filter.</div>
                    </div>
                </td>
            `;
            document.querySelector('#didTableBody').appendChild(emptyRow);
        } else {
            emptyRow.style.display = '';
        }
    } else if (emptyRow && allRows.length > 0) {
        emptyRow.style.display = 'none';
    }

    // Update bottom info
    const infoEl = document.getElementById('didEntriesInfo');
    if (infoEl) {
        if (totalVisible === 0) {
            infoEl.innerText = 'Showing 0 to 0 of 0 entries';
        } else {
            const displayStart = startIdx + 1;
            const displayEnd = Math.min(endIdx, totalVisible);
            infoEl.innerText = `Showing ${displayStart} to ${displayEnd} of ${totalVisible} entries`;
        }
    }

    // Update page buttons
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const pageNumBtn = document.getElementById('pageNumberBtn');
    if (prevBtn) prevBtn.disabled = (didCurrentPage <= 1);
    if (nextBtn) nextBtn.disabled = (didCurrentPage >= totalPages);
    if (pageNumBtn) pageNumBtn.innerText = didCurrentPage;
}

function navigatePage(direction) {
    didCurrentPage += direction;
    renderDidPagination();
}

function changePageLength(newVal) {
    didPageLength = parseInt(newVal, 10);
    didCurrentPage = 1;
    renderDidPagination();
}

function filterDidTable(term) {
    didSearchTerm = term.trim();
    didCurrentPage = 1;
    renderDidPagination();
}

/* Checkbox Selection & Bulk Edit */
function toggleSelectAll(masterCb) {
    const checkboxes = document.querySelectorAll('.did-row-checkbox');
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = masterCb.checked;
        }
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = Array.from(document.querySelectorAll('.did-row-checkbox:checked'));
    const count = checked.length;
    const badge = document.getElementById('selectionSummaryBadge');
    const countNum = document.getElementById('selectedCountNum');
    
    if (badge && countNum) {
        countNum.innerText = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function openBulkEditModal() {
    const checked = Array.from(document.querySelectorAll('.did-row-checkbox:checked'));
    if (checked.length === 0) {
        alert('Please select at least one DID record using the checkboxes on the left.');
        return;
    }

    const selectedIds = checked.map(cb => cb.value);
    document.getElementById('bulkSelectedIds').value = selectedIds.join(',');
    document.getElementById('bulkModalCountBadge').innerText = `${selectedIds.length} DIDs selected`;
    $('#bulkEditModal').modal('show');
}

function toggleBulkFields(actionVal) {
    const updateFields = document.getElementById('bulkUpdateFields');
    const deleteWarning = document.getElementById('bulkDeleteWarning');
    const submitBtn = document.getElementById('bulkSubmitBtn');

    if (actionVal === 'delete') {
        updateFields.style.display = 'none';
        deleteWarning.style.display = 'block';
        submitBtn.className = 'lg-action-btn danger';
        submitBtn.style.width = 'auto';
        submitBtn.style.padding = '0 16px';
        submitBtn.innerText = 'Delete Selected DIDs';
    } else {
        updateFields.style.display = 'block';
        deleteWarning.style.display = 'none';
        submitBtn.className = 'lg-btn-voucher-apply';
        submitBtn.style.width = '';
        submitBtn.style.padding = '0 18px';
        submitBtn.innerText = 'Apply Bulk Update';
    }
}

/* Export Table Data */
function exportDidTable(format) {
    const headers = ['Virtual Number', 'Date', 'Click2Call', 'WebRTC', 'Groups', 'Added By'];
    const visibleRows = getVisibleRows();

    if (visibleRows.length === 0) {
        alert('No DID data available to export.');
        return;
    }

    const rowsData = [];
    rowsData.push(headers);

    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 7) return;
        const rowData = [
            cells[1].innerText.replace(/\s+/g, ' ').trim(),
            cells[2].innerText.replace(/\s+/g, ' ').trim(),
            cells[3].innerText.replace(/\s+/g, ' ').trim(),
            cells[4].innerText.replace(/\s+/g, ' ').trim(),
            cells[5].innerText.replace(/\s+/g, ' ').trim(),
            cells[6].innerText.replace(/\s+/g, ' ').trim()
        ];
        rowsData.push(rowData);
    });

    if (format === 'copy') {
        const textContent = rowsData.map(r => r.join('\t')).join('\n');
        navigator.clipboard.writeText(textContent).then(() => {
            alert('Table data copied to clipboard!');
        }).catch(() => {
            alert('Failed to copy to clipboard.');
        });
    } else if (format === 'csv') {
        const csvContent = rowsData.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `DID_Directory_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    } else if (format === 'excel') {
        const tableHtml = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="utf-8"></head>
            <body>
                <table>
                    ${rowsData.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}
                </table>
            </body>
            </html>
        `;
        const blob = new Blob([tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `DID_Directory_${new Date().toISOString().slice(0, 10)}.xls`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

/* Smart31 Dynamic Modal */
function openSmart31Details(groupName) {
    const modal = $('#smart31DynamicModal');
    const titleEl = document.getElementById('smart31ModalTitle');
    const bodyEl = document.getElementById('smart31ModalBody');

    titleEl.innerText = 'Smart31 Pool: ' + groupName;
    bodyEl.innerHTML = '<div style="text-align: center; padding: 30px; color: #64748B;">Loading virtual numbers...</div>';
    modal.modal('show');

    $.ajax({
        url: "{{ url('/smart31-group/details') }}/" + encodeURIComponent(groupName),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!Array.isArray(data) || data.length === 0) {
                bodyEl.innerHTML = '<div style="text-align: center; color: #64748B; padding: 20px;">No virtual numbers currently found in this Smart31 group.</div>';
                return;
            }

            let html = '<div class="lg-table-responsive"><table class="table lg-did-table" style="min-width: 500px;">';
            html += '<thead><tr>';
            html += '<th>VMN</th><th>Usage Date</th><th>OBD</th><th>Click2Call</th><th>WebRTC</th><th>Verified</th>';
            html += '</tr></thead><tbody>';

            data.forEach(function(num) {
                html += '<tr>';
                html += '<td><span class="lg-vmn-pill">' + (num.vmn || '—') + '</span></td>';
                html += '<td>' + (num.usage_date || '—') + '</td>';
                html += '<td>' + (num.obd ? '<span class="lg-pill-yes">Yes</span>' : '<span class="lg-pill-no">No</span>') + '</td>';
                html += '<td>' + (num.c2c ? '<span class="lg-pill-yes">Yes</span>' : '<span class="lg-pill-no">No</span>') + '</td>';
                html += '<td>' + (num.webrtc ? '<span class="lg-pill-yes">Yes</span>' : '<span class="lg-pill-no">No</span>') + '</td>';
                html += '<td>' + (num.verified ? '<span class="lg-pill-yes">Yes</span>' : '<span class="lg-pill-no">No</span>') + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            bodyEl.innerHTML = html;
        },
        error: function() {
            bodyEl.innerHTML = '<div style="text-align: center; color: #EF4444; padding: 20px;">Failed to load Smart31 group details. Please try again.</div>';
        }
    });
}

// Initial render & modal stacking setup
document.addEventListener('DOMContentLoaded', function() {
    $('.modal.lg-modal').appendTo('body');
    renderDidPagination();
});
</script>
@endpush
