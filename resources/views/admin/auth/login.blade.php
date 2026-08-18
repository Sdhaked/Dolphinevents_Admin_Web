@extends('layouts.auth')

@section('title', 'Login')

@section('body')
    <section class="auth-wrapper">
        <div style="width:100%; max-width: 30rem;">
            <form class="style-box auth-box needs-validation" id="loginForm" action="{{ $showOtpForm ? route('login.verify.otp') : route('login.post') }}" method="POST"
                  novalidate>
                @csrf
                <img src="{{ asset('images/logo-w.svg') }}" alt="Dolphinevent logo" class="logo-img"/>
                <!-- <h1 class="hd-xl text-center my-2">Login</h1> -->

                {{-- Success Message --}}
                @if (session('success'))
                    <div style="color: green; margin-bottom: 10px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div style="color: #b7791f; margin-bottom: 10px;">
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="color: #dc2626; margin-bottom: 10px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($showOtpForm)
                    <input type="hidden" name="email" value="{{ $otpEmail }}">

                    <div class="mb-1">
                        <p style="font-size: 0.85rem; margin-bottom: 0.4rem;">
                            OTP sent to <strong>{{ $otpEmail }}</strong>
                        </p>
                    </div>

                    <div class="form-floating mb-1">
                        <input type="text" name="otp" class="form-control @error('otp') is-invalid @enderror" id="adminOtp"
                               inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                        <label for="adminOtp">Enter OTP</label>
                    </div>
                @else
                    <div class="form-floating mb-1">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="myemail" value="{{ old('email', $otpEmail) }}"
                               required>
                        <label for="myemail">Email</label>
                    </div>
                @endif

                <button type="submit" id="loginBtn" class="btn-md btn-prim">{{ $showOtpForm ? 'Verify OTP' : 'Send OTP' }}</button>
                <button class="btn-md btn-prim d-none" id="loginBtnLoader" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span role="status">Loading...</span>
                </button>
            </form>
            @if ($showOtpForm)
                <form action="{{ route('login.resend.otp') }}" method="POST" class="mt-3 text-end">
                    @csrf
                    <button type="submit" class="btn-link" @if($resendWaitSeconds > 0) disabled @endif>
                        Resend OTP@if($resendWaitSeconds > 0) ({{ $resendWaitSeconds }}s)@endif
                    </button>
                </form>
            @endif
        </div>
    </section>
    <script>
        let logo = document.querySelector('.logo-img');
        let getTheme = localStorage.getItem("theme") || "dark";
        if (getTheme == "dark") {
            logo.src = "{{ asset('images/logo-w.svg') }}";
        } else {
            logo.src = "{{ asset('images/logo.svg') }}";
        }

        const loginBtn = document.getElementById('loginBtn');
        const loginBtnLoader = document.getElementById('loginBtnLoader');
        const loginForm = document.getElementById('loginForm');

        loginBtn.addEventListener('click', function (e) {
            e.preventDefault();

            loginBtn.classList.add('d-none');
            loginBtnLoader.classList.remove('d-none');

            loginForm.submit();
        });
    </script>
@endsection
