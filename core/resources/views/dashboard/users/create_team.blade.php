@extends('dashboard.layouts.master')
@section('title', 'Add New Team - LeadGER')
@section('content')

<style>
.lg-create-team-wrapper {
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
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
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

.lg-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    max-width: 1050px;
}

.lg-search-input {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
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
    height: 42px;
    padding: 0 24px;
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

@media (max-width: 991px) {
    .lg-form-3col {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="lg-create-team-wrapper">

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
                <h1 class="lg-title-main">Add New Team</h1>
                <p class="lg-title-sub">Create a new team and assign team details</p>
            </div>
        </div>
        <div class="lg-breadcrumb-nav">
            <a href="{{ route('adminHome') }}">Home</a>
            <span>/</span>
            <a href="{{ route('teams') }}">Teams</a>
            <span>/</span>
            <span>Add New Team</span>
        </div>
    </div>

    <!-- MAIN FORM CARD -->
    <div class="lg-main-card">
        {{ Form::open(['route' => ['teamsStore'], 'method' => 'POST']) }}

        <!-- SECTION 1: BASIC DETAILS CARD -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
            <div style="font-size: 14px; font-weight: 700; color: #0F172A; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--lg-accent);"></span>
                Basic Details
            </div>

            <div>
                <div>
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;">
                        Team Name <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="text" name="name" class="lg-search-input" placeholder="Enter team name" required>
                </div>
            </div>
        </div>

        <!-- BOTTOM ACTIONS -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('teams') }}" class="btn white p-x-md" style="height: 42px; border-radius: 10px; font-weight: 700; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                Cancel
            </a>
            <button type="submit" class="lg-btn-add">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Create Team</span>
            </button>
        </div>

        {{ Form::close() }}
    </div>

</div>

@endsection
