<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Event;
use App\Models\TicketCounter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Set pagination theme
        Paginator::useBootstrapFive();

        // 2. Share Ticket Sold counts with the sidebar partial
        View::composer('admin._partials.sidebar', function ($view) {
            $eventId = session('active_event_id');
            
            $view->with([
                'activeEvent' => $eventId ? Event::find($eventId) : null,
                'soldCount'  => TicketCounter::where('event_id', $eventId)->confirmed()->where('is_viewed', 0)->count(),
                'failedCount' => TicketCounter::where('event_id', $eventId)->failedOrPendingVerification()->where('is_viewed', 0)->count(),
                'trashCount' => TicketCounter::onlyTrashed()->where('event_id', $eventId)->count(),
                'hasNew'     => TicketCounter::where('is_viewed', 0)->where('event_id', $eventId)->confirmed()->exists(),
            ]);
        });
    }
}
