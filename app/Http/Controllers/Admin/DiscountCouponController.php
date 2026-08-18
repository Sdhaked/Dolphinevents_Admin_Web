<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCoupon;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DiscountCouponController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    private function validateData(Request $request, $couponId = null): array
    {
        $eventId = session('active_event_id');
        $request->merge([
            'coupon_code' => strtoupper(trim((string) $request->input('coupon_code'))),
        ]);

        return $request->validate([
            'title' => 'required|string|max:255',
            'coupon_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('discount_coupons')->where(function ($query) use ($eventId) {
                    return $query->where('event_id', $eventId);
                })->ignore($couponId)
            ],
            'associate_name' => 'required|string|max:255',
            'discount' => 'required|numeric|min:0|max:100',
            'also_associate' => 'nullable|string',
            'ticket_type_ids' => 'required|array|min:1',
            'ticket_type_ids.*' => 'exists:ticket_types,id'
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $eventId = session('active_event_id');

        $coupons = DiscountCoupon::where('event_id', $eventId)
            ->with(['creator', 'event'])
            ->when($request->filled('search'), function ($query) use ($request, $eventId) {
                $search = $request->search;

                // Get ticket type IDs that match the search term in title
                $ticketTypeIds = TicketType::where('event_id', $eventId)
                    ->where('title', 'like', "%{$search}%")
                    ->pluck('id')
                    ->toArray();

                if (!empty($ticketTypeIds)) {
                    $query->where(function ($q) use ($ticketTypeIds) {
                        foreach ($ticketTypeIds as $ticketTypeId) {
                            // Search BOTH integer and string versions
                            $q->orWhereJsonContains('ticket_type_ids', (int)$ticketTypeId)
                              ->orWhereJsonContains('ticket_type_ids', (string)$ticketTypeId);
                        }
                    });
                } else {
                    // If no matching ticket types found, return no results
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.discount_coupons._partials.table', compact('coupons'))->render();
        }

        return view('admin.discount_coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $eventId = session('active_event_id');
        $ticketTypes = TicketType::where('event_id', $eventId)->get();

        return view('admin.discount_coupons.create', compact('ticketTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $validated['event_id'] = session('active_event_id');
        $validated['created_by'] = Auth::id();

        DiscountCoupon::create($validated);

        return redirect()->route('admin.discount.coupons.index')->with('success', 'Discount coupon created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $eventId = session('active_event_id');
        $coupon = DiscountCoupon::where('id', $id)
            ->where('event_id', $eventId)
            ->with(['creator', 'event'])
            ->first();

        if (!$coupon) {
            abort(404);
        }

        return view('admin.discount_coupons.show', compact('coupon'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $eventId = session('active_event_id');
        $coupon = DiscountCoupon::where('id', $id)
            ->where('event_id', $eventId)
            ->first();

        if (!$coupon) {
            abort(404);
        }

        $ticketTypes = TicketType::where('event_id', $eventId)->get();

        return view('admin.discount_coupons.edit', compact('coupon', 'ticketTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $eventId = session('active_event_id');
        $coupon = DiscountCoupon::where('id', $id)
            ->where('event_id', $eventId)
            ->first();

        if (!$coupon) {
            abort(404);
        }

        $validated = $this->validateData($request, $id);

        $coupon->update($validated);

        return redirect()->back()->with('success', 'Discount coupon updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $eventId = session('active_event_id');
        $coupon = DiscountCoupon::where('id', $id)
            ->where('event_id', $eventId)
            ->first();

        if (!$coupon) {
            abort(404);
        }

        // Coupons deleted from the admin screen must be removed permanently.
        // TicketCounter keeps its own coupon-code snapshot, so historical
        // booking records remain intact after the coupon row is removed.
        $coupon->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Discount coupon permanently deleted successfully!'
        ]);
    }
}
