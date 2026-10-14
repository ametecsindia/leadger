{{--
    Shared Lead Details popup (every dashboard page).
    - Any element <a class="lg-lead-id-link" data-lead-id="123">123</a> opens it (Helper::leadIdLink() renders one).
    - lgOpenLead(id) opens it from code; pages without their own leadDetail() get leadDetail = lgOpenLead.
    Content comes from the existing endpoint leadDetail (/admin/leads/{id}/view, AJAX -> leads/modal.blade.php),
    which applies the lead access rules (403) and returns 404 for unknown ids.
--}}
<style>
.lg-lead-id-link,
a.lg-lead-id-link:not(.btn) {
    color: var(--lg-accent, #1769E8) !important;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}
a.lg-lead-id-link:not(.lg-lead-id-pill):hover { text-decoration: underline; }
/* Lead ID pill (same look as Call Recordings) */
.lg-lead-id-pill {
    background: #EFF6FF;
    color: var(--lg-accent, #1769E8) !important;
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
.lg-lead-id-pill:hover { background: #DBEAFE; color: #0F52CC !important; }
</style>

<div id="lg-lead-popup" class="modal fade lead-details-modal" tabindex="-1" role="dialog" aria-label="Lead details" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content lg-lead-popup-content"></div>
    </div>
</div>

<script type="text/javascript">
(function () {
    function esc(t) { return $('<div>').text(t == null ? '' : String(t)).html(); }

    function box(inner) {
        return '<div style="padding: 48px 24px; text-align: center; background: #FFFFFF; border-radius: 16px;">' + inner + '</div>';
    }

    window.lgOpenLead = function (id) {
        id = String(id == null ? '' : id).trim();
        if (!/^\d+$/.test(id)) return;   // Lead IDs are digits; never guess a lead from other text
        var $modal = $('#lg-lead-popup');
        if (!$modal.parent().is('body')) $modal.appendTo('body');
        var $content = $modal.find('.lg-lead-popup-content');
        $content.html(box(
            '<div class="spinner-border text-primary" role="status" style="width: 2.6rem; height: 2.6rem;"><span class="sr-only">Loading...</span></div>' +
            '<div style="margin-top: 14px; font-size: 15px; font-weight: 600; color: #0F172A;">Loading lead ' + esc(id) + '...</div>'
        ));
        $modal.modal('show');
        $.ajax({
            type: 'GET',
            url: "{{ route('leadDetail', [':id']) }}".replace(':id', encodeURIComponent(id)),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'html'
        }).done(function (html) {
            $content.html(html);
            if ($.fn.select2) $content.find('select').select2();
        }).fail(function (xhr) {
            var msg = xhr.status === 403 ? 'You do not have permission to view lead ' + id + '.'
                    : xhr.status === 404 ? 'Lead ' + id + ' was not found.'
                    : xhr.status === 401 || xhr.status === 419 ? 'Your session has expired. Refresh the page and sign in again.'
                    : 'Could not load lead ' + id + ' (error ' + xhr.status + '). Please try again.';
            $content.html(box(
                '<div style="width: 50px; height: 50px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 22px;"><i class="fa fa-exclamation-triangle"></i></div>' +
                '<h5 style="font-weight: 700; color: #0F172A; margin-bottom: 8px;">Unable to load lead</h5>' +
                '<p style="color: #64748B; font-size: 13.5px; margin: 0 auto 18px auto;">' + esc(msg) + '</p>' +
                '<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>'
            ));
        });
    };

    // Any Lead ID link on any page (including rows added later by DataTables / AJAX)
    $(document).on('click', '.lg-lead-id-link[data-lead-id]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        lgOpenLead($(this).attr('data-lead-id'));
    });

    // Pages that call leadDetail(id) without defining it use the shared popup
    $(function () {
        if (typeof window.leadDetail !== 'function') window.leadDetail = window.lgOpenLead;
    });
})();
</script>
