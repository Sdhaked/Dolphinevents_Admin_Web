@php
    use App\Models\ContactPageContent;

    $navContact = ContactPageContent::find(1);
    $navPhoneNumber = filled($navContact?->phone_number_1)
        ? $navContact?->phone_number_1
        : $navContact?->phone_number_2;
    $navPhonePrefix = filled($navContact?->phone_number_1)
        ? $navContact?->phone_prefix_1
        : $navContact?->phone_prefix_2;
    $navPhoneLabel = trim(implode(' ', array_filter([$navPhonePrefix, $navPhoneNumber])));
    $navPhoneHref = preg_replace('/\s+/', '', $navPhoneLabel);
    $hasNavContactInfo = filled($navPhoneNumber) || filled($navContact?->email) || filled($navContact?->address);
@endphp

<header>
    <div class="emptyNav"></div>
    <!-- NAV BAR -->
    <nav>
        <div class="container">
            <!-- Brand Icon COL 1st 🥗-->
            <div class="logo-nav-col">
                <a href="{{ route('website.home.index') }}">
                    <img src="{{ asset('website/images/logo.svg') }}" alt="Dolphinevent logo" />
                </a>
            </div>

            <!-- Nav Link COL 2nd 🥗-->
            <div class="nav-link-col sm offcanvas-sec pop-boxJS" id="Offcn-hnav">
                <div class="offcanvas popJS">
                    <!-- Head -->
                    <div class="offcanvas-head">
                        <!-- Title -->
                        <h5 class="offcanvas-title">Menu Items</h5>

                        <!-- Close Btn -->
                        <button type="button" class="btn-close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="offcanvas-body">
                        <!-- Nav Liks -->
                        <ul class="nav-ul">
                            @include('website._partials.nav-menu')
                        </ul>

                        @if ($hasNavContactInfo)
                            <!-- Nav Contact  -->
                            <div class="mini-About">
                                @if (filled($navPhoneNumber))
                                    <div class="about-crd">
                                        <h6><i class="fa-solid fa-phone"></i> Call Us</h6>
                                        <a href="tel:{{ $navPhoneHref }}">
                                            {{ $navPhoneLabel }}
                                        </a>
                                    </div>
                                @endif

                                @if (filled($navContact?->email))
                                    <div class="about-crd">
                                        <h6><i class="fa-regular fa-envelope"></i> Email Us</h6>
                                        <a href="mailto:{{ $navContact->email }}">
                                            {{ $navContact->email }}
                                        </a>
                                    </div>
                                @endif

                                @if (filled($navContact?->address))
                                    <div class="about-crd">
                                        <h6><i class="fa-solid fa-location-dot"></i> Located at</h6>
                                        <a href="{{ filled($navContact?->map_link) ? $navContact->map_link : route('website.contact.index') }}"
                                            target="_blank" rel="noopener noreferrer">
                                            {{ $navContact->address }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Nav Btn Col 3rd 🥗-->
            <div class="nav-btn-col">
                <!-- Book Btn -->
                <a href="{{ route('website.events.index') }}" role="button" class="btn-md btn-prim hover-prim-outline">
                    Our Events
                </a>

                <!-- Toggle btn ⏸️ {android} -->
                <button class="nav-btn toggle-btn" role="button" onclick="showElement(`#Offcn-hnav`)">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        </div>
    </nav>
</header>
