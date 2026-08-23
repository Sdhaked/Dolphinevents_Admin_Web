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
        $selectedHero = $content?->show_what ?: 'default';
        $hasDefaultHero = $selectedHero === 'default';
        $hasHeroSlider = $selectedHero === 'slider' && ($content?->hero_slider?->isNotEmpty() ?? false);
        $hasHeroVideo = $selectedHero === 'video' && filled($content?->hero_video_path);
        $validHeadingTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $defaultHeroHeadingType1 = in_array($content?->default_hero_heading_type_1, $validHeadingTags, true) ? $content->default_hero_heading_type_1 : 'h3';
        $defaultHeroHeadingType2 = in_array($content?->default_hero_heading_type_2, $validHeadingTags, true) ? $content->default_hero_heading_type_2 : 'h1';
        $defaultHeroHeading1 = $content?->default_hero_heading_1 ?: 'About Dolphin Tickets';
        $defaultHeroHeading2 = $content?->default_hero_heading_2 ?: 'Your next memory starts right here.';
        $defaultHeroDescription = $content?->default_hero_description ?: 'Dolphin Tickets brings people closer to the events they love. From high-energy concerts and community celebrations to comedy, culture and family experiences, we make discovering and booking UK events effortless.';
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
        @if ($hasHeroSlider || $hasHeroVideo || $hasDefaultHero)
            @if ($hasDefaultHero)
                <section class="container-fluid spc-y default-hero-sec dark-bg">
                    <div class="container grid-sec-2 gap-col">
                        <div>
                            <{{ $defaultHeroHeadingType1 }} class="hd-prim"><span class="pulse"></span> {{ $defaultHeroHeading1 }}</{{ $defaultHeroHeadingType1 }}>
                            <{{ $defaultHeroHeadingType2 }} class="hd-big">{{ $defaultHeroHeading2 }}</{{ $defaultHeroHeadingType2 }}>

                            <!-- Description -->
                            @if (filled($content?->default_hero_processed_description))
                                {!! $content->default_hero_processed_description !!}
                            @else
                                <p>{{ $defaultHeroDescription }}</p>
                            @endif
                            <!-- Button -->
                            <a href="{{ route('website.events.index') }}" role="button" class="btn-sm btn-lite hover-sec mt-spc">
                                Book Tickets
                                <i class="fa-solid fa-arrow-right-long i-ml"></i>
                            </a>
                        </div>

                        <!-- EVENT Slider col -->
                        <div class="swiper heroSwiperDef">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide swap-event-card">
                                    <img src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?auto=format&fit=crop&w=1000&q=88" alt="event title" loading="lazy" decoding="async" />
                                    <a href="#" class="content">
                                        <h3 class="hd-prim">17 SEP 2023</h3>
                                        <h3 class="event-name">Lorem ipsum dolor sit amet</h3>
                                    </a>
                                </div>
                                <div class="swiper-slide swap-event-card">
                                    <img src="https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?auto=format&fit=crop&w=800&q=85" alt="event title" loading="lazy" decoding="async" />
                                    <a href="#" class="content">
                                        <h3 class="hd-prim">17 SEP 2023</h3>
                                        <h3 class="event-name">Lorem ipsum dolor sit amet</h3>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @else
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
                    <!--<div class="swiper-button-next"></div>-->
                    <!--<div class="swiper-button-prev"></div>-->
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
        @endif

    <div class="ticker">
      <div class="ticker-inner">
        LIVE MUSIC <b>✦</b> COMEDY <b>✦</b> FOOD &amp; DRINK <b>✦</b> NIGHTLIFE <b>✦</b> CULTURE <b>✦</b> FAMILY DAYS OUT <b>✦</b>
        LIVE MUSIC <b>✦</b> COMEDY <b>✦</b> FOOD &amp; DRINK <b>✦</b> NIGHTLIFE <b>✦</b> CULTURE <b>✦</b> FAMILY DAYS OUT <b>✦</b>
      </div>
    </div>
        

        @if ($content->featured_events->count() > 0)
            <!--==================================================
                      FEATURED EVENTS SECTION
            ======================================================-->
            <section class="container-fluid spc-y">
                <div class="container">
                    <div class="side-hd-holder">
                       <div>
                        <h3 class="hd-prim">Our Top 
                        <!--<span class="text-prim">{{ $content->featured_events->count() }}</span>-->
                            Events</h3>
                        <h3 class="hd-big">Trending near you
                        <!--<span class="text-prim">{{ $content->featured_events->count() }}</span>--></h3>
                       </div>

                       <div>
                           <a href="{{ route('website.about.index') }}" role="button" class="btn-sm btn-lite-outline hover-prim mt-spc">View all events ↗ </a>
                       </div>
                    </div>
                    <div class="grid-archive-4 gap-card">
                        @foreach ($content->featured_events as $event)
                            <x-website.event-archive-card :event="$event" />
                        @endforeach
                    </div>

                    <div class="center-btn-box only-mb">
                    <a href="{{ route('website.events.index') }}" class="btn-md btn-prim-outline hover-prim">
                        Explore All
                    </a>
                </div>
                </div>
            </section>
        @endif

        @if ($content?->about_heading_text_1 && $content?->about_processed_description && $content?->about_image_path)
            <!--==================================================
                   MINI ABOUT SECTION
            ======================================================-->
            <section class="container-fluid spc-y mini-about-sec dark-bg">
                <div class="container">
                    <div class="grid-sec-2 gap-col">
                        <div>
                            <div class="img-holder">
                                <img src="{{ asset('storage/' . $content->about_image_path) }}"
                                    alt="{{ $content->about_image_alt }}" loading="lazy" decoding="async" />
                            
                            </div>
                        </div>
                        <div>
                            
                                <{{ $content->about_heading_type_1 ?? 'h3' }} class="hd-prim">{{ $content->about_heading_text_1 }}
                                </{{ $content->about_heading_type_1 ?? 'h3' }}>

                                <{{ $content->about_heading_type_2 ?? 'h3' }} class="hd-big">
                                {{ $content->about_heading_text_2 }}
                                </{{ $content->about_heading_type_2 ?? 'h3' }}>
                                
                                <!-- Description -->
                                {!! $content->about_processed_description !!}

                                <div class="about-points">
                                  <div class="about-point">
                                    <strong>Discover</strong>
                                    <span>Unique UK events, all in one place.</span>
                                  </div>
                                  <div class="about-point">
                                    <strong>Book</strong>
                                    <span>Simple, secure and instant e-tickets.</span>
                                  </div>
                                  <div class="about-point">
                                    <strong>Experience</strong>
                                    <span>More moments worth sharing.</span>
                                  </div>
                                </div>

                                <a href="{{ route('website.about.index') }}" role="button" class="btn-sm btn-lite hover-sec mt-spc">
                                    Know more
                                    <i class="fa-solid fa-arrow-right-long i-ml"></i>
                                </a>
                            
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($content?->info_slider->count() > 0)
        <!--==================================================
                            INFO SLIDER SECTION
        ======================================================-->
            <section class="container-fluid spc-y-half">
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
                <div class="side-hd-holder">
                    <div>
                        <h3 class="hd-prim">Our Events</h3>
                        <h3 class="hd-big">Time to enjoy</h3>
                    </div>
                    <div>
                        <a href="{{ route('website.about.index') }}" role="button"     class="btn-sm btn-lite-outline hover-prim mt-spc">View all events ↗ </a>
                    </div>
                </div>

                
                <div class="grid-archive-4 gap-card">
                    @foreach ($content->upcoming_events as $event)
                        <x-website.event-archive-card :event="$event" />
                    @endforeach
                </div>
                <div class="center-btn-box only-mb">
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
            <!--================================================== 
            CALL TO ACTION SECTION 
            ======================================================-->
            <section class="container-fluid spc-y dark-bg">
                <div class="container calltoaction-container">
                    <div>
                        <h3 class="hd-prim">A one-night-only experience</h3>
                        <h3 class="hd-big">Invented {{ $content?->event_count-1 }}+ events</h3>
                        <p>Immerse yourself in an evening of music, movement and culture with award-winning artists and remarkable experiences arriving across the UK.</p>
                        <div>
                            <a href="{{ route('website.contact.index') }}"class="btn-sm btn-lite hover-sec">CONTACT US
                                <i class="fa-solid fa-arrow-right-long i-ml"></i></a>
                        </div>
                    </div>

                    <div>
                        <img src="	https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1100&q=85" alt="">
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
                        <h3 class="hd-prim">Gallery</h3>
                        <h3 class="hd-big">Past event memories</h3>
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
