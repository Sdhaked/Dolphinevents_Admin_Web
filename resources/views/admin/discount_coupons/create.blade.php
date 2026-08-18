@extends('layouts.admin')

@section('head')
    <title>Create Discount Coupon</title>
    <meta name="description" content="Create a new discount coupon for your event.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    <!-- PRELOADER -->
    @include('admin._partials.preloader')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <h5 class="hd-lg">Create Discount Coupon</h5>
            <form action="{{ route('admin.discount.coupons.store') }}" method="POST" novalidate=""
                class="grid-1 gap-card needs-validation">
                @csrf

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Title -->
                    <div class="form-floating">
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               id="title" value="{{ old('title') }}" required>
                        <label for="title">Title*</label>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Coupon Code -->
                    <div class="form-floating">
                        <input type="text" name="coupon_code" class="form-control @error('coupon_code') is-invalid @enderror"
                               id="coupon_code" value="{{ old('coupon_code') }}" oninput="this.value = this.value.toUpperCase()" required>
                        <label for="coupon_code">Coupon Code*</label>
                        @error('coupon_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Associate Name -->
                    <div class="form-floating">
                        <input type="text" name="associate_name" class="form-control @error('associate_name') is-invalid @enderror"
                               id="associate_name" value="{{ old('associate_name') }}" required>
                        <label for="associate_name">Associate Name*</label>
                        @error('associate_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Discount -->
                    <div class="input-group mb-3">
                        <div class="form-floating flex-grow-1">
                            <input type="number" name="discount" class="form-control @error('discount') is-invalid @enderror"
                                   id="discount" step="0.01" min="0" max="100" value="{{ old('discount') }}" required>
                            <label for="discount">Discount*</label>
                            @error('discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <!-- Also Associate -->
                <div class="form-floating">
                    <textarea name="also_associate" class="form-control @error('also_associate') is-invalid @enderror"
                              style="height: 100px" id="also_associate">{{ old('also_associate') }}</textarea>
                    <label for="also_associate">About Associate</label>
                    @error('also_associate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Apply to Ticket Type -->
                <div class="style-box">
                    <h6 class="hd-sm mb-3">Apply to Ticket Type</h6>
                    @error('ticket_type_ids')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div class="row">
                        @forelse($ticketTypes as $ticketType)
                            <div class="col-md-4 col-sm-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ticket_type_ids[]"
                                           value="{{ $ticketType->id }}" id="ticket_{{ $ticketType->id }}"
                                           {{ in_array($ticketType->id, old('ticket_type_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ticket_{{ $ticketType->id }}">
                                        {{ $ticketType->title }}
                                    </label>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted">No ticket types available for this event.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec">Submit</button>
                </div>
            </form>
        </main>
    </section>
@endsection
