@extends('dashboard.layouts.master')
@section('title', 'Products')

@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<style>
/* ==========================================================================
   LeadGER Products & Services Page Redesign
   Scoped strictly under .products-services-page container
   ========================================================================== */
.products-services-page {
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background-color: #F8FAFD;
    padding: 24px 32px;
    min-height: calc(100vh - 60px);
    color: #172B4D;
    box-sizing: border-box;
}

/* Page Header */
.products-services-page .lg-ps-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.products-services-page .lg-ps-title {
    font-size: 24px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px 0;
    line-height: 1.2;
}

.products-services-page .lg-ps-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
    font-weight: 500;
}

.products-services-page .lg-ps-btn-add {
    height: 42px;
    padding: 0 20px;
    background: #1769E8;
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(23, 105, 232, 0.2);
    cursor: pointer;
}

.products-services-page .lg-ps-btn-add:hover {
    background: #1056C7;
    color: #FFFFFF !important;
}

/* Card Container */
.products-services-page .lg-ps-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

/* Table Toolbar Controls */
.products-services-page .lg-dt-toolbar {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 24px !important;
    border-bottom: 1px solid #F1F5F9 !important;
    gap: 16px !important;
    flex-wrap: nowrap !important;
}

.products-services-page #lg_length_container {
    display: flex !important;
    align-items: center !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Fix "Show 30 entries" Length Dropdown - Single Horizontal Inline Control */
.products-services-page .dataTables_length {
    display: flex !important;
    align-items: center !important;
    font-size: 13.5px !important;
    color: #475569 !important;
    font-weight: 500 !important;
    margin: 0 !important;
    padding: 0 !important;
    float: none !important;
}

.products-services-page .dataTables_length label {
    display: inline-flex !important;
    align-items: center !important;
    flex-direction: row !important;
    gap: 4px !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 13.5px !important;
    color: #475569 !important;
    font-weight: 500 !important;
    white-space: nowrap !important;
    float: none !important;
    cursor: default !important;
    line-height: 1 !important;
}

.products-services-page .dataTables_length select {
    display: inline-block !important;
    height: 38px !important;
    width: 88px !important;
    min-width: 84px !important;
    max-width: 100px !important;
    padding: 0 28px 0 12px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #0F172A !important;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center !important;
    border: 1px solid #CBD5E1 !important;
    border-radius: 8px !important;
    outline: none !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    cursor: pointer !important;
    box-sizing: border-box !important;
    line-height: 36px !important;
    margin: 0 6px !important;
    float: none !important;
    vertical-align: middle !important;
}

.products-services-page .dataTables_length select:focus {
    border-color: #1769E8 !important;
    box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12) !important;
}

/* Search Input Box */
.products-services-page .lg-ps-search-box {
    position: relative;
    width: 280px;
    max-width: 100%;
}

.products-services-page .lg-ps-search-icon-inside {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.products-services-page .lg-ps-search-input {
    width: 100%;
    height: 38px;
    padding: 0 36px 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.products-services-page .lg-ps-search-input::placeholder {
    color: #94A3B8;
}

.products-services-page .lg-ps-search-input:focus {
    border-color: #1769E8;
    box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12);
}

/* Table Responsive & Base DataTables */
.products-services-page .lg-ps-table-responsive {
    overflow-x: auto;
    width: 100%;
}

.products-services-page table.dataTable {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
}

.products-services-page table.dataTable thead th {
    background: #F8FAFC !important;
    color: #475569 !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 14px 24px !important;
    border-top: none !important;
    border-bottom: 1px solid #E2E8F0 !important;
    white-space: nowrap;
}

.products-services-page table.dataTable tbody td {
    padding: 16px 24px !important;
    border-bottom: 1px solid #F1F5F9 !important;
    vertical-align: middle !important;
    color: #0F172A !important;
    font-size: 13.5px !important;
}

.products-services-page table.dataTable tbody tr:hover {
    background-color: #F8FAFC !important;
}

/* Row Content Styling */
.products-services-page .lg-ps-index {
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
}

.products-services-page .lg-ps-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.products-services-page .lg-ps-icon-badge {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.products-services-page .lg-ps-name-text {
    font-weight: 600;
    color: #0F172A;
    font-size: 13.5px;
}

.products-services-page .lg-ps-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.products-services-page .lg-ps-btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 12px;
    background: #EFF6FF;
    color: #1769E8 !important;
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
}

.products-services-page .lg-ps-btn-edit:hover {
    background: #1769E8;
    color: #FFFFFF !important;
    border-color: #1769E8;
}

.products-services-page .lg-ps-btn-more {
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

.products-services-page .lg-ps-btn-more:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

/* Footer & Pagination Container */
.products-services-page .lg-ps-card .lg-dt-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
    border-top: 1px solid #F1F5F9;
}

.products-services-page .dataTables_info {
    font-size: 13px !important;
    color: #64748B !important;
    font-weight: 500 !important;
}

.products-services-page .dataTables_info strong {
    color: #0F172A;
    font-weight: 600;
}

/* DataTables Custom Pagination */
.products-services-page .dataTables_paginate {
    margin: 0 !important;
}

.products-services-page .dataTables_paginate .pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.products-services-page .dataTables_paginate .paginate_button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 34px !important;
    min-width: 34px !important;
    padding: 0 12px !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background: #F8FAFC !important;
    border: 1px solid #E2E8F0 !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
    cursor: pointer !important;
    margin: 0 !important;
}

.products-services-page .dataTables_paginate .paginate_button.current,
.products-services-page .dataTables_paginate .paginate_button.active {
    background: #1769E8 !important;
    color: #FFFFFF !important;
    border-color: #1769E8 !important;
    box-shadow: 0 2px 6px rgba(23, 105, 232, 0.25) !important;
}

.products-services-page .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
    background: #F1F5F9 !important;
    color: #0F172A !important;
    border-color: #CBD5E1 !important;
}

.dataTables_filter {
    display: none !important;
}

/* ==========================================================================
   LeadGER Add/Edit Product Modal Popup Styling
   Matching LeadGER Add Staff Modal Reference
   ========================================================================== */
.lg-modal-overlay {
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

.lg-modal-overlay.active {
    display: flex !important;
}

.lg-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    overflow: hidden;
    animation: lgModalPopup 0.2s ease-out;
    margin: auto;
    display: flex;
    flex-direction: column;
}

@keyframes lgModalPopup {
    from {
        opacity: 0;
        transform: scale(0.96);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.lg-modal-header-bar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.lg-modal-header-title {
    font-size: 18px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-modal-close-btn {
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
}

.lg-modal-close-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
    border-color: #CBD5E1;
}

.lg-modal-body {
    padding: 24px 28px;
    background: #FFFFFF;
    box-sizing: border-box;
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-modal-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.lg-modal-control {
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

.lg-modal-control::placeholder {
    color: #94A3B8;
}

.lg-modal-control:focus {
    border-color: #1769E8;
    box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12);
}

.lg-modal-footer-bar {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}

.lg-btn-modal-cancel {
    height: 40px;
    padding: 0 20px;
    background: #FFFFFF;
    color: #172B4D;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.lg-btn-modal-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-btn-modal-submit {
    height: 40px;
    padding: 0 22px;
    background: #1769E8;
    color: #FFFFFF;
    border: none;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(23, 105, 232, 0.2);
}

.lg-btn-modal-submit:hover {
    background: #1056C7;
    color: #FFFFFF;
}

.lg-toast-notification {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0F172A;
    color: #FFFFFF;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.3);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 100000;
    animation: lgToastFade 0.25s ease-out;
}
@keyframes lgToastFade {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive adjustments for toolbar */
@media (max-width: 640px) {
    .products-services-page .lg-dt-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }
    .products-services-page .lg-ps-search-box {
        width: 100% !important;
    }
}
</style>
@endpush

@section('content')
<div class="products-services-page">

    <!-- 1. PAGE HEADER -->
    <div class="lg-ps-header">
        <div>
            <h1 class="lg-ps-title">Products</h1>
            <p class="lg-ps-subtitle">Manage and organize your products.</p>
        </div>

        @if(@Auth::user()->permissionsGroup->add_status)
            <a class="lg-ps-btn-add" href="{{ route('productServicesCreate') }}" onclick="openAddProductModal(); return false;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>New Products</span>
            </a>
        @endif
    </div>

    <!-- 2. MAIN TABLE CARD -->
    <div class="lg-ps-card">

        <!-- TABLE TOOLBAR CONTROLS (SHOW ENTRIES ON LEFT, SEARCH INPUT ON RIGHT) -->
        <div class="lg-dt-toolbar">
            <div id="lg_length_container"></div>
            <div class="lg-ps-search-box">
                <input type="text" name="find_q" id="find_q" class="lg-ps-search-input" placeholder="Search products..." autocomplete="off">
                <div class="lg-ps-search-icon-inside">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="lg-ps-table-responsive">
            <table class="table display nowrap" id="leads_table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>PRODUCT NAME</th>
                        @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                            <th class="text-right" style="width: 140px; text-align: right !important; padding-right: 24px !important;">ACTIONS</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>

    </div>

</div>

<!-- ==================================================
     REDESIGNED ADD / EDIT PRODUCT MODAL (POPUP)
================================================== -->
<div id="lgProductModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        
        <!-- MODAL HEADER -->
        <div class="lg-modal-header-bar">
            <h3 class="lg-modal-header-title" id="modalProductTitle">Add Product</h3>
            <button type="button" class="lg-modal-close-btn" id="btnCloseProductModal" title="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- MODAL FORM BODY -->
        <form id="lgProductForm" onsubmit="return false;">
            <input type="hidden" id="product_id" value="">
            <div class="lg-modal-body">
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label" for="product_name">Product Name</label>
                    <input type="text" 
                           id="product_name" 
                           class="lg-modal-control" 
                           placeholder="Enter product name..." 
                           autocomplete="off">
                    <div id="product_name_error" style="color: #EF4444; font-size: 12.5px; font-weight: 500; margin-top: 6px; display: none;">Product name is required.</div>
                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="lg-modal-footer-bar">
                <button type="button" class="lg-btn-modal-cancel" id="btnCancelProductModal">
                    Cancel
                </button>
                <button type="button" class="lg-btn-modal-submit" id="btnSubmitProductModal">
                    Add Product
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push("after-scripts")
    <script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
    <script type="text/javascript">
        var dataTable;

        function lgGetPsBadge(name) {
            var n = (name || '').toLowerCase();
            if (n.indexOf('youtube') !== -1 || n.indexOf('video') !== -1) {
                return {
                    bg: '#FEE2E2',
                    color: '#EF4444',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>'
                };
            } else if (n.indexOf('website') !== -1 || n.indexOf('web') !== -1 || n.indexOf('dev') !== -1) {
                return {
                    bg: '#CCFBF1',
                    color: '#0D9488',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'
                };
            } else if (n.indexOf('telozar') !== -1 || n.indexOf('phone') !== -1 || n.indexOf('call') !== -1) {
                return {
                    bg: '#F3E8FF',
                    color: '#9333EA',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                };
            } else if (n.indexOf('stationery') !== -1 || n.indexOf('stationary') !== -1 || n.indexOf('office') !== -1) {
                return {
                    bg: '#FFEDD5',
                    color: '#EA580C',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>'
                };
            } else if (n.indexOf('facebook') !== -1 || n.indexOf('fb') !== -1) {
                return {
                    bg: '#DBEAFE',
                    color: '#1464F4',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'
                };
            } else if (n.indexOf('social') !== -1 || n.indexOf('media') !== -1 || n.indexOf('management') !== -1) {
                return {
                    bg: '#D1FAE5',
                    color: '#059669',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'
                };
            } else {
                return {
                    bg: '#EFF6FF',
                    color: '#1769E8',
                    svg: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>'
                };
            }
        }

        function openAddProductModal() {
            $('#product_id').val('');
            $('#product_name').val('');
            $('#product_name_error').hide().text('');
            $('#modalProductTitle').text('Add Product');
            $('#btnSubmitProductModal').text('Add Product');
            $('#lgProductModal').addClass('active');
            $('body').css('overflow', 'hidden');
            setTimeout(function() { $('#product_name').focus(); }, 150);
        }

        function openEditProductModal(id, name) {
            $('#product_id').val(id);
            $('#product_name').val(name || '');
            $('#product_name_error').hide().text('');
            $('#modalProductTitle').text('Edit Product');
            $('#btnSubmitProductModal').text('Save Changes');
            $('#lgProductModal').addClass('active');
            $('body').css('overflow', 'hidden');
            setTimeout(function() { $('#product_name').focus(); }, 150);
        }

        function closeProductModal() {
            $('#lgProductModal').removeClass('active');
            $('body').css('overflow', '');
        }

        function saveProduct() {
            var rawName = $('#product_name').val();
            var name = (rawName || '').trim();
            var id = $('#product_id').val();

            if (!name) {
                $('#product_name_error').text('Product name is required.').show();
                $('#product_name').focus();
                return;
            }
            $('#product_name_error').hide();

            var $btn = $('#btnSubmitProductModal');
            $btn.prop('disabled', true).css('opacity', '0.7');

            var url = id ? '{{ url("/admin/productServices") }}/' + id + '/update' : '{{ route("productServicesStore") }}';
            var isEdit = !!id;

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name
                },
                dataType: 'json',
                success: function(res) {
                    $btn.prop('disabled', false).css('opacity', '1');
                    closeProductModal();
                    if (typeof dataTable !== 'undefined') {
                        dataTable.draw(false);
                    }
                    var msg = isEdit ? 'Product updated successfully' : 'Product added successfully';
                    showProductToast(res.message || msg);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).css('opacity', '1');
                    var errMsg = 'Product name is required.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#product_name_error').text(errMsg).show();
                }
            });
        }

        function showProductToast(msg) {
            if (typeof toastr !== 'undefined') {
                toastr.success(msg);
            } else {
                $('.lg-toast-notification').remove();
                var toastHtml = '<div class="lg-toast-notification">' +
                                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
                                    '<span>' + msg + '</span>' +
                                '</div>';
                $('body').append(toastHtml);
                setTimeout(function() {
                    $('.lg-toast-notification').fadeOut(300, function() { $(this).remove(); });
                }, 3000);
            }
        }

        $(document).ready(function () {
            var table_name = "#leads_table";
            dataTable = $(table_name).DataTable({
                "processing": true,
                "serverSide": true,
                "searching": true,
                "responsive": false,
                "scrollX": false,
                "pageLength": {{ env('BACKEND_PAGINATION', 30) }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
                "ajax": {
                    "url": "{{ route('productServicesData') }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{csrf_token()}}";
                        data.find_q = $('#find_q').val();
                    }
                },
                "dom": '<"dataTables_length"l><"row"<"col-12"t>><"lg-dt-bottom"<"dataTables_info"i><"dataTables_paginate"p>>',
                "fnDrawCallback": function () {
                    if ($(table_name + '_paginate .paginate_button').length > 3) {
                        $(table_name + '_paginate')[0].style.display = "block";
                    } else {
                        $(table_name + '_paginate')[0].style.display = "none";
                    }
                    $('[data-toggle="tooltip"]').tooltip({html: true});
                },
                "initComplete": function() {
                    $('.dataTables_length').appendTo('#lg_length_container');
                },
                "language": {
                    "emptyTable": "No products or services found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "lengthMenu": "Show _MENU_ entries",
                    "search": "",
                    "searchPlaceholder": "Search products & services...",
                    "paginate": {
                        "previous": "Previous",
                        "next": "Next"
                    }
                },
                "columns": [
                    {
                        "data": null,
                        "orderable": false,
                        "searchable": false,
                        "render": function (data, type, row, meta) {
                            return '<span class="lg-ps-index">' + (meta.row + meta.settings._iDisplayStart + 1) + '</span>';
                        }
                    },
                    {
                        "data": "name", "orderable": true,
                        "render": function(data) {
                            var badge = lgGetPsBadge(data);
                            return '<div class="lg-ps-cell">' +
                                        '<div class="lg-ps-icon-badge" style="background-color:' + badge.bg + '; color:' + badge.color + ';">' +
                                            badge.svg +
                                        '</div>' +
                                        '<span class="lg-ps-name-text">' + (data || '&mdash;') + '</span>' +
                                   '</div>';
                        }
                    },
                    @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                    {
                        "data": "options", "orderable": false,
                        "render": function(data, type, row) {
                            var id = row.id || '';
                            var nameStr = (row.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                            return '<div class="lg-ps-actions">' +
                                        '<button type="button" onclick="openEditProductModal(' + id + ', \'' + nameStr + '\'); return false;" class="lg-ps-btn-edit" title="Edit">' +
                                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
                                            '<span>Edit</span>' +
                                        '</button>' +
                                        '<button type="button" class="lg-ps-btn-more" title="More Options">' +
                                            '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' +
                                        '</button>' +
                                   '</div>';
                        }
                    }
                    @endif
                ],
                "order": [[1, "asc"]]
            });

            dataTable.on('page.dt', function () {
                $('html, body').animate({
                    scrollTop: $(".lg-ps-card").offset().top - 20
                }, 'slow');
            });

            $.fn.dataTable.ext.errMode = 'none';

            var searchTimer;
            $("#find_q").on('keyup input change', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    dataTable.draw();
                }, 250);
            });

            $('#btnCloseProductModal, #btnCancelProductModal').on('click', function() {
                closeProductModal();
            });

            $('#lgProductModal').on('click', function(e) {
                if ($(e.target).hasClass('lg-modal-overlay')) {
                    closeProductModal();
                }
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#lgProductModal').hasClass('active')) {
                    closeProductModal();
                }
            });

            $('#btnSubmitProductModal').on('click', function() {
                saveProduct();
            });

            $('#product_name').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    saveProduct();
                }
            });

            @if(isset($openModal) && $openModal === 'create')
                openAddProductModal();
            @elseif(isset($openModal) && $openModal === 'edit' && isset($editProduct))
                openEditProductModal({{ $editProduct->id }}, {!! json_encode($editProduct->name) !!});
            @endif
        });
    </script>
@endpush

