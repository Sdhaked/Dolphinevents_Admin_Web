<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BulkDiscountSlbController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Event\GalleryController;
use App\Http\Controllers\Admin\Event\SponsorController;
use App\Http\Controllers\Admin\Event\SupportController;
use App\Http\Controllers\Admin\Event\ContestentsController;
use App\Http\Controllers\Admin\Event\EventController;
use App\Http\Controllers\Admin\Event\Slider\InfoSliderController as SliderInfoSliderController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\Pages\AboutController;
use App\Http\Controllers\Admin\Pages\ContactController;
use App\Http\Controllers\Admin\Pages\EventArchiveController;
use App\Http\Controllers\Admin\Pages\HomeController;
use App\Http\Controllers\Admin\Pages\PolicyController;
use App\Http\Controllers\Admin\Pages\TermsController;
use App\Http\Controllers\Admin\Pages\TicketsController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\Slider\HeroSliderController;
use App\Http\Controllers\Admin\Slider\InfoSliderController;
use App\Http\Controllers\Admin\TicketCheckerController;
use App\Http\Controllers\Admin\TicketTypeController;
use App\Http\Controllers\Admin\TicketSoldController;
use App\Http\Controllers\Admin\DiscountCouponController;
use App\Http\Controllers\Admin\EventServiceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Website\AboutController as WebsiteAboutController;
use App\Http\Controllers\Website\BaseController;
use App\Http\Controllers\Website\ContactController as WebsiteContactController;
use App\Http\Controllers\Website\EventController as WebsiteEventController;
use App\Http\Controllers\Website\HomeController as WebsiteHomeController;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\SetActiveEvent;
use Illuminate\Support\Facades\Route;

// Root route is handled by WebsiteHomeController below

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('website.csrf_token');

/**
 * Admin guest routes
 */
Route::prefix('admin')->controller(AuthController::class)->middleware('guest')->group(function () {
    Route::get('/login', 'login')->name('login');
    Route::get('/forgot-password', 'forgotPassword')->name('forgot.password');
    Route::get('/set-new-password', 'setNewPassword')->name('set.new.password');

    Route::post('/login', 'loginPost')->name('login.post');
    Route::post('/login/verify-otp', 'verifyLoginOtp')->name('login.verify.otp');
    Route::post('/login/resend-otp', 'resendLoginOtp')->name('login.resend.otp');
    Route::post('/login/change-email', 'changeLoginEmail')->name('login.change.email');

    Route::post('/forgot-password', 'sendResetLink')->name('password.email');
    Route::post('/reset-password', 'reset')->name('password.reset');
});

/**
 * Admin Routes
 */
Route::prefix('admin')->middleware(['auth', SetActiveEvent::class, EnsureAdminPermission::class])->group(function () {
    Route::delete('/media/{target}/{id?}', [MediaController::class, 'destroy'])
        ->name('admin.media.destroy');

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('logout');
        Route::get('/profile', 'profile')->name('profile');
        Route::get('/profile/edit', 'editProfile')->name('profile.edit');
        Route::post('/profile/update', 'updateProfile')->name('profile.update');
        Route::post('/profile/change-password', 'changePassword')->name('profile.change.password');
    });

    /**
     * Dashboard
     */
    Route::controller(DashboardController::class)->group(function () {
        //Load dashboard stats
        Route::get('/', 'index')->name('admin.dashboard.index');

        //Send reminder 
        Route::post('/send-event-reminder', 'sendReminder')->name('admin.send.reminder');
    });

    /**
     * Gallery
     */
    Route::controller(AdminGalleryController::class)->prefix('gallery')->group(function () {
        Route::get('/', 'index')->name('admin.gallery.index');
        Route::post('/', 'store')->name('admin.gallery.store');
        Route::delete('/delete-all', 'destroyAll')->name('admin.gallery.destroy_all');
        Route::post('/{id}', 'update')->name('admin.gallery.update');
        Route::delete('/{id}', 'destroy')->name('admin.gallery.destroy');
    });

    /**
     * Settings
     */
    Route::controller(SettingController::class)->prefix('settings')->group(function () {
        Route::get('/', 'index')->name('admin.settings.index');
    });

    /**
     * Master Control
     */
    Route::prefix('master-control')->group(function () {
        Route::controller(UserController::class)->prefix('users')->group(function () {
            Route::get('/', 'index')->name('admin.users.index');
            Route::get('/create', 'create')->name('admin.users.create');
            Route::post('/store', 'store')->name('admin.users.store');
            Route::get('/edit/{user}', 'edit')->name('admin.users.edit');
            Route::post('/update/{user}', 'update')->name('admin.users.update');
            Route::post('/activate/{id}', 'activate')->name('admin.users.activate');
            Route::delete('/destroy/{user}', 'destroy')->name('admin.users.destroy');
        });

        Route::controller(RoleController::class)->prefix('roles')->group(function () {
            Route::get('/', 'index')->name('admin.roles.index');
            Route::get('/create', 'create')->name('admin.roles.create');
            Route::post('/store', 'store')->name('admin.roles.store');
            Route::get('/edit/{role}', 'edit')->name('admin.roles.edit');
            Route::post('/update/{role}', 'update')->name('admin.roles.update');
            Route::delete('/destroy/{role}', 'destroy')->name('admin.roles.destroy');
        });

        Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
            Route::get('/', 'index')->name('admin.permissions.index');
            Route::get('/create', 'create')->name('admin.permissions.create');
            Route::post('/store', 'store')->name('admin.permissions.store');
            Route::get('/edit/{permission}', 'edit')->name('admin.permissions.edit');
            Route::post('/update/{permission}', 'update')->name('admin.permissions.update');
            Route::delete('/destroy/{permission}', 'destroy')->name('admin.permissions.destroy');
        });
    });

    /**
     * Ticket Checkers
     */
    Route::controller(TicketCheckerController::class)->prefix('checkers')->middleware('auth')->group(function () {
        Route::get('/', 'index')->name('admin.checkers.index');
        Route::get('/create', 'create')->name('admin.checkers.create');
        Route::post('/store', 'store')->name('admin.checkers.store');
        Route::get('/{id}', 'show')->name('admin.checkers.view');
        Route::get('/edit/{id}', 'edit')->name('admin.checkers.edit');
        Route::post('/update/{id}', 'update')->name('admin.checkers.update');
        Route::delete('/destroy/{id}', 'destroy')->name('admin.checkers.destroy');
    });

    /**
     * Ticket Types
     */
    Route::controller(TicketTypeController::class)->prefix('/ticket-types')->group(function () {
        Route::get('/', 'index')->name('admin.ticket.types.index');
        Route::get('/create', 'create')->name('admin.ticket.types.create');
        Route::get('/createSeats', 'createSeats')->name('admin.ticket.types.createSeats');
        Route::post('/store', 'store')->name('admin.ticket.types.store');
        Route::get('/{id}', 'show')->name('admin.ticket.types.show');
        Route::get('/edit/{id}', 'edit')->name('admin.ticket.types.edit');
        Route::get('/editSeats/{id}', 'editSeats')->name('admin.ticket.types.editSeats');
        Route::post('/update/{id}', 'update')->name('admin.ticket.types.update');
        Route::delete('/destroy/{id}', 'destroy')->name('admin.ticket.types.destroy');
    });

    /**
     * Discount Coupons
     */
    Route::controller(DiscountCouponController::class)->prefix('/discount-coupons')->group(function () {
        Route::get('/', 'index')->name('admin.discount.coupons.index');
        Route::get('/create', 'create')->name('admin.discount.coupons.create');
        Route::post('/store', 'store')->name('admin.discount.coupons.store');
        Route::get('/{id}', 'show')->name('admin.discount.coupons.show');
        Route::get('/edit/{id}', 'edit')->name('admin.discount.coupons.edit');
        Route::post('/update/{id}', 'update')->name('admin.discount.coupons.update');
        Route::delete('/destroy/{id}', 'destroy')->name('admin.discount.coupons.destroy');
    });

    Route::controller(EventServiceController::class)->prefix('/event-services')->group(function () {
        Route::get('/', 'index')->name('admin.event.services.index');
        Route::post('/', 'store')->name('admin.event.services.store');
        Route::put('/{eventService}', 'update')->name('admin.event.services.update');
        Route::delete('/{eventService}', 'destroy')->name('admin.event.services.destroy');
    });

    /**
     * Ticket Sold
     */
    Route::controller(TicketSoldController::class)->prefix('/ticket-sold')->group(function () {
        // Ticket sold list
        Route::get('/', 'index')->name('admin.ticket.sold.index');
        
        // Ticket trash sold list
        Route::get('/trash', 'trash')->name('admin.ticket.sold.trash');

        //Export Ticket sold
        Route::get('/export', 'export')->name('admin.ticket.sold.export');
        
        //View Record
        Route::get('/view/{id}', 'show')->name('admin.ticket.sold.show');

        // Regenerate PDF
        Route::post('/regenerate-pdf/{id}', 'regeneratePDF')->name('admin.ticket.sold.regenerate_pdf');

        // Resend Email
        Route::post('/resend-email/{id}', 'resendEmail')->name('admin.ticket.sold.resend_email');

        // Move to trash
        Route::delete('/destroy/{id}', 'destroy')->name('admin.ticket.sold.destroy');

        // Permanent Remove
        Route::delete('/force-delete/{id}', 'forceDelete')->name('admin.ticket.sold.force_delete');

        // Restore from trash
        Route::post('/restore/{id}', 'restore')->name('admin.ticket.sold.restore');

        //Empty trash
        Route::delete('/empty-trash', 'emptyTrash')->name('admin.ticket.sold.empty_trash');
    });

    /**
     * Ticket Failed / Pending Verification
     */
    Route::controller(TicketSoldController::class)->prefix('/ticket-failed')->group(function () {
        Route::get('/', 'failed')->name('admin.ticket.failed.index');
        Route::get('/view/{id}', 'failedShow')->name('admin.ticket.failed.show');
        Route::post('/refund/{id}', 'markRefunded')->name('admin.ticket.failed.refund');
        Route::delete('/destroy/{id}', 'destroy')->name('admin.ticket.failed.destroy');
    });

    /**
     * Ticket Counter
     */
    Route::resource('ticket-counter', App\Http\Controllers\Admin\TicketCounterController::class);
    Route::prefix('ticket-counter')->controller(App\Http\Controllers\Admin\TicketCounterController::class)->group(function () {
        Route::get('/api/ticket-types', 'getTicketTypes')->name('admin.ticket.counter.api.ticket.types');
        Route::get('/api/available-quantity/{ticketTypeId}', 'getAvailableQuantity')->name('admin.ticket.counter.api.available.quantity');
        Route::get('/api/states/{countryId}', 'getStates')->name('admin.ticket.counter.api.states');
        Route::post('/api/check-bulk-discount', 'checkBulkDiscount')->name('admin.ticket.counter.api.check.bulk.discount');
        Route::post('/api/apply-coupon', 'applyCoupon')->name('admin.ticket.counter.api.apply.coupon');
        Route::post('/api/calculate-bill', 'calculateBill')->name('admin.ticket.counter.api.calculate.bill');
    });

    /**
     * Bulk Discounts (AJAX API for modals)
     */
    Route::controller(BulkDiscountSlbController::class)->prefix('/bulk-discount')->group(function () {
        Route::get('/', 'index')->name('admin.bulk-discount.index');
        Route::get('/{bulkDiscount}/edit', 'edit')->name('admin.bulk-discount.edit');
        Route::post('/', 'store')->name('admin.bulk-discount.store');
        Route::put('/{bulkDiscount}', 'update')->name('admin.bulk-discount.update');
        Route::delete('/{bulkDiscount}', 'destroy')->name('admin.bulk-discount.destroy');
        Route::get('/{bulkDiscount}', 'show')->name('admin.bulk-discount.show');
    });

    /**
     * Pages
     */
    Route::prefix('pages')->middleware('auth')->group(function () {
        /**
         * Home Page
         */
        Route::controller(HomeController::class)->prefix('home')->group(function () {
            Route::get('/', 'index')->name('admin.pages.home.index');
            Route::post('/', 'store')->name('admin.pages.home.store');
        });

        /**
         * About Page
         */
        Route::controller(AboutController::class)->prefix('about')->group(function () {
            Route::get('/', 'index')->name('admin.pages.about.index');
            Route::post('/', 'store')->name('admin.pages.about.store');
        });

        /**
         * Contact Page
         */
        Route::controller(ContactController::class)->prefix('contact')->group(function () {
            Route::get('/', 'index')->name('admin.pages.contact.index');
            Route::post('/', 'store')->name('admin.pages.contact.store');
        });

        /**
         * Event Archive
         */
        Route::controller(EventArchiveController::class)->prefix('event-archive')->group(function () {
            Route::get('/', 'index')->name('admin.pages.event_archive.index');
            Route::post('/', 'store')->name('admin.pages.event_archive.store');
        });

        /**
         * Tickets
         */
        Route::controller(TicketsController::class)->prefix('tickets')->group(function () {
            Route::get('/', 'index')->name('admin.pages.tickets.index');
            Route::post('/', 'store')->name('admin.pages.tickets.store');
        });

        /**
         * Terms
         */
        Route::controller(TermsController::class)->prefix('terms')->group(function () {
            Route::get('/', 'index')->name('admin.pages.terms');
            Route::post('/', 'store')->name('admin.pages.terms.store');
        });

        /**
         * Policy
         */
        Route::controller(PolicyController::class)->prefix('policy')->group(function () {
            Route::get('/', 'index')->name('admin.pages.policy');
            Route::post('/', 'store')->name('admin.pages.policy.store');
        });
    });

    /**
     * Sliders
     */
    Route::prefix('sliders')->middleware('auth')->group(function () {
        /**
         * Hero Slider
         */
        Route::controller(HeroSliderController::class)->prefix('hero')->group(function () {
            Route::get('/', 'index')->name('admin.sliders.hero.index');
            Route::post('/store', 'store')->name('admin.sliders.hero.store');
            Route::post('/update/{id}', 'update')->name('admin.sliders.hero.update');
            Route::delete('/destroy/{id}', 'destroy')->name('admin.sliders.hero.destroy');
        });

        /**
         * Ad Slider
         */
        Route::controller(InfoSliderController::class)->prefix('info')->group(function () {
            Route::get('/', 'index')->name('admin.sliders.info.index');
            Route::post('/store', 'store')->name('admin.sliders.info.store');
            Route::post('/update/{id}', 'update')->name('admin.sliders.info.update');
            Route::delete('/destroy/{id}', 'destroy')->name('admin.sliders.info.destroy');
        });
    });

    Route::prefix('events')->group(function () {
        /**
         * Events
         */
        Route::controller(EventController::class)->group(function () {
            Route::get('/', 'eventsList')->name('admin.events.list');
            Route::post('/store', 'store')->name('admin.events.store');
            Route::post('/duplicate', 'duplicate')->name('admin.events.duplicate');
            Route::get('/edit', 'edit')->name('admin.events.edit');
            Route::post('/update', 'update')->name('admin.events.update');
            Route::post('/editor/upload-image', 'uploadEditorImage')->name('admin.events.editor.upload_image');
            Route::get('', 'show')->name('admin.events.show');
            Route::post('/set-current', 'setCurrentEvent')->name('admin.events.set.current');
            Route::get('/get-current', 'getCurrentEvent')->name('admin.events.get.current');
            Route::delete('/destroy/{id}', 'destroy')->name('admin.events.destroy');
        });

        /**
         * Sponsors
         */
        Route::controller(SponsorController::class)->prefix('sponsors')->group(function () {
            Route::get('/', 'index')->name('admin.sponsors.index');
            Route::post('/store', 'store')->name('admin.sponsors.store');
            Route::post('/update/{sponsorId}', 'update')->name('admin.sponsors.update');
            Route::delete('/destroy/{sponsorId}', 'destroy')->name('admin.sponsors.destroy');
        });
       
        /**
         * Contestents
         */
        Route::controller(ContestentsController::class)->prefix('contestents')->group(function () {
            Route::get('/', 'index')->name('admin.contestents.index');
            Route::get('/create', 'create')->name('admin.contestents.create');
            Route::post('/store', 'store')->name('admin.contestents.store');
            Route::get('/{contestentId}', 'show')->name('admin.contestents.show');
            Route::get('/edit/{contestentId}', 'edit')->name('admin.contestents.edit');
            Route::post('/update/{contestentId}', 'update')->name('admin.contestents.update');
            Route::delete('/destroy/{contestentId}', 'destroy')->name('admin.contestents.destroy');
        });

        /**
         * Sliders
         */
        Route::prefix('sliders')->group(function () {
            /**
             * Info Slider
             */
            Route::controller(SliderInfoSliderController::class)->prefix('info-slider')->group(function () {
                Route::get('/', 'index')->name('admin.event.sliders.info.index');
                Route::post('/store', 'store')->name('admin.event.sliders.info.store');
                Route::post('/update/{sliderId}', 'update')->name('admin.event.sliders.info.update');
                Route::delete('/destroy/{sliderId}', 'destroy')->name('admin.event.sliders.info.destroy');
            });
        });

        /**
         * Gallery
         */
        Route::controller(GalleryController::class)->prefix('gallery')->group(function () {
            Route::get('/', 'index')->name('admin.event.gallery.index');
            Route::post('/store', 'store')->name('admin.event.gallery.store');
            Route::post('/update/{galleryId}', 'update')->name('admin.event.gallery.update');
            Route::delete('/destroy/{galleryId}', 'destroy')->name('admin.event.gallery.destroy');
        });

        /**
         * Support
         */
        Route::controller(SupportController::class)->prefix('support')->group(function () {
            Route::get('/', 'index')->name('admin.event.support.index');
            Route::post('/', 'store')->name('admin.event.support.store');
        });
    });
});


// Website Routes
/**
 * Home Page
 */
Route::controller(WebsiteHomeController::class)->group(function () {
    Route::get('/', 'index')->name('website.home.index');
    Route::get('/gallery/load-more', 'loadMoreGallery')->name('website.home.gallery.load_more');
    Route::get('/past-events/load-more', 'loadMorePastEvents')->name('website.home.past_events.load_more');
});

/**
 * About Page
 */
Route::controller(WebsiteAboutController::class)->group(function () {
    Route::get('/about', 'index')->name('website.about.index');
});

/**
 * Contact Page
 */
Route::controller(WebsiteContactController::class)->group(function () {
    Route::get('/contact', 'index')->name('website.contact.index');
});

/**
 * Legal Pages
 */
Route::controller(BaseController::class)->group(function () {
    Route::get('/policy', 'policy')->name('website.policy');
    Route::get('/terms-conditions', 'termsConditions')->name('website.terms.conditions');
});

/**
 * Events
 */
Route::controller(WebsiteEventController::class)->prefix('events')->group(function () {
    // event listing 
    Route::get('/', 'index')->name('website.events.index');
    
    Route::post('checkout/initiate', 'initiateCheckout')->name('website.events.checkout.initiate'); 
    
    // Stripe checkout session creation
    Route::post('checkout/stripe', 'createStripeCheckout')->name('website.events.checkout.stripe');
    Route::get('checkout/states/{countryId}', 'getStates')->name('website.events.checkout.states');

    // Stripe redirects
    Route::get('checkout/stripe-success/{paymentTransaction}', 'stripeSuccess')->name('website.events.checkout.stripe.success');
    Route::get('checkout/stripe-cancel/{paymentTransaction}', 'stripeCancel')->name('website.events.checkout.stripe.cancel');
    Route::get('checkout/success', 'stripeSuccess')->name('website.events.checkout.success');

    Route::get('checkout/cancel', 'stripeCancel')->name('website.events.checkout.cancel');

    Route::get('checkout/prepay-verify/{booking_id}', 'prePaymentEmailVerification')->name('website.events.checkout.prepay.verify');
    Route::post('checkout/prepay-verify/{booking_id}', 'verifyPrePaymentOtp')->name('website.events.checkout.prepay.verify_otp');
    Route::post('checkout/prepay-resend-otp/{booking_id}', 'resendPrePaymentOtp')->name('website.events.checkout.prepay.resend_otp');
    Route::post('checkout/prepay-change-email/{booking_id}', 'changePrePaymentEmail')->name('website.events.checkout.prepay.change_email');
    Route::get('checkout/payment/{booking_id}', 'startVerifiedStripeCheckout')->name('website.events.checkout.payment');

    Route::get('checkout/verify/{booking_id}', 'checkoutEmailVerification')->name('website.events.checkout.verify');
    Route::post('checkout/verify/{booking_id}', 'verifyCheckoutOtp')->name('website.events.checkout.verify_otp');
    Route::post('checkout/resend-otp/{booking_id}', 'resendCheckoutOtp')->name('website.events.checkout.resend_otp');
    Route::post('checkout/change-email/{booking_id}', 'changeCheckoutEmail')->name('website.events.checkout.change_email');

    Route::get('checkout/{token}', 'checkout')->name('website.events.checkout');
    
    // stripe success
    Route::get('checkout/success/{booking_id}','showSuccess')->name('website.events.checkout.success.page');

    // event venue layout
    Route::get('event-venue/{event:slug}', 'event_venue')->name('website.events.event_venue');

    // event tickets simple booking system
    Route::get('event-tickets/{event:slug}', 'event_tickets')->name('website.events.event_tickets');

    // event voting system
    Route::get('voting/{event:slug}/verify', 'votingEmailVerification')->name('website.events.voting.verify');
    Route::post('voting/{event:slug}/send-otp', 'sendVotingOtp')->name('website.events.voting.send_otp');
    Route::post('voting/{event:slug}/resend-otp', 'resendVotingOtp')->name('website.events.voting.resend_otp');
    Route::post('voting/{event:slug}/change-email', 'changeVotingEmail')->name('website.events.voting.change_email');
    Route::post('voting/{event:slug}/verify-otp', 'verifyVotingOtp')->name('website.events.voting.verify_otp');
    Route::get('voting/{event:slug}', 'eventVoting')->name('website.events.voting.show');
    Route::post('voting/{event:slug}/submit', 'submitVoting')->name('website.events.voting.submit');

    // Seat removal during checkout hold
    Route::post('checkout/remove-seat', 'removeSeatFromHold')->name('website.events.checkout.remove_seat');

    // event detail
    Route::get('/{event:slug}', 'show')->name('website.events.show');

    

});
