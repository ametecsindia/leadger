<div class="lg-lead-modal-wrapper">
    <!-- Scoped Modal Styles -->
    <style>
        .lg-lead-modal-wrapper {
            font-family: var(--lg-font-sans);
            color: #0F172A;
            background: #FFFFFF;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 40px);
        }

        /* Modal Header */
        .lg-modal-header {
            padding: 20px 24px 18px 24px;
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .lg-modal-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 260px;
        }
        .lg-modal-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #F0FDFA;
            border: 1px solid #CCFBF1;
            color: var(--lg-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .lg-modal-avatar svg {
            width: 24px;
            height: 24px;
        }
        .lg-modal-title-box h3 {
            font-size: 20px;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 4px 0;
            line-height: 1.25;
            letter-spacing: -0.01em;
        }
        .lg-modal-meta-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .lg-modal-pill-id {
            background: #EFF6FF;
            color: var(--lg-accent);
            border: 1px solid #DBEAFE;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
        }
        .lg-modal-pill {
            background: #F8FAFC;
            color: #475569;
            border: 1px solid #E2E8F0;
            font-size: 12px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Modal Header Actions */
        .lg-modal-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .lg-modal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            height: 38px;
            padding: 0 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none !important;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .lg-modal-btn-white {
            background: #FFFFFF;
            border-color: #D1D5DB;
            color: #334155;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .lg-modal-btn-white:hover {
            background: #F8FAFC;
            border-color: #94A3B8;
            color: #0F172A;
        }
        .lg-modal-btn-call {
            background: #059669;
            border-color: #059669;
            color: #FFFFFF !important;
            box-shadow: 0 2px 5px rgba(5, 150, 105, 0.25);
        }
        .lg-modal-btn-call:hover {
            background: #047857;
            border-color: #047857;
            color: #FFFFFF !important;
            transform: translateY(-1px);
        }
        .lg-modal-close-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 20px;
            line-height: 1;
            padding: 0;
        }
        .lg-modal-close-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        /* Action Drawers */
        .lg-modal-drawer {
            display: none;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            padding: 18px 24px;
            animation: lgSlideDown 0.2s ease;
        }
        @keyframes lgSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .lg-drawer-title {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Navigation Tabs */
        .lg-modal-tabs {
            display: flex;
            gap: 8px;
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 0 24px;
            position: sticky;
            top: 79px;
            z-index: 15;
        }
        .lg-modal-tab {
            padding: 13px 14px;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748B;
            background: transparent;
            border: 0;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .lg-modal-tab:hover {
            color: var(--lg-accent);
        }
        .lg-modal-tab.active {
            color: var(--lg-accent);
            border-bottom-color: var(--lg-accent);
        }
        .lg-modal-tab-badge {
            background: #F1F5F9;
            color: #475569;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1;
        }
        .lg-modal-tab.active .lg-modal-tab-badge {
            background: #CCFBF1;
            color: var(--lg-accent-hover);
        }

        /* Modal Body (Scrollable) */
        .lg-modal-body {
            flex: 1 1 auto;
            max-height: calc(100vh - 220px);
            overflow-y: auto;
            padding: 24px 24px 36px 24px;
            background: #F8FAFC;
        }
        .lg-modal-body::-webkit-scrollbar {
            width: 8px;
        }
        .lg-modal-body::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 4px;
        }
        .lg-modal-body::-webkit-scrollbar-thumb {
            background: #94A3B8;
            border-radius: 4px;
        }
        .lg-modal-body::-webkit-scrollbar-thumb:hover {
            background: #64748B;
        }

        /* Two-Column Info Cards */
        .lg-modal-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .lg-modal-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .lg-modal-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        .lg-modal-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }
        .lg-modal-card-title i, .lg-modal-card-title svg {
            color: var(--lg-accent);
        }

        /* Key-Value Grid */
        .lg-kv-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 20px;
        }
        .lg-kv-item {
            display: flex;
            flex-direction: column;
        }
        .lg-kv-item.full-width {
            grid-column: 1 / -1;
        }
        .lg-kv-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            text-transform: capitalize;
            margin-bottom: 4px;
        }
        .lg-kv-value {
            font-size: 14px;
            font-weight: 600;
            color: #0F172A;
            word-break: break-word;
        }
        .lg-kv-value.mono {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 13.5px;
        }

        /* Status Badge */
        .lg-modal-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .lg-modal-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .lg-modal-status-badge.ok {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }
        .lg-modal-status-badge.ok .lg-modal-status-dot { background: #10B981; }

        .lg-modal-status-badge.warn {
            background: #FFFBEB;
            color: #B45309;
            border: 1px solid #FDE68A;
        }
        .lg-modal-status-badge.warn .lg-modal-status-dot { background: #F59E0B; }

        .lg-modal-status-badge.live {
            background: #EFF6FF;
            color: var(--lg-accent-hover);
            border: 1px solid #BFDBFE;
        }
        .lg-modal-status-badge.live .lg-modal-status-dot { background: var(--lg-accent); }

        .lg-modal-status-badge.bad {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }
        .lg-modal-status-badge.bad .lg-modal-status-dot { background: #EF4444; }

        /* Call History Table */
        .lg-modal-table-wrap {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .lg-modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .lg-modal-table th {
            background: #F8FAFC;
            color: #64748B;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            white-space: nowrap;
        }
        .lg-modal-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
            color: #0F172A;
        }
        .lg-modal-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .lg-modal-table tbody tr:hover td {
            background: #F8FAFC;
        }

        /* Play & Action Buttons */
        .lg-btn-play {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: var(--lg-accent-hover);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-btn-play:hover {
            background: #DBEAFE;
            color: var(--lg-accent-hover);
        }
        .lg-btn-transcript {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #15803D;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-btn-transcript:hover {
            background: #DCFCE7;
            color: #166534;
        }

        /* Audio Player Bar */
        .lg-modal-audio-bar {
            display: none;
            background: #0F172A;
            color: #FFFFFF;
            border-radius: 10px;
            padding: 10px 16px;
            margin-top: 14px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .lg-modal-audio-bar audio {
            height: 34px;
            outline: none;
            flex: 1;
            max-width: 420px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .lg-modal-columns {
                grid-template-columns: 1fr;
            }
            .lg-modal-header {
                padding: 16px;
            }
            .lg-modal-body {
                padding: 16px;
            }
        }
        @media (max-width: 600px) {
            .lg-kv-grid {
                grid-template-columns: 1fr;
            }
            .lg-modal-header-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>

    <!-- 1. Modal Header -->
    <div class="lg-modal-header">
        <div class="lg-modal-header-left">
            <div class="lg-modal-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7.5" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div class="lg-modal-title-box">
                <h3>{{ $Lead->name ?: 'Unnamed Lead' }}</h3>
                <div class="lg-modal-meta-pills">
                    <span class="lg-modal-pill-id">{{ $Lead->id }}</span>
                    <span class="lg-modal-pill" title="Campaign">
                        <i class="fa fa-bullhorn text-muted"></i>
                        {{ $Campaign ? $Campaign->name : 'General Campaign' }}
                    </span>
                    <span class="lg-modal-pill" title="Product">
                        <i class="fa fa-tag text-muted"></i>
                        {{ $productName }}
                    </span>
                </div>
            </div>
        </div>

        <div class="lg-modal-header-actions">
            <button type="button" class="lg-modal-btn lg-modal-btn-white" onclick="toggleModalFollowupDrawer()" title="Schedule follow-up appointment">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Schedule Follow-up</span>
            </button>

            <button type="button" class="lg-modal-btn lg-modal-btn-white" onclick="toggleModalReassignDrawer()" title="Reassign lead to another agent">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Reassign</span>
            </button>

            @php
                $callTargetPhone = $Lead->phonenumber ?: '';
                $callingWorkspaceUrl = !empty($Lead->campaign_id) ? route('leadCallingView', ['campaignId' => $Lead->campaign_id, 'leadId' => $Lead->id]) : '';
            @endphp
            <button type="button" class="lg-modal-btn lg-modal-btn-call" onclick="initiateModalCall('{{ $callTargetPhone }}', '{{ $callingWorkspaceUrl }}')" title="Call this lead">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span>Call Lead</span>
            </button>

            <button type="button" class="lg-modal-close-btn" data-dismiss="modal" aria-label="Close" title="Close Lead Details">
                &times;
            </button>
        </div>
    </div>

    <!-- 2. Action Drawer: Schedule Follow-up -->
    <div id="drawerModalFollowup" class="lg-modal-drawer">
        <div class="lg-drawer-title">
            <i class="fa fa-calendar-alt text-primary"></i>
            <span>Schedule Follow-up for {{ $Lead->name ?: 'Lead' }}</span>
        </div>
        <form id="formModalFollowup" onsubmit="submitModalFollowup(event)">
            @csrf
            <div class="row">
                <div class="col-md-5 col-sm-12 mb-2">
                    <label class="lg-kv-label">Follow-up Date & Time *</label>
                    <input type="datetime-local" id="inputModalFollowupDate" name="follow_up_on" class="form-control" style="border-radius: 8px; border: 1px solid #CBD5E1; height: 38px; font-size: 13px;" value="{{ $Lead->follow_up_on ? date('Y-m-d\TH:i', strtotime($Lead->follow_up_on)) : '' }}" required>
                </div>
                <div class="col-md-7 col-sm-12 mb-2">
                    <label class="lg-kv-label">Follow-up Remarks / Note</label>
                    <input type="text" id="inputModalFollowupRemarks" name="remarks" class="form-control" style="border-radius: 8px; border: 1px solid #CBD5E1; height: 38px; font-size: 13px;" placeholder="e.g. Call in afternoon, interested in pricing..." value="{{ $Lead->remarks }}">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2" style="gap: 8px;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleModalFollowupDrawer()" style="border-radius: 6px;">Cancel</button>
                <button type="submit" id="btnSaveFollowup" class="btn btn-sm btn-primary" style="background: var(--lg-accent); border-color: var(--lg-accent); border-radius: 6px; font-weight: 600;">
                    <span class="spinner-border spinner-border-sm d-none" id="spinFollowup" role="status"></span>
                    Save Follow-up
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Action Drawer: Reassign Lead -->
    <div id="drawerModalReassign" class="lg-modal-drawer">
        <div class="lg-drawer-title">
            <i class="fa fa-user-friends text-primary"></i>
            <span>Reassign Lead to Agent</span>
        </div>
        <form id="formModalReassign" onsubmit="submitModalReassign(event)">
            @csrf
            <div class="row">
                <div class="col-md-7 col-sm-12 mb-2">
                    <label class="lg-kv-label">Select Agent *</label>
                    <select id="selectModalAssignedTo" name="assigned_to" class="form-control" style="border-radius: 8px; border: 1px solid #CBD5E1; height: 38px; font-size: 13px;" required>
                        <option value="">-- Choose Agent --</option>
                        @if(!empty($Staff))
                            <optgroup label="Telecallers / Staff">
                                @foreach($Staff as $stf)
                                    <option value="{{ $stf->id }}" {{ $Lead->assigned_to == $stf->id ? 'selected' : '' }}>
                                        {{ $stf->name }} ({{ $stf->email ?: 'Telecaller' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(!empty($SalesPersons))
                            <optgroup label="Sales Persons">
                                @foreach($SalesPersons as $sp)
                                    <option value="{{ $sp->id }}" {{ $Lead->assigned_to == $sp->id ? 'selected' : '' }}>
                                        {{ $sp->name }} (Sales Person)
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>
                <div class="col-md-5 col-sm-12 mb-2 d-flex align-items-end" style="gap: 8px;">
                    <button type="submit" id="btnSaveReassign" class="btn btn-sm btn-primary flex-fill" style="background: var(--lg-accent); border-color: var(--lg-accent); border-radius: 6px; font-weight: 600; height: 38px;">
                        <span class="spinner-border spinner-border-sm d-none" id="spinReassign" role="status"></span>
                        Reassign Lead
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleModalReassignDrawer()" style="border-radius: 6px; height: 38px;">Cancel</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Navigation Tabs -->
    <nav class="lg-modal-tabs" aria-label="Lead Tabs">
        <button type="button" class="lg-modal-tab active" id="modalTabBtnOverview" onclick="switchModalTab('overview')">
            <i class="fa fa-info-circle"></i>
            <span>Overview</span>
        </button>
        <button type="button" class="lg-modal-tab" id="modalTabBtnHistory" onclick="switchModalTab('history')">
            <i class="fa fa-phone-alt"></i>
            <span>Call History</span>
            <span class="lg-modal-tab-badge" id="modalCallHistoryCount">{{ count($CallLogs) }}</span>
        </button>
    </nav>

    <!-- 5. Modal Scrollable Content -->
    <div class="lg-modal-body">
        
        <!-- Tab Pane 1: Overview -->
        <div id="modalTabPaneOverview">
            <div class="lg-modal-columns">
                
                <!-- Card 1: Customer Information -->
                <div class="lg-modal-card">
                    <div class="lg-modal-card-header">
                        <h4 class="lg-modal-card-title">
                            <i class="fa fa-user"></i>
                            <span>Customer Information</span>
                        </h4>
                        <span class="badge badge-light" style="font-size: 11px; color: #64748B;">Client Details</span>
                    </div>

                    <div class="lg-kv-grid">
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Name</span>
                            <span class="lg-kv-value">{{ $Lead->name ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Mobile Number</span>
                            <span class="lg-kv-value mono">
                                @if(!empty($Lead->phonenumber))
                                    <a href="tel:{{ $Lead->phonenumber }}" style="color: var(--lg-accent); text-decoration: none;" title="Call {{ $Lead->phonenumber }}">
                                        {{ $Lead->phonenumber }}
                                    </a>
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Alternate Mobile</span>
                            <span class="lg-kv-value mono">{{ $Lead->phonenumber_2 ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Email</span>
                            <span class="lg-kv-value">
                                @if(!empty($Lead->email))
                                    <a href="mailto:{{ $Lead->email }}" style="color: var(--lg-accent); text-decoration: none;" title="Email {{ $Lead->email }}">
                                        {{ $Lead->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">City</span>
                            <span class="lg-kv-value">{{ $Lead->city ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Product</span>
                            <span class="lg-kv-value">{{ $productName ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Campaign</span>
                            <span class="lg-kv-value">{{ $Campaign ? $Campaign->name : '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Lead Source</span>
                            <span class="lg-kv-value">{{ $Lead->source ?: '—' }}</span>
                        </div>
                        @if(!empty($Lead->company))
                            <div class="lg-kv-item">
                                <span class="lg-kv-label">Company</span>
                                <span class="lg-kv-value">{{ $Lead->company }}</span>
                            </div>
                        @endif
                        @if(!empty($Lead->designation))
                            <div class="lg-kv-item">
                                <span class="lg-kv-label">Designation</span>
                                <span class="lg-kv-value">{{ $Lead->designation }}</span>
                            </div>
                        @endif
                        @if(!empty($Lead->address) || !empty($Lead->state))
                            <div class="lg-kv-item full-width">
                                <span class="lg-kv-label">Address</span>
                                <span class="lg-kv-value" style="font-weight: 500; font-size: 13.5px;">
                                    {{ implode(', ', array_filter([$Lead->address, $Lead->city, $Lead->state, $Lead->pin_code, $Lead->country])) ?: '—' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Card 2: Sales Information -->
                <div class="lg-modal-card">
                    <div class="lg-modal-card-header">
                        <h4 class="lg-modal-card-title">
                            <i class="fa fa-chart-line"></i>
                            <span>Sales Information</span>
                        </h4>
                        <span class="badge badge-light" style="font-size: 11px; color: #64748B;">CRM & Calls</span>
                    </div>

                    <div class="lg-kv-grid">
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Current Status</span>
                            <div class="lg-kv-value">
                                <span class="lg-modal-status-badge {{ $statusBadgeClass }}">
                                    <span class="lg-modal-status-dot"></span>
                                    <span>{{ $currentStatusName }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Disposition</span>
                            <span class="lg-kv-value">{{ $dispositionName ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Sub-disposition</span>
                            <span class="lg-kv-value">{{ $subDispositionName ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Last Call</span>
                            <span class="lg-kv-value">{{ $lastCallText ?: '—' }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Next Follow-up</span>
                            <span class="lg-kv-value" id="modalValNextFollowup" style="color: #D97706;">
                                {{ $nextFollowupText ?: '—' }}
                            </span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Number of Calls</span>
                            <span class="lg-kv-value mono">{{ count($CallLogs) }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Total Talk Time</span>
                            <span class="lg-kv-value mono">{{ $totalTalkTimeText }}</span>
                        </div>
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Assigned Agent</span>
                            <span class="lg-kv-value" id="modalValAssignedAgent">
                                {{ $assignedAgentName ?: '—' }}
                            </span>
                        </div>
                        @if(!empty($Lead->lead_value))
                            <div class="lg-kv-item">
                                <span class="lg-kv-label">Lead Value</span>
                                <span class="lg-kv-value mono" style="color: #059669;">
                                    ₹{{ number_format($Lead->lead_value) }}
                                </span>
                            </div>
                        @endif
                        <div class="lg-kv-item">
                            <span class="lg-kv-label">Created Date</span>
                            <span class="lg-kv-value" style="font-weight: 500; font-size: 13px;">
                                {{ date('d-M-Y', strtotime($Lead->created_at)) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Recent Calls Summary (if any exist) -->
            @if(count($CallLogs) > 0)
                <div class="mt-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width: 3px; height: 16px; background: var(--lg-accent); border-radius: 2px;"></span>
                            <h5 style="font-size: 14.5px; font-weight: 700; color: #0F172A; margin: 0;">Recent Calls</h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-link" onclick="switchModalTab('history')" style="color: var(--lg-accent); font-weight: 600; text-decoration: none; font-size: 12.5px; padding: 0;">
                            View all ({{ count($CallLogs) }}) &rarr;
                        </button>
                    </div>

                    <div class="lg-modal-table-wrap">
                        <table class="lg-modal-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Agent</th>
                                    <th>Duration</th>
                                    <th>Disposition</th>
                                    <th>Remarks</th>
                                    <th style="text-align: right;">Recording</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($CallLogs->take(3) as $cLog)
                                    @php
                                        $cs = $cLog->status_name ?: 'Connected';
                                        $csl = strtolower($cs);
                                        $bdg = 'ok';
                                        if (strpos($csl, 'follow') !== false || strpos($csl, 'pending') !== false) $bdg = 'warn';
                                        elseif (strpos($csl, 'new') !== false || strpos($csl, 'ringing') !== false) $bdg = 'live';
                                        elseif (strpos($csl, 'reject') !== false || strpos($csl, 'not') !== false || strpos($csl, 'lost') !== false) $bdg = 'bad';
                                        $dur = (!empty($cLog->duration) && is_numeric($cLog->duration)) ? sprintf('%02d:%02d', floor($cLog->duration / 60), $cLog->duration % 60) : '—';
                                        $rUrl = $cLog->click_recording_url ?: (!empty($cLog->recording) ? asset('uploads/recordings/' . $cLog->recording) : '');
                                    @endphp
                                    <tr>
                                        <td style="white-space: nowrap;">{{ date('d-M-Y h:i A', strtotime($cLog->created_at)) }}</td>
                                        <td style="font-weight: 600;">{{ $cLog->called_by ?: '—' }}</td>
                                        <td class="mono font-weight-bold">{{ $dur }}</td>
                                        <td>
                                            <span class="lg-modal-status-badge {{ $bdg }}">
                                                <span class="lg-modal-status-dot"></span>
                                                {{ $cs }}
                                            </span>
                                        </td>
                                        <td style="max-width: 260px; white-space: normal;">{{ $cLog->remarks ?: '—' }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            @if(!empty($rUrl))
                                                <button type="button" class="lg-btn-play" onclick="playModalAudio('{{ $rUrl }}', 'Call on {{ date('d-M h:i A', strtotime($cLog->created_at)) }}')">
                                                    <i class="fa fa-play" style="font-size: 10px;"></i>
                                                    Play
                                                </button>
                                            @else
                                                <span class="text-muted" style="font-size: 12px;">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Tab Pane 2: Call History -->
        <div id="modalTabPaneHistory" style="display: none;">
            <div class="lg-modal-table-wrap">
                <table class="lg-modal-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Agent</th>
                            <th>Duration</th>
                            <th>Call Status / Disposition</th>
                            <th>Sub-disposition / Remarks</th>
                            <th style="text-align: right;">Recording / Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($CallLogs as $call)
                            @php
                                $cStatus = $call->status_name ?: 'Connected';
                                $csLower = strtolower($cStatus);
                                $cBadge = 'ok';
                                if (strpos($csLower, 'follow') !== false || strpos($csLower, 'pending') !== false) {
                                    $cBadge = 'warn';
                                } elseif (strpos($csLower, 'new') !== false || strpos($csLower, 'ringing') !== false) {
                                    $cBadge = 'live';
                                } elseif (strpos($csLower, 'not') !== false || strpos($csLower, 'reject') !== false || strpos($csLower, 'lost') !== false || strpos($csLower, 'dnd') !== false) {
                                    $cBadge = 'bad';
                                }
                                $durationFormatted = !empty($call->duration) && is_numeric($call->duration) ? sprintf('%02d:%02d', floor($call->duration / 60), $call->duration % 60) : '—';
                                $recUrl = $call->click_recording_url ?: (!empty($call->recording) ? asset('uploads/recordings/' . $call->recording) : '');
                            @endphp
                            <tr>
                                <td style="white-space: nowrap;">
                                    <div style="font-weight: 600;">{{ date('d-M-Y', strtotime($call->created_at)) }}</div>
                                    <div style="font-size: 12px; color: #64748B;">{{ date('h:i:s A', strtotime($call->created_at)) }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #0F172A;">{{ $call->called_by ?: 'Agent' }}</div>
                                    <div style="font-size: 11.5px; color: #64748B;">{{ $call->call_type ? strtoupper($call->call_type) : 'DIAL' }}</div>
                                </td>
                                <td class="mono font-weight-bold" style="font-size: 13.5px;">{{ $durationFormatted }}</td>
                                <td>
                                    <span class="lg-modal-status-badge {{ $cBadge }}">
                                        <span class="lg-modal-status-dot"></span>
                                        {{ $cStatus }}
                                    </span>
                                </td>
                                <td style="max-width: 280px; white-space: normal;">
                                    {{ $call->remarks ?: '—' }}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="d-inline-flex align-items-center" style="gap: 6px;">
                                        @if(!empty($recUrl))
                                            <button type="button" class="lg-btn-play" onclick="playModalAudio('{{ $recUrl }}', 'Recording ({{ date('d-M-Y h:i A', strtotime($call->created_at)) }})')">
                                                <i class="fa fa-play" style="font-size: 10px;"></i>
                                                Play
                                            </button>
                                        @else
                                            <span class="text-muted" style="font-size: 12px;">No audio</span>
                                        @endif

                                        @if(function_exists('route') && \Illuminate\Support\Facades\Route::has('recordingsTranscript'))
                                            <button type="button" class="lg-btn-transcript" onclick="if(typeof openTranscriptModal==='function'){openTranscriptModal({{ $call->id }}, 2);}else{toastr.info('Opening transcript for call #{{ $call->id }}');}" title="View Transcript">
                                                <i class="fa fa-file-alt"></i>
                                                Transcript
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div style="padding: 30px; color: #64748B;">
                                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px;">
                                            <i class="fa fa-phone-slash"></i>
                                        </div>
                                        <h6 style="font-weight: 700; color: #0F172A; margin-bottom: 4px;">No Call History</h6>
                                        <p style="font-size: 13px; margin: 0;">No calls have been logged for this lead yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Integrated Audio Player Strip -->
            <div id="modalAudioPlayerBar" class="lg-modal-audio-bar">
                <div class="d-flex align-items-center gap-2" style="gap: 8px;">
                    <i class="fa fa-volume-up text-success" style="font-size: 16px;"></i>
                    <span id="modalAudioTitle" style="font-size: 12.5px; font-weight: 600;">Playing Call Recording</span>
                </div>
                <audio id="modalAudioPlayerElem" controls></audio>
                <button type="button" onclick="closeModalAudio()" style="background: transparent; border: 0; color: #94A3B8; font-size: 18px; cursor: pointer; line-height: 1;" title="Close Player">
                    &times;
                </button>
            </div>
        </div>

    </div>

    <!-- Scoped Modal JavaScript -->
    <script>
        function switchModalTab(tab) {
            if (tab === 'overview') {
                $('#modalTabBtnOverview').addClass('active');
                $('#modalTabBtnHistory').removeClass('active');
                $('#modalTabPaneOverview').show();
                $('#modalTabPaneHistory').hide();
            } else if (tab === 'history') {
                $('#modalTabBtnOverview').removeClass('active');
                $('#modalTabBtnHistory').addClass('active');
                $('#modalTabPaneOverview').hide();
                $('#modalTabPaneHistory').show();
            }
        }

        function toggleModalFollowupDrawer() {
            var $d = $('#drawerModalFollowup');
            $('#drawerModalReassign').hide();
            $d.slideToggle(150);
        }

        function toggleModalReassignDrawer() {
            var $d = $('#drawerModalReassign');
            $('#drawerModalFollowup').hide();
            $d.slideToggle(150);
        }

        function submitModalFollowup(e) {
            e.preventDefault();
            var dt = $('#inputModalFollowupDate').val();
            var remarks = $('#inputModalFollowupRemarks').val();
            if (!dt) {
                toastr.warning("Please choose a follow-up date and time.");
                return;
            }

            var $btn = $('#btnSaveFollowup');
            var $spin = $('#spinFollowup');
            $btn.prop('disabled', true);
            $spin.removeClass('d-none');

            $.ajax({
                url: "{{ route('leadsScheduleFollowup', $Lead->id) }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    follow_up_on: dt,
                    remarks: remarks
                },
                dataType: "json",
                success: function(resp) {
                    $btn.prop('disabled', false);
                    $spin.addClass('d-none');
                    if (resp.success) {
                        toastr.success(resp.message || "Follow-up scheduled successfully!");
                        $('#modalValNextFollowup').text(resp.follow_up_on);
                        $('#drawerModalFollowup').slideUp(150);
                        // Refresh dataTable background row if table exists
                        if (typeof dataTable !== 'undefined' && dataTable && typeof dataTable.draw === 'function') {
                            dataTable.draw(false);
                        }
                    } else {
                        toastr.error(resp.message || "Failed to schedule follow-up.");
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $spin.addClass('d-none');
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : "Error scheduling follow-up.";
                    toastr.error(err);
                }
            });
        }

        function submitModalReassign(e) {
            e.preventDefault();
            var agentId = $('#selectModalAssignedTo').val();
            if (!agentId) {
                toastr.warning("Please select an agent to reassign this lead.");
                return;
            }

            var $btn = $('#btnSaveReassign');
            var $spin = $('#spinReassign');
            $btn.prop('disabled', true);
            $spin.removeClass('d-none');

            $.ajax({
                url: "{{ route('leadsReassign', $Lead->id) }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    assigned_to: agentId
                },
                dataType: "json",
                success: function(resp) {
                    $btn.prop('disabled', false);
                    $spin.addClass('d-none');
                    if (resp.success) {
                        toastr.success(resp.message || "Lead reassigned successfully!");
                        $('#modalValAssignedAgent').text(resp.agent_name);
                        $('#drawerModalReassign').slideUp(150);
                        // Refresh dataTable background row if table exists
                        if (typeof dataTable !== 'undefined' && dataTable && typeof dataTable.draw === 'function') {
                            dataTable.draw(false);
                        }
                    } else {
                        toastr.error(resp.message || "Failed to reassign lead.");
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $spin.addClass('d-none');
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : "Error reassigning lead.";
                    toastr.error(err);
                }
            });
        }

        function initiateModalCall(phone, callUrl) {
            if (!phone) {
                toastr.warning("No phone number registered for this lead.");
                return;
            }

            if (callUrl && callUrl !== '') {
                // Confirm or directly open calling workspace
                toastr.info("Opening Sales Calling Workspace for " + phone);
                window.location.href = callUrl;
                return;
            }

            toastr.info("Dialing " + phone + "...");
            $.ajax({
                url: "{{ route('logCall') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    lead_id: "{{ $Lead->id }}",
                    phonenumber: phone,
                    call_type: "click2call"
                },
                success: function(resp) {
                    if (typeof resp === 'string') {
                        try { resp = JSON.parse(resp); } catch(e) {}
                    }
                    if (resp && resp.success === false) {
                        toastr.warning(resp.message || "Call could not be placed");
                        return;
                    }
                    toastr.success("Call connected to " + phone);
                },
                error: function(xhr) {
                    var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : null;
                    if (msg) {
                        toastr.warning(msg);
                    } else {
                        toastr.error("Failed to initiate call");
                    }
                }
            });
        }

        function playModalAudio(url, title) {
            if (!url) return;
            var bar = document.getElementById('modalAudioPlayerBar');
            var player = document.getElementById('modalAudioPlayerElem');
            var titleElem = document.getElementById('modalAudioTitle');
            if (title && titleElem) {
                titleElem.innerText = title;
            }
            player.src = url;
            bar.style.display = 'flex';
            player.play();
        }

        function closeModalAudio() {
            var bar = document.getElementById('modalAudioPlayerBar');
            var player = document.getElementById('modalAudioPlayerElem');
            if (player) player.pause();
            if (bar) bar.style.display = 'none';
        }
    </script>
</div>
