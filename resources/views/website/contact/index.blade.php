@extends('layouts.website')

@section('head')
    @if (empty($content?->meta_data))
        <title>Contact Us</title>
    @else
        {!! json_decode($content->meta_data, true) !!}
    @endif

    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/contact.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
@endsection

@section('body')
    @php
        $hasSocialLinks = $content?->social_links?->isNotEmpty() ?? false;
        $hasPhoneNumber = filled($content?->phone_number_1) || filled($content?->phone_number_2);
        $hasContactCards = $hasPhoneNumber || filled($content?->email) || filled($content?->address) || $hasSocialLinks;
    @endphp
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- MAIN BODY -->
    <main>
        @if ($content?->breadcrumb_heading_text)
            @include('website._partials.breadcrumb', [
                'breadcrumb_image_path' => $content?->breadcrumb_image_path,
                'breadcrumb_image_alt' => $content?->breadcrumb_image_alt,
                'breadcrumb_heading_type' => $content?->breadcrumb_heading_type,
                'breadcrumb_heading_text' => $content?->breadcrumb_heading_text,
                'breadcrumb_description' => $content?->breadcrumb_description,
            ])
        @endif

        <!--================================================== ABOUT SECTION ======================================================-->
        @if ($hasContactCards)
        <section class="container-fluid spc-y" id="top-sec">
            <div class="container">
                <div class="grid-archive-3 gap-card">
                    @if ($hasPhoneNumber)
                        <!-- Phone -->
                        <div class="contact-card">
                            <h3 class="hd-title">Phone</h3>
                            @if ($content?->phone_number_1)
                                <a href="tel:{{ $content->phone_prefix_1 }}{{ $content->phone_number_1 }}"
                                    class="content">{{ $content->phone_prefix_1 }} {{ $content->phone_number_1 }}</a>
                            @else
                                <a href="tel:{{ $content->phone_prefix_2 }}{{ $content->phone_number_2 }}"
                                    class="content">{{ $content->phone_prefix_2 }} {{ $content->phone_number_2 }}</a>
                            @endif
                        </div>
                    @endif

                    @if ($content?->email)
                        <!-- Email -->
                        <div class="contact-card">
                            <h3 class="hd-title">Email</h3>
                            <a href="mailto:{{ $content->email }}" class="content" target="_blank"
                                referrer="noopener noreferrer">{{ $content->email }}</a>
                        </div>
                    @endif

                    @if ($content?->address)
                        <!-- Address -->
                        <div class="contact-card">
                            <h3 class="hd-title">Address</h3>
                            <a href="{{ $content->map_link }}" class="content" target="_blank"
                                referrer="noopener noreferrer">
                                {{ $content->address }}
                            </a>
                        </div>
                    @endif

                    @if ($hasSocialLinks)
                        <!-- Social Links -->
                        <div class="contact-card">
                            <h3 class="hd-title">Follow Us</h3>
                            @include('website._partials.social-links')
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @endif


        @if ($content?->map_embed_link)
            <!--================================================== ABOUT SECTION ======================================================-->
            <section style="width:100%; padding: 0;">
                <iframe src="{{ $content?->map_embed_link }}" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade" class="my-map"></iframe>
            </section>
        @endif
    </main>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')
@endsection
