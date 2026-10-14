@extends('dashboard.layouts.master')
@section('title', 'Add Staff To Team - LeadGER')
@section('content')

@php
    $AllUsers = \App\Models\User::orderBy('name', 'asc')->get();
    $AllTeams = \App\Models\Team::orderBy('name', 'asc')->get();
@endphp

<style>
/* LeadGER Corporate Modern Edit Team / Add Staff To Team Page Styling */
.lg-edit-team-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px;
    min-height: calc(100vh - 70px);
}

.lg-header-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(20, 100, 244, 0.1);
    color: var(--lg-accent);
    border: 1px solid rgba(20, 100, 244, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.12);
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-title-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 400;
}

.lg-breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-breadcrumb-nav a {
    color: var(--lg-accent);
    text-decoration: none;
}

.lg-breadcrumb-nav a:hover {
    text-decoration: underline;
}

.lg-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    max-width: 1050px;
}

.lg-search-box {
    position: relative;
    width: 280px;
    max-width: 100%;
}

.lg-search-box svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-input {
    width: 100%;
    height: 40px;
    padding: 0 14px 0 40px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
}

.lg-search-input:focus {
    background: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    padding: 9.5px 24px;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-add:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-entries-select {
    height: 42px;
    padding: 0 14px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    font-weight: 600;
    outline: none;
}

@media (max-width: 991px) {
    .lg-form-2col {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="lg-edit-team-wrapper">

    <!-- PAGE HEADER -->
    <div class="lg-header-box">
        <div class="lg-header-left">
            <div class="lg-header-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>
                </svg>
            </div>
            <div>
                <h1 class="lg-title-main">Add Staff To Team</h1>
                <p class="lg-title-sub">Assign staff members to a team</p>
            </div>
        </div>
        <div class="lg-breadcrumb-nav">
            <a href="{{ route('adminHome') }}">Home</a>
            <span>/</span>
            <a href="{{ route('users') }}">Users</a>
            <span>/</span>
            <a href="{{ route('teams') }}">Teams</a>
            <span>/</span>
            <span>Add Staff To Team</span>
        </div>
    </div>

    <!-- MAIN FORM CARD -->
    <div class="lg-main-card">
        {{ Form::open(['route' => ['teamsUpdate', $Teams->id], 'method' => 'POST']) }}
        <input type="hidden" name="team_members_submitted" value="1">

        <!-- 1. CHOOSE TEAM FIELD -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <label style="font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 8px; display: block;">
                Choose Team <span style="color: #EF4444;">*</span>
            </label>
            <div style="position: relative;">
                <select name="team_id" id="teamSelectDropdownPage" class="lg-entries-select" style="width: 100%; height: 42px; padding: 0 14px; font-size: 13.5px; border-radius: 10px; background-color: #F8FAFC; border: 1px solid #CBD5E1; color: #0F172A; font-weight: 600; outline: none;">
                    @foreach($AllTeams as $tOption)
                        <option value="{{ $tOption->id }}" {{ $tOption->id == $Teams->id ? 'selected' : '' }}>
                            {{ $tOption->name }} {{ $tOption->description ? '('.$tOption->description.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 2. STAFF SELECTION CONTAINER -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--lg-accent);"></span>
                        Staff Member Selection
                    </div>
                    <p style="font-size: 12.5px; color: #64748B; margin: 2px 0 0 0; font-weight: 400;">Select staff members to assign to the selected team</p>
                </div>
                <div class="lg-search-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="staffSearchPage" class="lg-search-input" placeholder="Search Staff...">
                </div>
            </div>

            <div id="staffListPage" style="max-height: 260px; overflow-y: auto; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px; background: #F8FAFC; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;" class="lg-form-2col">
                @foreach($AllUsers as $staff)
                    @php
                        $userTeams = array_filter(explode(',', $staff->team_id ?? ''));
                        $isAssigned = in_array((string)$Teams->id, array_map('strval', $userTeams));
                    @endphp
                    <label class="staff-item" data-teams="{{ $staff->team_id ?? '' }}" data-name="{{ strtolower($staff->name ?? '') }}" data-email="{{ strtolower($staff->email ?? '') }}" style="display: flex; align-items: center; gap: 10px; background: #FFFFFF; border: 1px solid #E2E8F0; padding: 10px 12px; border-radius: 8px; cursor: pointer; margin: 0; transition: border-color 0.15s ease;">
                        <input type="checkbox" name="team_members[]" value="{{ $staff->id }}" {{ $isAssigned ? 'checked' : '' }} style="width: 17px; height: 17px; accent-color: var(--lg-accent); cursor: pointer; flex-shrink: 0;">
                        <div style="display: flex; flex-direction: column; overflow: hidden;">
                            <span style="font-size: 13px; font-weight: 600; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $staff->name }}</span>
                            <span style="font-size: 11.5px; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $staff->email }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('teams') }}" class="btn white p-x-md" style="height: 42px; border-radius: 10px; font-weight: 700; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                Cancel
            </a>
            <button type="submit" class="lg-btn-add" style="height: 42px; padding: 0 24px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Save Changes</span>
            </button>
        </div>

        {{ Form::close() }}
    </div>

</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const staffSearch = document.getElementById('staffSearchPage');
        if (staffSearch) {
            staffSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const container = document.getElementById('staffListPage');
                if (container) {
                    const items = container.querySelectorAll('.staff-item');
                    items.forEach(function(item) {
                        const name = item.getAttribute('data-name') || '';
                        const email = item.getAttribute('data-email') || '';
                        if (!query || name.includes(query) || email.includes(query)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
            });
        }

        const teamDropdown = document.getElementById('teamSelectDropdownPage');
        if (teamDropdown) {
            teamDropdown.addEventListener('change', function() {
                const selectedTeamId = this.value;
                const container = document.getElementById('staffListPage');
                if (container) {
                    const items = container.querySelectorAll('.staff-item');
                    items.forEach(function(item) {
                        const teamsStr = item.getAttribute('data-teams') || '';
                        const userTeams = teamsStr.split(',').map(function(t) { return t.trim(); });
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        if (userTeams.indexOf(selectedTeamId.toString()) !== -1) {
                            checkbox.checked = true;
                        } else {
                            checkbox.checked = false;
                        }
                    });
                }
            });
        }
    });
</script>
@endpush
