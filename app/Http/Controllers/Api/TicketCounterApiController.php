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
use App\Models\TicketCounterAgeGroup;
use App\Models\TicketCounterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use App\Services\TicketPdfService;

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
        $ticketType = TicketType::with('ageGroups')->findOrFail($ticketTypeId);
        
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

        $ageGroups = $ticketType->enable_age_group
            ? $ticketType->ageGroups->map(function ($ageGroup) {
                $sold = TicketCounterAgeGroup::where('ticket_type_age_group_id', $ageGroup->id)
                    ->whereHas('booking', function ($query) {
                        $query->withTrashed()->whereIn('booking_status', $this->activeBookingStatuses());
                    })
                    ->sum('quantity');
                $remaining = (int) $ageGroup->total_tickets > 0
                    ? max(0, (int) $ageGroup->total_tickets - (int) $sold)
                    : 20;

                return [
                    'id' => $ageGroup->id,
                    'label' => $ageGroup->label,
                    'price' => (float) $ageGroup->price,
                    'available_tickets' => min($remaining, 20),
                    'max_quantity_per_booking' => min(max(1, (int) $ageGroup->max_quantity_per_booking), 20),
                    'is_compulsory' => (bool) $ageGroup->is_compulsory,
                ];
            })->values()
            : collect();

        return response()->json([
            'available_tickets' => max(0, $availableToDisplay), // Ensure we don't return negative numbers
            'ticket_price' => $ticketType->ticket_price,
            'title' => $ticketType->title,
            'enable_age_group' => (bool) $ticketType->enable_age_group,
            'age_groups' => $ageGroups,
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

    private function activeBookingStatuses(): array
    {
        return [
            TicketCounter::STATUS_CONFIRMED,
            TicketCounter::STATUS_PENDING_VERIFICATION,
            TicketCounter::STATUS_PENDING_PAYMENT,
        ];
    }

    private function normalizeSelectedSeats(Request $request): array
    {
        $selectedSeats = is_array($request->selected_seats ?? null)
            ? $request->selected_seats
            : [];

        return array_values(array_unique(array_filter($selectedSeats, fn ($seatId) => filled($seatId))));
    }

    private function availableTicketQuantity(TicketType $ticketType): int
    {
        $soldCount = TicketCounter::withTrashed()
            ->where('ticket_type_id', $ticketType->id)
            ->whereIn('booking_status', $this->activeBookingStatuses())
            ->sum('qty');

        return max(0, (int) $ticketType->total_tickets - (int) $soldCount);
    }

    private function resolveAgeGroupItems(TicketType $ticketType, Request $request, array $selectedSeats)
    {
        if (!$ticketType->enable_age_group || !empty($selectedSeats)) {
            return collect();
        }

        $ageGroups = $ticketType->relationLoaded('ageGroups')
            ? $ticketType->ageGroups
            : $ticketType->ageGroups()->get();

        if ($ageGroups->isEmpty()) {
            return collect();
        }

        $requestedAgeGroups = collect(is_array($request->age_group_items ?? null) ? $request->age_group_items : [])
            ->mapWithKeys(fn ($item) => [(int) ($item['id'] ?? 0) => max(0, (int) ($item['quantity'] ?? 0))]);

        $ageGroupItems = collect();

        foreach ($ageGroups as $ageGroup) {
            $requestedQuantity = (int) ($requestedAgeGroups[$ageGroup->id] ?? 0);

            if ($ageGroup->is_compulsory && $requestedQuantity <= 0) {
                throw new \RuntimeException($ageGroup->label . ' age group is compulsory.');
            }

            if ($requestedQuantity <= 0) {
                continue;
            }

            $maxPerBooking = min(max(1, (int) $ageGroup->max_quantity_per_booking), 20);
            if ($requestedQuantity > $maxPerBooking) {
                throw new \RuntimeException("Maximum {$maxPerBooking} {$ageGroup->label} tickets are allowed per booking.");
            }

            if ((int) $ageGroup->total_tickets > 0) {
                $sold = TicketCounterAgeGroup::where('ticket_type_age_group_id', $ageGroup->id)
                    ->whereHas('booking', function ($query) {
                        $query->withTrashed()->whereIn('booking_status', $this->activeBookingStatuses());
                    })
                    ->sum('quantity');
                $available = max(0, (int) $ageGroup->total_tickets - (int) $sold);

                if ($requestedQuantity > $available) {
                    throw new \RuntimeException("Only {$available} {$ageGroup->label} tickets remaining.");
                }
            }

            $ageGroupItems->push([
                'id' => $ageGroup->id,
                'label' => $ageGroup->label,
                'quantity' => $requestedQuantity,
                'price' => (float) $ageGroup->price,
                'total' => round($requestedQuantity * (float) $ageGroup->price, 2),
            ]);
        }

        if ($ageGroupItems->isEmpty()) {
            throw new \RuntimeException('Please select at least one age-group ticket.');
        }

        return $ageGroupItems->values();
    }

    private function parseMoney($value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', (string) $value);
    }

    private function createBookingAgeGroups(TicketCounter $booking, array $ageGroupItems)
    {
        return collect($ageGroupItems)
            ->filter(fn ($item) => max(0, (int) ($item['quantity'] ?? 0)) > 0)
            ->map(function ($item) use ($booking) {
                return TicketCounterAgeGroup::create([
                    'ticket_counter_id' => $booking->id,
                    'ticket_type_age_group_id' => $item['id'] ?? null,
                    'label' => $item['label'] ?? 'Age Group',
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) ($item['price'] ?? 0),
                    'total_amount' => (float) ($item['total'] ?? ((int) $item['quantity'] * (float) ($item['price'] ?? 0))),
                ]);
            })
            ->values();
    }

    private function expandAgeGroupTicketAssignments($ageGroupRows): array
    {
        $assignments = [];

        foreach ($ageGroupRows as $ageGroupRow) {
            for ($i = 0; $i < max(0, (int) $ageGroupRow->quantity); $i++) {
                $assignments[] = [
                    'ticket_counter_age_group_id' => $ageGroupRow->id,
                    'ticket_type_age_group_id' => $ageGroupRow->ticket_type_age_group_id,
                    'sub_type_label' => $ageGroupRow->label,
                ];
            }
        }

        return $assignments;
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
        $quantity = $this->getResolvedQuantity($request);

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
            && !in_array((int) $ticketTypeId, array_map('intval', $allowedTicketTypes), true)
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
        try {
            return response()->json($this->buildBillData($request));
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function buildBillData(Request $request): array
    {
        $eventId = $request->event_id ?? session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;
        $couponCode = $request->coupon_code;
        $parkingSlots = (int) ($request->parking_slots ?? 0);
        $selectedSeats = $this->normalizeSelectedSeats($request);

        $event = Event::findOrFail($eventId);
        $parkingPricePerSlot = $event->car_slot_price ?? 0;
        $ticketType = TicketType::with('ageGroups')->findOrFail($ticket_type_id);

        $quantity = !empty($selectedSeats)
            ? count($selectedSeats)
            : max(1, (int) ($request->quantity ?? 1));
        $ageGroupItems = $this->resolveAgeGroupItems($ticketType, $request, $selectedSeats);

        if ($ticketType->enable_age_group && $ageGroupItems->isNotEmpty()) {
            $quantity = $ageGroupItems->sum('quantity');
            if ($quantity > 20) {
                throw new \RuntimeException('Maximum 20 tickets are allowed per booking.');
            }
            $subtotal = $ageGroupItems->sum('total');
        } else {
            if ($quantity > 20) {
                throw new \RuntimeException('Maximum 20 tickets are allowed per booking.');
            }
            $subtotal = $ticketType->ticket_price * $quantity;
        }

        if (empty($selectedSeats)) {
            $availableTickets = $this->availableTicketQuantity($ticketType);
            if ($quantity > $availableTickets) {
                throw new \RuntimeException("Only {$availableTickets} tickets remaining.");
            }
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
            $couponTicketTypes = $coupon?->ticket_type_ids ?? [];
            $couponApplies = $coupon && (empty($couponTicketTypes) || in_array((int) $ticket_type_id, array_map('intval', $couponTicketTypes), true));

            if ($couponApplies) {
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
                    'total' => round($quantity * (float) $service->price, 2),
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

        $orderSubtotal = $subtotal + $serviceTotal + $parkingTotal;

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

        return [
            'ticket_title' => $ticketType->title,
            'ticket_price' => $currency . number_format($ticketType->ticket_price, 2),
            'quantity' => $quantity,
            'subtotal' => $currency . number_format($subtotal, 2),
            'order_subtotal' => $currency . number_format($orderSubtotal, 2),
            'age_group_items' => $ageGroupItems->map(fn ($item) => [
                'label' => $item['label'],
                'quantity' => $item['quantity'],
                'price' => $currency . number_format($item['price'], 2),
                'total' => $currency . number_format($item['total'], 2),
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
            'raw_total' => round($final_amount, 2),
            'raw_quantity' => $quantity,
            'raw_subtotal' => round($subtotal, 2),
            'raw_order_subtotal' => round($orderSubtotal, 2),
            'raw_discount_amount' => round($discountAmount, 2),
            'raw_age_group_items' => $ageGroupItems->all(),
            'raw_service_items' => $serviceItems->all(),
        ];
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
        'quantity'       => 'required|integer|min:1|max:20',
        'name'           => 'required|string|max:255',
        'email'          => 'required|email|max:255',
        'phone_prefix'   => 'required|string|max:20',
        'mobile_number'  => 'required|digits_between:1,12',
        'country_id'     => 'required|exists:countries,id',
        'state_id'       => 'required|exists:states,id',
        'selected_seats' => 'nullable|array',
        'parking_slots'  => 'nullable',
        'car_details'    => 'nullable|array',
        'service_items'  => 'nullable|array',
        'service_items.*.id' => 'nullable|integer',
        'service_items.*.quantity' => 'nullable|integer|min:0',
        'age_group_items' => 'nullable|array',
        'age_group_items.*.id' => 'nullable|integer',
        'age_group_items.*.quantity' => 'nullable|integer|min:0',
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
                $billData = $this->buildBillData($request);
                $couponApplied = $billData['coupon_applied'] && !$billData['bulk_discount_applied'];
                $resolvedQuantity = (int) ($billData['raw_quantity'] ?? $request->quantity);

            // 2. Create the Booking Record
            $newBooking = TicketCounter::create([
                'event_id'              => $eventId,
                'ticket_type_id'        => $request->ticket_type_id,
                'qty'                   => $resolvedQuantity,
                'selected_seats'        => !empty($selectedSeats) ? json_encode($selectedSeats) : null,
                'bulk_discount_applied' => $billData['bulk_discount_applied'],
                'coupon_applied'        => $couponApplied,
                'coupon_code'           => $couponApplied ? $billData['coupon_code'] : null,
                'coupon_amount'         => $couponApplied ? (float) ($billData['raw_discount_amount'] ?? 0) : 0,
                'coupon_percentage'     => $couponApplied ? (float)($billData['coupon_percentage'] ?? 0) : 0,
                'total_amount'          => (float) ($billData['raw_total'] ?? $this->parseMoney($billData['total_amount'] ?? 0)),
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
                $ageGroupRows = $this->createBookingAgeGroups($newBooking, $billData['raw_age_group_items'] ?? []);
                $ticketAgeGroupAssignments = $this->expandAgeGroupTicketAssignments($ageGroupRows);

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
                for ($i = 0; $i < $resolvedQuantity; $i++) {
                    $seatId = $selectedSeats[$i] ?? null;
                    $ticketNumber = $seatId 
                        ? $bId . "-S" . $seatId . "-" . strtoupper(Str::random(4))
                        : $bId . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4));
                    $ageGroupAssignment = $ticketAgeGroupAssignments[$i] ?? [];

                    DB::table('booked_tickets')->insertOrIgnore([
                        'ticket_counter_id' => $newBooking->id,
                        'booking_id'        => $bId,
                        'ticket_number'     => $ticketNumber,
                        'venue_layout_id'   => $seatId,
                        'ticket_counter_age_group_id' => $ageGroupAssignment['ticket_counter_age_group_id'] ?? null,
                        'ticket_type_age_group_id' => $ageGroupAssignment['ticket_type_age_group_id'] ?? null,
                        'sub_type_label' => $ageGroupAssignment['sub_type_label'] ?? null,
                        'status'            => 'Not Scanned',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                foreach (($billData['raw_service_items'] ?? []) as $serviceItem) {
                    $serviceQuantity = max(0, (int) ($serviceItem['quantity'] ?? 0));

                    if ($serviceQuantity <= 0) {
                        continue;
                    }

                    TicketCounterService::create([
                        'ticket_counter_id' => $newBooking->id,
                        'event_id' => $eventId,
                        'event_service_id' => $serviceItem['id'] ?? null,
                        'service_name' => $serviceItem['name'] ?? 'Event Service',
                        'quantity' => $serviceQuantity,
                        'price' => (float) ($serviceItem['price'] ?? 0),
                        'total_amount' => (float) ($serviceItem['total'] ?? ($serviceQuantity * (float) ($serviceItem['price'] ?? 0))),
                        'service_code' => 'SV-' . strtoupper(Str::random(10)),
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

        } catch (\RuntimeException $e) {
            \Log::warning('API Store validation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
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
        if ($booking->ticket_email_sent_at) {
            return;
        }

        app(TicketPdfService::class)->sendTicketEmail($booking);

        $booking->forceFill([
            'ticket_email_sent_at' => now(),
        ])->save();
    }





}
