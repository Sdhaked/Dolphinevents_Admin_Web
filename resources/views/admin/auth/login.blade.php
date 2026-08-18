@extends('layouts.auth')

@section('title', 'Login')

@section('body')
    <section class="auth-wrapper">
        <div style="width:100%; max-width: 30rem;">
            <form class="style-box auth-box needs-validation" id="loginForm" action="{{ route('login.post') }}" method="POST"
                  novalidate>
                @csrf
                <img src="{{ asset('images/logo-w.svg') }}" alt="[company name] logo" class="logo-img"/>
                <!-- <h1 class="hd-xl text-center my-2">Login</h1> -->

                {{-- Success Message --}}
                @if (session('success'))
                    <div style="color: green; margin-bottom: 10px;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="form-floating mb-1">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="myemail" value="{{ old('email') }}"
                           required>
                    <label for="myemail">Email</label>
                </div>

                <div class="passBox mb-1">
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control" id="dg5" required>
                        <label for="dg5">Password</label>
                    </div>
                    <button type="button" class="input-group-text pass-eye">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>

                <button type="submit" id="loginBtn" class="btn-md btn-prim">Login</button>
                <button class="btn-md btn-prim d-none" id="loginBtnLoader" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span role="status">Loading...</span>
                </button>
            </form>
            <div class="mt-3">
                <p class="text-end"><a href="{{ route('forgot.password') }}">Forgot Password?</a></p>
            </div>
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