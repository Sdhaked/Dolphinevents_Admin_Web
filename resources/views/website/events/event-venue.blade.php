@extends('layouts.website')

@section('head')
    @include('website._partials.head.meta-data', ['metaData' => $event?->meta_data, 'fallbackTitle' => 'Event Venue layout'])
    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
</head>
@endsection

@section('body')
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- MAIN BODY -->
    <main>
        <!--==================================================
                  Event Detail SECTION
        ======================================================-->
        <section class="container-fluid spc-y-half main-sec">
            <div class="container">
                <!-- Back Box -->
                <div class="back-box">
                    <button class="btm-md btn-link" onclick="history.back()"> <i
                            class="fa-solid fa-arrow-left-long i-mr"></i>Back</button>
                </div>
                <!-- Header -->
                <div class="mb-prim all-text-center" data-aos="fade-up">
                    <h3 class=" hd-prim">Venue Layout</h3>
                </div>

                <!-- Main Content -->
                @if ($event?->venue_layout_image)
                <div style="text-align: center;">
                    <img src="{{ asset('storage/' . $event?->venue_layout_image) }}"
                                            alt="{{ $event?->venue_layout_image_alt_text }}"
                        loading="lazy" decoding="async" data-aos="zoom-in"
                        style="max-width: 60rem; margin-auto; border-radius: var(--radius-prim);" />
                </div>
                @endif

                <!-- Footer -->
                <div class="next-box">
                    <a href="{{ route('website.events.event_tickets', $event->slug) }}">
                        <button type="button" class="btn-md btn-prim hover-prim-outline">
                            Next <i class="fa-solid fa-arrow-right-long i-ml"></i>
                        </button>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- ####### FOOTER ####### -->
  
    @include('website._partials.Footer')
    
@endsection
