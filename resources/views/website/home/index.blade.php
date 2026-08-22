@extends('layouts.website')

@section('head')
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @if (empty($content?->meta_data))
        <title>Dolphinevent</title>
    @else
        {!! json_decode($content->meta_data, true) !!}
    @endif

    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Owl carousel v.2.3.4 CSS link -->
    <link rel="stylesheet" href="{{ asset('website/style/swiper-bundle.min.css') }}" />
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" /> -->

    <!-- gallery CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/jquery.fancybox.css') }}" />

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/home.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Owl carousel v.2.3.4 JS CDN link -->
    <script src="{{ asset('website/js/swiper-bundle.min.js') }}" defer></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script> -->

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- gallery CDN -->
    <script src="{{ asset('website/js/jquery.fancybox.min.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
    <script src="{{ asset('website/js/page-js/home.js') }}" defer></script>
    <script>
        window.HOME_GALLERY = {
            loadMoreUrl: "{{ route('website.home.gallery.load_more') }}",
            initialCount: {{ $content?->gallery->count() ?? 0 }},
            totalCount: {{ $content?->gallery_total ?? 0 }},
            perPage: 8
        };
        window.HOME_PAST_EVENTS = {
            loadMoreUrl: "{{ route('website.home.past_events.load_more') }}",
            initialCount: {{ $content?->past_events->count() ?? 0 }},
            totalCount: {{ $content?->past_events_total ?? 0 }},
            perPage: 8
        };
    </script>
@endsection

@section('body')
    @php
        $hasHeroSlider = $content?->show_what === 'slider' && ($content?->hero_slider?->isNotEmpty() ?? false);
        $hasHeroVideo = $content?->show_what === 'video' && filled($content?->hero_video_path);
        $hasActiveTodayEvents = $content?->active_today_events?->isNotEmpty() ?? false;
        $hasUpcomingEvents = $content?->upcoming_events?->isNotEmpty() ?? false;
        $hasPastEvents = $content?->past_events?->isNotEmpty() ?? false;
    @endphp
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- MAIN BODY -->
    <main>
        <!--==================================================
                                                               HERO SECTION
                                                        ======================================================-->
        @if ($hasHeroSlider || $hasHeroVideo)
            <section style="width:100%">
            @if ($hasHeroSlider)
                <!-- Slider container -->
                <div class="swiper heroSwiper">
                    <div class="swiper-wrapper">
                        @foreach($content->hero_slider as $slider)
                            <div class="swiper-slide">
                                <img src="{{ asset('storage/' . $slider->image) }}" alt="{{ $slider->alt_text }}"
                                    loading="lazy" decoding="async" />
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            @elseif($hasHeroVideo)
                <!-- Video Container (Display if Video is active from panel) -->
                <video style="width:100%; height:auto" autoplay muted loop plays-inline>
                    <source src="{{ asset('storage/' . $content->hero_video_path) }}" type="video/mp4" preload="auto"
                        poster="{{ $content->hero_video_poster ? asset('storage/' . $content->hero_video_poster) : '' }}"
                        title="Welcome to Dolphinevent">
                </video>
            @endif
            </section>
        @endif

        @if ($content->featured_events->count() > 0)
            <!--==================================================
                                                               FEATURED EVENTS SECTION
                                                        ======================================================-->
            <section class="container-fluid spc-y">
                <div class="container">
                    <div class="mb-prim">
                        <h3 class="hd-prim">Our Top <span class="text-prim">{{ $content->featured_events->count() }}</span>
                            Events</h3>
                    </div>
                    <div class="grid-archive-4 gap-card">
                        @foreach ($content->featured_events as $event)
                            <x-website.event-archive-card :event="$event" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($content?->about_heading_text_1 && $content?->about_processed_description && $content?->about_image_path)
            <!--==================================================
                                                               MINI ABOUT SECTION
                                                        ======================================================-->
            <section class="container-fluid spc-y bg-devider mini-about-sec">
                <div class="container">
                    <div class="grid-sec-40-60 gap-col">
                        <div>
                            <div class="img-holder">
                                <img src="{{ asset('storage/' . $content->about_image_path) }}"
                                    alt="{{ $content->about_image_alt }}" loading="lazy" decoding="async" />
                                <span></span>
                            </div>
                        </div>
                        <div>
                            <div class="mb-prim">
                                <{{ $content->about_heading_type_1 ?? 'h3' }} class="hd-prim">{{ $content->about_heading_text_1 }}
                                    </{{ $content->about_heading_type_1 ?? 'h3' }}>
                                    <{{ $content->about_heading_type_2 ?? 'h3' }} class="hd-big text-prim">
                                        {{ $content->about_heading_text_2 }}
                                        </{{ $content->about_heading_type_2 ?? 'h3' }}>
                                        <!-- Description -->
                                        {!! $content->about_processed_description !!}

                                        <a href="{{ route('website.about.index') }}" role="button"
                                            class="btn-sm btn-lite-outline hover-prim mt-spc">Know more
                                            <i class="fa-solid fa-arrow-right-long i-ml"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($content?->info_slider->count() > 0)
            <!--==================================================
                                                               INFO SLIDER SECTION
                                                    ======================================================-->
            <section class="container-fluid spc-y">
                <div class="container">
                    <!-- Slider container -->
                    <div class="swiper infoSlider">
                        <div class="swiper-wrapper">
                            @foreach ($content->info_slider as $slide)
                                @php
                                    $slideImage = asset('storage/' . $slide->image);
                                    $slideAltText = $slide->alt_text ?: 'Info slider image';
                                @endphp
                                <div class="swiper-slide">
                                    @if ($slide->url)
                                        <a href="{{ $slide->url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $slideAltText }}">
                                            <img src="{{ $slideImage }}" alt="{{ $slideAltText }}" loading="lazy" decoding="async" />
                                        </a>
                                    @else
                                        <img src="{{ $slideImage }}" alt="{{ $slideAltText }}" loading="lazy" decoding="async" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!--==================================================
                                                               ACTIVE EVENTS SLIDER SECTION
                                                    ======================================================-->
        @if ($hasActiveTodayEvents)
        <section class="container-fluid spc-y active-events-sec">
            <div class="container">
                <div class="gap-card mb-prim" style="display:grid; grid-template-columns: 1fr auto;">
                    <div>
                        <h3 class="hd-prim">Todays Events</h3>
                        <p>Stay up-to-date with exciting events happening right now! Explore details, grab tickets, and never miss a moment.</p>
                    </div>
                    <div class="today-date-box">
                        <h4 class="hd-big js-todays-date"></h4>
                    </div>
                </div>
                <!-- Slider container -->
                <div class="swiper activeEventsSlider">
                    <div class="swiper-wrapper">
                        @foreach ($content->active_today_events as $event)
                            <div class="swiper-slide">
                                <x-website.event-archive-card :event="$event" />
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>
        @endif

        <!--==================================================
                                                              UPCOMING EVENTS SECTION
                                                        ======================================================-->
        @if ($hasUpcomingEvents)
        <section class="container-fluid spc-y">
            <div class="container">
                <div class="mb-prim all-text-center">
                    <h3 class="hd-prim">Upcoming Events</h3>
                </div>
                <div class="grid-archive-4 gap-card">
                    @foreach ($content->upcoming_events as $event)
                        <x-website.event-archive-card :event="$event" />
                    @endforeach
                </div>

                <div class="center-btn-box">
                    <a href="{{ route('website.events.index') }}" class="btn-md btn-prim-outline hover-prim">
                        Explore All
                    </a>
                </div>
            </div>
        </section>
        @endif

        <!--==================================================
                                                              PAST EVENTS SECTION
                                                        ======================================================-->
        @if ($hasPastEvents)
        <section class="container-fluid spc-y">
            <div class="container">
                <div class="mb-prim all-text-center">
                    <h3 class="hd-prim">Past Events</h3>
                </div>
                <div class="grid-archive-4 gap-card" id="homePastEventsGrid">
                    @include('website.home._partials.past-event-items', ['events' => $content->past_events])
                </div>
                @if (($content?->past_events_total ?? 0) > ($content?->past_events->count() ?? 0))
                    <div class="center-btn-box" id="homePastEventsLoadMoreWrap">
                        <button type="button" class="btn-md btn-prim-outline hover-prim" id="homePastEventsLoadMoreBtn">
                            Load More
                        </button>
                    </div>
                @endif
            </div>
        </section>
        @endif

        @if ($content?->event_count > 0)
            <!--================================================== CALL TO ACTION SECTION ======================================================-->
            <section class="container-fluid spc-y-half calltoaction-sec">
                <div class="container">
                    <div class="row">
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <h3 class="hd-prim">We have Organized <span
                                    class="text-prim">{{ $content?->event_count-1 }}+</span> Events</h3>
                            <p>From concerts to cultural festivals, we bring Ireland's most exciting experiences to life. Trust us to make your next event seamless, memorable, and full of joy.</p>
                            <div>
                                <a href="{{ route('website.contact.index') }}" class="btn-md btn-link mt-prim">CONTACT US
                                    <i class="fa-solid fa-arrow-right-long i-ml"></i></a>
                            </div>
                        </div>
                        <div class="img-col">
                            <div>
                                <div class="holder overflow-hidden">
                                    <div class="overlay">
                                        <div>
                                            <h4>{{ $content?->event_count-1 }}+</h4>
                                            <h6>EVENTS</h6>
                                        </div>
                                    </div>
                                    <img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                                        alt="" loading="lazy" decoding="async" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        @endif

        @if ($content?->gallery->count() > 0)
            <!--==================================================
                                                               GALLERY SECTION
                                                    ======================================================-->
            <section class="container-fluid spc-y">
                <div class="container">
                    <div class="mb-prim all-text-center">
                        <h3 class="hd-prim">OUR event memories</h3>
                    </div>
                    <div class="grid-archive-4 gap-card" id="homeGalleryGrid" data-aos="fade-up">
                        @include('website.home._partials.gallery-items', ['images' => $content->gallery])
                    </div>

                    @if (($content?->gallery_total ?? 0) > ($content?->gallery->count() ?? 0))
                        <div class="center-btn-box" id="homeGalleryLoadMoreWrap">
                            <button type="button" class="btn-md btn-prim-outline hover-prim" id="homeGalleryLoadMoreBtn">
                                Load More Images
                            </button>
                        </div>
                    @endif
                </div>
            </section>
        @endif

    </main>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')
@endsection
