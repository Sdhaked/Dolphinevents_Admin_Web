@extends('layouts.website')

@section('head')
    @include('website._partials.head.meta-data', ['metaData' => $event?->meta_data, 'fallbackTitle' => 'Event Seat Selection'])
    
    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}" />
    <link rel="stylesheet" href="{{ asset('website/style/offer-bar.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')

    {{-- --- DATA BRIDGE FOR JS --- --}}
    <script>
        window.stadiumData = {
            lwdata: @json($lwdata),
            clwdata: @json($clwdata),
            crwdata: @json($crwdata),
            rwdata: @json($rwdata),
            seatAssignments: @json($seatAssignments), // Contains colors and ticket_type_ids
            heldSeatIds: @json($heldSeatIds), // Disable the held tickets
            targetTicketTypeId: @json($targetTicketTypeId) // The specific type the user chose
        };
    </script>

   
    <script src="{{ asset('javascript/pages/stadium/seat-selection.js') }}" defer></script>
    
    <script src="{{ asset('website/js/offer-bar.js') }}" defer type="module"></script>
    
@endsection

@section('body')
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- Venue Layout Popup Modal -->
    @include('website.components.venue-layout-popup')

    <!-- ==> Animation Canvas -->
    <canvas id="confetti-canvas"></canvas>

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
                <div class="head-box">
                   <div>
                        <h1 class="hd-prim" data-aos="fade-in">Select Seats</h1>
                        <h5 style="color: var(--color-text-200);">Event: 
                            <span style="color: var(--my-primary);">{{ $event->title }}</span>
                        </h5>
                        <h5 style="color: var(--color-text-200);">Category: 
                            <span style="color: {{ $selectedTicketType->ticket_type_color }};">[{{ $selectedTicketType->title }}]</span>
                        </h5>
                    </div>

                    <div>
                        <button onclick="showElement(`#venue-layout-pop`)" class="btn-md btn-lite-outline hover-lite">
                            <i class="fa-solid fa-layer-group i-mr"></i> Venue Layout
                        </button>
                    </div>
                </div>

                <!-- Offer Bar -->
                <div class="offer-comp"></div>

                <form action="{{ route('website.events.checkout.initiate') }}" method="POST" data-seat-selection-form>
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input type="hidden" name="ticket_type_id" value="{{ $targetTicketTypeId }}">
                <!-- Main Content -->
                <div data-aos="fade-up" class="main-seat-container">
                    <!-- //stadium Layout -->
                    <div class="screen-parent">
                        <div class="screen">
                            <div>STAGE</div>
                        </div>
                        <div class="stadium"></div>
                    </div>

                    <!-- Info Box -->
                    <div>
                        <p class="selected-seats">
                            Total Selected Seats: <span class="text-prim">0</span>
                        </p>

                        <p class="selected-seats">Selected Seats: <span></span></p>

                        @if (session('error'))
                            <p class="selected-seats" role="alert" style="color: #ff6b6b;">{{ session('error') }}</p>
                        @endif
                        <p class="selected-seats" data-seat-selection-error hidden role="alert" style="color: #ff6b6b;"></p>

                        <div class="select-info-box">
                            <div>
                                <div class="check-box selected-seat"><i class="fa-solid fa-check"></i></div>
                                <span>Seat Selected</span>
                            </div>
                            <div>
                                <div class="check-box empty-seat"></div>
                                <span>Empty Seat</span>
                            </div>
                            <div>
                                <div class="check-box booked-seat"><i class="fa-solid fa-check"></i></div>
                                <span>Booked Seat</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="next-box">
                        <button type="submit" class="btn-md btn-prim hover-prim-outline" data-seat-selection-submit>
                            Next <i class="fa-solid fa-arrow-right-long i-ml"></i>
                        </button>
                </div>
                
               </form>    <!-- form ends here --> 
            </div>
        </section>
    </main>
    <!-- ####### FOOTER ####### -->
   
 @include('website._partials.Footer')
 
{{-- Offer bar handling --}}

<script>
    window.bulkDiscountSlabs = @json($slabs);
</script>
    <script type="module">
    import { createOfferBar, offerBarFun, debounce } 
    from "{{ asset('website/js/offer-bar.js') }}";

    function initOfferBar() {
        const slabs = window.bulkDiscountSlabs || [];
        const offerComp = document.querySelector(".offer-comp");

        if (!offerComp || slabs.length === 0) {
            offerComp?.classList.add("hidden");
            return;
        }

        // 1. Define the debounced update function
        window.debouncedOfferBarFun = debounce((selectedTickets) => {
            const barElement = offerComp.querySelector(".offer-bar");
            if (barElement) {
                offerBarFun(selectedTickets, slabs, barElement);
            }
        }, 200);

        // 2. Initialize the UI
        createOfferBar(offerComp, slabs);

        // 3. Listen for seat selection changes
        const stadiumContainer = document.querySelector(".stadium");
        if (stadiumContainer) {
            stadiumContainer.addEventListener("change", (e) => {
                if (e.target.type === "checkbox") {
                    // Count only active/selected seats for this category
                    const selectedCount = stadiumContainer.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').length;
                    
                    // Trigger the offer bar update
                    window.debouncedOfferBarFun(selectedCount);
                }
            });
        }

        // Initial call (default 0 or 1 depending on your preference)
        window.debouncedOfferBarFun(0);
    }

    initOfferBar();


    const stadium = document.querySelector(".stadium");
    const screen = document.querySelector(".screen");
    const updateStageWidth =()=> {
        if (screen && stadium) {
            const width = stadium.scrollWidth;
            console.log("stadium width:", width);
            screen.style.width = width + "px";
        }
    }
    const observer = new MutationObserver(() => {
        updateStageWidth()
        centerSeatScrollbar(); // centralise scroll bar
        observer.disconnect(); // stop observing once seats are rendered
    });
    if (stadium) {
        observer.observe(stadium, { childList: true});
    }

    const centerSeatScrollbar = () => {
    const screenParent = document.querySelector(".screen-parent");

    if (screenParent) {
        requestAnimationFrame(() => {
            screenParent.scrollLeft = (screenParent.scrollWidth - screenParent.clientWidth) / 3;
        });
    }
};
</script>

@endsection
