@extends('dashboard.layouts.auth')
@section('title', __('backend.signedInToControl'))
@section('content')

<link rel="stylesheet" href="{{ asset('assets/dashboard/css/login-custom.css') }}" type="text/css"/>

<!-- Full Viewport Cinematic Background -->
<div class="lg-cinematic-bg"></div>

<!-- Vertically and Horizontally Centered License Activation Experience -->
<div class="lg-login-wrapper">
    <div class="lg-login-card">
        
        <!-- Header & Branding -->
        <div class="lg-login-brand-header">
            <div class="lg-logo-container">
                @if(Helper::GeneralSiteSettings("style_logo_" . @Helper::currentLanguage()->code) !="")
                    <img alt="LeadGER" class="lg-brand-logo-img"
                         src="{{ URL::to('uploads/settings/'.Helper::GeneralSiteSettings("style_logo_" . @Helper::currentLanguage()->code)) }}">
                @else
                    <img alt="LeadGER - Generate, Engage &amp; Retain" class="lg-brand-logo-img"
                         src="{{ asset('assets/dashboard/images/leadger-logo-sidebar.png') }}">
                @endif
            </div>
            
            <h2 class="lg-welcome-heading">License Activation</h2>
            <p class="lg-welcome-desc">Enter your credentials and license key to activate LeadGER</p>
        </div>

        <!-- Global / Server Error Message Display -->
        <div id="msg_title_fail" class="lg-alert-danger" style="display:none;"></div>
        <div id="msg_title" class="lg-alert-success" style="display:none;"></div>

        @if($errors->any())
            <div class="lg-alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- License Activation Form -->
        <form name="form" method="POST" action="{{ url('/'.env('BACKEND_PATH').'/login') }}" autocomplete="on">
            {{ csrf_field() }}

            <!-- Email Input -->
            <div class="lg-form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                <label class="lg-form-label" for="email">{{ __('backend.connectEmail') }}</label>
                <div class="lg-input-wrapper">
                    <span class="lg-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}" 
                        class="lg-input" 
                        placeholder="admin@leadger.com" 
                        required 
                        autofocus
                    >
                </div>
                <span id="err_email" class="lg-error-text"></span>
            </div>

            <!-- Password Input -->
            <div class="lg-form-group {{ $errors->has('password') ? 'has-error' : '' }}">
                <label class="lg-form-label" for="password">{{ __('backend.connectPassword') }}</label>
                <div class="lg-input-wrapper">
                    <span class="lg-input-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="lg-input" 
                        placeholder="Enter your password" 
                        required
                    >
                    <button type="button" class="lg-pwd-toggle" onclick="togglePasswordVisibility()" title="Toggle password visibility" tabindex="-1">
                        <svg id="pwd-icon-eye" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="pwd-icon-eye-off" viewBox="0 0 24 24" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 1 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <span id="err_password" class="lg-error-text"></span>
                @if ($errors->has('password'))
                    <span class="lg-error-text">{{ $errors->first('password') }}</span>
                @endif
            </div>

            <!-- License Key Input -->
            <div class="lg-form-group {{ $errors->has('license') ? 'has-error' : '' }}" id="div_license">
                <label class="lg-form-label" for="license">License Key</label>
                <div class="lg-input-wrapper">
                    <span class="lg-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 2l-2 2m-1.5 1.5l-3 3m-2 2l-3 3m-2 2l-3 3M3 21l3-3m0 0l-3-3m3 3l3 3"></path>
                            <circle cx="7.5" cy="7.5" r="4.5"></circle>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="license" 
                        id="license" 
                        class="lg-input" 
                        placeholder="Enter your product license key" 
                        autocomplete="off"
                        required
                    >
                </div>
                <span id="err_license" class="lg-error-text"></span>
            </div>

            <!-- Activate Submit Button -->
            <button onclick="login();return false;" type="button" class="lg-submit-btn" id="_login">
                <svg viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>Activate License</span>
            </button>
        </form>

        <!-- Copyright Footer -->
        <div class="lg-copyright">
            Copyright &copy; Tricube Media Solutions 2023
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script>
    function togglePasswordVisibility() {
        var pwdInput = document.getElementById('password');
        var eyeIcon = document.getElementById('pwd-icon-eye');
        var eyeOffIcon = document.getElementById('pwd-icon-eye-off');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.style.display = 'none';
            eyeOffIcon.style.display = 'block';
        } else {
            pwdInput.type = 'password';
            eyeIcon.style.display = 'block';
            eyeOffIcon.style.display = 'none';
        }
    }

    function login() {
        let strUrl = "{{ route('adminLicenseActivate') }}";
        let objInputs = {
            email: $('#email').val(),
            password: $('#password').val(),
            _token: '{{ csrf_token() }}',
            license: $('#license').val()
        };
        let intValid = validateInputs(objInputs);
        if (1 == intValid) {
            $('#_login').prop('disabled', true).css('opacity', '0.75');
            $.post(strUrl, objInputs, function (response) {
                $('#_login').prop('disabled', false).css('opacity', '1');
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                } catch(e) {}

                if (response.hasOwnProperty('success')) {
                    // the server restarts after saving the key: give it a moment before opening the login page
                    $('#msg_title_fail').hide();
                    $('#_login').prop('disabled', true).css('opacity', '0.75');
                    setTimeout(function () { window.location = '{{ URL::to('/admin/login') }}'; }, 2500);
                    return true;
                } else {
                    $('#msg_title_fail').show().html(response.error || 'License activation failed.');
                    return false;
                }
            }).fail(function(xhr) {
                $('#_login').prop('disabled', false).css('opacity', '1');
                let err = 'An error occurred during license activation.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    err = xhr.responseJSON.message;
                }
                $('#msg_title_fail').show().html(err);
            });
        }
    }

    function validateInputs(objInputs) {
        clearErrors('do_login');
        let intValid = 1;

        // Email
        if (objInputs.hasOwnProperty('email') && (!objInputs.email || objInputs.email.trim().length <= 0)) {
            intValid = 0;
            $('#err_email').html('Email address is required');
        }

        // Password
        if (objInputs.hasOwnProperty('password') && (!objInputs.password || objInputs.password.length <= 0)) {
            intValid = 0;
            $('#err_password').html('Password is required');
        }

        // License
        if (objInputs.hasOwnProperty('license') && (!objInputs.license || objInputs.license.trim().length <= 0)) {
            intValid = 0;
            $('#err_license').html('License key is required');
        }

        return intValid;
    }

    function clearErrors(strScenario) {
        $('#msg_title_fail').hide().html('');
        $('#err_email').html('');
        $('#err_password').html('');
        $('#err_license').html('');
        return true;
    }
</script>
@endpush