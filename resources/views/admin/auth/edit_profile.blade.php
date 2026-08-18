@extends('layouts.admin')

@section('head')
    <title>Edit Profile</title>
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

            <h4 class="hd-lg">Edit Profile</a>
            </h4>

            <form class="needs-validation" action="{{ route('profile.update') }}" novalidate method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Upload Profile image -->
                <div class="mb-4">
                    <div class="upload-box">
                        <div class="previewBox">
                            <img src="{{ $user->profile_picture && file_exists(storage_path('app/public/' . $user->profile_picture)) ? asset('storage/' . $user->profile_picture) : asset('images/defult_user.png') }}"
                                class="preview thumb-img x3">
                            <x-admin.media-remove
                                :exists="filled($user?->profile_picture)"
                                :delete-url="route('admin.media.destroy', ['target' => 'profile-picture'])"
                                label="profile picture" />
                        </div>
                        <div class="mt-2">
                            <input type="file" accept="image/*" name="profile_picture" class="d-none">
                            <button type="button" class="btn-xs btn-sec">
                                <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                Upload Profile pic</button>
                            @error('profile_picture')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Name -->
                    <div class="form-floating">
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name', $user->name) }}" required />
                        <label for="name">Name</label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-floating">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                            value="{{ old('email', $user->email) }}" required />
                        <label for="email">Email</label>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Prefix + Contact No. (prefix added in front of Contact No., like in Ticket Counter) -->
                    @php
                        $storedPrefix = old('mobile_number_prefix', $user->mobile_number_prefix ?? '+353');
                        $storedPrefix = trim((string) $storedPrefix);
                        $storedPrefix = preg_replace('/^\((\+\d{1,4})\)$/', '$1', $storedPrefix);

                        $mobileValue = old('mobile_number', $user->mobile_number ?? '');

                        if (blank($mobileValue) && filled($storedPrefix) && !str_starts_with($storedPrefix, '+')) {
                            $mobileValue = $storedPrefix;
                            $storedPrefix = '+353';
                        }

                        $selectedPrefix = filled($storedPrefix) && str_starts_with($storedPrefix, '+') ? $storedPrefix : '+353';
                    @endphp

                    <div class="d-flex gap-2" >
                        <div class="form-floating flex-shrink-0">
                            <select name="mobile_number_prefix"
                                    class="form-select @error('mobile_number_prefix') is-invalid @enderror"
                                    id="profile_mobile_prefix">
                                @include('admin._partials.options.prefix-options', ['selected' => $selectedPrefix])
                            </select>
                            <label for="profile_mobile_prefix">Prefix</label>
                            @error('mobile_number_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating flex-grow-1">
                            <input type="text" name="mobile_number"
                                   class="form-control @error('mobile_number') is-invalid @enderror"
                                   id="profile_mobile" value="{{ $mobileValue }}"
                                   inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" autocomplete="tel"
                                   title="Mobile number must contain only digits and maximum 12 digits.">
                            <label for="profile_mobile">Contact No.</label>
                            @error('mobile_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec btn-min-w">Update</button>
                </div>

            </form>


            <!-- Back Btn Sec -->
            <div class="d-flex justify-content-end my-5">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>
        </main>
    </section>
@endsection
