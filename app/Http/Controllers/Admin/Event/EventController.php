<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;

use App\Models\Event;
use App\Models\Currency;
use App\Models\EventSponsor;
use App\Models\EventGallery;
use App\Models\EventContestent;
use App\Models\EventSlider;
use App\Models\EventSupport;
use App\Models\DiscountCoupon;
use App\Models\TicketHold;
use App\Models\TicketChecker;
use App\Models\TicketCounter;
use App\Models\BookedTicket;
use App\Models\TicketParking;
use App\Models\TicketType;
use App\Models\BulkDiscount;

use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Duplicate an existing event.
     */
    public function duplicate(Request $request)
    {
        try {
            $validated = $request->validate([
                'new_title' => ['required', 'string', 'max:255'],
                'event_id' => ['required', 'exists:events,id'],
            ], [], $this->duplicateValidationAttributes());

            // 1. Fetch original with all relationships including DiscountCoupons
            // Note: Using 'ticketTypes' and 'bulkDiscounts' as defined in your Event model
            $original = Event::with(['support', 'ticketTypes', 'bulkDiscounts'])
                ->findOrFail($validated['event_id']);

            // 2. Replicate the core Event
            $newEvent = $original->replicate();
            $newEvent->title = $validated['new_title'];
            $newEvent->status = Event::STATUS_DRAFT; 
            $newEvent->save(); 

            // 3. Duplicate Support Details
            if ($original->support) {
                $newSupport = $original->support->replicate();
                $newSupport->event_id = $newEvent->id;
                // Ensure you have removed the UNIQUE constraint on email in your DB as discussed
                $newSupport->save();
            }

            // 4. Duplicate Ticket Types
            // We store the mapping of old ID => new ID to fix Coupon and Bulk Discount IDs
            $ticketTypeMapping = [];
            foreach ($original->ticketTypes as $type) {
                $newType = $type->replicate();
                $newType->event_id = $newEvent->id;
                $newType->save();
                $ticketTypeMapping[$type->id] = $newType->id;
            }

            // 5. Duplicate Bulk Discounts with synced Ticket Type IDs
            foreach ($original->bulkDiscounts as $discount) {
                $newDiscount = $discount->replicate();
                $newDiscount->event_id = $newEvent->id;
                
                // Sync the ticket_type_id to the NEW ticket type ID
                if (isset($ticketTypeMapping[$discount->ticket_type_id])) {
                    $newDiscount->ticket_type_id = $ticketTypeMapping[$discount->ticket_type_id];
                }
                
                $newDiscount->save();
            }
            // 6. Duplicate Discount Coupons with mapped Ticket Type IDs
            $oldCoupons = \App\Models\DiscountCoupon::where('event_id', $original->id)->get();
            foreach ($oldCoupons as $coupon) {
                $newCoupon = $coupon->replicate();
                $newCoupon->event_id = $newEvent->id;
                
                // Sync the ticket_type_ids array to the NEW ticket type IDs
                if (!empty($coupon->ticket_type_ids) && is_array($coupon->ticket_type_ids)) {
                    $newTicketIds = [];
                    foreach ($coupon->ticket_type_ids as $oldId) {
                        if (isset($ticketTypeMapping[$oldId])) {
                            $newTicketIds[] = $ticketTypeMapping[$oldId];
                        }
                    }
                    $newCoupon->ticket_type_ids = $newTicketIds;
                }

                // Save without setting usage_count or last_used_at to avoid SQL error
                $newCoupon->save();
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Event, Tickets, Bulk Discounts, and Coupons duplicated successfully.',
                'data' => $newEvent,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $this->firstValidationMessage($e, $this->duplicateValidationAttributes()),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Duplication Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function eventsList()
    {
        $events = Event::all();

        return response()->json([
            'status' => true,
            'events' => $events
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.events.create');
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'type' => ['required', 'integer', 'in:1,2'],
            ], [], $this->eventValidationAttributes());

            $event = Event::create([
                'title' => $validated['title'],
                'type' => $validated['type'],
            ]);

            session(['active_event_id' => $event->id]);

            return response()->json([
                'status' => true,
                'message' => 'Event created successfully.',
                'data' => $event,
            ], 201);
        } catch (ValidationException $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $this->firstValidationMessage($e, $this->eventValidationAttributes()),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while saving event.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $eventId = session('active_event_id');
        $event = Event::where('id', $eventId)->first();

        if (is_null($event)) {
            return response()->json([
                'status' => false,
                'message' => 'Event not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'event' => $event,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $eventId = session('active_event_id');
        
        if (!$eventId) {
            return redirect()->route('admin.dashboard.index')->with('error', 'No active event selected.');
        }
        
        $event = Event::find($eventId);
        
        if (!$event) {
            // Clear invalid session and redirect
            session()->forget('active_event_id');
            return redirect()->route('admin.dashboard.index')->with('error', 'Event not found.');
        }
        
        $currencies = Currency::options();

        return view('admin.events.edit', compact('event', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $isVotingEnabled = $request->boolean('enable_voting');

            $request->validate([
                'title' => 'required|string|max:255',
                'currency_id' => 'required|exists:currencies,id',
                'featured_image_alt_text' => 'nullable|string|max:255',
                'venue_layout_image_alt_text' => 'nullable|string|max:255',
                'event_pdf_sponser_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'from_date' => 'required|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'from_time' => 'required',
                'to_time' => 'required',
                'sell_tickets_till' => 'required|date',
                'map_link' => 'required|url',
                'address' => 'required|string',
                'enable_voting' => 'nullable|boolean',
                'voting_title' => [$isVotingEnabled ? 'required' : 'nullable', 'string', 'max:255'],
                'voting_btn_title' => [$isVotingEnabled ? 'required' : 'nullable', 'string', 'max:255'],
                'voting_des' => [$isVotingEnabled ? 'required' : 'nullable', 'string'],
            ], [], $this->eventValidationAttributes());
            
            
            $eventId = session('active_event_id');
            $event = Event::find($eventId);
            
            if (!$event) 
            {
                return redirect()->route('admin.events.edit')->with('error', 'Event not found.');
            }
                

            $featured_video_path = $event->featured_video ?? null;
            $thumbnail_path = $event->thumbnail ?? null;
            $featured_image_path = $event->featured_image ?? null;
            $venue_layout_image_path = $event->venue_layout_image ?? null;
            
            // PDF event sponser image
            // Keep track of the current image path
            $pdf_sponsor_image_path = $event->event_pdf_sponser_image ?? null;

            // Handle the File Upload
            if ($request->hasFile('event_pdf_sponser_image')) {
                // Delete old image if it exists in storage
                if ($pdf_sponsor_image_path && Storage::disk('public')->exists($pdf_sponsor_image_path)) {
                    Storage::disk('public')->delete($pdf_sponsor_image_path);
                }

                // Store new image in a dedicated folder
                $pdf_sponsor_image_path = $request->file('event_pdf_sponser_image')
                                                ->store('events/pdf_sponsors', 'public');
            }
            

            // Featured Video
            if ($request->hasFile('featured_video')) {
                // delete old if exists
                if ($featured_video_path && Storage::disk('public')->exists($featured_video_path)) {
                    Storage::disk('public')->delete($featured_video_path);
                }

                // store new
                $featured_video_path = $request->file('featured_video')->store('events/videos', 'public');
            }

            // Thumbnail
            if ($request->hasFile('thumbnail')) {
                if ($thumbnail_path && Storage::disk('public')->exists($thumbnail_path)) {
                    Storage::disk('public')->delete($thumbnail_path);
                }

                $thumbnail_path = $request->file('thumbnail')->store('events/thumbnails', 'public');
            }

            // Featured Image
            if ($request->hasFile('featured_image')) {
                if ($featured_image_path && Storage::disk('public')->exists($featured_image_path)) {
                    Storage::disk('public')->delete($featured_image_path);
                }

                $featured_image_path = $request->file('featured_image')->store('events/images', 'public');
            }

            // Venue Layout Image
            if ($request->hasFile('venue_layout_image')) {
                if ($venue_layout_image_path && Storage::disk('public')->exists($venue_layout_image_path)) {
                    Storage::disk('public')->delete($venue_layout_image_path);
                }

                $venue_layout_image_path = $request->file('venue_layout_image')->store('events/images', 'public');
            }

            // Process slug: lowercase and join with hyphens
            $slug = $request->input('slug');
            if ($slug) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
            }

            $event->update([
                'title' => $request->input('title'),
                'currency_id' => $request->input('currency_id'),
                'is_featured' => $request->has('is_featured') ? 1 : 0,
                'enable_car_parking' => $request->has('enable_car_parking', ) ? 1 : 0,
                'enable_voting' => $isVotingEnabled,
                'voting_title' => $request->input('voting_title'),
                'voting_btn_title' => $request->input('voting_btn_title'),
                'voting_des' => $request->input('voting_des'),
                'event_pdf_sponser_image' => $pdf_sponsor_image_path,
                'featured_video' => $featured_video_path,
                'car_parking_slots' => $request->car_parking_slots,
                'car_slot_price' => $request->car_slot_price,
                'thumbnail' => $thumbnail_path,
                'featured_image' => $featured_image_path,
                'featured_image_alt_text' => $request->input('featured_image_alt_text'),
                'venue_layout_image' => $venue_layout_image_path,
                'venue_layout_image_alt_text' => $request->input('venue_layout_image_alt_text'),
                'brought_you_by' => $request->input('brought_you_by'),
                // 'type' => $request->input('type') ?? 1,
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'from_time' => $request->input('from_time'),
                'to_time' => $request->input('to_time'),
                'sell_tickets_till' => $request->input('sell_tickets_till'),
                'map_link' => $request->input('map_link'),
                'address' => $request->input('address'),
                'description' => $request->input('editorData'),
                'slug' => $slug,
                'meta_data' => $request->input('meta_data') ?? null,
                'status' => $request->input('status', 0),
            ]);

            return redirect()->route('admin.events.edit')->with('success', 'Event updated successfully.');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $this->firstValidationMessage($e, $this->eventValidationAttributes()),
                    'errors' => $e->errors(),
                ], 422);
            }

            $errors = collect($e->validator->errors()->all())->implode(' ');
            return redirect()->route('admin.events.edit')
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please fill the form correctly. ' . $errors);
        } catch (\Exception $e) {
            Log::error('Event update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => session('active_event_id'),
            ]);

            return redirect()->route('admin.events.edit')->with('error', 'Something went wrong while updating the event.');
        }
    }

    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('events/editor-images', 'public');

        return response()->json([
            'url' => asset(Storage::url($path)),
        ]);
    }

    /**
     * Set the current active event
     */
    public function setCurrentEvent(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id'
        ]);

        session(['active_event_id' => $request->event_id]);

        return response()->json([
            'status' => true,
            'message' => 'Active event updated successfully.'
        ]);
    }

    /**
     * Get the current active event
     */
    public function getCurrentEvent()
    {
        $eventId = session('active_event_id');

        if (!$eventId) {
            return response()->json([
                'status' => false,
                'message' => 'No active event found.'
            ], 404);
        }

        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'Event not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->type
        ]);
    }

    /**
     * Remove the specified event and all associated data permanently.
     */
    public function destroy(Request $request, $id)
    {
        $transactionActive = false;

        try {
            $event = Event::findOrFail($id);

            // Check if event can be deleted based on dates
            $today = date('Y-m-d');
            
            if ($event->to_date) {
                // If to_date exists, event can only be deleted 1 day after to_date
                $deletableDate = date('Y-m-d', strtotime($event->to_date . ' +1 day'));
                if ($today < $deletableDate) {
                    $errorMsg = 'Event cannot be deleted until ' . date('d M Y', strtotime($deletableDate)) . ' (1 day after event ends).';
                    
                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => $errorMsg], 422);
                    }
                    return redirect()->back()->with('error', $errorMsg);
                }
            } else {
                // If to_date is null, check from_date - event can only be deleted 1 day after from_date
                if ($event->from_date) {
                    $deletableDate = date('Y-m-d', strtotime($event->from_date . ' +1 day'));
                    if ($today < $deletableDate) {
                        $errorMsg = 'Event cannot be deleted until ' . date('d M Y', strtotime($deletableDate)) . ' (1 day after event date).';
                        
                        if ($request->ajax()) {
                            return response()->json(['success' => false, 'message' => $errorMsg], 422);
                        }
                        return redirect()->back()->with('error', $errorMsg);
                    }
                }
            }

            DB::beginTransaction();
            $transactionActive = true;

            $ticketTypes = TicketType::withTrashed()
                ->where('event_id', $id)
                ->get(['id', 'featured_image']);
            $ticketTypeIds = $ticketTypes->pluck('id');

            $bookings = TicketCounter::withTrashed()
                ->where('event_id', $id)
                ->get(['id', 'booking_id']);
            $bookingIds = $bookings->pluck('id');

            $couponIds = DiscountCoupon::withTrashed()
                ->where('event_id', $id)
                ->pluck('id');

            $supportIds = EventSupport::where('event_id', $id)->pluck('id');
            $ticketCheckerIds = TicketChecker::withTrashed()
                ->where('event_id', $id)
                ->pluck('id');

            $filesToDelete = collect([
                $event->event_pdf_sponser_image,
                $event->featured_video,
                $event->thumbnail,
                $event->featured_image,
                $event->venue_layout_image,
            ])
                ->merge($ticketTypes->pluck('featured_image'))
                ->merge(EventSponsor::withTrashed()->where('event_id', $id)->pluck('image'))
                ->merge(EventGallery::withTrashed()->where('event_id', $id)->pluck('image'))
                ->merge(EventSlider::withTrashed()->where('event_id', $id)->pluck('image'))
                ->merge(EventContestent::withTrashed()->where('event_id', $id)->pluck('image'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $directoriesToDelete = $bookings
                ->pluck('booking_id')
                ->filter()
                ->map(fn ($bookingId) => 'tickets/' . $bookingId)
                ->push('events/' . $id)
                ->unique()
                ->values()
                ->all();

            // 1. Manual cleanup of associated data, child records first.
            TicketHold::where('event_id', $id)->delete();

            \App\Models\EventContestentVote::where('event_id', $id)->delete();

            if (\Illuminate\Support\Facades\Schema::hasTable('tickets')) {
                $ticketIds = \App\Models\Ticket::where('event_id', $id)->pluck('id');

                if ($ticketIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('ticket_status_history')) {
                    DB::table('ticket_status_history')->whereIn('ticket_id', $ticketIds)->delete();
                }

                \App\Models\Ticket::where('event_id', $id)->delete();
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('ticket_type_seats')) {
                DB::table('ticket_type_seats')->where('event_id', $id)->delete();

                if ($ticketTypeIds->isNotEmpty()) {
                    DB::table('ticket_type_seats')->whereIn('ticket_type_id', $ticketTypeIds)->delete();
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('event_seats')) {
                DB::table('event_seats')->where('event_id', $id)->delete();
            }

            if ($bookingIds->isNotEmpty()) {
                BookedTicket::whereIn('ticket_counter_id', $bookingIds)->delete();
                TicketParking::whereIn('ticket_counter_id', $bookingIds)->delete();
            }

            if ($ticketCheckerIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', TicketChecker::class)
                    ->whereIn('tokenable_id', $ticketCheckerIds)
                    ->delete();
            }

            TicketCounter::withTrashed()->where('event_id', $id)->forceDelete();
            TicketChecker::withTrashed()->where('event_id', $id)->forceDelete();

            if (
                \Illuminate\Support\Facades\Schema::hasTable('discount_coupon_ticket_type')
                && ($couponIds->isNotEmpty() || $ticketTypeIds->isNotEmpty())
            ) {
                DB::table('discount_coupon_ticket_type')
                    ->where(function ($query) use ($couponIds, $ticketTypeIds) {
                        if ($couponIds->isNotEmpty()) {
                            $query->whereIn('discount_coupon_id', $couponIds);
                        }

                        if ($ticketTypeIds->isNotEmpty()) {
                            $method = $couponIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                            $query->{$method}('ticket_type_id', $ticketTypeIds);
                        }
                    })
                    ->delete();
            }

            BulkDiscount::where('event_id', $id)->delete();

            if ($ticketTypeIds->isNotEmpty()) {
                BulkDiscount::whereIn('ticket_type_id', $ticketTypeIds)->delete();
            }

            DiscountCoupon::withTrashed()->where('event_id', $id)->forceDelete();
            TicketType::withTrashed()->where('event_id', $id)->forceDelete();

            // Delete Event-related data
            EventContestent::withTrashed()->where('event_id', $id)->forceDelete();
            EventSponsor::withTrashed()->where('event_id', $id)->forceDelete();
            EventGallery::withTrashed()->where('event_id', $id)->forceDelete();
            EventSlider::withTrashed()->where('event_id', $id)->forceDelete();

            if ($supportIds->isNotEmpty()) {
                \App\Models\EventSupportSocialLink::whereIn('event_support_id', $supportIds)->delete();
            }

            EventSupport::where('event_id', $id)->delete();

            // 2. Permanent Delete
            $event->forceDelete(); 

            DB::commit();
            $transactionActive = false;

            try {
                if (!empty($filesToDelete)) {
                    Storage::disk('public')->delete($filesToDelete);
                }

                foreach ($directoriesToDelete as $directory) {
                    Storage::disk('public')->deleteDirectory($directory);
                }
            } catch (\Throwable $fileException) {
                \Log::warning('Event files cleanup failed after event deletion', [
                    'event_id' => $id,
                    'error' => $fileException->getMessage(),
                ]);
            }

            // 3. Handle Session & Redirect Logic
            $nextEvent = Event::orderBy('id', 'desc')->first();

            if ($nextEvent) {
                // Set the first available event as active
                session(['active_event_id' => $nextEvent->id]);
                
                $redirectUrl = route('admin.dashboard.index'); // Adjust to your actual event dashboard route
                $message = 'Event deleted. Active event switched to: ' . $nextEvent->title;
            } 
            else 
            {
                // No events left in the system
                session()->forget('active_event_id');
                
                $redirectUrl = route('admin.dashboard.index');
                $message = 'Event deleted permanently. No other events found.';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => $redirectUrl
                ]);
            }

            return redirect($redirectUrl)->with('success', $message);

        } catch (\Exception $e) {
            if ($transactionActive) {
                DB::rollBack();
            }

            \Log::error('Event deletion failed', ['error' => $e->getMessage()]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete event.'], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }

    private function duplicateValidationAttributes(): array
    {
        return [
            'new_title' => 'Event name',
            'event_id' => 'Event',
        ];
    }

    private function eventValidationAttributes(): array
    {
        return [
            'title' => 'Event name',
            'currency_id' => 'Currency',
            'type' => 'Booking system',
            'featured_image_alt_text' => 'Featured image alt text',
            'venue_layout_image_alt_text' => 'Venue layout image alt text',
            'event_pdf_sponser_image' => 'PDF sponsor image',
            'from_date' => 'Start date',
            'to_date' => 'End date',
            'from_time' => 'Start time',
            'to_time' => 'End time',
            'sell_tickets_till' => 'Sell tickets till',
            'map_link' => 'Map link',
            'address' => 'Address',
            'enable_voting' => 'Voting module',
            'voting_title' => 'Voting title',
            'voting_btn_title' => 'Voting button title',
            'voting_des' => 'Voting description',
        ];
    }

    private function firstValidationMessage(ValidationException $exception, array $attributes = []): string
    {
        $errors = $exception->errors();
        $firstField = array_key_first($errors);

        if (!$firstField) {
            return 'Please fix the validation errors.';
        }

        $firstMessage = Arr::first($errors[$firstField]);

        if (!$firstMessage) {
            return 'Please fix the validation errors.';
        }

        $fieldLabel = $attributes[$firstField] ?? Str::of($firstField)->replace('_', ' ')->title()->toString();
        $formattedMessage = preg_replace(
            '/^The\s+.+?\s+field\s+/i',
            $fieldLabel . ' ',
            $firstMessage
        );

        return $formattedMessage ?: $firstMessage;
    }
}
