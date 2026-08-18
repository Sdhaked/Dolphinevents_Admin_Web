@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('body')
    <section class="auth-wrapper">
        <div style="width:100%; max-width: 30rem;">
            <div class="d-flex justify-content-end my-4">
                <button class="p-0" style="color:var(--color-text-100)" onclick="window.location.href='{{ route('login') }}'"><i
                        class="fa-solid fa-arrow-left-long i-mr"></i> Back to login</button>
            </div>
            <form class="style-box auth-box needs-validation" action="{{ route('password.email') }}" method="POST" novalidate>
                @csrf
                <h1 class="hd-lg text-center my-2">Forgot Password</h1>

                {{-- Success Message --}}
                @if (session('success'))
                    <div style="color: green; margin-bottom: 10px;">
                        {{ session('success') }}
                    </div>
                @endif

                <div style="display: flex; gap: 1rem;">
                    <div class="form-floating flex-grow-1">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="myemail" value="{{ old('email') }}" required>
                        <label for="myemail">Enter Registered Email</label>
                    </div>

                    <button type="submit" class="btn-md btn-prim flex-shrink-0"><i
                            class="fa-solid fa-arrow-right-long"></i></button>
                </div>
            </form>
            <p><a href="{{ route('set.new.password') }}">Is line ko delete kardena | next step link</a></p>
        </div>
    </section>
@endsection