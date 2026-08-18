@extends('layouts.website')

@section('head')
    @if (empty($event?->meta_data))
        <title>Event Venue layout</title>
    @else
        {!! $event->meta_data !!}
    @endif

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
@endsection

@section('body')
    @php
        $hasVenueLayoutImage = filled($event?->venue_layout_image);
        $hasTicketTypes = $event->ticketTypes->isNotEmpty();
    @endphp
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- Venue Layout Popup Modal -->
    @if ($hasVenueLayoutImage)
        @include('website.components.venue-layout-popup')
    @endif

    <!-- ==> Animation Canvas -->
    <!-- <canvas id="confetti-canvas"></canvas> -->

    <!-- MAIN BODY -->
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
                <div class="head-box">
                    <div>
                        <h1 class="hd-prim" data-aos="fade-in">Choose Ticket Type</h1>
                        <h5 style="color: var(--color-text-300);">Event Name: <span
                                style="color: var(--my-primary);">{{ $event?->title ?? '' }}</span></h5>
                    </div>

                    @if ($hasVenueLayoutImage)
                    <div data-aos="zoom-in">
                        <button onclick="showElement(`#venue-layout-pop`)" class="btn-md btn-lite-outline hover-lite">
                            <i class="fa-solid fa-layer-group i-mr"></i> Venue Layout
                        </button>
                    </div>
                    @endif
                </div>
            @php
                $formAction = ($event->type == 2)
                    ? route('website.events.event_seats', ['event' => $event->slug])
                    : route('website.events.checkout.initiate');

                $formMethod = ($event->type == 2) ? 'GET' : 'POST';
            @endphp

            <form method="{{ $formMethod }}" action="{{ $formAction }}">
                @if($formMethod === 'POST') 
                    @csrf 
                @endif
                
                {{-- Hidden field to pass the chosen event id to the next page --}}
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                
                <!-- Main Content -->
                @if ($hasTicketTypes)
                <div class="ticket-row">
                    @foreach($event->ticketTypes as $ticket)
                    <div class="ticket-card" data-aos="fade-up">
                        <label for="ticket-{{ $ticket->id }}" class="over-hidden">

                         <input type="radio" name="ticket_type_id" id="ticket-{{ $ticket->id }}" value="{{ $ticket->id }}"
                            {{ $ticket->available_tickets <= 0 ? 'disabled' : '' }} required>

                            @if($ticket->available_tickets <= 0) <span class="sold-out">Sold Out!!</span>
                                @endif

                                <div>
                                    <img src="{{ $ticket->featured_image
                            ? asset('storage/' . $ticket->featured_image)
                            : asset('website/images/default-ticket.jpg') }}" alt="{{ $ticket->title }}" loading="lazy"
                                        decoding="async">
                                </div>

                                <div class="text-box">
                                    <h3 class="ticket-name">{{ $ticket->title }}</h3>

                                    <p class="ticket-price">
                                        {{ $event->currency_symbol }}
                                        {{ number_format($ticket->ticket_price, 2) }}/-
                                        <sub>Per Ticket</sub>
                                    </p>

                                    @if($ticket->description)
                                    <p class="discount-msg">{{ $ticket->description }}</p>
                                    @endif

                                    @if($ticket->enable_bulk_discount && $ticket->bulkDiscounts->count() > 0)
                                    <ul class="check-list list-size-sm i-green">
                                        @foreach($ticket->bulkDiscounts as $bd)
                                        <li>
                                            <i class="fa-regular fa-circle-check"></i>
                                            {{ $bd->discount_percentage }}% off on booking minimum {{ $bd->min_order_qty }}
                                            tickets.
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </div>

                        </label>
                    </div>
                    @endforeach

                </div>
                @endif
      
                <!-- Footer -->
                @if ($hasTicketTypes)
                <div class="next-box">
                        <button type="submit" class="btn-md btn-prim hover-prim-outline">
                            Next <i class="fa-solid fa-arrow-right-long i-ml"></i>
                        </button>
                </div>
                @endif
            </form>
            </div>
        </section>
    </main>

    <!-- ####### FOOTER ####### -->
 
    @include('website._partials.Footer')
    
@endsection
