@extends('layouts.auth')

@section('title', 'Login')

@section('body')
<section class="auth-wrapper">
    <div style="width:100%; max-width: 30rem;">
        <div class="style-box auth-box">
            <img src="{{ asset('images/logo-w.svg') }}" alt="Dolphinevent logo" class="logo-img" />
            <!-- <h1 class="hd-xl text-center my-2">Login</h1> -->

            

            @if ($showOtpForm)
            <form class="needs-validation auth-form" id="loginForm" action="{{ route('login.verify.otp') }}"
                method="POST" novalidate>
                @csrf
                <input type="hidden" name="email" value="{{ $otpEmail }}" style="display: none;">

                <p class="auth-helper-text">
                    Email send to {{ $otpEmail }}
                </p>

                <div class="form-floating mb-1">
                    <input type="text" name="otp" class="form-control @error('otp') is-invalid @enderror" id="adminOtp"
                        inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                    <label for="adminOtp">Enter OTP</label>
                </div>

                <button type="submit" id="loginBtn" class="btn-md btn-prim">Verify OTP</button>
                <button class="btn-md btn-prim d-none" id="loginBtnLoader" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span role="status">Loading...</span>
                </button>
            </form>


            <div class="auth-otp-actions">
                <form action="{{ route('login.resend.otp') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-link" @disabled($resendWaitSeconds> 0)>
                        Resend OTP{{ $resendWaitSeconds > 0 ? ' (' . $resendWaitSeconds . 's)' : '' }}
                    </button>
                </form>
                <button type="button" class="btn-link" id="changeEmailToggle"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button>
            </div>

            <form action="{{ route('login.change.email') }}" method="POST"
                class="needs-validation auth-change-email-form d-none" id="changeEmailForm" novalidate>
                @csrf
                <div class="form-floating mb-1">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        id="changeEmail" value="{{ old('email') }}" required>
                    <label for="changeEmail">New Email</label>
                </div>
                <button type="submit" class="btn-md btn-prim">Send OTP</button>
            </form>
            @else
            <form class="needs-validation auth-form" id="loginForm" action="{{ route('login.post') }}" method="POST"
                novalidate>
                @csrf
                <div class="form-floating mb-1">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        id="myemail" value="{{ old('email', $otpEmail) }}" required>
                    <label for="myemail">Email</label>
                </div>

                <button type="submit" id="loginBtn" class="btn-md btn-prim">Send OTP</button>
                <button class="btn-md btn-prim d-none" id="loginBtnLoader" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span role="status">Loading...</span>
                </button>
            </form>
            @endif
        </div>

        {{-- Success Message --}}
            <!-- @if (session('success'))
            <div class="auth-message auth-message--success">
                {{ session('success') }}
            </div>
            @endif -->

            @if (session('warning'))
            <div class="auth-message auth-message--warning">
                {{ session('warning') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="auth-message auth-message--error">
                {{ $errors->first() }}
            </div>
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
const changeEmailToggle = document.getElementById('changeEmailToggle');
const changeEmailForm = document.getElementById('changeEmailForm');
const changeEmailInput = document.getElementById('changeEmail');

loginForm?.addEventListener('submit', function() {
    loginBtn.classList.add('d-none');
    loginBtnLoader.classList.remove('d-none');
});

changeEmailToggle?.addEventListener('click', function() {
    changeEmailForm.classList.toggle('d-none');
    changeEmailToggle.textContent = changeEmailForm.classList.contains('d-none') ? 'Change Email' : 'Cancel';
    changeEmailInput?.focus();
});
</script>
@endsection