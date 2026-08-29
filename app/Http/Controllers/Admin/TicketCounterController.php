<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCounter;
use App\Models\TicketType;
use App\Models\BulkDiscount;
use App\Models\DiscountCoupon;
use App\Models\Country;
use App\Models\Booking;
use App\Models\Event;
use App\Models\EventService;
use App\Models\BookedTicket;
use App\Models\State;
use App\Models\EventContestent;
use App\Models\EventContestentVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\TicketPdfService;

class TicketCounterController extends Controller
{
    /**
     * Display a listing of the resource.
    */
    public function index(Request $request)
    {
        $eventId = session('active_event_id');
        
        if (!$eventId) {
            return redirect()->route('admin.dashboard.index')->with('error', 'Please select an event first.');
        }

        $ticket_type_id = $request->query('ticket_type_id');

        // 1. Fetch Event and general Ticket Types
        // If ticket_type_id is null, the closure where condition is ignored or returns empty
        $event = Event::with(['ticketTypes' => function($query) use ($ticket_type_id) {
                if ($ticket_type_id) {
                    $query->where('id', $ticket_type_id);
                }
        }])->findOrFail($eventId);

        $ticketTypes = TicketType::sortByStartingPrice(
            TicketType::with('ageGroups')->where('event_id', $eventId)->get()
        );
        $countries = Country::orderBy('name')->get(['id', 'name']);
        $contestents = $event->enable_voting
            ? EventContestent::where('event_id', $eventId)->orderBy('name')->get()
            : collect();
        $eventServices = EventService::where('event_id', $eventId)
            ->where('status', true)
            ->orderBy('id')
            ->get();
        
        // Initialize defaults to prevent errors in Simple Booking mode
        $selectedTicketType = null;
        $slabs = collect();
        $heldSeatIds = [];
        $layouts = collect();
        $seatAssignments = collect();

        // 2. Only process Ticket-Type specific data if an ID is present
        if ($ticket_type_id) {
            $selectedTicketType = $event->ticketTypes->first();
                        
            // Use find() instead of findOrFail() to prevent 404 on invalid IDs
            $ticketType = $event->ticketTypes()
                ->with(['bulkDiscounts' => function ($q) {
                    $q->orderBy('min_order_qty', 'asc');
                }])
                ->find($ticket_type_id);

            if ($ticketType) {
                // Bulk discount slabs
                if ($ticketType->enable_bulk_discount) {
                    $slabs = $ticketType->bulkDiscounts->map(function ($bd) {
                        return [
                            'minTickets' => (int) $bd->min_order_qty,
                            'offer'      => (float) $bd->discount_percentage,
                        ];
                    })->values();
                }

                // Fetch Holds specifically for this type
                $activeHolds = \DB::table('ticket_holds')
                    ->where('event_id', $eventId)
                    ->where('ticket_type_id', $ticket_type_id)
                    ->where('expires_at', '>', now())
                    ->get(['selected_seats']);

                foreach ($activeHolds as $hold) {
                    $seats = $hold->selected_seats;
                    if (is_string($seats)) { $seats = json_decode($seats, true); }
                    if (is_string($seats)) { $seats = json_decode($seats, true); }

                    if (is_array($seats)) {
                        $heldSeatIds = array_merge($heldSeatIds, $seats);
                    }
                }
                $heldSeatIds = array_values(array_unique($heldSeatIds));
            }
        }

        // 3. Seat Logic - Only load if it's a seated event
        if ($event->type == 2) {
            $layouts = \App\Models\VenueLayout::orderBy('order_index')->get()->groupBy('wing');

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
        }

        $viewPath = ($event->type == 2) 
                    ? 'admin.event-ticket.ticket-counter-seat' 
                    : 'admin.event-ticket.ticket-counter';


        
        // Count how many parking tickets have already been issued for this event
        $parkingBookedSlots = \App\Models\TicketParking::whereHas('booking', function($query) use ($event) {
            $query->where('event_id', $event->id);
        })->count();

        // Calculate remaining slots
        $remainingSlots = max(0, $event->car_parking_slots - $parkingBookedSlots);

        return view($viewPath, [
            'event'              => $event,
            'ticketTypes'        => $ticketTypes,
            'countries'          => $countries,
            'targetTicketTypeId' => $ticket_type_id,
            'lwdata'             => $layouts->get('LW', []),
            'clwdata'            => $layouts->get('CLW', []),
            'crwdata'            => $layouts->get('CRW', []),
            'rwdata'             => $layouts->get('RW', []),
            'seatAssignments'    => $seatAssignments,
            'heldSeatIds'        => $heldSeatIds,
            'remainingSlots'        => $remainingSlots,
            'slabs'              => $slabs,
            'contestents'        => $contestents,
            'eventServices'      => $eventServices,
        ]);
    }
    /**
     * Get ticket types for the current event
     */
    public function getTicketTypes()
    {
        $eventId = session('active_event_id');
        $ticketTypes = TicketType::where('event_id', $eventId)
            ->where('enable_bulk_discount', 1)
            ->select('id', 'title', 'ticket_price', 'total_tickets')
            ->get()
            ->map(function ($ticketType) {
                $soldCount = TicketCounter::withTrashed()
                    ->where('ticket_type_id', $ticketType->id)
                    ->whereIn('booking_status', [TicketCounter::STATUS_CONFIRMED, TicketCounter::STATUS_PENDING_VERIFICATION])
                    ->sum('qty');
                $ticketType->available_tickets = $ticketType->total_tickets - $soldCount;
                return $ticketType;
            });

        return response()->json($ticketTypes);
    }

    /**
     * Get available quantity for a specific ticket type
     */
    public function getAvailableQuantity($ticketTypeId)
    {
        $ticketType = TicketType::findOrFail($ticketTypeId);
        $soldCount = TicketCounter::withTrashed()
            ->where('ticket_type_id', $ticketTypeId)
            ->whereIn('booking_status', [TicketCounter::STATUS_CONFIRMED, TicketCounter::STATUS_PENDING_VERIFICATION])
            ->sum('qty');
        $availableTickets = $ticketType->total_tickets - $soldCount;

        return response()->json([
            'available_tickets' => $availableTickets,
            'ticket_price' => $ticketType->ticket_price,
            'title' => $ticketType->title
        ]);
    }

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    /**
     * Check bulk discount eligibility
     */
    public function checkBulkDiscount(Request $request)
    {
        $eventId = session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        $quantity = $request->quantity;

        //Get ticket type details
        $ticketType = TicketType::findOrFail($ticket_type_id);
        $enable_bulk_discount = $ticketType->enable_bulk_discount;

        // Get all bulk discounts for this event, ordered by min_order_qty ascending
        
        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
            ->where('ticket_type_id',$ticket_type_id)
            ->orderBy('min_order_qty', 'asc')
            ->get();

        if (!$enable_bulk_discount || $bulkDiscounts->isEmpty()) {
            return response()->json(['has_bulk_discount' => false]);
        }

        $response = ['has_bulk_discount' => true,'enable_bulk_discount'=>$enable_bulk_discount];

        // Find the highest discount tier the user qualifies for
        $currentTier = null;
        $nextTier = null;

        foreach ($bulkDiscounts as $discount) {
            if ($quantity >= $discount->min_order_qty) {
                $currentTier = $discount;
            } else {
                if (!$nextTier) {
                    $nextTier = $discount;
                }
                break;
            }
        }

        // If user qualifies for the highest tier
        if ($currentTier && !$nextTier) {
            $response['eligible'] = true;
            $response['message'] = 'Perfect Choice :)';
            $response['discount_percentage'] = $currentTier->discount_percentage;
            $response['applied_tier'] = $currentTier;
        }
        // If user can reach a higher tier
        else if ($nextTier) {
            $response['eligible'] = false;
            $remaining = $nextTier->min_order_qty - $quantity;
            $response['message'] = "{$remaining} Tickets away from {$nextTier->discount_percentage}% Bulk Ticket Discount";
            $response['remaining_tickets'] = $remaining;
            $response['discount_percentage'] = $nextTier->discount_percentage;
            $response['next_tier'] = $nextTier;
            $response['current_tier'] = $currentTier;
        }
        // If user doesn't qualify for any tier yet
        else {
            $firstTier = $bulkDiscounts->first();
            $response['eligible'] = false;
            $remaining = $firstTier->min_order_qty - $quantity;
            $response['message'] = "{$remaining} Tickets away from {$firstTier->discount_percentage}% Bulk Ticket Discount";
            $response['remaining_tickets'] = $remaining;
            $response['discount_percentage'] = $firstTier->discount_percentage;
            $response['next_tier'] = $firstTier;
            $response['current_tier'] = null;
        }

        return response()->json($response);
    }

    /**
     * Validate and apply coupon
     */
    public function applyCoupon(Request $request)
    {
        $eventId = session('active_event_id');
        $couponCode = $request->coupon_code;
        $ticketTypeId = $request->ticket_type_id;

        $coupon = DiscountCoupon::where('event_id', $eventId)
            ->where('coupon_code', $couponCode)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code'
            ]);
        }

        // Check if coupon is valid for the selected ticket type
        $allowedTicketTypes = $coupon->ticket_type_ids;

        if (!empty($allowedTicketTypes) && !in_array($ticketTypeId, $allowedTicketTypes) && !in_array((string)$ticketTypeId, $allowedTicketTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not valid for selected ticket type'
            ]);
        }

        return response()->json([
            'success' => true,
            'discount_percentage' => $coupon->discount,
            'coupon_code' => $coupon->coupon_code,
            'message' => 'Coupon applied successfully'
        ]);
    }

    /**
     * Calculate bill details
     */
    public function calculateBill(Request $request)
    {
        $eventId = session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        $quantity = $request->quantity;
        $couponCode = $request->coupon_code;

        $ticketType = TicketType::findOrFail($ticket_type_id);
        $subtotal = $ticketType->ticket_price * $quantity;

        $response = [
            'ticket_title' => $ticketType->title,
            'ticket_price' => $ticketType->ticket_price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'bulk_discount_applied' => false,
            'coupon_applied' => false,
            'coupon_valid' => false,
            'total_amount' => $subtotal
        ];

        // Check bulk discount - find the highest applicable tier
        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
            ->where('ticket_type_id',$ticket_type_id)
            ->where('min_order_qty', '<=', $quantity)
            ->orderBy('min_order_qty', 'desc')
            ->first();

        $bulkDiscountAmount = 0;

        if ($bulkDiscounts && $ticketType->enable_bulk_discount) {
            $bulkDiscountAmount = ($subtotal * $bulkDiscounts->discount_percentage) / 100;
            $response['bulk_discount_applied'] = true;
            $response['bulk_discount_percentage'] = $bulkDiscounts->discount_percentage;
            $response['bulk_discount_amount'] = $bulkDiscountAmount;
        }

        // Check coupon
        $couponDiscountAmount = 0;
        if ($couponCode) {
            $coupon = DiscountCoupon::where('event_id', $eventId)
                ->where('coupon_code', $couponCode)
                ->first();

            if ($coupon) {
                // Check if coupon is valid for this ticket type
                // ticket_type_ids is cast as array in the model
                $allowedTicketTypes = $coupon->ticket_type_ids;
                $isValidForTicketType = empty($allowedTicketTypes) || in_array($ticket_type_id, $allowedTicketTypes) || in_array((string)$ticket_type_id, $allowedTicketTypes);

                if ($isValidForTicketType) {
                    $couponDiscountAmount = ($subtotal * $coupon->discount) / 100;
                    $response['coupon_valid'] = true;
                    $response['coupon_applied'] = true;
                    $response['coupon_code'] = $coupon->coupon_code;
                    $response['coupon_percentage'] = $coupon->discount;
                    $response['coupon_amount'] = $couponDiscountAmount;
                }
            }
        }

        // Apply discount priority: Bulk Ticket Discount takes priority over Coupon
        if ($response['bulk_discount_applied'] && $response['coupon_valid']) {
            // Bulk discount has priority - remove coupon and apply only bulk discount
            $response['total_amount'] = $subtotal - $bulkDiscountAmount;
            $response['coupon_applied'] = false;
            $response['coupon_amount'] = 0;
        } elseif ($response['bulk_discount_applied']) {
            $response['total_amount'] = $subtotal - $bulkDiscountAmount;
        } elseif ($response['coupon_applied']) {
            $response['total_amount'] = $subtotal - $couponDiscountAmount;
        }

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $eventId = session('active_event_id');

        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_prefix' => 'required|string|max:20',
            'mobile_number' => 'required|digits_between:1,12',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'coupon_code' => 'nullable|string',
            'coupon_valid' => 'nullable|string',
            'coupon_amount' => 'nullable|numeric',
            'coupon_percentage' => 'nullable|numeric',
            'contestent_id' => [
                'nullable',
                'integer',
                Rule::exists('event_contestents', 'id')->where(function ($query) use ($eventId) {
                    $query->where('event_id', $eventId)->whereNull('deleted_at');
                }),
            ],
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits_between' => 'Mobile number must not exceed 12 digits.',
            'contestent_id.exists' => 'Selected contestent is invalid.',
        ]);

        $stateBelongsToCountry = State::where('id', $request->state_id)
            ->where('country_id', $request->country_id)
            ->exists();

        if (!$stateBelongsToCountry) {
            return response()->json([
                'success' => false,
                'message' => 'Selected state does not belong to the selected country.'
            ], 422);
        }

        $event = Event::findOrFail($eventId);

        if ($request->filled('contestent_id') && !$event->enable_voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting is not enabled for this event.'
            ], 422);
        }

        $ticketType = TicketType::findOrFail($request->ticket_type_id);

        // Check availability
        $soldCount = TicketCounter::withTrashed()
            ->where('ticket_type_id', $request->ticket_type_id)
            ->whereIn('booking_status', [TicketCounter::STATUS_CONFIRMED, TicketCounter::STATUS_PENDING_VERIFICATION])
            ->sum('qty');
        $availableTickets = $ticketType->total_tickets - $soldCount;

        if ($availableTickets < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available'
            ]);
        }


        DB::beginTransaction();
        try {
            // Calculate bill details
            $billResponse = $this->calculateBill($request);
            $billData = $billResponse->getData(true);
            $couponApplied = ($billData['coupon_applied'] ?? false) && !($billData['bulk_discount_applied'] ?? false);
            $hasAppliedDiscount = $couponApplied || ($billData['bulk_discount_applied'] ?? false);
            $discountAmount = $billData['bulk_discount_applied'] ?? false
                ? (float) ($billData['bulk_discount_amount'] ?? 0)
                : (float) ($billData['coupon_amount'] ?? 0);
            $discountPercentage = $billData['bulk_discount_applied'] ?? false
                ? (float) ($billData['bulk_discount_percentage'] ?? 0)
                : (float) ($billData['coupon_percentage'] ?? 0);

            // Create ticket counter record
            $ticketCounter = TicketCounter::create([
                'event_id' => $eventId,
                'ticket_type_id' => $request->ticket_type_id,
                'qty' => $request->quantity,
                'bulk_discount_applied' => $billData['bulk_discount_applied'],
                'coupon_applied' => $couponApplied ? $billData['coupon_code'] : null,
                'coupon_code' => $couponApplied ? $billData['coupon_code'] : null,
                'coupon_amount' => $hasAppliedDiscount ? $discountAmount : 0,
                'coupon_percentage' => $hasAppliedDiscount ? $discountPercentage : 0,
                'total_amount' => $billData['total_amount'],
                'name' => $request->name,
                'email' => $request->email,
                'phone_prefix' => $request->phone_prefix,
                'mobile_number' => $request->mobile_number,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'payment_status' => 'paid',
                'booking_status' => TicketCounter::STATUS_CONFIRMED,
                'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                'payment_method' => 'manual',
                'created_by' => Auth::id()
            ]);

            // Generate individual booked tickets
            $ticketCounter->refresh();
            $bId = $ticketCounter->booking_id;

            if ($request->filled('contestent_id')) {
                $vote = EventContestentVote::firstOrCreate(
                    [
                        'event_id' => $eventId,
                        'booking_id' => $bId,
                    ],
                    [
                        'event_contestent_id' => $request->contestent_id,
                        'ticket_counter_id' => $ticketCounter->id,
                        'name' => $request->name,
                        'email' => $request->email,
                    ]
                );

                if ($vote->wasRecentlyCreated) {
                    EventContestent::where('event_id', $eventId)
                        ->where('id', $request->contestent_id)
                        ->increment('votes');
                }
            }
            
            for ($i = 0; $i < $request->quantity; $i++) {
                \App\Models\BookedTicket::create([
                    'ticket_counter_id' => $ticketCounter->id,
                    'booking_id' => $bId,
                    'ticket_number' => $bId . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4)),
                    'status' => 'Not Scanned'
                ]);
            }

            // Generate PDF and send email
            try {
                $ticketCounter->load(['parkings', 'ticketType', 'event']);
                app(TicketPdfService::class)->sendTicketEmail($ticketCounter);
            } catch (\Exception $e) {
                \Log::error('PDF/Email generation failed: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket purchased successfully!',
                'ticket_id' => $ticketCounter->id
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error processing ticket purchase: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->index();
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
