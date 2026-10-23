@extends('dashboard.layouts.master')
@section('title', 'Companies - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
<style>
.co-page .cr-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.co-page .cr-header-icon { width:44px; height:44px; border-radius:12px; }
.co-add-btn { height:38px; padding:0 16px; border-radius:8px; background:var(--lg-accent); border:1px solid var(--lg-accent); color:#fff !important; font-size:14px; font-weight:600; display:inline-flex; align-items:center; gap:8px; text-decoration:none !important; }
.co-add-btn:hover { filter:brightness(0.95); }
.co-note { margin:0 0 16px; padding:12px 16px; border-radius:10px; background:#FFF7E6; border:1px solid #FCD9A6; color:#7A4B00; font-size:13px; line-height:1.5; }
.co-search { height:38px; width:260px; max-width:100%; border:1px solid #CBD5E1; border-radius:8px; padding:0 12px; font-size:13.5px; }
.co-search:focus { outline:2px solid var(--lg-accent); outline-offset:1px; border-color:transparent; }
.co-table td, .co-table th { vertical-align:middle; }
.co-name { font-weight:700; color:#0F172A; }
.co-host { font-size:12.5px; color:var(--lg-accent); text-decoration:none; }
.co-host:hover { text-decoration:underline; }
.co-db { font-family:monospace; font-size:12.5px; color:#334155; }
.co-db small { display:block; color:#94A3B8; font-family:inherit; }
.co-num { font-variant-numeric:tabular-nums; color:#0F172A; font-weight:600; }
.co-muted { color:#94A3B8; font-size:12.5px; }
.co-db-state { font-size:12.5px; font-weight:600; white-space:nowrap; }
.co-db-state.ok { color:#0E8C59; }
.co-db-state.bad { color:#C62828; white-space:normal; max-width:220px; display:inline-block; }
.co-db-state.wait { color:#94A3B8; }
.co-actions { display:flex; gap:6px; flex-wrap:wrap; }
.co-btn { height:30px; padding:0 10px; border-radius:7px; border:1px solid #CBD5E1; background:#fff; color:#334155 !important; font-size:12.5px; font-weight:600; display:inline-flex; align-items:center; gap:5px; cursor:pointer; text-decoration:none !important; }
.co-btn:hover { border-color:var(--lg-accent); color:var(--lg-accent) !important; }
.co-btn:focus-visible, .co-add-btn:focus-visible { outline:2px solid var(--lg-accent); outline-offset:2px; }
.co-empty { padding:40px 16px !important; text-align:center; color:#64748B; }
</style>
@endpush

@section('content')
<div class="cr-canvas co-page">
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="3" y1="13" x2="21" y2="13"/></svg>
            </div>
            <div>
                <h1 class="cr-header-title">Companies</h1>
                <div class="cr-header-sub">{{ $companies->count() }} {{ \Illuminate\Support\Str::plural('company', $companies->count()) }} &middot; each with its own database &middot; list: {{ $registry }}</div>
            </div>
        </div>
        <a href="{{ route('companiesCreate') }}" class="co-add-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add company
        </a>
    </div>

    @if ($manageOnly)
        <p class="co-note">
            These companies are served by another LeadGER installation. Changes here update the shared companies list
            ({{ $registry }}).
        </p>
    @endif

    <div class="cr-table-card">
        <div class="cr-table-head">
            <div>
                <div class="cr-table-title">All companies</div>
                <div class="cr-table-sub">Users, leads and calls are read live from each company's own database</div>
            </div>
            <div class="cr-table-tools">
                <input type="search" class="co-search" id="coSearch" placeholder="Search companies" aria-label="Search companies">
            </div>
        </div>
        <div class="cr-table-wrap">
            <table class="cr-table co-table" id="coTable">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Database</th>
                        <th>Users</th>
                        <th>Leads</th>
                        <th>Calls</th>
                        <th>Last call</th>
                        <th>Updates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($companies as $c)
                    <tr data-check="{{ route('companiesCheck', $c->id) }}"
                        data-search="{{ strtolower($c->subdomain . ' ' . $c->db_name) }}">
                        <td>
                            <div class="co-name">{{ $c->shortName() }}</div>
                            <a class="co-host" href="https://{{ $c->subdomain }}" target="_blank" rel="noopener">{{ $c->subdomain }}</a>
                        </td>
                        <td class="co-db">{{ $c->db_name }}<small>{{ $c->db_host }}</small>
                            <span class="co-db-state wait" data-field="state">Checking&hellip;</span></td>
                        <td class="co-num" data-field="users">&ndash;</td>
                        <td class="co-num" data-field="leads">&ndash;</td>
                        <td class="co-num" data-field="calls">&ndash;</td>
                        <td class="co-muted" data-field="last_call">&ndash;</td>
                        <td class="co-muted" data-field="pending">&ndash;</td>
                        <td>
                            <div class="co-actions">
                                <a class="co-btn" href="{{ route('companiesEdit', $c->id) }}">Edit</a>
                                <a class="co-btn" href="https://{{ $c->subdomain }}" target="_blank" rel="noopener">Open</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="co-empty">No companies yet. Use "Add company" to register the first one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
(function () {
    'use strict';
    var rows = Array.prototype.slice.call(document.querySelectorAll('#coTable tbody tr[data-check]'));
    function fmt(n) { return n === null || n === undefined ? '–' : Number(n).toLocaleString('en-IN'); }
    function set(row, field, text, cls) {
        var el = row.querySelector('[data-field="' + field + '"]');
        if (!el) return;
        el.textContent = text;
        if (cls !== undefined) el.className = el.className.replace(/\b(ok|bad|wait)\b/g, '').trim() + ' ' + cls;
    }
    function check(row) {
        return fetch(row.getAttribute('data-check'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { set(row, 'state', 'Not reachable: ' + (d.error || 'error'), 'bad'); return; }
                set(row, 'state', 'Connected', 'ok');
                set(row, 'users', fmt(d.users)); set(row, 'leads', fmt(d.leads)); set(row, 'calls', fmt(d.calls));
                set(row, 'last_call', d.last_call ? new Date(d.last_call.replace(' ', 'T')).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : 'never');
                set(row, 'pending', d.pending_migrations === null ? 'no migrations table' : (d.pending_migrations === 0 ? 'Up to date' : d.pending_migrations + ' pending'));
            })
            .catch(function () { set(row, 'state', 'Check failed', 'bad'); });
    }
    // four at a time: each check opens a connection to that company's database
    var queue = rows.slice();
    function next() { var row = queue.shift(); if (row) check(row).then(next); }
    for (var i = 0; i < 4; i++) next();

    document.getElementById('coSearch').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        rows.forEach(function (row) { row.style.display = !q || row.getAttribute('data-search').indexOf(q) !== -1 ? '' : 'none'; });
    });
})();
</script>
@endpush
