@extends('layouts.admin')

@section('head')
    <title>Home Page Content</title>
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

            <h5 class="hd-lg">Home Page Content</h5>

            <form action="{{ route('admin.pages.home.store') }}" method="POST" class="needs-validation" novalidate
                enctype="multipart/form-data">
                @csrf
                <div class="style-box">
                    <h3 class="hd-sm">Show Image Slider / Video on Main page Hero Section?</h3>
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="show_what" id="slider" value="slider"
                                {{ ($content?->show_what ?? 'slider') == 'slider' ? 'checked' : '' }}>
                            <label for="slider">Image Slider</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="show_what" id="radio2" value="video"
                                {{ ($content?->show_what ?? 'slider') == 'video' ? 'checked' : '' }}>
                            <label for="radio2">Video</label>
                        </div>
                    </div>

                    <!-- Featured Video -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($content?->hero_video_path)"
                                :delete-url="route('admin.media.destroy', ['target' => 'home-hero-video'])"
                                label="hero video" />

                            <video width="320" height="240" class="preview" controls playsinline loop autoplay
                                src="{{ $content?->hero_video_path ? asset('storage/' . $content->hero_video_path) : asset('images/uploadimg.svg') }}"
                                aria-label="Event intro video" poster="{{ asset('images/uploadvideo.svg') }}"></video>
                        </div>
                        <div class="mt-4">
                            <label for="ds5v8d">Upload Hero Sec Video</label>
                            <input type="file" data-max-file-size-kb="5000" name="hero_video_path"
                                class="form-control mt-1" id="ds5v8d" accept="video/*">
                        </div>
                    </div>
                </div>

                <div class="style-box grid-1 gap-card">
                    <div>
                        <h4 class="hd-sm">Mini About Section</h4>

                        <!-- Featured Image -->
                        <div class="label-spc upload-box">
                            <div class="previewBox mt-2">
                                <x-admin.media-remove
                                    :exists="filled($content?->about_image_path)"
                                    :delete-url="route('admin.media.destroy', ['target' => 'home-about-image'])"
                                    label="home about image" />
                                <img src="{{ $content?->about_image_path ? asset('storage/' . $content->about_image_path) : asset('images/uploadimg.svg') }}"
                                    class="preview thumb-img x3">
                            </div>
                            <div class="mt-4">
                                <label for="fffdf">Upload About Image</label>

                                <input type="file" name="about_image_path" class="form-control mt-1" id="fffdf"
                                    accept="image/*">
                                <div class="label-spc mt-1">
                                    <input type="text" name="about_image_alt" class="form-control" placeholder="Alt Text"
                                        value="{{ $content?->about_image_alt ?? '' }}">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Heading -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="about_heading_type_1" id="dv8">
                                @include('admin._partials.options.hd-options', [
                                    'selected' => $content->about_heading_type_1 ?? '',
                                ])
                            </select>
                            <label for="dv8">Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="about_heading_text_1" class="form-control" id="sdvd5"
                                value="{{ $content->about_heading_text_1 ?? '' }}">
                            <label for="sdvd5">Main Heading *</label>
                        </div>
                    </div>

                    <!-- Sub Heading -->
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="about_heading_type_2" id="ds58">
                                @include('admin._partials.options.hd-options', [
                                    'selected' => $content->about_heading_type_2 ?? '',
                                ])
                            </select>
                            <label for="ds58">Sub Hd type?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="text" name="about_heading_text_2" class="form-control" id="dfv54"
                                value="{{ $content->about_heading_text_2 ?? '' }}">
                            <label for="dfv54">Main Heading *</label>
                        </div>
                    </div>

                    <div>
                        <div style="margin: 1rem 0">
                            @include('admin._partials.mini-editor-tags')
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" name="about_description" id="about_description" style="height: 15rem"
                                onblur="processDescription(event)">{{ $content->about_description ?? '' }}</textarea>
                            <label>Discription</label>
                        </div>
                    </div>
                    <input type="hidden" name="about_processed_description" id="about_processed_description">
                </div>


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
            const description = document.getElementById('about_description').value;
            document.getElementById('about_processed_description').value = HTMLProcesser(description);
        }
    </script>
@endsection
