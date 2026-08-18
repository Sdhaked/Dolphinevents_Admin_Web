@extends('layouts.admin')

@section('head')
    <title>About Page Content</title>
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

            <h5 class="hd-lg">About Page Content</h5>

            @if (session('success'))
                <script>
                    window.addEventListener('load', function() {
                        createNotification("success", "{{ session('success') }}", "");
                    });
                </script>
            @endif

            <form action="{{ route('admin.pages.about.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <!-- Breadcrumb -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Breadcrumb</h5>
                    <!-- Background Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($content?->breadcrumb_image_path)"
                                :delete-url="route('admin.media.destroy', ['target' => 'about-breadcrumb-image'])"
                                label="breadcrumb image" />
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
                            <select class="form-select" id="dv8" name="breadcrumb_heading_type">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->breadcrumb_heading_type ?? ''])
                            </select>
                            <label for="dv8">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="breadcrumb_heading_text" class="form-control" id="sdvd5" value="{{ $content?->breadcrumb_heading_text ?? '' }}">
                            <label for="sdvd5">Heading 1 *</label>
                        </div>
                    </div>

                    <div>
                        <div class="form-floating">
                            <textarea class="form-control" name="breadcrumb_description" style="height: 7rem">{{ $content?->breadcrumb_description ?? '' }}</textarea>
                            <label>Short Description</label>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">About Section</h5>

                    <!-- Featured Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($content?->about_featured_image_path)"
                                :delete-url="route('admin.media.destroy', ['target' => 'about-featured-image'])"
                                label="about featured image" />
                            <img src="{{ $content?->about_featured_image_path ? asset('storage/' . $content->about_featured_image_path) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="fffdf">Upload Featured Image</label>
                            <div class="invalid-feedback">Image is required!!</div>

                            <input type="file" name="about_featured_image_path" class="form-control mt-1" id="fffdf" accept="image/*">
                            <div class="label-spc mt-1">
                                <input type="text" name="about_featured_image_alt" value="{{ $content->about_featured_image_alt ?? '' }}" class="form-control" placeholder="Alt Text">
                            </div>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" id="dv8" name="about_heading_type">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->about_heading_type ?? ''])
                            </select>
                            <label for="dv8">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="about_heading_text" value="{{ $content->about_heading_text ?? '' }}" class="form-control" id="sdvd5">
                            <label for="sdvd5">Main Heading *</label>
                        </div>
                    </div>

                    <div>
                        <div style="margin: 1rem 0">
                            @include('admin._partials.mini-editor-tags')
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" name="about_description" id="about_description"
                                style="height: 15rem" onblur="processDescription(event)">{{ $content->about_description ?? '' }}</textarea>
                            <label>About US</label>
                        </div>
                        <input type="hidden" name="about_processed_description" id="about_processed_description">
                    </div>
                </div>

                <!-- Owner -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Owner Section</h5>
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Owner Image 1 -->
                        <div class="label-spc upload-box">
                            <div class="previewBox mt-2">
                                <x-admin.media-remove
                                    :exists="filled($content?->owner_image_1_path)"
                                    :delete-url="route('admin.media.destroy', ['target' => 'about-owner-image-1'])"
                                    label="owner image 1" />
                                <img src="{{ $content?->owner_image_1_path ? asset('storage/' . $content->owner_image_1_path) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                            </div>
                            <div class="mt-4">
                                <label for="sdvfv">Upload Owner Image 1</label>
                                <div class="invalid-feedback">Image is required!!</div>

                                <input type="file" name="owner_image_1_path" class="form-control mt-1" id="sdvfv" accept="image/*">
                                <div class="label-spc mt-1">
                                    <input type="text" name="owner_image_1_alt" value="{{ $content->owner_image_1_alt ?? '' }}" class="form-control" placeholder="Alt Text">
                                </div>
                            </div>
                        </div>

                        <!-- Owner Image 2 -->
                        <div class="label-spc upload-box">
                            <div class="previewBox mt-2">
                                <x-admin.media-remove
                                    :exists="filled($content?->owner_image_2_path)"
                                    :delete-url="route('admin.media.destroy', ['target' => 'about-owner-image-2'])"
                                    label="owner image 2" />
                                <img src="{{ $content?->owner_image_2_path ? asset('storage/' . $content->owner_image_2_path) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                            </div>
                            <div class="mt-4">
                                <label for="dfb4">Upload Owner Image 2</label>
                                <div class="invalid-feedback">Image is required!!</div>

                                <input type="file" name="owner_image_2_path" class="form-control mt-1" id="dfb4" accept="image/*">
                                <div class="label-spc mt-1">
                                    <input type="text" name="owner_image_2_alt" value="{{ $content->owner_image_2_alt ?? '' }}" class="form-control" placeholder="Alt Text">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" id="dv8" name="owner_heading_1_type">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->owner_heading_1_type ?? ''])
                            </select>
                            <label for="dv8">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="owner_heading_1_text" value="{{ $content->owner_heading_1_text ?? '' }}" class="form-control" id="sdvd5">
                            <label for="sdvd5">Heading 1</label>
                        </div>
                    </div>

                    <!-- Title 2 -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" id="fgb5" name="owner_heading_2_type">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->owner_heading_2_type ?? ''])
                            </select>
                            <label for="fgb5">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="owner_heading_2_text" value="{{ $content->owner_heading_2_text ?? '' }}" class="form-control" id="vvv7">
                            <label for="vvv7">Heading 2</label>
                        </div>
                    </div>

                    <div>
                        <div style="margin: 1rem 0">
                            @include('admin._partials.mini-editor-tags')
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" name="owner_description" id="owner_description"
                                style="height: 15rem" onblur="processDescription(event)">{{ $content->owner_description ?? '' }}</textarea>
                            <label>About US</label>
                        </div>
                        <input type="hidden" name="owner_processed_description" id="owner_processed_description">
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
        function processDescription(e) {
            e.preventDefault();
            const about_description = document.getElementById('about_description').value;
            document.getElementById('about_processed_description').value = HTMLProcesser(about_description);


            const description = document.getElementById('owner_description').value;
            document.getElementById('owner_processed_description').value = HTMLProcesser(description);
        }
    </script>
@endsection
