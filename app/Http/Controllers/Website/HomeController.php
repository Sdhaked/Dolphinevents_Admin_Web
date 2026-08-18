<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\HomePageContent;
use App\Models\Slider;

class HomeController extends Controller
{
    private const GALLERY_LOAD_COUNT = 8;
    private const PAST_EVENTS_LOAD_COUNT = 8;

    /**
     * index
     */
    public function index()
    {
        $content = HomePageContent::firstOrNew(['id' => 1]);

        $content->hero_slider = Slider::where('type', 1)->get();
        $content->info_slider = Slider::where('type', 2)->get();
        $content->event_count = sprintf("%02d", min(Event::count(), 9999));
        $content->gallery = Gallery::latest('id')->take(self::GALLERY_LOAD_COUNT)->get();
        $content->gallery_total = Gallery::count();

        // Featured events: show upcoming/active first, then recently expired ones.
        $featuredActiveOrUpcomingEvents = Event::query()
            ->featured()
            ->activeOrUpcoming()
            ->orderBy('from_date')
            ->orderBy('from_time')
            ->get();

        $featuredExpiredEvents = Event::query()
            ->featured()
            ->expired()
            ->orderByRaw('COALESCE(to_date, from_date) DESC')
            ->orderByDesc('to_time')
            ->get();

        $content->featured_events = $featuredActiveOrUpcomingEvents
            ->concat($featuredExpiredEvents)
            ->values();

        // Active today
        $content->active_today_events = Event::activeToday()
            ->orderBy('from_date')
            ->orderBy('from_time')
            ->get();

        // Upcoming events
        $content->upcoming_events = Event::activeOrUpcoming()
            ->where('status', Event::STATUS_PUBLISHED)
            ->orderBy('from_date')
            ->orderBy('from_time')
            ->get();

        // Past events
        $content->past_events = $this->pastEventsQuery()
            ->take(self::PAST_EVENTS_LOAD_COUNT)
            ->get();
        $content->past_events_total = $this->pastEventsQuery()->count();

        return view('website.home.index', compact('content'));
    }

    public function loadMoreGallery()
    {
        $offset = max(0, (int) request('offset', 0));
        $images = Gallery::latest('id')
            ->skip($offset)
            ->take(self::GALLERY_LOAD_COUNT)
            ->get();

        return response()->json([
            'html' => view('website.home._partials.gallery-items', compact('images'))->render(),
            'loaded_count' => $images->count(),
            'has_more' => Gallery::count() > ($offset + $images->count()),
            'next_offset' => $offset + $images->count(),
        ]);
    }

    public function loadMorePastEvents()
    {
        $offset = max(0, (int) request('offset', 0));
        $events = $this->pastEventsQuery()
            ->skip($offset)
            ->take(self::PAST_EVENTS_LOAD_COUNT)
            ->get();

        $total = $this->pastEventsQuery()->count();

        return response()->json([
            'html' => view('website.home._partials.past-event-items', compact('events'))->render(),
            'loaded_count' => $events->count(),
            'has_more' => $total > ($offset + $events->count()),
            'next_offset' => $offset + $events->count(),
        ]);
    }

    private function pastEventsQuery()
    {
        return Event::expired()
            ->orderByRaw('COALESCE(to_date, from_date) DESC')
            ->orderByDesc('to_time');
    }
}
