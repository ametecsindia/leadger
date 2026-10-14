@extends('dashboard.layouts.master')
@section('title', 'Edit Product')

@push("after-styles")
<style>
.products-services-page {
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background-color: #F8FAFD;
    padding: 24px 32px;
    min-height: calc(100vh - 60px);
    color: #172B4D;
    box-sizing: border-box;
}

.lg-modal-overlay-static {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}

.lg-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    overflow: hidden;
    margin: auto;
    display: flex;
    flex-direction: column;
}

.lg-modal-header-bar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.lg-modal-header-title {
    font-size: 18px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-modal-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none !important;
}

.lg-modal-close-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
    border-color: #CBD5E1;
}

.lg-modal-body {
    padding: 24px 28px;
    background: #FFFFFF;
    box-sizing: border-box;
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-modal-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.lg-modal-control {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-modal-control::placeholder {
    color: #94A3B8;
}

.lg-modal-control:focus {
    border-color: #1769E8;
    box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12);
}

.lg-modal-footer-bar {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}

.lg-btn-modal-cancel {
    height: 40px;
    padding: 0 20px;
    background: #FFFFFF;
    color: #172B4D;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.lg-btn-modal-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-btn-modal-submit {
    height: 40px;
    padding: 0 22px;
    background: #1769E8;
    color: #FFFFFF;
    border: none;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(23, 105, 232, 0.2);
}

.lg-btn-modal-submit:hover {
    background: #1056C7;
}
</style>
@endpush

@section('content')
<div class="products-services-page">
    <div class="lg-modal-overlay-static">
        <div class="lg-modal-wrapper">
            <div class="lg-modal-header-bar">
                <h3 class="lg-modal-header-title">Edit Product</h3>
                <a href="{{ route('productServices') }}" class="lg-modal-close-btn" title="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
            </div>

            {{ Form::open(['route' => ['productServicesUpdate', $ProductService->id], 'method' => 'POST', 'id' => 'editProductForm']) }}
            <div class="lg-modal-body">
                @if($errors->has('name'))
                    <div style="color: #EF4444; font-size: 12.5px; font-weight: 500; margin-bottom: 12px;">
                        {{ $errors->first('name') }}
                    </div>
                @endif
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label" for="name">Product Name</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           class="lg-modal-control" 
                           placeholder="Enter product name..." 
                           required 
                           value="{{ old('name', $ProductService->name) }}" 
                           autofocus>
                </div>
            </div>

            <div class="lg-modal-footer-bar">
                <a href="{{ route('productServices') }}" class="lg-btn-modal-cancel">Cancel</a>
                <button type="submit" class="lg-btn-modal-submit">Save Changes</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

