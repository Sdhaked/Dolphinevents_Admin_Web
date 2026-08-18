@extends('layouts.admin')

@section('head')
    <title>Tickets Page</title>
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

            <h4 class="hd-lg">Tickets Page Content</h4>

            @if (session('success'))
                <script>
                    window.addEventListener('load', function() {
                        createNotification("success", "{{ session('success') }}", "");
                    });
                </script>
            @endif

            <form action="{{ route('admin.pages.tickets.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
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