@extends('layouts.website')

@section('head')
    @if (empty($content?->meta_data))
        <title>Terms & Conditions</title>
    @else
        {!! json_decode($content->meta_data, true) !!}
    @endif

    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/documents.css') }}" />

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
@endsection

@section('body')
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    @if ($content?->breadcrumb_heading_text)
        <!--########## 🥗 BREADCRUMB 🥗 ##########-->
        @include('website._partials.breadcrumb', [
            'breadcrumb_image_path' => $content?->breadcrumb_image_path,
            'breadcrumb_image_alt' => $content?->breadcrumb_image_alt,
            'breadcrumb_heading_type' => $content?->breadcrumb_heading_type,
            'breadcrumb_heading_text' => $content?->breadcrumb_heading_text,
            'breadcrumb_description' => $content?->breadcrumb_description,
        ])
    @endif

    <!-- MAIN BODY -->
    <main>
        <!--================================================== T&C SECTION ======================================================-->
        @if (filled(trim(strip_tags($content?->processed_main_content ?? ''))))
        <section class="container-fluid spc-y">
            <div class="container">
                @if ($content)
                    <span class="update-date">Last Updated: {{ $content?->updated_at?->format('d/m/Y') }}</span>
                    {!! $content?->processed_main_content !!}
                @endif
            </div>
        </section>
        @endif
    </main>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')
@endsection
