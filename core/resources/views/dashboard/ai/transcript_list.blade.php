@extends('dashboard.layouts.master')
@section('title', 'Call Transcripts')

@section('content')
<!-- Include FontAwesome 6 for modern corporate icon rendering -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.lg-ai-dashboard {
    padding: 20px 24px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
}

/* Page Header */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.lg-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(23, 105, 255, 0.08);
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 20px;
    line-height: 1.2;
}
.lg-header-subtitle {
    color: #64748B;
    font-size: 13px;
}
.lg-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-btn-refresh {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 16px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    text-decoration: none !important;
}
.lg-btn-refresh:hover {
    background: #F1F5F9;
    color: #0F172A;
}
.lg-btn-export {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 18px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    text-decoration: none !important;
}
.lg-btn-export:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
}

/* Filter Section */
.lg-filter-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 20px;
}
.lg-filter-header {
    font-weight: 700;
    color: #1E293B;
    font-size: 14px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-filter-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    align-items: end;
    margin-bottom: 14px;
}
@media (max-width: 1199px) {
    .lg-filter-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 767px) {
    .lg-filter-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .lg-filter-grid { grid-template-columns: 1fr; }
}

.lg-filter-group {
    display: flex;
    flex-direction: column;
    position: relative;
}
.lg-filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    margin-bottom: 6px;
}
.lg-filter-select, .lg-filter-btn {
    width: 100%;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    padding: 0 12px;
    font-size: 13px;
    color: #334155;
    outline: none;
    box-shadow: none;
}
.lg-filter-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}

/* User Popover */
.lg-user-popover {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 270px;
    z-index: 9999;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    padding: 12px;
}

.lg-filter-actions-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #F1F5F9;
}
.lg-btn-apply {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    border-radius: 8px;
    font-weight: 600;
    height: 38px;
    padding: 0 20px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.lg-btn-apply:hover {
    background: #0F52CC;
    border-color: #0F52CC;
}
.lg-btn-reset {
    background: #F8FAFC;
    color: #475569;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-weight: 500;
    height: 38px;
    padding: 0 20px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
}
.lg-btn-reset:hover {
    background: #F1F5F9;
    color: #0F172A;
}

/* Table Card */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.lg-table-header {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.lg-table-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-table-meta {
    font-size: 12px;
    color: #64748B;
    font-weight: 500;
}
.lg-table-footer {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
</style>

<div class="lg-ai-dashboard">
    
    <!-- 1. PAGE HEADER CARD -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div>
                <h4 class="lg-header-title">Call Transcripts</h4>
                <div class="lg-header-subtitle">View speech-to-text transcripts extracted from call recordings</div>
            </div>
        </div>
        <div class="lg-header-actions">
            <a href="{{ route('callTranscriptsList') }}" class="lg-btn-refresh">
                <i class="fas fa-arrows-rotate"></i> Refresh
            </a>
            <a href="{{ route('callTranscriptsExport', request()->all()) }}" class="lg-btn-export">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fas fa-circle-check me-2"></i>{{ session('doneMessage') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 2. FILTER TOOLBAR CARD -->
    <form action="{{ route('callTranscriptsList') }}" method="GET" id="transcriptFilterForm">
        <div class="lg-filter-card">
            <div class="lg-filter-header">
                <i class="fas fa-sliders text-primary" style="color: var(--lg-accent) !important;"></i> Filters
            </div>

            <div class="lg-filter-grid">
                
                <!-- Filter 1: Select Users (Multiselect) -->
                <div class="lg-filter-group" id="userMultiselectWrapper">
                    <label class="lg-filter-label">Select Users</label>
                    <button type="button" class="lg-filter-btn" id="btnUserMultiselect" onclick="toggleUserDropdown(event)">
                        <span class="d-flex align-items-center gap-2 text-truncate">
                            <i class="fas fa-user-circle" style="color: #64748B;"></i>
                            <span id="userMultiselectLabel">
                                @if(empty($selectedUserIds) || in_array('all', $selectedUserIds))
                                    All Users
                                @elseif(count($selectedUserIds) === 1)
                                    @php
                                        $firstId = reset($selectedUserIds);
                                        $singleUser = $Users->firstWhere('id', (int)$firstId) ?? $Users->firstWhere('id', (string)$firstId);
                                    @endphp
                                    {{ $singleUser ? $singleUser->name : '1 User Selected' }}
                                @else
                                    {{ count($selectedUserIds) }} Users Selected
                                @endif
                            </span>
                        </span>
                        <i class="fas fa-chevron-down" style="font-size: 11px; color: #94A3B8;"></i>
                    </button>

                    <!-- User Multiselect Popover -->
                    <div class="lg-user-popover" id="userMultiselectMenu" onclick="event.stopPropagation()">
                        <div style="position: relative; margin-bottom: 8px;">
                            <input type="text" id="userSearchInput" onkeyup="filterUserList()" class="form-control form-control-sm" placeholder="Search users..." style="border-radius: 6px; border: 1px solid #CBD5E1; font-size: 12px; height: 34px; padding-left: 10px;">
                        </div>

                        <div style="max-height: 180px; overflow-y: auto; padding-right: 4px;">
                            <div class="form-check py-1 border-bottom mb-1" style="border-color: #F1F5F9 !important;">
                                <input class="form-check-input" type="checkbox" id="user_check_all" value="all" {{ empty($selectedUserIds) || in_array('all', $selectedUserIds) ? 'checked' : '' }} onchange="toggleSelectAllUsers(this)" style="cursor: pointer;">
                                <label class="form-check-label small font-weight-bold" for="user_check_all" style="cursor: pointer; color: #1E293B; font-size: 13px;">Select All</label>
                            </div>
                            <div id="userCheckboxList">
                                @foreach($Users as $u)
                                    <div class="form-check py-1 user-item-row" data-name="{{ strtolower($u->name) }}">
                                        <input class="form-check-input user-checkbox" type="checkbox" name="users[]" value="{{ $u->id }}" id="u_chk_{{ $u->id }}" {{ in_array($u->id, $selectedUserIds) ? 'checked' : '' }} data-name="{{ $u->name }}" onchange="handleUserCheckboxChange()" style="cursor: pointer;">
                                        <label class="form-check-label small text-dark" for="u_chk_{{ $u->id }}" style="cursor: pointer; font-size: 13px;">{{ $u->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-2 mt-2 border-top" style="border-color: #F1F5F9 !important;">
                            <button type="button" class="btn btn-sm btn-light" onclick="closeUserDropdown(event)" style="border-radius: 6px; font-size: 12px; font-weight: 500; height: 32px; padding: 0 12px; color: #475569;">Cancel</button>
                            <button type="submit" class="btn btn-sm text-white" style="background: var(--lg-accent); border-radius: 6px; font-size: 12px; font-weight: 600; height: 32px; padding: 0 14px;">Apply</button>
                        </div>
                    </div>
                </div>

                <!-- Filter 2: Campaign -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Campaign</label>
                    <select name="campaign" id="filter_campaign" class="lg-filter-select">
                        <option value="">All Campaigns</option>
                        @foreach($Campaigns as $camp)
                            <option value="{{ $camp->id }}" {{ ($campaignFilter == $camp->id) ? 'selected' : '' }}>{{ $camp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter 3: Customer Interest -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Customer Interest</label>
                    <select name="interest" id="filter_interest" class="lg-filter-select">
                        <option value="">All Interest Levels</option>
                        <option value="HIGH" {{ ($interestFilter == 'HIGH') ? 'selected' : '' }}>High Interest</option>
                        <option value="MEDIUM" {{ ($interestFilter == 'MEDIUM') ? 'selected' : '' }}>Medium Interest</option>
                        <option value="LOW" {{ ($interestFilter == 'LOW') ? 'selected' : '' }}>Low Interest</option>
                    </select>
                </div>

                <!-- Filter 4: Sentiment -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Sentiment</label>
                    <select name="sentiment" id="filter_sentiment" class="lg-filter-select">
                        <option value="">All Sentiments</option>
                        <option value="POSITIVE" {{ ($sentimentFilter == 'POSITIVE') ? 'selected' : '' }}>Positive</option>
                        <option value="NEUTRAL" {{ ($sentimentFilter == 'NEUTRAL') ? 'selected' : '' }}>Neutral</option>
                        <option value="NEGATIVE" {{ ($sentimentFilter == 'NEGATIVE') ? 'selected' : '' }}>Negative</option>
                    </select>
                </div>

                <!-- Filter 5: Analysis Status -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Analysis Status</label>
                    <select name="status" id="filter_status" class="lg-filter-select">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ ($statusFilter == 'completed') ? 'selected' : '' }}>Completed</option>
                        <option value="processing" {{ ($statusFilter == 'processing') ? 'selected' : '' }}>Processing</option>
                        <option value="pending" {{ ($statusFilter == 'pending') ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ ($statusFilter == 'failed') ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

            </div>

            <!-- Action Buttons Row -->
            <div class="lg-filter-actions-row">
                <button type="submit" class="lg-btn-apply">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="{{ route('callTranscriptsList') }}" class="lg-btn-reset">
                    <i class="fas fa-rotate-right"></i> Reset
                </a>
            </div>
        </div>
    </form>

    <!-- 3. TRANSCRIBED CALL RECORDINGS TABLE -->
    <div class="lg-table-card">
        <div class="lg-table-header">
            <h6 class="lg-table-title">
                <i class="fas fa-file-lines text-primary" style="color: var(--lg-accent) !important;"></i> Transcribed Call Recordings
            </h6>
            <div class="lg-table-meta">
                Showing {{ $transcripts->firstItem() ?? 0 }}–{{ $transcripts->lastItem() ?? 0 }} of {{ $transcripts->total() }} records
            </div>
        </div>
        <div style="padding: 0;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="width: 100%;">
                    <thead style="background: #F8FAFC; color: #64748B; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <tr>
                            <th style="padding: 14px 20px; font-weight: 700;">CALL ID</th>
                            <th style="padding: 14px 20px; font-weight: 700;">USER</th>
                            <th style="padding: 14px 20px; font-weight: 700;">DURATION</th>
                            <th style="padding: 14px 20px; font-weight: 700;">DATE</th>
                            <th style="padding: 14px 20px; font-weight: 700;">STATUS</th>
                            <th style="padding: 14px 20px; font-weight: 700; text-align: right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 13px; color: #334155;">
                        @forelse($transcripts as $item)
                            <tr>
                                <td style="padding: 14px 20px;">
                                    <a href="{{ route('callTranscriptDetail', $item->id) }}" class="font-weight-bold" style="color: var(--lg-accent); text-decoration: none;">
                                        #{{ $item->call_id }}
                                    </a>
                                </td>
                                <td style="padding: 14px 20px;">
                                    <span class="font-weight-bold text-dark">{{ $item->agent_name ?: 'System User' }}</span>
                                </td>
                                <td style="padding: 14px 20px;">
                                    <span style="color: #475569; font-weight: 500;">
                                        {{ round(($item->audio_duration_seconds ?: $item->duration ?: 0) / 60, 1) }} mins
                                    </span>
                                </td>
                                <td style="padding: 14px 20px; color: #64748B; font-size: 12px;">
                                    {{ date('d M Y H:i', strtotime($item->created_at)) }}
                                </td>
                                <td style="padding: 14px 20px;">
                                    @if(strtolower($item->status) === 'completed')
                                        <span class="badge" style="background: #DCFCE7; color: #166534; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-circle-check"></i> Completed
                                        </span>
                                    @elseif(strtolower($item->status) === 'processing')
                                        <span class="badge" style="background: #DBEAFE; color: var(--lg-accent-hover); font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-spinner fa-spin"></i> Processing
                                        </span>
                                    @elseif(strtolower($item->status) === 'pending')
                                        <span class="badge" style="background: #FFEDD5; color: #9A3412; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @else
                                        <span class="badge" style="background: #FEE2E2; color: #991B1B; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-circle-xmark"></i> {{ ucfirst($item->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 14px 20px; text-align: right;">
                                    <a href="{{ route('callTranscriptDetail', $item->id) }}" class="btn btn-sm" style="background: var(--lg-accent); color: #FFFFFF; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-eye"></i> View Transcript
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3" style="color: #CBD5E1 !important;"></i>
                                        <h6 class="font-weight-bold text-dark m-0">No call transcripts found</h6>
                                        <p class="text-muted small mt-1 mb-0">Try changing your user selection or filter options.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. PAGINATION FOOTER -->
        <div class="lg-table-footer">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size: 12px; color: #64748B; font-weight: 500;">Rows per page:</span>
                <select onchange="changePerPage(this.value)" class="form-select form-select-sm" style="width: 70px; height: 32px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 12px; color: #334155;">
                    <option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span style="font-size: 12px; color: #64748B; font-weight: 500;">
                    {{ $transcripts->firstItem() ?? 0 }}–{{ $transcripts->lastItem() ?? 0 }} of {{ $transcripts->total() }}
                </span>
                <div>
                    {{ $transcripts->links() }}
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function toggleUserDropdown(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('userMultiselectMenu');
    if (menu) {
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
}

function closeUserDropdown(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('userMultiselectMenu');
    if (menu) menu.style.display = 'none';
}

function filterUserList() {
    var query = (document.getElementById('userSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#userCheckboxList .user-item-row');
    rows.forEach(function(row) {
        var name = row.getAttribute('data-name') || '';
        if (name.indexOf(query) !== -1) {
            row.style.display = 'block';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleSelectAllUsers(master) {
    var isChecked = master.checked;
    var checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = isChecked; });
    updateUserMultiselectLabel();
}

function handleUserCheckboxChange() {
    var total = document.querySelectorAll('.user-checkbox').length;
    var checked = document.querySelectorAll('.user-checkbox:checked').length;
    var master = document.getElementById('user_check_all');
    if (master) master.checked = (total === checked);
    updateUserMultiselectLabel();
}

function updateUserMultiselectLabel() {
    var total = document.querySelectorAll('.user-checkbox').length;
    var checked = document.querySelectorAll('.user-checkbox:checked');
    var label = document.getElementById('userMultiselectLabel');
    if (!label) return;

    if (checked.length === 0) {
        label.textContent = 'No Users Selected';
    } else if (checked.length === total) {
        label.textContent = 'All Users';
    } else if (checked.length === 1) {
        label.textContent = checked[0].getAttribute('data-name') || '1 User Selected';
    } else {
        label.textContent = checked.length + ' Users Selected';
    }
}

function changePerPage(val) {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    var wrapper = document.getElementById('userMultiselectWrapper');
    var menu = document.getElementById('userMultiselectMenu');
    if (wrapper && menu && !wrapper.contains(e.target)) {
        menu.style.display = 'none';
    }
});
</script>
@endsection
