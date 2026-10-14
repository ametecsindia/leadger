@extends('dashboard.layouts.master')
@section('title', 'RMI Analysis - LeadGER')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Corporate SaaS Design System - RMI Analysis Page
   ========================================================================== */

.lg-rmi-dashboard {
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
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
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
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.lg-page-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.25;
    letter-spacing: -0.02em;
}

.lg-page-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

/* Filter Card */
.lg-card-section {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.lg-filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1.2fr 1.2fr auto auto;
    gap: 14px;
    align-items: flex-end;
}

.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lg-field-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
}

.lg-input-date-wrap {
    position: relative;
    width: 100%;
}

.lg-input-date-wrap svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-input-date {
    width: 100%;
    height: 42px;
    padding: 0 14px 0 40px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-input-date:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-select {
    width: 100%;
    height: 42px;
    padding: 0 28px 0 14px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    font-weight: 500;
    outline: none;
    cursor: pointer;
    appearance: none;
    box-sizing: border-box;
}

.lg-select:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-btn-search {
    height: 42px;
    padding: 0 20px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
    transition: all 0.15s ease;
}

.lg-btn-search:hover {
    background: var(--lg-accent-hover);
}

.lg-btn-reset {
    height: 42px;
    padding: 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-reset:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
}

/* Summary Metrics Cards Grid */
.lg-summary-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}

.lg-metric-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.015);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.lg-metric-title {
    font-size: 12px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 8px;
}

.lg-metric-value {
    font-size: 24px;
    font-weight: 800;
    color: #0F172A;
    line-height: 1;
}

/* Summary cards open the calls they count */
.lg-metric-link { cursor: pointer; transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease; }
.lg-metric-link:hover { border-top-color: #93B4FF; border-right-color: #93B4FF; border-bottom-color: #93B4FF; box-shadow: 0 6px 16px rgba(23, 105, 255, 0.10); }
.lg-metric-link:focus { outline: none; }
.lg-metric-link:focus-visible { box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.30); }
.lg-metric-link:active { transform: translateY(1px); }
.lg-metric-card.angry { border-left: 4px solid #EF4444; }
.lg-metric-card.angry .lg-metric-value { color: #DC2626; }

.lg-metric-card.frustrated { border-left: 4px solid #F97316; }
.lg-metric-card.frustrated .lg-metric-value { color: #EA580C; }

.lg-metric-card.confident { border-left: 4px solid var(--lg-accent); }
.lg-metric-card.confident .lg-metric-value { color: var(--lg-accent); }

.lg-metric-card.neutral { border-left: 4px solid #64748B; }
.lg-metric-card.neutral .lg-metric-value { color: #475569; }

.lg-metric-card.calm { border-left: 4px solid #10B981; }
.lg-metric-card.calm .lg-metric-value { color: #059669; }

/* Table Section */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.lg-table-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 14px;
}

.lg-entries-select-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #475569;
    font-weight: 500;
}

.lg-select-entries {
    height: 36px;
    padding: 0 24px 0 10px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 8px center;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    appearance: none;
}

.lg-table-wrapper {
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.lg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.lg-table th {
    background: #F8FAFC;
    color: #475569;
    font-weight: 700;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #E2E8F0;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.lg-table td {
    padding: 14px 16px;
    color: #1E293B;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}

.lg-table tbody tr:last-child td {
    border-bottom: none;
}

.lg-table tbody tr:hover {
    background: #F8FAFC;
}

.lg-role-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    background: #EFF6FF;
    color: var(--lg-accent-hover);
}

.lg-btn-view {
    height: 32px;
    padding: 0 12px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: var(--lg-accent);
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-view:hover {
    background: var(--lg-accent);
    color: #FFFFFF;
}

/* Footer Export Bar & Pagination */
.lg-table-footer-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-top: 14px;
    border-top: 1px solid #F1F5F9;
}

.lg-export-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-btn-export {
    height: 36px;
    padding: 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-export:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-pagination-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-pagination-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lg-page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-page-btn.active {
    background: var(--lg-accent);
    color: #FFFFFF;
    border-color: var(--lg-accent);
}

.lg-page-btn:hover:not(.active):not(:disabled) {
    background: #F8FAFC;
    border-color: #94A3B8;
}

@media (max-width: 1200px) {
    .lg-summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 991px) {
    .lg-filter-row {
        grid-template-columns: 1fr 1fr;
    }
    .lg-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
@endpush

@section('content')

<div class="lg-rmi-dashboard">

    <!-- 1. PAGE HEADER -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="2" width="6" height="12" rx="3"></rect>
                    <path d="M5 10a7 7 0 0 0 14 0M12 17v4M8 21h8"></path>
                </svg>
            </div>
            <div>
                <h1 class="lg-page-title">RMI Analysis</h1>
                <p class="lg-page-subtitle">Analyze call recordings and review RMI-based call insights.</p>
            </div>
        </div>
    </div>

    <!-- 2. FILTER SECTION -->
    <div class="lg-card-section">
        <div class="lg-filter-row">
            <!-- From Date -->
            <div class="lg-field-group">
                <label class="lg-field-label" for="filterFromDate">From Date</label>
                <div class="lg-input-date-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <input type="date" id="filterFromDate" class="lg-input-date" value="{{ date('Y-m-01') }}">
                </div>
            </div>

            <!-- To Date -->
            <div class="lg-field-group">
                <label class="lg-field-label" for="filterToDate">To Date</label>
                <div class="lg-input-date-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <input type="date" id="filterToDate" class="lg-input-date" value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <!-- Groups -->
            <div class="lg-field-group">
                <label class="lg-field-label" for="filterGroup">Groups</label>
                <select id="filterGroup" class="lg-select">
                    <option value="">All Groups</option>
                    @foreach($Teams as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Agent -->
            <div class="lg-field-group">
                <label class="lg-field-label" for="filterAgent">Agent</label>
                <select id="filterAgent" class="lg-select">
                    <option value="">All Agents</option>
                    @foreach($Users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Button -->
            <button type="button" class="lg-btn-search" id="btnSearchRmi">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>Search</span>
            </button>

            <!-- Reset Button -->
            <button type="button" class="lg-btn-reset" id="btnResetRmi" title="Reset Filters">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- 3. SUMMARY METRICS CARDS -->
    <div class="lg-summary-grid">
        {{-- Each card opens the calls it counts (same filters, same counting rules) --}}
        <div class="lg-metric-card lg-metric-link" data-rmi-cat="total_calls" role="link" tabindex="0" title="View these calls">
            <div class="lg-metric-title">Total Calls</div>
            <div class="lg-metric-value" id="summaryTotalCalls">0</div>
        </div>
        <div class="lg-metric-card lg-metric-link" data-rmi-cat="total_analyzed" role="link" tabindex="0" title="View the analyzed calls">
            <div class="lg-metric-title">Total Analyzed</div>
            <div class="lg-metric-value" id="summaryTotalAnalyzed">0</div>
        </div>
        <div class="lg-metric-card angry lg-metric-link" data-rmi-cat="angry" role="link" tabindex="0" title="View the Angry calls">
            <div class="lg-metric-title">Angry</div>
            <div class="lg-metric-value" id="summaryAngry">0</div>
        </div>
        <div class="lg-metric-card frustrated lg-metric-link" data-rmi-cat="frustrated" role="link" tabindex="0" title="View the Frustrated calls">
            <div class="lg-metric-title">Frustrated</div>
            <div class="lg-metric-value" id="summaryFrustrated">0</div>
        </div>
        <div class="lg-metric-card confident lg-metric-link" data-rmi-cat="confident" role="link" tabindex="0" title="View the Confident calls">
            <div class="lg-metric-title">Confident</div>
            <div class="lg-metric-value" id="summaryConfident">0</div>
        </div>
        <div class="lg-metric-card neutral lg-metric-link" data-rmi-cat="neutral" role="link" tabindex="0" title="View the Neutral calls">
            <div class="lg-metric-title">Neutral</div>
            <div class="lg-metric-value" id="summaryNeutral">0</div>
        </div>
        <div class="lg-metric-card calm lg-metric-link" data-rmi-cat="calm" role="link" tabindex="0" title="View the Calm calls">
            <div class="lg-metric-title">Calm</div>
            <div class="lg-metric-value" id="summaryCalm">0</div>
        </div>
    </div>

    <!-- 4. MAIN TABLE CONTAINER -->
    <div class="lg-table-card">
        <div class="lg-table-header-bar">
            <div class="lg-entries-select-wrap">
                <span>Show</span>
                <select id="selectPageLength" class="lg-select-entries">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>
        </div>

        <div class="lg-table-wrapper">
            <table class="lg-table" id="rmiAnalysisTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">S.No</th>
                        <th>Agent Name</th>
                        <th>Role</th>
                        <th>Total Calls</th>
                        <th>Total Analyzed</th>
                        <th style="width: 100px; text-align: center;">View</th>
                    </tr>
                </thead>
                <tbody id="rmiTableBody">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>

        <!-- FOOTER BAR: EXPORT & PAGINATION -->
        <div class="lg-table-footer-bar">
            <div id="paginationInfo" style="font-size: 13.5px; color: #64748B; font-weight: 500;">Showing 0 entries</div>

            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <div class="lg-pagination-nav" id="paginationNav">
                    <!-- Page buttons rendered by JS -->
                </div>

                <div class="lg-export-group">
                    <a href="{{ route('rmiAnalysisExport', 'excel') }}" class="lg-btn-export" id="btnExportExcel" target="_blank">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('rmiAnalysisExport', 'csv') }}" class="lg-btn-export" id="btnExportCsv" target="_blank">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>CSV</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- AGENT RMI DETAIL MODAL -->
<div class="modal fade" id="agentRmiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 850px;">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #E2E8F0; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 style="font-weight: 700; color: #0F172A; font-size: 18px; margin: 0;" id="modalAgentName">Agent RMI Analysis Detail</h4>
                    <span style="font-size: 13px; color: #64748B;" id="modalAgentRole">Role: -</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border: none; background: transparent; font-size: 24px; color: #64748B; cursor: pointer;">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 24px;">
                <!-- Modal Emotion Distribution Summary -->
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px;">
                    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; color: #DC2626;">Angry</div>
                        <div style="font-size: 18px; font-weight: 800; color: #991B1B;" id="modalAngry">0</div>
                    </div>
                    <div style="background: #FFEDD5; border: 1px solid #FDBA74; border-radius: 10px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; color: #EA580C;">Frustrated</div>
                        <div style="font-size: 18px; font-weight: 800; color: #C2410C;" id="modalFrustrated">0</div>
                    </div>
                    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--lg-accent);">Confident</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--lg-accent-hover);" id="modalConfident">0</div>
                    </div>
                    <div style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 10px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; color: #475569;">Neutral</div>
                        <div style="font-size: 18px; font-weight: 800; color: #334155;" id="modalNeutral">0</div>
                    </div>
                    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; padding: 12px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; color: #059669;">Calm</div>
                        <div style="font-size: 18px; font-weight: 800; color: #047857;" id="modalCalm">0</div>
                    </div>
                </div>

                <!-- Call Recordings Table -->
                <div style="border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden; max-height: 380px; overflow-y: auto;">
                    <table class="lg-table">
                        <thead>
                            <tr>
                                <th>Call ID</th>
                                <th>Lead Name</th>
                                <th>Phone</th>
                                <th>Duration</th>
                                <th>Sentiment</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody id="modalCallsTbody">
                            <!-- Dynamic Call Rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #F1F5F9; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="height: 38px; border-radius: 8px; font-weight: 600; padding: 0 18px; background: #F1F5F9; color: #475569; border: none;">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let pageSize = 10;
    let totalRecords = 0;

    const csrfToken = "{{ csrf_token() }}";
    const dataUrl = "{{ route('rmiAnalysisData') }}";

    // Date Validation & Load Data
    $('#btnSearchRmi').on('click', function () {
        const fromDate = $('#filterFromDate').val();
        const toDate = $('#filterToDate').val();

        if (fromDate && toDate && new Date(toDate) < new Date(fromDate)) {
            if (typeof toastr !== 'undefined') {
                toastr.error('To Date cannot be earlier than From Date.');
            } else {
                alert('To Date cannot be earlier than From Date.');
            }
            return;
        }

        currentPage = 1;
        loadRmiData();
    });

    // Reset Filters
    $('#btnResetRmi').on('click', function () {
        $('#filterFromDate').val("{{ date('Y-m-01') }}");
        $('#filterToDate').val("{{ date('Y-m-d') }}");
        $('#filterGroup').val('');
        $('#filterAgent').val('');
        currentPage = 1;
        loadRmiData();
    });

    // Page Length Change
    $('#selectPageLength').on('change', function () {
        pageSize = parseInt($(this).val());
        currentPage = 1;
        loadRmiData();
    });

    // Fetch RMI Data from Server
    function loadRmiData() {
        const start = (currentPage - 1) * pageSize;
        const postData = {
            _token: csrfToken,
            draw: currentPage,
            start: start,
            length: pageSize,
            from_date: $('#filterFromDate').val(),
            to_date: $('#filterToDate').val(),
            group_id: $('#filterGroup').val(),
            agent_id: $('#filterAgent').val()
        };

        $('#rmiTableBody').html('<tr><td colspan="6" style="text-align: center; padding: 24px; color: #64748B;">Loading RMI Analysis...</td></tr>');

        $.ajax({
            url: dataUrl,
            type: 'POST',
            data: postData,
            success: function (res) {
                totalRecords = res.recordsTotal || 0;
                renderSummaryCards(res.summary || {});
                renderTableRows(res.data || []);
                renderPagination(totalRecords, currentPage, pageSize);
                updateExportUrls();
            },
            error: function () {
                $('#rmiTableBody').html('<tr><td colspan="6" style="text-align: center; padding: 24px; color: #EF4444;">Unable to load RMI analysis. <button type="button" onclick="loadRmiData()" class="btn btn-sm btn-outline-primary ms-2">Retry</button></td></tr>');
            }
        });
    }

    // Render Summary Cards
    function renderSummaryCards(s) {
        $('#summaryTotalCalls').text(s.total_calls || 0);
        $('#summaryTotalAnalyzed').text(s.total_analyzed || 0);
        $('#summaryAngry').text(s.angry || 0);
        $('#summaryFrustrated').text(s.frustrated || 0);
        $('#summaryConfident').text(s.confident || 0);
        $('#summaryNeutral').text(s.neutral || 0);
        $('#summaryCalm').text(s.calm || 0);
    }

    // Render Table Rows
    function renderTableRows(rows) {
        if (rows.length === 0) {
            $('#rmiTableBody').html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="font-weight: 700; color: #0F172A; font-size: 15px;">No RMI analysis available</div>
                        <div style="font-size: 13px; color: #64748B; margin-top: 4px;">Run or load call analysis to see RMI insights.</div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        rows.forEach(function (r) {
            html += `
                <tr>
                    <td style="font-weight: 700; color: #64748B;">${r.s_no}</td>
                    <td style="font-weight: 600; color: #0F172A;">${r.agent_name}</td>
                    <td><span class="lg-role-badge">${r.role}</span></td>
                    <td style="font-weight: 700;">${r.total_calls}</td>
                    <td style="font-weight: 700; color: var(--lg-accent);">${r.total_analyzed}</td>
                    <td style="text-align: center;">
                        <button type="button" class="lg-btn-view btn-view-detail" data-id="${r.agent_id}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>View</span>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#rmiTableBody').html(html);
    }

    // Render Pagination
    function renderPagination(total, page, size) {
        if (total === 0) {
            $('#paginationInfo').text('Showing 0 entries');
            $('#paginationNav').empty();
            return;
        }

        const start = (page - 1) * size + 1;
        const end = Math.min(page * size, total);
        $('#paginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);

        const totalPages = Math.ceil(total / size);
        let navHtml = '';

        if (page > 1) {
            navHtml += `<button class="lg-page-btn" data-page="${page - 1}">‹</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                const activeClass = (i === page) ? 'active' : '';
                navHtml += `<button class="lg-page-btn ${activeClass}" data-page="${i}">${i}</button>`;
            } else if (i === page - 3 || i === page + 3) {
                navHtml += `<span style="padding: 0 4px; color: #94A3B8;">…</span>`;
            }
        }

        if (page < totalPages) {
            navHtml += `<button class="lg-page-btn" data-page="${page + 1}">›</button>`;
        }

        $('#paginationNav').html(navHtml);
    }

    $(document).on('click', '.lg-page-btn', function () {
        const targetPage = parseInt($(this).data('page'));
        if (targetPage && targetPage !== currentPage) {
            currentPage = targetPage;
            loadRmiData();
        }
    });

    // Update Export Links with Current Filters
    function updateExportUrls() {
        const fromDate = $('#filterFromDate').val();
        const toDate = $('#filterToDate').val();
        const groupId = $('#filterGroup').val();
        const agentId = $('#filterAgent').val();
        const queryParams = `?from_date=${fromDate}&to_date=${toDate}&group_id=${groupId}&agent_id=${agentId}`;

        $('#btnExportExcel').attr('href', "{{ url('/admin/reports/rmi-analysis/export/excel') }}" + queryParams);
        $('#btnExportCsv').attr('href', "{{ url('/admin/reports/rmi-analysis/export/csv') }}" + queryParams);
    }

    // View Agent RMI Detail Modal Event
    $(document).on('click', '.btn-view-detail', function () {
        const agentId = $(this).data('id');
        const detailUrl = "{{ url('/admin/reports/rmi-analysis/detail') }}/" + agentId + "?from_date=" + $('#filterFromDate').val() + "&to_date=" + $('#filterToDate').val();

        $('#modalAgentName').text('Loading agent detail...');
        $('#modalAgentRole').text('');
        $('#modalCallsTbody').html('<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748B;">Loading call details...</td></tr>');
        $('#agentRmiModal').modal('show');

        $.ajax({
            url: detailUrl,
            type: 'GET',
            success: function (res) {
                $('#modalAgentName').text(res.agent.name + ' - RMI Analysis Detail');
                $('#modalAgentRole').text('Role: ' + res.agent.role);

                const s = res.summary || {};
                $('#modalAngry').text(s.angry || 0);
                $('#modalFrustrated').text(s.frustrated || 0);
                $('#modalConfident').text(s.confident || 0);
                $('#modalNeutral').text(s.neutral || 0);
                $('#modalCalm').text(s.calm || 0);

                const calls = res.calls || [];
                if (calls.length === 0) {
                    $('#modalCallsTbody').html('<tr><td colspan="6" style="text-align: center; padding: 24px; color: #94A3B8;">No calls recorded for this agent in selected date range.</td></tr>');
                    return;
                }

                let html = '';
                calls.forEach(function (c) {
                    const sentiment = (c.sentiment || 'NEUTRAL').toUpperCase();
                    let badgeBg = '#F1F5F9', badgeColor = '#475569';
                    if (sentiment === 'ANGRY') { badgeBg = '#FEF2F2'; badgeColor = '#DC2626'; }
                    else if (sentiment === 'FRUSTRATED') { badgeBg = '#FFEDD5'; badgeColor = '#EA580C'; }
                    else if (sentiment === 'CONFIDENT' || sentiment === 'POSITIVE') { badgeBg = '#EFF6FF'; badgeColor = '#1464F4'; }
                    else if (sentiment === 'CALM') { badgeBg = '#ECFDF5'; badgeColor = '#059669'; }

                    const durationSec = c.duration || 0;
                    const durationStr = Math.floor(durationSec / 60) + 'm ' + (durationSec % 60) + 's';

                    html += `
                        <tr>
                            <td style="font-family: monospace; font-weight: 700; color: var(--lg-accent);">#${c.call_id}</td>
                            <td style="font-weight: 600;">${c.lead_name || '—'}</td>
                            <td>${c.phonenumber || '—'}</td>
                            <td>${durationStr}</td>
                            <td>
                                <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11.5px;">
                                    ${sentiment}
                                </span>
                            </td>
                            <td style="font-size: 12px; color: #64748B;">${c.created_at || '—'}</td>
                        </tr>
                    `;
                });

                $('#modalCallsTbody').html(html);
            },
            error: function () {
                $('#modalCallsTbody').html('<tr><td colspan="6" style="text-align: center; padding: 20px; color: #EF4444;">Failed to load detail.</td></tr>');
            }
        });
    });

    // Summary cards -> the calls behind the card, with the current filters. The filters are first kept in this
    // page's URL so the browser Back button returns to the same view.
    function currentFilters() {
        return { from_date: $('#filterFromDate').val(), to_date: $('#filterToDate').val(), group_id: $('#filterGroup').val(), agent_id: $('#filterAgent').val() };
    }
    function openRmiCard(category) {
        var f = currentFilters();
        history.replaceState(null, '', window.location.pathname + '?' + new URLSearchParams(f).toString());
        window.location.href = "{{ route('rmiAnalysisCalls') }}?" + new URLSearchParams($.extend({ category: category }, f)).toString();
    }
    $('.lg-metric-link').on('click', function () {
        openRmiCard($(this).data('rmi-cat'));
    }).on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openRmiCard($(this).data('rmi-cat')); }
    });

    // Filters from the URL (Back from a card's records, or the records page's "RMI Analysis" link)
    (function () {
        var p = new URLSearchParams(window.location.search);
        if (/^\d{4}-\d{2}-\d{2}$/.test(p.get('from_date') || '')) $('#filterFromDate').val(p.get('from_date'));
        if (/^\d{4}-\d{2}-\d{2}$/.test(p.get('to_date') || '')) $('#filterToDate').val(p.get('to_date'));
        ['group_id', 'agent_id'].forEach(function (k) {
            var v = (p.get(k) || '').replace(/[^0-9]/g, '');
            var $s = $(k === 'group_id' ? '#filterGroup' : '#filterAgent');
            if (v && $s.find('option[value="' + v + '"]').length) $s.val(v);
        });
    })();

    // Initial Load
    loadRmiData();
});
</script>
@endpush
