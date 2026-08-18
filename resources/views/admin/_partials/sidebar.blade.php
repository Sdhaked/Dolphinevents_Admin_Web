@php
    $permissionTablesReady = \Illuminate\Support\Facades\Schema::hasTable('permissions')
        && \Illuminate\Support\Facades\Schema::hasTable('role_permissions');
    $sidebarPermissionSlugs = collect();
    $authUser = auth()->user();

    if ($permissionTablesReady && $authUser?->role) {
        $sidebarPermissionSlugs = \App\Models\Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $authUser->role)
            ->pluck('permissions.slug');
    }

    $canSeeSidebar = function ($permissions) use ($permissionTablesReady, $sidebarPermissionSlugs) {
        if (!$permissionTablesReady) {
            return true;
        }

        return $sidebarPermissionSlugs->intersect((array) $permissions)->isNotEmpty();
    };

    $hasEvents = \App\Models\Event::query()->exists();

    $canCreateEvents = $canSeeSidebar(['events-create-events', 'events-manage-events']);
    $canDuplicateEvents = $canSeeSidebar(['events-duplicate-events', 'events-manage-events']);
    $canSetCurrentEvent = $canSeeSidebar(['events-set-current-event', 'events-view-current-event', 'events-manage-events']);
    $canOpenEventModal = $canCreateEvents || $canDuplicateEvents;

    $canDashboard = $canSeeSidebar(['dashboard-view-dashboard']);
    $canTicketCheckersView = $canSeeSidebar(['ticket-checkers-view-ticket-checkers', 'ticket-checkers-manage-ticket-checkers']);
    $canTicketCheckersCreate = $canSeeSidebar(['ticket-checkers-create-ticket-checkers', 'ticket-checkers-manage-ticket-checkers']);
    $canTicketCheckers = $canTicketCheckersView || $canTicketCheckersCreate;
    $canEditEvent = $canSeeSidebar(['events-edit-events', 'events-manage-events']);
    $canTicketCounter = $canSeeSidebar(['ticket-counter-view-ticket-counter', 'ticket-counter-manage-ticket-counter']);
    $canTicketSoldView = $canSeeSidebar(['ticket-sold-view-sold-tickets', 'ticket-sold-manage-ticket-sold']);
    $canTicketSoldTrash = $canSeeSidebar(['ticket-sold-view-ticket-sold-trash', 'ticket-sold-manage-ticket-sold-trash']);
    $canTicketSold = $canTicketSoldView || $canTicketSoldTrash;
    $canTicketFailed = $canSeeSidebar(['ticket-failed-view-failed-tickets', 'ticket-failed-manage-ticket-failed']);
    $canTicketTypesView = $canSeeSidebar(['ticket-types-view-ticket-types', 'ticket-types-manage-ticket-types']);
    $canTicketTypesCreate = $canSeeSidebar(['ticket-types-create-ticket-types', 'ticket-types-manage-ticket-types']);
    $canTicketTypes = $canTicketTypesView || $canTicketTypesCreate;
    $canDiscountCouponsView = $canSeeSidebar(['discount-coupons-view-discount-coupons', 'discount-coupons-manage-discount-coupons']);
    $canDiscountCouponsCreate = $canSeeSidebar(['discount-coupons-create-discount-coupons', 'discount-coupons-manage-discount-coupons']);
    $canDiscountCoupons = $canDiscountCouponsView || $canDiscountCouponsCreate;
    $canEventServices = $canSeeSidebar(['event-services-view-event-services', 'event-services-manage-event-services', 'ticket-types-manage-ticket-types']);
    $canContestents = $canSeeSidebar(['contestents-view-contestents', 'contestents-manage-contestents']);
    $canSponsors = $canSeeSidebar(['sponsors-view-sponsors', 'sponsors-manage-sponsors']);
    $canEventInfoSlider = $canSeeSidebar(['event-info-slider-view-event-info-slider', 'event-info-slider-manage-event-info-slider']);
    $canEventGallery = $canSeeSidebar(['event-gallery-view-event-gallery', 'event-gallery-manage-event-gallery']);
    $canEventSupport = $canSeeSidebar(['event-support-view-event-support', 'event-support-manage-event-support']);

    $canMasterUsers = $canSeeSidebar(['users-view-users', 'users-manage-users']);
    $canMasterRoles = $canSeeSidebar(['roles-view-roles', 'roles-manage-roles']);
    $canMasterPermissions = $canSeeSidebar(['permissions-view-permissions', 'permissions-manage-permissions']);
    $canMasterControl = $canSeeSidebar(['master-control-view-master-control', 'master-control-manage-master-control'])
        || $canMasterUsers
        || $canMasterRoles
        || $canMasterPermissions;

    $canHomePage = $canSeeSidebar(['home-page-content-view-home-page-content', 'home-page-content-manage-home-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canAboutPage = $canSeeSidebar(['about-page-content-view-about-page-content', 'about-page-content-manage-about-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canContactPage = $canSeeSidebar(['contact-page-content-view-contact-page-content', 'contact-page-content-manage-contact-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canEventArchivePage = $canSeeSidebar(['event-archive-page-content-view-event-archive-page-content', 'event-archive-page-content-manage-event-archive-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canTicketsPage = $canSeeSidebar(['tickets-page-content-view-tickets-page-content', 'tickets-page-content-manage-tickets-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canTermsPage = $canSeeSidebar(['terms-page-content-view-terms-page-content', 'terms-page-content-manage-terms-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canPolicyPage = $canSeeSidebar(['policy-page-content-view-policy-page-content', 'policy-page-content-manage-policy-page-content', 'page-content-view-page-content', 'page-content-manage-page-content']);
    $canPageContent = $canHomePage || $canAboutPage || $canContactPage || $canEventArchivePage || $canTicketsPage || $canTermsPage || $canPolicyPage;

    $canMainHeroSlider = $canSeeSidebar(['main-hero-slider-view-main-hero-slider', 'main-hero-slider-manage-main-hero-slider']);
    $canMainInfoSlider = $canSeeSidebar(['main-info-slider-view-main-info-slider', 'main-info-slider-manage-main-info-slider']);
    $canMainGallery = $canSeeSidebar(['main-gallery-view-main-gallery', 'main-gallery-manage-main-gallery']);

    $canShowEventTicketGroup = $hasEvents && ($canTicketCounter || $canTicketSold || $canTicketFailed || $canTicketTypes || $canDiscountCoupons || $canEventServices);
    $canShowEventOtherGroup = $hasEvents && ($canContestents || $canSponsors || $canEventInfoSlider || $canEventGallery || $canEventSupport);
    $canShowSiteContentGroup = $canMasterControl || $canPageContent || $canMainHeroSlider || $canMainInfoSlider || $canMainGallery;
@endphp

<nav class="side-nav">
    <div class="menu-items">
        <ul class="nav-ul">
            @if ($canOpenEventModal)
                <li class="main-li addEventBtn">
                    <button type="button" class="nav-link d-flex" style="width: 100%;" data-bs-toggle="modal"
                            data-bs-target="#createeventModal">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span class="link-name">Create Event</span>
                    </button>
                </li>
            @endif

            @if ($hasEvents && $canSetCurrentEvent)
                <div class="form-floating event-selector">
                    <x-admin.event-list />
                    <label for="events_list">Selected Event</label>
                </div>
            @endif

            @if ($canOpenEventModal || ($hasEvents && $canSetCurrentEvent))
                <hr style="margin: 1rem  22px; color:var(--color-border-100);">
            @endif

            @if ($canDashboard)
                <li class="main-li">
                    <a href="{{ route('admin.dashboard.index') }}" class="nav-link navJS">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span class="link-name">Dashboard</span>
                    </a>
                </li>
            @endif

            @if ($hasEvents)
                @if ($canTicketCheckers)
                    <li class="dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-secret"></i>
                            <span class="link-name">Ticket Checkers</span>
                        </button>
                        <ul class="dropdown-menu">
                            @if ($canTicketCheckersView)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.checkers.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> View All</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canTicketCheckersCreate)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.checkers.create') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Create New</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($canEditEvent)
                    <li class="main-li">
                        <a href="{{ route('admin.events.edit') }}" class="nav-link navJS">
                            <i class="fa-solid fa-calendar-week"></i>
                            <span class="link-name">Edit Event</span>
                        </a>
                    </li>
                @endif

                @if ($canShowEventTicketGroup)
                    <hr style="margin: 1rem  22px; color:var(--color-border-100);">

                    <li class="label-li">Event Ticket</li>

                    @if ($canTicketCounter)
                        <li class="main-li ticket-counter-simple">
                            <a href="{{ route('ticket-counter.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-store"></i>
                                <span class="link-name">Ticket Counter</span>
                            </a>
                        </li>

                        <li class="main-li ticket-counter-seat" style="display: none;">
                            <a href="{{ route('ticket-counter.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-store"></i>
                                <span class="link-name">Ticket Counter Seat</span>
                            </a>
                        </li>
                    @endif

                    @if ($canTicketSold)
                        <li class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-sack-dollar"></i>
                                <span class="link-name">Ticket Sold
                                    @if($hasNew)
                                        <span class="badge">New</span>
                                    @endif
                                </span>
                            </button>
                            <ul class="dropdown-menu">
                                @if ($canTicketSoldView)
                                    <li class="main-li">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.ticket.sold.index') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name">
                                                <i class="fa-solid fa-minus"></i> View Record
                                                @if($soldCount)
                                                    <span class="badge" id="sold-count">{{ $soldCount }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canTicketSoldTrash)
                                    <li class="main-li">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.ticket.sold.trash') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> Trash
                                                @if($trashCount)
                                                    <span class="badge" id="trash-count" style="{{ $trashCount > 0 ? '' : 'display: none;' }}">
                                                        {{ $trashCount }}
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($canTicketFailed)
                        <li class="main-li">
                            <a href="{{ route('admin.ticket.failed.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-ban"></i>
                                <span class="link-name">Ticket Failed
                                    @if($failedCount)
                                        <span class="badge">{{ $failedCount }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($canTicketTypes)
                        <li class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ticket"></i>
                                <span class="link-name">Ticket Types</span>
                            </button>
                            <ul class="dropdown-menu">
                                @if ($canTicketTypesView)
                                    <li class="main-li">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.ticket.types.index') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> View All</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canTicketTypesCreate)
                                    <li class="main-li create-ticket-simple">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.ticket.types.create') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> Create Ticket</span>
                                        </a>
                                    </li>
                                    <li class="main-li create-ticket-seat" style="display: none;">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.ticket.types.createSeats') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> Create Ticket Seat</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($canDiscountCoupons)
                        <li class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-tags"></i>
                                <span class="link-name">Discount Coupons</span>
                            </button>
                            <ul class="dropdown-menu">
                                @if ($canDiscountCouponsView)
                                    <li class="main-li">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.discount.coupons.index') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> View All</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canDiscountCouponsCreate)
                                    <li class="main-li">
                                        <a class="dropdown-item nav-link navJS" href="{{ route('admin.discount.coupons.create') }}">
                                            <i class="fa-regular fa-circle-dot"></i>
                                            <span class="link-name"><i class="fa-solid fa-minus"></i> Create New</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($canEventServices)
                        <li class="main-li">
                            <a href="{{ route('admin.event.services.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <span class="link-name">Event Services</span>
                            </a>
                        </li>
                    @endif
                @endif

                @if ($canShowEventOtherGroup)
                    <hr style="margin: 1rem  22px; color:var(--color-border-100);">

                    <li class="label-li">Event Other Content</li>

                    @if ($activeEvent?->enable_voting && $canContestents)
                        <li class="main-li">
                            <a href="{{ route('admin.contestents.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-user-astronaut"></i>
                                <span class="link-name">Contestents</span>
                            </a>
                        </li>
                    @endif

                    @if ($canSponsors)
                        <li class="main-li">
                            <a href="{{ route('admin.sponsors.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-user-tag"></i>
                                <span class="link-name">Sponsors</span>
                            </a>
                        </li>
                    @endif

                    @if ($canEventInfoSlider)
                        <li class="main-li">
                            <a href="{{ route('admin.event.sliders.info.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-panorama"></i>
                                <span class="link-name">Info Slider</span>
                            </a>
                        </li>
                    @endif

                    @if ($canEventGallery)
                        <li class="main-li">
                            <a href="{{ route('admin.event.gallery.index') }}" class="nav-link navJS">
                                <i class="fa-regular fa-images"></i>
                                <span class="link-name">Gallery</span>
                            </a>
                        </li>
                    @endif

                    @if ($canEventSupport)
                        <li class="main-li">
                            <a href="{{ route('admin.event.support.index') }}" class="nav-link navJS">
                                <i class="fa-solid fa-headset"></i>
                                <span class="link-name">Support</span>
                            </a>
                        </li>
                    @endif
                @endif
            @endif

            @if ($canShowSiteContentGroup)
                <hr style="margin: 1rem  22px; color:var(--color-border-100);">

                <li class="label-li">Site content<br/> (Global Content)</li>

                @if ($canMasterControl)
                    <li class="dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-shield"></i>
                            <span class="link-name">Master Control</span>
                        </button>
                        <ul class="dropdown-menu">
                            @if ($canMasterUsers)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.users.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Users</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canMasterRoles)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.roles.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Roles</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canMasterPermissions)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.permissions.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Permissions</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($canPageContent)
                    <li class="dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-file-signature"></i>
                            <span class="link-name">Page Content</span>
                        </button>
                        <ul class="dropdown-menu">
                            @if ($canHomePage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.home.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Home</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canAboutPage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.about.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> About</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canContactPage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.contact.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Contact</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canEventArchivePage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS"
                                       href="{{ route('admin.pages.event_archive.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Event Archive</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canTicketsPage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.tickets.index') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Tickets</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canTermsPage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.terms') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> T&amp;C</span>
                                    </a>
                                </li>
                            @endif
                            @if ($canPolicyPage)
                                <li class="main-li">
                                    <a class="dropdown-item nav-link navJS" href="{{ route('admin.pages.policy') }}">
                                        <i class="fa-regular fa-circle-dot"></i>
                                        <span class="link-name"><i class="fa-solid fa-minus"></i> Policy</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($canMainHeroSlider)
                    <li class="main-li">
                        <a href="{{ route('admin.sliders.hero.index') }}" class="nav-link navJS">
                            <i class="fa-solid fa-panorama"></i>
                            <span class="link-name">Main Hero Slider</span>
                        </a>
                    </li>
                @endif

                @if ($canMainInfoSlider)
                    <li class="main-li">
                        <a href="{{ route('admin.sliders.info.index') }}" class="nav-link navJS">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="link-name">Main Info Slider</span>
                        </a>
                    </li>
                @endif

                @if ($canMainGallery)
                    <li class="main-li">
                        <a href="{{ route('admin.gallery.index') }}" class="nav-link navJS">
                            <i class="fa-regular fa-images"></i>
                            <span class="link-name">Main Gallery</span>
                        </a>
                    </li>
                @endif
            @endif
        </ul>
    </div>
</nav>

@if ($canOpenEventModal)
    <div class="modal fade" id="createeventModal" tabindex="-1" aria-labelledby="createeventModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Create Event</h6>
                    <button type="button" class="event-btn-js" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    @if ($canCreateEvents && $canDuplicateEvents)
                        <div class="form-tabs d-flex flex-wrap gap-card">
                            <div>
                                <button type="button" class="check-btn">
                                    <input class="form-check-input" type="radio" value="create-new" name="choose-what"
                                           id="dfv55" checked>
                                    <label for="dfv55"> Create New Event</label>
                                </button>
                            </div>
                            <div>
                                <button type="button" class="check-btn">
                                    <input class="form-check-input" type="radio" value="duplicate-event"
                                           name="choose-what" id="fdv54vf">
                                    <label for="fdv54vf"> Duplicate Event</label>
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="create-event-form-box">
                        @if ($canCreateEvents)
                            <form class="grid-1 gap-card new-event-form needs-validation" novalidate method="POST">
                                <h3 class="hd-sm label-hd">New Event</h3>
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="title" id="dfv5" required>
                                    <label for="dfv5">Event Name*</label>
                                </div>

                                <div class="d-flex flex-wrap gap-card">
                                    <div>
                                        <button type="button" class="check-btn">
                                            <input class="form-check-input" type="radio" value="1" name="type"
                                                   id="dfv4" checked>
                                            <label for="dfv4">Simple Booking System</label>
                                        </button>
                                    </div>

                                    <div>
                                        <button type="button" class="check-btn">
                                            <input class="form-check-input" type="radio" value="2" name="type"
                                                   id="df8">
                                            <label for="df8">Seat Booking System</label>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-floating choose-pattern-fd54 d-none">
                                    <select class="form-select" id="dsc48" required="">
                                        <option selected>UK Stadium</option>
                                        <option>Dubai Stadium</option>
                                    </select>
                                    <label for="dsc48">Choose Layout</label>
                                </div>

                                <button type="submit" class="btn-md btn-sec">Create</button>
                            </form>
                        @endif

                        @if ($canDuplicateEvents)
                            <form class="grid-1 gap-card duplicate-event-form needs-validation {{ $canCreateEvents ? 'd-none' : '' }}" novalidate>
                                <h3 class="hd-sm label-hd">Duplicate Event</h3>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="df5" name="new_title" required>
                                    <label for="df5">Event Name*</label>
                                </div>

                                <div class="form-floating event-selector">
                                    <x-admin.event-list />
                                    <label for="dfg8">Event you want to duplicate?</label>
                                </div>

                                <button type="submit" class="btn-md btn-sec">Create</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@section('custom-script')
    @if (session('success'))
        <script>
            createNotification("success", "{{ session('success') }}", "");
        </script>
    @endif

    @if (session('error'))
        <script>
            createNotification("error", "{{ session('error') }}", "");
        </script>
    @endif

    @if (session('active_event_id'))
        @php
            $currentEvent = \App\Models\Event::find(session('active_event_id'));
        @endphp
        @if ($currentEvent)
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    updateMenuVisibility({{ $currentEvent->type }});
                });
            </script>
        @endif
    @endif

    <script>
        function getFirstErrorMessage(data, fallback = 'Please fix the validation errors.') {
            if (data?.errors) {
                const firstErrorGroup = Object.values(data.errors)[0];
                if (Array.isArray(firstErrorGroup) && firstErrorGroup.length) {
                    return firstErrorGroup[0];
                }
            }

            return data?.message || fallback;
        }

        function clearFormErrors(form) {
            if (!form) return;

            form.querySelectorAll('.is-invalid').forEach((field) => {
                field.classList.remove('is-invalid');
            });

            form.querySelectorAll('.invalid-feedback.dynamic-error').forEach((error) => {
                error.remove();
            });
        }

        function showFieldError(input, message) {
            if (!input || !message) return;

            input.classList.add('is-invalid');

            let errorDiv = input.parentNode.querySelector('.invalid-feedback.dynamic-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback dynamic-error d-block';
                input.parentNode.appendChild(errorDiv);
            }

            errorDiv.textContent = message;
        }

        function applyFormErrors(form, errors = {}) {
            clearFormErrors(form);

            const fieldMap = {
                title: '#dfv5',
                type: 'input[name="type"]:checked',
                new_title: '#df5',
                event_id: '#dfg8',
            };

            Object.entries(errors).forEach(([field, messages]) => {
                const selector = fieldMap[field] || `[name="${field}"]`;
                const input = form?.querySelector(selector) || document.querySelector(selector);
                const message = Array.isArray(messages) ? messages[0] : messages;

                showFieldError(input, message);
            });
        }

        $('.duplicate-event-form').on('submit', function(e) {
            e.preventDefault();
            clearFormErrors(this);

            const payload = {
                new_title: $('#df5').val(),
                event_id: $('#dfg8').val(),
            };

            fetch("{{ route('admin.events.duplicate') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    if (typeof createNotification === 'function') {
                        createNotification("success", data.message, "");
                    }
                    window.location.reload();
                } else {
                    applyFormErrors(document.querySelector('.duplicate-event-form'), data.errors || {});
                    if (typeof createNotification === 'function') {
                        createNotification("error", getFirstErrorMessage(data), "");
                    }
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.querySelector(".new-event-form");
            if (!form) return;

            form.addEventListener("submit", async function (e) {
                e.preventDefault();
                clearFormErrors(form);

                const formData = new FormData(form);

                formData.append("_token", "{{ csrf_token() }}");

                try {
                    const response = await fetch("{{ route('admin.events.store') }}", {
                        method: "POST",
                        body: formData,
                    });

                    const result = await response.json();

                    if (response.ok) {
                        form.reset();

                        const modal = form.closest('.modal');
                        const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
                        if (dismissBtn) dismissBtn.click();

                        createNotification("success", "Event created", "successfully");
                        setTimeout(() => {
                            location.reload();
                        }, 2000);

                    } else {
                        console.error(result);
                        applyFormErrors(form, result.errors || {});
                        createNotification("error", getFirstErrorMessage(result), "");
                    }
                } catch (error) {
                    console.error("Error:", error);
                }
            });
        });
    </script>
@endsection
