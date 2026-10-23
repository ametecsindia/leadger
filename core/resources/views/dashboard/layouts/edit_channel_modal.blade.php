<!-- ==========================================================================
     LeadGER Reusable Edit Channel Modal (Click2Call & WebRTC)
     Matches the "Edit Product" Modal design on /admin/productServices
     ========================================================================== -->
<style>
/* Modal Overlay with Dark Blurred Backdrop */
.lg-channel-modal-overlay {
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

.lg-channel-modal-overlay.active {
    display: flex !important;
}

/* White Card Modal Container (~560px wide, 16px radius, soft shadow) */
.lg-channel-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
    overflow: hidden;
    animation: lgChannelModalZoom 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    margin: auto;
    display: flex;
    flex-direction: column;
}

@keyframes lgChannelModalZoom {
    from {
        opacity: 0;
        transform: scale(0.96) translateY(6px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Header: Title bold ~20px on left, square X button on right, 1px divider */
.lg-channel-modal-header {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.lg-channel-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

/* Add mode only (hidden while editing) */
.lg-channel-modal-subtitle {
    font-size: 13.5px;
    font-weight: 500;
    color: #64748B;
    margin: 4px 0 0;
    line-height: 1.4;
}

.lg-channel-notice {
    padding: 10px 14px;
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    border-radius: 10px;
    color: #9A3412;
    font-size: 13.5px;
    font-weight: 600;
}

.lg-channel-hint {
    color: #64748B;
    font-size: 12.5px;
    font-weight: 500;
    margin-top: 2px;
}

.lg-channel-control[readonly] {
    background: #F8FAFC;
    color: #475569;
}

.lg-channel-modal-close {
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
    outline: none;
}

.lg-channel-modal-close:hover {
    background: #E2E8F0;
    color: #0F172A;
    border-color: #CBD5E1;
}

/* Body: padding 24px 32px, small gray labels, full width inputs ~48px high */
.lg-channel-modal-body {
    padding: 24px 32px;
    background: #FFFFFF;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.lg-channel-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-channel-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #64748B;
    margin: 0;
}

.lg-channel-control {
    width: 100%;
    height: 48px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14.5px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}

select.lg-channel-control {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 42px;
    cursor: pointer;
}

.lg-channel-control:focus {
    border-color: #1769E8;
    box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.14);
}

.lg-channel-control.has-error {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-channel-error {
    color: #EF4444;
    font-size: 12.5px;
    font-weight: 500;
    margin-top: 2px;
    display: none;
}

/* Footer: 1px divider, Cancel (white bordered) then Save Changes (solid blue) */
.lg-channel-modal-footer {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}

.lg-channel-btn-cancel {
    height: 44px;
    padding: 0 22px;
    background: #FFFFFF;
    color: #172B4D;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

.lg-channel-btn-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-channel-btn-save {
    height: 44px;
    padding: 0 24px;
    background: #1769E8;
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

.lg-channel-btn-save:hover {
    background: #1256BD;
}

.lg-channel-btn-save:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 576px) {
    .lg-channel-modal-wrapper {
        max-width: 95vw;
        border-radius: 14px;
    }
    .lg-channel-modal-header {
        padding: 16px 20px;
    }
    .lg-channel-modal-body {
        padding: 20px;
        gap: 16px;
    }
    .lg-channel-modal-footer {
        padding: 14px 20px;
        flex-direction: column-reverse;
        gap: 10px;
    }
    .lg-channel-btn-cancel,
    .lg-channel-btn-save {
        width: 100%;
        height: 44px;
    }
}
</style>

<!-- POPUP MODAL -->
<div id="lgEditChannelModal" class="lg-channel-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="lgChannelModalTitle">
    <div class="lg-channel-modal-wrapper" role="document">
        
        <!-- MODAL HEADER -->
        <div class="lg-channel-modal-header">
            <div>
                <h3 class="lg-channel-modal-title" id="lgChannelModalTitle">Edit Click2Call</h3>
                <p class="lg-channel-modal-subtitle" id="lgChannelModalSubtitle" hidden></p>
            </div>
            <button type="button" class="lg-channel-modal-close" id="lgBtnCloseChannelModal" title="Close" aria-label="Close" onclick="lgCloseEditChannelModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- MODAL FORM -->
        <form id="lgEditChannelForm" method="POST" action="" onsubmit="return false;" novalidate>
            @csrf
            <div class="lg-channel-modal-body">

                <!-- ADD MODE: no channels / no users left -->
                <div id="lgChannelModalNotice" class="lg-channel-notice" role="status" hidden></div>

                <!-- USER FIELD -->
                <div class="lg-channel-form-group">
                    <label class="lg-channel-label" for="channel_user">User</label>
                    <select id="channel_user" name="user" class="lg-channel-control" required>
                        <!-- Populated dynamically -->
                    </select>
                    <div id="channel_user_error" class="lg-channel-error">Please select a user.</div>
                </div>

                <!-- AGENT NUMBER FIELD (Click2Call only: WebRTC calls do not use an agent number) -->
                <div class="lg-channel-form-group" id="lgChannelAgentGroup">
                    <label class="lg-channel-label" for="channel_click_agent_number">Agent Number</label>
                    <input type="text" 
                           id="channel_click_agent_number" 
                           name="click_agent_number" 
                           class="lg-channel-control" 
                           placeholder="e.g. 9777436222" 
                           autocomplete="off"
                           required>
                    <div id="channel_click_agent_number_error" class="lg-channel-error">Agent number is required.</div>
                    <div id="channel_click_agent_number_hint" class="lg-channel-hint" hidden></div>
                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="lg-channel-modal-footer">
                <button type="button" class="lg-channel-btn-cancel" id="lgBtnCancelChannelModal" onclick="lgCloseEditChannelModal()">
                    Cancel
                </button>
                <button type="button" class="lg-channel-btn-save" id="lgBtnSaveChannelModal" onclick="lgSubmitEditChannelModal()">
                    Save Changes
                </button>
            </div>
        </form>

    </div>
</div>

<script>
/**
 * Shared Edit Channel Modal Logic (Click2Call & WebRTC)
 */
var currentEditingChannelType = 'click2Call';
var currentEditingUserId = null;
var lgChannelMode = 'edit';          // 'edit' or 'add' (Add WebRTC Channel)
var lgChannelAddData = null;         // create() data for add mode: counts, users without WebRTC, numbers in use
var lgChannelReturnFocus = null;     // add mode: the "+ Add" button gets focus back on close
var lgChannelSaving = false;

// Puts the shared popup in edit or add mode; edit mode is the popup exactly as it always was
function lgChannelSetMode(mode) {
    lgChannelMode = mode;
    var isAdd = mode === 'add';
    $('#lgChannelModalSubtitle').prop('hidden', !isAdd).text(isAdd ? 'Assign a WebRTC channel to a user' : '');
    $('#lgChannelModalNotice').prop('hidden', true).text('');
    $('#channel_click_agent_number_hint').prop('hidden', true).text('');
    if (isAdd) $('#lgEditChannelModal').attr('aria-describedby', 'lgChannelModalSubtitle');
    else $('#lgEditChannelModal').removeAttr('aria-describedby');
    $('#channel_user').prop('disabled', false);
    $('#channel_click_agent_number').prop('readonly', false);
    $('#lgBtnSaveChannelModal').prop('disabled', false).text(isAdd ? 'Save' : 'Save Changes');
    $('#lgChannelAgentGroup').css('display', '');
    lgChannelSaving = false;
}

// Agent number: Click2Call rings it; WebRTC calls do not use it (the field is hidden and not sent; the saved
// number stays for Click2Call)
function lgChannelUsesAgentNumber() {
    return currentEditingChannelType !== 'webrtc';
}

// The user list is a Select2 box (selects are enhanced globally): show the selected user after filling it
function lgChannelRefreshUserBox() {
    var $sel = $('#channel_user');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.trigger('change.select2');
}

function lgChannelApplyChannelType() {
    $('#lgChannelAgentGroup').css('display', lgChannelUsesAgentNumber() ? '' : 'none');
}

// A save / server message: under the agent number (Click2Call), in the notice area when that field is hidden
function lgChannelShowMessage(message) {
    if (lgChannelUsesAgentNumber()) {
        $('#channel_click_agent_number_error').text(message).show();
        $('#channel_click_agent_number').addClass('has-error');
    } else {
        $('#lgChannelModalNotice').text(message).prop('hidden', false);
    }
}

function lgOpenEditChannelModal(id, channelType, triggerBtn) {
    lgChannelSetMode('edit');
    currentEditingChannelType = channelType || 'click2Call';
    currentEditingUserId = id;

    var isWebRTC = (currentEditingChannelType === 'webrtc');
    var modalTitle = isWebRTC ? 'Edit WebRTC' : 'Edit Click2Call';
    var formAction = isWebRTC ? "{{ route('webrtcStore') }}" : "{{ route('click2CallStore') }}";
    var fetchUrl = isWebRTC ? ("/admin/webrtc/" + id + "/edit") : ("/admin/click2Call/" + id + "/edit");

    $('#lgChannelModalTitle').text(modalTitle);
    $('#lgEditChannelForm').attr('action', formAction);
    lgChannelApplyChannelType();

    // Reset validations
    $('.lg-channel-error').hide().text('');
    $('.lg-channel-control').removeClass('has-error');
    $('#lgBtnSaveChannelModal').prop('disabled', false).text('Save Changes');

    // Optimistic fallback from trigger element data attributes or table row
    var prefillName = '';
    var prefillNumber = '';

    if (triggerBtn) {
        var $btn = $(triggerBtn);
        prefillName = $btn.data('name') || '';
        prefillNumber = $btn.data('number') || '';
    }

    if (!prefillName && typeof id !== 'undefined') {
        var $row = $('tr[data-id="' + id + '"], .lg-c2c-row:has(a[href*="/' + id + '/"])');
        if ($row.length) {
            prefillName = $row.find('.lg-name-bold, .lg-user-flex span').text().trim();
            prefillNumber = $row.find('td:nth-child(4) span, .lg-c2c-mobile-col').text().trim();
            if (prefillNumber === '—' || prefillNumber === '-') prefillNumber = '';
        }
    }

    var $userSelect = $('#channel_user');
    $userSelect.empty();
    if (prefillName) {
        $userSelect.append($('<option>', {
            value: id,
            text: prefillName,
            selected: true
        }));
    }
    $('#channel_click_agent_number').val(prefillNumber);
    lgChannelRefreshUserBox();

    // Open Modal
    $('#lgEditChannelModal').addClass('active');

    // Fetch freshest record details via AJAX
    $.ajax({
        url: fetchUrl,
        type: 'GET',
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(res) {
            if (res && res.status === 'success' && res.user) {
                var u = res.user;
                $userSelect.empty();
                if (res.users && res.users.length) {
                    res.users.forEach(function(usr) {
                        $userSelect.append($('<option>', {
                            value: usr.id,
                            text: usr.name,
                            selected: (usr.id == u.id)
                        }));
                    });
                } else {
                    $userSelect.append($('<option>', {
                        value: u.id,
                        text: u.name,
                        selected: true
                    }));
                }
                $('#channel_click_agent_number').val(u.click_agent_number || '');
                lgChannelRefreshUserBox();
            }
        },
        error: function() {
            // Keep optimistic values if already populated
        }
    });

    setTimeout(function() {
        (isWebRTC ? $userSelect : $('#channel_click_agent_number')).focus();
    }, 100);
}

function lgCloseEditChannelModal() {
    var returnFocus = lgChannelMode === 'add' ? lgChannelReturnFocus : null;
    $('#lgEditChannelModal').removeClass('active');
    $('.lg-channel-error').hide().text('');
    $('.lg-channel-control').removeClass('has-error');
    lgChannelSetMode('edit');
    currentEditingUserId = null;
    lgChannelReturnFocus = null;
    if (returnFocus) returnFocus.focus();
}

/**
 * "+ Add" on the WebRTC page: the same popup in add mode. Lists only users without WebRTC and blocks saving
 * when no licensed channel is free. Saving uses the same store route as the old Add page.
 */
function lgOpenAddChannelModal(triggerBtn) {
    lgChannelSetMode('add');
    currentEditingChannelType = 'webrtc';
    currentEditingUserId = null;
    lgChannelAddData = null;
    lgChannelReturnFocus = triggerBtn || null;

    $('#lgChannelModalTitle').text('Add WebRTC Channel');
    $('#lgEditChannelForm').attr('action', "{{ route('webrtcStore') }}");
    lgChannelApplyChannelType();
    $('.lg-channel-error').hide().text('');
    $('.lg-channel-control').removeClass('has-error');

    var $userSelect = $('#channel_user');
    $userSelect.empty().append($('<option>', { value: '', text: 'Loading users…' })).prop('disabled', true);
    $('#channel_click_agent_number').val('');
    $('#lgBtnSaveChannelModal').prop('disabled', true);

    $('#lgEditChannelModal').addClass('active');
    setTimeout(function() { $userSelect.focus(); }, 100);

    $.ajax({
        url: "{{ route('webrtcCreate') }}",
        type: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        success: function(res) {
            if (lgChannelMode !== 'add') return;
            lgChannelAddData = res;
            lgChannelUpdateStatCards(res);
            $userSelect.empty().append($('<option>', { value: '', text: 'Select a user' }));
            (res.users || []).forEach(function(u) {
                $userSelect.append($('<option>', { value: u.id, text: u.name }));
            });
            $userSelect.prop('disabled', false);
            lgChannelRefreshUserBox();
            if (!res.available) {
                lgChannelAddBlocked('No channels available');
            } else if (!(res.users || []).length) {
                lgChannelAddBlocked('All users already have a WebRTC channel');
            } else {
                $('#lgBtnSaveChannelModal').prop('disabled', false);
            }
            $userSelect.focus();
        },
        error: function() {
            if (lgChannelMode !== 'add') return;
            $userSelect.empty().append($('<option>', { value: '', text: 'Select a user' })).prop('disabled', false);
            lgChannelAddBlocked('Could not load the users. Close the popup and try again.');
        }
    });
}

function lgChannelAddBlocked(message) {
    $('#lgChannelModalNotice').text(message).prop('hidden', false);
    $('#lgBtnSaveChannelModal').prop('disabled', true);
}

// Stat cards (Total / Used / Available) on the channel page, from create() data
function lgChannelUpdateStatCards(data) {
    if (!data) return;
    $('.channel-stats .stat-total .stat-value').text(data.total);
    $('.channel-stats .stat-used .stat-value').text(data.used);
    $('.channel-stats .stat-available .stat-value').text(data.available);
}

// Add mode: picking a user shows their current agent number. A Click2Call user keeps theirs (store() does not
// change it), so it is shown read-only.
function lgChannelAddUserChanged() {
    var id = $('#channel_user').val();
    var user = lgChannelAddData ? (lgChannelAddData.users || []).filter(function(u) { return String(u.id) === String(id); })[0] : null;
    var $num = $('#channel_click_agent_number');
    var $hint = $('#channel_click_agent_number_hint');
    $('#channel_user').removeClass('has-error');
    $('#channel_user_error').hide();
    if (!lgChannelUsesAgentNumber()) return;
    if (user && user.c2c && user.number) {
        $num.val(user.number).prop('readonly', true);
        $hint.text('This user\'s Click2Call agent number is used.').prop('hidden', false);
    } else {
        if ($num.prop('readonly')) $num.val('');
        $num.prop('readonly', false);
        if (user && user.number && !$num.val()) $num.val(user.number);
        $hint.prop('hidden', true).text('');
    }
}

function lgSubmitAddChannelModal() {
    if (lgChannelSaving || $('#lgBtnSaveChannelModal').prop('disabled')) return;

    var userId = $('#channel_user').val();
    var $num = $('#channel_click_agent_number');
    var agentNumber = $num.val().trim();
    var hasError = false;

    $('.lg-channel-error').hide();
    $('.lg-channel-control').removeClass('has-error');

    function fieldError(field, message) {
        $('#' + field + '_error').text(message).show();
        $('#' + field).addClass('has-error');
        hasError = true;
    }

    if (!userId) fieldError('channel_user', 'Please select a user.');

    if (!lgChannelUsesAgentNumber()) {
        // WebRTC: no agent number
    } else if (!agentNumber) {
        fieldError('channel_click_agent_number', 'Agent number is required.');
    } else if (!/^[0-9]+$/.test(agentNumber)) {
        fieldError('channel_click_agent_number', 'Use digits only.');
    } else if (agentNumber.length !== 10) {
        fieldError('channel_click_agent_number', 'Enter a 10-digit mobile number.');
    } else if (lgChannelAddData) {
        var taken = (lgChannelAddData.numbers || []).filter(function(n) {
            return n.number === agentNumber && String(n.id) !== String(userId);
        })[0];
        if (taken) fieldError('channel_click_agent_number', 'This number is already used by ' + taken.name + '.');
    }

    if (lgChannelAddData && !lgChannelAddData.available) {
        lgChannelAddBlocked('No channels available');
        return;
    }
    if (hasError) {
        $('.lg-channel-control.has-error').first().focus();
        return;
    }

    var $btn = $('#lgBtnSaveChannelModal');
    lgChannelSaving = true;
    $btn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: $('#lgEditChannelForm').attr('action'),
        type: 'POST',
        data: lgChannelUsesAgentNumber()
            ? { _token: "{{ csrf_token() }}", user: userId, click_agent_number: agentNumber }
            : { _token: "{{ csrf_token() }}", user: userId },
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        success: function(res) {
            lgChannelSaving = false;
            if (res && (res.status === 'success' || res.success)) {
                lgCloseEditChannelModal();
                lgChannelToast('Channel added', 'success');
                if ($('#leads_table').length && $.fn.DataTable && $.fn.DataTable.isDataTable('#leads_table')) {
                    $('#leads_table').DataTable().ajax.reload(null, false);
                }
                // fresh Used / Available counts
                $.ajax({
                    url: "{{ route('webrtcCreate') }}", type: 'GET', dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: lgChannelUpdateStatCards
                });
            } else {
                lgChannelAddFailed(res && res.message);
            }
        },
        error: function(xhr) {
            lgChannelSaving = false;
            var res = xhr.responseJSON;
            if (res && res.errors) {
                if (res.errors.user) fieldError('channel_user', res.errors.user[0]);
                if (res.errors.click_agent_number && lgChannelUsesAgentNumber()) fieldError('channel_click_agent_number', res.errors.click_agent_number[0]);
            }
            lgChannelAddFailed(res && res.message);
        }
    });
}

function lgChannelAddFailed(message) {
    message = message || 'Could not add the channel. Please try again.';
    $('#lgBtnSaveChannelModal').prop('disabled', false).text('Save');
    $('#lgChannelModalNotice').text(message).prop('hidden', false);
    lgChannelToast(message, 'error');
}

function lgChannelToast(message, type) {
    if (typeof toastr !== 'undefined') {
        type === 'error' ? toastr.error(message) : toastr.success(message);
    } else {
        lgShowMiniToast(message, type);
    }
}

function lgSubmitEditChannelModal() {
    if (lgChannelMode === 'add') {
        lgSubmitAddChannelModal();
        return;
    }
    var userId = $('#channel_user').val();
    var agentNumber = $('#channel_click_agent_number').val().trim();
    var hasError = false;

    $('.lg-channel-error').hide();
    $('.lg-channel-control').removeClass('has-error');

    if (!userId) {
        $('#channel_user_error').text('Please select a user.').show();
        $('#channel_user').addClass('has-error');
        hasError = true;
    }

    if (!agentNumber && lgChannelUsesAgentNumber()) {
        $('#channel_click_agent_number_error').text('Agent number is required.').show();
        $('#channel_click_agent_number').addClass('has-error');
        hasError = true;
    }

    if (hasError) return;

    var $btn = $('#lgBtnSaveChannelModal');
    $btn.prop('disabled', true).text('Saving...');
    $('#lgChannelModalNotice').prop('hidden', true).text('');

    var url = $('#lgEditChannelForm').attr('action');
    var payload = { _token: "{{ csrf_token() }}", user: userId };
    if (lgChannelUsesAgentNumber()) payload.click_agent_number = agentNumber;   // WebRTC: the saved number is kept

    $.ajax({
        url: url,
        type: 'POST',
        data: payload,
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(res) {
            $btn.prop('disabled', false).text('Save Changes');
            if (res && (res.status === 'success' || res.success)) {
                lgCloseEditChannelModal();

                var successToastMsg = (currentEditingChannelType === 'webrtc') ? 'WebRTC updated' : 'Click2Call updated';
                if (typeof toastr !== 'undefined') {
                    toastr.success(successToastMsg);
                } else {
                    lgShowMiniToast(successToastMsg, 'success');
                }

                // Update table without full page reload if possible
                var updatedUser = res.user || { id: userId, click_agent_number: agentNumber };

                if (typeof dataTable !== 'undefined' && dataTable && typeof dataTable.ajax !== 'undefined') {
                    dataTable.ajax.reload(null, false);
                } else if ($('#leads_table').length && $.fn.DataTable && $.fn.DataTable.isDataTable('#leads_table')) {
                    $('#leads_table').DataTable().ajax.reload(null, false);
                } else {
                    // Update matching row in HTML table directly
                    var updated = false;
                    $('tr').each(function() {
                        var $tr = $(this);
                        var editLink = $tr.find('a[href*="/' + updatedUser.id + '/edit"], button[onclick*="' + updatedUser.id + '"]');
                        if (editLink.length || $tr.data('id') == updatedUser.id) {
                            $tr.find('td:nth-child(4) span, td:nth-child(4)').text(updatedUser.click_agent_number);
                            $tr.attr('data-mobile', (updatedUser.click_agent_number || '').toLowerCase());
                            editLink.data('number', updatedUser.click_agent_number);
                            updated = true;
                        }
                    });
                    if (!updated) {
                        window.location.reload();
                    }
                }
            } else {
                lgChannelShowMessage((res && res.message) ? res.message : 'Unable to update channel.');
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Save Changes');
            var res = xhr.responseJSON;
            if (res && res.errors) {
                if (res.errors.user) {
                    $('#channel_user_error').text(res.errors.user[0]).show();
                    $('#channel_user').addClass('has-error');
                }
                if (res.errors.click_agent_number) {
                    lgChannelShowMessage(res.errors.click_agent_number[0]);
                }
            } else {
                lgChannelShowMessage((res && res.message) ? res.message : 'An error occurred while saving. Please try again.');
            }
        }
    });
}

function lgShowMiniToast(msg, type) {
    var toast = $('<div style="position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: #FFFFFF; padding: 12px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 999999; display: flex; align-items: center; gap: 8px;">' +
        (type === 'error'
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>') +
        '<span></span></div>');
    toast.find('span').text(msg);
    $('body').append(toast);
    setTimeout(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Global Backdrop and Escape Key Listeners
// (this partial is printed before jQuery loads, so wait for the page instead of calling $ right away)
document.addEventListener('DOMContentLoaded', function() {
    $('#lgEditChannelModal').on('click', function(e) {
        if ($(e.target).is('#lgEditChannelModal')) {
            lgCloseEditChannelModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#lgEditChannelModal').hasClass('active')) {
            lgCloseEditChannelModal();
        }
    });

    // Enter key submits modal form
    $('#channel_click_agent_number').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            lgSubmitEditChannelModal();
        }
    });

    // Add mode: the user dropdown fills in the agent number, and Enter on it submits too
    $('#channel_user').on('change', function() {
        if (lgChannelMode === 'add') lgChannelAddUserChanged();
    }).on('keydown', function(e) {
        // Enter on the user saves in add mode, and in WebRTC edit (no agent number field to press Enter in)
        if (e.key === 'Enter' && (lgChannelMode === 'add' || !lgChannelUsesAgentNumber())) {
            e.preventDefault();
            lgSubmitEditChannelModal();
        }
    });
});
</script>
