@extends('dashboard.layouts.master')
@section('title', 'Create Status')

@push("after-styles")
<style>
.lg-status-dashboard {
    padding: 24px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    font-family: var(--lg-font-sans);
    color: #0F172A;
}

.lg-page-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
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
    color: #64748B;
    text-decoration: none;
}

.lg-breadcrumb-nav a:hover {
    color: var(--lg-accent);
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-form-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    padding: 32px 36px;
    max-width: 680px;
}

.lg-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.lg-form-label {
    font-size: 13.5px;
    font-weight: 500;
    color: #475569;
}

.lg-form-control {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-form-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-form-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 14px center;
    cursor: pointer;
}

textarea.lg-form-control {
    height: 100px;
    padding: 12px 16px;
    resize: vertical;
}

.lg-btn-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 28px;
}

.lg-btn-primary {
    height: 42px;
    padding: 0 24px;
    background: var(--lg-accent);
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
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
}

.lg-btn-primary:hover {
    background: #0D52CC;
}

.lg-btn-secondary {
    height: 42px;
    padding: 0 20px;
    background: #FFFFFF;
    color: #0F172A;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.lg-btn-secondary:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
}
</style>
@endpush

@section('content')
<div class="lg-status-dashboard">
    <div class="lg-page-header-row">
        <div>
            <div class="lg-breadcrumb-nav">
                <a href="{{ route('adminHome') }}">Home</a>
                <span>/</span>
                <span>Lead Status</span>
                <span>/</span>
                <a href="{{ route('statuses') }}">Status</a>
                <span>/</span>
                <span>Create</span>
            </div>
            <h1 class="lg-title-main">Add a new Status</h1>
        </div>
    </div>

    <div class="lg-form-card">
        {{ Form::open(['route' => ['statusesStore'], 'method' => 'POST', 'files' => false]) }}

        <div class="lg-form-group">
            <label class="lg-form-label" for="name">Status Name</label>
            {!! Form::text('name', '', ['placeholder' => 'e.g. INTERESTED, NOT INTERESTED, CBK', 'class' => 'lg-form-control', 'id' => 'name', 'required' => '']) !!}
        </div>

        <div class="lg-form-group">
            <label class="lg-form-label" for="seq_order">Order</label>
            <select name="seq_order" id="seq_order" required class="lg-form-control lg-form-select">
                <option value="">- - Select Order - -</option>
                @for ($i=1; $i<=$Count; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>

        <div class="lg-form-group">
            <label class="lg-form-label" for="description">Description</label>
            {!! Form::textarea('description', '', ['placeholder' => 'Enter description...', 'class' => 'lg-form-control', 'id' => 'description']) !!}
        </div>

        <div class="lg-btn-row">
            <button type="submit" class="lg-btn-primary">Add Status</button>
            <a href="{{ route('statuses') }}" class="lg-btn-secondary">Cancel</a>
        </div>

        {{ Form::close() }}
    </div>
</div>
@endsection
