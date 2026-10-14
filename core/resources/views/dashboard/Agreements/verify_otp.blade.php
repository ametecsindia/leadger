@extends('dashboard.layouts.master')
@section('title', __('Verify OTP'))
@section('content')

<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>{{ __('Verify OTP') }}</h3>
            <small>
                <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                <a href="#">{{ __('Agreements') }}</a> /
                <a href="#">{{ __('Verify OTP') }}</a>
            </small>
        </div>

        <div class="box-body">
        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card card-box-shadow">
                        <div class="card-header text-center">
                            <h2>{{ __('Verify OTP') }}</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('agreements.otp.submit') }}" id="otpForm">
                                @csrf
                                <div class="form-group">
                                    <label for="otp">{{ __('Enter OTP sent to your email:') }}</label>
                                    <input type="text" name="otp" class="form-control" required>
                                    @error('otp') 
                                        <span class="text-danger">{{ $message }}</span> 
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-block" id="verifyBtn">{{ __('Verify') }}</button>
                            </form>

                            <form method="POST" action="{{ route('agreements.otp.resend') }}" style="margin-top: 15px;" id="resendForm">
                                @csrf
                                <button type="submit" class="btn btn-link btn-block" id="resendBtn" style="display: none;">{{ __('Resend OTP') }}</button>
                            </form>

                            <div class="text-center mt-3" id="timer">Time left: 5:00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push("after-scripts")
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var timer = 300; // 5 minutes
        var timerElement = document.getElementById("timer");

        function updateTimer() {
            var minutes = Math.floor(timer / 60);
            var seconds = timer % 60;
            if (seconds < 10) seconds = "0" + seconds;
            timerElement.innerHTML = "Time left: " + minutes + ":" + seconds;
        }

        function startTimer() {
            if (!timerElement) return;

            var timerInterval = setInterval(function() {
                updateTimer();

                if (timer <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById("verifyBtn").style.display = "none";
                    document.getElementById("resendBtn").style.display = "block";
                }

                timer--;
            }, 1000);
        }

        startTimer();
    });
</script>
@endpush
