@php
    use App\Models\ContactPageContent;
    use App\Models\ContactSocialLink;
    $contact = ContactPageContent::where('id', 1)->first();
    $hasFooterContactInfo = filled($contact?->address) || filled($contact?->email) || filled($contact?->phone_number_1) || filled($contact?->phone_number_2);
    $hasFooterSocialLinks = ContactSocialLink::query()->exists();
@endphp

<footer class="container-fluid">
    <div class="container">
        <!-- FOOTER TOP 🥗 -->
        <div class="foot-top">
            <!-- Box 1 -->
            <div class="box-1">
                <div>
                    <img src="{{ asset('website/images/logo.svg') }}" alt="Dolphinevent logo" class="f-logo" />
                </div>
                <ul>
                    @include('website._partials.footer-menu')
                </ul>
            </div>

            <!-- Box 2 -->
            @if ($hasFooterContactInfo)
            <div class="box-2">
                <h4 class="title">Dolphinevent</h4>

                    <p>We make event ticket sales and bookings simple and seamless. Sell your event tickets, manage bookings, and offer a smooth experience for your attendees. Start today!</p>

                <ul class="i-list">
                    @if ($contact?->email)
                        <li>
                            <a target="_blank"
                                href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $contact?->email }}">
                                <i class="fa-regular fa-envelope"></i> {{ $contact?->email }}
                            </a>
                        </li>
                    @endif

                    @if ($contact?->phone_number_1 || $contact?->phone_number_2)
                        <li>
                            @if ($contact?->phone_number_1)
                                <a href="tel:{{ $contact?->phone_prefix_1 }}{{ $contact?->phone_number_1 }}">
                                    <i class="fa-solid fa-phone"></i> ({{ $contact?->phone_prefix_1 }})
                                    {{ $contact?->phone_number_1 }}
                                </a>
                            @else
                                <a href="tel:{{ $contact?->phone_prefix_2 }}{{ $contact?->phone_number_2 }}">
                                    <i class="fa-solid fa-phone"></i> ({{ $contact?->phone_prefix_2 }})
                                    {{ $contact?->phone_number_2 }}
                                </a>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
            @endif
        </div>

        <!-- FOOTER BOTTOM 🥗 -->
        <div class="foot-bottom">
            @include('website._partials.backtotop')
            <div class="box-1">
                <p>
                    © <span id="year"></span> Copyright Dolphinevent. All Rights Reserved
                </p>
            </div>
            @if ($hasFooterSocialLinks)
                <div class="box-2">
                    <!-- //Social Links -->
                    @include('website._partials.social-links')
                </div>
            @endif
        </div>
    </div>
</footer>

<div class="footer-empty"></div>
