@extends('layouts.admin')

@section('head')
    <title>Event Archive Page</title>
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

            <h4 class="hd-lg">Event Archive Page Content</h4>

            <form action="{{ route('admin.pages.event_archive.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <!-- Breadcrumb -->
                <div class="style-box grid-1 gap-card">
                    <h5 class="hd-sm mb-0">Breadcrumb</h5>
                    <!-- Background Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($content?->breadcrumb_image_path)"
                                :delete-url="route('admin.media.destroy', ['target' => 'event-archive-breadcrumb-image'])"
                                label="event archive breadcrumb image" />
                            <img src="{{ $content?->breadcrumb_image_path ? asset('storage/' . $content->breadcrumb_image_path) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="5d4fvfd5">Upload Background Image</label>
                            <div class="invalid-feedback">Image is required!!</div>

                            <input type="file" name="breadcrumb_image_path" class="form-control mt-1" id="fffdf" accept="image/*">
                            <div class="label-spc mt-1">
                                <input type="text" name="breadcrumb_image_alt" class="form-control" placeholder="Alt Text" value="{{ $content?->breadcrumb_image_alt ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" id="dv8" name="breadcrumb_heading_type">
                                @include('admin._partials.options.hd-options', ['selected' => $content?->breadcrumb_heading_type])
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
@endsection
