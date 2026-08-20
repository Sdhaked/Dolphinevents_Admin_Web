<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Currency;
use App\Models\TicketCounter;
use App\Models\TicketType;
use App\Models\BulkDiscount;
use App\Models\DiscountCoupon;
use App\Models\State;
use App\Models\EventContestent;
use App\Models\EventContestentVote;
use App\Models\EventService;
use App\Models\TicketTypeAgeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\EventTicketMail;

class TicketCounterApiController extends Controller
{
    /**
     * Get ticket types for the current event
     */
    public function getTicketTypes(Request $request)
    {
        $eventId = $request->event_id ?? session('active_event_id');

        $ticketTypes = TicketType::where('event_id', $eventId)
            ->where('enable_bulk_discount', 1)
            ->select('id', 'title', 'ticket_price', 'total_tickets')
            ->get()
            ->map(function ($ticketType) {
                $soldCount = TicketCounter::where('ticket_type_id', $ticketType->id)
                    ->whereIn('booking_status', [
                        TicketCounter::STATUS_CONFIRMED,
                        TicketCounter::STATUS_PENDING_VERIFICATION,
                        TicketCounter::STATUS_PENDING_PAYMENT,
                    ])
                    ->sum('qty');
                $ticketType->available_tickets = $ticketType->total_tickets - $soldCount;
                return $ticketType;
            });

        return response()->json($ticketTypes);
    }

    /**
     * Get available quantity for a specific ticket type with a max cap of 20
     */
    public function getAvailableQuantity($ticketTypeId)
    {
        $ticketType = TicketType::findOrFail($ticketTypeId);
        
        // Calculate actual stock based on database records
        $soldCount = TicketCounter::where('ticket_type_id', $ticketTypeId)
            ->whereIn('booking_status', [
                TicketCounter::STATUS_CONFIRMED,
                TicketCounter::STATUS_PENDING_VERIFICATION,
                TicketCounter::STATUS_PENDING_PAYMENT,
            ])
            ->sum('qty');
        $actualRemaining = $ticketType->total_tickets - $soldCount;

        // Apply the cap: use min() to ensure it never exceeds 20
        $availableToDisplay = min($actualRemaining, 20);

        return response()->json([
            'available_tickets' => max(0, $availableToDisplay), // Ensure we don't return negative numbers
            'ticket_price' => $ticketType->ticket_price,
            'title' => $ticketType->title
        ]);
    }

    /**
     * Unified logic to get quantity from either manual input or seat selection
     */
    private function getResolvedQuantity(Request $request)
    {
        $ageGroupItems = collect(is_array($request->age_group_items ?? null) ? $request->age_group_items : [])
            ->filter(fn ($item) => max(0, (int) ($item['quantity'] ?? 0)) > 0);

        if ($ageGroupItems->isNotEmpty()) {
            return $ageGroupItems->sum(fn ($item) => max(0, (int) ($item['quantity'] ?? 0)));
        }

        $selectedSeats = collect(is_array($request->selected_seats ?? null) ? $request->selected_seats : [])
            ->filter(fn ($seatId) => filled($seatId))
            ->unique()
            ->values();

        if ($selectedSeats->isNotEmpty()) {
            return $selectedSeats->count();
        }

        return (int) ($request->quantity ?? 1);
    }

    /**
     * Check bulk discount eligibility
     */
    public function checkBulkDiscount(Request $request)
    {
        $eventId = $request->event_id ?? session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        
        // Use the helper to resolve quantity
        $quantity = $this->getResolvedQuantity($request);

        //Get ticket type details
        $ticketType = TicketType::findOrFail($ticket_type_id);
        $enable_bulk_discount = $ticketType->enable_bulk_discount;

        // Get all bulk discounts for this event, ordered by min_order_qty ascending
        
        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
            ->where('ticket_type_id',$ticket_type_id)
            ->orderBy('min_order_qty', 'asc')
            ->get();

        if (!$enable_bulk_discount) {
            return response()->json(['has_bulk_discount' => false,'disable_coupon' => false]);
        }

        $response = ['has_bulk_discount' => true,'disable_coupon'=>false,'enable_bulk_discount'=>$enable_bulk_discount];

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
            $response['message'] = '<b>Perfect Choice :)</b>';
            $response['discount_percentage'] = $currentTier->discount_percentage;
            $response['applied_tier'] = $currentTier;
            $response['disable_coupon'] = true;
        }
        // If user can reach a higher tier
        else if ($currentTier && $nextTier) {
            $response['eligible'] = false;
            $remaining = $nextTier->min_order_qty - $quantity;
            $response['message'] = "<b>{$remaining} Tickets</b> away from <b>{$nextTier->discount_percentage}% Bulk Ticket Discount</b>";
            $response['remaining_tickets'] = $remaining;
            $response['discount_percentage'] = $nextTier->discount_percentage;
            $response['next_tier'] = $nextTier;
            $response['disable_coupon'] = true;
            $response['current_tier'] = $currentTier;
        }
        // If user doesn't qualify for any tier yet
        else {
            $firstTier = $bulkDiscounts->first();
            if ($firstTier) {
                $response['eligible'] = false;
                $remaining = $firstTier->min_order_qty - $quantity;
                $response['message'] = "<b>{$remaining} Tickets</b> away from <b>{$firstTier->discount_percentage}% Bulk Ticket Discount</b>";
                $response['remaining_tickets'] = $remaining;
                $response['discount_percentage'] = $firstTier->discount_percentage;
                $response['next_tier'] = $firstTier;
                $response['disable_coupon'] = false;
                $response['current_tier'] = null;
            }
        }

        return response()->json($response);
    }

    /**
     * Validate and apply coupon
     */
    public function applyCoupon(Request $request)
    {
        $eventId = $request->event_id ?? session('active_event_id');
        $couponCode = $request->coupon_code;
        $ticketTypeId = $request->ticket_type_id;
        $quantity = $request->quantity ?? 1;

        // Fetch ticket type
        $ticketType = TicketType::findOrFail($ticketTypeId);

        // ðŸ”´ CHECK BULK DISCOUNT FIRST
        if ($ticketType->enable_bulk_discount) {

            $bulkTier = BulkDiscount::where('event_id', $eventId)
                ->where('ticket_type_id', $ticketTypeId)
                ->where('min_order_qty', '<=', $quantity)
                ->orderBy('min_order_qty', 'desc')
                ->first();

            if ($bulkTier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulk discount active, cannot apply coupon code'
                ]);
            }
        }

        // âœ… Validate coupon
        $coupon = DiscountCoupon::where('event_id', $eventId)
            ->where('coupon_code', $couponCode)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code'
            ]);
        }

        // Check ticket-type eligibility
        $allowedTicketTypes = $coupon->ticket_type_ids;

        if (!empty($allowedTicketTypes)
            && !in_array($ticketTypeId, $allowedTicketTypes)
            && !in_array((string)$ticketTypeId, $allowedTicketTypes)
        ) {
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
        $eventId = $request->event_id ?? session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        
        // Use the helper to resolve quantity
        $quantity = $this->getResolvedQuantity($request);

        $couponCode = $request->coupon_code;
        $parkingSlots = (int) ($request->parking_slots ?? 0);

        $event = Event::findOrFail($eventId);
        $parkingPricePerSlot = $event->car_slot_price ?? 0;
        $ticketType = TicketType::with('ageGroups')->findOrFail($ticket_type_id);
        
        $ageGroupItems = collect();
        if ($ticketType->enable_age_group && is_array($request->age_group_items ?? null)) {
            $ageGroupIds = collect($request->age_group_items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $ageGroups = TicketTypeAgeGroup::where('ticket_type_id', $ticketType->id)
                ->whereIn('id', $ageGroupIds)
                ->get()
                ->keyBy('id');

            $ageGroupItems = collect($request->age_group_items)
                ->map(function ($item) use ($ageGroups) {
                    $ageGroup = $ageGroups->get((int) ($item['id'] ?? 0));
                    $quantity = max(0, (int) ($item['quantity'] ?? 0));

                    if (!$ageGroup || $quantity <= 0) {
                        return null;
                    }

                    return [
                        'id' => $ageGroup->id,
                        'label' => $ageGroup->label,
                        'quantity' => min($quantity, (int) $ageGroup->max_quantity_per_booking),
                        'price' => (float) $ageGroup->price,
                    ];
                })
                ->filter()
                ->values();
        }

        if ($ticketType->enable_age_group && $ageGroupItems->isNotEmpty()) {
            $quantity = $ageGroupItems->sum('quantity');
            $subtotal = $ageGroupItems->sum(fn ($item) => $item['price'] * $item['quantity']);
        } else {
            $subtotal = $ticketType->ticket_price * $quantity;
        }

        $currency = Currency::symbolForEvent($event);

        // --- Discount Calculations ---
        $discountAmount = 0;
        $appliedDiscountPercentage = 0;
        $bulkDiscountApplied = false;
        $couponApplied = false;

        if ($ticketType->enable_bulk_discount) {
            $bulkTier = BulkDiscount::where('event_id', $eventId)
                ->where('ticket_type_id', $ticket_type_id)
                ->where('min_order_qty', '<=', $quantity)
                ->orderBy('min_order_qty', 'desc')->first();

            if ($bulkTier) {
                $bulkDiscountApplied = true;
                $appliedDiscountPercentage = $bulkTier->discount_percentage;
                $discountAmount = ($subtotal * $appliedDiscountPercentage) / 100;
            }
        }

        if (!$bulkDiscountApplied && $couponCode) {
            $coupon = DiscountCoupon::where('event_id', $eventId)->where('coupon_code', $couponCode)->first();
            if ($coupon && (empty($coupon->ticket_type_ids) || in_array($ticket_type_id, $coupon->ticket_type_ids))) {
                $couponApplied = true;
                $appliedDiscountPercentage = $coupon->discount;
                $discountAmount = ($subtotal * $appliedDiscountPercentage) / 100;
            }
        }

        // --- Services ---
        $serviceItems = collect();
        if (is_array($request->service_items ?? null)) {
            $serviceIds = collect($request->service_items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $services = EventService::where('event_id', $eventId)
                ->where('status', true)
                ->whereIn('id', $serviceIds)
                ->get()
                ->keyBy('id');

            $serviceItems = collect($request->service_items)
                ->map(function ($item) use ($services, $ticket_type_id) {
                    $service = $services->get((int) ($item['id'] ?? 0));
                    $quantity = max(0, (int) ($item['quantity'] ?? 0));

                    if (!$service || $quantity <= 0 || !$service->isApplicableToTicketType($ticket_type_id)) {
                        return null;
                    }

                    $quantity = min($quantity, (int) $service->max_buy_limit);

                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'quantity' => $quantity,
                        'price' => (float) $service->price,
                        'total' => $quantity * (float) $service->price,
                    ];
                })
                ->filter()
                ->values();
        }

        $serviceTotal = $serviceItems->sum('total');

        // --- Parking ---
        $parkingTotal = 0;
        if($event->enable_car_parking && $parkingSlots > 0) {
            $parkingTotal = $parkingSlots * $parkingPricePerSlot;
        }

        // --- Tax & Extra Charges ---
        $ticketTotalAfterDiscount = max(0, $subtotal - $discountAmount);
        $taxableBasis = $ticketTotalAfterDiscount + $serviceTotal + $parkingTotal;
        
        $taxAmount = 0;
        if ($ticketType->enable_tax) {
            $taxAmount = ($taxableBasis * $ticketType->tax_value) / 100; //
        }

        $extraChargeAmount = 0;
        if ($ticketType->enable_extra_charges) {
            $extraChargeAmount = (($taxableBasis + $taxAmount) * $ticketType->extra_charges_value) / 100; //
        }

        $final_amount = $taxableBasis + $taxAmount + $extraChargeAmount;

        return response()->json([
            'ticket_title' => $ticketType->title,
            'ticket_price' => $currency . number_format($ticketType->ticket_price, 2),
            'quantity' => $quantity,
            'subtotal' => $currency . number_format($subtotal, 2),
            'age_group_items' => $ageGroupItems->map(fn ($item) => [
                'label' => $item['label'],
                'quantity' => $item['quantity'],
                'price' => $currency . number_format($item['price'], 2),
                'total' => $currency . number_format($item['price'] * $item['quantity'], 2),
            ])->all(),
            'service_items' => $serviceItems->map(fn ($item) => [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $currency . number_format($item['price'], 2),
                'total' => $currency . number_format($item['total'], 2),
            ])->all(),
            'service_total' => $currency . number_format($serviceTotal, 2),
            'parking_price' => $currency . number_format($parkingPricePerSlot, 2),
            'parking_slots' => $parkingSlots,
            'parking_total' => $currency . number_format($parkingTotal, 2),
            
            'bulk_discount_applied' => $bulkDiscountApplied,
            'bulk_discount_percentage' => $appliedDiscountPercentage,
            'bulk_discount_amount' => $currency . number_format($discountAmount, 2),
            
            'coupon_applied' => $couponApplied,
            'coupon_code' => $couponCode,
            'coupon_percentage' => $appliedDiscountPercentage,
            'coupon_amount' => $currency . number_format($discountAmount, 2),

            // Tax & Extra Charges
            'enable_tax' => (bool) $ticketType->enable_tax, //
            'tax_label' => $ticketType->tax_label, //
            'tax_value' => $ticketType->tax_value, //
            'tax_amount' => $currency . number_format($taxAmount, 2),

            'enable_extra_charges' => (bool) $ticketType->enable_extra_charges, //
            'extra_charges_label' => $ticketType->extra_charges_label, //
            'extra_charges_value' => $ticketType->extra_charges_value, //
            'extra_charges_amount' => $currency . number_format($extraChargeAmount, 2),

            'total_amount' => $currency . number_format($final_amount, 2),
            'raw_total' => $final_amount
        ]);
    }

    public function calculateBillOld(Request $request)
    {
        //Payload request
        $eventId = $request->event_id ?? session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        $quantity = $request->quantity ?? 1;
        $couponCode = $request->coupon_code;
        $parkingSlots = (int) ($request->parking_slots ?? 0);

        //Get details
        $event = Event::findOrFail($eventId);
        $parkingPricePerSlot = $event->car_slot_price ?? 0;
       
        $ticketType = TicketType::findOrFail($ticket_type_id);
        
        //Calculate the total
        $subtotal = $ticketType->ticket_price * $quantity;
        $final_amount = $subtotal;


        $parkingTotal = 0;
        $alreadyBooked = \App\Models\TicketParking::whereHas('booking', function($q) use ($event) {
            $q->where('event_id', $event->id);
        })->count();

        $availableSlots = (int) $event->car_parking_slots - $alreadyBooked;
        if($event->enable_car_parking && $parkingSlots > 0){
            if ($parkingSlots <= $availableSlots) {
                $parkingTotal = $parkingSlots * $parkingPricePerSlot;
                $final_amount = $parkingTotal+$subtotal;
            }
        }
        
        $currency = Currency::symbolForEvent($event);

        $response = [
            'ticket_title' => $ticketType->title,
            'ticket_price' => $currency . number_format($ticketType->ticket_price, 2),
            'quantity' => $quantity,
            'parking_price' => $currency.$parkingPricePerSlot,
            'parking_slots' => $parkingSlots ?? 0,
            'subtotal' => $currency . number_format($subtotal, 2),
            'parking_total' => $currency . number_format($parkingTotal, 2),
            'bulk_discount_applied' => false,
            'coupon_applied' => false,
            'total_amount' => $currency . number_format($final_amount, 2)
        ];

        /**
         * ðŸ”´ BULK DISCOUNT CHECK (PRIORITY)
         */
        $bulkTier = null;

        if ($ticketType->enable_bulk_discount) {
            $bulkTier = BulkDiscount::where('event_id', $eventId)
                ->where('ticket_type_id', $ticket_type_id)
                ->where('min_order_qty', '<=', $quantity)
                ->orderBy('min_order_qty', 'desc')
                ->first();
        }

        if ($bulkTier) {
            $bulkDiscountAmount = ($subtotal * $bulkTier->discount_percentage) / 100;

            $response['bulk_discount_applied'] = true;
            $response['bulk_discount_percentage'] = $bulkTier->discount_percentage;
            $response['bulk_discount_amount'] = $currency . number_format($bulkDiscountAmount, 2);

            // ðŸ”¥ Coupon is NOT allowed
            $response['coupon_applied'] = false;
            $response['coupon_blocked_reason'] = 'Bulk discount active';

            $response['total_amount'] = $currency . number_format($final_amount - $bulkDiscountAmount, 2);

            return response()->json($response);
        }

        /**
         * ðŸŸ¢ COUPON CHECK (ONLY IF NO BULK DISCOUNT)
         */
        if ($couponCode) {
            $coupon = DiscountCoupon::where('event_id', $eventId)
                ->where('coupon_code', $couponCode)
                ->first();

            if ($coupon) {
                $allowedTicketTypes = $coupon->ticket_type_ids;

                $isValidForTicketType = empty($allowedTicketTypes)
                    || in_array($ticket_type_id, $allowedTicketTypes)
                    || in_array((string)$ticket_type_id, $allowedTicketTypes);

                if ($isValidForTicketType) {
                    $couponAmount = ($subtotal * $coupon->discount) / 100;

                    $response['coupon_applied'] = true;
                    $response['coupon_code'] = $coupon->coupon_code;
                    $response['coupon_percentage'] = $coupon->discount;
                    $response['coupon_amount'] = $currency . number_format($couponAmount, 2);

                    $response['total_amount'] = $currency . number_format($final_amount - $couponAmount, 2);
                }
            }
        }

        return response()->json($response);
    }
public function store(Request $request)
{
    $eventId = $request->event_id ?? session('active_event_id');

    $request->validate([
        'ticket_type_id' => 'required|exists:ticket_types,id',
        'quantity'       => 'required|integer|min:1',
        'name'           => 'required|string|max:255',
        'email'          => 'required|email|max:255',
        'phone_prefix'   => 'required|string|max:20',
        'mobile_number'  => 'required|digits_between:1,12',
        'country_id'     => 'required|exists:countries,id',
        'state_id'       => 'required|exists:states,id',
        'selected_seats' => 'nullable|array',
        'parking_slots'  => 'nullable',
        'car_details'    => 'nullable|array',
        'contestent_id'  => [
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

        $selectedSeats = is_array($request->selected_seats ?? null)
            ? array_values(array_unique($request->selected_seats))
            : [];

        // 1. Pre-transaction Availability Check
        if (!empty($selectedSeats)) {
            $request->merge(['quantity' => count($selectedSeats)]);
            $alreadyTaken = DB::table('ticket_type_seats')
                ->where('ticket_type_id', $request->ticket_type_id)
                ->whereIn('venue_layout_id', $selectedSeats)
                ->where('is_booked', true)
                ->exists();

            if ($alreadyTaken) {
                return response()->json(['success' => false, 'message' => 'One or more seats were just sold.']);
            }
        }

        try {
            // Handle transaction exactly like the Web/Stripe controller
            $booking = DB::transaction(function () use ($request, $eventId, $selectedSeats) {
                
                $billResponse = $this->calculateBill($request);
                $billData = $billResponse->getData(true);
                $couponApplied = $billData['coupon_applied'] && !$billData['bulk_discount_applied'];

            // --- DATA CLEANING STEP ---
            // Strip any currency symbols and commas before decimal casting.
            $cleanTotal = preg_replace('/[^0-9.]/', '', $billData['total_amount']);
            $cleanCoupon = preg_replace('/[^0-9.]/', '', $billData['coupon_amount'] ?? 0);

            // 2. Create the Booking Record
            $newBooking = TicketCounter::create([
                'event_id'              => $eventId,
                'ticket_type_id'        => $request->ticket_type_id,
                'qty'                   => $request->quantity,
                'selected_seats'        => !empty($selectedSeats) ? json_encode($selectedSeats) : null,
                'bulk_discount_applied' => $billData['bulk_discount_applied'],
                'coupon_applied'        => $couponApplied,
                'coupon_code'           => $couponApplied ? $billData['coupon_code'] : null,
                'coupon_amount'         => $couponApplied ? (float)$cleanCoupon : 0,
                'coupon_percentage'     => $couponApplied ? (float)($billData['coupon_percentage'] ?? 0) : 0,
                'total_amount'          => (float)$cleanTotal, 
                'name'                  => $request->name,
                'email'                 => $request->email,
                'phone_prefix'          => $request->phone_prefix,
                'mobile_number'         => $request->mobile_number,
                'country_id'            => $request->country_id,
                'state_id'              => $request->state_id,
                'payment_status'        => 'paid',
                'booking_status'        => TicketCounter::STATUS_CONFIRMED,
                'refund_status'         => TicketCounter::REFUND_NOT_REQUIRED,
                'payment_method'        => 'manual',
                'created_by'            => auth()->id() ?? 1,
            ]);

                $newBooking->refresh();
                $bId = $newBooking->booking_id;

                if ($request->filled('contestent_id')) {
                    $vote = EventContestentVote::firstOrCreate(
                        [
                            'event_id' => $eventId,
                            'booking_id' => $bId,
                        ],
                        [
                            'event_contestent_id' => $request->contestent_id,
                            'ticket_counter_id' => $newBooking->id,
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

                // 3. Generate Individual Tickets
                for ($i = 0; $i < $request->quantity; $i++) {
                    $seatId = $selectedSeats[$i] ?? null;
                    $ticketNumber = $seatId 
                        ? $bId . "-S" . $seatId . "-" . strtoupper(Str::random(4))
                        : $bId . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4));

                    DB::table('booked_tickets')->insertOrIgnore([
                        'ticket_counter_id' => $newBooking->id,
                        'booking_id'        => $bId,
                        'ticket_number'     => $ticketNumber,
                        'venue_layout_id'   => $seatId,
                        'status'            => 'Not Scanned',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                // 4. Handle Parking
                $parkingSlots = $request->parking_slots;
                $parkingCount = is_array($parkingSlots) ? count($parkingSlots) : (int)$parkingSlots;
                if ($parkingCount <= 0 && is_array($request->car_details)) {
                    $parkingCount = count($request->car_details);
                }

                if ($parkingCount > 0) {
                    for ($i = 0; $i < $parkingCount; $i++) {
                        \App\Models\TicketParking::create([
                            'ticket_counter_id' => $newBooking->id,
                            'ticket_type_id'    => $request->ticket_type_id,
                            'car_number'        => $request->car_details[$i] ?? 'Generic-' . ($i + 1),
                            'parking_code'      => 'PK-' . strtoupper(Str::random(10)),
                            'status'            => 'unused'
                        ]);
                    }
                }

                // 5. Sync Physical Seats
                if (!empty($selectedSeats)) {
                    DB::table('ticket_type_seats')
                        ->where('ticket_type_id', $request->ticket_type_id)
                        ->whereIn('venue_layout_id', $selectedSeats)
                        ->update([
                            'is_booked'         => true,
                            'ticket_counter_id' => $newBooking->id,
                            'booking_id'        => $bId,
                            'booked_at'         => now(),
                            'updated_at'        => now()
                        ]);
                }

                return $newBooking; // Returns the full Object to $booking outside the closure
            });

            // --- EMAIL LOGIC (Post-Commit) ---
            if ($booking) {
                try {
                    // Ensure relationships are loaded before passing to the mailer
                    $booking->load(['parkings', 'ticketType', 'event']);
                    
                    $this->sendTicketEmail($booking); 
                    \Log::info('Mail success for booking: ' . $booking->booking_id);
                } catch (\Exception $e) {
                    \Log::error('Mail Error: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket purchased successfully!',
                'booking_id' => $booking->booking_id,
            ]);

        } catch (\Exception $e) {
            \Log::error('API Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to handle PDF/Email logic
    */
    protected function sendTicketEmail($booking) 
    {
        $booking->load('parkings');
        $event = Event::with('support')->find($booking->event_id);
        $ticketType = TicketType::find($booking->ticket_type_id);

        $ticketDirectory = "tickets/{$booking->booking_id}";
        if (!Storage::disk('public')->exists($ticketDirectory)) {
            Storage::disk('public')->makeDirectory($ticketDirectory);
        }

        // 1. Generate Main Ticket PDF (Multi-section based on qty)
        $ticketFileName = "Tickets_{$booking->booking_id}.pdf";
        $ticketPath = "{$ticketDirectory}/{$ticketFileName}";
        $ticketPdf = Pdf::loadView('website.events.event-ticket-pdf', [
            'booking'    => $booking,
            'event'      => $event,
            'ticketType' => $ticketType,
        ])->setPaper('a4')->output();
        Storage::disk('public')->put($ticketPath, $ticketPdf);

        // 2. Generate Combined Parking PDF (One file, multiple sections)
        $parkingFinalPath = null;
        if ($booking->parkings->count() > 0) {
            $parkingFileName = "Parking_Passes_{$booking->booking_id}.pdf";
            $parkingRelPath = "{$ticketDirectory}/{$parkingFileName}";
            
            $parkingPdf = Pdf::loadView('website.events.event-parking-pdf', [
                'booking'    => $booking,
                'event'      => $event,
                'ticketType' => $ticketType,
                'parkings'   => $booking->parkings
            ])->setPaper('a4')->output();

            Storage::disk('public')->put($parkingRelPath, $parkingPdf);
            $parkingFinalPath = storage_path("app/public/{$parkingRelPath}");
        }

        $finalTicketPath = storage_path("app/public/{$ticketPath}");

        // 3. Send Email (Pass parking path as a string or null, not an array)
        Mail::to($booking->email)->send(new EventTicketMail(
            $booking, 
            $event, 
            $ticketType, 
            $finalTicketPath, 
            $parkingFinalPath
        ));
    }





}
