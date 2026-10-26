@extends('dashboard.layouts.master')
@section('title', 'Leads')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
    <style>
        /* ==========================================================================
           LEADGER COMMAND CENTER - LEADS LIST REDESIGN
           ========================================================================== */
        
        .lg-leads-page {
            padding: 24px 30px 40px 30px;
            background: #F4F6FA;
            min-height: calc(100vh - 60px);
            font-family: var(--lg-font-sans);
            color: #0F172A;
        }

        /* --- Header Section --- */
        .lg-header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .lg-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
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
        .lg-header-titles {
            display: flex;
            flex-direction: column;
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

        /* --- Action Buttons --- */
        .lg-btn-primary {
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            background: var(--lg-accent);
            color: #FFFFFF !important;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--lg-accent);
            box-shadow: 0 2px 6px rgba(23, 105, 255, 0.22);
            cursor: pointer;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }
        .lg-btn-primary:hover {
            background: #0F52CC;
            border-color: #0F52CC;
            box-shadow: 0 4px 10px rgba(23, 105, 255, 0.3);
            transform: translateY(-1px);
        }
        .lg-btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            transition: all 0.15s ease;
        }
        .lg-btn-icon:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
            color: #0F172A;
        }

        /* --- Main Content Card --- */
        .lg-main-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            padding: 22px 24px;
        }

        /* --- Horizontal Filters Row --- */
        .lg-filters-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .lg-filter-col {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .lg-filter-col.search-col {
            flex: 1 1 260px;
            min-width: 220px;
        }
        .lg-filter-col.dropdown-col {
            flex: 0 0 auto;
        }
        .lg-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin: 0;
            text-transform: capitalize;
        }
        .lg-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .lg-search-icon {
            position: absolute;
            left: 14px;
            color: #94A3B8;
            pointer-events: none;
        }
        .lg-input-search {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 40px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0F172A;
            transition: all 0.15s ease;
            outline: none;
            box-sizing: border-box;
        }
        .lg-input-search:focus {
            border-color: var(--lg-accent);
            box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
        }
        .lg-input-search::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }

        .lg-select-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: auto;
        }
        .lg-select-wrap::before,
        .lg-select-wrap::after,
        .dropdown-col::before,
        .dropdown-col::after {
            display: none !important;
            content: none !important;
        }
        .lg-select-wrap .lg-select-arrow,
        .lg-select-wrap > svg,
        .lg-select-wrap > i,
        .dropdown-col > svg:not(.lg-search-icon),
        .dropdown-col > i {
            display: none !important;
        }
        .lg-select-input {
            width: auto;
            min-width: unset;
            max-width: 100%;
            height: 42px;
            padding: 0 30px 0 12px !important;
            background-color: #FFFFFF !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: calc(100% - 10px) center !important;
            background-size: 12px 12px !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: #0F172A !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            outline: none !important;
            box-sizing: border-box !important;
            line-height: normal !important;
        }
        .lg-select-input::-ms-expand {
            display: none !important;
        }
        .lg-select-input:focus {
            border-color: var(--lg-accent) !important;
            box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12) !important;
        }
        .lg-select-arrow {
            display: none !important;
        }

        /* --- Modern Data Table Container --- */
        .lg-table-wrapper {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: #FFFFFF;
        }
        #leads_table {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }
        #leads_table thead th {
            background: #F8FAFC !important;
            color: #475569 !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.6px !important;
            padding: 14px 18px !important;
            border-bottom: 1px solid #E2E8F0 !important;
            border-top: none !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }
        #leads_table tbody td {
            padding: 16px 18px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #F1F5F9 !important;
            font-size: 13.5px !important;
            color: #0F172A !important;
            background: #FFFFFF;
            transition: background 0.1s ease;
        }
        #leads_table tbody tr:hover td {
            background: #F8FAFC !important;
        }
        #leads_table tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* --- Table Elements --- */
        .lg-lead-id-pill {
            background: #EFF6FF;
            color: var(--lg-accent) !important;
            font-size: 13px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
            transition: all 0.15s ease;
            border: 1px solid #DBEAFE;
        }
        .lg-lead-id-pill:hover {
            background: #DBEAFE;
            color: #0F52CC !important;
            transform: scale(1.02);
        }
        .lg-lead-id-pill svg {
            color: var(--lg-accent);
        }

        .lg-campaign-text {
            color: #334155;
            font-size: 13.5px;
            font-weight: 500;
            max-width: 200px;
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

        /* --- Status Badges --- */
        .lg-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
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
        }
        .lg-status-badge.green .dot {
            background: #10B981;
        }
        .lg-status-badge.amber {
            background: #FFFBEB;
            color: #D97706;
        }
        .lg-status-badge.amber .dot {
            background: #F59E0B;
        }
        .lg-status-badge.blue {
            background: #EFF6FF;
            color: var(--lg-accent);
        }
        .lg-status-badge.blue .dot {
            background: #3B82F6;
        }
        .lg-status-badge.gray {
            background: #F1F5F9;
            color: #475569;
        }
        .lg-status-badge.gray .dot {
            background: #94A3B8;
        }

        /* --- Call Action Button --- */
        .lg-table-call-btn {
            background: transparent;
            border: none;
            color: var(--lg-accent);
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .lg-table-call-btn:hover {
            background: #EFF6FF;
            color: #0F52CC;
        }
        .lg-table-call-btn svg {
            color: var(--lg-accent);
            transition: transform 0.15s ease;
        }
        .lg-table-call-btn:hover svg {
            transform: scale(1.1);
        }

        /* --- Three Dots Action Menu --- */
        .lg-dots-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-dots-btn:hover, .lg-dots-btn[aria-expanded="true"] {
            background: #F1F5F9;
            color: #0F172A;
        }
        .lg-dropdown-menu {
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06);
            border-radius: 10px;
            padding: 6px;
            min-width: 160px;
            z-index: 1050;
        }
        .lg-dropdown-menu .dropdown-item {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.1s ease;
        }
        .lg-dropdown-menu .dropdown-item:hover {
            background: #F8FAFC;
            color: var(--lg-accent);
        }
        .lg-dropdown-menu .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        /* --- Table Bottom Controls --- */
        .lg-table-bottom-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            padding: 0 4px;
        }
        .lg-entries-info {
            font-size: 13px;
            color: #64748B;
            font-weight: 500;
        }

        /* --- Lead Details Modal Redesign --- */
        .lead-details-modal .modal-dialog {
            max-width: 1040px !important;
            width: 95% !important;
            margin: 28px auto !important;
        }
        .lead-details-modal .modal-content {
            border-radius: 16px !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.25) !important;
            background: #FFFFFF !important;
            overflow: hidden !important;
        }
        .lg-lead-name-link {
            color: #0F172A;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .lg-lead-name-link:hover {
            color: var(--lg-accent);
            text-decoration: underline;
        }

        /* Auto Dialer & Floating Widgets (Preserved) */
        #sso-hangup-container {
            position: fixed;
            bottom: 120px;
            right: 24px;
            width: 300px;
            height: 250px;
            overflow: hidden;
            background: transparent;
            border: 2px solid #265F9C;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 99999;
            display: none;
            font-family: var(--lg-font-sans);
        }
        #sso-hangup-container h3 {
            margin: 0;
            text-align: center;
            padding: 8px 12px;
            font-size: 16px;
            font-weight: bold;
            background: #265F9C;
            color: white;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            cursor: grab;
        }
        #sso-drag-handle {
            position: absolute;
            top: -6px;
            left: -6px;
            right: -6px;
            height: 28px;
            cursor: grab;
            z-index: 2;
            background: transparent;
        }
        #sso-drag-handle.dragging { cursor: grabbing; }

        @media (max-width: 992px) {
            .lg-filters-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .lg-leads-page {
                padding: 16px;
            }
            .lg-header-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .lg-filters-row {
                grid-template-columns: 1fr;
            }
            .lg-table-bottom-bar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
<div class="lg-leads-page">
    <!-- Header Section -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="lg-header-titles">
                <h1 class="lg-page-title">Leads</h1>
                <div class="lg-page-subtitle">Manage and track your leads</div>
            </div>
        </div>
        <div class="lg-header-right">
            @if(\App\Support\RoleAccess::allows(Auth::user(), 'leads.lead_list.create'))
                <a href="{{ route('leadsCreate', ['id' => $id]) }}" class="lg-btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>New Lead</span>
                </a>
            @endif

            @if(\App\Support\RoleAccess::allows(Auth::user(), 'leads.lead_list.export'))
                <button type="button" class="lg-btn-icon" id="excel_btn" title="Export to Excel" onclick="print_as('excel')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </button>
            @endif

            <button type="button" class="lg-btn-icon" id="filter_btn" title="Search Filters" onclick="$('#find_q').focus();">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="lg-main-card">
        <!-- Horizontal Filter Row -->
        <div class="lg-filters-row">
            <!-- Search -->
            <div class="lg-filter-col search-col">
                <label class="lg-filter-label" for="find_q">Search</label>
                <div class="lg-search-wrap">
                    <svg class="lg-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="find_q" class="lg-input-search" placeholder="Search by name, mobile or lead ID" autocomplete="off">
                </div>
            </div>

            <!-- Campaign -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="find_campaign">Campaign</label>
                <div class="lg-select-wrap">
                    <select id="find_campaign" class="lg-select-input">
                        <option value="">All campaigns</option>
                        @foreach($Campaigns as $Campaign)
                            <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Agent -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="find_user">Agent</label>
                <div class="lg-select-wrap">
                    <select id="find_user" class="lg-select-input">
                        <option value="">All agents</option>
                        @foreach($Users as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Status -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="filter_action">Status</label>
                <div class="lg-select-wrap">
                    <select id="filter_action" class="lg-select-input">
                        <option value="">Any status</option>
                        <option value="Actioned">Actioned</option>
                        <option value="UnActioned">UnActioned</option>
                        @php
                            $availableStatuses = \App\Models\Status::where('status', 1)->orderBy('name')->get();
                        @endphp
                        @foreach($availableStatuses as $st)
                            <option value="status_{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Modern Data Table -->
        <div class="lg-table-wrapper">
            <table class="table display nowrap" id="leads_table">
                <thead>
                    <tr>
                        <th>LEAD ID</th>
                        <th>CAMPAIGN</th>
                        <th>LEAD NAME</th>
                        <th>ACTION STATUS</th>
                        <th>STATUS</th>
                        <th>CALL</th>
                        <th style="width: 40px; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Bottom Controls placeholder for DataTables -->
        <div id="lg_table_bottom" class="lg-table-bottom-bar"></div>
    </div>
</div>

<!-- Preserved Modals -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" id="animate">
        <div class="modal-content lead-content">
        </div>
    </div>
</div>

<div id="sms-popup" class="modal fade lead-details-modal" data-backdrop="true">
    <div class="modal-dialog modal-sm" id="animate">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pull-left">Send SMS</h5>
            </div>
            <div class="modal-body lead-popup-wrap p-lg">
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Phone Number</label>
                        <select class="form-control select2" id="smsnumber" style="z-index:9999;"></select>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>SMS Content</label>
                        <textarea class="form-control" maxlength="150" id="smstext"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <div class='row'>
                    <div class="col m12 s12">
                        <span style='color:green' id='sms_success_msg'></span>
                    </div>
                </div>
                <div class='row'>
                    <div class="col m12 s12">
                        <button type="button" id="" row-id="" onclick="send_sms()" class="btn btn-fw primary">Send</button>
                        <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="email-popup" class="modal fade lead-details-modal" data-backdrop="true">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pull-left">Send Email</h5>
            </div>
            <div class="modal-body lead-popup-wrap p-lg">
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Address</label>
                        <input type='text' class="form-control" id="email_address" />
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Subject</label>
                        <input type='text' class="form-control" id="email_subject" />
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Content</label>
                        <div class="box p-a-xs">
                            {!! Form::textarea('email_content','', array('ui-jp'=>'summernote','id'=>'email_content','class' => 'form-control summernote_'.@Helper::currentLanguage()->code,'ui-options'=>'{height: 200,callbacks: {
                            onImageUpload: function(files, editor, welEditable) {
                                sendFile(files[0], editor, welEditable,"'.@Helper::currentLanguage()->code.'");
                            }
                        }}')) !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <div class='row'>
                    <div class="col m12 s12">
                        <span style='color:green' id='email_success_msg'></span>
                    </div>
                </div>
                <div class='row'>
                    <div class="col m12 s12">
                        <button type="button" id="" row-id="" onclick="send_email()" class="btn btn-fw primary">Send</button>
                        <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto Dialer Floating Controls -->
<div class="boat-deatils" style="display: none;">
    <div class="input-field">
        <div class="select-wrapper ">
            <label>Dialer Type</label>
            <select id="dialerType">
                @if(Auth::user()->c2c_channel == 1)
                <option value="click2call">Click2Call</option>
                @endif
                @if(Auth::user()->webrtc_channel == 1)
                <option value="webrtc">WebRTC</option>
                @endif
                <option value="phone">Mobile</option>
                <option value="sip">SIP</option>
            </select>
        </div>
    </div>
    <a id="startautodial" onclick="autoDialerStart()" class="btn btn-sm primary modal-trigger">Start</a>
    <a id="stopautodial" onclick="autoDialerStop()" class="btn btn-sm warn" style="display:none">Stop</a>
</div>

<div class="boatcontainer hide-mob" data-position="top" data-tooltip="Auto Dialer">
    <span class="icon-phone trin-trin"></span>
    <h6>Auto Dialer</h6>
</div>
@endsection

@push("after-scripts")
    <script src="{{ URL::asset('assets/frontend/js/Chart.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>

    <script type="text/javascript">
        let autoDialer = {
            leads: [],
            currentIndex: 0,
            isRunning: false,
            dialerType: '',
        };

        $(document).on('show.bs.modal', '.modal', function (event) {
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });

        function autoDialerStart() {
            autoDialer.dialerType = $('#dialerType').val();
            var lead_rows = $('#leads_table').DataTable().rows().data().toArray();
            
            autoDialer.leads = lead_rows.map(lead => {
                if (typeof lead.id === 'string') {
                    var match = lead.id.match(/leadDetail\('(\d+)'\)/);
                    return match ? match[1] : lead.id.replace(/[^0-9]/g, '');
                }
                return lead.id;
            }).filter(id => id);

            if (autoDialer.leads.length === 0) {
                toastr.error("No leads found to dial.");
                return;
            }

            autoDialer.currentIndex = 0;
            autoDialer.isRunning = true;
            $('#startautodial').hide();
            $('#stopautodial').show();
            openNextLead();
        }

        function autoDialerStop() {
            autoDialer.isRunning = false;
            $('#startautodial').show();
            $('#stopautodial').hide();
        }

        function leadDetail(id, callback = null) {
            if (!id) return;
            if (typeof id === 'string' && id.indexOf('<a') !== -1) {
                let match = id.match(/>([^<]+)</);
                if (match) id = match[1];
            }
            id = String(id).replace(/[^0-9]/g, '');
            if (!id) return;

            let url = "{{ route('leadDetail', [':id']) }}".replace(':id', id);
            let $modal = $('#lead-detail');

            if ($modal.parent().not('body').length) {
                $modal.appendTo('body');
            }

            function loadAndShow() {
                $('.lead-content').html(`
                    <div style="padding: 60px 20px; text-align: center; background: #FFFFFF; border-radius: 16px;">
                        <div class="spinner-border text-primary" role="status" style="width: 2.8rem; height: 2.8rem; color: var(--lg-accent) !important;">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div style="margin-top: 16px; font-size: 15px; font-weight: 600; color: #0F172A;">Loading Lead Details...</div>
                        <div style="font-size: 13px; color: #64748B; margin-top: 4px;">Fetching customer records and call history</div>
                    </div>
                `);
                $modal.modal({ backdrop: 'static', keyboard: false }).modal("show");

                $.ajax({
                    type: "GET",
                    url: url,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    dataType: "html",
                    success: function(html) {
                        $('.lead-content').html(html);
                        if ($.fn.select2) {
                            $('.lead-content select').select2();
                        }
                        if (typeof callback === 'function') {
                            callback();
                        }
                    },
                    error: function (xhr) {
                        var errMsg = "Failed to load lead details (#" + id + "). Please try again.";
                        if (xhr.status === 403) {
                            errMsg = "You do not have permission to view lead " + id + ".";
                        } else if (xhr.status === 404) {
                            errMsg = "Lead " + id + " was not found.";
                        }
                        $('.lead-content').html(`
                            <div style="padding: 48px 24px; text-align: center; background: #FFFFFF; border-radius: 16px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 22px;">
                                    <i class="fa fa-exclamation-triangle"></i>
                                </div>
                                <h5 style="font-weight: 700; color: #0F172A; margin-bottom: 8px;">Unable to Load Lead</h5>
                                <p style="color: #64748B; font-size: 13.5px; max-width: 400px; margin: 0 auto 20px auto;">` + errMsg + `</p>
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 600; padding: 6px 18px;">Close</button>
                            </div>
                        `);
                    }
                });
            }

            if ($modal.is(':visible') || $modal.hasClass('in') || $modal.hasClass('show')) {
                $modal.modal('hide');
                setTimeout(loadAndShow, 200);
            } else {
                loadAndShow();
            }
        }

        function openNextLead() {
            if (!autoDialer.isRunning || autoDialer.currentIndex >= autoDialer.leads.length) {
                autoDialerStop();
                return;
            }

            let lead = autoDialer.leads[autoDialer.currentIndex];
            if (!lead) {
                toastr.error("Invalid lead ID");
                autoDialerStop();
                return;
            }

            leadDetail(lead, () => {
                const checkReady = setInterval(() => {
                    const lead_id = $('#lead-detail').find('#lead_id').val();
                    const phone_number = $('#called_phonenumber').val();
                    if (lead_id && phone_number) {
                        clearInterval(checkReady);
                        if (autoDialer.dialerType == 'webrtc') {
                            var webrtcBtn = document.getElementById("webrtc-btn");
                            if (webrtcBtn) webrtcBtn.click();
                        } else {
                            var c2cBtn = document.getElementById("click2call-btn");
                            if (c2cBtn) c2cBtn.click();
                        }
                    }
                }, 300);
                setTimeout(() => clearInterval(checkReady), 5000);
            });
        }

        function phoneCall(call_type) {
            let lead_id = $('#lead_id').val();
            let token = "{{ csrf_token() }}";
            let phonenumber = $('#called_phonenumber').val();
            let vmn = $("#vmn").val();
            
            if (call_type === 'click2call' && (!vmn || vmn === '')) {
                toastr.error("Please select a VMN for Click2Call");
                return false;
            }

            $.ajax({
                type: "POST",
                url: "{!! route('logCall') !!}",
                data: {
                    'lead_id': lead_id, 
                    '_token': token, 
                    'phonenumber': phonenumber, 
                    'call_type': call_type, 
                    'vmn': vmn, 
                    'default_vmn': $('#default_vmn').is(":checked")
                },
                success: function(result) {
                    try {
                        if (typeof result === 'string') {
                            result = JSON.parse(result);
                        }
                        if (result && result.success) {
                            $('#save_button').attr('disabled', false);
                            $('#call_type').val(call_type);
                            $('#call_log_id').val(result.call_log_id);
                            toastr.success("Called to " + phonenumber);
                            
                            if (call_type == 'phone' && typeof Android != 'undefined') {
                                window.open("tel:" + phonenumber);
                            }
                            if (call_type == 'sip' && typeof Android == 'undefined') {
                                window.open('dcm:' + phonenumber, "_blank");
                            }
                            
                            if (autoDialer && autoDialer.isRunning) {
                                setTimeout(() => {
                                    autoDialer.currentIndex++;
                                    openNextLead();
                                }, 5000);
                            }
                        } else {
                            toastr.warning((result && result.message) ? result.message : 'Call failed');
                            if (autoDialer && autoDialer.isRunning) autoDialerStop();
                        }
                    } catch(e) {
                        toastr.error("Error processing call response");
                        if (autoDialer && autoDialer.isRunning) autoDialerStop();
                    }
                },
                error: function(xhr) {
                    var msg = 'Call failed';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (xhr && (xhr.status === 403 || xhr.status === 422)) {
                        toastr.warning(msg);
                    } else {
                        toastr.error(msg);
                    }
                    if (autoDialer && autoDialer.isRunning) autoDialerStop();
                }
            });
        }

        let currentModalLeadId = null;

        function openSmsModal(id, phone) {
            currentModalLeadId = id;
            $('#smsnumber').html('<option value="' + phone + '">' + phone + '</option>');
            $('#smstext').val('');
            $('#sms_success_msg').text('');
            $('#sms-popup').modal('show');
        }

        function openEmailModal(id, email) {
            currentModalLeadId = id;
            $('#email_address').val(email || '');
            $('#email_subject').val('');
            if (typeof $('#email_content').summernote === 'function') {
                $('#email_content').summernote('code', '');
            } else {
                $('#email_content').val('');
            }
            $('#email_success_msg').text('');
            $('#email-popup').modal('show');
        }

        function send_sms() {
            var phonenumber = $("#smsnumber").val();
            var content = $("#smstext").val();
            if (!phonenumber) {
                toastr.error("Please select a Phone Number");
                return false;
            }
            if (!content) {
                toastr.error("Please enter SMS Content");
                return false;
            }
            $.ajax({
                type: "POST",
                url: "{!! route('sendSms') !!}",
                data: {
                    _token: "{!! csrf_token() !!}",
                    lead_id: currentModalLeadId || '',
                    phonenumber: phonenumber,
                    content: content
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        toastr.success("SMS Sent Successfully");
                        $("#sms_success_msg").html('SMS Sent Successfully');
                        setTimeout(function() { $('#sms-popup').modal('hide'); }, 1200);
                    } else {
                        toastr.error(data.error || 'Failed to send SMS');
                    }
                },
                error: function() {
                    toastr.error('Failed to send SMS');
                }
            });
        }

        function send_email() {
            var email_address = $("#email_address").val();
            var email_subject = $("#email_subject").val();
            var content = $("#email_content").val();
            if (!email_address) {
                toastr.error("Please enter E-mail Address");
                return false;
            }
            if (!email_subject) {
                toastr.error("Please enter E-mail Subject");
                return false;
            }
            if (!content) {
                toastr.error("Please enter Email Content");
                return false;
            }
            $.ajax({
                type: "POST",
                url: "{!! route('sendEmail') !!}",
                data: {
                    _token: "{!! csrf_token() !!}",
                    lead_id: currentModalLeadId || '',
                    email_address: email_address,
                    email_subject: email_subject,
                    content: content
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        toastr.success("Email Sent Successfully");
                        $("#email_success_msg").html('Email Sent Successfully');
                        setTimeout(function() { $('#email-popup').modal('hide'); }, 1200);
                    } else {
                        toastr.error(data.error || 'Failed to send Email');
                    }
                },
                error: function() {
                    toastr.error('Failed to send Email');
                }
            });
        }

        var dataTable = null;

        $(document).ready(function () {
            $('#lead-detail').appendTo('body');
            $('#sms-popup').appendTo('body');
            $('#email-popup').appendTo('body');

            $(".boatcontainer").click(function(){
                $(".boat-deatils").toggle(500);
            });

            dataTable = $('#leads_table').DataTable({
                "processing": true,
                "serverSide": true,
                "searching": false,
                "responsive": false,
                "autoWidth": false,
                "scrollX": false,
                "pageLength": 30,
                "lengthMenu": [[10, 20, 30, 50, 75, 100], [10, 20, 30, 50, 75, 100]],
                "order": [[0, "desc"]],
                "ajax": {
                    "url": "{{ route('leadsList', $id) }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{ csrf_token() }}";
                        data.find_q = $('#find_q').val();
                        data.find_campaign = $('#find_campaign').val();
                        data.find_user = $('#find_user').val();
                        data.find_action = $('#filter_action').val();
                    },
                    "error": function (xhr, error, thrown) {
                        console.error("DataTables AJAX error: ", xhr.responseText || thrown);
                        if (typeof toastr !== 'undefined') {
                            toastr.error("Unable to load leads data. Please check server connection.");
                        }
                    }
                },
                "dom": 't<"lg-table-bottom-bar"<"lg-entries-info"i><"lg-pagination"p>>',
                "fnDrawCallback": function () {
                    $('[data-toggle="tooltip"]').tooltip({html: true});
                },
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "previous": "&lt;",
                        "next": "&gt;"
                    }
                },
                "columns": [
                    /* 1. LEAD ID */
                    {
                        "data": "id",
                        "orderable": true,
                        "render": function (data, type, row) {
                            if (!data) return '';
                            var leadId = data;
                            if (typeof data === 'string' && data.indexOf('<a') !== -1) {
                                var match = data.match(/>([^<]+)</);
                                if (match) leadId = match[1];
                            }
                            leadId = String(leadId).replace(/[^0-9]/g, '');
                            return '<a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-id-pill" title="View lead ' + leadId + '">' +
                                   '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' +
                                   '<span>' + leadId + '</span>' +
                                   '</a>';
                        }
                    },
                    /* 2. CAMPAIGN */
                    {
                        "data": "campaign_name",
                        "orderable": true,
                        "render": function (data) {
                            return '<span class="lg-campaign-text" title="' + (data || '') + '">' + (data || '—') + '</span>';
                        }
                    },
                    /* 3. LEAD NAME */
                    {
                        "data": "name",
                        "orderable": true,
                        "render": function (data, type, row) {
                            var leadId = row.id;
                            if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                                var match = leadId.match(/>([^<]+)</);
                                if (match) leadId = match[1];
                            }
                            leadId = String(leadId).replace(/[^0-9]/g, '');
                            return '<a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-name-link" title="View Lead Details">' + (data || 'Unnamed Lead') + '</a>';
                        }
                    },
                    /* 4. ACTION STATUS */
                    {
                        "data": "action_status",
                        "orderable": true,
                        "render": function (data) {
                            if (data === 'Actioned') {
                                return '<span class="lg-status-badge green"><span class="dot"></span>Actioned</span>';
                            } else if (data === 'Follow-up') {
                                return '<span class="lg-status-badge amber"><span class="dot"></span>Follow-up</span>';
                            } else {
                                return '<span class="lg-status-badge amber"><span class="dot"></span>UnActioned</span>';
                            }
                        }
                    },
                    /* 5. STATUS */
                    {
                        "data": "status_name",
                        "orderable": true,
                        "render": function (data) {
                            if (!data) return '<span class="lg-status-badge gray"><span class="dot"></span>Not Contactable</span>';
                            var d = data.toLowerCase();
                            var colorClass = 'gray';
                            if (d.indexOf('ringing') !== -1 || d.indexOf('contactable') !== -1) {
                                colorClass = 'gray';
                            } else if (d.indexOf('follow') !== -1 || d.indexOf('pending') !== -1) {
                                colorClass = 'amber';
                            } else if (d.indexOf('deal') !== -1 || d.indexOf('converted') !== -1 || d.indexOf('won') !== -1) {
                                colorClass = 'green';
                            } else if (d.indexOf('demo') !== -1 || d.indexOf('interested') !== -1) {
                                colorClass = 'blue';
                            }
                            return '<span class="lg-status-badge ' + colorClass + '"><span class="dot"></span>' + data + '</span>';
                        }
                    },
                    /* 6. CALL */
                    {
                        "data": "id",
                        "orderable": false,
                        "render": function (data, type, row) {
                            var leadId = row.id;
                            if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                                var match = leadId.match(/>([^<]+)</);
                                if (match) leadId = match[1];
                            }
                            leadId = String(leadId).replace(/[^0-9]/g, '');
                            return '<button type="button" class="lg-table-call-btn" onclick="leadDetail(\'' + leadId + '\')" title="Call Lead">' +
                                   '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
                                   '<span>Call</span>' +
                                   '</button>';
                        }
                    },
                    /* 7. MORE / ACTIONS */
                    {
                        "data": "id",
                        "orderable": false,
                        "className": "text-right",
                        "render": function (data, type, row) {
                            var leadId = row.id;
                            if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                                var match = leadId.match(/>([^<]+)</);
                                if (match) leadId = match[1];
                            }
                            leadId = String(leadId).replace(/[^0-9]/g, '');
                            var editUrl = "{{ route('leadsEdit', [':id']) }}".replace(':id', leadId);
                            var phone = (row.phonenumber || '').replace(/[^0-9+]/g, '');
                            var email = row.email || '';
                            
                            var menu = '<div class="dropdown" style="display:inline-block;">' +
                                       '<button class="lg-dots-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="More options">' +
                                       '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>' +
                                       '</button>' +
                                       '<div class="dropdown-menu dropdown-menu-right lg-dropdown-menu">' +
                                       '<a class="dropdown-item" href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')"><i class="fa fa-eye text-primary"></i> View Details</a>' +
                                       '<a class="dropdown-item" href="' + editUrl + '"><i class="fa fa-pencil text-info"></i> Edit Lead</a>';
                            
                            if (phone) {
                                menu += '<a class="dropdown-item" href="javascript:void(0)" onclick="openSmsModal(\'' + leadId + '\', \'' + phone + '\')"><i class="fa fa-commenting text-success"></i> Send SMS</a>';
                            }
                            if (email) {
                                menu += '<a class="dropdown-item" href="javascript:void(0)" onclick="openEmailModal(\'' + leadId + '\', \'' + email + '\')"><i class="fa fa-envelope text-warning"></i> Send Email</a>';
                            }
                            menu += '</div></div>';
                            return menu;
                        }
                    }
                ]
            });

            // Search filter with debounce
            var searchTimeout = null;
            $('#find_q').on('input keyup', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    dataTable.draw();
                }, 350);
            });

            // Dropdown filters
            $('#find_campaign, #find_user, #filter_action').on('change', function () {
                dataTable.draw();
            });

            $.fn.dataTable.ext.errMode = 'none';
        });

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

                XLSX.utils.book_append_sheet(wb, ws, "Leads");
                XLSX.writeFile(wb, "leads.xlsx");
            }
        }
    </script>
@endpush
