{{--
    Row actions (the ⋮ button in a table's Options column), shared by Segments, Sources and Products & Services.

    Markup per row, next to the ⋮ button:
        <button type="button" class="..." onclick="lgRowMenu.toggle(event, this)" aria-haspopup="true" aria-expanded="false">⋮</button>
        <div class="lg-action-dropdown-menu"> <div class="lg-action-dropdown-item">Edit</div> <div class="lg-action-dropdown-item danger">Delete</div> </div>

    While open, the menu is moved to <body> with position: fixed, so the table card's overflow cannot hide it; it opens
    upward when there is no room below. One menu at a time; closed by a click outside, an item, Escape, scroll or resize.

    lgRowMenu.confirmDelete({ title, question, name, url, onDone }) asks, then sends DELETE to url and reports the result.
--}}
<style>
.lg-action-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
    min-width: 140px;
    z-index: 100;
    display: none;
    padding: 6px 0;
    text-align: left;
}
.lg-action-dropdown-menu.show { display: block; }
.lg-action-dropdown-menu.lg-floating { position: fixed; margin: 0; z-index: 1090; }
.lg-action-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s ease;
    white-space: nowrap;
}
.lg-action-dropdown-item:hover, .lg-action-dropdown-item:focus-visible { background: #F8FAFC; color: #0F172A; outline: none; }
.lg-action-dropdown-item.danger { color: #EF4444; background: transparent; }   /* app.css .danger paints a background */
.lg-action-dropdown-item.danger:hover, .lg-action-dropdown-item.danger:focus-visible { background: #FEF2F2; color: #DC2626; }

/* Delete confirmation: the Segments page's popup */
#lgRowDeleteModal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 1100; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; visibility: hidden; transition: all 0.25s ease-in-out; }
#lgRowDeleteModal.active { opacity: 1; visibility: visible; }
#lgRowDeleteModal .lgrd-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.25); width: 100%; max-width: 420px; transform: scale(0.95) translateY(10px); transition: all 0.25s ease-in-out; overflow: hidden; box-sizing: border-box; }
#lgRowDeleteModal.active .lgrd-card { transform: scale(1) translateY(0); }
#lgRowDeleteModal .lgrd-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #F1F5F9; background: #FFFFFF; }
#lgRowDeleteModal .lgrd-title { font-size: 18px; font-weight: 700; color: #0F172A; margin: 0; }
#lgRowDeleteModal .lgrd-close { width: 32px; height: 32px; border-radius: 8px; border: none; background: #F8FAFC; color: #64748B; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s ease; }
#lgRowDeleteModal .lgrd-close:hover { background: #F1F5F9; color: #0F172A; }
#lgRowDeleteModal .lgrd-body { text-align: center; padding: 28px 24px; }
#lgRowDeleteModal .lgrd-icon { width: 48px; height: 48px; border-radius: 12px; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
#lgRowDeleteModal .lgrd-question { font-size: 15px; font-weight: 600; color: #0F172A; margin: 0 0 6px 0; }
#lgRowDeleteModal .lgrd-name { font-size: 13.5px; color: #64748B; margin: 0; word-break: break-word; }
#lgRowDeleteModal .lgrd-error { display: none; margin: 14px 0 0; padding: 10px 12px; border-radius: 10px; background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C; font-size: 13px; text-align: left; }
#lgRowDeleteModal .lgrd-footer { display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 24px; background: #F8FAFC; border-top: 1px solid #F1F5F9; }
#lgRowDeleteModal .lgrd-cancel { height: 40px; padding: 0 18px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 13.5px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s ease; }
#lgRowDeleteModal .lgrd-cancel:hover { background: #F1F5F9; color: #0F172A; border-color: #94A3B8; }
#lgRowDeleteModal .lgrd-danger { height: 40px; padding: 0 20px; background: #DC2626; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 600; color: #FFFFFF; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25); }
#lgRowDeleteModal .lgrd-danger:hover { background: #B91C1C; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); }
#lgRowDeleteModal .lgrd-danger:disabled { opacity: 0.7; cursor: wait; }
</style>

<div id="lgRowDeleteModal" role="dialog" aria-modal="true" aria-labelledby="lgRowDeleteTitle">
    <div class="lgrd-card">
        <div class="lgrd-header">
            <h3 class="lgrd-title" id="lgRowDeleteTitle">Delete</h3>
            <button type="button" class="lgrd-close" data-lgrd-cancel aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="lgrd-body">
            <div class="lgrd-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <p class="lgrd-question" id="lgRowDeleteQuestion"></p>
            <p class="lgrd-name" id="lgRowDeleteName"></p>
            <p class="lgrd-error" id="lgRowDeleteError" role="alert"></p>
        </div>
        <div class="lgrd-footer">
            <button type="button" class="lgrd-cancel" data-lgrd-cancel>Cancel</button>
            <button type="button" class="lgrd-danger" id="lgRowDeleteConfirm">Delete</button>
        </div>
    </div>
</div>

<script>
window.lgRowMenu = window.lgRowMenu || (function () {
    'use strict';
    var open = null;   // { menu, parent, next, btn }

    function close() {
        if (!open) return;
        var o = open;
        open = null;
        o.menu.classList.remove('show', 'lg-floating');
        o.menu.style.top = o.menu.style.left = '';
        // back into its row (the row may have been redrawn meanwhile)
        if (o.parent && o.parent.isConnected) o.parent.insertBefore(o.menu, o.next && o.next.parentNode === o.parent ? o.next : null);
        else o.menu.remove();
        o.btn.setAttribute('aria-expanded', 'false');
    }

    function toggle(event, btn) {
        event.stopPropagation();
        if (open && open.btn === btn) { close(); return; }
        close();
        var menu = btn.parentNode.querySelector('.lg-action-dropdown-menu');
        if (!menu) return;
        open = { menu: menu, parent: menu.parentNode, next: menu.nextSibling, btn: btn };
        document.body.appendChild(menu);
        menu.classList.add('lg-floating', 'show');
        place();
        btn.setAttribute('aria-expanded', 'true');
        menu.querySelectorAll('.lg-action-dropdown-item').forEach(function (i) { i.tabIndex = 0; i.setAttribute('role', 'menuitem'); });
    }

    // Next to its button: below it, or above it when there is not enough room below
    function place() {
        var menu = open.menu, r = open.btn.getBoundingClientRect(), w = menu.offsetWidth, h = menu.offsetHeight;
        var below = window.innerHeight - r.bottom, up = below < h + 12 && r.top > below;
        menu.style.top = Math.max(8, up ? r.top - h - 4 : r.bottom + 4) + 'px';
        menu.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
    }

    // a click on an item runs the item first (its own onclick), then reaches here and closes the menu
    document.addEventListener('click', function (e) {
        if (open && !open.btn.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { if (open) { var b = open.btn; close(); b.focus(); } closeDelete(); }
        if ((e.key === 'Enter' || e.key === ' ') && open && e.target.classList && e.target.classList.contains('lg-action-dropdown-item')) {
            e.preventDefault();
            e.target.click();
        }
    });
    window.addEventListener('resize', close);
    // Scrolling (the page or the table) moves the menu with its button; it closes once the button is out of view
    window.addEventListener('scroll', function () {
        if (!open) return;
        if (!open.btn.isConnected || !visible(open.btn)) close(); else place();
    }, true);

    // Is the button still in view: inside the window and inside every scrolling box around it (the table card)?
    function visible(el) {
        var r = el.getBoundingClientRect(), top = Math.max(0, r.top), bottom = Math.min(window.innerHeight, r.bottom);
        for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
            var oy = getComputedStyle(p).overflowY;
            if (oy === 'auto' || oy === 'scroll' || oy === 'hidden') {
                var pr = p.getBoundingClientRect();
                top = Math.max(top, pr.top);
                bottom = Math.min(bottom, pr.bottom);
            }
        }
        return bottom - top > 4;
    }

    /* ---- delete confirmation ---- */
    var pending = null;
    var modal = document.getElementById('lgRowDeleteModal');
    var confirmBtn = document.getElementById('lgRowDeleteConfirm');
    var errorEl = document.getElementById('lgRowDeleteError');

    function closeDelete() {
        if (!modal.classList.contains('active')) return;
        modal.classList.remove('active');
        pending = null;
    }

    function confirmDelete(opts) {
        close();
        pending = opts;
        document.getElementById('lgRowDeleteTitle').textContent = opts.title || 'Delete';
        document.getElementById('lgRowDeleteQuestion').textContent = opts.question || 'Are you sure you want to delete this?';
        document.getElementById('lgRowDeleteName').textContent = opts.name || '';
        errorEl.style.display = 'none';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Delete';
        modal.classList.add('active');
        setTimeout(function () { confirmBtn.focus(); }, 50);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target.closest('[data-lgrd-cancel]')) closeDelete();
    });
    confirmBtn.addEventListener('click', function () {
        if (!pending) return;
        var opts = pending;
        var token = document.querySelector('meta[name="csrf-token"]');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';
        fetch(opts.url, {
            method: 'DELETE', credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '{{ csrf_token() }}' }
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
            if (!res.ok || !res.d.success) {
                // e.g. still used by campaigns / leads, or no delete permission: the record stays, the reason is shown
                errorEl.textContent = res.d.message || 'The record could not be deleted.';
                errorEl.style.display = 'block';
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';
                return;
            }
            closeDelete();
            if (typeof toastr !== 'undefined') toastr.success(res.d.message || 'Deleted successfully.');
            if (opts.onDone) opts.onDone(res.d);
        }).catch(function () {
            errorEl.textContent = 'A network error occurred. Please try again.';
            errorEl.style.display = 'block';
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Delete';
        });
    });

    return { toggle: toggle, close: close, confirmDelete: confirmDelete };
})();
</script>
