<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BulkDiscountSlbController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $eventId = session('active_event_id');
        $ticket_type_id = $request->ticket_type_id;

        $bulkDiscounts = BulkDiscount::where('event_id', $eventId)
                        ->where('ticket_type_id', $ticket_type_id)
                        ->orderBy('min_order_qty', 'asc')->get();

       return response()->json([
                'html' => view('admin.bulk-discount._partials.table', compact('bulkDiscounts'))->render()
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BulkDiscount $bulkDiscount)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $bulkDiscount
            ]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BulkDiscount $bulkDiscount)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $bulkDiscount
            ]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $eventId = session('active_event_id');

            $request->validate([
                'min_order_qty' => [
                    'required',
                    'integer',
                    'min:1',
                    Rule::unique('bulk_discounts')
                        ->where('ticket_type_id', $request->ticket_type_id)
                        ->where('event_id', $eventId),
                ],
                'discount_percentage' => 'required|numeric|min:0|max:100',
            ]);

            // Check sequence validation
            $existingDiscounts = BulkDiscount::where('ticket_type_id', $request->ticket_type_id)
                ->where('event_id', $eventId)
                ->orderBy('min_order_qty', 'asc')
                ->get();

            $newMinQty = (int)$request->min_order_qty;
            
            // Validate sequence: new quantity should be greater than the highest existing quantity
            if ($existingDiscounts->isNotEmpty()) {
                $maxExistingQty = $existingDiscounts->max('min_order_qty');
                if ($newMinQty <= $maxExistingQty) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['min_order_qty' => ['Min order quantity must be greater than ' . $maxExistingQty . ' to maintain sequence.']]
                        ], 422);
                    }
                    return back()->withErrors(['min_order_qty' => 'Min order quantity must be greater than ' . $maxExistingQty . ' to maintain sequence.']);
                }
            }

            BulkDiscount::create([
                'event_id' => session('active_event_id'),
                'ticket_type_id' => $request->ticket_type_id,
                'min_order_qty' => $request->min_order_qty,
                'discount_percentage' => $request->discount_percentage,
                'created_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bulk discount created successfully.'
                ]);
            }

            return back()->with('success', 'Bulk discount created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BulkDiscount $bulkDiscount)
    {
        try {
            $eventId = session('active_event_id');

            $uniqueRule = 'unique:bulk_discounts,min_order_qty,' . $bulkDiscount->id . ',id,event_id,' . $eventId;

            $request->validate([
                'min_order_qty' => 'required|integer|min:1|' . $uniqueRule,
                'discount_percentage' => 'required|numeric|min:0|max:100',
            ]);

            // Check sequence validation for update
            $existingDiscounts = BulkDiscount::where('ticket_type_id', $bulkDiscount->ticket_type_id)
                ->where('event_id', $eventId)
                ->where('id', '!=', $bulkDiscount->id)
                ->orderBy('min_order_qty', 'asc')
                ->get();

            $newMinQty = (int)$request->min_order_qty;
            
            // Find the position where this discount should be placed
            $lowerDiscount = $existingDiscounts->where('min_order_qty', '<', $newMinQty)->last();
            $higherDiscount = $existingDiscounts->where('min_order_qty', '>', $newMinQty)->first();
            
            // Validate sequence
            if ($lowerDiscount && $newMinQty <= $lowerDiscount->min_order_qty) {
                return response()->json([
                    'success' => false,
                    'errors' => ['min_order_qty' => ['Min order quantity must be greater than ' . $lowerDiscount->min_order_qty . ' to maintain sequence.']]
                ], 422);
            }
            
            if ($higherDiscount && $newMinQty >= $higherDiscount->min_order_qty) {
                return response()->json([
                    'success' => false,
                    'errors' => ['min_order_qty' => ['Min order quantity must be less than ' . $higherDiscount->min_order_qty . ' to maintain sequence.']]
                ], 422);
            }

            $bulkDiscount->update([
                'min_order_qty' => $request->min_order_qty,
                'discount_percentage' => $request->discount_percentage,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                    'success' => true,
                    'message' => 'Bulk discount updated successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BulkDiscount $bulkDiscount)
    {
        $bulkDiscount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bulk discount deleted successfully.'
        ]);
    }
}
