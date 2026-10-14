@extends('dashboard.layouts.master')
@section('title', 'Agent Performance')

@push("after-styles")
<!-- FontAwesome 6, DataTables & Flatpickr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
/* ==========================================================================
   LeadGER Corporate Modern Design System - Agent Performance Report
   ========================================================================== */

/* Page Canvas */
.lg-agent-dashboard {
    padding: 24px 30px 40px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #0F172A;
}

/* 1. Page Header Card */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    gap: 16px;
    flex-wrap: wrap;
}
.lg-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.lg-header-icon-badge {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.08);
}
.lg-header-title {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}
.lg-header-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 3px 0 0 0;
    font-weight: 500;
}

/* 2. Filters Row Card */
.lg-filters-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.lg-filters-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.lg-filters-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-left: auto;
}

/* Date Range Controls */
.lg-date-filter-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    flex-wrap: nowrap;
}
.lg-date-input-box {
    position: relative;
    display: inline-flex;
    align-items: center;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    height: 44px;
    width: 154px;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
    box-sizing: border-box;
}
.lg-date-input-box:hover {
    border-color: var(--lg-accent);
}
.lg-date-input-box:focus-within,
.lg-date-input-box.is-active {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}
.lg-date-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748B;
    font-size: 14px;
    line-height: 1;
    pointer-events: none;
    z-index: 2;
    transition: color 0.15s ease;
}
.lg-date-input-box:hover .lg-date-icon,
.lg-date-input-box:focus-within .lg-date-icon {
    color: var(--lg-accent);
}
.lg-date-field {
    width: 100% !important;
    height: 42px !important;
    border: none !important;
    background: transparent !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #0F172A !important;
    padding: 0 10px 0 38px !important;
    outline: none !important;
    cursor: pointer !important;
    border-radius: 9px !important;
    letter-spacing: 0.2px !important;
    box-sizing: border-box !important;
    z-index: 1;
}
.lg-date-sep {
    color: #94A3B8;
    font-weight: 600;
    font-size: 14px;
    user-select: none;
    flex-shrink: 0;
}
.lg-date-error-msg {
    font-size: 12px;
    color: #EF4444;
    font-weight: 600;
    margin-top: 4px;
    width: 100%;
}

/* Flatpickr Custom Styling */
.flatpickr-calendar {
    background: #FFFFFF !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 14px !important;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12) !important;
    font-family: inherit !important;
    width: 280px !important;
    padding: 8px !important;
    margin-top: 6px !important;
    z-index: 99999 !important;
}
.flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after {
    display: none !important;
}
.flatpickr-months {
    background: transparent !important;
    padding: 4px 0 8px 0 !important;
    align-items: center !important;
}
.flatpickr-months .flatpickr-month {
    color: #0F172A !important;
    height: 36px !important;
}
.flatpickr-current-month {
    font-size: 13.5px !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    padding: 2px 0 0 0 !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months {
    font-weight: 700 !important;
    color: #0F172A !important;
}
.flatpickr-current-month input.cur-year {
    font-weight: 700 !important;
    color: #0F172A !important;
}
.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
    color: #475569 !important;
    fill: #475569 !important;
    padding: 6px 8px !important;
    border-radius: 6px !important;
    transition: all 0.15s ease !important;
}
.flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-months .flatpickr-next-month:hover {
    color: var(--lg-accent) !important;
    fill: var(--lg-accent) !important;
    background: #EFF6FF !important;
}
span.flatpickr-weekday {
    color: #64748B !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
}
.flatpickr-days {
    width: 264px !important;
}
.dayContainer {
    width: 264px !important;
    min-width: 264px !important;
    max-width: 264px !important;
}
.flatpickr-day {
    color: #1E293B !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    font-size: 12.5px !important;
    height: 34px !important;
    line-height: 34px !important;
    max-width: 34px !important;
    margin: 1px auto !important;
    border: 1px solid transparent !important;
    transition: all 0.12s ease !important;
}
.flatpickr-day:hover {
    background: #EFF6FF !important;
    border-color: #DBEAFE !important;
    color: var(--lg-accent) !important;
}
.flatpickr-day.today {
    border-color: var(--lg-accent) !important;
    color: var(--lg-accent) !important;
    font-weight: 700 !important;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: var(--lg-accent) !important;
    border-color: var(--lg-accent) !important;
    color: #FFFFFF !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.3) !important;
}
.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
    color: #CBD5E1 !important;
    background: transparent !important;
    cursor: not-allowed !important;
}

/* Dropdown Filters with inside aligned chevron arrow */
.lg-filter-select-wrap {
    position: relative;
    display: inline-block;
}
.lg-filter-select {
    height: 44px;
    min-width: 160px;
    padding: 0 38px 0 14px;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    background-color: #FFFFFF;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 13px center;
    color: #0F172A;
    font-size: 13.5px;
    font-weight: 500;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.lg-filter-select:hover {
    border-color: var(--lg-accent);
}
.lg-filter-select:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* Export & Refresh Buttons (Height 44px, matching border radius) */
.lg-btn-export {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    font-weight: 600;
    border-radius: 9px;
    height: 44px;
    padding: 0 18px;
    font-size: 13.5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    text-decoration: none;
    box-sizing: border-box;
    white-space: nowrap;
}
.lg-btn-export:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
    box-shadow: 0 3px 8px rgba(20, 100, 244, 0.25);
}

.lg-btn-refresh {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
    font-weight: 600;
    border-radius: 9px;
    height: 44px;
    padding: 0 16px;
    font-size: 13.5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    box-sizing: border-box;
    white-space: nowrap;
}
.lg-btn-refresh:hover {
    background: #F8FAFC;
    color: #0F172A;
    border-color: #94A3B8;
}

/* 3. KPI Stat Cards (6 Equal Columns) */
.lg-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
@media (max-width: 1400px) {
    .lg-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .lg-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .lg-kpi-grid { grid-template-columns: 1fr; }
}

.lg-kpi-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 114px;
    box-sizing: border-box;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.lg-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
/* Summary cards open the records behind each number */
.lg-kpi-link {
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.lg-kpi-link:hover {
    border-color: #93B4FF;
    box-shadow: 0 6px 16px rgba(23, 105, 255, 0.10);
}
.lg-kpi-link:focus { outline: none; }
.lg-kpi-link:focus-visible {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.25);
}
.lg-kpi-link:active { transform: translateY(1px); }
.lg-kpi-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}
.lg-kpi-title {
    color: #64748B;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.35px;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    padding-top: 2px;
}
.lg-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.icon-purple { background: #F5F3FF; color: #7C3AED; }
.icon-blue   { background: #EFF6FF; color: var(--lg-accent); }
.icon-green  { background: #ECFDF5; color: #059669; }
.icon-amber  { background: #FFFBEB; color: #D97706; }
.icon-cyan   { background: #ECFEFF; color: #0891B2; }
.icon-pink   { background: #FDF2F8; color: #DB2777; }

.lg-kpi-val {
    font-size: 24px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.2;
    margin-bottom: 2px;
    letter-spacing: -0.3px;
}
.lg-kpi-subtext {
    font-size: 11.5px;
    color: #64748B;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    line-height: 1.2;
}
.lg-trend-up {
    color: #059669;
    font-weight: 600;
}
.lg-trend-down {
    color: #DC2626;
    font-weight: 600;
}
.lg-trend-neutral {
    color: #94A3B8;
    font-weight: 500;
}

/* 4. Details Table Card */
.lg-table-card {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.lg-table-top {
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.lg-table-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-table-title-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.lg-table-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.2;
}

/* Custom Table Toolbar */
.lg-table-toolbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.lg-table-length-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}
.lg-table-length-select {
    height: 40px;
    padding: 0 28px 0 10px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    background-color: #FFFFFF;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    color: #0F172A;
    font-size: 13px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    box-sizing: border-box;
}
.lg-table-length-select:focus {
    border-color: var(--lg-accent);
}
.lg-search-field-wrap {
    position: relative;
    width: 220px;
}
.lg-search-field-wrap input {
    width: 100%;
    height: 40px;
    padding: 0 12px 0 34px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.lg-search-field-wrap input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.1);
}
.lg-search-field-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 13px;
}

/* Table Styling */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 !important;
}
.lg-table {
    width: 100% !important;
    min-width: 980px;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 0 !important;
}
.lg-table thead th {
    background: #F8FAFD !important;
    color: #0F172A !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    text-transform: uppercase !important;
    padding: 13px 14px !important;
    border-top: none !important;
    border-bottom: 1px solid #E2E8F0 !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}
.lg-table tbody td {
    padding: 12px 14px !important;
    font-size: 13px !important;
    color: #1E293B !important;
    border-bottom: 1px solid #F1F5F9 !important;
    border-top: none !important;
    vertical-align: middle !important;
    background: #FFFFFF !important;
}
.lg-table tbody tr:hover td {
    background-color: #F8FAFC !important;
}

/* Agent column: keep long names on one line (sticky header / first column come from the global
   lg-sticky-tables.js + leadger-theme.css section 10) */
#agentTableCard .lg-agent-cell { min-width: 0; }
#agentTableCard .lg-agent-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px; }

/* Eliminate any gap row created by DataTables scroll or table styling */
.dataTables_scrollHeadInner,
.dataTables_scrollHeadInner table {
    width: 100% !important;
}
.dataTables_scrollBody thead th,
.dataTables_scrollBody thead td {
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    height: 0 !important;
    line-height: 0 !important;
    font-size: 0 !important;
}
.dataTables_scrollBody thead tr {
    height: 0 !important;
    border: none !important;
}

/* Agent cell styling */
.lg-agent-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--lg-accent), #0F52CC);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lg-agent-name {
    font-weight: 600;
    color: #0F172A;
    white-space: nowrap;
}

.lg-role-badge {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 12px;
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #DBEAFE;
}

.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 12px;
}
.status-active {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}
.status-inactive {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #E2E8F0;
}

/* Two-line Date & Time cells */
.lg-dt-cell {
    line-height: 1.35;
    white-space: nowrap;
}
.lg-dt-date {
    font-size: 12.5px;
    font-weight: 600;
    color: #0F172A;
}
.lg-dt-time {
    font-size: 11.5px;
    font-weight: 500;
    color: #64748B;
}

/* Table Footer (Info & Pagination) */
.lg-table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-top: 1px solid #F1F5F9;
    background: #FFFFFF;
    flex-wrap: wrap;
    gap: 12px;
}
.lg-footer-info .dataTables_info,
.dataTables_info {
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
    padding: 0 !important;
    margin: 0 !important;
}
.lg-footer-pagination .dataTables_paginate,
.dataTables_paginate {
    padding: 0 !important;
    margin: 0 !important;
}

/* Excel / CSV export: bottom left of the details table; "Showing ..." and pagination stay together on the right */
.lg-table-footer.has-export { padding-top: 16px; }
#agentTableCard > .lg-ap-export { display: none; } /* until the table footer exists */
.lg-ap-export { display: flex; align-items: center; gap: 10px; }
.lg-footer-right { display: flex; align-items: center; justify-content: flex-end; gap: 16px; flex-wrap: wrap; margin-left: auto; }
.lg-ap-export-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.lg-ap-export-btn.excel { background: #12A66A; border: 1px solid #12A66A; color: #FFFFFF; }
.lg-ap-export-btn.excel:hover:not(:disabled) { background: #0E8C59; border-color: #0E8C59; box-shadow: 0 3px 8px rgba(18, 166, 106, 0.25); }
.lg-ap-export-btn.csv { background: #FFFFFF; border: 1px solid #CBD5E1; color: #0F172A; }
.lg-ap-export-btn.csv:hover:not(:disabled) { background: #F8FAFC; border-color: #94A3B8; }
.lg-ap-export-btn:focus-visible { outline: 2px solid var(--lg-accent); outline-offset: 2px; }
.lg-ap-export-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.lg-ap-export-btn .lg-ap-spinner {
    display: none;
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: lgApSpin 0.7s linear infinite;
}
.lg-ap-export-btn.is-loading .lg-ap-spinner { display: inline-block; }
.lg-ap-export-btn.is-loading svg { display: none; }
@keyframes lgApSpin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .lg-ap-export-btn .lg-ap-spinner { animation-duration: 2s; } }
</style>
@endpush

@section('content')
@php
    // Same roles as the "Export Report" button (and the export route)
    $canExportAgentPerformance = in_array(optional(Auth::user()->permissionsGroup)->name, ['Webmaster', 'Admin', 'Manager'], true);
@endphp
<div class="lg-agent-dashboard">

    <!-- 1. PAGE HEADER CARD -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-badge">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <h1 class="lg-header-title">Agent Performance</h1>
                <p class="lg-header-subtitle">Track and analyze your telecallers performance</p>
            </div>
        </div>
    </div>

    <!-- 2. FILTERS CARD -->
    <div class="lg-filters-card">
        <div class="lg-filters-left">
            <!-- Date Filter Box -->
            <div class="lg-date-filter-wrap">
                <div class="lg-date-input-box" id="lgFromDateBox">
                    <i class="far fa-calendar lg-date-icon"></i>
                    <input type="text" id="from_date" class="lg-date-field" value="{{ date('Y-m-d') }}" placeholder="From Date" readonly>
                </div>
                <span class="lg-date-sep">-</span>
                <div class="lg-date-input-box" id="lgToDateBox">
                    <i class="far fa-calendar lg-date-icon"></i>
                    <input type="text" id="to_date" class="lg-date-field" value="{{ date('Y-m-d') }}" placeholder="To Date" readonly>
                </div>
                <div id="dateValidationError" class="lg-date-error-msg" style="display: none;"></div>
            </div>

            <!-- Agent Filter Dropdown -->
            <div class="lg-filter-select-wrap">
                <select id="agentFilterSelect" class="lg-filter-select" onchange="handleFilterChange()">
                    <option value="all" selected>All Agents</option>
                    @if(isset($Telecallers))
                        @foreach($Telecallers as $telecaller)
                            <option value="{{ $telecaller->id }}">{{ $telecaller->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Campaign Filter Dropdown -->
            <div class="lg-filter-select-wrap">
                <select id="campaignFilterSelect" class="lg-filter-select" onchange="handleFilterChange()">
                    @if(isset($Campaigns) && count($Campaigns) > 0)
                        <option value="all" selected>All Campaigns</option>
                        @foreach($Campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                        @endforeach
                    @else
                        <option value="" selected>No Campaigns</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="lg-filters-right">
            <!-- Export Button -->
            @if(in_array(Auth::user()->permissionsGroup->name, ['Webmaster','Admin','Manager']))
            <button type="button" class="lg-btn-export" id="excel_btn" onclick="print_as('excel')">
                <i class="fas fa-download"></i>
                <span>Export Report</span>
            </button>
            @endif

            <!-- Refresh Button -->
            <button type="button" class="lg-btn-refresh" id="btnRefreshTable" onclick="handleRefresh()">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh</span>
            </button>
        </div>
    </div>

    <!-- 3. KPI STAT CARDS (6 Equal Width Cards) -->
    <div class="lg-kpi-grid">
        <!-- Card 1: TOTAL AGENTS -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="agents" role="link" tabindex="0" title="View these agents" aria-label="Total agents: view these agents">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">TOTAL AGENTS</span>
                <div class="lg-kpi-icon icon-purple">
                    <i class="fas fa-user-gear"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiTotalAgents">{{ isset($Telecallers) ? count($Telecallers) : 3 }}</div>
            <div class="lg-kpi-subtext" id="kpiTotalAgentsSub">{{ isset($Telecallers) ? count($Telecallers) : 3 }} Active agents</div>
        </div>

        <!-- Card 2: TOTAL CALLS -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="calls" role="link" tabindex="0" title="View these calls in the Call Report" aria-label="Total calls: view these calls in the Call Report">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">TOTAL CALLS</span>
                <div class="lg-kpi-icon icon-blue">
                    <i class="fas fa-phone"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiTotalCalls">0</div>
            <div class="lg-kpi-subtext" id="kpiTotalCallsSub"><span id="trendTotalCalls" class="lg-trend-neutral">—</span> vs last 7 days</div>
        </div>

        <!-- Card 3: CONNECTED CALLS -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="connected" role="link" tabindex="0" title="View the connected calls in the Call Report" aria-label="Connected calls: view them in the Call Report">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">CONNECTED CALLS</span>
                <div class="lg-kpi-icon icon-green">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiConnectedCalls">0</div>
            <div class="lg-kpi-subtext" id="kpiConnectedCallsSub"><span id="trendConnectedCalls" class="lg-trend-neutral">—</span> vs last 7 days</div>
        </div>

        <!-- Card 4: CONVERSIONS -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="conversions" role="link" tabindex="0" title="View the calls that ended in a Deal" aria-label="Conversions: view the calls that ended in a Deal">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">CONVERSIONS</span>
                <div class="lg-kpi-icon icon-amber">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiConversions">0</div>
            <div class="lg-kpi-subtext" id="kpiConversionsSub"><span id="trendConversions" class="lg-trend-neutral">—</span> vs last 7 days</div>
        </div>

        <!-- Card 5: AVG TALK TIME -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="avg_talk" role="link" tabindex="0" title="View each call's talk duration in the Call Report" aria-label="Average talk time: view each call's duration in the Call Report">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">AVG TALK TIME</span>
                <div class="lg-kpi-icon icon-cyan">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiAvgTalkTime">0 mins</div>
            <div class="lg-kpi-subtext" id="kpiAvgTalkTimeSub"><span id="trendAvgTalkTime" class="lg-trend-neutral">—</span> vs last 7 days</div>
        </div>

        <!-- Card 6: CONVERSION RATE -->
        <div class="lg-kpi-card lg-kpi-link" data-kpi="conversion_rate" role="link" tabindex="0" title="View the Deal calls and how the rate is calculated" aria-label="Conversion rate: view the Deal calls and the calculation">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">CONVERSION RATE</span>
                <div class="lg-kpi-icon icon-pink">
                    <i class="fas fa-percent"></i>
                </div>
            </div>
            <div class="lg-kpi-val" id="kpiConversionRate">0%</div>
            <div class="lg-kpi-subtext" id="kpiConversionRateSub"><span id="trendConversionRate" class="lg-trend-neutral">—</span> vs last 7 days</div>
        </div>
    </div>

    <!-- 4. DETAILS TABLE CARD -->
    <div class="lg-table-card" id="agentTableCard" style="scroll-margin-top: 24px;">
        <div class="lg-table-top">
            <div class="lg-table-title-wrap">
                <div class="lg-table-title-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h5 class="lg-table-title">Agent Performance Details</h5>
            </div>

            <div class="lg-table-toolbar-right">
                <div class="lg-table-length-wrap">
                    <span>Show</span>
                    <select id="showEntriesSelect" class="lg-table-length-select" onchange="changeTableLength(this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="lg-search-field-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="tableSearchInput" placeholder="Search agent..." onkeyup="handleSearchInput(this.value)">
                </div>
            </div>
        </div>

        <div id="agentFilterChipContainer" style="display: none; padding: 0 24px 16px 24px;"></div>

        <table class="table lg-table mb-0" id="leads_table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Role</th>
                    <th>Login</th>
                    <th>Logout</th>
                    <th>Total Calls</th>
                    <th>Connected</th>
                    <th>Talk Time</th>
                    <th>Interested</th>
                    <th>Converted</th>
                    <th>Conv. Rate</th>
                </tr>
            </thead>
        </table>

        @if($canExportAgentPerformance)
        {{-- Moved into the table footer (bottom left) once the table is ready --}}
        <div class="lg-ap-export" id="lgApExport" role="group" aria-label="Export Agent Performance Details">
            <button type="button" class="lg-ap-export-btn excel" data-format="xlsx" title="Download as Excel (.xlsx)" aria-label="Download as Excel (.xlsx)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
                <span class="lg-ap-spinner" aria-hidden="true"></span>
                <span>Excel</span>
            </button>
            <button type="button" class="lg-ap-export-btn csv" data-format="csv" title="Download as CSV" aria-label="Download as CSV">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                <span class="lg-ap-spinner" aria-hidden="true"></span>
                <span>CSV</span>
            </button>
        </div>
        @endif
    </div>

</div>

<!-- Lead Detail Modal -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content"></div>
    </div>
</div>
@endsection

@push("after-scripts")
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script type="text/javascript">
function leadDetail(id) {
    var url = "{{ route('leadDetail', [':id']) }}";
    url = url.replace(':id', id);
    $('.modal-content').html('');
    $.ajax({
        type: "GET",
        url: url,
        dataType: "html",
        success: function(html){
            $('.modal-content').html(html);
            $("#lead-detail").modal("show");
            if ($.fn.select2) {
                $("select").select2();
            }
        }
    });
}

function emptyVal(val) {
    return val === null || val === undefined || val === '' || val === 'null' || val === 'NULL';
}

function formatCompactDateTime(dtStr) {
    if (emptyVal(dtStr)) {
        return '<span style="color: #94A3B8; font-weight: 500;">—</span>';
    }
    var normalized = dtStr.replace('T', ' ').replace(/-/g, '/');
    var d = new Date(normalized);
    if (isNaN(d.getTime())) {
        return '<span style="font-size: 12.5px; color: #1E293B;">' + dtStr + '</span>';
    }
    var day = ("0" + d.getDate()).slice(-2);
    var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var month = months[d.getMonth()];
    var hours = d.getHours();
    var minutes = ("0" + d.getMinutes()).slice(-2);
    var ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    var formattedHours = ("0" + hours).slice(-2);
    
    return '<div class="lg-dt-cell">' +
           '<div class="lg-dt-date">' + day + ' ' + month + '</div>' +
           '<div class="lg-dt-time">' + formattedHours + ':' + minutes + ' ' + ampm + '</div>' +
           '</div>';
}

var searchTimer = null;
function handleSearchInput(val) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        if (window.agentDataTable) {
            window.agentDataTable.search(val).draw();
        }
    }, 300);
}

function changeTableLength(len) {
    if (window.agentDataTable) {
        window.agentDataTable.page.len(parseInt(len, 10)).draw();
    }
}

function handleFilterChange() {
    if (window.agentDataTable) {
        window.agentDataTable.draw();
    }
}

function handleRefresh() {
    var refreshIcon = document.querySelector('#btnRefreshTable i');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    if (window.agentDataTable) {
        if (window.agentDataTable.ajax && typeof window.agentDataTable.ajax.reload === 'function') {
            window.agentDataTable.ajax.reload(null, false);
        } else if (typeof window.agentDataTable.draw === 'function') {
            window.agentDataTable.draw();
        }
        if (typeof toastr !== 'undefined') {
            toastr.success('Data refreshed successfully');
        }
    }

    setTimeout(function() {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }, 500);
}

$(document).ready(function () {
    // Validation message helpers for Date Range
    function showDateError(msg) {
        var errEl = $('#dateValidationError');
        if (errEl.length) {
            errEl.text(msg).show();
        }
    }

    function hideDateError() {
        var errEl = $('#dateValidationError');
        if (errEl.length) {
            errEl.text('').hide();
        }
    }

    function renderAgentFilterChip() {
        var chipContainer = $('#agentFilterChipContainer');
        if (!chipContainer.length) return;
        if (window.activeAgentFilter === 'online') {
            var dateText = $('#from_date').val() ? $('#from_date').val() : 'Today';
            if (dateText === moment().format('YYYY-MM-DD')) dateText = 'Today';
            chipContainer.html(
                '<div style="display: inline-flex; align-items: center; gap: 8px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 999px; padding: 4px 12px; font-size: 12.5px; font-weight: 600; color: #047857;">' +
                '<i class="fas fa-circle" style="font-size: 7px; color: #10B981;"></i>' +
                '<span>Filter: Online Agents · ' + dateText + '</span>' +
                '<button type="button" onclick="clearAgentFilter()" style="border: none; background: transparent; cursor: pointer; color: #047857; font-weight: 700; font-size: 15px; padding: 0; margin-left: 4px; line-height: 1;" title="Clear filter">&times;</button>' +
                '</div>'
            ).show();
        } else {
            chipContainer.hide().empty();
        }
    }

    window.clearAgentFilter = function() {
        window.activeAgentFilter = '';
        renderAgentFilterChip();
        if (window.agentDataTable) {
            window.agentDataTable.draw();
        }
    };

    // Initialize LeadGER Flatpickr Datepickers
    var fromPicker = flatpickr("#from_date", {
        dateFormat: "Y-m-d",
        allowInput: false,
        clickOpens: true,
        onChange: function(selectedDates, dateStr) {
            if (!dateStr) return;
            hideDateError();
            toPicker.set("minDate", dateStr);

            var curTo = $('#to_date').val();
            if (curTo && curTo < dateStr) {
                toPicker.setDate(dateStr, false);
            }
            handleFilterChange();
        }
    });

    var toPicker = flatpickr("#to_date", {
        dateFormat: "Y-m-d",
        allowInput: false,
        clickOpens: true,
        onChange: function(selectedDates, dateStr) {
            if (!dateStr) return;
            var curFrom = $('#from_date').val();
            if (curFrom && dateStr < curFrom) {
                showDateError("'To Date' cannot be earlier than 'From Date'.");
                return;
            }
            hideDateError();
            handleFilterChange();
        }
    });

    // Clicking anywhere inside input box or calendar icon opens calendar popup
    $('#lgFromDateBox').on('click', function(e) {
        if (!$(e.target).is('#from_date')) {
            fromPicker.open();
        }
    });

    $('#lgToDateBox').on('click', function(e) {
        if (!$(e.target).is('#to_date')) {
            toPicker.open();
        }
    });

    // Parse URL params for pre-filtering (e.g. from Dashboard "Online Agents" card)
    var urlParams = new URLSearchParams(window.location.search);
    var paramFilter = urlParams.get('filter');
    var paramFrom = urlParams.get('from_date');
    var paramTo = urlParams.get('to_date');

    window.activeAgentFilter = paramFilter || '';

    if (paramFrom) {
        var fromVal = (paramFrom === 'today') ? moment().format('YYYY-MM-DD') : paramFrom;
        $('#from_date').val(fromVal);
        fromPicker.setDate(fromVal, false);
    }
    if (paramTo) {
        var toVal = (paramTo === 'today') ? moment().format('YYYY-MM-DD') : paramTo;
        $('#to_date').val(toVal);
        toPicker.setDate(toVal, false);
    }

    // Agent / campaign from the URL too (set when a summary card was clicked, so Back restores every filter)
    ['agent_id', 'campaign_id'].forEach(function (key) {
        var val = urlParams.get(key);
        var $sel = $(key === 'agent_id' ? '#agentFilterSelect' : '#campaignFilterSelect');
        if (val && $sel.find('option[value="' + String(val).replace(/[^0-9a-z]/gi, '') + '"]').length) {
            $sel.val(val);
        }
    });

    renderAgentFilterChip();

    // Summary cards: open the existing page that lists the records behind each number, with the same filters
    var lgCanViewStaff = {{ @Auth::user()->permissionsGroup->settings_status ? 'true' : 'false' }};
    function lgKpiNavigate(kpi) {
        var f = {
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val(),
            agent_id: $('#agentFilterSelect').val() || 'all',
            campaign_id: $('#campaignFilterSelect').val() || 'all'
        };
        // Keep the current filters in this page's URL so the browser Back button returns to the same view
        var here = new URLSearchParams(window.location.search);
        Object.keys(f).forEach(function (k) { if (f[k]) here.set(k, f[k]); });
        history.replaceState(null, '', window.location.pathname + '?' + here.toString());

        var agentSelected = f.agent_id && f.agent_id !== 'all';
        if (kpi === 'agents') {
            if (!lgCanViewStaff) {
                var tableCard = document.getElementById('agentTableCard');
                if (tableCard) tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            var staffUrl = "{{ route('users') }}";
            if (agentSelected) staffUrl += '?search=' + encodeURIComponent($('#agentFilterSelect option:selected').text().trim());
            window.location.href = staffUrl;
            return;
        }

        var q = new URLSearchParams({ scope: 'agents', from_date: f.from_date, to_date: f.to_date });
        if (agentSelected) {
            q.set('telecaller_id', f.agent_id);
            q.set('agent_name', $('#agentFilterSelect option:selected').text().trim());
        }
        if (f.campaign_id && f.campaign_id !== 'all') q.set('find_campaign', f.campaign_id);
        if (kpi === 'connected') q.set('status', 'connected');
        if (kpi === 'conversions' || kpi === 'conversion_rate') q.set('status', 'converted');
        if (kpi === 'avg_talk' || kpi === 'conversion_rate') q.set('metric', kpi);
        window.location.href = "{{ route('callReport') }}?" + q.toString();
    }
    $('.lg-kpi-link').on('click', function () {
        lgKpiNavigate($(this).data('kpi'));
    }).on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            lgKpiNavigate($(this).data('kpi'));
        }
    });

    if (window.activeAgentFilter) {
        setTimeout(function() {
            var el = document.getElementById('agentTableCard');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 350);
    }

    $('#tableSearchInput').on('input keyup', function () {
        handleSearchInput($(this).val());
    });

    var table_name = "#leads_table";
    var lgApCanExport = @json($canExportAgentPerformance);

    window.agentDataTable = $(table_name).DataTable({
        "processing": true,
        "serverSide": true,
        "searching": true,
        "responsive": false,
        "scrollX": false,
        "autoWidth": false,
        "pageLength": {{ env('BACKEND_PAGINATION', 10) }},
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "ajax": {
            "url": "{{ route('agentPerformanceData') }}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {
                data._token = "{{csrf_token()}}";
                data.from_date = $('#from_date').val();
                data.to_date = $('#to_date').val();
                data.agent_id = $('#agentFilterSelect').val();
                data.campaign_id = $('#campaignFilterSelect').val();
                data.find_q = $('#tableSearchInput').val();
                data.filter = window.activeAgentFilter || '';
            }
        },
        "dom": lgApCanExport
            ? '<"table-responsive"t><"lg-table-footer has-export"<"lg-ap-export-slot"><"lg-footer-right"<"lg-footer-info"i><"lg-footer-pagination"p>>>'
            : '<"table-responsive"t><"lg-table-footer"<"lg-footer-info"i><"lg-footer-pagination"p>>',
        "initComplete": function () {
            if (lgApCanExport) $('#agentTableCard .lg-ap-export-slot').append($('#lgApExport'));
        },
        "fnDrawCallback": function (settings) {
            var api = this.api();
            var json = api.ajax.json();
            lgApSyncExportButtons(json ? (json.recordsFiltered || 0) : 0);

            if (json) {
                var totalAgents = json.recordsFiltered !== undefined ? json.recordsFiltered : (json.recordsTotal || 0);
                $('#kpiTotalAgents').text(totalAgents);
                $('#kpiTotalAgentsSub').text(totalAgents + ' Active agents');

                var s = json.statics || {};
                var totalCalls = s.total_calls !== undefined ? s.total_calls : 0;
                var connectedCalls = s.connected_calls !== undefined ? s.connected_calls : 0;
                var conversions = s.conversions !== undefined ? s.conversions : 0;
                var avgTalkTime = s.avg_talk_time !== undefined ? s.avg_talk_time : '0 mins';
                var conversionRate = s.conversion_rate !== undefined ? s.conversion_rate : '0%';

                $('#kpiTotalCalls').text(parseInt(totalCalls, 10).toLocaleString());
                $('#kpiConnectedCalls').text(parseInt(connectedCalls, 10).toLocaleString());
                $('#kpiConversions').text(parseInt(conversions, 10).toLocaleString());
                $('#kpiAvgTalkTime').text(avgTalkTime);
                $('#kpiConversionRate').text(conversionRate);

                function applyTrend(elId, trend) {
                    var el = $('#' + elId);
                    if (!el.length) return;
                    if (!trend || !trend.has_data || trend.text === '—') {
                        el.removeClass('lg-trend-up lg-trend-down').addClass('lg-trend-neutral').text('—');
                    } else if (trend.direction === 'up') {
                        el.removeClass('lg-trend-down lg-trend-neutral').addClass('lg-trend-up').text(trend.text);
                    } else if (trend.direction === 'down') {
                        el.removeClass('lg-trend-up lg-trend-neutral').addClass('lg-trend-down').text(trend.text);
                    } else {
                        el.removeClass('lg-trend-up lg-trend-down').addClass('lg-trend-neutral').text(trend.text);
                    }
                }

                var trends = s.trends || {};
                applyTrend('trendTotalCalls', trends.total_calls);
                applyTrend('trendConnectedCalls', trends.connected_calls);
                applyTrend('trendConversions', trends.conversions);
                applyTrend('trendAvgTalkTime', trends.avg_talk_time);
                applyTrend('trendConversionRate', trends.conversion_rate);
            }

            if ($(table_name + '_paginate .paginate_button').length > 3) {
                $(table_name + '_paginate').show();
            } else {
                $(table_name + '_paginate').hide();
            }
        },
        "columns": [
            {
                "data": "uname",
                "orderable": true,
                "render": function(data, type, row) {
                    var name = data || row.agent_name || 'Unknown Agent';
                    var initial = name.charAt(0).toUpperCase();
                    return '<div class="lg-agent-cell">' +
                           '<div class="lg-avatar">' + initial + '</div>' +
                           '<span class="lg-agent-name">' + name + '</span>' +
                           '</div>';
                }
            },
            {
                "data": "role_name",
                "orderable": true,
                "render": function(data, type, row) {
                    var role = data || row.role || 'TeleCaller';
                    return '<span class="lg-role-badge">' + role + '</span>';
                }
            },
            {
                "data": "login",
                "orderable": true,
                "render": function(data, type, row) {
                    var loginVal = data || row.login_time;
                    return formatCompactDateTime(loginVal);
                }
            },
            {
                "data": "logout",
                "orderable": true,
                "render": function(data, type, row) {
                    var logoutVal = data || row.logout_time;
                    var loginVal = row.login || row.login_time;
                    var isOnline = row.online !== undefined ? row.online : (!emptyVal(loginVal) && emptyVal(logoutVal));
                    if (isOnline) {
                        return '<span class="lg-status-pill status-active" style="display: inline-flex; align-items: center; gap: 5px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 7px; color: #10B981;"></i> Currently Online</span>';
                    }
                    return formatCompactDateTime(logoutVal);
                }
            },
            {
                "data": "call_count",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? data : (row.total_calls || 0);
                    return '<span class="fw-semibold">' + val + '</span>';
                }
            },
            {
                "data": "connected_count",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? data : (row.connected || row.call_log_count || 0);
                    return '<span class="fw-semibold">' + val + '</span>';
                }
            },
            {
                "data": "total_time",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = data || row.talk_time || row.avg_call_time || '00:00:00';
                    return '<span class="text-muted">' + val + '</span>';
                }
            },
            {
                "data": "interested_count",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? data : (row.interested || 0);
                    return '<span class="fw-semibold">' + val + '</span>';
                }
            },
            {
                "data": "converted_count",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? data : (row.converted || 0);
                    return '<span class="fw-semibold">' + val + '</span>';
                }
            },
            {
                "data": "conversion_rate",
                "orderable": true,
                "render": function(data) {
                    return '<span class="fw-semibold text-primary">' + (data || '0%') + '</span>';
                }
            }
        ],
        "order": [[0, "asc"]]
    });

    $.fn.dataTable.ext.errMode = 'none';
});

/* Excel / CSV export of the details table: every row for the current filters, search and sorting (built by the server) */
var lgApExportBusy = false;
var lgApRowCount = null;

function lgApToast(message, type) {
    if (typeof toastr !== 'undefined') {
        type === 'error' ? toastr.error(message) : (type === 'info' ? toastr.info(message) : toastr.success(message));
    }
}

// Disabled while a file is being prepared, and when the current filters match no rows
function lgApSyncExportButtons(rowCount) {
    if (rowCount !== undefined) lgApRowCount = rowCount;
    var empty = lgApRowCount === 0;
    $('#lgApExport .lg-ap-export-btn').each(function () {
        var $b = $(this);
        $b.prop('disabled', lgApExportBusy || empty);
        if (!$b.data('title')) $b.data('title', $b.attr('title'));
        $b.attr('title', empty ? 'No data to export' : $b.data('title'));
    });
}

function lgApExport(format, btn) {
    if (lgApExportBusy) return;
    if (lgApRowCount === 0) {
        lgApToast('No data to export', 'info');
        return;
    }
    var from = $('#from_date').val();
    var to = $('#to_date').val();
    if (from && to && to < from) {
        lgApToast("'To Date' cannot be earlier than 'From Date'.", 'error');
        return;
    }

    var params = new URLSearchParams({ from_date: from, to_date: to });
    var agent = $('#agentFilterSelect').val();
    var campaign = $('#campaignFilterSelect').val();
    var search = ($('#tableSearchInput').val() || '').trim();
    if (agent && agent !== 'all') params.set('agent_id', agent);
    if (campaign && campaign !== 'all') params.set('campaign_id', campaign);
    if (search) params.set('search', search);
    if (window.activeAgentFilter === 'online') params.set('filter', 'online');
    var dt = window.agentDataTable;
    if (dt) {
        var ord = dt.order()[0];
        if (ord) {
            params.set('order_column', dt.column(ord[0]).dataSrc());
            params.set('order_dir', ord[1] === 'desc' ? 'desc' : 'asc');
        }
    }

    lgApExportBusy = true;
    $(btn).addClass('is-loading').attr('aria-busy', 'true');
    lgApSyncExportButtons();
    function done() {
        lgApExportBusy = false;
        $(btn).removeClass('is-loading').removeAttr('aria-busy');
        lgApSyncExportButtons();
    }

    var fallbackName = 'agent-performance-' + from + '-to-' + to + '.' + format;
    var exportUrl = "{{ route('agentPerformanceExport', ['format' => 'csv']) }}".replace(/csv$/, format);
    fetch(exportUrl + '?' + params.toString(), {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
    }).then(function (res) {
        var type = res.headers.get('Content-Type') || '';
        // errors come back as JSON; an expired session redirects to the login page (HTML)
        if (!res.ok || type.indexOf('application/json') !== -1 || type.indexOf('text/html') !== -1) {
            return res.json().catch(function () { return {}; }).then(function (j) {
                throw new Error((j && j.message) || 'The report could not be exported. Please try again.');
            });
        }
        var cd = res.headers.get('Content-Disposition') || '';
        var m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd);
        var name = m ? decodeURIComponent(m[1]) : fallbackName;
        return res.blob().then(function (blob) { return { blob: blob, name: name }; });
    }).then(function (file) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(file.blob);
        a.download = file.name;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
        done();
    }).catch(function (err) {
        done();
        lgApToast(err && err.message ? err.message : 'The report could not be exported. Please try again.', err && err.message === 'No data to export' ? 'info' : 'error');
    });
}

$(document).on('click', '#lgApExport .lg-ap-export-btn', function () {
    lgApExport($(this).data('format'), this);
});

function print_as(stat) {
    if (stat === 'excel') {
        var table = document.getElementById('leads_table');
        if (!table) {
            if (typeof toastr !== 'undefined') toastr.error('Table element not found');
            return;
        }
        if (typeof XLSX === 'undefined') {
            if (typeof toastr !== 'undefined') toastr.error('Excel library is loading, please try again.');
            return;
        }
        try {
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(table);

            Object.keys(ws).forEach(cell => {
                if (ws[cell] && ws[cell].v) {
                    let value = ws[cell].v.toString();
                    if (value.match(/^\d{10,}$/)) {
                        ws[cell].t = 's';
                    }
                    if (value.match(/^\d{4}-\d{2}-\d{2}/)) {
                        ws[cell].t = 's';
                    }
                }
            });

            let columnWidths = [];
            if (ws['!ref']) {
                let range = XLSX.utils.decode_range(ws['!ref']);
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    columnWidths.push({ wch: 20 });
                }
                ws['!cols'] = columnWidths;
            }

            XLSX.utils.book_append_sheet(wb, ws, "AgentPerformance");
            XLSX.writeFile(wb, "agent_performance_report.xlsx");
            if (typeof toastr !== 'undefined') toastr.success('Report exported successfully');
        } catch(err) {
            console.error('Export error:', err);
            if (typeof toastr !== 'undefined') toastr.error('Failed to export Excel file.');
        }
    }
}
</script>
@endpush
