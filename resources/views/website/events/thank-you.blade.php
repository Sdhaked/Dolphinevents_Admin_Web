@extends('layouts.website')

@section('head')
    <title>Event Success</title>

    {{-- Head meta / favicon / fonts --}}
    @include('website._partials.head.head-files')

    {{-- Animate CSS --}}
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}">

    {{-- Global CSS --}}
    @include('website._partials.head.g-css-files')

    {{-- Page specific CSS --}}
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}">

    {{-- Animation JS --}}
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    {{-- Global JS --}}
    @include('website._partials.head.g-js-files')
@endsection


@section('body')

    {{-- Preloader --}}
    @include('website._partials.preloader')

    {{-- Header --}}
    @include('website._partials.nav')

    <main>
        <section class="container-fluid spc-y-half main-sec">
            <div class="container d-flex justify-content-center align-items-center">

                {{-- Floating Confetti --}}
                <div class="confetti-container">
                    <div class="confetti" style="top:5rem; left:2.5rem;"></div>
                    <div class="confetti small success" style="top:8rem; right:5rem;"></div>
                    <div class="confetti large semi" style="bottom:10rem; left:25%;"></div>
                    <div class="confetti small semi" style="top:33%; right:33%;"></div>
                    <div class="confetti medium semi" style="bottom:5rem; right:2.5rem;"></div>
                </div>

                <div class="thankyou-card">
                    <div class="card-content">

                        {{-- Success Icon --}}
                        <div class="icon-container">
                            <img
                                src="{{ asset('website/images/success-celebration.png') }}"
                                alt="Payment Successful"
                            >
                            <div class="pulse-bg"></div>
                        </div>

                        {{-- Message --}}
                        <div class="main-message">
                            <span class="badge">
                                <i class="fa-regular fa-circle-check"></i>
                                Payment Successful
                            </span>
                            <h1>Thank You!</h1>
                            <p>
                                Your event tickets have been successfully booked.
                                Get ready for an amazing experience!
                            </p>
                        </div>

                        {{-- Booking Details --}}
                        <div class="details-card">
                            <div class="detail-header">
                                <span>BOOKING CONFIRMED</span>
                                <i class="fa-solid fa-thumbs-up"></i>
                            </div>

                            <div class="details-grid">
                                <div>
                                    <i class="fa-solid fa-calendar"></i>
                                    Event Name<br>
                                    <b>{{ $event->title ?? 'Event' }}</b>
                                </div>

                                <div>
                                    <i class="fa-solid fa-users"></i><br>
                                    <b>{{ $booking->qty ?? 1 }} Ticket(s)</b>
                                </div>

                                <div>
                                    <i class="fa-solid fa-ticket"></i>
                                    Ticket Type<br>
                                    <b>{{ $ticketType->title ?? '-' }}</b>
                                </div>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="email-card">
                            <div class="email-header">
                                <i class="fa-solid fa-envelope"></i>
                                <span>Confirmation Details</span>
                            </div>

                            <p>Your tickets and invoice have been sent to:</p>
                            <p class="email">{{ $booking->email ?? '-' }}</p>
                        </div>

                        {{-- Actions --}}
                        <div class="actions">
                            <a href="{{ route('website.events.index') }}"
                               class="btn-md btn-prim hover-prim-outline">
                                <i class="fa-solid fa-champagne-glasses"></i>
                                Explore Events
                            </a>

                            <a href="{{ url('/') }}"
                               class="btn-md btn-lite-outline hover-lite">
                                <i class="fa-solid fa-house"></i>
                                Back to Home
                            </a>
                        </div>

                        {{-- Footer Info --}}
                        <div class="support">
                            <p>Need help? Contact our support team</p>
                            <p class="booking-id">
                                Booking ID: <b>{{ $booking->booking_id ?? '-' }}</b>
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </section>
    </main>

    {{-- Footer --}}
    @include('website._partials.Footer')

@endsection
