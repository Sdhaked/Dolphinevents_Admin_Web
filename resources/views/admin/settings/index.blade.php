@extends('layouts.admin')

@section('head')
    <title>Settings</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

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

            <h4 class="hd-lg">Settings</h4>
            <form class="needs-validation" novalidate>
                <div class="style-box">
                    <h3 class="hd-sm">PDF Sponser Image</h3>
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                            <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="5d4fvfd5">PDF Sponser Image</label>
                            <input type="file" class="form-control mt-1" id="5d4fvfd5" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <div class="style-box">
                    <h3 class="hd-sm">Mini Settings</h3>
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Company -->
                        <div class="form-floating">
                            <input type="text" class="form-control" id="company" required="">
                            <label for="company">Company Name</label>
                        </div>

                        <!-- Base Country -->
                        <div class="form-floating">
                            <select class="form-select" id="selectit" required="">
                                @include('admin._partials.options.countries-options')
                            </select>
                            <label for="selectit">Country</label>
                        </div>

                        <div>
                            <button type="button" class="check-btn">
                                <input class="form-check-input" type="checkbox" value="select all" id="447dU">
                                <label for="447dU"> Access To Super Admin: <span class="orange">Permanent Delete</span>,
                                    <span class="red">Empty Trash</span></label>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec btn-min-w">Save</button>
                </div>
            </form>

            <hr class="mt-5">
            <!-- SMTP FORM -->
            <div class="style-box mt-5">
                <h3 class="hd-sm">Email Templet SMTP Settings</h3>

                <div>
                    <div class="form-floating" style="max-width: 20rem;">
                        <select class="form-select" id="selectit" required="">
                            <option selected>Ticket Sale</option>
                            <option>Event Reminder</option>
                            <option>Checker App Login</option>
                            <option>Admin Forgot Password</option>
                        </select>
                        <label for="selectit">Email Templet</label>
                    </div>
                </div>
                <form class="needs-validation grid-2 grid-sm-1 mt-4" novalidate>
                    <!-- Host -->
                    <div class="form-floating">
                        <input type="email" class="form-control" id="host" required="">
                        <label for="host">Host</label>
                    </div>

                    <!-- CC -->
                    <div class="form-floating">
                        <input type="email" class="form-control" id="CC" required="">
                        <label for="CC">CC</label>
                    </div>

                    <!-- set From -->
                    <div class="form-floating">
                        <input type="email" class="form-control" id="setfrom" required="">
                        <label for="setfrom">Set From</label>
                    </div>

                    <!-- Port -->
                    <div class="form-floating">
                        <input type="number" class="form-control" id="Port" required="">
                        <label for="Port">Port</label>
                    </div>

                    <!-- Username -->
                    <div class="form-floating">
                        <input type="email" class="form-control" id="username" required="">
                        <label for="username">Username</label>
                    </div>

                    <!-- Password -->
                    <div class="passBox">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="pass">
                            <label for="pass">Password</label>
                        </div>
                        <button type="button" class="input-group-text pass-eye">
                            <i class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>

                    <div>
                        <button type="submit" class="btn-md btn-sec btn-min-w">Save</button>
                    </div>
                </form>
            </div>
        </main>
    </section>
@endsection
