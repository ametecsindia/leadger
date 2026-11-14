{{-- Like SmartDCM: the Print Screen key does not copy the page (the clipboard is emptied) and the user sees
     "Could not take Screenshot". Off with SCREENSHOT_GUARD=false in .env. A browser cannot stop every capture
     (Snipping Tool shortcuts, other apps, phone cameras); printing (Payout, Teams pages) is left as it is. --}}
@if(config('app.screenshot_guard', true))
<script>
(function () {
    function emptyClipboard() {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(fallback);
            } else {
                fallback();
            }
        } catch (e) { fallback(); }
    }
    function fallback() {
        try {
            var t = document.createElement('textarea');
            t.value = ' ';
            t.setAttribute('readonly', '');
            t.style.cssText = 'position: fixed; left: -9999px; top: 0;';
            document.body.appendChild(t);
            t.select();
            document.execCommand('copy');
            t.remove();
        } catch (e) {}
    }
    function isPrintScreen(e) { return e.key === 'PrintScreen' || e.keyCode === 44; }
    var warned = 0;
    function block(e) {
        if (!isPrintScreen(e)) return;
        emptyClipboard();
        if (e.type === 'keyup' && Date.now() - warned > 1000) {   // keydown + keyup: one message
            warned = Date.now();
            setTimeout(function () { emptyClipboard(); alert('Could not take Screenshot'); }, 0);
        }
    }
    document.addEventListener('keydown', block, true);
    document.addEventListener('keyup', block, true);
})();
</script>
@endif
