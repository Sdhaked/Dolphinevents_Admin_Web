@extends('layouts.admin')

@section('head')
    <title>Contact</title>
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

            <h4 class="hd-lg">Contact Details</h4>

            <form action="{{ route('admin.pages.contact.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <!-- Breadcrumb -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Breadcrumb</h5>
                    <!-- Background Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($content?->breadcrumb_image_path)"
                                :delete-url="route('admin.media.destroy', ['target' => 'contact-breadcrumb-image'])"
                                label="contact breadcrumb image" />
                            <img src="{{ $content?->breadcrumb_image_path ? asset('storage/' . $content->breadcrumb_image_path) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="5d4fvfd5">Upload Background Image</label>
                            <div class="invalid-feedback">Image is required!!</div>

                            <input type="file" name="breadcrumb_image_path" class="form-control mt-1" id="fffdf" accept="image/*">
                            <div class="label-spc mt-1">
                                <input type="text" name="breadcrumb_image_alt" value="{{ $content->breadcrumb_image_alt ?? '' }}" class="form-control" placeholder="Alt Text">
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="breadcrumb_heading_type" id="dv8">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->breadcrumb_heading_type ?? ''])
                            </select>
                            <label for="dv8">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="breadcrumb_heading_text" class="form-control" id="sdvd5" value="{{ $content?->breadcrumb_heading_text ?? '' }}">
                            <label for="sdvd5">Title *</label>
                        </div>
                    </div>

                    <div>
                        <div class="form-floating">
                            <textarea class="form-control" name="breadcrumb_description" style="height: 7rem">{{ $content?->breadcrumb_description ?? '' }}</textarea>
                            <label>Short Description</label>
                        </div>
                    </div>
                </div>

                <!-- Site Contact Details -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Site Contact Detail</h5>
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="phone_prefix_1" id="phone_prefix_1">
                                @include('admin._partials.options.prefix-options', ['selected' => $content?->phone_prefix_1 ?? ''])
                            </select>
                            <label for="phone_prefix_1">Prefix?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="phone" name="phone_number_1" class="form-control" id="phone_number_1" value="{{ $content?->phone_number_1 ?? '' }}">
                            <label for="myphone">Phone No *</label>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="phone_prefix_2" id="phone_prefix_2">
                                @include('admin._partials.options.prefix-options', ['selected' => $content?->phone_prefix_2 ?? ''])
                            </select>
                            <label for="phone_prefix_2">Prefix?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="phone" name="phone_number_2" class="form-control" id="phone_number_2" value="{{ $content?->phone_number_2 ?? '' }}">
                            <label for="myphone">Phone No *</label>
                        </div>
                    </div>


                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="myemail" value="{{ $content?->email ?? '' }}">
                        <label for="myemail">Email</label>
                    </div>

                    <div class="form-floating">
                        <textarea class="form-control" name="address" style="height: 100px">{{ $content?->address ?? '' }}</textarea>
                        <label>Address</label>
                    </div>

                    <div class="form-floating">
                        <input type="url" name="map_link" class="form-control" id="mylink" value="{{ $content?->map_link ?? '' }}">
                        <label for="mylink">Map Link</label>
                    </div>

                    <div class="form-floating">
                        <input type="url" name="map_embed_link" class="form-control" id="dfgf" value="{{ $content?->map_embed_link ?? '' }}">
                        <label for="dfgf">Map Embed Link</label>
                    </div>

                    <!-- Social Box -->
                    <div class="mt-4">
                        <h5 class="hd-sm">Social Links</h5>
                        <ul class="social-list">
                            @for ($i = 1; $i <= 4; $i++)
                                @php
                                    $link = $social[$i - 1] ?? null;
                                @endphp
                                <li>
                                    <input type="hidden" name="social_link_id[{{ $i }}]" value="{{ $link?->id }}">
                                    <select class="form-select" name="platform[{{ $i }}]">
                                        @include('admin._partials.options.social-options', ['selected' => $link?->platform ?? ''])
                                    </select>

                                    <input type="url" name="url[{{ $i }}]" class="form-control" placeholder="Social Link" value="{{ $link?->url ?? '' }}">
                                </li>
                            @endfor
                        </ul>
                    </div>
                </div>

                <!-- SEO -->
                <div class="style-box">
                    <h4 class="hd-sm">SEO Settings</h4>

                    <div class="grid-1 gap-card">
                        <!-- Meta Field -->
                        <div class="form-floating">
                            <textarea class="form-control metabox" name="meta_data" id="metabox">{{ json_decode($content?->meta_data, true) ?? '' }}</textarea>
                            <label for="metabox">Meta Box</label>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec btn-min-w">Save</button>
                </div>
            </form>
        </main>
    </section>

    <script>
        /**
         * Social Pair Validation
         */
        const selects = document.querySelectorAll('select[name^="platform"]');
        const inputs = document.querySelectorAll('input[name^="url"]');

        selects.forEach((select, index) => {
            const input = inputs[index];

            select.addEventListener('change', () => {
                validatePair(select, input);
                updateSelectOptions();
            });
            input.addEventListener('input', () => validatePair(select, input));
            validatePair(select, input);
        });

        function validatePair(select, input) {
            // remove previous error classes
            select.classList.remove('border-danger');
            input.classList.remove('border-danger');

            // Social URL is optional. Only require platform when a URL is typed.
            input.required = false;
            select.required = false;
            input.removeAttribute('required');
            select.removeAttribute('required');

            // If URL is present, platform is needed to save/create that row.
            if (input.value.trim() && !select.value) {
                select.classList.add('border-danger');
                select.required = true;
            }
        }

        function updateSelectOptions() {
            // collect all selected values
            const selectedValues = Array.from(selects)
                .map(s => s.value)
                .filter(v => v !== "");

            // for each select, disable options that are already selected elsewhere
            selects.forEach(select => {
                const currentValue = select.value;

                Array.from(select.options).forEach(option => {
                    if (option.value === "") return; // skip placeholder

                    if (selectedValues.includes(option.value) && option.value !== currentValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }

        updateSelectOptions();
    </script>
@endsection
