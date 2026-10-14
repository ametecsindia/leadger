@extends('dashboard.layouts.master')
@section('title', 'AI Usage & Billing')

@section('content')
<div class="padding">
    <div class="box">
        <div class="box-header d-flex align-items-center justify-content-between p-3 border-bottom" style="background: #0A1426; color: #FFFFFF; border-radius: 6px 6px 0 0;">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('aiSettings') }}" class="btn btn-sm btn-outline-light text-white" title="Back to AI Settings">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h5 class="m-0 font-weight-bold" style="color: #FFFFFF; font-size: 16px;">AI Usage & Audit Logs</h5>
                    <small style="color: #94A3B8;">Track Sarvam AI API usage, audio duration, and billing metrics</small>
                </div>
            </div>
            <div>
                <a href="{{ route('aiSettings') }}" class="btn btn-sm btn-outline-light text-white">
                    <i class="fas fa-cog me-1"></i> AI Settings
                </a>
            </div>
        </div>

        <div class="box-body p-4" style="background: #F8FAFC;">
            
            <!-- Summary Metric Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Calls Analyzed (Current Month)</div>
                                <div class="h3 font-weight-bold text-dark mt-1 mb-0">{{ number_format($totalCalls) }}</div>
                            </div>
                            <div class="rounded-circle bg-light-primary p-3 text-primary">
                                <i class="fas fa-phone-alt fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Total Audio Duration</div>
                                <div class="h3 font-weight-bold text-dark mt-1 mb-0">{{ $totalMinutes }} <span class="small font-weight-normal text-muted">mins</span></div>
                            </div>
                            <div class="rounded-circle bg-light-info p-3 text-info">
                                <i class="fas fa-stopwatch fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Total AI Usage</div>
                                <div class="h3 font-weight-bold text-primary mt-1 mb-0">₹{{ number_format($totalUsageCost, 4) }}</div>
                            </div>
                            <div class="rounded-circle bg-light-success p-3 text-success">
                                <i class="fas fa-receipt fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold text-dark mb-1">From Date</label>
                            <input type="date" id="filter_from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold text-dark mb-1">To Date</label>
                            <input type="date" id="filter_to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold text-dark mb-1">Status</label>
                            <select id="filter_status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="completed">Success</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2" style="margin-top: 28px;">
                            <button type="button" class="btn btn-sm btn-primary w-100" id="btn_apply_filter" style="background: var(--lg-accent); border-color: var(--lg-accent);">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                            <button type="button" class="btn btn-sm btn-light border w-100" id="btn_reset_filter">
                                <i class="fas fa-redo me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Data Table -->
            <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-list text-primary"></i> AI Usage Records
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="aiUsageTable" style="width: 100%;">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Call ID</th>
                                    <th>Agent</th>
                                    <th>Duration</th>
                                    <th>Service</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables Ajax Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#aiUsageTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('aiUsageData') }}",
            type: "POST",
            data: function(d) {
                d._token = "{{ csrf_token() }}";
                d.from_date = $('#filter_from_date').val();
                d.to_date = $('#filter_to_date').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'date', name: 'date' },
            { data: 'call_id', name: 'call_id' },
            { data: 'agent', name: 'agent' },
            { data: 'duration', name: 'duration' },
            { data: 'service', name: 'service' },
            { data: 'usage', name: 'usage' },
            { data: 'status', name: 'status' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            emptyTable: "No AI usage logs recorded yet."
        }
    });

    $('#btn_apply_filter').click(function() {
        table.draw();
    });

    $('#btn_reset_filter').click(function() {
        $('#filter_from_date').val("{{ date('Y-m-01') }}");
        $('#filter_to_date').val("{{ date('Y-m-d') }}");
        $('#filter_status').val("");
        table.draw();
    });
});
</script>
@endsection
