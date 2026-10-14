/**
 * LeadGER - sticky table headings + fixed first column, for every data table in the admin.
 *
 * Headings are already position:sticky (leadger-theme.css, "GLOBAL FROZEN / STICKY TABLE HEADERS").
 * This script completes it:
 *   1. Finds each table's real scroll box (the innermost wrapper that scrolls) and stops OUTER
 *      wrappers (cards etc.) from also scrolling - nested scroll boxes caused duplicate scrollbars
 *      and headings that scrolled away with the outer box.
 *   2. Fixes the first meaningful column on the left (Lead Name, Agent, Campaign Name, ...).
 *      A leading checkbox / S.No / # column is fixed together with it.
 *   3. Re-applies after DataTables draws, column resizing, window resizing and AJAX-loaded content.
 *
 * Opt out per table with: <table data-lg-sticky="off">  (or "header" to keep only the sticky heading)
 * DataTables FixedColumns tables keep their own fixed columns and are left alone.
 */
(function () {
    'use strict';

    var LEADING = /^(#|s\.?\s*no\.?|sl\.?\s*no\.?|sr\.?\s*no\.?|no\.?|sno)$/i;
    var SKIP_SELECTOR = '.datepicker, .ui-datepicker, .flatpickr-calendar, .daterangepicker, .fc, .mce-container, .note-editor';
    var MAX_FIXED_SHARE = 0.45;   // never fix columns covering more than 45% of the visible table area

    function isScrollBox(el) {
        if (!el || el === document.body || el === document.documentElement) return false;
        var cs = getComputedStyle(el);
        return /(auto|scroll)/.test(cs.overflowX + ' ' + cs.overflowY);
    }

    /** overflow:hidden also makes an element a scroll box for sticky cells (one that never scrolls),
        which silently disables sticky headings / columns. overflow:clip clips the same but does not. */
    function isHidden(el) {
        var cs = getComputedStyle(el);
        return cs.overflowX === 'hidden' || cs.overflowY === 'hidden';
    }

    function scrollBoxOf(table) {
        if (isHidden(table)) table.classList.add('lg-sticky-clip');
        // DataTables scrollX/scrollY: body table lives in .dataTables_scrollBody
        var dtBody = table.closest('.dataTables_scrollBody');
        for (var el = table.parentElement; el && el !== document.body; el = el.parentElement) {
            if (el.classList.contains('modal') || el.classList.contains('modal-dialog')) return null;
            if (dtBody && el === dtBody) return dtBody;
            if (isScrollBox(el)) return el;
            if (isHidden(el)) el.classList.add('lg-sticky-clip');
        }
        return null;
    }

    var boxOfTable = new WeakMap();

    /** Outer wrappers (cards, DataTables rows, ...) that also scroll: let only the inner box scroll. */
    function flattenOuterScrollBoxes(box) {
        for (var el = box.parentElement; el && el !== document.body; el = el.parentElement) {
            if (el.id === 'content' || el.id === 'view' || el.classList.contains('app-body') ||
                el.classList.contains('modal-body') || el.classList.contains('modal-content') || el.classList.contains('modal')) break;
            if (isScrollBox(el) && !el.classList.contains('dataTables_scrollHead')) {
                el.classList.add('lg-sticky-outer');
            }
        }
    }

    function headerRowOf(table) {
        var thead = table.tHead;
        if (!thead || thead.rows.length !== 1) return null;   // multi-row headings: keep heading sticky only
        return thead.rows[0];
    }

    /** How many leading columns to fix: leading checkbox / S.No columns + the first meaningful one. */
    function leadingCount(headerRow) {
        var cells = headerRow.cells, n = 0;
        for (var i = 0; i < cells.length && i < 3; i++) {
            if (cells[i].colSpan > 1) return 0;
            var txt = (cells[i].textContent || '').replace(/\s+/g, ' ').trim();
            var onlyCheckbox = cells[i].querySelector('input[type=checkbox]') && txt === '';
            if (txt === '' || onlyCheckbox || LEADING.test(txt)) { n++; continue; }
            return n + 1;
        }
        return 0;
    }

    function solidBackground(cell) {
        for (var el = cell; el && el !== document.documentElement; el = el.parentElement) {
            var bg = getComputedStyle(el).backgroundColor;
            if (bg && bg !== 'transparent' && !/rgba\(\s*\d+,\s*\d+,\s*\d+,\s*0\s*\)/.test(bg)) return bg;
        }
        return '#FFFFFF';
    }

    function clearFixed(table) {
        table.querySelectorAll('.lg-sticky-col').forEach(function (c) {
            c.classList.remove('lg-sticky-col', 'lg-sticky-col-last');
            c.style.removeProperty('--lg-sticky-left');
            c.style.removeProperty('--lg-sticky-bg');
        });
    }

    function markColumns(table, count, lefts) {
        var rows = table.rows;
        for (var r = 0; r < rows.length; r++) {
            var cells = rows[r].cells;
            if (!cells.length || (cells[0].colSpan > 1 && rows[r].parentElement.tagName !== 'THEAD')) continue;
            for (var i = 0; i < count && i < cells.length; i++) {
                if (cells[i].colSpan > 1) break;
                var c = cells[i];
                c.classList.add('lg-sticky-col');
                c.classList.toggle('lg-sticky-col-last', i === count - 1);
                c.style.setProperty('--lg-sticky-left', lefts[i] + 'px');
                if (c.tagName === 'TD') {
                    c.style.removeProperty('--lg-sticky-bg');
                    var bg = solidBackground(c);
                    c.style.setProperty('--lg-sticky-bg', bg);
                }
            }
        }
    }

    /** DataTables scroll mode draws the heading as a separate table; its column widths drift a few
        pixels from the body. Size each heading cell exactly like the body cell below it. */
    function syncHeadingWidths(headTable, bodyTable, headerRow) {
        var row = bodyTable.tBodies[0] && Array.prototype.find.call(bodyTable.tBodies[0].rows, function (r) {
            return r.cells.length === headerRow.cells.length;
        });
        if (!row) return;
        headTable.style.width = bodyTable.getBoundingClientRect().width + 'px';
        for (var i = 0; i < headerRow.cells.length; i++) {
            var w = row.cells[i].getBoundingClientRect().width + 'px', th = headerRow.cells[i];
            if (th.style.width === w && th.style.boxSizing === 'border-box') continue;
            th.style.boxSizing = 'border-box';
            th.style.width = th.style.minWidth = th.style.maxWidth = w;
        }
    }

    function apply(table) {
        if (!table || !table.isConnected || table.closest(SKIP_SELECTOR) || table.parentElement.closest('table')) return;
        var mode = table.getAttribute('data-lg-sticky');
        if (mode === 'off') return;
        if (!table.tHead || !table.tHead.rows.length) return;

        // DataTables scroll mode: the body table has a hidden header copy; work on the visible header table
        var dtScroll = table.closest('.dataTables_scroll');
        var isDtBodyTable = !!table.closest('.dataTables_scrollBody');
        if (table.closest('.dataTables_scrollHead')) return;   // handled together with its body table

        var box = scrollBoxOf(table);
        var oldBox = boxOfTable.get(table);
        if (oldBox && oldBox !== box) oldBox.classList.remove('lg-sticky-box', 'lg-scrolled-x');   // table was moved (e.g. by DataTables)
        boxOfTable.set(table, box);
        if (box) {
            box.classList.add('lg-sticky-box');
            flattenOuterScrollBoxes(box);
        }
        if (mode === 'header') return;

        // FixedColumns extension already fixes columns on these tables
        var wrapper = table.closest('.dataTables_wrapper');
        if (wrapper && (wrapper.querySelector('.dtfc-fixed-left, .DTFC_LeftWrapper') || wrapper.classList.contains('DTFC_ScrollWrapper'))) return;

        var headTable = isDtBodyTable && dtScroll ? dtScroll.querySelector('.dataTables_scrollHead table') : null;
        if (headTable) {
            // the heading copy sits in .dataTables_scrollHead (scrolled by DataTables): unclip everything inside it
            var head = dtScroll.querySelector('.dataTables_scrollHead');
            for (var h = headTable; h && h !== head; h = h.parentElement) {
                if (isHidden(h)) h.classList.add('lg-sticky-clip');
            }
        }
        var headerRow = headerRowOf(headTable || table);
        clearFixed(table);
        if (headTable) clearFixed(headTable);
        if (!headerRow) return;
        if (headTable) syncHeadingWidths(headTable, table, headerRow);

        var count = leadingCount(headerRow);
        if (!count) return;

        // Measure from the first data row when present (header copies in DataTables can be 0-height)
        var measureRow = (table.tBodies[0] && table.tBodies[0].rows[0]) || headerRow;
        if (measureRow.cells.length < headerRow.cells.length && measureRow !== headerRow) measureRow = headerRow;
        var lefts = [], acc = 0;
        for (var i = 0; i < count; i++) {
            var cell = measureRow.cells[i] || headerRow.cells[i];
            if (!cell) return;
            lefts.push(acc);
            acc += cell.getBoundingClientRect().width;
        }

        // Only fix columns when the table can actually scroll sideways, and never over half the view
        var viewWidth = box ? box.clientWidth : document.documentElement.clientWidth;
        var tableWidth = table.getBoundingClientRect().width;
        if (tableWidth <= viewWidth + 1 || acc > viewWidth * MAX_FIXED_SHARE) return;

        markColumns(table, count, lefts);
        if (headTable) markColumns(headTable, count, lefts);
        table.classList.add('lg-sticky-first');
    }

    function onBoxScroll(e) {
        var box = e.target;
        if (box && box.classList && box.classList.contains('lg-sticky-box')) {
            box.classList.toggle('lg-scrolled-x', box.scrollLeft > 0);
            var head = box.parentElement && box.parentElement.querySelector(':scope > .dataTables_scrollHead');
            if (head) head.classList.toggle('lg-scrolled-x', box.scrollLeft > 0);
        }
    }

    var queued = null;
    function applyAll() {
        queued = null;
        document.querySelectorAll('#content table, .modal table, .app-body table, body > table').forEach(apply);
    }
    function schedule() {
        if (queued) return;
        queued = (window.requestAnimationFrame || setTimeout)(applyAll);
    }

    function start() {
        applyAll();
        document.addEventListener('scroll', onBoxScroll, true);
        window.addEventListener('resize', schedule);

        if (window.jQuery) {
            // DataTables redraws (paging, sorting, search, AJAX) and column re-measuring
            jQuery(document).on('draw.dt init.dt column-sizing.dt responsive-resize.dt', schedule);
            jQuery(document).on('shown.bs.modal shown.bs.tab shown.bs.collapse', schedule);
        }

        // Tables or rows added later (AJAX partials, modals, tabs)
        if (window.MutationObserver) {
            new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    var t = muts[i].target;
                    if (t.nodeType === 1 && (t.closest('table') || t.querySelector && t.querySelector('table'))) { schedule(); return; }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
        setTimeout(schedule, 600);   // after fonts / late layout
    }

    window.LeadgerStickyTables = { refresh: schedule, apply: apply };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
