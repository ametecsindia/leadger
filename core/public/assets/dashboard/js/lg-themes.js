/*
 * LeadGER admin themes: the picker next to the bell, saving the choice, and mapping hard-coded colors.
 *
 * Original (default) is the unchanged look: nothing here touches the page while it is active.
 * For Executive Slate / Professional Green / Midnight Dark, the pages' own hard-coded colors are mapped to the
 * theme without editing the pages:
 *   1. CSS rules (same-origin stylesheets and <style> blocks): a themed copy of each color rule is added with the
 *      same selector under html[data-theme="..."], so :hover / .active / .selected states keep working.
 *   2. Inline style="" colors: rewritten in place (originals kept, restored when switching back), including
 *      styles that page scripts add later (MutationObserver).
 * Only neutral surfaces, text greys, borders and the accent blues are mapped. Brand elements (logo, green Make a
 * Call header, Click2Call buttons, the green Calling toast) and status colors are left alone.
 */
(function () {
    'use strict';

    var ALLOWED = ['original', 'slate', 'green', 'dark'];
    var html = document.documentElement;

    // Theme palettes used for mapping (must match lg-themes.css)
    var P = {
        slate: { card: '#FFFFFF', alt: '#F8F9FA', page: '#F5F6F8', border: '#E0E3E8', borderStrong: '#C7CCD4', text: '#1E293B', body: '#334155', muted: '#667080', accent: '#4A6FA5', accentHover: '#3C5C8C', accentSoft: '#EDF1F7', accentBorder: '#C5D2E6' },
        green: { card: '#FFFFFF', alt: '#F6FAF8', page: '#F2F8F5', border: '#D4E6DD', borderStrong: '#B5D0C3', text: '#0F2F23', body: '#23463A', muted: '#557465', accent: '#12805A', accentHover: '#0E6B4B', accentSoft: '#E7F3ED', accentBorder: '#BCDCCB' },
        dark: { card: '#18233B', alt: '#1F2C47', page: '#0F172A', border: '#27344F', borderStrong: '#34435F', text: '#E5EAF5', body: '#CDD5E5', muted: '#93A1BD', accent: '#3B82F6', accentHover: '#60A5FA', accentSoft: '#1A2E52', accentBorder: '#2E4C80' }
    };

    // Brand / layout parts that are themed explicitly in lg-themes.css (or never themed)
    var SKIP_EL = '#aside, .top-header, #cw-call-toast, .cw-card-green-header, .c2c-btn, .cw-btn-call-start, .lg-theme-picker, [data-theme-keep], .swal2-icon';
    var SKIP_SELECTOR = /sidebar|aside|navside|lg-menu|lg-sub|lg-nav|top-header|app-header|header-|rail-cta|cw-call-toast|cw-toast|green-header|c2c-btn|cw-btn-call-start|lg-theme-|notification|toast-success|toast-error|toast-warning|toast-info|swal2-icon/i;

    function current() {
        var t = html.getAttribute('data-theme');
        return ALLOWED.indexOf(t) === -1 ? 'original' : t;
    }

    /* ---------- color helpers ---------- */
    var NAMED = { white: [255, 255, 255, 1], black: [0, 0, 0, 1] };
    function parse(v) {
        if (!v) return null;
        v = String(v).trim().toLowerCase();
        if (NAMED[v]) return NAMED[v].slice();
        var m = /^#([0-9a-f]{3}|[0-9a-f]{6})$/.exec(v);
        if (m) {
            var h = m[1].length === 3 ? m[1].replace(/./g, '$&$&') : m[1];
            return [parseInt(h.substr(0, 2), 16), parseInt(h.substr(2, 2), 16), parseInt(h.substr(4, 2), 16), 1];
        }
        m = /^rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+%?))?\s*\)$/.exec(v);
        if (m) {
            var a = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
            return [+m[1], +m[2], +m[3], a];
        }
        return null;
    }
    function hsl(c) {
        var r = c[0] / 255, g = c[1] / 255, b = c[2] / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, s = 0, h = 0, d = max - min;
        if (d) {
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
            h *= 60;
        }
        return { h: h, s: s, l: l };
    }
    function mix(c, hex, w) {   // w = weight of c (0..1) over hex
        var o = parse(hex);
        return 'rgb(' + [0, 1, 2].map(function (i) { return Math.round(c[i] * w + o[i] * (1 - w)); }).join(', ') + ')';
    }
    var STATUS = {};
    ['#12A66A', '#DC2626', '#D97706', '#1F5FBF'].forEach(function (h) { STATUS[parse(h).slice(0, 3).join(',')] = 1; });
    function isAccent(x) { return x.h >= 205 && x.h <= 235 && x.s > 0.6 && x.l > 0.33 && x.l < 0.66; }
    function isAccentTint(x) { return x.h >= 200 && x.h <= 240 && x.s > 0.45 && x.l > 0.84; }

    /** Theme color for a hard-coded color, or null to leave it. kind: bg | text | border */
    function mapColor(value, kind, theme) {
        var c = parse(value);
        if (!c || c[3] === 0) return null;
        if (STATUS[c.slice(0, 3).join(',')]) return null;      // status colors are the same in every theme
        var t = P[theme], x = hsl(c), neutral = x.s < 0.25 || x.l > 0.96;
        if (theme === 'dark') {
            if (kind === 'bg') {
                if (isAccent(x)) return null;                        // accent buttons keep their blue
                if (neutral && x.l > 0.9) return x.l > 0.985 ? t.card : t.alt;
                if (neutral && x.l > 0.78) return t.borderStrong;
                if (x.l > 0.84) return mix(c, t.card, 0.16);         // light tints (badges, chips) -> dark tint, same hue
                return null;
            }
            if (kind === 'text') {
                if (x.l > 0.88) return null;                         // already light
                if (neutral || (x.l < 0.2 && x.s < 0.75)) return x.l < 0.3 ? t.text : (x.l < 0.5 ? t.body : t.muted);
                if (x.l < 0.6) return mix(c, '#FFFFFF', 0.55);       // colored text -> lighter tint of the same hue
                return null;
            }
            if (kind === 'border') {
                if (x.l > 0.7) return x.s > 0.3 ? mix(c, t.card, 0.35) : t.border;
                return null;
            }
            return null;
        }
        // Executive Slate / Professional Green (light themes): accent family, light neutrals, dark text
        if (isAccent(x)) return x.l <= 0.47 ? t.accentHover : t.accent;
        if (isAccentTint(x)) return kind === 'border' ? t.accentBorder : (x.l > 0.95 ? t.accentSoft : t.accentBorder);
        if (kind === 'bg') return neutral && x.l > 0.9 && x.l <= 0.985 ? t.alt : null;
        if (kind === 'border') return neutral && x.l > 0.8 ? (x.l > 0.86 ? t.border : t.borderStrong) : null;
        if (kind === 'text') {
            if (x.l < 0.2 && x.s < 0.75) return t.text;
            if (neutral && x.l < 0.32) return t.body;
            if (neutral && x.l < 0.55) return t.muted;
        }
        return null;
    }

    var PROPS = [['background-color', 'bg'], ['color', 'text'], ['border-top-color', 'border'], ['border-right-color', 'border'], ['border-bottom-color', 'border'], ['border-left-color', 'border']];

    /* ---------- 1. stylesheet rules ---------- */
    function prefix(selectorText, theme) {
        var scope = 'html[data-theme="' + theme + '"]';
        return selectorText.split(',').map(function (s) {
            s = s.trim();
            if (/^html\b/.test(s)) return s.replace(/^html/, scope);
            if (/^:root\b/.test(s)) return s.replace(/^:root/, scope);
            return scope + ' ' + s;
        }).join(', ');
    }
    function rulesFor(list, theme, out) {
        for (var i = 0; i < list.length; i++) {
            var r = list[i];
            if (r.cssRules && r.media) {               // @media: recurse and wrap
                var inner = [];
                rulesFor(r.cssRules, theme, inner);
                if (inner.length) out.push('@media ' + r.media.mediaText + '{' + inner.join('') + '}');
                continue;
            }
            if (!r.selectorText || !r.style || SKIP_SELECTOR.test(r.selectorText)) continue;
            var decl = [];
            PROPS.forEach(function (p) {
                var v = r.style.getPropertyValue(p[0]);
                if (!v || v.indexOf('var(') > -1) return;
                var m = mapColor(v, p[1], theme);
                if (m) decl.push(p[0] + ':' + m + (r.style.getPropertyPriority(p[0]) === 'important' ? ' !important' : ''));
            });
            if (decl.length) out.push(prefix(r.selectorText, theme) + '{' + decl.join(';') + '}');
        }
    }
    function buildSheet(theme) {
        var el = document.getElementById('lg-theme-rules');
        if (theme === 'original') { if (el) el.textContent = ''; return; }
        var out = [];
        for (var i = 0; i < document.styleSheets.length; i++) {
            var sh = document.styleSheets[i];
            if (sh.ownerNode && sh.ownerNode.id === 'lg-theme-rules') continue;
            if (sh.href && /lg-themes\.css/.test(sh.href)) continue;
            var rules;
            try { rules = sh.cssRules; } catch (e) { continue; }   // cross-origin (fonts): not readable, nothing to map
            if (rules) rulesFor(rules, theme, out);
        }
        if (!el) {
            el = document.createElement('style');
            el.id = 'lg-theme-rules';
            document.head.appendChild(el);
        }
        el.textContent = out.join('\n');
        // lg-themes.css stays last so its explicit theme rules win over the generated copies
        var themeCss = document.querySelector('link[href*="lg-themes.css"]');
        if (themeCss) document.head.appendChild(themeCss);
    }

    /* ---------- 2. inline style="" colors ---------- */
    var mapped = new Set();
    var busy = false;
    function mapEl(el, theme) {
        if (!el.style || !el.getAttribute('style') || (el.closest && el.closest(SKIP_EL))) return;
        var orig = el.__lgOrig || {};
        var changed = false;
        PROPS.forEach(function (p) {
            var prop = p[0], cur = el.style.getPropertyValue(prop);
            if (!cur) return;
            var rec = orig[prop];
            var src = rec && rec.mapped === cur ? rec.value : cur;      // already ours: map from the original
            if (rec && rec.mapped === cur && theme === rec.theme) return;
            var m = mapColor(src, p[1], theme);
            if (!m) { if (rec && rec.mapped !== cur) delete orig[prop]; return; }
            var prio = el.style.getPropertyPriority(prop);
            el.style.setProperty(prop, m, prio);
            // the browser normalises the value (hex -> rgb), so remember what it actually stored
            orig[prop] = { value: src, prio: prio, theme: theme, mapped: el.style.getPropertyValue(prop) };
            changed = true;
        });
        if (changed) { el.__lgOrig = orig; mapped.add(el); }
    }
    function revertAll() {
        mapped.forEach(function (el) {
            var orig = el.__lgOrig || {};
            Object.keys(orig).forEach(function (prop) {
                if (el.style.getPropertyValue(prop) === orig[prop].mapped) el.style.setProperty(prop, orig[prop].value, orig[prop].prio);
            });
            delete el.__lgOrig;
        });
        mapped.clear();
    }
    function mapTree(root, theme) {
        if (root.nodeType !== 1) return;
        if (root.hasAttribute && root.hasAttribute('style')) mapEl(root, theme);
        var list = root.querySelectorAll ? root.querySelectorAll('[style]') : [];
        for (var i = 0; i < list.length; i++) mapEl(list[i], theme);
    }
    var observer = new MutationObserver(function (records) {
        var theme = current();
        if (theme === 'original' || busy) return;
        busy = true;
        records.forEach(function (rec) {
            if (rec.type === 'attributes') mapEl(rec.target, theme);
            else rec.addedNodes.forEach(function (n) { mapTree(n, theme); });
        });
        observer.takeRecords();
        busy = false;
    });

    /* ---------- Chart.js follows the theme ---------- */
    function themeCharts(theme) {
        var Chart = window.Chart;
        if (!Chart) return;
        var dark = theme === 'dark', t = P[theme];
        var text = theme === 'original' ? '#64748B' : t.muted, grid = theme === 'original' ? 'rgba(0,0,0,0.1)' : (dark ? 'rgba(147,161,189,0.18)' : t.border);
        try {
            if (Chart.defaults && Chart.defaults.global) { Chart.defaults.global.defaultFontColor = text; }
            else if (Chart.defaults) { Chart.defaults.color = text; Chart.defaults.borderColor = grid; }
            var inst = Chart.instances ? Object.values(Chart.instances) : [];
            inst.forEach(function (c) { if (c && c.update) c.update(); });
        } catch (e) {}
    }

    /* ---------- apply ---------- */
    function apply(theme, animate) {
        if (ALLOWED.indexOf(theme) === -1) theme = 'original';
        if (animate) html.classList.add('lg-theme-anim');
        busy = true;
        revertAll();
        html.setAttribute('data-theme', theme);
        html.style.colorScheme = theme === 'dark' ? 'dark' : 'light';
        buildSheet(theme);
        if (theme !== 'original') mapTree(document.body, theme);
        observer.takeRecords();
        busy = false;
        themeCharts(theme);
        syncPicker(theme);
        if (animate) setTimeout(function () { html.classList.remove('lg-theme-anim'); }, 220);
    }

    /* ---------- saving (per user, quietly retried) ---------- */
    var retryTimer = null;
    function save(theme, attempt) {
        attempt = attempt || 0;
        try { localStorage.setItem('lg_theme', theme); localStorage.setItem('lg_theme_pending', theme); } catch (e) {}
        clearTimeout(retryTimer);
        var token = document.querySelector('meta[name="csrf-token"]');
        var url = (document.querySelector('base') ? document.querySelector('base').getAttribute('href').replace(/\/+$/, '') : '/admin') + '/me/theme';
        fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ theme: theme })
        }).then(function (res) {
            // an expired session redirects to the login page (200 HTML), so only a JSON success counts as saved
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        }).then(function (data) {
            if (!data || data.success !== true) throw new Error('not saved');
            try { if (localStorage.getItem('lg_theme_pending') === theme) localStorage.removeItem('lg_theme_pending'); } catch (e) {}
        }).catch(function () {
            // keep the theme applied; try again later (5s, 15s, 45s, then on the next page load)
            if (attempt < 3) retryTimer = setTimeout(function () { save(theme, attempt + 1); }, 5000 * Math.pow(3, attempt));
        });
    }

    /* ---------- picker ---------- */
    var btn, menu;
    function options() { return menu ? Array.prototype.slice.call(menu.querySelectorAll('.lg-theme-opt, .lg-theme-reset')) : []; }
    function syncPicker(theme) {
        if (!menu) return;
        menu.querySelectorAll('.lg-theme-opt').forEach(function (o) {
            o.setAttribute('aria-checked', o.getAttribute('data-theme-value') === theme ? 'true' : 'false');
        });
    }
    function openMenu() {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        var on = menu.querySelector('.lg-theme-opt[aria-checked="true"]') || options()[0];
        if (on) on.focus();
    }
    function closeMenu(focusBtn) {
        if (!menu || menu.hidden) return;
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        if (focusBtn) btn.focus();
    }
    function choose(theme) {
        closeMenu(true);
        if (theme === current()) return;
        apply(theme, true);
        save(theme);
    }
    function initPicker() {
        btn = document.getElementById('lgThemeBtn');
        menu = document.getElementById('lgThemeMenu');
        if (!btn || !menu) return;
        syncPicker(current());
        btn.addEventListener('click', function (e) { e.stopPropagation(); menu.hidden ? openMenu() : closeMenu(false); });
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openMenu(); }
        });
        menu.addEventListener('click', function (e) {
            var o = e.target.closest('.lg-theme-opt');
            if (o) choose(o.getAttribute('data-theme-value'));
            else if (e.target.closest('#lgThemeReset')) choose('original');
            e.stopPropagation();
        });
        menu.addEventListener('keydown', function (e) {
            var list = options(), i = list.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); list[(i + 1) % list.length].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); list[(i - 1 + list.length) % list.length].focus(); }
            else if (e.key === 'Home') { e.preventDefault(); list[0].focus(); }
            else if (e.key === 'End') { e.preventDefault(); list[list.length - 1].focus(); }
            else if (e.key === 'Escape') { e.preventDefault(); closeMenu(true); }
            else if (e.key === 'Tab') { closeMenu(false); }
        });
        document.addEventListener('click', function (e) { if (!e.target.closest('#lgThemePicker')) closeMenu(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(true); });
    }

    function init() {
        initPicker();
        var theme = current();
        if (theme !== 'original') apply(theme, false);   // no transition on load
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });
        // A choice that did not reach the server last time
        try {
            var pending = btn ? localStorage.getItem('lg_theme_pending') : null;   // signed-out pages have no picker
            if (pending && ALLOWED.indexOf(pending) !== -1) save(pending, 1);
        } catch (e) {}
        // Same-origin stylesheets added later (page styles pushed at the end): refresh the generated rules once
        window.addEventListener('load', function () { if (current() !== 'original') buildSheet(current()); });
    }

    window.lgTheme = { apply: function (t) { apply(t, true); save(t); }, current: current };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
