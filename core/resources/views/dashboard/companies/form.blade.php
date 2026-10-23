@extends('dashboard.layouts.master')
@section('title', ($company->exists ? 'Edit company' : 'Add company') . ' - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
<style>
.co-form-page .cr-header-icon { width:44px; height:44px; border-radius:12px; }
.co-card { background:#fff; border:1px solid #E2E8F0; border-radius:14px; padding:22px 24px; margin-bottom:18px; max-width:880px; }
.co-card h2 { font-size:15px; font-weight:700; color:#0F172A; margin:0 0 4px; }
.co-card .co-card-sub { font-size:12.5px; color:#64748B; margin:0 0 16px; }
.co-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; }
@media (max-width: 720px) { .co-grid { grid-template-columns:1fr; } }
.co-field label { display:block !important; float:none !important; width:auto !important; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:6px; }
.co-field input, .co-field select { display:block !important; width:100% !important; max-width:none !important; height:40px; border:1px solid #CBD5E1; border-radius:8px; padding:0 12px; font-size:14px; color:#0F172A; background:#fff; box-sizing:border-box; }
.co-field input:focus, .co-field select:focus { outline:2px solid var(--lg-accent); outline-offset:1px; border-color:transparent; }
.co-field .co-hint { font-size:12px; color:#94A3B8; margin-top:5px; }
.co-field .co-err { font-size:12px; color:#C62828; margin-top:5px; font-weight:500; }
.co-field.has-error input, .co-field.has-error select { border-color:#C62828; }
.co-row-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; max-width:880px; }
.co-primary { height:40px; padding:0 20px; border-radius:8px; border:1px solid var(--lg-accent); background:var(--lg-accent); color:#fff; font-size:14px; font-weight:600; cursor:pointer; }
.co-secondary { height:40px; padding:0 16px; border-radius:8px; border:1px solid #CBD5E1; background:#fff; color:#334155 !important; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; text-decoration:none !important; }
.co-primary:focus-visible, .co-secondary:focus-visible { outline:2px solid var(--lg-accent); outline-offset:2px; }
.co-test-result { font-size:13px; font-weight:600; }
.co-test-result.ok { color:#0E8C59; }
.co-test-result.bad { color:#C62828; }
</style>
@endpush

@section('content')
<div class="cr-canvas co-form-page">
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="3" y1="13" x2="21" y2="13"/></svg>
            </div>
            <div>
                <h1 class="cr-header-title">{{ $company->exists ? 'Edit ' . $company->subdomain : 'Add company' }}</h1>
                <div class="cr-header-sub"><a href="{{ route('companies') }}">Companies</a> &rsaquo; {{ $company->exists ? 'Edit' : 'Add' }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $company->exists ? route('companiesUpdate', $company->id) : route('companiesStore') }}" id="coForm" novalidate>
        @csrf
        @if ($company->exists) @method('PUT') @endif

        <div class="co-card">
            <h2>Company</h2>
            <p class="co-card-sub">The company's users open LeadGER at its web address.</p>
            <div class="co-grid">
                @php $f = fn ($name, $default = null) => old($name, $company->{$name} ?? $default); @endphp
                <div class="co-field {{ $errors->has('subdomain') ? 'has-error' : '' }}">
                    <label for="co-host">Web address *</label>
                    <input id="co-host" name="subdomain" value="{{ $f('subdomain') }}" placeholder="company-a.{{ config('tenancy.base_domain') }}" maxlength="191" required>
                    <div class="co-hint">Address only, without https:// (DNS must point to the server)</div>
                    @error('subdomain')<div class="co-err">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="co-card">
            <h2>Company database</h2>
            <p class="co-card-sub">The company's own database. It must already exist; it is checked before saving and never changed here.</p>
            <div class="co-grid">
                <div class="co-field {{ $errors->has('db_host') ? 'has-error' : '' }}">
                    <label for="co-dbhost">Database server *</label>
                    <input id="co-dbhost" name="db_host" value="{{ $f('db_host') }}" maxlength="191" required>
                    @error('db_host')<div class="co-err">{{ $message }}</div>@enderror
                </div>
                <div class="co-field {{ $errors->has('db_name') ? 'has-error' : '' }}">
                    <label for="co-dbname">Database name *</label>
                    <input id="co-dbname" name="db_name" value="{{ $f('db_name') }}" maxlength="191" required>
                    @error('db_name')<div class="co-err">{{ $message }}</div>@enderror
                </div>
                <div class="co-field {{ $errors->has('db_user') ? 'has-error' : '' }}">
                    <label for="co-dbuser">Database user *</label>
                    <input id="co-dbuser" name="db_user" value="{{ $f('db_user') }}" maxlength="191" required autocomplete="off">
                    @error('db_user')<div class="co-err">{{ $message }}</div>@enderror
                </div>
                <div class="co-field {{ $errors->has('db_password') ? 'has-error' : '' }}">
                    <label for="co-dbpass">Database password {{ $company->exists ? '' : '*' }}</label>
                    <input id="co-dbpass" name="db_password" type="password" maxlength="255" autocomplete="new-password"
                           placeholder="{{ $company->exists ? 'Leave empty to keep the saved password' : '' }}">
                    @error('db_password')<div class="co-err">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="co-row-actions">
            <button type="submit" class="co-primary">{{ $company->exists ? 'Save changes' : 'Add company' }}</button>
            <button type="button" class="co-secondary" id="coTest">Test connection</button>
            <a href="{{ route('companies') }}" class="co-secondary">Cancel</a>
            <span class="co-test-result" id="coTestResult" role="status" aria-live="polite"></span>
        </div>
    </form>
</div>
@endsection

@push('after-scripts')
<script>
(function () {
    'use strict';
    var btn = document.getElementById('coTest'), out = document.getElementById('coTestResult');
    btn.addEventListener('click', function () {
        var form = document.getElementById('coForm');
        var body = new FormData();
        body.append('_token', form.querySelector('[name=_token]').value);
        ['db_host', 'db_name', 'db_user', 'db_password'].forEach(function (k) { body.append(k, form.querySelector('[name=' + k + ']').value); });
        @if ($company->exists) body.append('id', '{{ $company->id }}'); @endif
        btn.disabled = true; out.className = 'co-test-result'; out.textContent = 'Testing…';
        fetch(@json(route('companiesTestConnection')), { method: 'POST', body: body, headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, d: d }; }); })
            .then(function (res) {
                if (res.status === 422) { out.className = 'co-test-result bad'; out.textContent = 'Fill in the database server, name and user.'; return; }
                out.className = 'co-test-result ' + (res.d.ok ? 'ok' : 'bad');
                out.textContent = res.d.ok ? 'Connected to the database.' : 'Not reachable: ' + (res.d.error || 'error');
            })
            .catch(function () { out.className = 'co-test-result bad'; out.textContent = 'Test failed. Try again.'; })
            .then(function () { btn.disabled = false; });
    });
})();
</script>
@endpush
