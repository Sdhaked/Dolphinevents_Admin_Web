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

                        <!-- Nav Contact  -->
                        <div class="mini-About">
                            <div class="about-crd">
                                <h6><i class="fa-solid fa-phone"></i> Call Us</h6>
                                <a href="" target="_blank" rel="noopener noreferrer">
                                    (+91) 8544 124 253
                                </a>
                            </div>

                            <div class="about-crd">
                                <h6><i class="fa-regular fa-envelope"></i> Email Us</h6>
                                <a href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=demo@gmail.com"
                                    target="_blank" rel="noopener noreferrer">
                                    demo@gmail.com
                                </a>
                            </div>

                            <div class="about-crd">
                                <h6><i class="fa-solid fa-location-dot"></i> Located at</h6>
                                <a href="" target="_blank" rel="noopener noreferrer">
                                    23 Suspendis matti, Visaosang Building , North American
                                </a>
                            </div>
                        </div>
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
