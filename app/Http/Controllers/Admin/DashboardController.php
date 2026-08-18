<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Event;
use App\Mail\EventReminderMail;
use App\Models\TicketCounter;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
    {
        // Check if any events exist
        $hasEvents = Event::exists();
        
        if (!$hasEvents) {
            // Clear any stale session data
            session()->forget('active_event_id');
            return view('admin.dashboard.index', compact('hasEvents'));
        }
        
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        if (!$event) {
            // Set first available event as active
            $event = Event::first();
            if ($event) {
                session(['active_event_id' => $event->id]);
            } else {
                session()->forget('active_event_id');
                $hasEvents = false;
                return view('admin.dashboard.index', compact('hasEvents'));
            }
        }

        $ticketStats = DB::table('ticket_types')
            ->leftJoin('ticket_counters', function($join) use ($event) {
                $join->on('ticket_types.id', '=', 'ticket_counters.ticket_type_id')
                     ->whereNull('ticket_counters.deleted_at')
                     ->where('ticket_counters.event_id', '=', $event->id);
            })
            ->where('ticket_types.event_id', $event->id)
            ->whereNull('ticket_types.deleted_at')
            ->select(
                'ticket_types.title',
                'ticket_types.total_tickets as capacity',
            )
            ->selectRaw(
                'IFNULL(SUM(CASE WHEN ticket_counters.booking_status = ? THEN ticket_counters.qty ELSE 0 END), 0) as sold_count',
                [TicketCounter::STATUS_CONFIRMED]
            )
            ->selectRaw(
                'IFNULL(SUM(CASE WHEN ticket_counters.booking_status IN (?, ?) THEN ticket_counters.qty ELSE 0 END), 0) as failed_count',
                [TicketCounter::STATUS_FAILED, TicketCounter::STATUS_PENDING_VERIFICATION]
            )
            ->groupBy('ticket_types.id', 'ticket_types.title', 'ticket_types.total_tickets')
            ->get();

        $totalSold = $ticketStats->sum('sold_count');
        $totalFailed = $ticketStats->sum('failed_count');
        $totalCapacity = $ticketStats->sum('capacity');
        $failedTicketStats = $ticketStats->filter(fn ($stat) => (int) $stat->failed_count > 0)->values();
        
        $totalRevenue = DB::table('ticket_counters')
            ->where('event_id', $event->id)
            ->whereNull('deleted_at')
            ->where('booking_status', TicketCounter::STATUS_CONFIRMED)
            ->sum('total_amount');

        return view('admin.dashboard.index', compact('event', 'ticketStats', 'failedTicketStats', 'totalSold', 'totalFailed', 'totalCapacity', 'totalRevenue', 'hasEvents'));
    }


    /**
     * Send email reminder
     */
    public function sendReminder(Request $request)
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        if (!$event) {
            return redirect()->back()->with('error', 'No active event selected.');
        }

       
        // 1. Get the full records
        $customers = TicketCounter::where('event_id', $eventId)
                        ->whereNotNull('email')
                        ->get()
                        ->unique('email');

        foreach ($customers as $customer) {
            // 2. Pass the SINGLE $customer object, not the $customers collection
            Mail::to($customer->email)->send(new EventReminderMail($event, $customer));
        }

        // Update the Event record with tracking info
        $event->update([
            'last_reminder_sent_at' => now(),
            'last_reminded_by' => Auth::user()->name ?? 'Admin', 
        ]);

        return redirect()->back()->with('success', 'Reminders sent to ' . $customers->count() . ' attendees.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
