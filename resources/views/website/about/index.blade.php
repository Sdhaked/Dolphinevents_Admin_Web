@extends('layouts.website')

@section('head')
    @if (empty($content?->meta_data))
        <title>About Us</title>
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
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/about.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

     <script defer>
document.addEventListener("DOMContentLoaded", function () {

    let counterStarted = false;

    function startCounter() {

        const section = document.querySelector(".myCounter");

        if (!section || counterStarted) return;

        const sectionTop = section.getBoundingClientRect().top + window.scrollY;
        const windowBottom = window.scrollY + window.innerHeight;

        if (windowBottom > sectionTop) {

            counterStarted = true;

            document.querySelectorAll(".num").forEach((counter) => {

                const target = parseInt(counter.textContent, 10);
                const duration = 4000;
                const startTime = performance.now();

                function animate(currentTime) {

                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    counter.textContent = Math.ceil(progress * target);

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                }

                requestAnimationFrame(animate);
            });
        }
    }

    window.addEventListener("scroll", startCounter);
    window.addEventListener("load", startCounter);

    // Trigger immediately in case section is already visible
    startCounter();
});
</script>

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

        @if ($content?->about_featured_image_path && $content?->about_heading_text && $content?->about_processed_description)
            <!--================================================== ABOUT SECTION ======================================================-->
            <section class="container-fluid spc-y" id="top-sec">
                <div class="container">
                    <div class="grid-sec-2 item-center gap-col" style="margin-bottom: 2rem">
                        <div class="img-box" style="height: 18dvh;">
                            <img src="{{ asset('storage/' . $content->about_featured_image_path) }}"
                                alt="{{ $content->about_featured_image_alt }}" loading="lazy" decoding="async" />
                        </div>
                        <div>
                            <{{ $content->about_heading_type ?? 'h3' }} class="hd-prim m-0">
                                {{ $content->about_heading_text }}
                                </{{ $content->about_heading_type ?? 'h3' }}>
                        </div>
                    </div>

                    {!! $content->about_processed_description !!}

                    <hr class="hr-full">

                    @include('website._partials.social-links')
                </div>
            </section>
        @endif

        <!--================================================== COUNT SECTION ======================================================-->
        <section class="container-fluid spc-y-half count-sec">
            <div class="container">
                <div class="grid-auto item-center gap-card myCounter">
                    <div class="count-box">
                        <h4><span class="num">2500</span>+</h4>
                        <p>HAPPY CUSTOMERS</p>
                    </div>
                    <div class="count-box">
                        <h4><span class="num">5</span>+</h4>
                        <p>YEARS OF EXPERIANCE</p>
                    </div>
                    <div class="count-box">
                        <h4><span class="num">3000</span>+</h4>
                        <p>TICKET BOOKINGS</p>
                    </div>
                    <div class="count-box">
                        <h4><span class="num">8</span>+</h4>
                        <p>COLLABORATION</p>
                    </div>
                </div>
            </div>
        </section>

        @if (
            $content?->owner_heading_1_text &&
                $content?->owner_heading_2_text &&
                $content?->owner_image_1_path &&
                $content?->owner_image_2_path &&
                $content?->owner_processed_description)
            <!--================================================== ABOUT SECTION ======================================================-->
            <section class="container-fluid spc-y owner-sec">
                <div class="container">
                    <div class="grid-sec-2 item-center gap-col">
                        <div class="img-collarge-box">
                            <div class="img-box" style="height: 55dvh;">
                                <img src="{{ asset('storage/' . $content->owner_image_1_path) }}"
                                    alt="{{ $content->owner_image_1_alt }}" loading="lazy" decoding="async" />
                            </div>
                            <div>
                                <div class="empty-box"></div>
                                <div class="img-box">
                                    <img src="{{ asset('storage/' . $content->owner_image_2_path) }}"
                                        alt="{{ $content->owner_image_2_alt }}" loading="lazy" decoding="async" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <{{ $content->owner_heading_1_type ?? 'h3' }} class="hd-prim">
                                {{ $content->owner_heading_1_text }}
                                </{{ $content->owner_heading_1_type ?? 'h3' }}>
                                <{{ $content->owner_heading_2_type ?? 'h3' }} class="hd-big text-prim">
                                    {{ $content->owner_heading_2_text }}</{{ $content->owner_heading_2_type ?? 'h3' }}>
                                    {!! $content->owner_processed_description !!}
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <!-- ####### FOOTER ####### -->
    @include('website._partials.Footer')
@endsection
