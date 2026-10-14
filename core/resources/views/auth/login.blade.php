@extends('dashboard.layouts.auth')
@section('title', __('backend.signedInToControl'))
@section('content')

<link rel="stylesheet" href="{{ asset('assets/dashboard/css/login-custom.css') }}" type="text/css"/>

<!-- Full Viewport Cinematic Background with Tele-Sales Office Environment -->
<div class="lg-cinematic-bg"></div>

<!-- Vertically and Horizontally Centered Login Experience -->
<div class="lg-login-wrapper">
    <div class="lg-login-card">
        
        <!-- Header & Branding (Using exact Dashboard Sidebar Logo asset) -->
        <div class="lg-login-brand-header">
            <div class="lg-logo-container">
                <img src="{{ asset('assets/dashboard/images/leadger-logo-sidebar.png') }}" alt="LeadGER - Generate, Engage &amp; Retain" class="lg-brand-logo-img">
            </div>
            
            <h2 class="lg-welcome-heading">Welcome Back!</h2>
            <p class="lg-welcome-desc">Sign in to your LeadGER command center</p>
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

        <!-- Login Form -->
        <form name="form" method="POST" action="{{ url('/'.env('BACKEND_PATH').'/login') }}" autocomplete="on">
            {{ csrf_field() }}

            <!-- Email / Username Input -->
            <div class="lg-form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                <div class="lg-input-wrapper">
                    <span class="lg-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="email" 
                        id="email" 
                        value="{{ old('email', 'admin@leadger.com') }}" 
                        class="lg-input" 
                        placeholder="Enter your email or username" 
                        required 
                        autofocus
                    >
                </div>
                <span id="err_email" class="lg-error-text"></span>
            </div>

            <!-- Password Input -->
            <div class="lg-form-group {{ $errors->has('password') ? 'has-error' : '' }}">
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

            <!-- Options Row: Remember Me & Forgot Password -->
            <div class="lg-form-options">
                <label class="lg-remember-label">
                    <input type="checkbox" name="remember" id="remember" class="lg-checkbox">
                    <span>Remember me</span>
                </label>
                <a href="{{ url('/'.env('BACKEND_PATH').'/password/reset') }}" class="lg-forgot-link">
                    Forgot password?
                </a>
            </div>

            <!-- OTP Verification Section (Hidden by default, shown during 2FA flow) -->
            <div class="lg-otp-box" style="display:none;" id="div_otp">
                <label class="lg-form-label" for="otp">Security Code (OTP)</label>
                <div class="lg-input-wrapper">
                    <span class="lg-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </span>
                    <input type="text" name="otp" id="otp" class="lg-input" placeholder="Enter 4-digit OTP" maxlength="4" pattern="\d{4}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);">
                </div>
                <div class="lg-timer-text" style="font-size: 12px; color: #64748B; margin-top: 6px;">
                    <span id="otp_msg"></span> 
                    <span>Expires in <strong id="timer-show">60</strong>s</span>
                </div>
                <span id="err_otp" class="lg-error-text"></span>
            </div>

            <!-- Sign In Submit Button -->
            <button onclick="login();return false;" type="button" class="lg-submit-btn" id="_login">
                <svg viewBox="0 0 24 24">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                <span>Sign In</span>
            </button>

            <button onclick="validateOTP();return false;" type="button" class="lg-submit-btn" id="_validate_otp" style="display:none;">
                <span>Verify OTP &amp; Continue</span>
            </button>

            <button onclick="resendOTP();return false;" type="button" class="lg-submit-btn" id="_resend_otp" style="display:none; background: #475569;">
                <span>Resend OTP</span>
            </button>
        </form>

        <!-- Copyright Footer -->
        <div class="lg-copyright">
            &copy; 2026. Tricube Media Solutions. All rights reserved.
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
        let strUrl = "{{ route('adminCustomLogin') }}";
        let objInputs = {
            email: $('#email').val(),
            password: $('#password').val(),
            _token: '{{ csrf_token() }}',
            remember: false
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

                $('#div_otp').hide();
                $('#_validate_otp').hide();
                $('#_resend_otp').hide();
                $('#_login').show();
                $('#msg_title').hide().html('');
                $('#msg_title_fail').hide().html('');

                if (response.hasOwnProperty('success')) {
                    $('#div_otp').show();
                    $("#otp_msg").html(response.otp_msg || 'OTP code sent to your registered device.');
                    startTimer(60);
                    $('#_validate_otp').show();
                    $('#_login').hide();
                    $('#msg_title').show().html(response.success);
                    return true;
                } else {
                    $('#msg_title_fail').show().html(response.error || 'Invalid credentials.');
                    if (response.error == 'Application License Expired') {
                        window.location = '{{ route('adminLicense') }}';
                    }
                    return false;
                }
            }).fail(function() {
                $('#_login').prop('disabled', false).css('opacity', '1');
                document.forms['form'].submit();
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

        // OTP
        if (objInputs.hasOwnProperty('otp')) {
            const otpVal = objInputs.otp ? objInputs.otp.trim() : '';
            if (!otpVal || !/^\d{4}$/.test(otpVal)) {
                intValid = 0;
                $('#err_otp').html('Please enter a valid 4-digit OTP.');
            }
        }

        return intValid;
    }

    function clearErrors(strScenario) {
        switch (strScenario) {
            case 'do_login':
                $('#err_email').html('');
                $('#err_password').html('');
                break;
            case 'validate_otp':
                $('#msg_title').hide().html('');
                $('#msg_title_fail').hide().html('');
                $('#err_otp').html('');
                break;
            case 'clear_default_errs':
                $('#msg_title').hide().html('');
                $('#msg_title_fail').hide().html('');
                $('#err_email').html('');
                $('#err_password').html('');
                $('#err_otp').html('');
                break;
        }
        return true;
    }

    function validateOTP() {
        clearErrors('validate_otp');
        let strUrl = "{{ route('adminValidateOtp') }}";
        var mobile = false;
        if (typeof Android != 'undefined') {
            mobile = true;
        }
        let objInputs = {
            _token: '{{ csrf_token() }}',
            otp: $('#otp').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            remember: false,
            mobile: mobile
        };

        let intValid = validateInputs(objInputs);

        if (1 == intValid) {
            $('#_validate_otp').prop('disabled', true);
            $.post(strUrl, objInputs, function (response) {
                $('#_validate_otp').prop('disabled', false);
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                } catch(e) {}
                
                if (response.hasOwnProperty('success')) {
                    window.location.href = response.url || '{{ route("adminHome") }}';
                } else {
                    $('#msg_title_fail').show().html(response.error || 'Please enter a valid 4-digit OTP.');
                    return false;
                }
            }).fail(function() {
                $('#_validate_otp').prop('disabled', false);
            });
        }
    }

    function resendOTP() {
        $("#div_otp").hide();
        login();
    }

    function startTimer(timeleft) {
        $("#timer-show").html(timeleft);
        var countdownTimer = setInterval(() => {
            timeleft--;
            $("#timer-show").html(timeleft);
            if (timeleft <= 0) {
                clearInterval(countdownTimer);
                $("#div_otp").hide();
                $("#_validate_otp").hide();
                $("#_resend_otp").show();
            }
        }, 1000);
    }
</script>
@endpush