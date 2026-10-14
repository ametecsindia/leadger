@extends('dashboard.layouts.master')
@section('title', 'Source')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Premium Corporate Sources Page Styling
   Scoped strictly under .sources-page container
   ========================================================================== */
.sources-page {
    font-family: var(--lg-font-sans);
    background-color: #F8FAFC;
    padding: 24px 32px;
    min-height: auto;
    color: #0F172A;
    box-sizing: border-box;
}

/* Page Header & Breadcrumbs */
.sources-page .lg-sources-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.sources-page .lg-sources-breadcrumbs {
    font-size: 13px;
    font-weight: 500;
    color: #94A3B8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sources-page .lg-sources-breadcrumbs a {
    color: #94A3B8;
    text-decoration: none;
    transition: color 0.15s ease;
}

.sources-page .lg-sources-breadcrumbs a:hover {
    color: var(--lg-accent);
}

.sources-page .lg-sources-breadcrumbs .sep {
    color: #CBD5E1;
}

.sources-page .lg-sources-breadcrumbs .active {
    color: #64748B;
    font-weight: 600;
}

.sources-page .lg-sources-title {
    font-size: 28px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.sources-page .lg-sources-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
    font-weight: 400;
}

/* Add Source Primary Action Button */
.sources-page .lg-sources-btn-add {
    height: 42px;
    padding: 0 20px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    white-space: nowrap;
}

.sources-page .lg-sources-btn-add:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}

/* Card Container */
.sources-page .lg-sources-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

/* Table Toolbar Controls */
.sources-page .lg-sources-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
}

.sources-page .lg-sources-entries {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #475569;
    font-weight: 500;
}

.sources-page .lg-sources-select-entries {
    height: 38px;
    padding: 0 28px 0 14px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    outline: none;
    cursor: pointer;
    appearance: none;
    transition: border-color 0.15s ease;
}

.sources-page .lg-sources-select-entries:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.sources-page .lg-sources-search-wrapper {
    position: relative;
    width: 280px;
}

.sources-page .lg-sources-search-wrapper svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.sources-page .lg-sources-search-input {
    width: 100%;
    height: 40px;
    padding: 0 14px 0 38px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.sources-page .lg-sources-search-input::placeholder {
    color: #94A3B8;
}

.sources-page .lg-sources-search-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

/* Data Table Styling */
.sources-page .lg-sources-table-responsive {
    overflow-x: auto;
    width: 100%;
}

.sources-page .lg-sources-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.sources-page .lg-sources-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 24px;
    border-top: 1px solid #F1F5F9;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.sources-page .lg-sources-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
    font-size: 14px;
}

.sources-page .lg-sources-table tbody tr {
    transition: background-color 0.15s ease;
}

.sources-page .lg-sources-table tbody tr:hover {
    background-color: #F8FAFC;
}

/* Source Cell (Icon Badge + Name) */
.sources-page .lg-source-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.sources-page .lg-source-icon-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sources-page .lg-source-name-text {
    font-weight: 700;
    color: #0F172A;
    font-size: 14px;
}

.sources-page .lg-source-desc-text {
    color: #64748B;
    font-size: 13.5px;
    margin: 0;
    line-height: 1.4;
    font-weight: 400;
}

.sources-page .lg-source-index {
    font-weight: 600;
    color: #64748B;
    font-size: 13.5px;
}

/* Row Options Buttons */
.sources-page .lg-sources-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
}

.sources-page .lg-sources-btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #EFF6FF;
    color: var(--lg-accent) !important;
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
    font-family: inherit;
}

.sources-page .lg-sources-btn-edit:hover {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
}

.sources-page .lg-sources-btn-more {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sources-page .lg-sources-btn-more:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

/* Footer Pagination */
.sources-page .lg-sources-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
    border-top: 1px solid #F1F5F9;
}

.sources-page .lg-sources-info {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.sources-page .lg-sources-info strong {
    color: #0F172A;
    font-weight: 600;
}

/* Custom Pagination Buttons */
.sources-page .pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.sources-page .pagination li a,
.sources-page .pagination li span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 34px;
    min-width: 34px;
    padding: 0 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.sources-page .pagination li.active span,
.sources-page .pagination li.active a {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}

.sources-page .pagination li a:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

.sources-page .pagination li.disabled span,
.sources-page .pagination li.disabled a {
    opacity: 0.5;
    cursor: not-allowed;
    background: #F8FAFC;
}

/* Empty State */
.sources-page .lg-sources-empty {
    padding: 60px 20px;
    text-align: center;
    color: #64748B;
}

.sources-page .lg-sources-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: #F1F5F9;
    color: #94A3B8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.sources-page .lg-sources-empty-title {
    font-size: 16px;
    font-weight: 600;
    color: #0F172A;
    margin-bottom: 6px;
}

.sources-page .lg-sources-empty-desc {
    font-size: 13.5px;
    color: #64748B;
    margin-bottom: 20px;
}

/* ==========================================================================
   LeadGER Edit Source Popup Modal & Toast Notification Styling
   ========================================================================== */
.lg-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}

.lg-modal-overlay.active {
    display: flex;
}

.lg-modal-container {
    background: #FFFFFF;
    border-radius: 16px;
    width: 560px;
    max-width: 90vw;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.22), 0 2px 6px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    box-sizing: border-box;
    animation: lgModalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes lgModalPop {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-8px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.lg-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.lg-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-modal-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-modal-title {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-modal-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 2px 0 0 0;
    font-weight: 400;
}

.lg-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748B;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    padding: 0;
}

.lg-modal-close:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.lg-modal-body {
    padding: 24px;
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 20px;
}

.lg-modal-form-group:last-child {
    margin-bottom: 0;
}

.lg-modal-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.lg-modal-required {
    color: #EF4444;
    font-weight: 700;
}

.lg-modal-input {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-modal-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-modal-input.error {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-modal-textarea {
    height: 115px;
    padding: 12px 16px;
    resize: vertical;
    line-height: 1.5;
}

.lg-modal-error-message {
    font-size: 12.5px;
    color: #EF4444;
    font-weight: 500;
    margin-top: 2px;
}

.lg-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.lg-modal-btn-cancel {
    height: 40px;
    padding: 0 18px;
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: inherit;
}

.lg-modal-btn-cancel:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-modal-btn-submit {
    height: 40px;
    padding: 0 22px;
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
    gap: 8px;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    font-family: inherit;
}

.lg-modal-btn-submit:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
}

.lg-modal-btn-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    box-shadow: none;
}

.lg-spin {
    animation: lgSpinAnim 0.8s linear infinite;
}
@keyframes lgSpinAnim {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Toast Notifications */
.lg-sources-toast {
    position: fixed;
    top: 24px;
    right: 28px;
    z-index: 100001;
    background: #FFFFFF;
    border-left: 4px solid #10B981;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 4px 6px -2px rgba(15, 23, 42, 0.04);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-top: 1px solid #E2E8F0;
    border-right: 1px solid #E2E8F0;
    border-bottom: 1px solid #E2E8F0;
    animation: lgToastSlideIn 0.25s ease-out;
}

@keyframes lgToastSlideIn {
    from { transform: translateX(40px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.lg-sources-toast-content {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0F172A;
    font-size: 13.5px;
    font-weight: 500;
}

.lg-sources-toast-content svg {
    color: #10B981;
    flex-shrink: 0;
}

.lg-sources-toast-close {
    background: transparent;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    padding: 2px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
}

.lg-sources-toast-close:hover {
    color: #0F172A;
    background: #F1F5F9;
}

/* Flash alerts */
.lg-sources-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 13.5px;
    font-weight: 500;
}

.lg-sources-alert.success {
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
}

.lg-sources-alert.error {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
}
</style>
@endpush

@php
if (!function_exists('lgGetSourceBadgeDetails')) {
    function lgGetSourceBadgeDetails($name) {
        $n = strtolower($name);
        if (str_contains($n, 'direct') || str_contains($n, 'door')) {
            return [
                'bg' => '#EFF6FF',
                'color' => '#1464F4',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>'
            ];
        } elseif (str_contains($n, 'sms')) {
            return [
                'bg' => '#ECFDF5',
                'color' => '#10B981',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="13" y2="13"/></svg>'
            ];
        } elseif (str_contains($n, 'whatsapp')) {
            return [
                'bg' => '#F5F3FF',
                'color' => '#8B5CF6',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'
            ];
        } elseif (str_contains($n, 'google') || str_contains($n, 'adwords') || str_contains($n, 'ad')) {
            return [
                'bg' => '#FFF7ED',
                'color' => '#EA580C',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
            ];
        } elseif (str_contains($n, 'facebook') || str_contains($n, 'fb') || str_contains($n, 'social')) {
            return [
                'bg' => '#EFF6FF',
                'color' => '#1877F2',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>'
            ];
        } else {
            return [
                'bg' => '#EFF6FF',
                'color' => '#1464F4',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>'
            ];
        }
    }
}
@endphp

@section('content')
<div class="sources-page">

    <!-- 1. PAGE HEADER & BREADCRUMBS -->
    <div class="lg-sources-header">
        <div>
            <div class="lg-sources-breadcrumbs">
                <a href="{{ route('adminHome') }}">Administration</a>
                <span class="sep">/</span>
                <span class="active">Sources</span>
            </div>
            <h1 class="lg-sources-title">Source</h1>
            <p class="lg-sources-subtitle">Manage and organize your lead sources.</p>
        </div>

        @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
            <div>
                <a class="lg-sources-btn-add" href="{{ route('sourcesCreate') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Add a new source</span>
                </a>
            </div>
        @endif
    </div>

    {{-- Flash Alerts --}}
    @if(session('doneMessage'))
        <div class="lg-sources-alert success" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span>{{ session('doneMessage') }}</span>
        </div>
    @endif
    @if(session('errorMessage'))
        <div class="lg-sources-alert error" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ session('errorMessage') }}</span>
        </div>
    @endif

    {{-- Floating Toast for AJAX updates --}}
    <div id="lgSourceToast" class="lg-sources-toast" style="display: none;">
        <div class="lg-sources-toast-content">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span id="lgSourceToastText">Source updated successfully.</span>
        </div>
        <button type="button" class="lg-sources-toast-close" onclick="lgHideSourceToast()" title="Dismiss">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- 2. MAIN CARD CONTAINER -->
    <div class="lg-sources-card">

        <!-- TOOLBAR: SHOW ENTRIES & SEARCH -->
        <div class="lg-sources-toolbar">
            <div class="lg-sources-entries">
                <span>Show</span>
                <select id="lgEntriesSelect" class="lg-sources-select-entries" onchange="lgChangeEntries(this.value)">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-sources-search-wrapper">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="lgSourcesSearch" class="lg-sources-search-input" placeholder="Search sources..." onkeyup="lgFilterSources()">
            </div>
        </div>

        <!-- TABLE SECTION -->
        @if($Sources->total() > 0)
            <div class="lg-sources-table-responsive">
                <table class="lg-sources-table" id="lgSourcesTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">SOURCE NAME</th>
                            <th style="width: 45%;">DESCRIPTION</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                                <th class="text-right" style="width: 15%; text-align: right; padding-right: 24px;">OPTIONS</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($Sources as $Source)
                            @php
                                $badgeData = lgGetSourceBadgeDetails($Source->name);
                            @endphp
                            <tr class="lg-source-row" id="lgSourceRow_{{ $Source->id }}">
                                <td>
                                    <span class="lg-source-index">{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    <div class="lg-source-cell">
                                        <div class="lg-source-icon-badge" id="lgSourceBadge_{{ $Source->id }}" style="background-color: {{ $badgeData['bg'] }}; color: {{ $badgeData['color'] }};">
                                            {!! $badgeData['svg'] !!}
                                        </div>
                                        <span class="lg-source-name-text" id="lgSourceName_{{ $Source->id }}">{!! $Source->name !!}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="lg-source-desc-text" id="lgSourceDesc_{{ $Source->id }}">{!! $Source->description ? $Source->description : '—' !!}</span>
                                </td>
                                @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                                    <td class="text-right" style="text-align: right; padding-right: 24px;">
                                        <div class="lg-sources-actions">
                                            <button type="button" 
                                                    class="lg-sources-btn-edit" 
                                                    title="Edit Source"
                                                    data-id="{{ $Source->id }}"
                                                    data-name="{{ $Source->name }}"
                                                    data-desc="{{ $Source->description ?? '' }}"
                                                    onclick="lgOpenEditSourceModal(this)">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" class="lg-sources-btn-more" title="More Options">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                                    <circle cx="12" cy="5" r="2"/>
                                                    <circle cx="12" cy="12" r="2"/>
                                                    <circle cx="12" cy="19" r="2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- FOOTER & PAGINATION -->
            <div class="lg-sources-footer">
                <div class="lg-sources-info">
                    Showing <strong>{{ $Sources->firstItem() }}</strong> to <strong>{{ $Sources->lastItem() }}</strong> of <strong>{{ $Sources->total() }}</strong> entries
                </div>
                <div class="lg-sources-pagination-wrapper">
                    {!! $Sources->links() !!}
                </div>
            </div>

        @else
            <!-- EMPTY STATE -->
            <div class="lg-sources-empty">
                <div class="lg-sources-empty-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div class="lg-sources-empty-title">No sources found</div>
                <div class="lg-sources-empty-desc">Create your first lead source to start tracking incoming leads effectively.</div>
                @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                    <a class="lg-sources-btn-add" href="{{ route('sourcesCreate') }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Add a new source</span>
                    </a>
                @endif
            </div>
        @endif

    </div>

    <!-- EDIT SOURCE POPUP MODAL -->
    <div id="lgEditSourceModal" class="lg-modal-overlay" onclick="lgHandleBackdropClick(event)">
        <div class="lg-modal-container" onclick="event.stopPropagation()">
            
            <!-- Modal Header -->
            <div class="lg-modal-header">
                <div class="lg-modal-header-left">
                    <div class="lg-modal-header-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="lg-modal-title">Edit Source</h3>
                        <p class="lg-modal-subtitle">Update source information</p>
                    </div>
                </div>
                <button type="button" class="lg-modal-close" onclick="lgCloseEditSourceModal()" title="Close (Esc)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="lgEditSourceForm" method="POST" action="" onsubmit="lgSubmitEditSource(event)">
                @csrf
                <input type="hidden" id="lgEditSourceId" name="id" value="">

                <div class="lg-modal-body">
                    <!-- Source Name -->
                    <div class="lg-modal-form-group">
                        <label class="lg-modal-label" for="lgModalSourceName">
                            Source Name <span class="lg-modal-required">*</span>
                        </label>
                        <input type="text" 
                               id="lgModalSourceName" 
                               name="name" 
                               class="lg-modal-input" 
                               placeholder="Source Name" 
                               required 
                               autocomplete="off">
                        <div id="lgModalNameError" class="lg-modal-error-message" style="display: none;"></div>
                    </div>

                    <!-- Description -->
                    <div class="lg-modal-form-group">
                        <label class="lg-modal-label" for="lgModalSourceDesc">
                            Description
                        </label>
                        <textarea id="lgModalSourceDesc" 
                                  name="description" 
                                  class="lg-modal-input lg-modal-textarea" 
                                  placeholder="Description..."></textarea>
                        <div id="lgModalDescError" class="lg-modal-error-message" style="display: none;"></div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="lg-modal-footer">
                    <button type="button" class="lg-modal-btn-cancel" onclick="lgCloseEditSourceModal()">
                        Cancel
                    </button>
                    <button type="submit" id="lgModalSubmitBtn" class="lg-modal-btn-submit">
                        <span class="lg-modal-btn-text">Update Source</span>
                        <span class="lg-modal-btn-spinner" style="display: none;">
                            <svg class="lg-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
                            </svg>
                            <span>Updating...</span>
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push("after-scripts")
<script type="text/javascript">
let currentEditId = null;
let toastTimeout = null;

function lgOpenEditSourceModal(elOrId, name, desc) {
    let id = elOrId;
    let sourceName = name;
    let sourceDesc = desc;

    if (typeof elOrId === 'object' && elOrId !== null) {
        id = elOrId.getAttribute('data-id');
        sourceName = elOrId.getAttribute('data-name');
        sourceDesc = elOrId.getAttribute('data-desc');
    }

    currentEditId = id;

    const modal = document.getElementById('lgEditSourceModal');
    const form = document.getElementById('lgEditSourceForm');
    const idInput = document.getElementById('lgEditSourceId');
    const nameInput = document.getElementById('lgModalSourceName');
    const descInput = document.getElementById('lgModalSourceDesc');
    const nameError = document.getElementById('lgModalNameError');
    const descError = document.getElementById('lgModalDescError');

    if (!modal || !form) return;

    // Reset error states
    nameError.style.display = 'none';
    nameError.textContent = '';
    descError.style.display = 'none';
    descError.textContent = '';
    nameInput.classList.remove('error');
    descInput.classList.remove('error');

    // Reset submit button state
    lgSetSubmitLoading(false);

    // Populate data
    idInput.value = id;
    nameInput.value = sourceName || '';
    descInput.value = sourceDesc || '';

    // Set form action: /admin/sources/{id}/update
    form.action = "{{ url('admin/sources') }}/" + id + "/update";

    // Show modal with animation
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Focus on name input
    setTimeout(function() {
        nameInput.focus();
        nameInput.select();
    }, 120);
}

function lgCloseEditSourceModal() {
    const modal = document.getElementById('lgEditSourceModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Clean URL if opened via direct edit URL
    if (window.location.pathname.indexOf('/edit') > -1 || window.location.search.indexOf('edit=') > -1) {
        window.history.replaceState({}, document.title, "{{ route('sources') }}");
    }
}

function lgHandleBackdropClick(event) {
    if (event.target === document.getElementById('lgEditSourceModal')) {
        lgCloseEditSourceModal();
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('lgEditSourceModal');
        if (modal && modal.classList.contains('active')) {
            lgCloseEditSourceModal();
        }
    }
});

function lgSetSubmitLoading(loading) {
    const btn = document.getElementById('lgModalSubmitBtn');
    if (!btn) return;
    const textSpan = btn.querySelector('.lg-modal-btn-text');
    const spinnerSpan = btn.querySelector('.lg-modal-btn-spinner');

    if (loading) {
        btn.disabled = true;
        if (textSpan) textSpan.style.display = 'none';
        if (spinnerSpan) spinnerSpan.style.display = 'inline-flex';
    } else {
        btn.disabled = false;
        if (textSpan) textSpan.style.display = 'inline';
        if (spinnerSpan) spinnerSpan.style.display = 'none';
    }
}

function lgSubmitEditSource(event) {
    event.preventDefault();

    const form = document.getElementById('lgEditSourceForm');
    const nameInput = document.getElementById('lgModalSourceName');
    const descInput = document.getElementById('lgModalSourceDesc');
    const nameError = document.getElementById('lgModalNameError');
    const descError = document.getElementById('lgModalDescError');

    // Reset previous errors
    nameError.style.display = 'none';
    nameError.textContent = '';
    descError.style.display = 'none';
    descError.textContent = '';
    nameInput.classList.remove('error');

    const nameVal = nameInput.value.trim();
    if (!nameVal) {
        nameError.textContent = 'Source name is required.';
        nameError.style.display = 'block';
        nameInput.classList.add('error');
        nameInput.focus();
        return;
    }

    lgSetSubmitLoading(true);

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async function(response) {
        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (response.status === 422 && data && data.errors) {
            // Validation errors
            lgSetSubmitLoading(false);
            if (data.errors.name) {
                nameError.textContent = data.errors.name[0];
                nameError.style.display = 'block';
                nameInput.classList.add('error');
                nameInput.focus();
            }
            if (data.errors.description) {
                descError.textContent = data.errors.description[0];
                descError.style.display = 'block';
                descInput.classList.add('error');
            }
            return;
        }

        if (!response.ok) {
            // Server error: fallback to regular submit
            form.submit();
            return;
        }

        // Success!
        lgSetSubmitLoading(false);
        lgCloseEditSourceModal();

        const updatedName = data?.source?.name || nameVal;
        const updatedDesc = data?.source?.description || descInput.value;
        const sourceId = currentEditId;

        // Update table row if exists
        const nameEl = document.getElementById('lgSourceName_' + sourceId);
        if (nameEl) nameEl.textContent = updatedName;

        const descEl = document.getElementById('lgSourceDesc_' + sourceId);
        if (descEl) descEl.textContent = updatedDesc ? updatedDesc : '—';

        // Update badge icon if name changes
        const badgeEl = document.getElementById('lgSourceBadge_' + sourceId);
        if (badgeEl) {
            lgUpdateSourceBadge(badgeEl, updatedName);
        }

        // Update edit button data attributes
        const editBtn = document.querySelector('.lg-sources-btn-edit[data-id="' + sourceId + '"]');
        if (editBtn) {
            editBtn.setAttribute('data-name', updatedName);
            editBtn.setAttribute('data-desc', updatedDesc);
        }

        // Show toast
        const msg = data?.message || 'Source updated successfully.';
        lgShowSourceToast(msg);
    })
    .catch(function(err) {
        console.error('Update source error:', err);
        // Fallback to standard form submission
        form.submit();
    });
}

function lgUpdateSourceBadge(el, name) {
    const n = (name || '').toLowerCase();
    let bg = '#EFF6FF';
    let color = '#1464F4';
    let svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>';

    if (n.includes('direct') || n.includes('door')) {
        bg = '#EFF6FF';
        color = '#1464F4';
        svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>';
    } else if (n.includes('sms')) {
        bg = '#ECFDF5';
        color = '#10B981';
        svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="13" y2="13"/></svg>';
    } else if (n.includes('whatsapp')) {
        bg = '#F5F3FF';
        color = '#8B5CF6';
        svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
    } else if (n.includes('google') || n.includes('adwords') || n.includes('ad')) {
        bg = '#FFF7ED';
        color = '#EA580C';
        svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    } else if (n.includes('facebook') || n.includes('fb') || n.includes('social')) {
        bg = '#EFF6FF';
        color = '#1877F2';
        svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>';
    }

    el.style.backgroundColor = bg;
    el.style.color = color;
    el.innerHTML = svg;
}

function lgShowSourceToast(msg) {
    const toast = document.getElementById('lgSourceToast');
    const textEl = document.getElementById('lgSourceToastText');
    if (!toast || !textEl) return;

    textEl.textContent = msg;
    toast.style.display = 'flex';

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(function() {
        lgHideSourceToast();
    }, 4000);
}

function lgHideSourceToast() {
    const toast = document.getElementById('lgSourceToast');
    if (toast) toast.style.display = 'none';
}

function lgFilterSources() {
    const input = document.getElementById('lgSourcesSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('lgSourcesTable');
    if (!table) return;
    
    const trs = table.getElementsByClassName('lg-source-row');

    for (let i = 0; i < trs.length; i++) {
        const rowText = trs[i].textContent || trs[i].innerText;
        if (rowText.toLowerCase().indexOf(filter) > -1) {
            trs[i].style.display = '';
        } else {
            trs[i].style.display = 'none';
        }
    }
}

function lgChangeEntries(val) {
    const table = document.getElementById('lgSourcesTable');
    if (!table) return;
    const trs = table.getElementsByClassName('lg-source-row');
    const limit = parseInt(val);

    for (let i = 0; i < trs.length; i++) {
        if (i < limit) {
            trs[i].style.display = '';
        } else {
            trs[i].style.display = 'none';
        }
    }
}

@if(isset($editSource) && !empty($editSource))
document.addEventListener('DOMContentLoaded', function() {
    lgOpenEditSourceModal('{{ $editSource->id }}', {!! json_encode($editSource->name) !!}, {!! json_encode($editSource->description ?? '') !!});
});
@elseif(request('edit'))
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.querySelector('.lg-sources-btn-edit[data-id="{{ request('edit') }}"]');
    if (editBtn) {
        lgOpenEditSourceModal(editBtn);
    }
});
@endif
</script>
@endpush
