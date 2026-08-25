@extends('layouts.website')

@section('head')
    @include('website._partials.head.meta-data', ['metaData' => $event?->meta_data, 'fallbackTitle' => 'Event Details'])

    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Owl carousel v.2.3.4 CSS link -->
    <link rel="stylesheet" href="{{ asset('website/style/swiper-bundle.min.css') }}" />

    <!-- gallery CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/jquery.fancybox.css') }}" />

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/event-detail.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Swiper carousel JS link -->
    <script src="{{ asset('website/js/swiper-bundle.min.js') }}" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- gallery CDN -->
    <script src="{{ asset('website/js/jquery.fancybox.min.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
    <script src="{{ asset('website/js/page-js/event-detail.js') }}" defer></script>
@endsection

@section('body')
    @php
        $hasEventDescription = filled(trim(strip_tags($event?->description ?? '')));
        $hasSupportContent = $event?->support && (
            filled($event->support->phone_number) ||
            filled($event->support->secondary_phone_number) ||
            filled($event->support->email) ||
            filled($event->support->address) ||
            ($event->support->socialLinks?->isNotEmpty() ?? false)
        );
        $hasTicketTypes = $event->ticketTypes->isNotEmpty();
        $hasFeaturedVideo = filled($event?->featured_video);
        $canShowVotingSection = $event?->enable_voting
            && ($event->contestents?->count() ?? 0) > 0
            && $event?->sell_tickets_till
            && now()->format('Y-m-d H:i') <= $event->sell_tickets_till->format('Y-m-d H:i');

            $eventEndAt = null;
            if ($event?->from_date && $event?->to_time) {
                $eventEndDate = $event->to_date ?: $event->from_date;
                $eventEndAt = \Carbon\Carbon::parse(
                    $eventEndDate->format('Y-m-d') . ' ' . $event->to_time->format('H:i:s')
                );
            }

        $canShowEventTimer = $eventEndAt && now()->lessThanOrEqualTo($eventEndAt);
        $bookingStartUrl = ((int) ($event?->type ?? 1) === 2 && config('entities.event_booking_systems.show_selection', false))
            ? route('website.events.event_venue', $event->slug)
            : route('website.events.event_tickets', $event->slug);
    @endphp
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- MAIN BODY -->
    <main>

    @php
    $showAside =
        $canShowEventTimer ||
        $hasSupportContent ||
        $event->infoSlider->count() > 0 ||
        $hasTicketTypes;
    @endphp
        <!--==================================================
                            Top Header
        ======================================================-->
        <section class="spc-y" style="padding-top: 0 !important">
            <!-- Hero Box -->
            <div class="container-fluid spc-y hero-box overflow-hidden"
                style="background: url({{ asset('storage/' . $event->featured_image) }}); background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;">
                <div class="container">
                    <h1 class="hd-big" data-aos="fade-up" data-aos-delay="100">{{ $event?->title ?? '' }}</h1>
                </div>
            </div>

            <!-- Main-Box -->
            <div class="container-fluid">
                <div class="container devider-box">
                    <!-- DETAIL -->
                    <div class="event-detail">
                        <div class="my-box">
                            <!-- Featured Image -->
                            <div>
                                <img src="{{ asset('storage/' . $event->featured_image) }}" alt="" loading="lazy"
                                    decoding="async" />
                            </div>

                            @if ($event?->brought_you_by)
                                <p class="sponser-name"><em>Presented by, <strong>{{ $event?->brought_you_by }}</strong></em></p>
                            @endif

                            @if ($canShowEventTimer)
                            <div class="counter-row">
                                <!-- Counter Col -->
                                    <div class="flex item-center">
                                        <div class="count-box">
                                            <h4>Event Starts In</h4>
                                            <div data-aos="fade-up" data-aos-delay="200">
                                                <div class="timer timer-js-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                
                                <!-- Book Ticket Button Col -->
                                @if($event?->sell_tickets_till && now()->format('Y-m-d H:i') <= $event->sell_tickets_till->format('Y-m-d H:i'))
                                    <div>
                                        <div data-aos="zoom-in" data-aos-delay="550">
                                            <a href="{{ $bookingStartUrl }}" role="button"
                                                class="btn-md btn-prim hover-prim-outline">
                                                Book Ticket <i class="fa-solid fa-ticket i-ml"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                <div>
                                     <button type="button" class="btn-md btn-prim hover-prim-outline" disabled="">Booking Closed</button>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Table -->
                            <div class="table-responsive" style="margin-bottom:2rem;">
                                <table class="table bordered">
                                    <tbody>
                                        <tr>
                                            <th>Event Name</th>
                                            <td>{{ $event?->title ?? '' }}</td>
                                        </tr>
                                        @if ($event?->brought_you_by)
                                            <tr>
                                                <th>Presented by</th>
                                                <td>{{ $event?->brought_you_by ?? '' }}</td>
                                            </tr>
                                        @endif

                                        @if ($event?->featured_video)
                                            <tr>
                                                <th>Event Video</th>
                                                <td><button onclick="showElement(`#event-video`)" class="btn-sm btn-link">
                                                        Watch Now <i class="fa-solid fa-circle-play"></i>
                                                    </button></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th>Event Date</th>
                                            <td>
                                                {{ $event?->from_date->format('M j, Y') }}
                                                @if ($event?->to_date)
                                                    to {{ $event?->to_date->format('M j, Y') }}
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Event Time</th>
                                            <td>
                                                {{ $event?->from_time->format('g:i A') }}
                                                @if ($event->to_time)
                                                    to {{ $event?->to_time->format('g:i A') }}
                                                @endif
                                            </td>
                                        </tr>
                                        {{-- <tr>
                                            <th>Country</th>
                                            <td>UK</td>
                                        </tr> --}}
                                        <tr>
                                            <th>Address</th>
                                            <td>{{ $event?->address ?? '-' }}</td>
                                        </tr>

                                        @if ($event?->map_link)
                                            <tr>
                                                <th>Location</th>
                                                <td><a href="{{ $event?->map_link ?? '' }}" target="_blank"
                                                        rel="noopener noreferrer"
                                                        style="color:var(--my-primary); font-size:0.8rem; font-weight:400 "><b>View
                                                            Map <i class="fa-solid fa-location-crosshairs"></i></b></a></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th>Share</th>
                                            <td>
                                                 @include('website._partials.social-share')
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            @if ($event?->venue_layout_image)
                                <!-- Vanue Image -->
                                <details class="Accordion aos-init aos-animate" name="faq" data-aos="fade-up">
                                    <summary>
                                        <p class="title">Venue Layout</p>
                                        <i class="fa-solid fa-plus icon"></i>
                                    </summary>
                                    <div class="accordion-body">
                                        <img src="{{ asset('storage/' . $event?->venue_layout_image) }}"
                                            alt="{{ $event?->venue_layout_image_alt_text }}" style="border-radius:0.5rem">
                                    </div>
                                </details>
                            @endif


                            @if ($canShowVotingSection)
                                <div class="vote-trigger">
                                    <div>
                                        <h3>{{ $event?->voting_title }}</h3>
                                        <p>{{ $event?->voting_des }}</p>
                                    </div>

                                    <!-- Button -->
                                    <div>
                                        <a href="{{ route('website.events.voting.verify', $event->slug) }}" role="button" class="btn-md btn-prim hover-prim-outline">
                                            {{ $event?->voting_btn_title }}
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <!-- Event Blog Content -->
                            @if ($hasEventDescription)
                            <div style="margin: 2rem 0;">
                                {!! $event?->description ?? '-' !!}
                            </div>
                            @endif

                            @if ($event->gallery->count() > 0)
                                <!-- Gallery -->
                                <div class="grid-archive-4 gap-card" data-aos="fade-up">
                                    @foreach ($event->gallery as $image)
                                        <!-- img card -->
                                        <div class="g-img-card">
                                            <a href="{{ asset('storage/' . $image?->image) }}" class="fancybox"
                                                data-fancybox="gallery1">
                                                <img src="{{ asset('storage/' . $image?->image) }}" loading="lazy"
                                                    alt="{{ $image?->alt_text }}" decoding="async" />
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($showAside)
                    <!-- ASIDE -->
                    <div class="aside">
                        <!-- Event Timer -->
                         @if ($canShowEventTimer)
                            <div>
                                <div class=" my-box aos-init aos-animate" data-aos="fade-up">
                                    <h5 class="hd-sub text-center" style="font-size:1.2rem; margin:0">Event
                                        Starts In</h5>
                                    <div style="display:flex; justify-content:center; align-items:center; margin:0.5rem 0">
                                        <div class="timer timer-js-2"></div>
                                    </div>
                                    @if($event?->sell_tickets_till && now()->format('Y-m-d H:i') <= $event->sell_tickets_till->format('Y-m-d H:i'))
                                        <a href="{{ route('website.events.event_venue', $event->slug) }}" role="button"
                                        class="btn-md btn-sec hover-sec-outline btn-w-full">Book Tickets</a>
                                    @else
                                        <button type="button" class="btn-md btn-sec hover-sec-outline btn-w-full" disabled>Booking Closed</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($hasSupportContent)
                            <!-- Need Support -->
                            <div>
                                <div class="my-box aos-init aos-animate" data-aos="fade-up">
                                    <h5 class="hd-sub">Need Support?</h5>
                                    <div class="table-responsive">
                                        <table class="table bordered">
                                            <tbody>
                                                <!-- Phone Number -->
                                                @if ($event?->support?->phone_number)
                                                    <tr>
                                                        <th>Phone</th>
                                                        <td><a
                                                                href="tel:{{ $event?->support?->phone_prefix }}{{ $event?->support?->phone_number }}">({{ $event?->support?->phone_prefix }})
                                                                {{ $event?->support?->phone_number }}</a></td>
                                                    </tr>
                                                @endif

                                                <!-- Secondary Phone Number -->
                                                @if ($event?->support?->secondary_phone_number)
                                                    <tr>
                                                        <th>Phone</th>
                                                        <td><a
                                                                href="tel:{{ $event?->support?->secondary_phone_prefix }}{{ $event?->support?->secondary_phone_number }}">({{ $event?->support?->secondary_phone_prefix }})
                                                                {{ $event?->support?->secondary_phone_number }}</a></td>
                                                    </tr>
                                                @endif

                                                <!-- Email -->
                                                @if ($event?->support?->email)
                                                    <tr>
                                                        <th>Email</th>
                                                        <td><a href="mailto:{{ $event?->support?->email }}" class="text-break" target="_blank"
                                                                rel="noopener noreferrer">{{ $event?->support?->email }}</a>
                                                        </td>
                                                    </tr>
                                                @endif

                                                <!-- Address -->
                                                @if ($event?->support?->address)
                                                    <tr>
                                                        <th>Address</th>
                                                        <td>{{ $event?->support?->address }}</td>
                                                    </tr>
                                                @endif

                                                <!-- Social lnks -->
                                                @if ($event?->support?->socialLinks?->isNotEmpty())
                                                    <tr>
                                                        <td colspan="2">
                                                            <div class="social-list">
                                                                <ul>
                                                                    @foreach ($event->support->socialLinks as $link)
                                                                        @php
                                                                            $social = config("entities.social_options.{$link->platform}");
                                                                        @endphp

                                                                        @if (!empty($social['icon']) && !empty($link->url))
                                                                            <li data-aos="zoom-in">
                                                                                <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                                                                                    <i class="{{ $social['icon'] }}"></i>
                                                                                </a>
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif


                        @if ($event->infoSlider->count() > 0)
                            <div>
                                <div class="my-box aos-init aos-animate" data-aos="fade-up">
                                    <!-- Slider container -->
                                    <div class="swiper infoSlider radious-prim">
                                        <div class="swiper-wrapper">
                                            @foreach ($event->infoSlider as $slide)
                                                @php
                                                    $slideImage = asset('storage/' . $slide->image);
                                                    $slideAltText = $slide->alt_text ?: 'Info slider image';
                                                @endphp
                                                <div class="swiper-slide">
                                                    @if ($slide->url)
                                                        <a href="{{ $slide->url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $slideAltText }}">
                                                            <img src="{{ $slideImage }}" alt="{{ $slideAltText }}" loading="lazy" decoding="async"
                                                                class="radious-prim" />
                                                        </a>
                                                    @else
                                                        <img src="{{ $slideImage }}" alt="{{ $slideAltText }}" loading="lazy" decoding="async"
                                                            class="radious-prim" />
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Tickets  -->
                        @if ($hasTicketTypes)
                        <div>
                            <div class="my-box aos-init aos-animate" data-aos="fade-up">
                                <h5 class="hd-sub">Event Tickets</h5>
                                <!-- Tickets -->
                                <div class="grid-1 gap-card">
                                    @foreach($event->ticketTypes as $ticket)
                                    <!-- Ticket Card -->
                                    <div class="ticket-card {{ $ticket->available_tickets <= 0 ? 'sold-out' : '' }}">
                                        @if($ticket->available_tickets <= 0)
                                            <span class="sold-tag">Sold Out</span>
                                        @endif
                                        <h4 class="ticket-name">{{ $ticket->title }}</h4>
                                        <h6 class="ticket-price">
                                            Price:
                                            <span class="text-prim">{{ $event->currency_symbol }} {{ number_format($ticket->ticket_price, 2) }}</span>
                                        </h6>
                                        @if($ticket->bulkDiscounts && $ticket->bulkDiscounts->count())
                                            <ul class="check-list list-size-sm i-green">
                                                @foreach($ticket->bulkDiscounts as $bd)
                                                    <li>
                                                        <i class="fa-regular fa-circle-check"></i>
                                                        {{ $bd->discount_percentage }}% off on booking minimum {{ $bd->min_order_qty }} tickets.
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                    @endforeach

                                    <!-- buy Btn -->
                                 @if ($canShowEventTimer)
                                    @if($event?->sell_tickets_till && now()->format('Y-m-d H:i') <= $event->sell_tickets_till->format('Y-m-d H:i'))
                                       <a href="{{ route('website.events.event_venue', $event->slug) }}" role="button"
                                        class="btn-md btn-sec-outline hover-sec btn-w-full">Book
                                        Tickets</a>
                                    @else
                                       <button type="button" class="btn-md btn-sec hover-sec-outline btn-w-full" disabled="">Booking Closed</button>
                                    @endif
                                 @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($event->sponsors->count() > 0)
            <!--================================================== SPONSERS SECTION ======================================================-->
            <section class="container-fluid spc-y" style="padding-top: 0 !important">
                <div class="container">

                    <div class="mb-prim all-text-center">
                        <h3 class="hd-prim">Our sponsers</h3>
                    </div>
                    <div class="platform-row">
                        @foreach ($event->sponsors as $sponsor)
                            <div data-aos="fade-up">
                                <a href="{{ $sponsor?->url ?? '' }}" target="_blank" class="card">
                                    <img src="{{ asset('storage/' . $sponsor?->image) }}"
                                        alt="{{ $sponsor?->alt_text ?? '' }}" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    <!-- Popup Box -->
    @if ($hasFeaturedVideo)
    <div class="popup-container pop-boxJS" id="event-video">
        <div class="popup w-s popJS" style="overflow: visible;">
            <!-- Header -->
            <div class="close-box">
                <div class="title-box">
                    <h5 class="hd-sub">Event Video</h5>
                </div>
                <button class="btn-lg btn-close">
                    <i class="fa-regular fa-circle-xmark"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="module-body">
                <!-- Video Container -->
                <div class="swiper-slide">
                    <video style="display:block;  width:100%; height:auto" loop plays-inline controls preload="auto"
                        poster="{{ asset('storage/' . $event?->thumbnail) }}"
                        title="Event video for {{ $event?->title ?? 'Dolphinevent' }}">
                        <source src="{{ asset('storage/' . $event?->featured_video) }}" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="popup-container pop-boxJS" id="votingSuccessAlertModal">
        <div class="popup w-s popJS">
            <div class="close-box">
                <div class="title-box">
                    <h5 class="hd-sub" id="votingSuccessAlertTitle">Success</h5>
                </div>
                <button class="btn-lg btn-close">
                    <i class="fa-regular fa-circle-xmark"></i>
                </button>
            </div>

            <div class="module-body" style="overflow: visible;">
                <h5 class="mb-0 text-center" id="votingSuccessAlertMessage">
                    <span aria-hidden="true">🎉</span> Thankyou for voting we have receved your vote successfully
                </h5>

                <div class="flex justify-center" style="margin-top: 1rem;">
                    <button type="button" class="btn-sm btn-prim hover-prim-outline btn-close">Ok</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')

    <script defer>
        document.addEventListener("DOMContentLoaded", function() {
            const votingSuccessStorageKey = @json('event_voting_success_' . $event->slug);

            try {
                const votingSuccessMessage = localStorage.getItem(votingSuccessStorageKey);

                if (votingSuccessMessage) {
                    const votingSuccessMessageBox = document.getElementById('votingSuccessAlertMessage');

                    if (votingSuccessMessageBox) {
                        votingSuccessMessageBox.textContent = votingSuccessMessage;
                    }

                    localStorage.removeItem(votingSuccessStorageKey);
                    showElement('#votingSuccessAlertModal');
                }
            } catch (error) {
                console.warn('Voting success message could not be shown.', error);
            }

            const output = document.querySelector(".timer-js-1");
            const output2 = document.querySelector(".timer-js-2");
            const toDate = "{{ $event?->to_date ?? '' }}";
    
            let startDateTimeString = "{{ $event?->from_date?->format('Y-m-d') }}T{{ $event?->from_time->format('H:i:s') }}";
            
            let endDateTimeString;
            if(toDate)
            {
                endDateTimeString = "{{ $event?->to_date?->format('Y-m-d') }}T{{ $event?->to_time->format('H:i:s') }}";
            } 
            else 
            {
                endDateTimeString = "{{ $event?->from_date?->format('Y-m-d') }}T{{ $event?->to_time->format('H:i:s') }}";
            }
            
            let eventstartDate = new Date(startDateTimeString);
            let eventEndDate = new Date(endDateTimeString);
           
            createTimer(eventstartDate, eventEndDate, output);
            createTimer(eventstartDate, eventEndDate, output2);
        });
    </script>
@endsection
