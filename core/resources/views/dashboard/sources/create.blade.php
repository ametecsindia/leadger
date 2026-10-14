@extends('dashboard.layouts.master')
@section('title', 'Create Source')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Premium Corporate Sources Page Styling (Add Source Form)
   Scoped strictly under .sources-page container
   ========================================================================== */
.sources-page {
    font-family: var(--lg-font-sans);
    background-color: #F8FAFC;
    padding: 24px 32px;
    min-height: calc(100vh - 72px);
    color: #0F172A;
    box-sizing: border-box;
}

.sources-page .lg-sources-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.sources-page .lg-sources-breadcrumbs {
    font-size: 13px;
    font-weight: 500;
    color: #94A3B8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sources-page .lg-sources-breadcrumbs a {
    color: #94A3B8;
    text-decoration: none;
    transition: color 0.15s ease;
}

.sources-page .lg-sources-breadcrumbs a:hover {
    color: var(--lg-accent);
}

.sources-page .lg-sources-breadcrumbs .sep {
    color: #CBD5E1;
}

.sources-page .lg-sources-breadcrumbs .active {
    color: #64748B;
    font-weight: 600;
}

.sources-page .lg-sources-title {
    font-size: 28px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.sources-page .lg-sources-form-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    padding: 32px 36px;
    max-width: 680px;
}

.sources-page .lg-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 22px;
}

.sources-page .lg-form-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
}

.sources-page .lg-form-control {
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

.sources-page .lg-form-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.sources-page textarea.lg-form-control {
    height: 110px;
    padding: 12px 16px;
    resize: vertical;
}

.sources-page .lg-btn-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 28px;
}

.sources-page .lg-btn-primary {
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
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.sources-page .lg-btn-primary:hover {
    background: var(--lg-accent-hover);
}

.sources-page .lg-btn-secondary {
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

.sources-page .lg-btn-secondary:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
}
</style>
@endpush

@section('content')
<div class="sources-page">
    <div class="lg-sources-header">
        <div>
            <div class="lg-sources-breadcrumbs">
                <a href="{{ route('adminHome') }}">Administration</a>
                <span class="sep">/</span>
                <a href="{{ route('sources') }}">Sources</a>
                <span class="sep">/</span>
                <span class="active">Create Source</span>
            </div>
            <h1 class="lg-sources-title">Add a new Source</h1>
        </div>
    </div>

    <div class="lg-sources-form-card">
        {{ Form::open(['route' => ['sourcesStore'], 'method' => 'POST', 'files' => false]) }}

        <div class="lg-form-group">
            <label class="lg-form-label" for="name">Source Name</label>
            {!! Form::text('name', '', ['placeholder' => 'e.g. Direct Marketing, WhatsApp Campaign', 'class' => 'lg-form-control', 'id' => 'name', 'required' => '']) !!}
        </div>

        <div class="lg-form-group">
            <label class="lg-form-label" for="description">Description</label>
            {!! Form::textarea('description', '', ['placeholder' => 'Enter source description...', 'class' => 'lg-form-control', 'id' => 'description']) !!}
        </div>

        <div class="lg-btn-row">
            <button type="submit" class="lg-btn-primary">Add Source</button>
            <a href="{{ route('sources') }}" class="lg-btn-secondary">Cancel</a>
        </div>

        {{ Form::close() }}
    </div>
</div>
@endsection
