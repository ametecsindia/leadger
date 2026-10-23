@extends('dashboard.layouts.master')
@section('title', 'Disposition settings')

@push("after-styles")
<style>
/* Page Canvas */
.lg-disposition-dashboard {
    padding: 24px 32px 48px 32px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
}

/* 1. Page Header (Gear icon beside Title) */
.lg-disp-header-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.lg-disp-header-icon-box {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 12px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0F172A;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    font-size: 20px;
    flex-shrink: 0;
}

.lg-disp-header-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-disp-page-title {
    font-size: 26px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.lg-disp-page-subtitle {
    font-size: 14px;
    color: #64748B;
    margin: 0;
    font-weight: 400;
}

/* 2. Main White Container */
.lg-settings-container {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    width: 100%;
}

/* 3. Horizontal Settings Tab Navigation */
.lg-settings-tabs-row {
    display: flex;
    align-items: center;
    gap: 28px;
    padding: 0 28px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.lg-settings-tab {
    padding: 16px 0;
    font-size: 14px;
    font-weight: 600;
    color: #64748B;
    text-decoration: none !important;
    border-bottom: 2px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-settings-tab:hover {
    color: #0F172A;
}

.lg-settings-tab.active {
    color: var(--lg-accent);
    border-bottom-color: var(--lg-accent);
}

/* 4. Container Inner Body */
.lg-settings-body {
    padding: 24px 28px 32px 28px;
}

.lg-settings-body-subtitle {
    font-size: 14px;
    color: #64748B;
    margin: 0 0 20px 0;
    font-weight: 400;
}

/* 5. Dispositions List */
.lg-dispositions-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* 6. Disposition Card */
.lg-disposition-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-disposition-card:hover {
    border-color: #CBD5E1;
}

/* Card Header (Title & Checkbox) */
.lg-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}

.lg-card-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-status-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.lg-disposition-name {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

/* Follow-up Checkbox */
.lg-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    margin: 0;
    user-select: none;
    font-size: 13.5px;
    font-weight: 500;
    color: #475569;
}

.lg-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 1px solid #CBD5E1;
    cursor: pointer;
    accent-color: var(--lg-accent);
}

/* Sub-dispositions Chips */
.lg-chips-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.lg-substatus-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    transition: all 0.15s ease;
}

.lg-chip-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.lg-chip-text {
    line-height: 1.3;
}

.lg-chip-remove {
    background: none;
    border: none;
    padding: 0;
    margin-left: 2px;
    color: #94A3B8;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s ease;
}

.lg-chip-remove:hover {
    color: #EF4444;
}

/* Add Sub-disposition Inline Input + Button */
.lg-add-substatus-row {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 540px;
}

.lg-substatus-input {
    flex: 1;
    height: 42px;
    padding: 0 14px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    background: #FFFFFF;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-substatus-input::placeholder {
    color: #94A3B8;
}

.lg-substatus-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.1);
}

.lg-add-btn {
    height: 42px;
    padding: 0 18px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.lg-add-btn:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}
</style>
@endpush

@section('content')
<div class="lg-disposition-dashboard">
    <!-- Page Header -->
    <div class="lg-disp-header-row">
        <div class="lg-disp-header-icon-box">
            <i class="fa fa-cog"></i>
        </div>
        <div class="lg-disp-header-text-group">
            <h1 class="lg-disp-page-title">Disposition settings</h1>
            <p class="lg-disp-page-subtitle">Changes apply to the dialer straight away.</p>
        </div>
    </div>

    <!-- Main Settings Container -->
    <div class="lg-settings-container">
        <!-- Settings Body -->
        <div class="lg-settings-body">
            <p class="lg-settings-body-subtitle">Changes apply to the dialer straight away.</p>

            <!-- Dispositions Vertical List -->
            <div class="lg-dispositions-list">
                @foreach($Statuses as $status)
                    @php
                        $nameLower = strtolower(trim($status->name));
                        if (str_contains($nameLower, 'not connected') || str_contains($nameLower, 'not-connected') || str_contains($nameLower, 'unreachable')) {
                            $dotColor = '#64748B'; // Gray
                        } elseif (str_contains($nameLower, 'connected')) {
                            $dotColor = '#10B981'; // Green
                        } elseif (str_contains($nameLower, 'not interested')) {
                            $dotColor = '#EF4444'; // Red
                        } elseif (str_contains($nameLower, 'interested')) {
                            $dotColor = '#10B981'; // Green
                        } elseif (str_contains($nameLower, 'converted') || str_contains($nameLower, 'won') || str_contains($nameLower, 'sale')) {
                            $dotColor = '#059669'; // Dark Green
                        } elseif (str_contains($nameLower, 'wrong number') || str_contains($nameLower, 'invalid') || str_contains($nameLower, 'dnd')) {
                            $dotColor = '#EF4444'; // Red
                        } elseif (str_contains($nameLower, 'follow') || str_contains($nameLower, 'call back') || str_contains($nameLower, 'callback') || str_contains($nameLower, 'pending') || str_contains($nameLower, 'hold')) {
                            $dotColor = '#F59E0B'; // Orange
                        } else {
                            $dotColor = '#3B82F6'; // Default Blue
                        }
                    @endphp
                    <div class="lg-disposition-card" data-status-id="{{ $status->id }}">
                        <!-- Card Header -->
                        <div class="lg-card-header">
                            <div class="lg-card-title-group">
                                <span class="lg-status-dot" style="background-color: {{ $dotColor }};"></span>
                                <h3 class="lg-disposition-name">{{ $status->name }}</h3>
                            </div>
                            <label class="lg-checkbox-label">
                                <input type="checkbox" class="js-followup-toggle" data-status-id="{{ $status->id }}" {{ $status->lead_pool == 1 ? 'checked' : '' }}>
                                <span>Requires follow-up date</span>
                            </label>
                            <label class="lg-checkbox-label" title="This status cannot be saved for a call that did not connect">
                                <input type="checkbox" class="js-connected-toggle" data-status-id="{{ $status->id }}" {{ !empty($status->needs_connected_call) ? 'checked' : '' }}>
                                <span>Only after a connected call</span>
                            </label>
                        </div>

                        <!-- Sub-dispositions Chips -->
                        <div class="lg-chips-container" id="chips-container-{{ $status->id }}">
                            @if(isset($status->substatuses) && count($status->substatuses) > 0)
                                @foreach($status->substatuses as $sub)
                                    <span class="lg-substatus-chip" id="chip-{{ $sub->id }}">
                                        <span class="lg-chip-dot" style="background-color: {{ $dotColor }};"></span>
                                        <span class="lg-chip-text">{{ $sub->name }}</span>
                                        <button type="button" class="lg-chip-remove js-delete-substatus" data-id="{{ $sub->id }}" title="Remove">&times;</button>
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        <!-- Add Sub-disposition Inline Form -->
                        <div class="lg-add-substatus-row">
                            <input type="text" class="lg-substatus-input js-substatus-input" id="input-substatus-{{ $status->id }}" data-status-id="{{ $status->id }}" placeholder="Add a sub-disposition" autocomplete="off" />
                            <button type="button" class="lg-add-btn js-add-substatus" data-status-id="{{ $status->id }}">+ Add</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
$(document.body).on('change', '.js-followup-toggle', function() {
    var $checkbox = $(this);
    var statusId = $checkbox.data('status-id');
    var isChecked = $checkbox.is(':checked') ? 1 : 0;

    $.ajax({
        url: "{{ route('statusesToggleFollowup') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            status_id: statusId,
            requires_followup: isChecked
        },
        success: function(response) {
            if (response.success) {
                // Settings saved silently or brief feedback
            } else {
                alert(response.message || 'Error updating follow-up setting');
                $checkbox.prop('checked', !isChecked);
            }
        },
        error: function(xhr) {
            alert('Failed to update follow-up setting.');
            $checkbox.prop('checked', !isChecked);
        }
    });
});

$(document.body).on('change', '.js-connected-toggle', function() {
    var $checkbox = $(this);
    var isChecked = $checkbox.is(':checked') ? 1 : 0;

    $.post("{{ route('statusesToggleConnected') }}", {
        _token: "{{ csrf_token() }}",
        status_id: $checkbox.data('status-id'),
        needs_connected_call: isChecked
    }).done(function(response) {
        if (!response.success) {
            alert(response.message || 'Error updating connected-call setting');
            $checkbox.prop('checked', !isChecked);
        }
    }).fail(function() {
        alert('Failed to update connected-call setting.');
        $checkbox.prop('checked', !isChecked);
    });
});

function addSubstatus(statusId) {
    var $input = $('#input-substatus-' + statusId);
    var name = $.trim($input.val());

    if (!name) {
        return;
    }

    var $card = $('.lg-disposition-card[data-status-id="' + statusId + '"]');
    var dotColor = $card.find('.lg-status-dot').css('background-color') || '#3B82F6';

    $.ajax({
        url: "{{ route('statusesSubstatusStore') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            status_id: statusId,
            name: name
        },
        success: function(response) {
            if (response.success && response.substatus) {
                var sub = response.substatus;
                var chipHtml = '<span class="lg-substatus-chip" id="chip-' + sub.id + '">' +
                               '<span class="lg-chip-dot" style="background-color: ' + dotColor + ';"></span>' +
                               '<span class="lg-chip-text">' + escapeHtml(sub.name) + '</span>' +
                               '<button type="button" class="lg-chip-remove js-delete-substatus" data-id="' + sub.id + '" title="Remove">&times;</button>' +
                               '</span>';

                $('#chips-container-' + statusId).append(chipHtml);
                $input.val('');
            } else {
                alert(response.message || 'Failed to add sub-disposition');
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add sub-disposition.';
            alert(msg);
        }
    });
}

$(document.body).on('click', '.js-add-substatus', function() {
    var statusId = $(this).data('status-id');
    addSubstatus(statusId);
});

$(document.body).on('keypress', '.js-substatus-input', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        var statusId = $(this).data('status-id');
        addSubstatus(statusId);
    }
});

$(document.body).on('click', '.js-delete-substatus', function() {
    var subId = $(this).data('id');
    if (!subId) return;

    if (!confirm('Are you sure you want to remove this sub-disposition?')) {
        return;
    }

    $.ajax({
        url: "{{ route('statusesSubstatusDelete') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: subId
        },
        success: function(response) {
            if (response.success) {
                $('#chip-' + subId).fadeOut(200, function() {
                    $(this).remove();
                });
            } else {
                alert(response.message || 'Failed to remove sub-disposition');
            }
        },
        error: function(xhr) {
            alert('Error removing sub-disposition.');
        }
    });
});

function escapeHtml(text) {
    return $('<div>').text(text).html();
}
</script>
@endpush
