@extends('layouts.auth')

@section('title', 'Set New Password')

@section('body')
    <section class="auth-wrapper">
        <div style="width:100%; max-width: 30rem;">
            <form method="POST" action="{{ route('password.reset') }}" class="style-box auth-box needs-validation" novalidate>
                @csrf
                <h1 class="hd-lg text-center my-2">Set New Password</h1>

                {{-- Success Message --}}
                @if (session('success'))
                    <div style="color: green; margin-bottom: 10px;">
                        {{ session('success') }}
                    </div>
                @endif

                <input type="hidden" name="token" value="{{ request('token') }}">
                <input type="hidden" name="email" value="{{ request('email') }}">

                <div class="passBox mb-1">
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="sd5" required>
                        <label for="sd5">Password</label>
                    </div>
                    <button type="button" class="input-group-text pass-eye">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>

                <div class="passBox mb-1">
                    <div class="form-floating">
                        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" id="newpass" required>
                        <label for="newpass">Conform Password</label>
                    </div>
                    <button type="button" class="input-group-text pass-eye">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>

                <button type="submit" class="btn-md btn-prim">Submit</button>

                @error('error')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </form>
        </div>
    </section>
@endsection