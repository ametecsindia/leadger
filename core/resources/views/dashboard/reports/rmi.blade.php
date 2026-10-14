@extends('dashboard.layouts.master')
@section('title', 'RMI Report - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* LeadGER RMI Report Page */
.rmi-canvas {
    background: #F4F6FB;
    min-height: calc(100vh - 64px);
    padding: 28px 30px;
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}
@media (max-width: 768px) { .rmi-canvas { padding: 16px 14px; } }

.rmi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.rmi-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.rmi-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #E11D48 0%, #BE123C 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 14px rgba(225, 29, 72, 0.28);
    flex-shrink: 0;
}
.rmi-header-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.2;
}
.rmi-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 500;
}
.rmi-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}
.rmi-badge-active {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #059669;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="rmi-canvas">
    <div class="rmi-header">
        <div class="rmi-header-left">
            <div class="rmi-header-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <h1 class="rmi-header-title">RMI Report</h1>
                <div class="rmi-header-sub">Recovery &amp; Management Information Analysis Report</div>
            </div>
        </div>
        <div class="rmi-badge-active">
            <i class="fa-solid fa-circle-check"></i>
            <span>Module Active in Company Settings</span>
        </div>
    </div>

    <div class="rmi-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #F1F5F9; padding-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0F172A;">RMI Overview &amp; Summary</h3>
            <span style="font-size: 13px; color: #64748B;">Generated: {{ date('M d, Y') }}</span>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Total Portfolio Accounts</div>
                <div style="font-size: 24px; font-weight: 800; color: #0F172A; margin-top: 6px;">{{ number_format(\App\Models\Lead::count()) }}</div>
            </div>
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Total Connected Calls</div>
                <div style="font-size: 24px; font-weight: 800; color: #0F172A; margin-top: 6px;">{{ number_format(\App\Models\CallLog::count()) }}</div>
            </div>
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                <div style="font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase;">Resolved / Deals</div>
                <div style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 6px;">{{ number_format(\App\Models\Lead::where('status', 'Deal')->orWhere('status', '1')->count()) }}</div>
            </div>
        </div>

        <p style="color: #64748B; font-size: 13.5px; line-height: 1.6; margin: 0;">
            This RMI (Recovery &amp; Management Information) Report is enabled in <strong>Company Settings</strong>. When disabled in Company Settings, backend authorization blocks access to this report for non-administrators.
        </p>
    </div>
</div>
@endsection
