@extends('layouts.admin')

@php
    $isEdit = $isEdit ?? $contestent->exists;
    $pageTitle = $isEdit ? 'Edit Contestent' : 'Create Contestent';
    $formAction = $isEdit
        ? route('admin.contestents.update', $contestent->id)
        : route('admin.contestents.store');
    $submitText = $isEdit ? 'Update' : 'Submit';
    $socialLinks = $contestent->social_links ?? [];
    $imageUrl = $isEdit && $contestent->image
        ? asset('storage/' . $contestent->image)
        : asset('images/uploadimg.svg');
@endphp

@section('head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageTitle }} for {{ $event->title }}.">

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

    <!-- MAIN CONTENT -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb', ['breadcrumb_title' => $pageTitle])

            <h5 class="hd-lg">{{ $pageTitle }}</h5>

            <form class="needs-validation grid-1 gap-card" id="contestentForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Contestent Detail</h5>

                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            @if ($isEdit)
                                <x-admin.media-remove
                                    :exists="filled($contestent?->image)"
                                    :delete-url="route('admin.media.destroy', ['target' => 'contestent-image', 'id' => $contestent->id])"
                                    label="contestent image" />
                            @else
                                <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                            @endif
                            <img src="{{ $imageUrl }}" class="preview thumb-img x3" alt="{{ $isEdit ? $contestent->name : 'upload' }}">
                        </div>
                        <div class="mt-4">
                            <input type="file" name="image" class="form-control mt-1" id="contestentImage" accept="image/*" data-max-file-size-kb="5120" {{ $isEdit ? '' : 'required' }}>
                            <label for="contestentImage">
                                {{ $isEdit ? 'Change Candidate Image' : 'Upload Candidate Image' }}
                                @if (!$isEdit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            @if (!$isEdit)
                                <div class="invalid-feedback">Image is required.</div>
                            @endif
                            @error('image')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="text" name="name" class="form-control" id="contestentName" value="{{ old('name', $contestent->name) }}" required>
                        <label for="contestentName">Name <span class="text-danger">*</span></label>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="contestentEmail" value="{{ old('email', $contestent->email) }}">
                        <label for="contestentEmail">Email</label>
                        @error('email')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="phone_prefix" id="contestentPhonePrefix">
                                @include('admin._partials.options.prefix-options', ['selected' => old('phone_prefix', $contestent->phone_prefix ?? '+91')])
                            </select>
                            <label for="contestentPhonePrefix">Prefix?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="tel" name="phone_number" class="form-control" id="contestentPhone" value="{{ old('phone_number', $contestent->phone_number) }}" inputmode="numeric" minlength="4" maxlength="12" pattern="[0-9]{4,12}">
                            <label for="contestentPhone">Phone No</label>
                            <div class="invalid-feedback">Phone number must contain only numbers and be between 4 and 12 digits.</div>
                            @error('phone_number')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="hd-sm">Social Links</h5>
                        <ul class="social-list">
                            @for ($i = 0; $i < 4; $i++)
                                <li>
                                    <select class="form-select" name="platform[]">
                                        @include('admin._partials.options.social-options', ['selected' => old("platform.$i", $socialLinks[$i]['platform'] ?? '')])
                                    </select>
                                    <input type="url" name="url[]" class="form-control" placeholder="Social Link" value="{{ old("url.$i", $socialLinks[$i]['url'] ?? '') }}">
                                </li>
                                @error("platform.$i")
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                @error("url.$i")
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endfor
                        </ul>
                        @error('social_links')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec" id="saveBtn">{{ $submitText }}</button>
                    <button class="btn-md btn-sec d-none" type="button" id="saveBtnLoader" disabled>
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <span role="status">{{ $submitText }}</span>
                    </button>
                </div>
            </form>
        </main>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('contestentForm');
            const saveBtn = document.getElementById('saveBtn');
            const saveBtnLoader = document.getElementById('saveBtnLoader');
            const selects = document.querySelectorAll('select[name="platform[]"]');
            const inputs = document.querySelectorAll('input[name="url[]"]');

            function validateSocialPair(select, input) {
                select.classList.remove('border-danger');
                input.classList.remove('border-danger');

                input.required = false;
                select.required = false;

                if (select.value && !input.value.trim()) {
                    input.classList.add('border-danger');
                    input.required = true;
                }

                if (input.value.trim() && !select.value) {
                    select.classList.add('border-danger');
                    select.required = true;
                }
            }

            function validateSocialPairs() {
                selects.forEach((select, index) => {
                    const input = inputs[index];
                    if (input) validateSocialPair(select, input);
                });
            }

            function updateSocialOptions() {
                const selectedValues = Array.from(selects)
                    .map(select => select.value)
                    .filter(value => value !== '');

                selects.forEach((select) => {
                    const currentValue = select.value;

                    Array.from(select.options).forEach((option) => {
                        if (option.value === '') return;

                        option.disabled = selectedValues.includes(option.value) && option.value !== currentValue;
                    });
                });
            }

            selects.forEach((select, index) => {
                const input = inputs[index];
                if (!input) return;

                select.addEventListener('change', () => {
                    validateSocialPair(select, input);
                    updateSocialOptions();
                });

                input.addEventListener('input', () => validateSocialPair(select, input));
                validateSocialPair(select, input);
            });

            updateSocialOptions();

            form.addEventListener('submit', function (event) {
                validateSocialPairs();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }

                saveBtn.classList.add('d-none');
                saveBtnLoader.classList.remove('d-none');
            });
        });
    </script>
@endsection
