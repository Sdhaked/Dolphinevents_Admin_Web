<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketType;
use App\Models\VenueLayout;
use App\Models\Event;
use App\Models\DiscountCoupon;
use App\Models\BulkDiscount;
use App\Models\TicketCounter;
use App\Models\TicketTypeAgeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketTypeController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    private function withTicketsSoldSum($query)
    {
        return $query->withSum([
            'ticketCounters as tickets_sold' => fn ($counterQuery) => $counterQuery
                ->whereIn('booking_status', TicketCounter::countedSoldStatuses()),
        ], 'qty');
    }

    private function hasPendingPaymentLock(int $ticketTypeId, int $eventId): bool
    {
        return TicketCounter::where('ticket_type_id', $ticketTypeId)
            ->where('event_id', $eventId)
            ->where('booking_status', TicketCounter::STATUS_PENDING_PAYMENT)
            ->exists();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'event_id' => 'required|exists:events,id',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt_text' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'ticket_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'tax_label' => 'nullable|string|max:100',
            'tax_value' => 'nullable|numeric|min:0|max:100',
            'extra_charges_label' => 'nullable|string|max:100',
            'extra_charges_value' => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function ageGroupTotal(Request $request): int
    {
        if (!$request->has('enable_age_group')) {
            return 0;
        }

        return collect($request->input('age_group_total_tickets', []))
            ->map(fn ($quantity) => max(0, (int) $quantity))
            ->sum();
    }

    private function syncAgeGroups(TicketType $ticket, Request $request): void
    {
        $ticket->ageGroups()->delete();

        if (!$request->has('enable_age_group')) {
            return;
        }

        $labels = $request->input('age_group_label', []);
        $prices = $request->input('age_group_price', []);
        $totals = $request->input('age_group_total_tickets', []);
        $maxQty = $request->input('age_group_max_quantity', []);
        $compulsory = array_map('strval', (array) $request->input('age_group_compulsory', []));

        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            $totalTickets = max(0, (int) ($totals[$index] ?? 0));

            if ($label === '' || $totalTickets <= 0) {
                continue;
            }

            TicketTypeAgeGroup::create([
                'ticket_type_id' => $ticket->id,
                'label' => $label,
                'price' => max(0, (float) ($prices[$index] ?? $ticket->ticket_price)),
                'total_tickets' => $totalTickets,
                'max_quantity_per_booking' => max(1, (int) ($maxQty[$index] ?? 20)),
                'is_compulsory' => in_array((string) $index, $compulsory, true),
                'order_index' => $index,
            ]);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        $event = Event::where('id', $eventId)->first();

        $tickets = $this->withTicketsSoldSum(TicketType::where('event_id', $eventId))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.ticket_types._partials.table', compact('tickets', 'event'))->render();
        }
        return view('admin.ticket_types.index', compact('tickets','event'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        return view('admin.ticket_types.create', compact('event'));
    }

    
    /**
     * Show the form for creating a seat resource.
     */
    public function createSeats()
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        // Get all seat IDs already assigned to any ticket type for this event
        $assignedSeatIds = \DB::table('ticket_type_seats')
            ->where('event_id', $eventId)
            ->pluck('venue_layout_id')
            ->toArray();

        $layouts = \App\Models\VenueLayout::orderBy('order_index')->get()->groupBy('wing');

        return view('admin.ticket_types.create-ticket-type-seat', [
            'lwdata'  => $layouts->get('LW', []),
            'clwdata' => $layouts->get('CLW', []),
            'crwdata' => $layouts->get('CRW', []),
            'rwdata'  => $layouts->get('RW', []),
            'otherIds' => $assignedSeatIds, // Mark these as disabled in JS
            'currentIds' => [], // No seats are "pre-selected" during creation
            'event' => $event,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Keep validation as is to support simple ticket types
        $validated = $this->validateData($request);
       // dd($request->all());
        // Capture selected seats and calculate total count
        $selectedSeats = $request->input('selected_seats', []);
        $totalTicketsCount = count($selectedSeats);
        $ageGroupTotal = $this->ageGroupTotal($request);

        // Ensure event_id and new fields are provided
        $validated['event_id'] = $request->input('event_id');
        // If no seats are selected (simple ticket), we use the request's total_tickets or default
        $validated['total_tickets'] = $ageGroupTotal > 0
            ? $ageGroupTotal
            : ($totalTicketsCount > 0 ? $totalTicketsCount : ($request->input('total_tickets') ?? 0));
        $validated['ticket_price'] = $request->has('enable_age_group') ? 0 : $validated['ticket_price'];
        $validated['ticket_type_color'] = $request->input('ticket_type_color');

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('ticket_types', 'public');
            $validated['featured_image_alt_text'] = $request->input('featured_image_alt_text') ?? 'featured image';
        }

        $validated['enable_bulk_discount'] = $request->has('enable_bulk_discount');
        $validated['enable_tax'] = $request->has('enable_tax');
        $validated['enable_extra_charges'] = $request->has('enable_extra_charges');
        $validated['enable_age_group'] = $request->has('enable_age_group') && $ageGroupTotal > 0;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        /** ------------------------------------------------
         * 1) CREATE TICKET TYPE FIRST
         * ------------------------------------------------ */
        $ticket = TicketType::create($validated);
        $this->syncAgeGroups($ticket, $request);

        /** ------------------------------------------------
         * 2) RECORD SEATS SEPARATELY (NEW LOGIC)
         * ------------------------------------------------ */
        if (!empty($selectedSeats)) {
            $seatEntries = [];
            foreach ($selectedSeats as $seatId) {
                $seatEntries[] = [
                    'ticket_type_id'  => $ticket->id, 
                    'venue_layout_id' => $seatId,   
                    'event_id'        => $request->event_id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }

            // Use the DB facade for a guaranteed insert
            \DB::table('ticket_type_seats')->insert($seatEntries);
        }

        /** ------------------------------------------------
         * 3) SYNC BULK DISCOUNTS
         * ------------------------------------------------ */
        if ($request->has('bulk_discount_qty') && $request->has('bulk_discount_value')) {
            $qtyList = $request->bulk_discount_qty;
            $valueList = $request->bulk_discount_value;

            foreach ($qtyList as $key => $qty) {
                if (empty($qty) || empty($valueList[$key])) {
                    continue;
                }

                BulkDiscount::create([
                    'event_id'           => $request->event_id,
                    'ticket_type_id'     => $ticket->id,
                    'min_order_qty'      => $qty,
                    'discount_percentage'=> $valueList[$key],
                    'created_by'         => Auth::id(),
                    'updated_by'         => Auth::id(),
                ]);
            }
        }

        return redirect()->route('admin.ticket.types.index')->with('success', 'Tickets created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $eventId = session('active_event_id');

        $ticket = $this->withTicketsSoldSum(
            TicketType::where('id', $id)
                ->where('event_id', $eventId)
        )->first();

        if (!$ticket) {
            abort(404);
        }

        // Load bulk discounts
        $bulkDiscounts = BulkDiscount::with('ticketType')
            ->where('event_id', $eventId)
            ->where('ticket_type_id', $id)
            ->orderBy('min_order_qty', 'asc')
            ->get();

        // Load discount coupons attached to this ticket type
        $discountCoupons = DiscountCoupon::where('event_id', $eventId)
            ->whereJsonContains('ticket_type_ids', (string)$id)   // Works for ["1"], ["12","13"], etc.
            ->get();

        return view('admin.ticket_types.show', compact('ticket', 'bulkDiscounts', 'discountCoupons'));
    }


    /**
     * Show the form for editing normal type the specified resource.
     */
    public function edit($id)
    {
        $eventId = session('active_event_id');
        $ticket = TicketType::where('id', $id)->where('event_id', $eventId)->first();
        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
                        ->where('ticket_type_id', $id)
                        ->get();

        if(!$ticket){
            abort(404);
        }

        $event = Event::find($eventId);
        $ticket->load('ageGroups');

        return view('admin.ticket_types.edit', compact('ticket', 'bulkDiscounts', 'event'));
    }

    
    /**
     * Show the form for editing seats type the specified resource.
     */
    public function editSeats($id)
    {
        $eventId = session('active_event_id');
        $ticket = \App\Models\TicketType::where('id', $id)->where('event_id', $eventId)->firstOrFail();
        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
                        ->where('ticket_type_id', $id)
                        ->get();

        // 1. IDs assigned to OTHER ticket types for this event (Disable these)
        $otherIds = \DB::table('ticket_type_seats')
            ->where('event_id', $eventId)
            ->where('ticket_type_id', '!=', $id)
            ->pluck('venue_layout_id')
            ->toArray();

        // 2. IDs assigned specifically to THIS ticket type (Check these)
        $currentIds = \DB::table('ticket_type_seats')
            ->where('ticket_type_id', $id)
            ->pluck('venue_layout_id')
            ->toArray();

        $seatAssignments = \DB::table('ticket_type_seats')
            ->join('ticket_types', 'ticket_type_seats.ticket_type_id', '=', 'ticket_types.id')
            ->where('ticket_type_seats.event_id', $eventId)
            ->select(
                'ticket_type_seats.venue_layout_id',
                'ticket_type_seats.is_booked',
                'ticket_types.id as ticket_type_id',
                'ticket_types.ticket_type_color',
                'ticket_types.title'
            )
            ->get()
            ->keyBy('venue_layout_id');

        $layouts = \App\Models\VenueLayout::orderBy('order_index')->get()->groupBy('wing');

        return view('admin.ticket_types.edit-ticket-type-seat', [
            'ticket' => $ticket,
            'bulkDiscounts' => $bulkDiscounts,
            'event' => Event::find($eventId),
            'lwdata'  => $layouts->get('LW', []),
            'clwdata' => $layouts->get('CLW', []),
            'crwdata' => $layouts->get('CRW', []),
            'rwdata'  => $layouts->get('RW', []),
            'otherIds' => $otherIds,
            'currentIds' => $currentIds,
            'seatAssignments' => $seatAssignments
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $eventId = session('active_event_id');
        $ticket = TicketType::where('id', $id)->where('event_id', $eventId)->firstOrFail();

        $validated = $this->validateData($request);
        
        // Calculate new total tickets
        $isSeatSelectionUpdate = $request->has('seat_selection_form') || $request->has('selected_seats');
        $selectedSeats = array_values(array_unique(array_filter(array_map('intval', $request->input('selected_seats', [])))));
        $bookedSeatIds = [];
        $finalSeatIds = $selectedSeats;
        $ageGroupTotal = $this->ageGroupTotal($request);

        if ($isSeatSelectionUpdate) {
            $bookedSeatIds = \DB::table('ticket_type_seats')
                ->where('event_id', $eventId)
                ->where('ticket_type_id', $id)
                ->where('is_booked', true)
                ->pluck('venue_layout_id')
                ->map(fn ($seatId) => (int) $seatId)
                ->toArray();

            $finalSeatIds = array_values(array_unique(array_merge($selectedSeats, $bookedSeatIds)));
        }

        $validated['total_tickets'] = $ageGroupTotal > 0
            ? $ageGroupTotal
            : ($isSeatSelectionUpdate ? count($finalSeatIds) : ($request->input('total_tickets') ?? $ticket->total_tickets));
        $validated['ticket_price'] = $request->has('enable_age_group') ? 0 : $validated['ticket_price'];
        $validated['ticket_type_color'] = $request->input('ticket_type_color');
        $validated['updated_by'] = Auth::id();

        // Image handling
        if ($request->hasFile('featured_image')) {
            $this->deleteTicketTypePublicFileIfUnused($ticket->featured_image, (int) $ticket->id);
            $validated['featured_image'] = $request->file('featured_image')->store('ticket_types', 'public');
        }

        $validated['enable_bulk_discount'] = $request->has('enable_bulk_discount');
        $validated['enable_tax'] = $request->has('enable_tax');
        $validated['enable_extra_charges'] = $request->has('enable_extra_charges');
        $validated['enable_age_group'] = $request->has('enable_age_group') && $ageGroupTotal > 0;

        $ticket->update($validated);
        $this->syncAgeGroups($ticket, $request);

        // --- SEAT SYNC LOGIC ---
        if ($isSeatSelectionUpdate) {
            // 1. Remove only unbooked seats that are no longer selected
            $deleteQuery = \DB::table('ticket_type_seats')
                ->where('event_id', $eventId)
                ->where('ticket_type_id', $id)
                ->where('is_booked', false);

            if (!empty($finalSeatIds)) {
                $deleteQuery->whereNotIn('venue_layout_id', $finalSeatIds);
            }

            $deleteQuery->delete();

            $existingSeatIds = \DB::table('ticket_type_seats')
                ->where('event_id', $eventId)
                ->where('ticket_type_id', $id)
                ->pluck('venue_layout_id')
                ->map(fn ($seatId) => (int) $seatId)
                ->toArray();

            // 2. Insert only newly selected seats
            $seatEntries = [];
            foreach ($finalSeatIds as $seatId) {
                if (in_array($seatId, $existingSeatIds)) {
                    continue;
                }

                $seatEntries[] = [
                    'ticket_type_id'  => $id,
                    'venue_layout_id' => $seatId,
                    'event_id'        => $eventId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }

            if (!empty($seatEntries)) {
                \DB::table('ticket_type_seats')->insert($seatEntries);
            }
        }

        return redirect()->route('admin.ticket.types.index')->with('success', 'Tickets updated successfully!');
    }
    /**
     * Remove the specified resource from storage from the current event context.
     */
    public function destroy(string $id)
    {
        $eventId = session('active_event_id');
        
        // Find the ticket strictly within the active event
        $ticket = $this->withTicketsSoldSum(
            TicketType::where('id', $id)
                ->where('event_id', $eventId)
        )->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket type not found or access denied.'
            ], 404);
        }

        // Check if any tickets have been sold
        if (($ticket->tickets_sold ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ticket type. Tickets have already been sold for this type.'
            ], 422);
        }

        if ($this->hasPendingPaymentLock((int) $ticket->id, (int) $eventId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ticket type. A checkout is currently pending for this type.'
            ], 422);
        }

        // 1. Manually remove associated seat records if cascade isn't used
        // This frees up the seats in the venue layout for other ticket types
        \DB::table('ticket_type_seats')->where('ticket_type_id', $id)->delete();

        // 2. Cleanup physical storage (Featured Image)
        $this->deleteTicketTypePublicFileIfUnused($ticket->featured_image, (int) $ticket->id);

        // 3. Delete the Ticket Type (BulkDiscounts will cascade if set in migration)
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tickets deleted successfully!'
        ]);
    }

    private function deleteTicketTypePublicFileIfUnused(?string $path, int $ticketTypeIdToIgnore): void
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return;
        }

        $eventUsesPath = Event::withTrashed()
            ->where(function ($query) use ($path) {
                $query->where('event_pdf_sponser_image', $path)
                    ->orWhere('featured_video', $path)
                    ->orWhere('thumbnail', $path)
                    ->orWhere('featured_image', $path)
                    ->orWhere('venue_layout_image', $path);
            })
            ->exists();

        if ($eventUsesPath) {
            return;
        }

        $ticketTypeUsesPath = TicketType::withTrashed()
            ->where('featured_image', $path)
            ->where('id', '!=', $ticketTypeIdToIgnore)
            ->exists();

        if ($ticketTypeUsesPath) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
