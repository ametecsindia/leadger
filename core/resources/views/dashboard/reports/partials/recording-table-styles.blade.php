{{-- Report table design used by Call Recordings: card, header/rows, entries/search/pagination, Actions buttons, player --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Table Card Container */
.table-card-container {
    background: #FFFFFF;
    border-radius: 14px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    padding: 20px;
    overflow: hidden;
}

.table-card-container table.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    width: 100% !important;
}

.table-card-container table.dataTable thead th {
    background: #F8FAFC !important;
    color: #0F172A !important;
    font-size: 11.5px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 14px 16px !important;
    border-bottom: 2px solid #E2E8F0 !important;
    border-top: none !important;
}

.table-card-container table.dataTable tbody td {
    font-size: 13px !important;
    color: #334155 !important;
    padding: 12px 16px !important;
    border-bottom: 1px solid #F1F5F9 !important;
    vertical-align: middle !important;
}

.table-card-container table.dataTable tbody tr:hover {
    background-color: #F8FAFC !important;
}

.dataTables_wrapper .dataTables_length {
    text-align: left !important;
    float: left !important;
    margin-bottom: 14px !important;
}

.dataTables_wrapper .dataTables_length label {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
}

.dataTables_wrapper .dataTables_length select {
    height: 36px !important;
    padding: 0 10px !important;
    border-radius: 8px !important;
    border: 1.5px solid #CBD5E1 !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #0F172A !important;
    background-color: #FAFAFA !important;
    outline: none !important;
    cursor: pointer !important;
    margin: 0 4px !important;
}

.dataTables_wrapper .dataTables_filter {
    text-align: right !important;
    float: right !important;
    margin-bottom: 14px !important;
}

.dataTables_wrapper .dataTables_filter label {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
}

.dataTables_wrapper .dataTables_filter input {
    height: 36px !important;
    padding: 0 12px !important;
    border-radius: 8px !important;
    border: 1.5px solid #CBD5E1 !important;
    font-size: 13px !important;
    color: #0F172A !important;
    background-color: #FAFAFA !important;
    outline: none !important;
    margin-left: 6px !important;
    transition: all 0.15s ease !important;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--lg-accent) !important;
    background-color: #FFFFFF !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
}

.dataTables_wrapper .dataTables_info {
    font-size: 12.5px !important;
    color: #64748B !important;
    padding-top: 14px !important;
}


/* Single table container: the page card above is the only box. leadger-theme.css styles every
   .dataTables_wrapper as a card, which nested a second bordered box inside this one. */
.table-card-container .dataTables_wrapper {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    margin-bottom: 0 !important;
    padding: 0 !important;
}
.table-card-container .dl-dt-top,
.table-card-container .dl-dt-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.table-card-container .dl-dt-top { margin-bottom: 14px; }
.table-card-container .dl-dt-bottom { margin-top: 14px; }
.table-card-container .dataTables_wrapper .dataTables_length,
.table-card-container .dataTables_wrapper .dataTables_filter {
    float: none !important;
    margin-bottom: 0 !important;
}
.table-card-container .dataTables_wrapper .dataTables_info { padding-top: 0 !important; }
/* scrollX keeps an invisible copy of the header row inside the body for column sizing; the heading
   padding/border above made it show as an empty band with a second line under the real headings */
/* (#leads_table outranks class selectors: leadger-theme.css styles "#leads_table thead th" too) */
.table-card-container .dataTables_scrollBody #leads_table thead th,
.table-card-container .dataTables_scrollBody #leads_table thead td {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    border: none !important;
    box-shadow: none !important;
    height: 0 !important;
    --lg-table-head-pad-y: 0px; /* lg-design-system.css pads every header cell with this variable */
}

/* ACTIONS column: Download / Play / Delete, kept visible on the right while the table scrolls sideways */
.lg-dl-actions { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.lg-dl-act {
    width: 30px; height: 30px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; border: 1px solid #CBD5E1; background: #FFFFFF;
    color: #475569; font-size: 12px; cursor: pointer; transition: all 0.15s ease;
}
.lg-dl-act:hover { border-color: #94A3B8; color: #0F172A; background: #F8FAFC; }
.lg-dl-act-play { background: #1769E8; border-color: #1769E8; color: #FFFFFF; }
.lg-dl-act-play:hover { background: #1056C7; border-color: #1056C7; color: #FFFFFF; }
.lg-dl-act-delete { color: #DC2626; border-color: #FECACA; }
.lg-dl-act-delete:hover { background: #FEF2F2; border-color: #EF4444; color: #B91C1C; }
.lg-dl-act:disabled { opacity: 0.6; cursor: wait; }
/* Actions column: part of the normal table flow (scrolls with the other columns, not frozen) */

/* Recording player popup */
.lg-dl-player-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 99999; display: none; align-items: center; justify-content: center; padding: 16px;
}
.lg-dl-player-overlay.active { display: flex; }
.lg-dl-player {
    background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; width: 100%; max-width: 460px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25); overflow: hidden;
}
.lg-dl-player-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid #E2E8F0; }
.lg-dl-player-title { font-size: 17px; font-weight: 700; color: #0F172A; margin: 0; }
.lg-dl-player-close {
    width: 32px; height: 32px; border-radius: 8px; background: #F1F5F9; border: 1px solid #E2E8F0;
    color: #64748B; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;
}
.lg-dl-player-close:hover { background: #E2E8F0; color: #0F172A; }
.lg-dl-player-body { padding: 20px 22px 22px; }
.lg-dl-player-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; font-size: 13px; color: #475569; margin-bottom: 18px; }
.lg-dl-player-meta strong { color: #0F172A; font-weight: 600; }
.lg-dl-player-bar { display: flex; align-items: center; gap: 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 10px 14px; }
.lg-dl-player-toggle {
    width: 38px; height: 38px; border-radius: 50%; border: none; background: #1769E8; color: #FFFFFF;
    display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; font-size: 13px;
}
.lg-dl-player-toggle:disabled { opacity: 0.6; cursor: wait; }
.lg-dl-player-track { flex: 1; height: 6px; background: #CBD5E1; border-radius: 3px; overflow: hidden; cursor: pointer; }
.lg-dl-player-progress { height: 100%; width: 0%; background: #1769E8; border-radius: 3px; }
.lg-dl-player-time { font-size: 12px; font-weight: 600; color: #64748B; white-space: nowrap; font-variant-numeric: tabular-nums; }
.lg-dl-player-status { margin-top: 10px; font-size: 12.5px; min-height: 18px; color: #64748B; }
.lg-dl-player-status.error { color: #DC2626; font-weight: 500; }
</style>
