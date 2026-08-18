@extends('layouts.website')

@section('head')
    @if (empty($content?->meta_data))
        <title>Events</title>
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
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/events.css') }}" />

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

        <!--==================================================
                          EVENTS ARCHIVE SECTION
        ======================================================-->
        <section class="container-fluid spc-y" id="top-sec">
            <div class="container">
                <div class="filter-box">
                    <div class="col-1">
                        <div class="label-box">
                            <label for="dsvc4">Filter By Month</label>
                            {{-- <button type="button" id="clearFilter">Clear
                                <i class="fa-solid fa-circle-xmark i-ml"></i>
                            </button> --}}
                        </div>
                        <div class="i-holder">
                            <input type="month" class="form-control my-datepicker" id="dsvc4">
                            <i class="fa-regular fa-calendar-days my-i"></i>
                        </div>
                    </div>

                    <div class="col-2">
                        <p class="status-420">
                            Total Results: <span id="totalResults">{{ $events->count() }}</span>
                            <span> <span id="monthLabel"></span> </span>
                        </p>
                    </div>
                </div>

                <div id="eventList">
                    @include('website.events._partials.list', ['events' => $events])
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const monthInput = document.getElementById("dsvc4");
            const clearBtn = document.getElementById("clearFilter");

            async function fetchEvents(month) {
                try {
                    const response = await fetch(`{{ route('website.events.index') }}?month=${month}`, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });
                    if (!response.ok) throw new Error("Failed to fetch events");

                    const html = await response.text();
                    document.getElementById("eventList").innerHTML = html;

                    // Update labels
                    const date = new Date(`${month}-01`);
                    const fullMonth = date.toLocaleString('default', {
                        month: 'long'
                    });
                    const year = date.getFullYear(); 
                    document.getElementById("monthLabel").innerHTML = `| Events on <span class="text-prim">${fullMonth}, ${year}</span>`;
                    document.getElementById("totalResults").textContent =
                        document.querySelectorAll("#eventList .event-archive-card").length;
                } catch (error) {
                    console.error("Error fetching events:", error);
                }
            }

            // Change month
            monthInput.addEventListener("change", function() {
                fetchEvents(this.value);
            });

            // Clear filter (reset to current month)
            clearBtn.addEventListener("click", function() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, "0");
                const current = `${year}-${month}`;
                monthInput.value = current;
                fetchEvents(current);
            });
        });
    </script>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')
@endsection
