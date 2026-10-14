@extends('dashboard.layouts.master')
@section('title', 'Edit WebRTC')

@section('content')
<style>
.lg-direct-edit-page {
    padding: 30px 20px 60px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: calc(100vh - 120px);
}

.lg-direct-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.lg-direct-header {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-direct-title {
    font-size: 20px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-direct-close {
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
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-direct-close:hover {
    background: #E2E8F0;
    color: #0F172A;
    border-color: #CBD5E1;
}

.lg-direct-body {
    padding: 24px 32px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.lg-direct-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-direct-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #64748B;
    margin: 0;
}

.lg-direct-control {
    width: 100%;
    height: 48px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14.5px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}

select.lg-direct-control {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 42px;
    cursor: pointer;
}

.lg-direct-control:focus {
    border-color: #1769E8;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-direct-control.has-error {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-direct-error {
    color: #EF4444;
    font-size: 12.5px;
    font-weight: 500;
    margin-top: 2px;
}

.lg-direct-footer {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

.lg-direct-btn-cancel {
    height: 44px;
    padding: 0 22px;
    background: #FFFFFF;
    color: #172B4D;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.lg-direct-btn-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-direct-btn-save {
    height: 44px;
    padding: 0 24px;
    background: #1769E8;
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.lg-direct-btn-save:hover {
    background: #1256BD;
}
</style>

<div class="lg-direct-edit-page">
    <div class="lg-direct-card">
        
        <div class="lg-direct-header">
            <h3 class="lg-direct-title">Edit WebRTC</h3>
            <a href="{{ route('webrtc') }}" class="lg-direct-close" title="Close" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </a>
        </div>

        <form action="{{ route('webrtcStore') }}" method="POST">
            @csrf
            <div class="lg-direct-body">
                
                @if(session('errorMessage'))
                    <div style="background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; padding: 12px 16px; border-radius: 10px; font-size: 13.5px;">
                        {{ session('errorMessage') }}
                    </div>
                @endif

                <div class="lg-direct-form-group">
                    <label class="lg-direct-label" for="user">User</label>
                    <select name="user" id="user" required class="lg-direct-control @error('user') has-error @enderror">
                        @foreach ($Users as $row)
                            <option value="{{ $row->id }}" {{ ($User->id == $row->id) ? 'selected' : '' }}>
                                {{ $row->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user')
                        <div class="lg-direct-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="lg-direct-form-group">
                    <label class="lg-direct-label" for="click_agent_number">Agent Number</label>
                    <input type="text" 
                           name="click_agent_number" 
                           id="click_agent_number" 
                           value="{{ old('click_agent_number', $User->click_agent_number) }}" 
                           class="lg-direct-control @error('click_agent_number') has-error @enderror" 
                           placeholder="e.g. 9777436222" 
                           required>
                    @error('click_agent_number')
                        <div class="lg-direct-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="lg-direct-footer">
                <a href="{{ route('webrtc') }}" class="lg-direct-btn-cancel">Cancel</a>
                <button type="submit" class="lg-direct-btn-save">Save Changes</button>
            </div>
        </form>

    </div>
</div>
@endsection
