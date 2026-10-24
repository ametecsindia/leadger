@extends('dashboard.layouts.master')
@section('title', 'Call Recordings')
@push("after-styles")
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">

    <style>
        /* ==========================================================================
           LEADGER RECORDINGS COMMAND CENTER - DESIGN SYSTEM
           ========================================================================== */
        
        .lg-recordings-page {
            padding: 24px 28px 40px 28px;
            background: #F8FAFC;
            min-height: calc(100vh - 60px);
            font-family: var(--lg-font-sans);
            color: #0F172A;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow-x: hidden;
        }

        /* --- Header Section --- */
        .lg-header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            width: 100%;
            box-sizing: border-box;
        }
        .lg-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .lg-header-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #EFF6FF;
            border: 1px solid #DBEAFE;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--lg-accent);
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(23, 105, 255, 0.08);
        }
        .lg-header-titles {
            display: flex;
            flex-direction: column;
        }
        .lg-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748B;
            font-weight: 500;
            margin-bottom: 2px;
        }
        .lg-breadcrumb a {
            color: #64748B;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .lg-breadcrumb a:hover {
            color: var(--lg-accent);
        }
        .lg-breadcrumb .sep {
            color: #CBD5E1;
            font-size: 11px;
        }
        .lg-breadcrumb .active {
            color: #0F172A;
            font-weight: 600;
        }
        .lg-page-title {
            font-size: 22px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.3px;
        }
        .lg-page-subtitle {
            font-size: 13px;
            font-weight: 400;
            color: #64748B;
            margin-top: 3px;
        }
        .lg-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- Filter Container --- */
        .lg-filters-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
            width: 100%;
            box-sizing: border-box;
        }
        .lg-filters-flex-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            width: 100%;
            flex-wrap: wrap;
        }
        .lg-filter-col {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }
        .lg-col-search   { flex: 1.4 1 140px; min-width: 110px; }
        .lg-col-source   { flex: 1 1 110px; min-width: 95px; }
        .lg-col-campaign { flex: 1 1 110px; min-width: 95px; }
        .lg-col-agent    { flex: 1 1 110px; min-width: 95px; }
        .lg-col-date     { flex: 0.9 1 100px; min-width: 90px; }
        .lg-col-actions  { flex: 0 0 auto; display: flex; flex-direction: row; align-items: flex-end; gap: 8px; margin-left: auto; }
        /* Keep every filter box inside its own column (no overlap) */
        .lg-filter-col > div { width: 100%; min-width: 0; }
        .lg-filter-col .lg-select-input,
        .lg-filter-col .lg-emp-dropdown-btn,
        .lg-filter-col .lg-input-date,
        .lg-filter-col .lg-input-search { width: 100% !important; max-width: 100%; box-sizing: border-box; }
        .lg-filter-col .select2-container { width: 100% !important; max-width: 100%; }
        .lg-filter-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lg-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0.1px;
            display: block;
        }

        /* Form Inputs & Selects */
        .lg-search-wrap, .lg-select-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .lg-search-icon-btn {
            position: absolute;
            left: 8px;
            background: none;
            border: none;
            padding: 4px;
            color: #475569;
            cursor: pointer;
            pointer-events: auto;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            transition: color 0.15s ease, background 0.12s ease;
        }
        .lg-search-icon-btn:hover {
            color: var(--lg-accent);
            background: #EFF6FF;
        }
        .lg-search-clear-btn {
            position: absolute;
            right: 9px;
            background: none;
            border: none;
            padding: 3px 5px;
            color: #94A3B8;
            cursor: pointer;
            pointer-events: auto;
            z-index: 2;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            line-height: 1;
            border-radius: 4px;
            transition: color 0.15s ease;
        }
        .lg-search-clear-btn:hover {
            color: #DC2626;
        }
        .lg-input-search {
            width: 100%;
            height: 40px;
            padding: 0 30px 0 34px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 9px;
            font-size: 12.5px;
            color: #0F172A;
            font-weight: 500;
            transition: all 0.15s ease;
            outline: none;
            box-sizing: border-box;
        }
        .lg-input-search::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .lg-select-input {
            width: 100%;
            height: 40px;
            padding: 0 32px 0 10px;
            background-color: #FFFFFF !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: calc(100% - 10px) center !important;
            background-size: 12px 12px !important;
            border: 1px solid #CBD5E1;
            border-radius: 9px;
            font-size: 12.5px;
            color: #0F172A;
            font-weight: 500;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            cursor: pointer;
            transition: all 0.15s ease;
            outline: none;
            box-sizing: border-box;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .lg-select-input::-ms-expand {
            display: none !important;
        }
        .lg-select-wrap .lg-select-arrow {
            display: none !important;
        }
        .lg-select-arrow {
            position: absolute;
            right: 8px;
            pointer-events: none;
            color: #64748B;
        }

        /* Calendar Date Input */
        .lg-date-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .lg-input-date {
            width: 100%;
            height: 40px;
            padding: 0 42px 0 10px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 9px;
            font-size: 12.5px;
            color: #0F172A;
            font-weight: 500;
            transition: all 0.15s ease;
            outline: none;
            box-sizing: border-box;
            cursor: pointer;
        }
        .lg-input-date::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .lg-date-clear-btn {
            position: absolute;
            right: 26px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #64748B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-date-clear-btn:hover {
            background: #CBD5E1;
            color: #0F172A;
        }
        .lg-date-icon {
            position: absolute;
            right: 8px;
            color: #64748B;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease;
        }
        .lg-date-icon:hover {
            color: var(--lg-accent);
        }

        .lg-input-search:hover, .lg-select-input:hover, .lg-input-date:hover {
            background: #FFFFFF;
            border-color: #94A3B8;
        }
        .lg-input-search:focus, .lg-select-input:focus, .lg-input-date:focus {
            background: #FFFFFF;
            border-color: var(--lg-accent);
            box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
        }

        /* Action Buttons */
        .lg-btn-primary-filter {
            height: 40px;
            padding: 0 16px;
            background: var(--lg-accent);
            color: #FFFFFF !important;
            border: none;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(23, 105, 255, 0.22);
            transition: all 0.15s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .lg-btn-primary-filter:hover {
            background: #0F52CC;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 105, 255, 0.3);
        }
        .lg-btn-secondary-reset {
            height: 40px;
            width: 40px;
            min-width: 40px;
            background: #FFFFFF;
            color: #475569 !important;
            border: 1px solid #CBD5E1;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        .lg-btn-secondary-reset:hover {
            background: #F8FAFC;
            color: #0F172A !important;
            border-color: #94A3B8;
            transform: translateY(-1px);
        }

        /* Modal Backdrop & Overlap Fixes */
        body.modal-open {
            padding-right: 0 !important;
            overflow: hidden !important;
        }
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.5) !important;
            backdrop-filter: blur(3px) !important;
        }
        .lead-details-modal {
            z-index: 1060 !important;
            padding-right: 0 !important;
        }
        .lead-details-modal .modal-dialog {
            max-width: 920px;
            margin: 20px auto;
            width: 94%;
            max-height: calc(100vh - 40px);
            display: flex;
            align-items: center;
        }
        .lead-details-modal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            background: #FFFFFF;
            width: 100%;
            max-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1080px) and (min-width: 721px) {
            .lg-filters-flex-row {
                flex-wrap: wrap;
                gap: 12px 10px;
            }
            .lg-col-search { flex: 1.4 1 calc(33.333% - 10px); }
            .lg-col-source { flex: 1.0 1 calc(33.333% - 10px); }
            .lg-col-campaign { flex: 1.0 1 calc(33.333% - 10px); }
            .lg-col-agent { flex: 1.1 1 calc(25% - 10px); }
            .lg-col-ai { flex: 1.0 1 calc(25% - 10px); }
            .lg-col-date { flex: 1.0 1 calc(25% - 10px); }
            .lg-col-actions { flex: 0 0 auto; margin-left: auto; }
        }
        @media (max-width: 720px) {
            .lg-filters-flex-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .lg-filter-col {
                flex: 1 1 100%;
                width: 100%;
            }
            .lg-col-actions {
                flex-direction: row;
                width: 100%;
                justify-content: flex-end;
                margin-top: 4px;
            }
            .lg-btn-primary-filter {
                flex: 1 1 auto;
            }
            .lg-btn-secondary-reset {
                flex: 0 0 46px;
            }
        }

        /* Custom Corporate Flatpickr Calendar Styling */
        .flatpickr-calendar {
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 14px !important;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12) !important;
            font-family: var(--lg-font-sans) !important;
            width: 290px !important;
            padding: 8px !important;
            margin-top: 4px !important;
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
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #0F172A !important;
            padding: 2px 0 0 0 !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 700 !important;
            color: #0F172A !important;
            appearance: none !important;
            background: transparent !important;
        }
        .flatpickr-current-month input.cur-year {
            font-weight: 700 !important;
            color: #0F172A !important;
        }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            color: #475569 !important;
            fill: #475569 !important;
            padding: 8px 10px !important;
            border-radius: 6px !important;
            transition: all 0.15s ease !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            color: var(--lg-accent) !important;
            fill: var(--lg-accent) !important;
            background: #EFF6FF !important;
        }
        .flatpickr-weekdays {
            margin-bottom: 4px !important;
        }
        span.flatpickr-weekday {
            color: #64748B !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        .flatpickr-days {
            width: 274px !important;
        }
        .dayContainer {
            width: 274px !important;
            min-width: 274px !important;
            max-width: 274px !important;
        }
        .flatpickr-day {
            color: #1E293B !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            font-size: 13px !important;
            height: 36px !important;
            line-height: 36px !important;
            max-width: 36px !important;
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
        .flatpickr-day.today:hover {
            background: #EFF6FF !important;
            color: var(--lg-accent) !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: var(--lg-accent) !important;
            border-color: var(--lg-accent) !important;
            color: #FFFFFF !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 6px rgba(23, 105, 255, 0.3) !important;
        }
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #CBD5E1 !important;
            background: transparent !important;
            border-color: transparent !important;
            cursor: not-allowed !important;
        }

        /* --- Table Badges & Cell Typography --- */
        .lg-lead-id-pill {
            background: #EFF6FF;
            color: var(--lg-accent) !important;
            font-size: 12.5px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none !important;
            transition: all 0.15s ease;
            border: 1px solid #DBEAFE;
        }
        .lg-lead-id-pill:hover {
            background: #DBEAFE;
            color: #0F52CC !important;
            transform: scale(1.02);
        }
        .lg-id-tag {
            background: #F1F5F9;
            color: #475569;
            font-size: 12.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid #E2E8F0;
        }

        .lg-source-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .lg-source-badge.campaign {
            background: #EFF6FF;
            color: var(--lg-accent);
            border: 1px solid #DBEAFE;
        }
        .lg-source-badge.dialing {
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #DCFCE7;
        }
        /* Type column: calling method */
        .lg-source-badge.click2call { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .lg-source-badge.webrtc { background: #EFF6FF; color: var(--lg-accent); border: 1px solid #BFDBFE; }
        .lg-source-badge.other { background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }

        .lg-campaign-text {
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
        .lg-lead-name-text {
            color: #0F172A;
            font-size: 13.5px;
            font-weight: 600;
            white-space: nowrap;
        }
        .lg-phone-text {
            font-size: 13px;
            color: #334155;
            font-weight: 500;
            white-space: nowrap;
        }
        .lg-filename-text {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            font-size: 12px;
            color: #64748B;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        /* Speaker-wise English conversation in the transcript popup */
        .lg-conv-turn { border-radius: 10px; padding: 8px 12px; margin-bottom: 8px; border: 1px solid #E2E8F0; background: #FFFFFF; white-space: normal; }
        .lg-conv-turn .lg-conv-who { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 2px; }
        .lg-conv-turn .lg-conv-time { font-weight: 600; color: #94A3B8; font-variant-numeric: tabular-nums; }
        .lg-conv-turn .lg-conv-text { font-size: 13px; line-height: 1.55; color: #0F172A; }
        .lg-conv-turn.agent { background: #EFF6FF; border-color: #BFDBFE; margin-right: 28px; }
        .lg-conv-turn.agent .lg-conv-who { color: #1D4ED8; }
        .lg-conv-turn.customer { background: #ECFDF5; border-color: #A7F3D0; margin-left: 28px; }
        .lg-conv-turn.customer .lg-conv-who { color: #047857; }
        .lg-conv-turn.s2 { background: #F8FAFC; margin-left: 28px; }
        .lg-conv-turn.s1 { margin-right: 28px; }
        .lg-conv-role-btn { border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; border-radius: 6px; padding: 2px 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .lg-conv-role-btn.active { background: var(--lg-accent); border-color: var(--lg-accent); color: #FFFFFF; }
        .lg-transcribe-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 6px; cursor: pointer;
            font-size: 12px; font-weight: 600; line-height: 1.4;
            color: var(--lg-accent); background: #EFF6FF; border: 1px solid #BFDBFE;
        }
        .lg-transcribe-btn:hover { background: #DBEAFE; }
        .lg-transcribe-btn:disabled { opacity: .6; cursor: wait; }
        .lg-transcribe-btn.retry { margin-top: 4px; color: #B45309; background: #FFFBEB; border-color: #FCD34D; }
        .lg-ai-error {
            max-width: 170px; margin-top: 3px; font-size: 11px; color: #B91C1C;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .lg-duration-text {
            font-size: 12.5px;
            color: #334155;
            font-weight: 600;
            font-family: 'Consolas', 'Monaco', monospace;
            white-space: nowrap;
        }
        .lg-date-text {
            font-size: 12px;
            color: #64748B;
            white-space: nowrap;
        }

        /* --- Status Badges --- */
        .lg-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .lg-status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .lg-status-badge.green {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .lg-status-badge.green .dot {
            background: #10B981;
        }
        .lg-status-badge.amber {
            background: #FFFBEB;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        .lg-status-badge.amber .dot {
            background: #F59E0B;
        }
        .lg-status-badge.blue {
            background: #EFF6FF;
            color: var(--lg-accent);
            border: 1px solid #DBEAFE;
        }
        .lg-status-badge.blue .dot {
            background: #3B82F6;
        }
        .lg-status-badge.gray {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #E2E8F0;
        }
        .lg-status-badge.gray .dot {
            background: #94A3B8;
        }


        /* Responsive: wrap footer on small screens */
        @media (max-width: 1200px) {
            .lg-filters-grid {
                grid-template-columns: 2fr 1fr 1fr 1fr;
            }
        }
        @media (max-width: 768px) {
            .lg-recordings-page {
                padding: 16px;
            }
            .lg-header-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .lg-filters-grid {
                grid-template-columns: 1fr;
            }
        }

        /* --- Selective Call Transcription & Client Billing Bar --- */
        .lg-bulk-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 12px;
            margin: 0 0 16px 0;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.12);
            border: 1px solid #334155;
            animation: lgFadeInSlide 0.22s ease-out;
        }
        @keyframes lgFadeInSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .lg-bulk-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lg-bulk-count-badge {
            background: var(--lg-accent);
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 700;
            padding: 5px 13px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(23, 105, 255, 0.3);
        }
        .lg-bulk-summary-info {
            font-size: 13px;
            color: #94A3B8;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .lg-bulk-dot {
            color: #475569;
        }
        .lg-bulk-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lg-btn-bulk-deselect {
            background: transparent;
            color: #94A3B8;
            border: 1px solid #475569;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-btn-bulk-deselect:hover {
            color: #FFFFFF;
            background: #334155;
            border-color: #64748B;
        }
        .lg-btn-bulk-transcript {
            background: var(--lg-accent);
            color: #FFFFFF !important;
            border: none;
            padding: 7px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 2px 8px rgba(23, 105, 255, 0.35);
            transition: all 0.15s ease;
        }
        .lg-btn-bulk-transcript:hover {
            background: #0F52CC;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(23, 105, 255, 0.45);
        }
        .lg-btn-bulk-download {
            background: #1E3A8A;
            color: #FFFFFF !important;
            border: 1px solid #3B5BDB;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .lg-btn-bulk-download:hover:not(:disabled) {
            background: #1D4ED8;
            border-color: #60A5FA;
            transform: translateY(-1px);
        }
        .lg-btn-bulk-download:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .lg-recording-row-cb, .lg-table-header-cb {
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: var(--lg-accent);
            margin: 0;
            vertical-align: middle;
        }

        /* Employee Multi-Select Dropdown */
        .lg-emp-dropdown-wrap {
            position: relative;
            width: 100%;
        }
        .lg-emp-dropdown-btn {
            width: 100%;
            height: 40px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 0 30px 0 12px;
            font-size: 13px;
            font-weight: 500;
            color: #1E293B;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
        }
        .lg-emp-dropdown-btn:hover {
            border-color: var(--lg-accent);
        }
        .lg-emp-label-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            max-width: 130px;
        }
        .lg-emp-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 250px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
            z-index: 1050;
            padding: 10px;
            font-size: 13px;
        }
        .lg-emp-menu-header {
            margin-bottom: 8px;
        }
        .lg-emp-search-input {
            width: 100%;
            height: 32px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            padding: 0 10px;
            font-size: 12px;
            outline: none;
            box-sizing: border-box;
        }
        .lg-emp-search-input:focus {
            border-color: var(--lg-accent);
            box-shadow: 0 0 0 2px rgba(23,105,255,0.12);
        }
        .lg-emp-select-all-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 6px 8px 6px;
            border-bottom: 1px solid #F1F5F9;
            margin-bottom: 6px;
            font-size: 12px;
        }
        .lg-emp-list-container {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .lg-emp-cb-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            margin: 0;
            user-select: none;
            transition: background 0.12s ease;
        }
        .lg-emp-cb-label:hover {
            background: #F1F5F9;
        }
        .lg-emp-cb {
            width: 15px;
            height: 15px;
            accent-color: var(--lg-accent);
            cursor: pointer;
            margin: 0;
        }
    </style>
    @include('dashboard.reports.partials.recording-table-styles')
@endpush

@section('content')
<div class="lg-recordings-page">

    <!-- Header Section -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
            </div>
            <div class="lg-header-titles">
                <div class="lg-breadcrumb">
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a>
                    <span class="sep">/</span>
                    <span>Records</span>
                    <span class="sep">/</span>
                    <span class="active">All Records</span>
                </div>
                <h1 class="lg-page-title">Call Recordings</h1>
                <div class="lg-page-subtitle">Manage, review, and analyze all call recordings.</div>
            </div>
        </div>

    </div>


        <!-- Filters Section -->
        <div class="lg-filters-card" id="filter_div">
            <div class="lg-filters-flex-row">
                <!-- 1. Search Box -->
                <div class="lg-filter-col lg-col-search">
                    <label class="lg-filter-label" for="find_q">Search</label>
                    <div class="lg-search-wrap">
                        <button type="button" class="lg-search-icon-btn" id="search_icon_btn" title="Search">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                        <input type="text" id="find_q" class="lg-input-search" placeholder="Search by ID, name, mobile..." autocomplete="off">
                        <button type="button" class="lg-search-clear-btn" id="search_clear_btn" title="Clear search">&times;</button>
                    </div>
                </div>

                <!-- 2. Recording Source Filter -->
                <div class="lg-filter-col lg-col-source">
                    <label class="lg-filter-label" for="find_source">Recording Source</label>
                    <div class="lg-select-wrap">
                        <select id="find_source" class="lg-select-input">
                            <option value="">All Sources</option>
                            <option value="Campaign Calling">Campaign Calling</option>
                            <option value="Dialing">Dialing</option>
                        </select>
                    </div>
                </div>

                <!-- 3. Campaign Filter -->
                <div class="lg-filter-col lg-col-campaign">
                    <label class="lg-filter-label" for="find_campaign">Campaign</label>
                    <div class="lg-select-wrap">
                        <select id="find_campaign" class="lg-select-input">
                            <option value="">All Campaigns</option>
                            @foreach($Campaigns as $Campaign)
                                <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 4. Employee/Agent Multi-Select Filter -->
                <div class="lg-filter-col lg-col-agent" style="position: relative;">
                    <label class="lg-filter-label">Employee / Agent</label>
                    <div class="lg-emp-dropdown-wrap">
                        <button type="button" class="lg-emp-dropdown-btn" id="lg_emp_dropdown_btn" onclick="toggleEmpDropdown(event)">
                            <span id="lg_emp_dropdown_label" class="lg-emp-label-text">All Employees</span>
                            <svg class="lg-select-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="lg-emp-dropdown-menu" id="lg_emp_dropdown_menu" style="display: none;" onclick="event.stopPropagation();">
                            <div class="lg-emp-menu-header">
                                <input type="text" id="lg_emp_search_input" class="lg-emp-search-input" placeholder="Search employee..." onkeyup="filterEmpList()">
                            </div>
                            <div class="lg-emp-select-all-row">
                                <label class="lg-emp-cb-label">
                                    <input type="checkbox" id="lg_emp_select_all" onchange="toggleAllEmployees(this.checked)">
                                    <span class="font-weight-bold" style="font-size: 12px; color: #1E293B;">Select All</span>
                                </label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-muted" onclick="clearAllEmployees()" style="font-size: 11.5px; text-decoration: none;">Clear</button>
                            </div>
                            <div class="lg-emp-list-container" id="lg_emp_list">
                                @php
                                    $empList = isset($Users) ? $Users : (isset($Telecallers) ? $Telecallers : []);
                                @endphp
                                @foreach($empList as $user)
                                    <label class="lg-emp-cb-label lg-emp-item">
                                        <input type="checkbox" class="lg-emp-cb" value="{{ $user->id }}" data-name="{{ $user->name }}" onchange="onEmpSelectChange()">
                                        <span style="font-size: 12.5px; color: #334155;">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" id="telecaller_id" value="">
                    </div>
                </div>

                <!-- 5. From Date -->
                <div class="lg-filter-col lg-col-date">
                    <label class="lg-filter-label" for="from_date">From Date</label>
                    <div class="lg-date-wrap">
                        <input type="text" id="from_date" class="lg-input-date" placeholder="From Date" autocomplete="off" readonly value="{{ now()->toDateString() }}">
                        <span class="lg-date-clear-btn" id="from_date_clear" title="Clear Date">&times;</span>
                        <span class="lg-date-icon" id="from_date_icon" title="Open Calendar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                    </div>
                </div>

                <!-- 7. To Date -->
                <div class="lg-filter-col lg-col-date">
                    <label class="lg-filter-label" for="to_date">To Date</label>
                    <div class="lg-date-wrap">
                        <input type="text" id="to_date" class="lg-input-date" placeholder="To Date" autocomplete="off" readonly value="{{ now()->toDateString() }}">
                        <span class="lg-date-clear-btn" id="to_date_clear" title="Clear Date">&times;</span>
                        <span class="lg-date-icon" id="to_date_icon" title="Open Calendar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                    </div>
                </div>

                <!-- 8 & 9. Action Buttons -->
                <div class="lg-filter-col lg-col-actions">
                    <button type="button" class="lg-btn-primary-filter" id="search-btn" title="Search recordings">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span>Search</span>
                    </button>
                    <button type="button" class="lg-btn-secondary-reset" id="reset-btn" title="Reset Filters">
                        <i class="fas fa-rotate-left"></i>
                    </button>
                </div>
            </div>
            <!-- Date range validation message alert -->
            <div id="date-range-error" style="display: none; margin-top: 10px; font-size: 12.5px; color: #DC2626; font-weight: 500; align-items: center; gap: 6px;">
                <i class="fas fa-exclamation-circle"></i>
                <span id="date-range-error-text">"To Date" cannot be earlier than "From Date".</span>
            </div>
        </div>

        @if(isset($sttConfigured) && !$sttConfigured)
        {{-- no valid Sarvam key for this company: admins get the way to add one, others whom to ask --}}
        <div class="alert" role="status" style="margin: 12px 0 0; border-radius: 10px; background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; font-size: 13px;">
            <i class="fas fa-exclamation-triangle me-1"></i>
            @if(optional(Auth::user()->permissionsGroup)->name === 'Admin')
                Add your Sarvam API key in <strong>Configuration → Profile → License Info</strong> to run transcripts.
                <a href="{{ route('usersEdit', Auth::id()) }}#license-info" style="font-weight: 700; color: #92400E; text-decoration: underline;">Open License Info</a>
            @else
                Transcription is not set up. Please ask your admin.
            @endif
        </div>
        @endif

        <!-- Selective Transcription Bulk Action Bar -->
        <div id="lg_bulk_action_bar" class="lg-bulk-action-bar" style="display: none;">
            <div class="lg-bulk-left">
                <div class="lg-bulk-count-badge">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span id="lg_selected_count_badge">0</span> recordings selected
                </div>
                <div class="lg-bulk-summary-info">
                    <span class="lg-bulk-dot">•</span>
                    <span id="lg_bulk_duration_summary">Total duration: 0 min</span>
                    <span class="lg-bulk-dot">•</span>
                    <span id="lg_bulk_rate_info">Provider: Sarvam AI</span>
                </div>
            </div>
            <div class="lg-bulk-right">
                <button type="button" class="lg-btn-bulk-deselect" onclick="lgClearSelection()" title="Clear Selection">
                    Deselect All
                </button>
                <button type="button" class="lg-btn-bulk-download" id="btn_download_selected" onclick="downloadSelectedRecordings()" title="Download the selected recordings as one ZIP file" disabled>
                    <i class="fas fa-download"></i>
                    <span>Download Selected</span>
                </button>
                @if(\App\Helpers\Helper::canDeleteRecordings())
                <button type="button" class="btn btn-sm btn-danger" id="btn_delete_selected" onclick="deleteSelectedRecordings()" style="background: #EF4444; border: 1px solid #DC2626; color: #FFF; font-weight: 600; border-radius: 8px; padding: 7px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <i class="fas fa-trash-alt"></i>
                    <span>Delete Selected</span>
                </button>
                @endif
                @if(!empty($canTranscribe))
                <button type="button" class="lg-btn-bulk-transcript" id="btn_transcript_selected" onclick="openBulkCostEstimateModal()">
                    <i class="fas fa-file-alt me-1"></i>
                    <span>Transcribe selected</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Recordings table -->
        <div class="table-card-container">
            <table class="table display nowrap" id="leads_table" style="width: 100%">
                <thead>
                    <tr>
                        <th style="width: 38px; text-align: center;">
                            <input type="checkbox" id="lg_select_all" class="lg-table-header-cb" title="Select All" onchange="lgToggleSelectAll(this.checked)">
                        </th>
                        <th>Lead ID</th>
                        <th>Employee Name</th>
                        <th>Mobile Number</th>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Duration</th>
                        <th>Date &amp; Time</th>
                        <th>Actions</th>
                        <th>AI Analysis</th>
                    </tr>
                </thead>
            </table>
        </div>
</div>

<!-- Transcription Cost Calculation & Confirmation Modal -->
<div id="lg-cost-estimate-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 60px rgba(15,23,42,0.25); overflow: hidden; background: #FFFFFF;">
            <!-- Modal Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, var(--lg-accent-hover) 0%, var(--lg-accent) 100%); color: #FFFFFF; padding: 18px 24px; border: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #FFFFFF; flex-shrink: 0;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold" style="font-size: 17px; margin: 0; color: #FFFFFF; line-height: 1.2;">
                            Transcription Cost Estimate
                        </h5>
                        <small style="opacity: 0.9; font-size: 12px; color: #DBEAFE;">Verify estimated usage and pricing before processing</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9; font-size: 24px; color: #FFFFFF; background: transparent; border: none; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" style="padding: 24px;">
                <!-- Loading State -->
                <div id="lg-cost-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2" style="color: var(--lg-accent) !important;"></i>
                    <p class="font-weight-bold text-muted small">Calculating dynamic audio duration and provider rate...</p>
                </div>

                <!-- Estimation Content -->
                <div id="lg-cost-content" style="display: none;">
                    <!-- Key Metric Highlights -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-3">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; text-align: center;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; letter-spacing: 0.5px;">Recordings</div>
                                <div id="modal-rec-count" style="font-size: 20px; font-weight: 700; color: #0F172A; margin-top: 2px;">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; text-align: center;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; letter-spacing: 0.5px;">Total Duration</div>
                                <div id="modal-total-duration" style="font-size: 15px; font-weight: 700; color: #0F172A; margin-top: 4px;">0s</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; text-align: center;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; letter-spacing: 0.5px;">Provider</div>
                                <div id="modal-provider" style="font-size: 14.5px; font-weight: 700; color: var(--lg-accent); margin-top: 4px;">Sarvam AI</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; text-align: center;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; letter-spacing: 0.5px;">Rate</div>
                                <div id="modal-unit-rate" style="font-size: 14.5px; font-weight: 700; color: #059669; margin-top: 4px;">₹0.15 / min</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estimated Cost Highlight Card -->
                    <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: var(--lg-accent-hover);">Estimated Total Cost</div>
                            <div style="font-size: 11.5px; color: #3B82F6; margin-top: 2px;">
                                Billable minutes: <span id="modal-billable-minutes" style="font-weight: 700;">0.00</span> mins
                            </div>
                        </div>
                        <div id="modal-estimated-cost" style="font-size: 24px; font-weight: 800; color: var(--lg-accent); letter-spacing: -0.5px;">
                            ₹0.00
                        </div>
                    </div>

                    <!-- Client Billing Notice -->
                    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 10px 14px; display: flex; align-items: flex-start; gap: 10px; font-size: 12px; color: #166534; margin-bottom: 16px;">
                        <i class="fas fa-shield-alt" style="font-size: 14px; margin-top: 2px; color: #16A34A; flex-shrink: 0;"></i>
                        <div>
                            <strong>Fair Usage Billing Policy:</strong> Charges are billed to the client account only upon <strong>successful transcription completion</strong>. If any recording fails to process, it will <strong>not</strong> be charged.
                        </div>
                    </div>

                    <!-- Call Breakdown Collapsible Table -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 12.5px; font-weight: 700; color: #334155;">Selected Call Breakdown</span>
                            <button type="button" class="btn btn-link p-0 text-primary" style="font-size: 12px; text-decoration: none;" onclick="lgToggleBreakdownTable()">
                                <span id="lg-breakdown-toggle-text">Show Details</span> <i class="fas fa-chevron-down ms-1" id="lg-breakdown-chevron"></i>
                            </button>
                        </div>
                        <div id="lg-breakdown-container" style="display: none; max-height: 180px; overflow-y: auto; border: 1px solid #E2E8F0; border-radius: 8px;">
                            <table class="table table-sm table-striped m-0" style="font-size: 12px;">
                                <thead style="background: #F8FAFC; position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th style="padding: 8px 12px;">Call ID</th>
                                        <th style="padding: 8px 12px;">Agent</th>
                                        <th style="padding: 8px 12px;">Duration</th>
                                        <th style="padding: 8px 12px; text-align: right;">Est. Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="lg-breakdown-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Processing / In-Progress State -->
                <div id="lg-cost-processing" style="display: none; text-align: center; padding: 24px 10px;">
                    <div class="mb-3">
                        <i class="fas fa-cog fa-spin fa-3x text-primary" style="color: var(--lg-accent) !important;"></i>
                    </div>
                    <h6 class="font-weight-bold" style="color: #0F172A; font-size: 16px;" id="lg-progress-title">Processing Transcription Jobs...</h6>
                    <p class="text-muted small mb-3" id="lg-progress-desc">Connecting to Sarvam AI Speech Engine...</p>
                    <div class="progress mb-2" style="height: 10px; border-radius: 10px; background: #E2E8F0;">
                        <div id="lg-batch-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 45%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted font-weight-bold" style="font-size: 11.5px;">
                        <span id="lg-progress-status-text">Processing recordings...</span>
                        <span id="lg-progress-count-text">Please wait...</span>
                    </div>
                </div>

                <!-- Completion Summary State -->
                <div id="lg-cost-completed-summary" style="display: none; text-align: center; padding: 16px 10px;">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #ECFDF5; border: 2px solid #A7F3D0; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; color: #10B981; margin-bottom: 12px;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h5 class="font-weight-bold" style="color: #0F172A; margin-bottom: 4px;">Transcription Batch Finished</h5>
                    <p class="text-muted small" id="lg-complete-msg">All selected recordings have been processed.</p>
                    
                    <div class="row g-2 mt-3 mb-3 justify-content-center">
                        <div class="col-4">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px;">
                                <div style="font-size: 11px; color: #64748B; font-weight: 600;">COMPLETED</div>
                                <div id="modal-comp-success" style="font-size: 18px; font-weight: 700; color: #10B981;">0</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px;">
                                <div style="font-size: 11px; color: #64748B; font-weight: 600;">FAILED</div>
                                <div id="modal-comp-failed" style="font-size: 18px; font-weight: 700; color: #EF4444;">0</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 10px;">
                                <div style="font-size: 11px; color: var(--lg-accent-hover); font-weight: 600;">ACTUAL BILLED</div>
                                <div id="modal-comp-cost" style="font-size: 18px; font-weight: 700; color: var(--lg-accent);">₹0.00</div>
                            </div>
                    </div>

                    <!-- Direct List of Transcribed Calls with View Transcript Buttons -->
                    <div id="lg-completed-transcripts-container" style="display: none; margin-top: 14px; text-align: left; border: 1px solid #E2E8F0; border-radius: 10px; background: #FFFFFF; overflow: hidden;">
                        <div style="padding: 10px 14px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 12px; font-weight: 700; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-file-alt text-primary me-1" style="color: var(--lg-accent) !important;"></i> Transcribed Recordings</span>
                            <span class="badge" id="lg-completed-transcripts-count" style="background: #EFF6FF; color: var(--lg-accent); font-weight: 700; font-size: 11px;">0 calls</span>
                        </div>
                        <div id="lg-completed-transcripts-list" style="max-height: 160px; overflow-y: auto; padding: 4px 12px;"></div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer" id="lg-cost-footer" style="padding: 14px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                <button type="button" class="btn btn-light border" data-dismiss="modal" id="btn-cancel-cost-estimate" style="font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius: 8px;">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btn-confirm-start-transcript" onclick="confirmAndStartTranscription()" style="background: var(--lg-accent); border-color: var(--lg-accent); font-size: 13px; font-weight: 600; padding: 8px 22px; border-radius: 8px; box-shadow: 0 2px 8px rgba(23,105,255,0.3);">
                    <i class="fas fa-check me-1"></i> Confirm &amp; Start Transcript
                </button>
            </div>
            <div class="modal-footer" id="lg-cost-completed-footer" style="display: none; padding: 14px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; justify-content: center;">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="finishBatchAndReload()" style="background: var(--lg-accent); border-color: var(--lg-accent); font-size: 13px; font-weight: 600; padding: 8px 24px; border-radius: 8px;">
                    Done &amp; View Transcripts
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lead Detail Modal -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content" style="border-radius:12px; overflow:hidden;">
        </div>
    </div>
</div>

<!-- Transcript & AI Analysis Modal -->
<!-- Option 4: Compact Clean Layout Transcript & AI Intelligence Modal -->
<div id="transcript-modal" class="modal fade lg-transcript-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 920px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 45px rgba(0,0,0,0.22); overflow: hidden; background: #FFFFFF;">
            <!-- Modal Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, var(--lg-accent) 0%, var(--lg-accent-hover) 100%); color: #FFFFFF; padding: 16px 24px; border-bottom: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #FFFFFF; flex-shrink: 0;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold" style="font-size: 17px; margin: 0; line-height: 1.2; color: #FFFFFF;">
                            Call Transcript &amp; AI Intelligence
                        </h5>
                        <small style="opacity: 0.9; font-size: 12px; color: #E0E7FF;">Automated call transcription and AI analysis</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" onclick="stopModalAudio()" aria-label="Close" style="opacity: 0.9; font-size: 24px; color: #FFFFFF; background: transparent; border: none; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Call Meta Details Bar -->
            <div class="modal-body p-x-md p-y-sm" id="transcript-meta-info" style="font-size: 13px; color: #334155; background: #F8FAFC !important; border-bottom: 1px solid #E2E8F0; padding: 12px 24px;">
                <div class="row align-items-center">
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Call ID:</span> 
                        <span id="meta-call-id" class="text-primary font-weight-bold" style="color: var(--lg-accent) !important; font-size: 13.5px;">—</span>
                    </div>
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Source:</span> 
                        <span id="meta-source">—</span>
                    </div>
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Agent:</span> 
                        <span id="meta-agent" style="font-weight: 700; color: #0F172A;">—</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span style="font-weight: 600; color: #64748B;">Customer:</span> 
                        <span id="meta-customer" style="font-weight: 700; color: #0F172A;">—</span>
                    </div>
                </div>
                <div class="row align-items-center" style="margin-top: 6px;">
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Number:</span>
                        <span id="meta-phone">—</span>
                    </div>
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Date:</span>
                        <span id="meta-date">—</span>
                    </div>
                    <div class="col-6 col-md-3 mb-1 mb-md-0">
                        <span style="font-weight: 600; color: #64748B;">Duration:</span>
                        <span id="meta-duration" style="font-weight: 700; color: #0F172A;">—</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span style="font-weight: 600; color: #64748B;">Campaign:</span>
                        <span id="meta-campaign">—</span>
                    </div>
                </div>
            </div>

            <!-- Modal Content Body -->
            <div class="modal-body" style="min-height: 320px; background: #FFFFFF; padding: 0;">
                <!-- Loading State -->
                <div id="transcript-loading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3" style="color: var(--lg-accent) !important;"></i>
                    <p class="font-weight-bold" id="transcript-loading-text" style="color: #475569; font-size: 14px;">Loading call transcript...</p>
                    <p id="transcript-loading-sub" style="display: none; color: #64748B; font-size: 12.5px; margin-top: -4px;"></p>
                </div>

                <!-- Error / Processing Failed State -->
                <div id="transcript-error" style="display: none;" class="text-center py-5">
                    <div class="alert alert-danger d-inline-block text-left p-a-md mb-3" style="max-width: 550px; border-radius: 12px; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B;">
                        <h6 class="font-weight-bold" id="transcript-error-title" style="color: #991B1B;"><i class="fas fa-exclamation-triangle me-2"></i> Transcript Unavailable</h6>
                        <p id="transcript-error-msg" class="mb-2" style="font-size: 13px; color: #7F1D1D;">Unable to process recording transcript.</p>
                        <div id="transcript-retry-container" class="mt-3">
                            <button class="btn btn-sm font-weight-bold" id="btn-retry-transcript" onclick="retryTranscriptProcess()" style="background: #F59E0B; border: none; color: #FFFFFF !important; font-size: 12.5px; padding: 6px 16px; border-radius: 6px;">
                                <i class="fas fa-sync me-1"></i> <span id="btn-retry-transcript-label">Retry</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2-Column Content Wrapper (Option 4 Compact Layout) -->
                <div id="transcript-content" style="display: none; padding: 20px 24px;">
                    <div class="row">
                        <!-- Left Column: Transcript & Audio Player -->
                        <div class="col-lg-6 mb-4 mb-lg-0" style="display: flex; flex-direction: column;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #F1F5F9;">
                                <div style="font-size: 14px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-align-left text-primary" style="color: var(--lg-accent) !important;"></i> Transcript
                                </div>
                                <div style="display: flex; gap: 6px;">
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="copyTranscriptText()" style="font-size: 11.5px; font-weight: 600; color: #475569; border: 1px solid #CBD5E1; background: #FFFFFF; border-radius: 6px; padding: 3px 8px;">
                                        <i class="fas fa-copy me-1"></i> Copy
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="downloadTranscriptText()" style="font-size: 11.5px; font-weight: 600; color: #475569; border: 1px solid #CBD5E1; background: #FFFFFF; border-radius: 6px; padding: 3px 8px;">
                                        <i class="fas fa-download me-1"></i> Download
                                    </button>
                                </div>
                            </div>

                            <!-- English conversation: language + who is the agent (confirmed by the user, never guessed) -->
                            <div id="lg-conv-bar" style="display: none; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; font-size: 11.5px; color: #64748B;">
                                <span id="lg-conv-lang"></span>
                                <span id="lg-conv-roles" style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-weight: 700; color: #334155;">Who is the Agent?</span>
                                    <button type="button" class="lg-conv-role-btn" data-agent="S1" onclick="lgSetAgentSpeaker('S1')">Speaker 1</button>
                                    <button type="button" class="lg-conv-role-btn" data-agent="S2" onclick="lgSetAgentSpeaker('S2')">Speaker 2</button>
                                    <button type="button" class="lg-conv-role-btn" data-agent="" onclick="lgSetAgentSpeaker('')" title="Show neutral speaker labels">Not sure</button>
                                </span>
                            </div>
                            <div id="lg-conv-missing" style="display: none; background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; border-radius: 8px; padding: 8px 12px; margin-bottom: 8px; font-size: 12px;">
                                This transcript was made before speaker-wise English conversations.
                                <button type="button" id="lg-conv-generate" onclick="retryTranscriptProcess()" style="margin-left: 6px; border: 1px solid #F59E0B; background: #FFFFFF; color: #92400E; border-radius: 6px; padding: 2px 8px; font-weight: 700; font-size: 11.5px; cursor: pointer;">Generate English conversation</button>
                            </div>

                            <!-- Readable Transcript Text Box -->
                            <div id="transcript-text-box" style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; font-size: 13px; line-height: 1.65; color: #1E293B; height: 320px; overflow-y: auto; white-space: pre-wrap; font-weight: 500;">
                            </div>
                            <div id="lg-conv-original-wrap" style="display: none; margin-top: 6px; font-size: 11.5px;">
                                <a href="javascript:void(0)" onclick="$('#lg-conv-original').slideToggle(150)" style="color: var(--lg-accent); font-weight: 600;">Original language transcript</a>
                                <div id="lg-conv-original" style="display: none; margin-top: 6px; background: #FFFFFF; border: 1px dashed #CBD5E1; border-radius: 8px; padding: 8px 12px; color: #475569; white-space: pre-wrap; max-height: 120px; overflow-y: auto;"></div>
                            </div>

                            <!-- Audio Player -->
                            <div id="lg-audio-player-box" style="display: none; align-items: center; gap: 12px; background: #F1F5F9; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 14px; margin-top: 12px;">
                                <button type="button" id="modal-audio-play-btn" onclick="toggleModalAudio()" style="width: 34px; height: 34px; border-radius: 50%; background: var(--lg-accent); color: #FFFFFF; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; flex-shrink: 0;">
                                    <i class="fas fa-play" id="modal-audio-icon"></i>
                                </button>
                                <div id="modal-audio-track" onclick="seekModalAudio(event)" style="flex: 1; height: 6px; background: #CBD5E1; border-radius: 3px; overflow: hidden; cursor: pointer; position: relative;">
                                    <div id="modal-audio-progress" style="height: 100%; width: 0%; background: var(--lg-accent); border-radius: 3px; transition: width 0.1s linear;"></div>
                                </div>
                                <span id="modal-audio-time" style="font-size: 11.5px; font-weight: 600; color: #64748B; white-space: nowrap;">00:00 / 00:00</span>
                                <a id="modal-audio-download" href="#" download class="lg-audio-dl-btn" title="Download Audio" style="color: #64748B; font-size: 13px; text-decoration: none; padding: 4px;">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: AI Highlights -->
                        <div class="col-lg-6">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #F1F5F9;">
                                <div style="font-size: 14px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-brain text-primary" style="color: var(--lg-accent) !important;"></i> AI Highlights
                                </div>
                                <span id="ai-source" style="font-size: 11px; color: #94A3B8;"></span>
                            </div>

                            <!-- Sentiment & Interest Badges -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px;">
                                        <div style="font-size: 10.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">Customer Sentiment</div>
                                        <span id="ai-sentiment-badge" class="lg-status-badge blue" style="font-size: 12px; font-weight: 700;">—</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px;">
                                        <div style="font-size: 10.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">Interest Level</div>
                                        <span id="ai-interest-badge" class="lg-status-badge amber" style="font-size: 12px; font-weight: 700;">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Call Summary Card -->
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px;">
                                <div style="font-size: 12px; font-weight: 700; color: var(--lg-accent); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-quote-left"></i> Call Summary
                                </div>
                                <div id="ai-summary" style="font-size: 12.5px; line-height: 1.55; color: #0F172A; font-weight: 500;"></div>
                            </div>

                            <!-- Key Discussion Points Card -->
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px;">
                                <div style="font-size: 12px; font-weight: 700; color: var(--lg-accent); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-list-ul"></i> Key Discussion Points
                                </div>
                                <div id="ai-keypoints" style="font-size: 12.5px; line-height: 1.55; color: #0F172A; font-weight: 500;"></div>
                            </div>

                            <!-- Intent & Next Action -->
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; height: 100%;">
                                        <div style="font-size: 11.5px; font-weight: 700; color: var(--lg-accent); margin-bottom: 3px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-bullseye"></i> Customer Intent
                                        </div>
                                        <div id="ai-intent" style="font-size: 12px; color: #0F172A; font-weight: 500; line-height: 1.4;"></div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; height: 100%;">
                                        <div style="font-size: 11.5px; font-weight: 700; color: var(--lg-accent); margin-bottom: 3px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-tasks"></i> Suggested Next Action
                                        </div>
                                        <div id="ai-next-action" style="font-size: 12px; color: #0F172A; font-weight: 500; line-height: 1.4;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Disposition & Follow-up Row -->
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; height: 100%;">
                                        <div style="font-size: 11.5px; font-weight: 700; color: var(--lg-accent); margin-bottom: 3px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-tag"></i> Disposition
                                        </div>
                                        <div id="ai-disposition" style="font-size: 12px; color: #059669; font-weight: 600; line-height: 1.4;">—</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; height: 100%;">
                                        <div style="font-size: 11.5px; font-weight: 700; color: var(--lg-accent); margin-bottom: 3px; display: flex; align-items: center; gap: 5px;">
                                            <i class="far fa-calendar-check"></i> Follow-up Required
                                        </div>
                                        <div id="ai-followup" style="font-size: 12px; color: var(--lg-accent-hover); font-weight: 600; line-height: 1.4;">—</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Emotion Analysis & Quality Flags -->
                            <div id="ai-emotion-quality-box" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px;">
                                            <div style="font-size: 11.5px; font-weight: 700; color: #7C3AED; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-heart-pulse"></i> Emotion Analysis
                                            </div>
                                            <div id="ai-emotion" style="font-size: 12px; color: #334155; line-height: 1.45;"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px;">
                                            <div style="font-size: 11.5px; font-weight: 700; color: #059669; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-shield-alt"></i> Quality Flags
                                            </div>
                                            <div id="ai-quality" style="font-size: 12px; color: #334155; line-height: 1.45;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer" style="background: #F8FAFC !important; border-top: 1px solid #E2E8F0; padding: 12px 24px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn font-weight-bold" data-dismiss="modal" onclick="stopModalAudio()" style="background: #64748B; border: none; color: #FFFFFF; padding: 7px 20px; border-radius: 8px; font-size: 13px;">Close</button>
            </div>
        </div>
    </div>
</div>

@if(\App\Helpers\Helper::canDeleteRecordings())
<!-- Delete Recording Confirmation Modal -->
<div id="lg-delete-confirm-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 45px rgba(0,0,0,0.22); overflow: hidden; background: #FFFFFF;">
            <div class="modal-header" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: #FFFFFF; padding: 16px 24px; border-bottom: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #FFFFFF; flex-shrink: 0;">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h5 class="modal-title font-weight-bold" id="lg-delete-title" style="font-size: 17px; margin: 0; color: #FFFFFF;">Delete this recording?</h5>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9; font-size: 24px; color: #FFFFFF; background: transparent; border: none; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px 24px; font-size: 13.5px; color: #334155; line-height: 1.55;">
                <p id="lg-delete-text" style="margin: 0 0 8px;">The audio file will be permanently deleted.</p>
                <p style="margin: 0; font-weight: 600; color: #B91C1C;">This cannot be undone.</p>
            </div>
            <div class="modal-footer" style="background: #F8FAFC !important; border-top: 1px solid #E2E8F0; padding: 12px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn font-weight-bold" data-dismiss="modal" style="background: #FFFFFF; border: 1px solid #CBD5E1; color: #334155; padding: 7px 18px; border-radius: 8px; font-size: 13px;">Cancel</button>
                <button type="button" class="btn font-weight-bold" id="lg-delete-confirm-btn" onclick="lgRunDelete()" style="background: #EF4444; border: none; color: #FFFFFF; padding: 7px 18px; border-radius: 8px; font-size: 13px;"><i class="fas fa-trash-alt me-1"></i> Delete</button>
            </div>
        </div>
    </div>
</div>
@endif

@include('dashboard.reports.partials.recording-player')
@endsection

@push("after-scripts")
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>

    <script type="text/javascript">
        function leadDetail(id) {
            var url = window.location.origin + "{{ '/' . ltrim(parse_url(route('leadDetail', [':id']), PHP_URL_PATH), '/') }}";
            url = url.replace(':id', id);
            $('.modal-content.lead-content').html('<div class="p-5 text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
            $("#lead-detail").modal("show");
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.modal-content.lead-content').html(html);
                    // Only the popup's own dropdowns (not the page filters)
                    $('#lead-detail select').select2({ dropdownParent: $('#lead-detail') });
                }
            });
        }

        var dataTable = null;

        // Accepts seconds (number or numeric string), "mm:ss" or "hh:mm:ss"; never returns NaN.
        function lgToSeconds(v) {
            if (v === null || v === undefined || v === '') return 0;
            if (typeof v === 'number') return isFinite(v) && v > 0 ? Math.round(v) : 0;
            var s = String(v).trim();
            if (/^\d+(\.\d+)?$/.test(s)) return Math.round(parseFloat(s));
            var m = s.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
            if (!m) return 0;
            return m[3] !== undefined
                ? (+m[1]) * 3600 + (+m[2]) * 60 + (+m[3])
                : (+m[1]) * 60 + (+m[2]);
        }
        function lgFormatDuration(v) {
            var sec = lgToSeconds(v);
            var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            return h > 0 ? h + ':' + pad(m) + ':' + pad(s) : pad(m) + ':' + pad(s);
        }

        $(document).ready(function () {
            // app.js turns every <select> into select2 with a fixed pixel width; keep the page's own filter dropdowns
            $('#find_source, #find_campaign').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) { $(this).select2('destroy'); }
            });

            var table_name = "#leads_table";
            dataTable = window.lgDlTable = $(table_name).DataTable({
                "processing": true,
                "serverSide": true,
                "searching": true,
                "responsive": false,
                "scrollX": true,
                "pageLength": {{ env('BACKEND_PAGINATION', 10) }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
                "order": [[8, "desc"]],
                "ajax": {
                    "url": window.location.origin + "{{ '/' . ltrim(parse_url(route('recordingsData'), PHP_URL_PATH), '/') }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{ csrf_token() }}";
                        data.find_q = $('#find_q').val();
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                        data.find_campaign = $('#find_campaign').val();
                        data.telecaller_id = $('#telecaller_id').val();
                        data.find_source = $('#find_source').val();
                    }
                },
                "dom": '<"dl-dt-top"lf>rt<"dl-dt-bottom"ip>',
                "fnDrawCallback": function () {
                    var pager = $(table_name + '_paginate')[0];
                    if (pager) pager.style.display = $(table_name + '_paginate .paginate_button').length > 3 ? 'block' : 'none';
                    $('[data-toggle="tooltip"]').tooltip({html: true});
                    syncRowCheckboxes();
                },
                "language": {
                    "emptyTable": "No data found",
                    "zeroRecords": "No data found"
                },
                "columns": [
                    { "data": "checkbox", "orderable": false, "searchable": false, "width": "38px" },
                    { "data": "id", "orderable": true },
                    { "data": "called_by", "orderable": true },
                    { "data": "phonenumber", "orderable": true },
                    { "data": "campaign_name", "orderable": true },
                    { "data": "source_type", "orderable": true },
                    { "data": "recording", "orderable": true },
                    {
                        "data": "duration",
                        "orderable": true,
                        "render": function(data, type, row) {
                            var text = row.duration_formatted || lgFormatDuration(data);
                            return text;
                        }
                    },
                    { "data": "created_at", "orderable": true },
                    { "data": "play", "orderable": false, "searchable": false },
                    { "data": "ai_analysis", "orderable": true }
                ]
            });

            // Flatpickr datepicker initialization
            var fromPicker = flatpickr("#from_date", {
                dateFormat: "Y-m-d",
                allowInput: false,
                clickOpens: true,
                onChange: function(selectedDates, dateStr) {
                    if (selectedDates.length > 0) {
                        $('#from_date_clear').show();
                        toPicker.set("minDate", dateStr);
                        var toVal = $('#to_date').val();
                        if (toVal && toVal < dateStr) {
                            $('#to_date').val('');
                            $('#to_date_clear').hide();
                            $('#date-range-error').css('display', 'flex');
                            $('#date-range-error-text').text("'To Date' cannot be earlier than 'From Date'. Please reselect 'To Date'.");
                            if (typeof toastr !== 'undefined') {
                                toastr.warning("'To Date' cannot be earlier than 'From Date'.");
                            }
                        } else {
                            $('#date-range-error').hide();
                        }
                    } else {
                        $('#from_date_clear').hide();
                        toPicker.set("minDate", null);
                        $('#date-range-error').hide();
                    }
                    // dates apply when Search is pressed
                }
            });

            var toPicker = flatpickr("#to_date", {
                dateFormat: "Y-m-d",
                minDate: $('#from_date').val() || null,
                allowInput: false,
                clickOpens: true,
                onChange: function(selectedDates, dateStr) {
                    var fromVal = $('#from_date').val();
                    if (selectedDates.length > 0) {
                        $('#to_date_clear').show();
                        if (fromVal && dateStr < fromVal) {
                            $('#to_date').val('');
                            $('#to_date_clear').hide();
                            $('#date-range-error').css('display', 'flex');
                            $('#date-range-error-text').text("'To Date' cannot be earlier than 'From Date'.");
                            if (typeof toastr !== 'undefined') {
                                toastr.error("'To Date' cannot be earlier than 'From Date'.");
                            }
                            return;
                        } else {
                            $('#date-range-error').hide();
                        }
                    } else {
                        $('#to_date_clear').hide();
                        $('#date-range-error').hide();
                    }
                    // dates apply when Search is pressed
                }
            });

            // Click calendar icons to open pickers
            $('#from_date_icon').on('click', function(e) {
                e.stopPropagation();
                fromPicker.open();
            });
            $('#to_date_icon').on('click', function(e) {
                e.stopPropagation();
                toPicker.open();
            });

            // Clear date buttons
            $('#from_date_clear').on('click', function(e) {
                e.stopPropagation();
                fromPicker.clear();
                $('#from_date_clear').hide();
                toPicker.set("minDate", null);
                $('#date-range-error').hide();
                // dates apply when Search is pressed
            });
            $('#to_date_clear').on('click', function(e) {
                e.stopPropagation();
                toPicker.clear();
                $('#to_date_clear').hide();
                $('#date-range-error').hide();
                // dates apply when Search is pressed
            });

            // Filter button click
            $("#search-btn").on('click', function () {
                var fromVal = $('#from_date').val();
                var toVal = $('#to_date').val();
                if (fromVal && toVal && toVal < fromVal) {
                    $('#date-range-error').css('display', 'flex');
                    if (typeof toastr !== 'undefined') {
                        toastr.error("'To Date' cannot be earlier than 'From Date'.");
                    }
                    return;
                }
                if (dataTable) dataTable.draw();
            });

            // Search icon click → trigger draw immediately
            $('#search_icon_btn').on('click', function () {
                clearTimeout(searchTimeout);
                if (dataTable) dataTable.draw();
            });

            // Clear button → clear input and redraw
            $('#search_clear_btn').on('click', function () {
                clearTimeout(searchTimeout);
                $('#find_q').val('').focus();
                $(this).hide();
                if (dataTable) dataTable.draw();
            });

            // Search debounce & Enter key
            var searchTimeout = null;
            $('#find_q').on('input keyup', function (e) {
                var val = $(this).val();
                if (val.length > 0) {
                    $('#search_clear_btn').css('display', 'flex');
                } else {
                    $('#search_clear_btn').hide();
                }
                if (e.which === 13) {
                    clearTimeout(searchTimeout);
                    if (dataTable) dataTable.draw();
                    return;
                }
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    if (dataTable) dataTable.draw();
                }, 350);
            });

            // Dropdown filters
            $('#find_source, #find_campaign').on('change', function () {
                if (dataTable) dataTable.draw();
            });

            // Close Employee multi-select dropdown when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.lg-emp-dropdown-wrap').length) {
                    $('#lg_emp_dropdown_menu').hide();
                }
            });

            // Reset filters
            $("#reset-btn").on('click', function () {
                $('#find_q').val('');
                $('#find_source').val('');
                $('#find_campaign').val('');
                $('#telecaller_id').val('');
                clearAllEmployees();
                lgClearSelection();
                // back to the default: today
                fromPicker.setDate('{{ now()->toDateString() }}', false);
                toPicker.setDate('{{ now()->toDateString() }}', false);
                toPicker.set("minDate", $('#from_date').val());
                $('#from_date_clear, #to_date_clear').show();
                $('#date-range-error').hide();
                if (dataTable) dataTable.draw();
            });


            // Scroll on page change
            if (dataTable) {
                dataTable.on('page.dt', function () {
                    var target = $(".table-card-container");
                    if (target.length) {
                        $('html, body').animate({
                            scrollTop: target.offset().top - 20
                        }, 'slow');
                    }
                });
            }

            $.fn.dataTable.ext.errMode = 'none';
        });

        // Auto-refresh while idle: every 8 seconds while a row is waiting for its recording or its transcript
        // (spinner / Pending badge), otherwise every 30 seconds so newly arrived recordings appear automatically
        var lgRefreshTick = 0;
        setInterval(function () {
            lgRefreshTick++;
            var busy = $('#leads_table .fa-spinner, #leads_table .lg-status-badge.amber').length > 0;
            if (!busy && lgRefreshTick % 4 !== 0) return;
            if (dataTable && !$('#lgDlPlayer').hasClass('active')) {
                if (!$('#transcript-modal').is(':visible') && !$('#lg-cost-estimate-modal').is(':visible')) {
                    dataTable.ajax.reload(null, false);
                }
            }
        }, 8000);

        // ---- Delete recording (Admin only, when Delete Permission is enabled; enforced on the server too) ----
        var lgPendingDelete = [];

        function deleteRecording(id, sourceCode) {
            lgConfirmDelete([{ id: id, source_code: sourceCode || 0 }],
                'Delete this recording?',
                'The audio file will be permanently deleted. The call itself stays in the Call Report.');
        }

        function deleteSelectedRecordings() {
            var keys = Object.keys(selectedRecordings);
            if (keys.length === 0) {
                lgToast('warning', 'Please select at least one recording to delete.');
                return;
            }
            var items = keys.map(function (k) {
                return { id: selectedRecordings[k].id, source_code: selectedRecordings[k].source_code };
            });
            lgConfirmDelete(items,
                'Delete ' + items.length + ' recording' + (items.length > 1 ? 's' : '') + '?',
                'The selected audio files will be permanently deleted. The calls themselves stay in the Call Report.');
        }

        function lgConfirmDelete(items, title, text) {
            lgPendingDelete = items;
            $('#lg-delete-title').text(title);
            $('#lg-delete-text').text(text);
            $('#lg-delete-confirm-btn').prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i> Delete');
            if ($('#lg-delete-confirm-modal').parent().is(':not(body)')) {
                $('#lg-delete-confirm-modal').appendTo('body');
            }
            $('#lg-delete-confirm-modal').modal('show');
        }

        function lgRunDelete() {
            if (!lgPendingDelete.length) return;
            var items = lgPendingDelete;
            var isBulk = items.length > 1;
            $('#lg-delete-confirm-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');

            var request = isBulk
                ? { url: "{{ route('recordingsBulkDelete') }}", type: 'POST', data: { _token: "{{ csrf_token() }}", items: items } }
                : { url: "{{ url('/admin/reports/recordings') }}/" + items[0].id + "/" + items[0].source_code, type: 'DELETE', data: { _token: "{{ csrf_token() }}" } };

            $.ajax($.extend(request, { dataType: 'json' }))
                .done(function (res) {
                    $('#lg-delete-confirm-modal').modal('hide');
                    lgToast(res.success ? 'success' : 'warning', res.message || 'Recording deleted successfully.');
                    if (isBulk) lgClearSelection();
                    if (dataTable) dataTable.ajax.reload(null, false);
                })
                .fail(function (xhr) {
                    $('#lg-delete-confirm-modal').modal('hide');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'The recording could not be deleted.';
                    lgToast('error', msg);
                    if (dataTable) dataTable.ajax.reload(null, false);
                });
        }

        // Transcript & AI Intelligence Modal Handling (Option 4 Compact Clean Layout)
        let activeTranscriptLogId = null;
        let activeTranscriptSourceCode = null;
        let modalAudioObj = null;

        function openTranscriptModal(id, source_code) {
            if ($('#lg-cost-estimate-modal').hasClass('show') || $('#lg-cost-estimate-modal').is(':visible')) {
                $('#lg-cost-estimate-modal').modal('hide');
            }
            if ($('#transcript-modal').parent().is(':not(body)')) {
                $('#transcript-modal').appendTo('body');
            }
            activeTranscriptLogId = id;
            activeTranscriptSourceCode = source_code;

            stopModalAudio();

            $('#transcript-loading-text').text('Loading call transcript...');
            $('#transcript-loading-sub').hide();
            $('#transcript-loading').show();
            $('#transcript-error').hide();
            $('#transcript-content').hide();
            activeTranscriptText = '';
            activeConversation = null; activeSpeakerRoles = null; activeSourceLanguage = '';
            $('#lg-conv-bar, #lg-conv-missing, #lg-conv-original-wrap, #lg-conv-original').hide();
            $('#ai-source').text('');

            $('#transcript-modal').modal('show');

            fetchTranscriptData(id, source_code);
        }

        function fetchTranscriptData(id, source_code) {
            var url = window.location.origin + "/admin/reports/recordings/transcript/" + id + "/" + source_code;

            $.ajax({
                type: "GET",
                url: url,
                dataType: "json",
                success: function (res) {
                    $('#transcript-loading').hide();
                    if (res.meta) lgFillTranscriptMeta(res.meta);

                    if (res.status === 'pending' || res.status === 'processing') {
                        // Still running: Queued / Processing with the time so far; checked again less often the longer
                        // it runs, and no longer than 20 minutes (the job runs on in the background either way)
                        var p = res.progress || {};
                        var secs = p.seconds || 0;
                        var queued = p.state === 'queued' || (!p.state && res.status === 'pending');
                        var expect = 20 + (p.duration || 0);   // measured: about 20 s plus the call's length
                        $('#transcript-loading-text').text(queued
                            ? 'Queued - waiting for the transcription worker (' + formatAudioTime(secs) + ')'
                            : 'Transcribing and analysing the call... ' + formatAudioTime(secs) + ' so far');
                        $('#transcript-loading-sub').text((queued ? '' : 'Usually about ' + formatAudioTime(expect) + ' for this call. ')
                            + 'You can close this window - the transcript keeps processing in the background.').show();
                        $('#transcript-loading').show();
                        if (secs > 1200) {
                            $('#transcript-loading').hide();
                            $('#transcript-error-title').html('<i class="fas fa-clock me-2"></i> Still Processing');
                            $('#transcript-error-msg').text('This transcript is taking much longer than usual. It is still running in the background - close this window and check again later.');
                            $('#transcript-retry-container').hide();
                            $('#transcript-error').show();
                            return;
                        }
                        setTimeout(function () {
                            if ($('#transcript-modal').is(':visible') && activeTranscriptLogId == id && activeTranscriptSourceCode == source_code) {
                                fetchTranscriptData(id, source_code);
                            }
                        }, secs < 60 ? 3000 : (secs < 180 ? 5000 : 10000));
                        return;
                    }
                    $('#transcript-loading-sub').hide();

                    if (res.status === 'failed' || res.status === 'none') {
                        var canRun = res.meta && res.meta.can_transcribe;
                        var isFailed = res.status === 'failed';
                        $('#transcript-error-title').html('<i class="fas fa-exclamation-triangle me-2"></i> ' + (isFailed ? 'Transcript Failed' : 'No Transcript Yet'));
                        var msg = isFailed ? (res.message || 'Transcription failed.') : 'This recording has not been transcribed yet.';
                        if (!canRun && res.meta && res.meta.audio_issue) msg += ' ' + res.meta.audio_issue;
                        $('#transcript-error-msg').text(msg);
                        $('#btn-retry-transcript-label').text(isFailed ? 'Retry' : 'Transcribe');
                        $('#transcript-retry-container').toggle(!!canRun);
                        $('#transcript-error').show();
                        return;
                    }

                    if (res.status === 'success') {
                        var meta = res.meta;
                        var analysis = res.analysis;
                        activeTranscriptMeta = meta;
                        activeConversation = analysis.conversation || null;
                        activeSpeakerRoles = analysis.speaker_roles || null;
                        activeSourceLanguage = analysis.source_language || '';

                        if (activeConversation && activeConversation.length) {
                            // Speaker-wise English conversation (Sarvam: translate + diarization)
                            lgRenderConversation();
                            $('#lg-conv-missing').hide();
                            $('#lg-conv-original').text(analysis.transcript || '');
                            $('#lg-conv-original-wrap').toggle(!!analysis.transcript);
                        } else {
                            // Transcript made before speaker-wise English conversations: show it as before
                            activeTranscriptText = analysis.transcript || '';
                            $('#transcript-text-box').css('white-space', 'pre-wrap').html(formatTranscriptHtml(analysis.transcript));
                            $('#lg-conv-bar').hide();
                            $('#lg-conv-original-wrap').hide();
                            $('#lg-conv-missing').toggle(!!meta.can_transcribe);
                        }

                        // AI Analysis Highlights (from the English conversation when it exists)
                        var src = analysis.analysis_source || '';
                        $('#ai-source').text(src ? (src.indexOf('keyword') === 0 ? 'Keyword rules (AI model unavailable)' : 'Sarvam AI ' + src + ' · from the English conversation') : '');
                        $('#ai-summary').text(analysis.summary || 'Summary unavailable.');
                        $('#ai-keypoints').html(formatKeyPointsHtml(analysis.key_points));
                        $('#ai-intent').text(analysis.intent || 'General Enquiry');
                        $('#ai-next-action').text(analysis.next_action || 'Follow up with lead.');

                        // Sentiment Badge
                        var sent = (analysis.sentiment || 'NEUTRAL').toUpperCase();
                        var sentClass = (sent === 'POSITIVE') ? 'green' : ((sent === 'NEGATIVE') ? 'red' : 'blue');
                        $('#ai-sentiment-badge').attr('class', 'lg-status-badge ' + sentClass).html('<span class="dot"></span> ' + sent);

                        // Interest Level Badge
                        var intLevel = (analysis.interest_level || 'MEDIUM').toUpperCase();
                        var intClass = (intLevel === 'HIGH') ? 'green' : ((intLevel === 'LOW') ? 'gray' : 'amber');
                        $('#ai-interest-badge').attr('class', 'lg-status-badge ' + intClass).html('<span class="dot"></span> ' + intLevel);

                        // Disposition & Follow-up
                        $('#ai-disposition').text(analysis.auto_disposition || 'Follow-up');
                        var followUpText = 'No';
                        if (analysis.follow_up_time) {
                            followUpText = 'Yes (' + analysis.follow_up_time + ')';
                        } else if (analysis.follow_up_date) {
                            followUpText = 'Yes (' + analysis.follow_up_date + ')';
                        }
                        $('#ai-followup').text(followUpText);

                        // Emotion analysis & Quality flags
                        if (analysis.emotion_analysis || analysis.quality_flags) {
                            $('#ai-emotion').text(analysis.emotion_analysis || 'Positive tone, calm and receptive');
                            $('#ai-quality').text(analysis.quality_flags || 'Clear disclosure provided, accurate interest rate info, consent obtained');
                            $('#ai-emotion-quality-box').show();
                        } else {
                            $('#ai-emotion-quality-box').hide();
                        }

                        // Setup Audio Player if audio_url is present
                        if (meta.audio_url) {
                            setupModalAudio(meta.audio_url);
                            $('#lg-audio-player-box').css('display', 'flex');
                        } else {
                            $('#lg-audio-player-box').hide();
                        }

                        $('#transcript-content').show();
                    } else {
                        $('#transcript-error-msg').text(res.message || 'Failed to retrieve recording transcript.');
                        $('#transcript-retry-container').hide();
                        $('#transcript-error').show();
                    }
                },
                error: function (xhr) {
                    $('#transcript-loading').hide();
                    var err = 'An unexpected error occurred while fetching the transcript.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    }
                    $('#transcript-error-msg').text(err);
                    $('#transcript-retry-container').hide();
                    $('#transcript-error').show();
                }
            });
        }

        var activeTranscriptText = '';
        var activeTranscriptMeta = null;
        var activeConversation = null, activeSpeakerRoles = null, activeSourceLanguage = '';

        var lgLanguageNames = { 'te-IN': 'Telugu', 'hi-IN': 'Hindi', 'en-IN': 'English', 'ta-IN': 'Tamil', 'kn-IN': 'Kannada', 'ml-IN': 'Malayalam', 'mr-IN': 'Marathi', 'bn-IN': 'Bengali', 'gu-IN': 'Gujarati', 'pa-IN': 'Punjabi', 'od-IN': 'Odia' };

        // Agent (From) / Customer (To) only once a user has said which speaker is the agent; otherwise Speaker 1 / 2
        function lgSpeakerLabel(s, roles) {
            var role = roles && roles[s];
            if (role === 'agent') return 'Agent (From)';
            if (role === 'customer') return 'Customer (To)';
            return 'Speaker ' + String(s).replace(/^S/, '');
        }
        function lgTs(sec) {
            if (sec === null || sec === undefined || sec === '') return '';
            sec = Math.floor(Number(sec));
            return (sec < 600 ? '0' : '') + Math.floor(sec / 60) + ':' + (sec % 60 < 10 ? '0' : '') + (sec % 60);
        }
        // Plain text used by Copy and Download: "[mm:ss] Speaker: sentence", one turn per line, in spoken order
        function lgConversationText(turns, roles) {
            return (turns || []).map(function (t) {
                var ts = lgTs(t.start);
                return (ts ? '[' + ts + '] ' : '') + lgSpeakerLabel(t.speaker, roles) + ': ' + t.text;
            }).join('\n');
        }
        function lgRenderConversation() {
            var roles = activeSpeakerRoles;
            var html = activeConversation.map(function (t) {
                var role = roles && roles[t.speaker];
                var cls = role === 'agent' ? 'agent' : (role === 'customer' ? 'customer' : String(t.speaker).toLowerCase());
                return '<div class="lg-conv-turn ' + cls + '">'
                    + '<div class="lg-conv-who"><span>' + escapeHtmlText(lgSpeakerLabel(t.speaker, roles)) + '</span><span class="lg-conv-time">' + escapeHtmlText(lgTs(t.start)) + '</span></div>'
                    + '<div class="lg-conv-text">' + escapeHtmlText(t.text) + '</div></div>';
            }).join('');
            $('#transcript-text-box').css('white-space', 'normal').html(html);
            activeTranscriptText = lgConversationText(activeConversation, roles);
            var lang = lgLanguageNames[activeSourceLanguage] || activeSourceLanguage;
            $('#lg-conv-lang').text((lang ? 'Spoken in ' + lang + ' · ' : '') + 'translated to English');
            var agent = '';
            if (roles) { Object.keys(roles).forEach(function (k) { if (roles[k] === 'agent') agent = k; }); }
            var speakers = activeConversation.map(function (t) { return t.speaker; }).filter(function (v, i, a) { return a.indexOf(v) === i; });
            $('.lg-conv-role-btn').each(function () {
                var a = $(this).data('agent');
                $(this).toggle(a === '' || speakers.indexOf(a) !== -1).toggleClass('active', String(a) === agent);
            });
            $('#lg-conv-roles').toggle(speakers.length > 1);
            $('#lg-conv-bar').css('display', 'flex');
        }
        // Saved for this recording, so everyone sees the same labels; only the labels change, never the text
        function lgSetAgentSpeaker(agent) {
            if (!activeTranscriptLogId) return;
            $.ajax({
                type: 'POST',
                url: window.location.origin + '/admin/reports/recordings/transcript/' + activeTranscriptLogId + '/' + activeTranscriptSourceCode + '/speakers',
                data: { _token: "{{ csrf_token() }}", agent: agent },
                dataType: 'json'
            }).done(function (res) {
                activeSpeakerRoles = res.speaker_roles || null;
                lgRenderConversation();
            }).fail(function (xhr) {
                lgToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save the speaker labels.');
            });
        }

        function lgFillTranscriptMeta(meta) {
            $('#meta-call-id').text('#' + meta.log_id);
            $('#meta-source').html('<span class="lg-source-badge ' + (meta.source_code == 0 ? 'campaign' : 'dialing') + '">' + escapeHtmlText(meta.source_type || (meta.source_code == 0 ? 'Campaign Calling' : 'Dialing')) + '</span>');
            $('#meta-agent').text(meta.agent_name || 'N/A');
            $('#meta-customer').text(meta.customer_name || meta.phonenumber || 'N/A');
            $('#meta-phone').text(meta.phonenumber || '—');
            $('#meta-date').text(meta.created_at || '—');
            $('#meta-duration').text(meta.duration_formatted || lgFormatDuration(meta.duration));
            $('#meta-campaign').text(meta.campaign_name || '—');
        }

        // ---- One-click transcript (row button, popup Retry) ----
        function lgToast(type, msg) {
            if (typeof toastr !== 'undefined' && toastr[type]) { toastr[type](msg); } else { alert(msg); }
        }

        function lgSetAiCell(id, sc, html) {
            $('.lg-ai-cell[data-key="' + sc + '_' + id + '"]').html(html);
        }

        function lgTranscribeOne(id, sc, btn, openWhenDone) {
            if (openWhenDone === undefined) openWhenDone = true;
            if (btn) $(btn).prop('disabled', true);
            lgSetAiCell(id, sc, '<span class="lg-status-badge blue"><i class="fas fa-spinner fa-spin" style="font-size:10px;"></i> Transcribing</span>');

            $.ajax({
                type: 'POST',
                url: window.location.origin + "/admin/reports/recordings/transcription/jobs",
                data: { _token: "{{ csrf_token() }}", recordings: [sc + '_' + id] },
                dataType: 'json'
            }).done(function (res) {
                if (res.status === 'success' && res.batch_id) {
                    lgPollTranscript(res.batch_id, id, sc, openWhenDone);
                    // Retry from the open popup: show its Queued / Processing progress there
                    if ($('#transcript-modal').is(':visible') && activeTranscriptLogId == id && activeTranscriptSourceCode == sc) {
                        fetchTranscriptData(id, sc);
                    }
                } else {
                    lgTranscribeFailed(id, sc, res.message || 'Could not start the transcript.');
                }
            }).fail(function (xhr) {
                lgTranscribeFailed(id, sc, (xhr.responseJSON && xhr.responseJSON.message) || 'Could not start the transcript.');
            });
        }

        function lgTranscribeFailed(id, sc, msg) {
            lgToast('error', msg);
            if (dataTable) dataTable.ajax.reload(null, false);
            if ($('#transcript-modal').is(':visible') && activeTranscriptLogId == id) fetchTranscriptData(id, sc);
        }

        function lgPollTranscript(batchId, id, sc, openWhenDone) {
            var tries = 0, warned = false;
            var timer = setInterval(function () {
                tries++;
                if (tries > 400) { clearInterval(timer); return; } // ~20 minutes
                $.getJSON(window.location.origin + "/admin/reports/recordings/transcription/status/" + batchId).done(function (res) {
                    var b = res.data || res;
                    if (b.queue_stalled && !warned) {
                        warned = true;
                        lgToast('warning', 'Transcript is queued but the background worker is not running. Start LeadGER with start-leadger.bat (it runs the queue worker).');
                    }
                    if (!b.is_finished) return;
                    clearInterval(timer);
                    var r = (b.results || [])[0] || {};
                    if (dataTable) dataTable.ajax.reload(null, false);
                    if (r.success) {
                        lgToast('success', 'Transcript completed.');
                        if (openWhenDone) openTranscriptModal(id, sc);
                    } else {
                        lgToast('error', 'Transcript failed: ' + (r.error || 'unknown error'));
                        if ($('#transcript-modal').is(':visible') && activeTranscriptLogId == id) fetchTranscriptData(id, sc);
                    }
                });
            }, 3000);
        }

        function downloadTranscriptText() {
            if (!activeTranscriptText) return;
            var m = activeTranscriptMeta || {};
            var header = 'Call #' + (m.log_id || '') + '\n'
                + 'Agent: ' + (m.agent_name || '') + '\n'
                + 'Number: ' + (m.phonenumber || '') + '\n'
                + 'Date: ' + (m.created_at || '') + '\n'
                + 'Duration: ' + (m.duration_formatted || lgFormatDuration(m.duration)) + '\n'
                + (activeConversation && activeConversation.length
                    ? 'Language: ' + (lgLanguageNames[activeSourceLanguage] || activeSourceLanguage || 'auto-detected') + ' (translated to English)\n' : '')
                + '\n';
            var blob = new Blob([header + activeTranscriptText], { type: 'text/plain;charset=utf-8' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'transcript_call_' + (m.log_id || 'recording') + '.txt';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
        }

        function formatTranscriptHtml(text) {
            if (!text) return '<span class="text-muted">No transcript text available.</span>';
            var lines = text.split('\n');
            var html = '';
            var currentSpeaker = null;
            var currentContent = [];

            function flush() {
                if (!currentSpeaker && currentContent.length === 0) return;
                var body = escapeHtmlText(currentContent.join(' ')).trim();
                if (currentSpeaker === 'agent') {
                    html += '<div class="mb-2" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 8px 12px;"><span class="lg-speaker-tag lg-speaker-agent" style="background: #EFF6FF; color: var(--lg-accent); border: 1px solid #BFDBFE; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 8px;">Agent</span><span style="color: #1E293B;">' + body + '</span></div>';
                } else if (currentSpeaker === 'customer') {
                    html += '<div class="mb-2" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 8px 12px;"><span class="lg-speaker-tag lg-speaker-customer" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 8px;">Customer</span><span style="color: #1E293B;">' + body + '</span></div>';
                } else if (body) {
                    html += '<div class="mb-2" style="padding: 4px 6px;">' + body + '</div>';
                }
                currentSpeaker = null;
                currentContent = [];
            }

            lines.forEach(function(line) {
                var trimmed = line.trim();
                if (!trimmed) return;
                var agentMatch = trimmed.match(/^(Agent|Telecaller):\s*(.*)$/i);
                var custMatch = trimmed.match(/^(Customer|User|Lead):\s*(.*)$/i);
                if (agentMatch) {
                    flush();
                    currentSpeaker = 'agent';
                    if (agentMatch[2]) currentContent.push(agentMatch[2]);
                } else if (custMatch) {
                    flush();
                    currentSpeaker = 'customer';
                    if (custMatch[2]) currentContent.push(custMatch[2]);
                } else {
                    if (currentSpeaker) {
                        currentContent.push(trimmed);
                    } else {
                        currentContent.push(trimmed);
                        flush();
                    }
                }
            });
            flush();
            return html || escapeHtmlText(text);
        }

        function formatKeyPointsHtml(points) {
            if (!points) return '<span class="text-muted">No key discussion points recorded.</span>';
            if (typeof points === 'string') {
                var lines = points.split('\n');
                var html = '<ul class="ps-3 mb-0" style="padding-left: 18px; margin-bottom: 0;">';
                lines.forEach(function(l) {
                    l = l.trim().replace(/^[-•*]\s*/, '');
                    if (l) html += '<li>' + escapeHtmlText(l) + '</li>';
                });
                html += '</ul>';
                return html;
            }
            return escapeHtmlText(points);
        }

        // Seconds -> "mm:ss" (or "h:mm:ss"); used by the popup audio player
        function formatAudioTime(sec) {
            sec = Math.floor(Number(sec));
            if (!isFinite(sec) || sec < 0) return '00:00';
            var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            return h > 0 ? h + ':' + pad(m) + ':' + pad(s) : pad(m) + ':' + pad(s);
        }

        function setupModalAudio(audioUrl) {
            stopModalAudio();
            modalAudioObj = new Audio(audioUrl);
            modalAudioObj.preload = 'metadata';
            $('#modal-audio-time').text('Loading...');

            modalAudioObj.onerror = function () {
                $('#modal-audio-icon').attr('class', 'fas fa-play');
                $('#modal-audio-time').text('Not available');
                lgToast('error', 'The recording audio could not be loaded. It may have been deleted, or your session expired - refresh the page and try again.');
            };

            modalAudioObj.ontimeupdate = function() {
                if (!modalAudioObj || !modalAudioObj.duration) return;
                var pct = (modalAudioObj.currentTime / modalAudioObj.duration) * 100;
                $('#modal-audio-progress').css('width', pct + '%');
                $('#modal-audio-time').text(formatAudioTime(modalAudioObj.currentTime) + ' / ' + formatAudioTime(modalAudioObj.duration));
            };

            modalAudioObj.onended = function() {
                $('#modal-audio-icon').attr('class', 'fas fa-play');
                $('#modal-audio-progress').css('width', '0%');
                if (modalAudioObj && modalAudioObj.duration) {
                    $('#modal-audio-time').text('00:00 / ' + formatAudioTime(modalAudioObj.duration));
                }
            };

            modalAudioObj.onloadedmetadata = function() {
                if (modalAudioObj && modalAudioObj.duration) {
                    $('#modal-audio-time').text('00:00 / ' + formatAudioTime(modalAudioObj.duration));
                }
            };

            $('#modal-audio-download').attr('href', audioUrl);
        }

        function toggleModalAudio() {
            if (!modalAudioObj) return;
            if (modalAudioObj.paused) {
                $('#modal-audio-icon').attr('class', 'fas fa-spinner fa-spin');
                var p = modalAudioObj.play();
                var playing = function () { $('#modal-audio-icon').attr('class', 'fas fa-pause'); };
                if (p && p.then) {
                    p.then(playing).catch(function () { $('#modal-audio-icon').attr('class', 'fas fa-play'); });
                } else {
                    playing();
                }
            } else {
                modalAudioObj.pause();
                $('#modal-audio-icon').attr('class', 'fas fa-play');
            }
        }

        function stopModalAudio() {
            if (modalAudioObj) {
                modalAudioObj.pause();
                modalAudioObj.currentTime = 0;
                modalAudioObj = null;
            }
            $('#modal-audio-icon').attr('class', 'fas fa-play');
            $('#modal-audio-progress').css('width', '0%');
            $('#modal-audio-time').text('00:00 / 00:00');
        }

        function seekModalAudio(e) {
            if (!modalAudioObj || !modalAudioObj.duration) return;
            var track = $('#modal-audio-track');
            var offset = e.pageX - track.offset().left;
            var pct = offset / track.width();
            modalAudioObj.currentTime = pct * modalAudioObj.duration;
        }

        function escapeHtmlText(text) {
            return $('<div>').text(text).html();
        }

        function retryTranscriptProcess() {
            if (!activeTranscriptLogId || activeTranscriptSourceCode === null) return;

            $('#transcript-error').hide();
            $('#transcript-content').hide();
            $('#transcript-loading-text').text('Queued - starting the transcript...');
            $('#transcript-loading-sub').text('You can close this window - the transcript keeps processing in the background.').show();
            $('#transcript-loading').show();

            lgTranscribeOne(activeTranscriptLogId, activeTranscriptSourceCode, null, true);
        }

        function copyTranscriptText() {
            var txt = activeTranscriptText || $('#transcript-text-box').text();
            if (!txt) return;
            var fallback = function () {
                var temp = $('<textarea>');
                $('body').append(temp);
                temp.val(txt).select();
                document.execCommand('copy');
                temp.remove();
                lgToast('success', 'Transcript copied to clipboard.');
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(txt).then(function () {
                    lgToast('success', 'Transcript copied to clipboard.');
                }).catch(fallback);
            } else {
                fallback();
            }
        }

        // Excel Export
        function print_as(stat) {
            if (stat === 'excel') {
                var table = document.getElementById('leads_table');
                var wb = XLSX.utils.book_new();
                var ws = XLSX.utils.table_to_sheet(table);

                Object.keys(ws).forEach(cell => {
                    if (ws[cell].v) {
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
                let range = XLSX.utils.decode_range(ws['!ref']);
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    columnWidths.push({ wch: 20 });
                }
                ws['!cols'] = columnWidths;

                XLSX.utils.book_append_sheet(wb, ws, "Call Recordings");
                XLSX.writeFile(wb, "call_recordings.xlsx");
            }
        }

        // -------------------------------------------------------------
        // Employee Multi-Select Filter Handlers
        // -------------------------------------------------------------
        function toggleEmpDropdown(e) {
            e.stopPropagation();
            $('#lg_emp_dropdown_menu').toggle();
        }

        function filterEmpList() {
            var q = $('#lg_emp_search_input').val().toLowerCase();
            $('.lg-emp-item').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(q) > -1);
            });
        }

        function toggleAllEmployees(isChecked) {
            $('.lg-emp-cb').prop('checked', isChecked);
            onEmpSelectChange();
        }

        function clearAllEmployees() {
            $('.lg-emp-cb').prop('checked', false);
            $('#lg_emp_select_all').prop('checked', false);
            $('#lg_emp_dropdown_label').text('All Employees');
            $('#telecaller_id').val('');
        }

        function onEmpSelectChange() {
            var selectedIds = [];
            var selectedNames = [];
            var allCount = $('.lg-emp-cb').length;

            $('.lg-emp-cb:checked').each(function() {
                selectedIds.push($(this).val());
                selectedNames.push($(this).data('name'));
            });

            if (selectedIds.length === 0) {
                $('#lg_emp_dropdown_label').text('All Employees');
                $('#telecaller_id').val('');
                $('#lg_emp_select_all').prop('checked', false);
            } else if (selectedIds.length === 1) {
                $('#lg_emp_dropdown_label').text(selectedNames[0]);
                $('#telecaller_id').val(selectedIds.join(','));
                $('#lg_emp_select_all').prop('checked', selectedIds.length === allCount);
            } else if (selectedIds.length === allCount) {
                $('#lg_emp_dropdown_label').text('All Employees (' + allCount + ')');
                $('#telecaller_id').val(selectedIds.join(','));
                $('#lg_emp_select_all').prop('checked', true);
            } else {
                $('#lg_emp_dropdown_label').text(selectedIds.length + ' Employees selected');
                $('#telecaller_id').val(selectedIds.join(','));
                $('#lg_emp_select_all').prop('checked', false);
            }

            if (dataTable) dataTable.draw();
        }

        // -------------------------------------------------------------
        // Selective Transcription: Selection & Action Bar Handling
        // -------------------------------------------------------------
        var selectedRecordings = {};

        function lgToggleSelectAll(isChecked) {
            $('.lg-recording-row-cb').each(function() {
                if (!$(this).is(':disabled')) {
                    var key = $(this).data('key');
                    if (isChecked) {
                        selectedRecordings[key] = {
                            id: $(this).data('id'),
                            source_code: $(this).data('source'),
                            duration: lgToSeconds($(this).data('duration')),
                            status: $(this).data('status'),
                            caller: $(this).data('caller')
                        };
                        $(this).prop('checked', true);
                    } else {
                        delete selectedRecordings[key];
                        $(this).prop('checked', false);
                    }
                }
            });
            updateBulkActionBar();
        }

        function lgOnRowSelectChange() {
            $('.lg-recording-row-cb').each(function() {
                var key = $(this).data('key');
                if (this.checked) {
                    selectedRecordings[key] = {
                        id: $(this).data('id'),
                        source_code: $(this).data('source'),
                        duration: lgToSeconds($(this).data('duration')),
                        status: $(this).data('status'),
                        caller: $(this).data('caller')
                    };
                } else {
                    delete selectedRecordings[key];
                }
            });
            updateBulkActionBar();
        }

        function syncRowCheckboxes() {
            var allChecked = true;
            var visibleCbs = $('.lg-recording-row-cb:not(:disabled)');
            if (visibleCbs.length === 0) {
                allChecked = false;
            } else {
                visibleCbs.each(function() {
                    var key = $(this).data('key');
                    if (selectedRecordings[key]) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                        allChecked = false;
                    }
                });
            }
            $('#lg_select_all').prop('checked', allChecked);
        }

        function updateBulkActionBar() {
            var keys = Object.keys(selectedRecordings);
            var count = keys.length;
            if (!lgZipInProgress) {
                $('#btn_download_selected').prop('disabled', count === 0);
            }
            if (count > 0) {
                var totalSec = 0;
                keys.forEach(function(k) {
                    totalSec += (selectedRecordings[k].duration || 0);
                });
                $('#lg_selected_count_badge').text(count);
                $('#lg_bulk_duration_summary').text('Total duration: ' + lgFormatDuration(totalSec));
                $('#lg_bulk_action_bar').slideDown(150);
            } else {
                $('#lg_bulk_action_bar').slideUp(150);
                $('#lg_select_all').prop('checked', false);
            }
        }

        // Bulk download: the server puts the selected recordings into one ZIP, then the browser downloads it
        var lgZipInProgress = false;

        function downloadSelectedRecordings() {
            var keys = Object.keys(selectedRecordings);
            if (keys.length === 0) {
                lgToast('warning', 'Please select at least one recording to download.');
                return;
            }
            if (lgZipInProgress) return;
            var items = keys.map(function (k) {
                return { id: selectedRecordings[k].id, source_code: selectedRecordings[k].source_code };
            });
            var $btn = $('#btn_download_selected');
            var original = $btn.html();
            lgZipInProgress = true;
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <span>Preparing ZIP...</span>');

            $.ajax({
                url: "{{ route('recordingsBulkDownload') }}",
                type: 'POST',
                dataType: 'json',
                data: { _token: "{{ csrf_token() }}", items: items }
            }).done(function (res) {
                if (res && res.success && res.download_url) {
                    var skipped = (res.missing ? res.missing.length : 0) + (res.denied || 0);
                    lgToast(skipped ? 'warning' : 'success', skipped ? res.message : res.added + ' recording' + (res.added > 1 ? 's' : '') + ' downloading as one ZIP file.');
                    window.location.href = res.download_url;   // attachment: the page stays open
                } else {
                    lgToast('error', (res && res.message) || 'The recordings could not be downloaded.');
                }
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'The recordings could not be downloaded.';
                lgToast('error', msg);
            }).always(function () {
                lgZipInProgress = false;
                $btn.html(original).prop('disabled', Object.keys(selectedRecordings).length === 0);
            });
        }

        function lgClearSelection() {
            selectedRecordings = {};
            $('.lg-recording-row-cb').prop('checked', false);
            $('#lg_select_all').prop('checked', false);
            updateBulkActionBar();
        }

        // -------------------------------------------------------------
        // Selective Transcription: Dynamic Cost Estimation & Confirmation
        // -------------------------------------------------------------
        var currentEstimateBatch = [];

        function openBulkCostEstimateModal() {
            var keys = Object.keys(selectedRecordings);
            if (keys.length === 0) {
                alert('Please select at least one recording to transcribe.');
                return;
            }
            currentEstimateBatch = keys.map(function(k) {
                return {
                    id: selectedRecordings[k].id,
                    source_code: selectedRecordings[k].source_code
                };
            });
            fetchEstimateAndOpenModal(currentEstimateBatch);
        }

        function requestSingleTranscription(id, source_code, isRetry) {
            currentEstimateBatch = [{
                id: id,
                source_code: source_code
            }];
            fetchEstimateAndOpenModal(currentEstimateBatch);
        }

        function fetchEstimateAndOpenModal(recordings) {
            $('#lg-cost-loading').show();
            $('#lg-cost-content').hide();
            $('#lg-cost-processing').hide();
            $('#lg-cost-completed-summary').hide();
            $('#lg-cost-completed-footer').hide();
            $('#lg-cost-footer').show();
            $('#btn-confirm-start-transcript').prop('disabled', false).html('<i class="fas fa-check me-1"></i> Confirm &amp; Start Transcript');
            $('#lg-breakdown-container').hide();
            $('#lg-breakdown-toggle-text').text('Show Details');
            $('#lg-breakdown-chevron').attr('class', 'fas fa-chevron-down ms-1');

            if ($('#lg-cost-estimate-modal').parent().is(':not(body)')) {
                $('#lg-cost-estimate-modal').appendTo('body');
            }
            $('#lg-cost-estimate-modal').modal('show');

            var url = window.location.origin + "/admin/reports/recordings/transcription/estimate";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    _token: "{{ csrf_token() }}",
                    recordings: recordings
                },
                dataType: "json",
                success: function(res) {
                    $('#lg-cost-loading').hide();
                    if (res.status === 'success' && res.data) {
                        var d = res.data;
                        $('#modal-recording-count').text(d.recording_count);
                        $('#modal-total-duration').text(d.formatted_duration);
                        $('#modal-unit-rate').text(d.currency_symbol + Number(d.price_per_unit).toFixed(4) + ' / ' + d.pricing_unit);
                        $('#modal-billable-minutes').text(Number(d.billable_minutes).toFixed(2));
                        $('#modal-estimated-cost').text(d.currency_symbol + d.formatted_estimated_cost);
                        $('#modal-provider-name').text(d.provider || 'Sarvam AI');

                        var tbodyHtml = '';
                        (d.recordings || []).forEach(function(rec) {
                            tbodyHtml += '<tr>' +
                                '<td style="padding: 6px 12px; font-weight: 600; color: var(--lg-accent);">#' + (rec.call_id || rec.id) + '</td>' +
                                '<td style="padding: 6px 12px;">' + escapeHtmlText(rec.agent_name || rec.called_by || '—') + '</td>' +
                                '<td style="padding: 6px 12px;">' + (rec.duration_formatted || lgFormatDuration(rec.duration_seconds)) + '</td>' +
                                '<td style="padding: 6px 12px; text-align: right; font-weight: 600; color: #0F172A;">' + d.currency_symbol + Number(rec.estimated_cost).toFixed(2) + '</td>' +
                            '</tr>';
                        });
                        $('#lg-breakdown-tbody').html(tbodyHtml);

                        $('#lg-cost-content').show();
                    } else {
                        alert(res.message || 'Unable to calculate transcription cost estimate.');
                        $('#lg-cost-estimate-modal').modal('hide');
                    }
                },
                error: function(xhr) {
                    $('#lg-cost-loading').hide();
                    var msg = 'Failed to calculate transcription cost estimate.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                    $('#lg-cost-estimate-modal').modal('hide');
                }
            });
        }

        function lgToggleBreakdownTable() {
            var container = $('#lg-breakdown-container');
            if (container.is(':visible')) {
                container.slideUp(150);
                $('#lg-breakdown-toggle-text').text('Show Details');
                $('#lg-breakdown-chevron').attr('class', 'fas fa-chevron-down ms-1');
            } else {
                container.slideDown(150);
                $('#lg-breakdown-toggle-text').text('Hide Details');
                $('#lg-breakdown-chevron').attr('class', 'fas fa-chevron-up ms-1');
            }
        }

        function confirmAndStartTranscription() {
            if (!currentEstimateBatch || currentEstimateBatch.length === 0) {
                alert('No recordings specified.');
                return;
            }

            $('#lg-cost-content').hide();
            $('#lg-cost-footer').hide();
            $('#lg-cost-completed-summary').hide();
            $('#lg-cost-completed-footer').hide();
            $('#lg-cost-processing').show();
            $('#lg-progress-title').text('Processing ' + currentEstimateBatch.length + ' Transcription Job(s)...');
            $('#lg-progress-desc').text('Submitting audio to Sarvam AI Speech Engine...');
            $('#lg-batch-progress-bar, #lg-cost-progress-bar').css('width', '25%');
            $('#lg-progress-status-text, #modal-proc-status-text').text('Submitting audio...');
            $('#lg-progress-count-text').text('Please wait...');

            var url = window.location.origin + "/admin/reports/recordings/transcription/jobs";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    _token: "{{ csrf_token() }}",
                    recordings: currentEstimateBatch
                },
                dataType: "json",
                success: function(res) {
                    var b = res.data || res;
                    var isSuccess = (res.status === 'success') || (b && b.status === 'success');

                    if (isSuccess) {
                        var batchId = b.batch_id || res.batch_id;
                        var isFinished = b.is_finished || (b.completed !== undefined && b.total !== undefined && ((b.completed + (b.failed || 0)) >= b.total));

                        if (isFinished) {
                            showTranscriptionBatchComplete(b, res.message);
                        } else if (batchId) {
                            $('#lg-batch-progress-bar, #lg-cost-progress-bar').css('width', '50%');
                            $('#lg-progress-desc').text('Jobs queued. Transcribing recordings in background...');
                            $('#lg-progress-status-text, #modal-proc-status-text').text('Transcribing...');
                            startPollingBatchStatus(batchId);
                        } else {
                            showTranscriptionBatchComplete(b, res.message);
                        }
                    } else {
                        $('#lg-progress-title').text('Processing Failed');
                        $('#lg-progress-desc').text(res.message || 'Unable to create transcription jobs.');
                        $('#lg-cost-footer').show();
                        $('#btn-confirm-start-transcript').prop('disabled', false).html('<i class="fas fa-redo me-1"></i> Try Again');
                    }
                },
                error: function(xhr) {
                    var msg = 'Failed to submit transcription jobs.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#lg-progress-title').text('Processing Failed');
                    $('#lg-progress-desc').text(msg);
                    $('#lg-cost-footer').show();
                    $('#btn-confirm-start-transcript').prop('disabled', false).html('<i class="fas fa-redo me-1"></i> Try Again');
                }
            });
        }

        var batchPollTimer = null;

        function startPollingBatchStatus(batchId) {
            if (batchPollTimer) clearInterval(batchPollTimer);

            var attempts = 0;
            var maxAttempts = 120; // 4 minutes max

            batchPollTimer = setInterval(function() {
                attempts++;
                var url = window.location.origin + "/admin/reports/recordings/transcription/status/" + batchId;

                $.ajax({
                    type: "GET",
                    url: url,
                    dataType: "json",
                    success: function(res) {
                        if (res.status === 'success' && res.data) {
                            var b = res.data;
                            var total = b.total || 1;
                            var done = (b.completed || 0) + (b.failed || 0);
                            var pct = Math.max(30, Math.min(100, Math.round((done / total) * 100)));

                            $('#lg-batch-progress-bar, #lg-cost-progress-bar').css('width', pct + '%');
                            $('#lg-progress-status-text, #modal-proc-status-text').text((b.completed || 0) + ' completed, ' + (b.failed || 0) + ' failed');
                            $('#lg-progress-count-text').text(done + ' of ' + total + ' total');

                            if (b.is_finished || b.in_progress === 0 || attempts >= maxAttempts) {
                                showTranscriptionBatchComplete(b);
                            }
                        }
                    },
                    error: function() {
                        if (attempts >= maxAttempts) {
                            clearInterval(batchPollTimer);
                            batchPollTimer = null;
                            alert('Transcription batch processing timed out. Please refresh the page.');
                        }
                    }
                });
            }, 2000);
        }

        function showTranscriptionBatchComplete(b, customMsg) {
            if (batchPollTimer) {
                clearInterval(batchPollTimer);
                batchPollTimer = null;
            }

            $('#lg-batch-progress-bar, #lg-cost-progress-bar').css('width', '100%');
            $('#lg-cost-processing').hide();
            $('#lg-cost-footer').hide();
            $('#lg-cost-completed-summary').show();
            $('#lg-cost-completed-footer').show();

            var completedCount = b.completed || 0;
            var failedCount = b.failed || 0;
            var costStr = b.total_actual_cost_formatted || ('₹' + Number(b.total_actual_cost || 0).toFixed(2));

            $('#modal-comp-success').text(completedCount);
            $('#modal-comp-failed').text(failedCount);
            $('#modal-comp-cost').text(costStr);

            var finishMsg = customMsg || (completedCount + ' recording(s) successfully transcribed and billed to client.');
            if (failedCount > 0 && !customMsg) {
                finishMsg += ' ' + failedCount + ' recording(s) failed and were NOT charged.';
            }
            $('#lg-complete-msg').text(finishMsg);

            // Populate clickable list of completed transcripts
            renderCompletedTranscriptsList(b.results || currentEstimateBatch);

            lgClearSelection();
            if (dataTable) dataTable.ajax.reload(null, false);
        }

        function renderCompletedTranscriptsList(items) {
            var $container = $('#lg-completed-transcripts-container');
            var $list = $('#lg-completed-transcripts-list');
            $list.empty();

            if (!items || items.length === 0) {
                $container.hide();
                return;
            }

            var renderedCount = 0;
            items.forEach(function(item) {
                var callId = item.call_id || item.id || item.log_id;
                var sourceCode = (item.source_code !== undefined) ? item.source_code : (item.source !== undefined ? item.source : 2);
                var isSuccess = (item.success !== false) && (item.status !== 'FAILED');
                var caller = item.agent_name || item.called_by || '';
                var phone = item.phonenumber || item.phone_number || '';
                var details = [phone, caller].filter(Boolean).join(' • ');

                if (callId) {
                    renderedCount++;
                    var html = '<div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 6px; border-bottom: 1px solid #F1F5F9;">' +
                        '<div>' +
                            '<span style="font-weight: 700; color: #0F172A; font-size: 13px;">Call #' + escapeHtmlText(String(callId)) + '</span>' +
                            (details ? '<span style="font-size: 11.5px; color: #64748B; margin-left: 8px;">(' + escapeHtmlText(details) + ')</span>' : '') +
                        '</div>' +
                        '<div>' +
                            (isSuccess ? 
                                '<button type="button" class="btn btn-sm" onclick="viewTranscriptFromBatchModal(' + callId + ', ' + sourceCode + ')" style="background: #10B981; color: #FFFFFF; font-weight: 600; font-size: 11.5px; padding: 4px 12px; border-radius: 6px; border: none; cursor: pointer;">' +
                                    '<i class="fas fa-file-alt me-1"></i> View Transcript' +
                                '</button>' :
                                '<span class="badge" style="background: #FEE2E2; color: #DC2626; font-size: 11px;">Failed</span>'
                            ) +
                        '</div>' +
                    '</div>';
                    $list.append(html);
                }
            });

            if (renderedCount > 0) {
                $('#lg-completed-transcripts-count').text(renderedCount + ' call' + (renderedCount > 1 ? 's' : ''));
                $container.show();
            } else {
                $container.hide();
            }
        }

        function viewTranscriptFromBatchModal(id, source_code) {
            $('#lg-cost-estimate-modal').modal('hide');
            setTimeout(function() {
                openTranscriptModal(id, source_code);
            }, 300);
        }

        function finishBatchAndReload() {
            $('#lg-cost-estimate-modal').modal('hide');
            if (dataTable) dataTable.ajax.reload(null, false);
        }

        function openCompletedTranscriptModal(id, source_code) {
            openTranscriptModal(id, source_code);
        }

    </script>
@endpush
