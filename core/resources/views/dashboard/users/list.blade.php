@extends('dashboard.layouts.master')
@section('title', 'Staff')

@section('content')
<style>
/* LeadGER Corporate Users / Teams & Add User Modal Styling */
.lg-staff-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px 30px;
    box-sizing: border-box;
    min-height: calc(100vh - 72px);
}

/* 1. Page Header with Icon & Breadcrumbs */
.lg-c2c-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
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
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    flex-shrink: 0;
}

.lg-header-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-breadcrumb-nav a {
    color: #64748B;
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-breadcrumb-nav a:hover {
    color: var(--lg-accent);
}

/* 2. Main Settings Card */
.lg-settings-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    margin-bottom: 28px;
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
    color: var(--lg-accent);
    font-weight: 700;
    border-bottom-color: var(--lg-accent);
}

/* 4. Form / List Body */
.lg-settings-body {
    padding: 28px 32px 36px 32px;
}

/* Table Search & Controls Toolbar */
.lg-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 22px;
}

.lg-search-box {
    position: relative;
    width: 320px;
    max-width: 100%;
}

.lg-search-box svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-input-field {
    width: 100%;
    height: 40px;
    padding: 0 14px 0 40px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-search-input-field:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-toolbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.lg-filters-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lg-filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    margin-right: 6px;
}

.lg-filter-select {
    height: 40px;
    padding: 0 32px 0 14px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    color: #0F172A;
    font-weight: 500;
    cursor: pointer;
    appearance: none;
    outline: none;
}

.lg-filter-select:focus {
    border-color: var(--lg-accent);
}

/* Primary "+ Add User" Button */
.lg-btn-add-user-top {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--lg-accent) !important;
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    height: 40px;
    padding: 0 20px;
    border-radius: 9px;
    border: none !important;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-add-user-top:hover {
    background: #0F52CC !important;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

/* Staff Table Styling */
.lg-table-wrapper {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #FFFFFF;
}

.lg-staff-table {
    width: 100%;
    border-collapse: collapse;
}

.lg-staff-table th {
    background: #F8FAFC;
    color: #0F172A;
    font-weight: 700;
    font-size: 13px;
    padding: 14px 18px;
    border-bottom: 1px solid #E2E8F0;
    text-align: left;
    white-space: nowrap;
}

.lg-staff-table td {
    padding: 14px 18px;
    vertical-align: middle;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    background: #FFFFFF;
    font-size: 13.5px;
}

.lg-staff-table tr:hover td {
    background: #F8FAFC;
}

.lg-name-flex {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #EFF6FF;
    color: var(--lg-accent);
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #BFDBFE;
    flex-shrink: 0;
}

.lg-name-bold {
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
}

/* Role Badges */
.lg-role-badge {
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}

.lg-role-badge.admin { background: #EFF6FF; color: var(--lg-accent-hover); border: 1px solid #BFDBFE; }
.lg-role-badge.telecaller { background: #F5F3FF; color: #6D28D9; border: 1px solid #DDD6FE; }
.lg-role-badge.manager { background: #ECFEFF; color: #0991B1; border: 1px solid #A5F3FC; }
.lg-role-badge.default { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }

/* Status Badges */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}

.lg-status-pill.active { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
.lg-status-pill.active .lg-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #10B981; }
.lg-status-pill.inactive { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
.lg-status-pill.inactive .lg-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #EF4444; }

/* Action Icon Buttons */
.lg-action-icon-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-action-icon-btn:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: var(--lg-accent);
}

.lg-action-icon-btn.delete:hover {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #EF4444;
}

/* Footer & Pagination */
.lg-table-footer-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
}

.lg-records-summary {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.lg-export-buttons-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.lg-export-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 12.5px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-export-btn:hover {
    background: #0F52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.3);
}

/* ==================================================
   LEADGER MODERN MODAL STYLES (ADD & EDIT STAFF)
================================================== */
.lg-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px 20px;
    box-sizing: border-box;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.lg-modal-overlay.active {
    display: flex !important;
    opacity: 1;
}

.lg-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 740px;
    max-height: 90vh;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22), 0 4px 12px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: lgModalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    margin: auto;
}

@keyframes lgModalSlideUp {
    from {
        opacity: 0;
        transform: scale(0.97) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Modal Header Bar */
.lg-modal-header-bar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.lg-modal-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-modal-header-title {
    font-size: 19px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-modal-header-sub {
    font-size: 13px;
    color: #64748B;
    margin: 2px 0 0 0;
    font-weight: 400;
}

.lg-modal-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.lg-modal-close-btn:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

/* Modal Form Body */
.lg-modal-body {
    padding: 24px 28px;
    background: #FFFFFF;
    overflow-y: auto;
    max-height: calc(90vh - 145px);
    box-sizing: border-box;
}

.lg-modal-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 24px;
}

@media (max-width: 640px) {
    .lg-modal-form-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lg-modal-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

.lg-req {
    color: #EF4444;
    font-weight: 700;
}

.lg-modal-control {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-modal-control::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

.lg-modal-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-modal-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 36px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 14px center;
    cursor: pointer;
}

/* Custom Single-Select Dropdown Styling (LeadGER Reusable Dropdown) */
.lg-custom-dropdown-wrap {
    position: relative;
    width: 100%;
}

.lg-custom-dropdown-wrap.open {
    z-index: 1060;
}

.lg-modal-form-group.dropdown-open,
.lg-modal-form-group:has(.lg-custom-dropdown-wrap.open),
.lg-field-group.dropdown-open,
.lg-field-group:has(.lg-custom-dropdown-wrap.open) {
    position: relative;
    z-index: 1060;
}

.lg-custom-dropdown-trigger {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
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
    z-index: 1070;
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
    box-sizing: border-box;
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

/* Multi-Team Select Dropdown */
.lg-multi-team-wrap {
    position: relative;
    width: 100%;
}

.lg-multi-team-wrap.open {
    z-index: 1060;
}

.lg-modal-form-group:has(.lg-multi-team-wrap.open),
.lg-modal-form-group.multi-open {
    position: relative;
    z-index: 1060;
}

.lg-multi-team-trigger {
    min-height: 42px;
    width: 100%;
    padding: 5px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.lg-multi-team-trigger:hover,
.lg-multi-team-trigger:focus,
.lg-multi-team-wrap.open .lg-multi-team-trigger {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-multi-team-trigger.error {
    border-color: #EF4444 !important;
}

.lg-multi-team-tags {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    flex: 1;
}

.lg-multi-placeholder {
    color: #94A3B8;
    font-size: 13.5px;
    font-weight: 400;
}

.lg-team-tag {
    background: #EFF6FF;
    color: var(--lg-accent-hover);
    border: 1px solid #BFDBFE;
    border-radius: 6px;
    padding: 2px 7px;
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    line-height: 1.3;
}

.lg-team-tag-x {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    cursor: pointer;
    color: var(--lg-accent);
    font-size: 13px;
    font-weight: 700;
    transition: all 0.15s ease;
}

.lg-team-tag-x:hover {
    background: #DBEAFE;
    color: var(--lg-accent-hover);
}

.lg-multi-team-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    z-index: 1070;
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
    box-sizing: border-box;
}

.lg-multi-team-wrap.open .lg-multi-team-menu {
    display: block;
}

.lg-multi-team-wrap.open .lg-custom-dropdown-arrow {
    transform: rotate(180deg);
    stroke: var(--lg-accent);
}

.lg-multi-team-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.12s ease;
    user-select: none;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
}

.lg-multi-team-option:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.lg-multi-team-option.selected {
    background: #F0F7FF;
    color: var(--lg-accent);
    font-weight: 600;
}

.lg-team-chk-custom {
    width: 16px;
    height: 16px;
    border: 1.5px solid #CBD5E1;
    border-radius: 4px;
    background: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    flex-shrink: 0;
    box-sizing: border-box;
}

.lg-multi-team-option.selected .lg-team-chk-custom {
    background: var(--lg-accent);
    border-color: var(--lg-accent);
}

.lg-multi-team-option.selected .lg-team-chk-custom::after {
    content: "";
    width: 4px;
    height: 8px;
    border: solid #FFFFFF;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-top: -2px;
}

/* Calling Method Section */
.lg-modal-calling-section {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
}

.lg-calling-options-flex {
    display: flex;
    align-items: center;
    gap: 28px;
    margin-top: 8px;
}

/* Custom Square Checkbox for Calling Method */
.lg-custom-checkbox-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    margin: 0;
}
.lg-custom-checkbox-label input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}
.lg-custom-checkmark {
    width: 17px;
    height: 17px;
    background-color: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 4px;
    display: inline-block;
    position: relative;
    box-sizing: border-box;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.lg-custom-checkbox-label:hover input ~ .lg-custom-checkmark {
    border-color: var(--lg-accent);
}
.lg-custom-checkbox-label input:checked ~ .lg-custom-checkmark {
    background-color: var(--lg-accent);
    border-color: var(--lg-accent);
}
.lg-custom-checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 5px;
    top: 2px;
    width: 4px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.lg-custom-checkbox-label input:checked ~ .lg-custom-checkmark:after {
    display: block;
}

/* Modal Footer Actions */
.lg-modal-footer-bar {
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}

.lg-btn-modal-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 22px;
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-modal-cancel:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-btn-modal-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    padding: 0 24px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-modal-submit:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}
</style>

<div class="lg-staff-wrapper">

    <!-- 1. PAGE HEADER -->
    <div class="lg-c2c-header-row">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </div>
            <div class="lg-header-text-group">
                <h1 class="lg-title-main">Staff</h1>
                <div class="lg-breadcrumb-nav">
                    <a href="{{ route('adminHome') }}">Settings</a>
                    <span>/</span>
                    <span>Staff</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 20px;">
            {{ session('doneMessage') }}
        </div>
    @endif
    @if(session('errorMessage') || (isset($errors) && $errors->any()))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 20px;">
            {{ session('errorMessage') ?: 'Not saved: ' . $errors->first() }}
        </div>
    @endif

    <!-- 2. MAIN SETTINGS CARD WITH HORIZONTAL TABS -->
    <div class="lg-settings-card">
        
        

        <!-- TAB BODY CONTENT -->
        <div class="lg-settings-body">
            
            <!-- TABLE CONTROLS & ADD USER BUTTON ROW -->
            <div class="lg-table-toolbar">
                <div class="lg-search-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="lgStaffSearch" class="lg-search-input-field" placeholder="Search staff by name or email…">
                </div>

                <div class="lg-toolbar-right">


                    @if(\App\Support\RoleAccess::allows(Auth::user(), 'people.staff.create'))
                        <button type="button" class="lg-btn-add-user-top" onclick="lgOpenAddStaffModal()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Add Staff</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- USERS TABLE CONTAINER -->
            @if(count($Users) == 0)
                <div style="text-align: center; padding: 40px; color: #64748B;">
                    {{ __('backend.noData') }}
                </div>
            @else
                {{ Form::open(['route' => 'usersUpdateAll', 'method' => 'post', 'id' => 'lgBulkForm']) }}
                
                <div class="lg-table-wrapper">
                    <table class="lg-staff-table" id="lgStaffTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Team</th>
                                <th>Work Email</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Users as $User)
                                @php
                                    $roleName = @$User->permissionsGroup->name ?? 'Telecaller';
                                    $roleClass = 'default';
                                    $lowerRole = strtolower($roleName);
                                    if (strpos($lowerRole, 'admin') !== false) $roleClass = 'admin';
                                    elseif (strpos($lowerRole, 'tele') !== false || strpos($lowerRole, 'caller') !== false) $roleClass = 'telecaller';
                                    elseif (strpos($lowerRole, 'manager') !== false) $roleClass = 'manager';

                                    $initials = strtoupper(substr($User->name ?? 'U', 0, 2));

                                    // Team Resolution (Multiple Teams)
                                    $userAssignedTeams = collect();
                                    if (!empty($User->team_id)) {
                                        $tIds = array_filter(explode(',', (string)$User->team_id), 'strlen');
                                        $userAssignedTeams = $Teams->whereIn('id', $tIds);
                                    }
                                @endphp
                                <tr class="lg-staff-row" 
                                    data-name="{{ strtolower($User->name ?? '') }}" 
                                    data-email="{{ strtolower($User->email ?? '') }}" 
                                    data-role="{{ strtolower($roleName) }}">
                                    {{-- the user's ID: given once and never reused (a deleted user's number is not taken again) --}}
                                    <td style="font-weight: 600; color: #475569; font-variant-numeric: tabular-nums;">{{ $User->id }}</td>
                                    <td>
                                        <div class="lg-name-flex">
                                            <div class="lg-user-avatar">
                                                {{ $initials }}
                                            </div>
                                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                                <span class="lg-name-bold">{!! $User->name !!}</span>
                                                @if(!empty($User->mobile))
                                                    <span class="lg-staff-mobile" style="font-size: 12px; color: #64748B; font-weight: 500; margin-top: 2px;">{{ $User->mobile }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="lg-role-badge {{ $roleClass }}">
                                            {{ $roleName }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($userAssignedTeams->count() > 0)
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 240px;">
                                                @foreach($userAssignedTeams as $uTeam)
                                                    <span style="display: inline-block; background: #F1F5F9; color: #334155; font-size: 11.5px; font-weight: 500; padding: 2px 7px; border-radius: 5px; border: 1px solid #E2E8F0;">
                                                        {{ $uTeam->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: #94A3B8; font-weight: 500; font-size: 12.5px;">No Team</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="color: #475569; font-weight: 500;">{!! $User->email !!}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($User->status == 1)
                                            <span class="lg-status-pill active">
                                                <span class="lg-status-dot"></span> Active
                                            </span>
                                        @else
                                            <span class="lg-status-pill inactive">
                                                <span class="lg-status-dot"></span> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                            <button type="button" 
                                                    class="lg-action-icon-btn" 
                                                    title="Edit Staff" 
                                                    onclick="lgOpenEditStaffModal({
                                                        id: '{{ $User->id }}',
                                                        name: '{{ addslashes($User->name) }}',
                                                        email: '{{ addslashes($User->email) }}',
                                                        mobile: '{{ addslashes($User->mobile) }}',
                                                        permissions_id: '{{ $User->permissions_id }}',
                                                        team_id: '{{ is_array($User->team_id) ? implode(',', $User->team_id) : $User->team_id }}',
                                                        work_location_id: '{{ $User->work_location_id }}',
                                                        shift_id: '{{ $User->shift_id }}',
                                                        status: '{{ $User->status }}',
                                                        languages_known: '{{ addslashes($User->languages_known ?? '') }}',
                                                        calling_method: '{{ $User->c2c_channel == 1 ? 'click2call' : ($User->webrtc_channel == 1 ? 'webrtc' : 'none') }}',
                                                        c2c_enabled: {{ $User->c2c_channel == 1 ? 'true' : 'false' }},
                                                        webrtc_enabled: {{ $User->webrtc_channel == 1 ? 'true' : 'false' }},
                                                        update_url: '{{ route('usersUpdate', $User->id) }}'
                                                    })">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            @if(Helper::canDelete('people.staff'))
                                                <button type="button" class="lg-action-icon-btn delete" onclick="lgConfirmDeleteStaff('{{ route('usersDestroy', ['id' => $User->id]) }}', '{{ addslashes($User->name) }}')" title="Delete User">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- FOOTER & PAGINATION -->
                <div class="lg-table-footer-bar">
                    <div class="lg-records-summary">
                        Showing {{ $Users->firstItem() ?? 1 }} to {{ $Users->lastItem() ?? count($Users) }} of {{ $Users->total() }} entries
                    </div>
                    <div class="lg-pagination-container">
                        {!! $Users->links() !!}
                    </div>
                </div>

                {{ Form::close() }}
            @endif

        </div>

    </div>

</div>

<!-- ==================================================
     ADD STAFF MODAL (CENTERED POPUP)
================================================== -->
<div id="lgAddStaffModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        
        <!-- MODAL HEADER -->
        <div class="lg-modal-header-bar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="lg-modal-header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="16" y1="11" x2="22" y2="11"/>
                    </svg>
                </div>
                <div>
                    <h3 class="lg-modal-header-title">Add Staff</h3>
                    <p class="lg-modal-header-sub">Enter the staff member details below to create an account</p>
                </div>
            </div>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseAddStaffModal()" title="Close (Esc)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- MODAL FORM BODY -->
        {{ Form::open(['route' => ['usersStore'], 'method' => 'POST', 'id' => 'lgAddUserForm']) }}
        <div class="lg-modal-body">
            
            <div class="lg-modal-form-grid">
                
                <!-- ROW 1: Full name & Mobile Number -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Full Name <span class="lg-req">*</span></label>
                    <input type="text" 
                           name="name" 
                           id="modal_name" 
                           class="lg-modal-control" 
                           placeholder="For example: Anjali Verma" 
                           required>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Mobile Number <span class="lg-req">*</span></label>
                    <input type="tel" 
                           name="mobile" 
                           id="modal_mobile" 
                           class="lg-modal-control" 
                           placeholder="Enter mobile number" 
                           pattern="^[0-9+\s\-]{7,15}$"
                           title="Please enter a valid mobile number (numeric, optional + and country code)"
                           required>
                </div>

                <!-- ROW 2: Work email & Login Password -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Work Email <span class="lg-req">*</span></label>
                    <input type="email" 
                           name="email" 
                           id="modal_email" 
                           class="lg-modal-control" 
                           placeholder="anjali@ametecs.in" 
                           required>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Login Password <span class="lg-req">*</span></label>
                    <input type="text" 
                           name="password" 
                           id="modal_password" 
                           class="lg-modal-control" 
                           placeholder="Set login password (min 6 chars)" 
                           value="Leadger@123"
                           required
                           minlength="6">
                </div>

                <!-- ROW 3: Role & Team -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Role <span class="lg-req">*</span></label>
                    @php
                        $defaultRole = (isset($Permissions) && count($Permissions) > 0) ? ($Permissions->firstWhere('name', 'Telecaller') ?? $Permissions->first()) : null;
                        $defaultRoleId = $defaultRole ? $defaultRole->id : 7;
                        $defaultRoleName = $defaultRole ? $defaultRole->name : 'Telecaller';
                    @endphp
                    <div class="lg-custom-dropdown-wrap" data-select-id="modal_permissions_id">
                        <input type="hidden" name="permissions_id" id="modal_permissions_id" value="{{ $defaultRoleId }}" required>
                        <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <span class="lg-custom-dropdown-label">{{ $defaultRoleName }}</span>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-custom-dropdown-menu" role="listbox">
                            @if(isset($Permissions))
                                @foreach ($Permissions as $Permission)
                                    <div class="lg-custom-dropdown-option {{ $Permission->id == $defaultRoleId ? 'selected' : '' }}" 
                                         data-val="{{ $Permission->id }}" 
                                         role="option" 
                                         aria-selected="{{ $Permission->id == $defaultRoleId ? 'true' : 'false' }}">
                                        <span>{{ $Permission->name }}</span>
                                        @if($Permission->id == $defaultRoleId)
                                            <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="lg-custom-dropdown-option selected" data-val="1" role="option" aria-selected="true">
                                    <span>Telecaller</span>
                                    <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Team <span class="lg-req">*</span></label>
                    <div class="lg-multi-team-wrap" id="add_team_multi_wrap">
                        <div class="lg-multi-team-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <div class="lg-multi-team-tags" id="add_team_tags">
                                <span class="lg-multi-placeholder">Select Teams</span>
                            </div>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-multi-team-menu" role="listbox">
                            @if(isset($Teams) && count($Teams) > 0)
                                @foreach ($Teams as $Team)
                                    <div class="lg-multi-team-option" data-team-id="{{ $Team->id }}" data-team-name="{{ $Team->name }}">
                                        <div class="lg-team-chk-custom"></div>
                                        <span class="lg-multi-team-label">{{ $Team->name }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="lg-multi-team-option" data-team-id="1" data-team-name="Inside Sales A">
                                    <div class="lg-team-chk-custom"></div>
                                    <span class="lg-multi-team-label">Inside Sales A</span>
                                </div>
                            @endif
                        </div>
                        <div class="lg-multi-team-inputs" id="add_team_inputs"></div>
                    </div>
                    <div id="add_team_error_msg" style="display: none; color: #EF4444; font-size: 11.5px; margin-top: 3px; font-weight: 500;"></div>
                </div>

                <!-- ROW 4: Work Location & Shift -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Work Location <span class="lg-req">*</span></label>
                    <select name="work_location_id" id="modal_work_location_id" class="lg-modal-control lg-modal-select" required>
                        @if(isset($WorkLocations) && count($WorkLocations) > 0)
                            @foreach ($WorkLocations as $WorkLocation)
                                <option value="{{ $WorkLocation->id }}">{{ $WorkLocation->name }}</option>
                            @endforeach
                        @else
                            <option value="1" selected>Main Office</option>
                        @endif
                    </select>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Shift <span class="lg-req">*</span></label>
                    <div class="lg-custom-dropdown-wrap" data-select-id="modal_shift_id">
                        <input type="hidden" name="shift_id" id="modal_shift_id" value="1" required>
                        <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <span class="lg-custom-dropdown-label">Regular – 9:00 AM – 6:00 PM</span>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-custom-dropdown-menu" role="listbox">
                            <div class="lg-custom-dropdown-option selected" data-val="1" role="option" aria-selected="true">
                                <span>Regular – 9:00 AM – 6:00 PM</span>
                                <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CALLING METHOD SECTION -->
            <div class="lg-modal-calling-section">
                <label class="lg-modal-label">Calling Method</label>
                <div class="lg-calling-options-flex">
                    <label class="lg-custom-checkbox-label">
                        <input type="checkbox" name="calling_method[]" value="click2call" id="cm_click2call" checked onclick="selectCallingMethod('click2call')">
                        <span class="lg-custom-checkmark"></span>
                        <span>Click2Call</span>
                    </label>

                    <label class="lg-custom-checkbox-label">
                        <input type="checkbox" name="calling_method[]" value="webrtc" id="cm_webrtc" onclick="selectCallingMethod('webrtc')">
                        <span class="lg-custom-checkmark"></span>
                        <span>WebRTC</span>
                    </label>
                </div>
                <input type="hidden" name="calling_method_present" value="1">
            </div>

            <!-- HIDDEN FIELDS TO PRESERVE BACKEND COMPATIBILITY -->
            @if(isset($SmsGateways) && count($SmsGateways) > 0)
                <input type="hidden" name="sms_gateway_id" value="{{ $SmsGateways[0]->id }}">
            @endif
            @if(isset($EmailSettings) && count($EmailSettings) > 0)
                <input type="hidden" name="email_setting_id" value="{{ $EmailSettings[0]->id }}">
            @endif

        </div>

        <!-- MODAL FOOTER -->
        <div class="lg-modal-footer-bar">
            <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseAddStaffModal()">
                Cancel
            </button>
            <button type="submit" class="lg-btn-modal-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Save Staff</span>
            </button>
        </div>
        {{ Form::close() }}

    </div>
</div>

<!-- ==================================================
     EDIT STAFF MODAL (CENTERED POPUP)
================================================== -->
<div id="lgEditStaffModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        
        <!-- MODAL HEADER -->
        <div class="lg-modal-header-bar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="lg-modal-header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="lg-modal-header-title">Edit Staff</h3>
                    <p class="lg-modal-header-sub">Update selected staff member details and permissions</p>
                </div>
            </div>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseEditStaffModal()" title="Close (Esc)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- MODAL FORM BODY -->
        {{ Form::open(['url' => '', 'method' => 'POST', 'id' => 'lgEditUserForm']) }}
        <div class="lg-modal-body">
            
            <div class="lg-modal-form-grid">
                
                <!-- ROW 1: Full name & Mobile Number -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Full Name <span class="lg-req">*</span></label>
                    <input type="text" 
                           name="name" 
                           id="edit_name" 
                           class="lg-modal-control" 
                           placeholder="Enter full name" 
                           required>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Mobile Number <span class="lg-req">*</span></label>
                    <input type="tel" 
                           name="mobile" 
                           id="edit_mobile" 
                           class="lg-modal-control" 
                           placeholder="Enter mobile number" 
                           pattern="^[0-9+\s\-]{7,15}$"
                           title="Please enter a valid mobile number (numeric, optional + and country code)"
                           required>
                </div>

                <!-- ROW 2: Work Email & Login Password -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Work Email <span class="lg-req">*</span></label>
                    <input type="email" 
                           name="email" 
                           id="edit_email" 
                           class="lg-modal-control" 
                           placeholder="user@company.com" 
                           required>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Login Password</label>
                    <input type="text" 
                           name="password" 
                           id="edit_password" 
                           class="lg-modal-control" 
                           placeholder="Leave blank to keep unchanged">
                </div>

                <!-- ROW 3: Role & Team -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Role <span class="lg-req">*</span></label>
                    <div class="lg-custom-dropdown-wrap" data-select-id="edit_permissions_id">
                        <input type="hidden" name="permissions_id" id="edit_permissions_id" value="" required>
                        <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <span class="lg-custom-dropdown-label">Select Role</span>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-custom-dropdown-menu" role="listbox">
                            @if(isset($Permissions))
                                @foreach ($Permissions as $Permission)
                                    <div class="lg-custom-dropdown-option" 
                                         data-val="{{ $Permission->id }}" 
                                         role="option">
                                        <span>{{ $Permission->name }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="lg-custom-dropdown-option" data-val="1" role="option">
                                    <span>Telecaller</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Team <span class="lg-req">*</span></label>
                    <div class="lg-multi-team-wrap" id="edit_team_multi_wrap">
                        <div class="lg-multi-team-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <div class="lg-multi-team-tags" id="edit_team_tags">
                                <span class="lg-multi-placeholder">Select Teams</span>
                            </div>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-multi-team-menu" role="listbox">
                            @if(isset($Teams) && count($Teams) > 0)
                                @foreach ($Teams as $Team)
                                    <div class="lg-multi-team-option" data-team-id="{{ $Team->id }}" data-team-name="{{ $Team->name }}">
                                        <div class="lg-team-chk-custom"></div>
                                        <span class="lg-multi-team-label">{{ $Team->name }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="lg-multi-team-option" data-team-id="1" data-team-name="Inside Sales A">
                                    <div class="lg-team-chk-custom"></div>
                                    <span class="lg-multi-team-label">Inside Sales A</span>
                                </div>
                            @endif
                        </div>
                        <div class="lg-multi-team-inputs" id="edit_team_inputs"></div>
                    </div>
                    <div id="edit_team_error_msg" style="display: none; color: #EF4444; font-size: 11.5px; margin-top: 3px; font-weight: 500;"></div>
                </div>

                <!-- ROW 4: Work Location & Shift -->
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Work Location <span class="lg-req">*</span></label>
                    <select name="work_location_id" id="edit_work_location_id" class="lg-modal-control lg-modal-select" required>
                        @if(isset($WorkLocations))
                            @foreach ($WorkLocations as $WorkLocation)
                                <option value="{{ $WorkLocation->id }}">{{ $WorkLocation->name }}</option>
                            @endforeach
                        @else
                            <option value="1">Main Office</option>
                        @endif
                    </select>
                </div>

                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Shift <span class="lg-req">*</span></label>
                    <div class="lg-custom-dropdown-wrap" data-select-id="edit_shift_id">
                        <input type="hidden" name="shift_id" id="edit_shift_id" value="1" required>
                        <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                            <span class="lg-custom-dropdown-label">Regular – 9:00 AM – 6:00 PM</span>
                            <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-custom-dropdown-menu" role="listbox">
                            <div class="lg-custom-dropdown-option selected" data-val="1" role="option" aria-selected="true">
                                <span>Regular – 9:00 AM – 6:00 PM</span>
                                <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CALLING METHOD SECTION -->
            <div class="lg-modal-calling-section">
                <label class="lg-modal-label">Calling Method</label>
                <div class="lg-calling-options-flex">
                    <label class="lg-custom-checkbox-label">
                        <input type="checkbox" name="calling_method[]" value="click2call" id="edit_cm_click2call" checked onclick="selectEditCallingMethod('click2call')">
                        <span class="lg-custom-checkmark"></span>
                        <span>Click2Call</span>
                    </label>

                    <label class="lg-custom-checkbox-label">
                        <input type="checkbox" name="calling_method[]" value="webrtc" id="edit_cm_webrtc" onclick="selectEditCallingMethod('webrtc')">
                        <span class="lg-custom-checkmark"></span>
                        <span>WebRTC</span>
                    </label>
                </div>
                <input type="hidden" name="calling_method_present" value="1">
            </div>

            <!-- HIDDEN FIELDS TO PRESERVE BACKEND COMPATIBILITY -->
            @if(isset($SmsGateways) && count($SmsGateways) > 0)
                <input type="hidden" name="sms_gateway_id" value="{{ $SmsGateways[0]->id }}">
            @endif
            @if(isset($EmailSettings) && count($EmailSettings) > 0)
                <input type="hidden" name="email_setting_id" value="{{ $EmailSettings[0]->id }}">
            @endif

        </div>

        <!-- MODAL FOOTER -->
        <div class="lg-modal-footer-bar">
            <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseEditStaffModal()">
                Cancel
            </button>
            <button type="submit" class="lg-btn-modal-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Save Changes</span>
            </button>
        </div>
        {{ Form::close() }}

    </div>
</div>

<!-- ==================================================
     DELETE STAFF CONFIRMATION MODAL
================================================== -->
<div id="lgDeleteStaffModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper" style="max-width: 440px;">
        <div class="lg-modal-header-bar">
            <h3 class="lg-modal-header-title" style="color: #EF4444; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Confirm Delete</span>
            </h3>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseDeleteStaffModal()" title="Close (Esc)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="lg-modal-body" style="text-align: center; padding: 28px 24px;">
            <p style="color: #475569; font-size: 14px; margin-bottom: 8px;">Are you sure you want to delete this staff member?</p>
            <strong id="lgDeleteStaffName" style="color: #0F172A; font-size: 16px; font-weight: 700;"></strong>
            <p style="color: #94A3B8; font-size: 12px; margin-top: 8px;">This action cannot be undone.</p>
        </div>
        <div class="lg-modal-footer-bar">
            <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseDeleteStaffModal()">Cancel</button>
            <a id="lgDeleteStaffBtn" href="#" class="lg-btn-modal-submit" style="background: #EF4444; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25); text-decoration: none !important;">Delete Staff</a>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        // Search filter
        const searchInput = document.getElementById('lgStaffSearch');
        const roleFilter = document.getElementById('lgRoleFilter');
        const tableRows = document.querySelectorAll('.lg-staff-row');

        function filterTable() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const role = roleFilter ? roleFilter.value.toLowerCase() : '';

            tableRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const rowRole = row.getAttribute('data-role') || '';

                const matchesQuery = !query || name.includes(query) || email.includes(query);
                const matchesRole = !role || rowRole.includes(role);

                if (matchesQuery && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (roleFilter) roleFilter.addEventListener('change', filterTable);

        // ?search=<name> (e.g. from the Agent Performance "Total Agents" card) pre-fills the search
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchInput && searchParam) {
            searchInput.value = searchParam;
            filterTable();
        }

        // Backdrop click to close modals
        const modalOverlay = document.getElementById('lgAddStaffModal');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    lgCloseAddStaffModal();
                }
            });
        }

        const editModalOverlay = document.getElementById('lgEditStaffModal');
        if (editModalOverlay) {
            editModalOverlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    lgCloseEditStaffModal();
                }
            });
        }

        const deleteOverlay = document.getElementById('lgDeleteStaffModal');
        if (deleteOverlay) {
            deleteOverlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    lgCloseDeleteStaffModal();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                lgCloseAddStaffModal();
                lgCloseEditStaffModal();
                lgCloseDeleteStaffModal();
            }
        });
    });

    function lgOpenAddStaffModal() {
        const modal = document.getElementById('lgAddStaffModal');
        const form = document.getElementById('lgAddUserForm');
        if (form) {
            form.reset();
            if (document.getElementById('modal_password')) {
                document.getElementById('modal_password').value = 'Leadger@123';
            }
            document.getElementById('cm_click2call').checked = true;
            document.getElementById('cm_webrtc').checked = false;
            setTeamSelection('add', []);

            // Reset custom dropdowns in Add Staff modal
            const $addRoleWrap = $('[data-select-id="modal_permissions_id"]');
            if ($addRoleWrap.length) {
                let $defOpt = $addRoleWrap.find('.lg-custom-dropdown-option[data-val="7"]');
                if (!$defOpt.length) {
                    $defOpt = $addRoleWrap.find('.lg-custom-dropdown-option').not('[data-val=""]').first();
                }
                if ($defOpt.length) {
                    const defVal = $defOpt.data('val');
                    $('#modal_permissions_id').val(defVal);
                    $addRoleWrap.find('.lg-custom-dropdown-label').text($.trim($defOpt.find('span').text() || $defOpt.text()));
                    $addRoleWrap.find('.lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
                    $defOpt.addClass('selected');
                    if (!$defOpt.find('.lg-option-check').length) {
                        $defOpt.append('<svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
                    }
                }
            }
            const $addShiftWrap = $('[data-select-id="modal_shift_id"]');
            if ($addShiftWrap.length) {
                $('#modal_shift_id').val(1);
                $addShiftWrap.find('.lg-custom-dropdown-label').text('Regular – 9:00 AM – 6:00 PM');
                $addShiftWrap.find('.lg-custom-dropdown-option').addClass('selected');
            }
        }
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const firstInput = document.getElementById('modal_name');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }

    function lgCloseAddStaffModal() {
        const modal = document.getElementById('lgAddStaffModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function lgOpenEditStaffModal(user) {
        const modal = document.getElementById('lgEditStaffModal');
        const form = document.getElementById('lgEditUserForm');
        if (modal && form) {
            form.action = user.update_url;
            
            if (document.getElementById('edit_name')) document.getElementById('edit_name').value = user.name || '';
            if (document.getElementById('edit_mobile')) document.getElementById('edit_mobile').value = user.mobile || '';
            if (document.getElementById('edit_email')) document.getElementById('edit_email').value = user.email || '';
            if (document.getElementById('edit_password')) document.getElementById('edit_password').value = '';
            
            if (document.getElementById('edit_permissions_id')) {
                const pVal = String(user.permissions_id || 1);
                document.getElementById('edit_permissions_id').value = pVal;
                const $roleWrap = $('[data-select-id="edit_permissions_id"]');
                const $targetOption = $roleWrap.find(`.lg-custom-dropdown-option[data-val="${pVal}"]`);
                $roleWrap.find('.lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
                if ($targetOption.length) {
                    $targetOption.addClass('selected');
                    $targetOption.append('<svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
                    $roleWrap.find('.lg-custom-dropdown-label').text($.trim($targetOption.find('span').text() || $targetOption.text()));
                } else {
                    $roleWrap.find('.lg-custom-dropdown-label').text('Select Role');
                }
            }

            // Set Multi-Team Selection for Edit Staff
            let tVals = [];
            if (user.team_id) {
                tVals = String(user.team_id).split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            }
            setTeamSelection('edit', tVals);

            if (document.getElementById('edit_work_location_id')) {
                const locSelect = document.getElementById('edit_work_location_id');
                if (user.work_location_id) {
                    locSelect.value = user.work_location_id;
                } else if (locSelect.options.length > 0) {
                    locSelect.selectedIndex = 0;
                }
            }
            if (document.getElementById('edit_shift_id')) {
                document.getElementById('edit_shift_id').value = 1;
                const $shiftWrap = $('[data-select-id="edit_shift_id"]');
                $shiftWrap.find('.lg-custom-dropdown-label').text('Regular – 9:00 AM – 6:00 PM');
                $shiftWrap.find('.lg-custom-dropdown-option').addClass('selected');
            }
            
            document.getElementById('edit_cm_click2call').checked = !!user.c2c_enabled;
            document.getElementById('edit_cm_webrtc').checked = !!user.webrtc_enabled;
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const firstInput = document.getElementById('edit_name');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }

    function lgCloseEditStaffModal() {
        const modal = document.getElementById('lgEditStaffModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function lgConfirmDeleteStaff(url, name) {
        const modal = document.getElementById('lgDeleteStaffModal');
        const nameElem = document.getElementById('lgDeleteStaffName');
        const deleteBtn = document.getElementById('lgDeleteStaffBtn');
        if (nameElem) nameElem.textContent = name;
        if (deleteBtn) deleteBtn.href = url;
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function selectCallingMethod(method) {
        // Click2Call and WebRTC are independent: a user may have one, both or neither, so nothing else changes
    }

    function selectEditCallingMethod(method) {
        // Click2Call and WebRTC are independent: a user may have one, both or neither, so nothing else changes
    }

    function lgCloseDeleteStaffModal() {
        const modal = document.getElementById('lgDeleteStaffModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Multi-Team Select Logic
    var lgSelectedTeams = {
        add: [],
        edit: []
    };

    function renderMultiTeam(scope) {
        var $wrap = $('#' + scope + '_team_multi_wrap');
        var $tags = $('#' + scope + '_team_tags');
        var $inputs = $('#' + scope + '_team_inputs');
        var selected = lgSelectedTeams[scope] || [];

        $tags.empty();
        $inputs.empty();

        if (selected.length === 0) {
            $tags.html('<span class="lg-multi-placeholder">Select Teams</span>');
        } else {
            selected.forEach(function (team) {
                var $tag = $('<span class="lg-team-tag"></span>');
                $tag.text(team.name + ' ').attr('data-id', team.id);
                var $x = $('<span class="lg-team-tag-x">&times;</span>');
                $x.on('click', function (e) {
                    e.stopPropagation();
                    toggleTeamSelection(scope, team.id, team.name);
                });
                $tag.append($x);
                $tags.append($tag);

                $inputs.append('<input type="hidden" name="team_id[]" value="' + team.id + '">');
            });
        }

        // Highlight options in dropdown menu
        $wrap.find('.lg-multi-team-option').each(function () {
            var optId = String($(this).data('team-id'));
            var isSel = selected.some(function (t) { return String(t.id) === optId; });
            if (isSel) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        });

        if (selected.length > 0) {
            $wrap.find('.lg-multi-team-trigger').removeClass('error');
            $('#' + scope + '_team_error_msg').hide();
        }
    }

    function toggleTeamSelection(scope, teamId, teamName) {
        teamId = String(teamId);
        var list = lgSelectedTeams[scope] || [];
        var index = list.findIndex(function (t) { return String(t.id) === teamId; });

        if (index > -1) {
            list.splice(index, 1);
        } else {
            list.push({ id: teamId, name: teamName });
        }
        lgSelectedTeams[scope] = list;
        renderMultiTeam(scope);
    }

    function setTeamSelection(scope, teamIds) {
        var list = [];
        var $wrap = $('#' + scope + '_team_multi_wrap');
        if (!Array.isArray(teamIds)) {
            teamIds = teamIds ? [teamIds] : [];
        }
        var idStrings = teamIds.map(function(id) { return String(id).trim(); });

        $wrap.find('.lg-multi-team-option').each(function () {
            var optId = String($(this).data('team-id'));
            var optName = $(this).data('team-name');
            if (idStrings.indexOf(optId) !== -1) {
                list.push({ id: optId, name: optName });
            }
        });
        lgSelectedTeams[scope] = list;
        renderMultiTeam(scope);
    }

    // LeadGER Custom Dropdown Interactive Handling
    $(document).ready(function () {
        // Destroy select2 on modal selects to prevent background attachment issues
        if (typeof $.fn.select2 !== 'undefined') {
            $('.lg-modal select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
        }

        $(document).on('click', '.lg-custom-dropdown-trigger', function (e) {
            e.stopPropagation();
            const $wrap = $(this).closest('.lg-custom-dropdown-wrap');
            const isOpen = $wrap.hasClass('open');

            // Close any other open custom dropdowns
            $('.lg-custom-dropdown-wrap').not($wrap).removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
            $('.dropdown-open').removeClass('dropdown-open');

            if (isOpen) {
                $wrap.removeClass('open');
                $wrap.closest('.lg-modal-form-group, .lg-field-group').removeClass('dropdown-open');
                $(this).attr('aria-expanded', 'false');
            } else {
                $wrap.addClass('open');
                $wrap.closest('.lg-modal-form-group, .lg-field-group').addClass('dropdown-open');
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

            // 1. Update native hidden select value for form submit
            $('#' + selectId).val(val).trigger('change');

            // 2. Update visual label
            $wrap.find('.lg-custom-dropdown-label').text(text);

            // 3. Highlight selected option with checkmark
            $wrap.find('.lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
            $option.addClass('selected');
            if (!$option.find('.lg-option-check').length) {
                $option.append('<svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
            }

            // 4. Close dropdown
            $wrap.removeClass('open');
            $wrap.closest('.lg-modal-form-group, .lg-field-group').removeClass('dropdown-open');
            $wrap.find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function () {
            $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
            $('.dropdown-open').removeClass('dropdown-open');
        });

        // Keyboard support: Escape to close, Enter/Space/ArrowDown
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
                $('.dropdown-open').removeClass('dropdown-open');
            }
        });

        $(document).on('keydown', '.lg-custom-dropdown-trigger', function (e) {
            const $trigger = $(this);
            const $wrap = $trigger.closest('.lg-custom-dropdown-wrap');
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $trigger.trigger('click');
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$wrap.hasClass('open')) {
                    $trigger.trigger('click');
                }
            }
        });

        // Multi-Team Dropdown Trigger Toggle
        $(document).on('click', '.lg-multi-team-trigger', function (e) {
            e.stopPropagation();
            var $wrap = $(this).closest('.lg-multi-team-wrap');
            var isOpen = $wrap.hasClass('open');

            // Close any other open dropdowns
            $('.lg-multi-team-wrap').not($wrap).removeClass('open').closest('.lg-modal-form-group').removeClass('multi-open');
            $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
            $('.dropdown-open').removeClass('dropdown-open');

            if (isOpen) {
                $wrap.removeClass('open');
                $wrap.closest('.lg-modal-form-group').removeClass('multi-open');
                $(this).attr('aria-expanded', 'false');
            } else {
                $wrap.addClass('open');
                $wrap.closest('.lg-modal-form-group').addClass('multi-open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        // Multi-Team Option Selection Toggle
        $(document).on('click', '.lg-multi-team-option', function (e) {
            e.stopPropagation();
            var $wrap = $(this).closest('.lg-multi-team-wrap');
            var scope = $wrap.attr('id').startsWith('add') ? 'add' : 'edit';
            var teamId = $(this).data('team-id');
            var teamName = $(this).data('team-name');
            toggleTeamSelection(scope, teamId, teamName);
        });

        // Form Validation on Add Staff submit
        $('#lgAddUserForm').on('submit', function (e) {
            if (!lgSelectedTeams.add || lgSelectedTeams.add.length === 0) {
                e.preventDefault();
                $('#add_team_multi_wrap .lg-multi-team-trigger').addClass('error');
                $('#add_team_error_msg').text('Please select at least one team.').show();
                return false;
            }
        });

        // Close multi-team dropdown on outside click
        $(document).on('click', function () {
            $('.lg-multi-team-wrap').removeClass('open').closest('.lg-modal-form-group').removeClass('multi-open');
            $('.lg-multi-team-trigger').attr('aria-expanded', 'false');
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('.lg-multi-team-wrap').removeClass('open').closest('.lg-modal-form-group').removeClass('multi-open');
                $('.lg-multi-team-trigger').attr('aria-expanded', 'false');
            }
        });
    });
</script>
@endpush
