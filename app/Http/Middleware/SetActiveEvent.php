<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetActiveEvent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('active_event_id')) {

            // Try to find the most relevant event
            $event = Event::query()
                ->where('status', 1)
                ->orderByDesc('created_at')
                ->first();

            // If found, store its ID in session
            if ($event) {
                Session::put('active_event_id', $event->id);
            }
        }

        return $next($request);
    }
}
