<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use App\Models\Currency;
use App\Models\Event;
use App\Models\EventContestent;
use App\Models\EventContestentVote;
use App\Models\TicketHold;
use App\Models\BulkDiscount;
use App\Models\Country;
use App\Models\EventArchivePageContent;
use App\Models\State;
use App\Models\TicketType;
use App\Models\TicketCounter;
use App\Models\PaymentTransaction;

use Stripe\Stripe;
use Stripe\Checkout\Session;

use App\Services\TicketPdfService;


class EventController extends Controller
{
    /**
     * List of events
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        $now = Carbon::createFromDate($year, $month, 1);

        $content = EventArchivePageContent::find(1);

        $eventsQuery = Event::query()
            ->where('status', Event::STATUS_PUBLISHED);

        // Default: current date and future events
        if (!$request->filled('month')) {
            $today = Carbon::today();
            $eventsQuery->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereNull('to_date')
                       ->whereDate('from_date', '>=', $today);
                })->orWhere(function ($q2) use ($today) {
                    $q2->whereNotNull('to_date')
                       ->whereDate('from_date', '<=', $today)
                       ->whereDate('to_date', '>=', $today);
                });
            });
        } else {
            // Filter month: show any event overlapping the selected month
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $eventsQuery->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where(function ($q2) use ($startOfMonth, $endOfMonth) {
                    // single-day / no end date: if from_date is within month
                    $q2->whereNull('to_date')
                       ->whereDate('from_date', '>=', $startOfMonth)
                       ->whereDate('from_date', '<=', $endOfMonth);
                })->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                    // range events: any overlap with month
                    $q2->whereNotNull('to_date')
                       ->whereDate('from_date', '<=', $endOfMonth)
                       ->whereDate('to_date', '>=', $startOfMonth);
                });
            });
        }

        // Keep the archive page in the same chronological order as the
        // upcoming-events section on the home page.
        $events = $eventsQuery
            ->orderBy('from_date')
            ->orderBy('from_time')
            ->get();

        // If it's an AJAX request, return only the event HTML
        if ($request->ajax()) {
            return view('website.events._partials.list', compact('events', 'now'))->render();
        }

        return view('website.events.index', compact('content', 'events', 'now'));
    }

    /**
     * Event Details Page
     */
    public function show(Event $event)
    {
        // $event = Event::with([
        //                     'ticketTypes',
        //                     'ticketTypes.bulkDiscounts'
        //                 ])->find($id);
        $event->load([
                        'ticketTypes',
                        'ticketTypes.bulkDiscounts'
                    ]);

        if (!$event) {
            abort(404);
        }

        return view('website.events.show', compact('event'));
    }

    /**
     * Event venue Page
     */
    public function event_venue(Event $event)
    {
        
        $event->load([
                    'ticketTypes',
                    'ticketTypes.bulkDiscounts'
                ]);

        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        if (!$event) {
            abort(404);
        }

        return view('website.events.event-venue', compact('event'));
    }

     /**
     * Event ticket simple selection Page
     */
    public function event_tickets(Event $event)
    {
        $event->load([
                    'ticketTypes',
                    'ticketTypes.bulkDiscounts'
                ]);


        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        if (!$event) {
            abort(404);
        }

        return view('website.events.event-tickets', compact('event'));
    }

    /**
     * Event ticket seats selection Page
     */
    public function event_seats(Event $event, $ticket_type_id = null)
    {
        // Use the route parameter or fallback to a query string ?ticket_type_id=
        $ticket_type_id = $ticket_type_id ?? request('ticket_type_id');

        // Fetch the event with the specific ticket type and its discount slabs
       $event->load(['ticketTypes' => function($query) use ($ticket_type_id) {
                    $query->where('id', $ticket_type_id);
                    }, 'ticketTypes.bulkDiscounts']);

        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        
        $selectedTicketType = $event->ticketTypes->first();
                        
        // fetch the ticket type id from the ticket hold
        $ticketType = $event->ticketTypes()
            ->with(['bulkDiscounts' => function ($q) {
                $q->orderBy('min_order_qty', 'asc');
            }])
            ->findOrFail($ticket_type_id);

        // default: no slabs
        $slabs = collect();

        // if bulk discount is enabled
        if ($ticketType->enable_bulk_discount) {
            $slabs = $ticketType->bulkDiscounts->map(function ($bd) {
                return [
                    'minTickets' => (int) $bd->min_order_qty,
                    'offer'      => (float) $bd->discount_percentage,
                ];
            })->values();
        }


        if (!$ticket_type_id) {
            return redirect()->back()->with('error', 'Please select a ticket type first.');
        }

        // 1. Fetch Venue Layout grouped by wing
        $layouts = \App\Models\VenueLayout::orderBy('order_index')->get()->groupBy('wing');

        // 2. Fetch ALL seat assignments to determine what is "available" vs "taken by others"
        $seatAssignments = \DB::table('ticket_type_seats')
        ->join('ticket_types', 'ticket_type_seats.ticket_type_id', '=', 'ticket_types.id')
        ->where('ticket_type_seats.event_id', $event->id)
        ->select(
            'ticket_type_seats.venue_layout_id', 
            'ticket_type_seats.is_booked', // From your DB screenshot
            'ticket_types.id as ticket_type_id', 
            'ticket_types.ticket_type_color', 
            'ticket_types.title'
        )
        ->get()
        ->keyBy('venue_layout_id');

        // 3. Fetch the Held tickets
       // 3.1. Fetch all active holds for this event that haven't expired
        $activeHolds = \DB::table('ticket_holds')
            ->where('event_id', $event->id)
            ->where('ticket_type_id', $ticket_type_id)
            ->where('expires_at', '>', now())
            ->get(['selected_seats']);

        $heldSeatIds = [];


        // 3.2. Iterate through records and decode the JSON arrays
        foreach ($activeHolds as $hold) {
            $seats = $hold->selected_seats;

            // If the data is a string, attempt to decode it
            if (is_string($seats)) {
                $seats = json_decode($seats, true);
            }

            // Handle "Double Encoding" (where the JSON is stored as a stringified string)
            if (is_string($seats)) {
                $seats = json_decode($seats, true);
            }

            if (is_array($seats)) {
                $heldSeatIds = array_merge($heldSeatIds, $seats);
            }
        }

        $heldSeatIds = array_values(array_unique($heldSeatIds));
        //dd($heldSeatIds);

        // 3.3. Remove any duplicates and reset array keys for clean JSON output
        $heldSeatIds = array_values(array_unique($heldSeatIds));

        return view('website.events.event-seat-selection', [
            'event'                => $event,
            'selectedTicketType'   => $selectedTicketType, 
            'targetTicketTypeId'   => $ticket_type_id,
            'lwdata'               => $layouts->get('LW', []),
            'clwdata'              => $layouts->get('CLW', []),
            'crwdata'              => $layouts->get('CRW', []),
            'rwdata'               => $layouts->get('RW', []),
            'seatAssignments'      => $seatAssignments,
            'heldSeatIds'     => $heldSeatIds,
            'slabs'      => $slabs,

        ]);
    }


    /**
     * Initiate checkout ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ create temporary hold
     */
    public function votingEmailVerification(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        if ($request->boolean('reset_booking')) {
            session()->forget($this->votingOtpSessionKey($event));
        }

        $otpSession = session($this->votingOtpSessionKey($event));
        $showOtpForm = $this->hasActiveVotingOtp($event);
        $bookingId = $showOtpForm ? ($otpSession['booking_id'] ?? '') : '';
        $maskedEmail = $showOtpForm ? $this->maskEmail($otpSession['email'] ?? '') : '';
        $resendWaitSeconds = $showOtpForm ? $this->votingResendWaitSeconds($event) : 0;
        $verificationFlow = 'voting';
        $allowEmailChange = false;
        $pageTitle = 'Voting Verification';
        $eventTitle = $event?->title;
        $resetUrl = route('website.events.voting.verify', ['event' => $event->slug, 'reset_booking' => 1]);
        $backUrl = $showOtpForm ? $resetUrl : route('website.events.show', $event->slug);
        $sendOtpUrl = route('website.events.voting.send_otp', $event->slug);
        $verifyOtpUrl = route('website.events.voting.verify_otp', $event->slug);
        $resendOtpUrl = route('website.events.voting.resend_otp', $event->slug);
        $changeEmailUrl = route('website.events.voting.change_email', $event->slug);

        return view('website.events.email-verifaction', compact(
            'event',
            'showOtpForm',
            'bookingId',
            'maskedEmail',
            'resendWaitSeconds',
            'verificationFlow',
            'allowEmailChange',
            'pageTitle',
            'eventTitle',
            'backUrl',
            'sendOtpUrl',
            'verifyOtpUrl',
            'resendOtpUrl',
            'changeEmailUrl',
            'resetUrl'
        ));
    }

    public function sendVotingOtp(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:255'],
        ]);

        $booking = $this->findVotingBooking($event, $validated['booking_id']);
        if (!$booking) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valid paid booking was not found for this event.',
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Valid paid booking was not found for this event.');
        }

        if (blank($booking->email)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No email address is attached to this booking id.',
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'No email address is attached to this booking id.');
        }

        try {
            $this->sendOtpForVotingBooking($event, $booking);
        } catch (\Throwable $e) {
            Log::error('Voting OTP email failed', [
                'event_id' => $event->id,
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send OTP right now. Please try again.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Unable to send OTP right now. Please try again.');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent to the email registered with your booking id.',
                'otp_step' => true,
                'booking_id' => $booking->booking_id,
                'masked_email' => $this->maskEmail($booking->email),
                'verify_url' => route('website.events.voting.verify_otp', $event->slug),
                'resend_url' => route('website.events.voting.resend_otp', $event->slug),
                'change_email_url' => route('website.events.voting.change_email', $event->slug),
                'allow_email_change' => false,
                'reset_url' => route('website.events.voting.verify', ['event' => $event->slug, 'reset_booking' => 1]),
                'resend_after_seconds' => $this->votingResendWaitSeconds($event),
            ]);
        }

        return redirect()
            ->route('website.events.voting.verify', $event->slug)
            ->with('success', 'OTP sent to the email registered with your booking id.');
    }

    public function resendVotingOtp(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $otpSession = session($this->votingOtpSessionKey($event));
        $bookingId = $otpSession['booking_id'] ?? null;
        $booking = $bookingId ? $this->findVotingBooking($event, $bookingId) : null;

        if (!$booking) {
            session()->forget($this->votingOtpSessionKey($event));

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter your booking id again.',
                    'reset' => true,
                ], 422);
            }

            return redirect()
                ->route('website.events.voting.verify', $event->slug)
                ->with('error', 'Please enter your booking id again.');
        }

        $waitSeconds = $this->votingResendWaitSeconds($event);
        if ($waitSeconds > 0) {
            $message = 'Please wait ' . $waitSeconds . ' seconds before resending OTP.';

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'type' => 'warning',
                    'message' => $message,
                    'resend_after_seconds' => $waitSeconds,
                ], 429);
            }

            return back()->with('warning', $message);
        }

        try {
            $this->sendOtpForVotingBooking($event, $booking);
        } catch (\Throwable $e) {
            Log::error('Voting OTP resend failed', [
                'event_id' => $event->id,
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to resend OTP right now. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Unable to resend OTP right now. Please try again.');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'A new OTP has been sent to your registered email.',
                'masked_email' => $this->maskEmail($booking->email),
                'resend_after_seconds' => $this->votingResendWaitSeconds($event),
            ]);
        }

        return back()->with('success', 'A new OTP has been sent to your registered email.');
    }

    public function changeVotingEmail(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $otpSession = session($this->votingOtpSessionKey($event));
        $bookingId = $otpSession['booking_id'] ?? null;
        $booking = $bookingId ? $this->findVotingBooking($event, $bookingId) : null;

        if (!$booking) {
            session()->forget($this->votingOtpSessionKey($event));

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter your booking id again.',
                    'reset' => true,
                ], 422);
            }

            return redirect()
                ->route('website.events.voting.verify', $event->slug)
                ->with('error', 'Please enter your booking id again.');
        }

        $booking->update([
            'email' => $validated['email'],
        ]);
        $booking->refresh();

        try {
            $this->sendOtpForVotingBooking($event, $booking);
        } catch (\Throwable $e) {
            Log::error('Voting email change OTP failed', [
                'event_id' => $event->id,
                'booking_id' => $booking->booking_id,
                'email' => $booking->email,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email updated, but OTP could not be sent. Please try resend OTP.',
                ], 500);
            }

            return back()->with('error', 'Email updated, but OTP could not be sent. Please try resend OTP.');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email updated and a new OTP has been sent.',
                'otp_step' => true,
                'booking_id' => $booking->booking_id,
                'masked_email' => $this->maskEmail($booking->email),
                'verify_url' => route('website.events.voting.verify_otp', $event->slug),
                'resend_url' => route('website.events.voting.resend_otp', $event->slug),
                'change_email_url' => route('website.events.voting.change_email', $event->slug),
                'allow_email_change' => false,
                'reset_url' => route('website.events.voting.verify', ['event' => $event->slug, 'reset_booking' => 1]),
                'resend_after_seconds' => $this->votingResendWaitSeconds($event),
            ]);
        }

        return back()->with('success', 'Email updated and a new OTP has been sent.');
    }

    public function verifyVotingOtp(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);

        $otpSession = session($this->votingOtpSessionKey($event));
        if (!$this->hasActiveVotingOtp($event) || ($otpSession['booking_id'] ?? '') !== $validated['booking_id']) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired. Please request a new OTP.',
                ], 422);
            }

            return redirect()
                ->route('website.events.voting.verify', $event->slug)
                ->with('error', 'OTP expired. Please request a new OTP.');
        }

        if (!Hash::check($validated['otp'], $otpSession['otp_hash'] ?? '')) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please check and try again.',
                ], 422);
            }

            return back()
                ->withInput(['booking_id' => $validated['booking_id']])
                ->with('error', 'Invalid OTP. Please check and try again.');
        }

        $booking = $this->findVotingBooking($event, $validated['booking_id']);
        if (!$booking) {
            session()->forget($this->votingOtpSessionKey($event));

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking could not be verified. Please enter your booking id again.',
                ], 422);
            }

            return redirect()
                ->route('website.events.voting.verify', $event->slug)
                ->with('error', 'Booking could not be verified. Please enter your booking id again.');
        }

        session()->put($this->verifiedVotingSessionKey($event), [
            'ticket_counter_id' => $booking->id,
            'booking_id' => $booking->booking_id,
            'verified_at' => now()->toIso8601String(),
        ]);
        session()->forget($this->votingOtpSessionKey($event));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'redirect' => route('website.events.voting.show', $event->slug),
            ]);
        }

        return redirect()->route('website.events.voting.show', $event->slug);
    }

    public function eventVoting(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $booking = $this->getVerifiedVotingBooking($event);
        if (!$booking) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your booking id before voting.',
                    'redirect' => route('website.home.index'),
                ], 403);
            }

            return redirect()->route('website.home.index');
        }

        $contestents = $event->contestents()
            ->orderBy('name')
            ->get();

        $existingVote = EventContestentVote::with('contestent')
            ->where('event_id', $event->id)
            ->where('booking_id', $booking->booking_id)
            ->first();

        $completionUrl = $this->votingCompletionUrl($event, $booking);

        if (!$this->canShowVotingSection($event)) {
            $this->clearVotingFlowSession($event);

            return redirect($completionUrl);
        }

        return view('website.events.event-voting', compact(
            'event',
            'booking',
            'contestents',
            'existingVote',
            'completionUrl'
        ));
    }

    public function submitVoting(Request $request, Event $event)
    {
        $this->abortIfVotingDisabled($event);

        $booking = $this->getVerifiedVotingBooking($event);
        if (!$booking) {
            return redirect()
                ->route('website.events.voting.verify', $event->slug)
                ->with('error', 'Please verify your booking id before voting.');
        }

        $completionUrl = $this->votingCompletionUrl($event, $booking);

        $validated = $request->validate([
            'contestent_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($event) {
                    if (!EventContestent::where('event_id', $event->id)->where('id', $value)->exists()) {
                        $fail('Please choose a valid contestent.');
                    }
                },
            ],
        ]);

        try {
            DB::transaction(function () use ($event, $booking, $validated) {
                $vote = EventContestentVote::where([
                        'event_id' => $event->id,
                        'booking_id' => $booking->booking_id,
                    ])
                    ->lockForUpdate()
                    ->first();

                if (!$vote) {
                    EventContestentVote::create([
                        'event_id' => $event->id,
                        'booking_id' => $booking->booking_id,
                        'event_contestent_id' => $validated['contestent_id'],
                        'ticket_counter_id' => $booking->id,
                        'name' => $booking->name,
                        'email' => $booking->email,
                    ]);

                    EventContestent::where('id', $validated['contestent_id'])->increment('votes');
                    return;
                }

                $previousContestentId = $vote->event_contestent_id;

                $vote->update([
                    'event_contestent_id' => $validated['contestent_id'],
                    'ticket_counter_id' => $booking->id,
                    'name' => $booking->name,
                    'email' => $booking->email,
                ]);

                if ((int) $previousContestentId !== (int) $validated['contestent_id']) {
                    EventContestent::where('id', $previousContestentId)
                        ->where('votes', '>', 0)
                        ->decrement('votes');

                    EventContestent::where('id', $validated['contestent_id'])->increment('votes');
                }
            });
        } catch (\Throwable $e) {
            Log::error('Voting submit failed', [
                'event_id' => $event->id,
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to submit your vote. Please try again.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Unable to submit your vote. Please try again.');
        }

        $this->clearVotingFlowSession($event);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your vote has been submitted successfully.',
                'redirect' => $completionUrl,
            ]);
        }

        return redirect($completionUrl)
            ->with('success', 'Your vote has been submitted successfully.');
    }

    private function abortIfVotingDisabled(Event $event): void
    {
        if (!$event->enable_voting) {
            abort(404);
        }
    }

    private function canShowVotingSection(Event $event): bool
    {
        return (bool) $event->enable_voting
            && $event->contestents()->count() > 0
            && $event->sell_tickets_till
            && now()->format('Y-m-d H:i') <= $event->sell_tickets_till->format('Y-m-d H:i');
    }

    private function votingOtpSessionKey(Event $event): string
    {
        return 'event_voting_otp_' . $event->id;
    }

    private function verifiedVotingSessionKey(Event $event): string
    {
        return 'event_voting_verified_' . $event->id;
    }

    private function checkoutVotingSessionKey(Event $event): string
    {
        return 'checkout_voting_after_otp_' . $event->id;
    }

    private function votingCompletionUrl(Event $event, TicketCounter $booking): string
    {
        $checkoutVoting = session($this->checkoutVotingSessionKey($event));

        if (is_array($checkoutVoting) && ($checkoutVoting['booking_id'] ?? null) === $booking->booking_id) {
            return $checkoutVoting['redirect'] ?? route('website.events.checkout.success.page', $booking->booking_id);
        }

        return route('website.events.show', $event->slug);
    }

    private function clearVotingFlowSession(Event $event): void
    {
        session()->forget([
            $this->verifiedVotingSessionKey($event),
            $this->checkoutVotingSessionKey($event),
        ]);
    }

    private function hasActiveVotingOtp(Event $event): bool
    {
        $otpSession = session($this->votingOtpSessionKey($event));

        return is_array($otpSession)
            && !empty($otpSession['expires_at'])
            && now()->lt(Carbon::parse($otpSession['expires_at']));
    }

    private function votingResendWaitSeconds(Event $event): int
    {
        $otpSession = session($this->votingOtpSessionKey($event));
        $resendAvailableAt = $otpSession['resend_available_at'] ?? null;

        if (!$resendAvailableAt) {
            return 0;
        }

        $availableAt = Carbon::parse($resendAvailableAt);

        return $availableAt->isFuture() ? (int) now()->diffInSeconds($availableAt) : 0;
    }

    private function findVotingBooking(Event $event, string $bookingId): ?TicketCounter
    {
        return TicketCounter::where('event_id', $event->id)
            ->where('booking_id', trim($bookingId))
            ->where('payment_status', 'paid')
            ->first();
    }

    private function getVerifiedVotingBooking(Event $event): ?TicketCounter
    {
        $verified = session($this->verifiedVotingSessionKey($event));
        $bookingId = is_array($verified) ? ($verified['booking_id'] ?? null) : null;

        if (!$bookingId) {
            return null;
        }

        $booking = $this->findVotingBooking($event, $bookingId);
        if (!$booking) {
            session()->forget($this->verifiedVotingSessionKey($event));
        }

        return $booking;
    }

    private function sendOtpForVotingBooking(Event $event, TicketCounter $booking): void
    {
        $otp = (string) random_int(100000, 999999);

        session()->put($this->votingOtpSessionKey($event), [
            'booking_id' => $booking->booking_id,
            'email' => $booking->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'resend_available_at' => now()->addSeconds(60)->toIso8601String(),
        ]);

        Mail::send('emails.voting_otp', [
            'event' => $event,
            'booking' => $booking,
            'otp' => $otp,
        ], function ($message) use ($event, $booking) {
            $message->to($booking->email)
                ->subject('Your Voting OTP - ' . $event->title);
        });
    }

    private function maskEmail(?string $email): string
    {
        if (blank($email) || !str_contains($email, '@')) {
            return 'your registered email';
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = substr($name, 0, 2);

        return $visible . str_repeat('*', max(strlen($name) - 2, 3)) . '@' . $domain;
    }

    public function initiateCheckout(Request $request)
    {
        $request->validate([
            'event_id'       => 'required|exists:events,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity'       => 'nullable|integer|min:1',
            'selected_seats' => 'nullable|array', 
        ]);

        $event = Event::findOrFail($request->event_id);
        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        $ticketType = $event->ticketTypes()->findOrFail($request->ticket_type_id);
        
        // Determine quantity: if seats are provided, count them; otherwise use quantity input
        $selectedSeats = collect($request->input('selected_seats', []))
            ->filter(fn ($seatId) => filled($seatId))
            ->map(fn ($seatId) => (int) $seatId)
            ->values()
            ->all();
        $requiresSeatSelection = DB::table('ticket_type_seats')
            ->where('event_id', $event->id)
            ->where('ticket_type_id', $ticketType->id)
            ->exists();

        if ($requiresSeatSelection && empty($selectedSeats)) {
            return back()
                ->withInput()
                ->with('error', 'Please select at least one seat to continue.');
        }

        $quantity = !empty($selectedSeats) ? count($selectedSeats) : $request->input('quantity', 1);
        
        // 1. Availability Check
        if ($ticketType->available_tickets < $quantity) {
            return back()->with('error', 'Not enough tickets available');
        }

        // 2. Seating-Specific Validation (Security Check)
        if (!empty($selectedSeats)) {
            // Ensure these seats actually belong to this ticket type and aren't already booked
            $validSeats = \DB::table('ticket_type_seats')
                ->where('ticket_type_id', $request->ticket_type_id)
                ->whereIn('venue_layout_id', $selectedSeats)
                ->count();

            if ($validSeats !== count($selectedSeats)) {
                return back()->with('error', 'Some selected seats are invalid or assigned to another category.');
            }

            // Check if any of these seats are currently held by others
            $alreadyHeld = TicketHold::where('event_id', $request->event_id)
                ->where('expires_at', '>', now())
                ->whereJsonContains('selected_seats', $selectedSeats) // Logic depends on your TicketHold schema
                ->exists();

            if ($alreadyHeld) {
                return back()->with('error', 'One or more of your selected seats were just taken. Please refresh.');
            }
        }

        // 3. Calculate Price & Discounts (Offer Bar Logic)
        // We calculate this here so the checkout page is ready with the final amount
        $unitPrice = $ticketType->ticket_price;
        $discountSlab = $ticketType->bulkDiscounts
            ->where('min_order_qty', '<=', $quantity)
            ->sortByDesc('min_order_qty')
            ->first();

        $discountPercent = $discountSlab ? $discountSlab->discount_percentage : 0;
        $totalAmount = ($unitPrice * $quantity) * (1 - ($discountPercent / 100));
        
        // 4. Generate Unique Token
        $token = Str::upper(Str::random(6));
        while (TicketHold::where('token', $token)->exists()) {
            $token = Str::upper(Str::random(6));
        }

        // 5. Create Hold (5 Minutes)
        TicketHold::create([
            'event_id'       => $request->event_id,
            'ticket_type_id' => $request->ticket_type_id,
            'quantity'       => $quantity,
            'selected_seats' => !empty($selectedSeats) ? json_encode($selectedSeats) : null, // Hold the IDs
            'total_amount'   => $totalAmount,
            'token'          => $token,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'expires_at'     => now()->copy()->addMinutes(5),
        ]);

        return redirect()->route('website.events.checkout', $token);
    }


    /**
     * Remove selected seat and update ticket hold
     */

    public function removeSeatFromHold(Request $request)
    {
        try {
            $hold = TicketHold::where('token', $request->token)
                ->where('expires_at', '>', now())
                ->first();

            if (!$hold) {
                return response()->json(['success' => false, 'message' => 'Session expired.']);
            }

            $currentSeats = $hold->selected_seats ?? [];
            
            // Ensure $currentSeats is actually an array before filtering
            if (!is_array($currentSeats)) {
                $currentSeats = json_decode($currentSeats, true) ?? [];
            }

            $updatedSeats = array_values(array_filter($currentSeats, function($id) use ($request) {
                return $id != $request->seat_id;
            }));

            if (empty($updatedSeats)) {
                return response()->json(['success' => false, 'message' => 'Minimum 1 seat required.']);
            }

            // Re-calculate the bill to keep the database total in sync
            $ticketType = $hold->ticketType; // Assumes relationship 'ticketType' exists
            $newQty = count($updatedSeats);
            
            // Apply Slab Logic
            $discountPercent = 0;
            $slab = $ticketType->bulkDiscounts()
                ->where('min_order_qty', '<=', $newQty)
                ->orderBy('min_order_qty', 'desc')
                ->first();
                
            if ($slab) $discountPercent = $slab->discount_percentage;
            $newTotal = ($ticketType->ticket_price * $newQty) * (1 - ($discountPercent / 100));

            $hold->update([
                'selected_seats' => $updatedSeats,
                'quantity' => $newQty,
                'total_amount' => $newTotal
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            // This will log the actual error in storage/logs/laravel.log
            \Log::error("Seat Removal Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Show checkout page with valid hold
     */
    public function checkout($token)
    {
        // check the ticket checkout time if it expires throw error and redirect to the index
        $hold = TicketHold::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$hold) {
            return redirect()->route('website.events.index')
                ->with('error', 'Your ticket hold has expired. Please try again.');
        }

        // fetch event detail from the ticket hold
        $event = Event::findOrFail($hold->event_id);

        // fetch the ticket type id from the ticket hold
        $ticketType = $event->ticketTypes()
            ->with(['bulkDiscounts' => function ($q) {
                $q->orderBy('min_order_qty', 'asc');
            }])
            ->findOrFail($hold->ticket_type_id);

        
        // Fetch Selected Seats Details if applicable
        $selectedSeatLabels = [];
        if ($hold->selected_seats) {
            $seatIds = is_array($hold->selected_seats) ? $hold->selected_seats : json_decode($hold->selected_seats, true);
            $selectedSeatLabels = \App\Models\VenueLayout::whereIn('id', $seatIds)
                ->select('id', 'wing', 'row', 'seat_number')
                ->get()
                ->map(fn($seat) => [
                    'id'    => $seat->id, 
                    'label' => "{$seat->wing}-{$seat->row}{$seat->seat_number}"
                ])->toArray();
        }

    
        // default: no slabs
        $slabs = collect();

        // if bulk discount is enabled
        if ($ticketType->enable_bulk_discount) {
            $slabs = $ticketType->bulkDiscounts->map(function ($bd) {
                return [
                    'minTickets' => (int) $bd->min_order_qty,
                    'offer'      => (float) $bd->discount_percentage,
                ];
            })->values();
        }

        // Count how many parking tickets have already been issued for this event
        $bookedSlots = \App\Models\TicketParking::whereHas('booking', function($query) use ($event) {
            $query->where('event_id', $event->id);
        })->count();

        // Calculate remaining slots
        $remainingSlots = max(0, $event->car_parking_slots - $bookedSlots);


        $checkout = [
            'event_id'       => $hold->event_id,
            'ticket_type_id' => $hold->ticket_type_id,
            'quantity'       => $hold->quantity,
            'token'          => $hold->token,
            'expires_at'     => $hold->expires_at,
            'selected_seats' => $selectedSeatLabels,
        ];

        $countries = Country::orderBy('name')->get(['id', 'name']);

        return view(
            'website.events.checkout',
            compact('event', 'ticketType', 'checkout', 'slabs', 'remainingSlots', 'countries')
        );
    }

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    // Stripe
    public function createStripeCheckout(Request $request)
    {
    $request->validate([
        'token' => 'required|exists:ticket_holds,token',
        'name'  => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone_prefix' => 'required|string|max:20',
        'phone' => ['required', 'regex:/^[0-9]{5,12}$/'],
        'country_id' => 'required|exists:countries,id',
        'state_id' => 'required|exists:states,id',
        'quantity' => 'required|integer|min:1',
        'coupon_code' => 'nullable|string',
        'parking_slots' => 'nullable|integer|min:0',
        'car_details' => 'nullable|array',
    ], [
        'phone.required' => 'Phone number is required.',
        'phone.regex' => 'Phone number must be 5 to 12 digits.',
    ]);

    $stateBelongsToCountry = State::where('id', $request->state_id)
        ->where('country_id', $request->country_id)
        ->exists();

    if (!$stateBelongsToCountry) {
        return response()->json(['message' => 'Selected state does not belong to the selected country.'], 422);
    }

    $hold = TicketHold::where('token', $request->token)->first();

    if (!$hold || $hold->expires_at <= now()) {
        return response()->json(['message' => 'Checkout session expired. Please start again.'], 410);
    }

    $ticketType = TicketType::findOrFail($hold->ticket_type_id);
    $event = Event::findOrFail($ticketType->event_id);

    // --- INITIALIZE VARIABLES ---
    $parkingPricePerSlot = (float) $event->car_slot_price;
    $parkingTotal = 0; 
    $discountPercentage = 0;
    $appliedCouponCode = null;

    // 1. Calculate Ticket Subtotal
    $baseAmount = (float) $ticketType->ticket_price * (int) $request->quantity;

    // 2. Calculate Parking (Logic remains same, but safer)
    $parkingSlots = (int) $request->parking_slots;
    $alreadyBooked = \App\Models\TicketParking::whereHas('booking', function($q) use ($event) {
        $q->where('event_id', $event->id);
    })->count();

    $availableSlots = (int) $event->car_parking_slots - $alreadyBooked;

    if (!$event->enable_car_parking && $parkingSlots > 0) {
        return response()->json(['message' => 'Parking is not available for this event.'], 422);
    }

    if ($event->enable_car_parking && $parkingSlots > 0) {
        if ($parkingSlots <= $availableSlots) {
            $parkingTotal = $parkingSlots * $parkingPricePerSlot;
        } else {
            return response()->json(['message' => "Only $availableSlots parking slots remaining."], 422);
        }
    }

    // 3. Handle Discounts (Bulk vs Coupon)
    $bulk = null;
    if ($ticketType->enable_bulk_discount) {
        $bulk = $ticketType->bulkDiscounts()
            ->where('min_order_qty', '<=', (int) $hold->quantity)
            ->orderByDesc('min_order_qty')
            ->first();
    }

    if ($bulk) {
        $discountPercentage = (float) $bulk->discount_percentage;
    } elseif ($request->coupon_code) {
        $coupon = \App\Models\DiscountCoupon::where('coupon_code', $request->coupon_code)
            ->where('event_id', $hold->event_id)
            ->first();

        if ($coupon && !empty($coupon->ticket_type_ids) && in_array($hold->ticket_type_id, $coupon->ticket_type_ids)) {
            $discountPercentage = (float) $coupon->discount;
            $appliedCouponCode = $coupon->coupon_code;
        }
    }

    // 4. Perform Final Math
    $discountAmount = ($baseAmount * $discountPercentage) / 100;
    $taxableBasis = max(0, round(($baseAmount - $discountAmount) + $parkingTotal, 2));

    // --- New: Tax and Extra Charges Calculation ---
    $taxAmount = 0;
    if ($ticketType->enable_tax && $ticketType->tax_value > 0) {
        // Calculate tax based on the percentage defined in settings
        $taxAmount = ($taxableBasis * $ticketType->tax_value) / 100;
    }

    $extraChargesAmount = 0;
    if ($ticketType->enable_extra_charges && $ticketType->extra_charges_value > 0) {
        // Calculate extra charges based on the percentage defined in settings
        $extraChargesAmount = ($taxableBasis * $ticketType->extra_charges_value) / 100;
    }

    // This is the final amount to be sent to the Stripe 'amount' field
    $finalAmount = round($taxableBasis + $taxAmount + $extraChargesAmount, 2);
   
    Log::info('Checkout Calculation Debug:', [
    'ticket_price'      => $ticketType->ticket_price,
    'quantity'          => $hold->quantity,
    'base_amount'       => $baseAmount,
    'parking_price'     => $parkingPricePerSlot,
    'coupon'            => $appliedCouponCode,
    'discount_percent'  => $discountPercentage,
    'discount_amount'   => $discountAmount,
    'parking_total'     => $parkingTotal,
    'final_amount'      => $finalAmount,
]);

    // Keep enough information on the temporary hold to show an abandoned
    // checkout in the admin Failed Tickets screen after the hold expires.
    $hold->update([
        'name' => $request->name,
        'email' => $request->email,
        'phone_prefix' => $request->phone_prefix,
        'mobile_number' => $request->phone,
        'country_id' => $request->country_id,
        'state_id' => $request->state_id,
        'coupon_code' => $appliedCouponCode,
        'total_amount' => $finalAmount,
        'checkout_started_at' => now(),
    ]);

    $currencyCode = Currency::codeForEvent($event);

    if ($finalAmount <= 0) {
        try {
            $booking = DB::transaction(function () use ($request) {
                $lockedHold = TicketHold::where('token', $request->token)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (!$lockedHold) {
                    throw new \RuntimeException('Checkout session expired. Please start again.');
                }

                return $this->completeCheckoutBooking($lockedHold, [
                    'quantity' => (int) $request->quantity,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_prefix' => $request->phone_prefix,
                    'phone' => $request->phone,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'coupon_code' => $appliedCouponCode,
                    'parking_slots' => (int) $request->parking_slots,
                    'car_details' => $request->car_details ?? [],
                    'currency' => Currency::codeForEvent($lockedHold->event),
                    'payment_status' => 'paid',
                    'payment_method' => 'free',
                    'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                ]);
            });

            $this->rememberCheckoutAccess($booking);

            try {
                $this->sendCheckoutOtpForBooking($booking);
            } catch (\Throwable $e) {
                $booking->forceFill(['checkout_otp_resend_available_at' => null])->save();

                Log::error('Checkout OTP email failed after free booking', [
                    'booking_id' => $booking?->booking_id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'url' => route('website.events.checkout.verify', $booking->booking_id),
                'message' => 'Booking created successfully. Please verify your email.',
            ]);
        } catch (\RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'expired') ? 410 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\Throwable $e) {
            Log::error('Free checkout booking failed', [
                'token' => $request->token,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to complete booking right now. Please try again.'], 500);
        }
    }

    // ISO currency code for Stripe checkout.
    Stripe::setApiKey(config('services.stripe.secret'));

    $paymentTransaction = PaymentTransaction::create([
        'event_id' => $hold->event_id,
        'ticket_type_id' => $hold->ticket_type_id,
        'hold_token' => $hold->token,
        'gateway' => 'stripe',
        'status' => PaymentTransaction::STATUS_INITIATED,
        'currency' => strtoupper($currencyCode),
        'amount' => $finalAmount,
        'quantity' => (int) $request->quantity,
        'selected_seats' => $hold->selected_seats,
        'parking_slots' => (int) $request->parking_slots,
        'car_details' => $request->car_details ?? [],
        'coupon_code' => $appliedCouponCode,
        'customer_name' => $request->name,
        'customer_email' => $request->email,
        'phone_prefix' => $request->phone_prefix,
        'mobile_number' => $request->phone,
        'country_id' => $request->country_id,
        'state_id' => $request->state_id,
        'initiated_at' => now(),
    ]);

    try {
    $session = Session::create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => $currencyCode,
                'unit_amount' => (int) ($finalAmount * 100),
                'product_data' => ['name' => $ticketType->title . ($request->parking_slots > 0 ? " + Parking" : "")],
            ],
            'quantity' => 1,
        ]],
        'currency' => $currencyCode,
        'success_url' => route('website.events.checkout.success', ['payment_transaction_id' => $paymentTransaction->id]) . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => route('website.events.checkout.cancel', ['payment_transaction_id' => $paymentTransaction->id]),
        'metadata' => [
            'payment_transaction_id' => (string) $paymentTransaction->id,
            'name'        => $request->name,
            'email'       => $request->email,
            'phone_prefix' => $request->phone_prefix,
            'phone'       => $request->phone,
            'country_id'  => $request->country_id,
            'state_id'    => $request->state_id,
            'quantity'    => $request->quantity,
            'hold_token'  => $hold->token,
            'selected_seats' => $hold->selected_seats ? json_encode($hold->selected_seats) : null,
            'coupon_code' => $appliedCouponCode,
            'parking_slots' => $request->parking_slots,
            'car_details'   => json_encode($request->car_details),
            'event_id' => $hold->event_id,
            'ticket_type_id' => $hold->ticket_type_id,
            'checksum'    => hash_hmac('sha256', $hold->token, config('app.key')),
        ],
    ]);

    $paymentTransaction->update([
        'gateway_session_id' => $session->id,
        'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
        'transaction_id' => $this->stripeTransactionId($session),
        'gateway_payment_status' => $session->payment_status ?? null,
        'raw_payload' => $this->stripePayload($session),
    ]);
    } catch (\Throwable $e) {
        $paymentTransaction->update([
            'status' => PaymentTransaction::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $e->getMessage(),
        ]);

        Log::error('Stripe checkout session creation failed', [
            'payment_transaction_id' => $paymentTransaction->id,
            'error' => $e->getMessage(),
        ]);

        return response()->json(['message' => 'Unable to start payment right now. Please try again.'], 500);
    }

    return response()->json(['url' => $session->url]);
    }

    public function checkoutEmailVerification(string $booking_id)
    {
        $booking = $this->findCheckoutBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return redirect()->route('website.home.index');
        }

        if ($booking->email_verified_at && $booking->ticket_email_sent_at) {
            return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
        }

        $event = Event::find($booking->event_id);
        $maskedEmail = $this->maskEmail($booking->email);
        $resendWaitSeconds = $this->checkoutOtpWaitSeconds($booking);
        $showOtpForm = true;
        $bookingId = $booking->booking_id;
        $verificationFlow = 'checkout';
        $allowEmailChange = true;
        $pageTitle = 'Verify Email';
        $eventTitle = $event?->title ?? 'Event';
        $backUrl = $event?->slug ? route('website.events.show', $event->slug) : route('website.events.index');
        $sendOtpUrl = null;
        $verifyOtpUrl = route('website.events.checkout.verify_otp', $booking->booking_id);
        $resendOtpUrl = route('website.events.checkout.resend_otp', $booking->booking_id);
        $changeEmailUrl = route('website.events.checkout.change_email', $booking->booking_id);
        $resetUrl = null;

        return view('website.events.email-verifaction', compact(
            'booking',
            'event',
            'showOtpForm',
            'bookingId',
            'maskedEmail',
            'resendWaitSeconds',
            'verificationFlow',
            'allowEmailChange',
            'pageTitle',
            'eventTitle',
            'backUrl',
            'sendOtpUrl',
            'verifyOtpUrl',
            'resendOtpUrl',
            'changeEmailUrl',
            'resetUrl'
        ));
    }

    public function resendCheckoutOtp(Request $request, string $booking_id)
    {
        $booking = $this->findCheckoutBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->email_verified_at && $booking->ticket_email_sent_at) {
            return $this->checkoutOtpJsonOrRedirect($request, [
                'success' => true,
                'message' => 'Your email is already verified.',
                'redirect' => route('website.events.checkout.success.page', $booking->booking_id),
            ], route('website.events.checkout.success.page', $booking->booking_id));
        }

        $waitSeconds = $this->checkoutOtpWaitSeconds($booking);
        if ($waitSeconds > 0) {
            $message = 'Please wait ' . $waitSeconds . ' seconds before resending OTP.';

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'type' => 'warning',
                    'message' => $message,
                    'resend_after_seconds' => $waitSeconds,
                ], 429);
            }

            return back()->with('warning', $message);
        }

        try {
            $this->sendCheckoutOtpForBooking($booking);
            $booking->refresh();
        } catch (\Throwable $e) {
            $booking->forceFill(['checkout_otp_resend_available_at' => null])->save();

            Log::error('Checkout OTP resend failed', [
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to resend OTP right now. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Unable to resend OTP right now. Please try again.');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'A new OTP has been sent to your email.',
                'masked_email' => $this->maskEmail($booking->email),
                'resend_after_seconds' => $this->checkoutOtpWaitSeconds($booking),
            ]);
        }

        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function changeCheckoutEmail(Request $request, string $booking_id)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $booking = $this->findCheckoutBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->email_verified_at && $booking->ticket_email_sent_at) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'type' => 'warning',
                    'message' => 'This booking has already been verified.',
                    'redirect' => route('website.events.checkout.success.page', $booking->booking_id),
                ], 409);
            }

            return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
        }

        $booking->update([
            'email' => $validated['email'],
            'email_verified_at' => null,
            'ticket_email_sent_at' => null,
        ]);
        $booking->refresh();

        try {
            $this->sendCheckoutOtpForBooking($booking);
            $booking->refresh();
        } catch (\Throwable $e) {
            $booking->forceFill(['checkout_otp_resend_available_at' => null])->save();

            Log::error('Checkout email change OTP failed', [
                'booking_id' => $booking->booking_id,
                'email' => $booking->email,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email updated, but OTP could not be sent. Please try resend OTP.',
                ], 500);
            }

            return back()->with('error', 'Email updated, but OTP could not be sent. Please try resend OTP.');
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email updated and a new OTP has been sent.',
                'masked_email' => $this->maskEmail($booking->email),
                'resend_after_seconds' => $this->checkoutOtpWaitSeconds($booking),
            ]);
        }

        return back()->with('success', 'Email updated and a new OTP has been sent.');
    }

    public function verifyCheckoutOtp(Request $request, string $booking_id)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $booking = $this->findCheckoutBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->email_verified_at && $booking->ticket_email_sent_at) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your email is already verified.',
                    'redirect' => route('website.events.checkout.success.page', $booking->booking_id),
                ]);
            }

            return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
        }

        if (!$booking->checkout_otp_hash || !$booking->checkout_otp_expires_at || now()->gt($booking->checkout_otp_expires_at)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired. Please resend OTP.',
                ], 422);
            }

            return back()->with('error', 'OTP expired. Please resend OTP.');
        }

        if (!Hash::check($validated['otp'], $booking->checkout_otp_hash)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please check and try again.',
                ], 422);
            }

            return back()->withInput()->with('error', 'Invalid OTP. Please check and try again.');
        }

        try {
            if (!$booking->ticket_email_sent_at) {
                app(TicketPdfService::class)->sendTicketEmail($booking);
            }

            $booking->update([
                'email_verified_at' => now(),
                'ticket_email_sent_at' => now(),
                'booking_status' => TicketCounter::STATUS_CONFIRMED,
                'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                'checkout_otp_hash' => null,
                'checkout_otp_expires_at' => null,
                'checkout_otp_resend_available_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Checkout ticket email failed after OTP verification', [
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP verified, but ticket email could not be sent. Please try again.',
                ], 500);
            }

            return back()->with('error', 'OTP verified, but ticket email could not be sent. Please try again.');
        }

        $event = Event::find($booking->event_id);
        $redirectAfterOtp = route('website.events.checkout.success.page', $booking->booking_id);

        if ($event && $this->canShowVotingSection($event)) {
            session()->put($this->verifiedVotingSessionKey($event), [
                'ticket_counter_id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'verified_at' => now()->toIso8601String(),
            ]);

            session()->put($this->checkoutVotingSessionKey($event), [
                'booking_id' => $booking->booking_id,
                'redirect' => route('website.events.checkout.success.page', $booking->booking_id),
            ]);

            $redirectAfterOtp = route('website.events.voting.show', $event->slug);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully.',
                'redirect' => $redirectAfterOtp,
            ]);
        }

        return redirect($redirectAfterOtp);
    }

    private function findCheckoutBooking(string $bookingId): TicketCounter
    {
        return TicketCounter::where('booking_id', trim($bookingId))
            ->where('payment_status', 'paid')
            ->firstOrFail();
    }

    private function checkoutOtpWaitSeconds(TicketCounter $booking): int
    {
        if (!$booking->checkout_otp_resend_available_at) {
            return 0;
        }

        return $booking->checkout_otp_resend_available_at->isFuture()
            ? (int) now()->diffInSeconds($booking->checkout_otp_resend_available_at)
            : 0;
    }

    private function sendCheckoutOtpForBooking(TicketCounter $booking): void
    {
        $otp = (string) random_int(100000, 999999);
        $event = Event::find($booking->event_id);

        $booking->update([
            'checkout_otp_hash' => Hash::make($otp),
            'checkout_otp_expires_at' => now()->addMinutes(15),
            'checkout_otp_resend_available_at' => now()->addSeconds(60),
        ]);

        Mail::send('emails.checkout_otp', [
            'event' => $event,
            'booking' => $booking,
            'otp' => $otp,
        ], function ($message) use ($event, $booking) {
            $eventTitle = $event?->title ?? 'Event';

            $message->to($booking->email)
                ->subject('Your Ticket Verification OTP - ' . $eventTitle);
        });
    }

    private function checkoutOtpJsonOrRedirect(Request $request, array $payload, string $redirect)
    {
        if ($request->ajax()) {
            return response()->json($payload);
        }

        return redirect($redirect);
    }

    private function checkoutAccessSessionKey(TicketCounter|string $booking): string
    {
        $bookingId = $booking instanceof TicketCounter ? $booking->booking_id : $booking;

        return 'checkout_booking_access_' . $bookingId;
    }

    private function checkoutSuccessViewedSessionKey(TicketCounter|string $booking): string
    {
        $bookingId = $booking instanceof TicketCounter ? $booking->booking_id : $booking;

        return 'checkout_success_viewed_' . $bookingId;
    }

    private function rememberCheckoutAccess(TicketCounter $booking): void
    {
        session()->put($this->checkoutAccessSessionKey($booking), [
            'booking_id' => $booking->booking_id,
            'expires_at' => now()->addHours(2)->toIso8601String(),
        ]);
    }

    private function hasCheckoutAccess(TicketCounter $booking): bool
    {
        $access = session($this->checkoutAccessSessionKey($booking));

        if (!is_array($access) || ($access['booking_id'] ?? null) !== $booking->booking_id) {
            return false;
        }

        if (empty($access['expires_at']) || now()->gt(Carbon::parse($access['expires_at']))) {
            session()->forget($this->checkoutAccessSessionKey($booking));
            return false;
        }

        return true;
    }

    private function checkoutAccessDenied(Request $request)
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'This link has expired. Please start again.',
                'redirect' => route('website.home.index'),
            ], 403);
        }

        return redirect()->route('website.home.index');
    }

    private function completeCheckoutBooking(TicketHold $hold, array $data): TicketCounter
    {
        $quantity = max(1, (int) ($data['quantity'] ?? $hold->quantity));
        $ticketType = TicketType::findOrFail($hold->ticket_type_id);
        $event = Event::findOrFail($ticketType->event_id);
        $selectedSeats = $data['selected_seats'] ?? $hold->selected_seats ?? [];

        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (!is_array($selectedSeats)) {
            $selectedSeats = [];
        }

        $carNumbers = $data['car_details'] ?? [];
        if (is_string($carNumbers)) {
            $carNumbers = json_decode($carNumbers, true) ?? [];
        }
        if (!is_array($carNumbers)) {
            $carNumbers = [];
        }

        $carNumbers = array_values(array_filter(array_map(function ($number) {
            return trim((string) $number);
        }, $carNumbers)));

        $parkingSlots = max(0, (int) ($data['parking_slots'] ?? 0));
        $baseAmount = (float) $ticketType->ticket_price * $quantity;
        $bulkDiscountApplied = false;
        $couponApplied = false;
        $couponCode = null;
        $finalDiscountPercent = 0;

        if ($ticketType->enable_bulk_discount) {
            $bulk = $ticketType->bulkDiscounts()
                ->where('min_order_qty', '<=', (int) $hold->quantity)
                ->orderByDesc('min_order_qty')
                ->first();

            if ($bulk) {
                $bulkDiscountApplied = true;
                $finalDiscountPercent = (float) $bulk->discount_percentage;
            }
        }

        if (!$bulkDiscountApplied && !empty($data['coupon_code'])) {
            $coupon = \App\Models\DiscountCoupon::where('coupon_code', $data['coupon_code'])
                ->where('event_id', $hold->event_id)
                ->first();

            if ($coupon && !empty($coupon->ticket_type_ids) && in_array($hold->ticket_type_id, $coupon->ticket_type_ids) && $coupon->canBeUsed()) {
                $couponApplied = true;
                $couponCode = $coupon->coupon_code;
                $finalDiscountPercent = (float) $coupon->discount;
                $coupon->incrementUsage();
            }
        }

        $couponAmount = ($baseAmount * $finalDiscountPercent) / 100;
        $ticketTotal = max(0, round($baseAmount - $couponAmount, 2));
        $parkingTotal = 0;

        if ($parkingSlots > 0) {
            $alreadyBooked = \App\Models\TicketParking::whereHas('booking', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })->count();

            $availableSlots = max(0, (int) $event->car_parking_slots - $alreadyBooked);

            if (!$event->enable_car_parking || $parkingSlots > $availableSlots) {
                throw new \RuntimeException("Only $availableSlots parking slots remaining.");
            }

            $parkingTotal = $parkingSlots * (float) ($event->car_slot_price ?? 0);
        }

        $taxableBasis = $ticketTotal + $parkingTotal;
        $taxAmount = 0;
        if ($ticketType->enable_tax && $ticketType->tax_value > 0) {
            $taxAmount = ($taxableBasis * $ticketType->tax_value) / 100;
        }

        $extraChargesAmount = 0;
        if ($ticketType->enable_extra_charges && $ticketType->extra_charges_value > 0) {
            $extraChargesAmount = ($taxableBasis * $ticketType->extra_charges_value) / 100;
        }

        $grandTotal = round($taxableBasis + $taxAmount + $extraChargesAmount, 2);

        $booking = TicketCounter::create([
            'event_id'              => $hold->event_id,
            'ticket_type_id'        => $hold->ticket_type_id,
            'qty'                   => $quantity,
            'bulk_discount_applied' => $bulkDiscountApplied,
            'selected_seats'        => json_encode($selectedSeats),
            'coupon_applied'        => $couponApplied,
            'coupon_code'           => $couponCode,
            'coupon_amount'         => (float) preg_replace('/[^0-9.]/', '', $couponAmount),
            'coupon_percentage'     => (float) $finalDiscountPercent,
            'total_amount'          => (float) preg_replace('/[^0-9.]/', '', $grandTotal),
            'name'                  => $data['name'] ?? null,
            'email'                 => $data['email'] ?? null,
            'phone_prefix'          => $data['phone_prefix'] ?? null,
            'mobile_number'         => $data['phone'] ?? null,
            'country_id'            => $data['country_id'] ?? null,
            'state_id'              => $data['state_id'] ?? null,
            'payment_status'        => $data['payment_status'] ?? 'paid',
            'booking_status'        => TicketCounter::STATUS_PENDING_VERIFICATION,
            'refund_status'         => $data['refund_status'] ?? TicketCounter::REFUND_PENDING,
            'payment_method'        => $data['payment_method'] ?? 'stripe',
            'payment_transaction_id' => $data['payment_transaction_id'] ?? null,
            'transaction_id'         => $data['transaction_id'] ?? null,
            'gateway_session_id'     => $data['gateway_session_id'] ?? null,
            'gateway_payment_intent_id' => $data['gateway_payment_intent_id'] ?? null,
            'payment_initiated_at'   => $data['payment_initiated_at'] ?? null,
            'payment_completed_at'   => $data['payment_completed_at'] ?? null,
            'payment_failed_at'      => $data['payment_failed_at'] ?? null,
            'payment_cancelled_at'   => $data['payment_cancelled_at'] ?? null,
            'payment_failure_reason' => $data['payment_failure_reason'] ?? null,
        ]);

        $paymentTransaction = null;

        if (!empty($data['payment_transaction_id'])) {
            $paymentTransaction = PaymentTransaction::find($data['payment_transaction_id']);

            $paymentTransaction?->update([
                'ticket_counter_id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'transaction_id' => $data['transaction_id'] ?? $paymentTransaction->transaction_id,
                'gateway_session_id' => $data['gateway_session_id'] ?? $paymentTransaction->gateway_session_id,
                'gateway_payment_intent_id' => $data['gateway_payment_intent_id'] ?? $paymentTransaction->gateway_payment_intent_id,
            ]);
        } elseif (($data['payment_method'] ?? null) === 'free') {
            $paymentTransaction = PaymentTransaction::create([
                'ticket_counter_id' => $booking->id,
                'event_id' => $hold->event_id,
                'ticket_type_id' => $hold->ticket_type_id,
                'booking_id' => $booking->booking_id,
                'hold_token' => $hold->token,
                'gateway' => 'free',
                'transaction_id' => 'FREE-' . $booking->booking_id,
                'status' => PaymentTransaction::STATUS_COMPLETED,
                'gateway_payment_status' => 'paid',
                'currency' => strtoupper($data['currency'] ?? Currency::codeForEvent($hold->event)),
                'amount' => 0,
                'quantity' => $quantity,
                'selected_seats' => $selectedSeats,
                'parking_slots' => (int) ($data['parking_slots'] ?? 0),
                'car_details' => $carNumbers,
                'coupon_code' => $couponCode,
                'customer_name' => $data['name'] ?? null,
                'customer_email' => $data['email'] ?? null,
                'phone_prefix' => $data['phone_prefix'] ?? null,
                'mobile_number' => $data['phone'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'initiated_at' => now(),
                'completed_at' => now(),
            ]);

            $booking->update([
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $paymentTransaction->transaction_id,
                'payment_initiated_at' => $paymentTransaction->initiated_at,
                'payment_completed_at' => $paymentTransaction->completed_at,
            ]);
        }

        for ($i = 0; $i < $quantity; $i++) {
            $seatId = $selectedSeats[$i] ?? null;
            $ticketNumber = $seatId
                ? $booking->booking_id . "-S" . $seatId
                : $booking->booking_id . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4));

            \App\Models\BookedTicket::create([
                'ticket_counter_id' => $booking->id,
                'booking_id'        => $booking->booking_id,
                'ticket_number'     => $ticketNumber,
                'venue_layout_id'   => $seatId,
                'status'            => 'Not Scanned',
            ]);
        }

        if (!empty($selectedSeats)) {
            DB::table('ticket_type_seats')
                ->where('ticket_type_id', $hold->ticket_type_id)
                ->whereIn('venue_layout_id', $selectedSeats)
                ->update([
                    'is_booked' => true,
                    'ticket_counter_id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'booked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        foreach (array_slice($carNumbers, 0, $parkingSlots) as $number) {
            \App\Models\TicketParking::create([
                'ticket_counter_id' => $booking->id,
                'ticket_type_id' => $hold->ticket_type_id,
                'car_number' => $number,
                'parking_code' => 'PK-' . strtoupper(Str::random(10)),
                'status' => 'unused',
            ]);
        }

        $hold->delete();

        return $booking;
    }

    // Payment complete and record the tickets in the table, then verify email before ticket mail.
    public function stripeSuccess(Request $request)
    {
    if (!$request->filled('session_id')) {
        return redirect()->route('website.events.index')->with('error', 'Missing payment session.');
    }

    Stripe::setApiKey(config('services.stripe.secret'));
    $session = Session::retrieve($request->session_id);
    $paymentTransaction = $this->findPaymentTransactionForSession($session, $request->input('payment_transaction_id'));
    $transactionId = $this->stripeTransactionId($session);
    $paymentCompletedAt = ($session->payment_status ?? null) === 'paid' ? now() : null;

    $paymentTransaction->update([
        'gateway_session_id' => $session->id,
        'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
        'transaction_id' => $transactionId,
        'status' => ($session->payment_status ?? null) === 'paid'
            ? PaymentTransaction::STATUS_COMPLETED
            : PaymentTransaction::STATUS_FAILED,
        'gateway_payment_status' => $session->payment_status ?? null,
        'currency' => strtoupper((string) ($session->currency ?? $paymentTransaction->currency)),
        'amount' => $this->stripeAmount($session, $paymentTransaction->amount),
        'completed_at' => $paymentCompletedAt,
        'failed_at' => ($session->payment_status ?? null) === 'paid' ? null : now(),
        'failure_reason' => ($session->payment_status ?? null) === 'paid' ? null : 'Stripe returned payment_status=' . ($session->payment_status ?? 'unknown'),
        'raw_payload' => $this->stripePayload($session),
    ]);

    $token      = $session->metadata->hold_token ?? null;
    $checksum   = $session->metadata->checksum ?? null;
    $metaCoupon = $session->metadata->coupon_code ?? null; 
    $phone      = $session->metadata->phone ?? null;
    $phonePrefix = $session->metadata->phone_prefix ?? null;
    $email      = $session->metadata->email ?? null;
    $name       = $session->metadata->name ?? null;
    $countryId  = $session->metadata->country_id ?? null;
    $stateId    = $session->metadata->state_id ?? null;
    $parkingSlots = $session->metadata->parking_slots ?? 0;
    $quantity = $session->metadata->quantity ?? 0;
    $carNumbers   = json_decode($session->metadata->car_details ?? '[]', true);
    $rawSeats = $session->metadata->selected_seats ?? '[]';

    // 1. First decode: removes outer quotes and backslashes
    $decodedOnce = json_decode($rawSeats, true);

    // 2. Second decode: converts the string '["277"]' into a real PHP array
    if (is_string($decodedOnce)) {
        $selectedSeats = json_decode($decodedOnce, true);
    } else {
        $selectedSeats = $decodedOnce;
    }

    // 3. Safety check
    if (!is_array($selectedSeats)) {
        $selectedSeats = [];
    }
    
    if (!$token || hash_hmac('sha256', $token, config('app.key')) !== $checksum) {
        abort(403, 'Invalid payment metadata');
    }

    try {
    $bookingId = DB::transaction(function () use ($session, $token, $name, $email, $phonePrefix, $phone, $countryId, $stateId, $metaCoupon, $parkingSlots, $carNumbers, $quantity, $selectedSeats, $paymentTransaction, $transactionId, $paymentCompletedAt) {
        $hold = TicketHold::where('token', $token)->lockForUpdate()->first();

        if (!$hold) {
            return null;
        }

        return $this->completeCheckoutBooking($hold, [
            'quantity' => (int) $quantity,
            'name' => $name,
            'email' => $email,
            'phone_prefix' => $phonePrefix,
            'phone' => $phone,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'coupon_code' => $metaCoupon,
            'parking_slots' => (int) $parkingSlots,
            'car_details' => $carNumbers,
            'selected_seats' => $selectedSeats,
            'payment_status' => $session->payment_status,
            'payment_method' => 'stripe',
            'refund_status' => TicketCounter::REFUND_PENDING,
            'payment_transaction_id' => $paymentTransaction->id,
            'transaction_id' => $transactionId,
            'gateway_session_id' => $session->id,
            'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
            'payment_initiated_at' => $paymentTransaction->initiated_at,
            'payment_completed_at' => $paymentCompletedAt,
            'payment_failed_at' => ($session->payment_status ?? null) === 'paid' ? null : now(),
            'payment_failure_reason' => ($session->payment_status ?? null) === 'paid' ? null : 'Stripe returned payment_status=' . ($session->payment_status ?? 'unknown'),
        ]);
    });

    }catch (\Exception $e) {
        \Log::error('Booking failed: ' . $e->getMessage());
        // If the money was taken (paid) but the DB transaction failed, record it for refund
        $this->recordFailedBooking($session, 'System Error: ' . $e->getMessage(), $paymentTransaction);
        
        return redirect()->route('website.events.index')->with('error', 'A system error occurred. Our admin will review your payment for a refund.');
    }

    if (!$bookingId) {
        $latePaymentReason = 'Payment completed but checkout hold expired before booking could be completed.';

        if (($session->payment_status ?? null) === 'paid') {
            $failedTicket = $paymentTransaction->ticket_counter_id
                ? TicketCounter::find($paymentTransaction->ticket_counter_id)
                : null;

            if ($failedTicket) {
                $failedTicket->update([
                    'payment_status' => 'paid',
                    'refund_status' => TicketCounter::REFUND_PENDING,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'transaction_id' => $transactionId,
                    'gateway_session_id' => $session->id,
                    'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
                    'payment_completed_at' => $paymentCompletedAt,
                    'payment_failed_at' => null,
                    'payment_failure_reason' => $latePaymentReason,
                ]);
            } else {
                $this->recordFailedBooking($session, $latePaymentReason, $paymentTransaction);
            }
        }

        $paymentTransaction->update([
            'failure_reason' => trim(($paymentTransaction->failure_reason ? $paymentTransaction->failure_reason . ' | ' : '') . $latePaymentReason),
        ]);

        return redirect()->route('website.events.index')->with('error', 'This payment has already been processed or the checkout session expired.');
    }

    $this->rememberCheckoutAccess($bookingId);

    try {
        $this->sendCheckoutOtpForBooking($bookingId);
    } catch (\Throwable $e) {
        $bookingId->forceFill(['checkout_otp_resend_available_at' => null])->save();

        Log::error('Checkout OTP email failed after payment', [
            'booking_id' => $bookingId?->booking_id,
            'error' => $e->getMessage(),
        ]);

        return redirect()
            ->route('website.events.checkout.verify', $bookingId->booking_id)
            ->with('error', 'Payment successful, but OTP could not be sent. Please use resend OTP.');
    }

    return redirect()->route('website.events.checkout.verify', $bookingId->booking_id);
    }

    private function findPaymentTransactionForSession($session, $paymentTransactionId = null): PaymentTransaction
    {
        $paymentTransaction = null;

        if ($paymentTransactionId) {
            $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
        }

        if (!$paymentTransaction && isset($session->metadata->payment_transaction_id)) {
            $paymentTransaction = PaymentTransaction::find($session->metadata->payment_transaction_id);
        }

        if (!$paymentTransaction && isset($session->id)) {
            $paymentTransaction = PaymentTransaction::where('gateway_session_id', $session->id)->first();
        }

        if ($paymentTransaction) {
            return $paymentTransaction;
        }

        $selectedSeats = json_decode((string) ($session->metadata->selected_seats ?? '[]'), true);
        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true);
        }

        return PaymentTransaction::create([
            'event_id' => $session->metadata->event_id ?? null,
            'ticket_type_id' => $session->metadata->ticket_type_id ?? null,
            'hold_token' => $session->metadata->hold_token ?? null,
            'gateway' => 'stripe',
            'gateway_session_id' => $session->id ?? null,
            'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
            'transaction_id' => $this->stripeTransactionId($session),
            'status' => PaymentTransaction::STATUS_INITIATED,
            'gateway_payment_status' => $session->payment_status ?? null,
            'currency' => strtoupper((string) ($session->currency ?? '')),
            'amount' => $this->stripeAmount($session),
            'quantity' => (int) ($session->metadata->quantity ?? 0),
            'selected_seats' => is_array($selectedSeats) ? $selectedSeats : null,
            'parking_slots' => (int) ($session->metadata->parking_slots ?? 0),
            'car_details' => json_decode((string) ($session->metadata->car_details ?? '[]'), true) ?: null,
            'coupon_code' => $session->metadata->coupon_code ?? null,
            'customer_name' => $session->metadata->name ?? null,
            'customer_email' => $session->metadata->email ?? null,
            'phone_prefix' => $session->metadata->phone_prefix ?? null,
            'mobile_number' => $session->metadata->phone ?? null,
            'country_id' => $session->metadata->country_id ?? null,
            'state_id' => $session->metadata->state_id ?? null,
            'initiated_at' => isset($session->created) ? Carbon::createFromTimestamp($session->created) : now(),
            'raw_payload' => $this->stripePayload($session),
        ]);
    }

    private function stripeObjectId($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return isset($value->id) ? (string) $value->id : null;
    }

    private function stripeTransactionId($session): ?string
    {
        return $this->stripeObjectId($session->payment_intent ?? null) ?: ($session->id ?? null);
    }

    private function stripeAmount($session, $fallback = 0): float
    {
        $amount = $session->amount_total ?? $session->amount_subtotal ?? null;

        if (is_numeric($amount)) {
            return round(((float) $amount) / 100, 2);
        }

        return (float) $fallback;
    }

    private function stripePayload($session): array
    {
        try {
            if (is_object($session) && method_exists($session, 'toArray')) {
                return $session->toArray();
            }

            return json_decode(json_encode($session), true) ?: [];
        } catch (\Throwable $e) {
            return ['payload_error' => $e->getMessage()];
        }
    }

    /**
     * Helper to record a failed attempt for the Admin Panel
    */
    private function recordFailedBooking($session, $reason, ?PaymentTransaction $paymentTransaction = null): TicketCounter
    {
        $transactionId = $this->stripeTransactionId($session);
        $paymentStatus = $session->payment_status ?? 'paid';
        $isPaid = $paymentStatus === 'paid';

        $ticket = TicketCounter::create([
            'event_id'       => $session->metadata->event_id ?? null,
            'ticket_type_id' => $session->metadata->ticket_type_id ?? null,
            'qty'            => $session->metadata->quantity ?? 0,
            'name'           => $session->metadata->name ?? 'Unknown',
            'email'          => $session->metadata->email ?? 'Unknown',
            'phone_prefix'   => $session->metadata->phone_prefix ?? null,
            'mobile_number'  => $session->metadata->phone ?? 'Unknown',
            'total_amount'   => ($session->amount_total ?? 0) / 100, // Stripe uses cents
            'payment_status' => $session->payment_status ?? 'paid',
            'booking_status' => TicketCounter::STATUS_FAILED,
            'refund_status' => TicketCounter::REFUND_PENDING,
            'payment_method' => 'stripe',
            'payment_transaction_id' => $paymentTransaction?->id,
            'transaction_id' => $transactionId,
            'gateway_session_id' => $session->id ?? null,
            'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
            'payment_initiated_at' => $paymentTransaction?->initiated_at,
            'payment_completed_at' => $isPaid ? now() : null,
            'payment_failed_at' => $isPaid ? null : now(),
            'payment_failure_reason' => $reason,
        ]);

        $paymentTransaction?->update([
            'ticket_counter_id' => $ticket->id,
            'booking_id' => $ticket->booking_id,
            'gateway_session_id' => $session->id ?? $paymentTransaction->gateway_session_id,
            'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null) ?? $paymentTransaction->gateway_payment_intent_id,
            'transaction_id' => $transactionId ?? $paymentTransaction->transaction_id,
            'status' => $isPaid ? PaymentTransaction::STATUS_COMPLETED : PaymentTransaction::STATUS_FAILED,
            'gateway_payment_status' => $paymentStatus,
            'currency' => strtoupper((string) ($session->currency ?? $paymentTransaction->currency)),
            'amount' => $this->stripeAmount($session, $paymentTransaction->amount),
            'completed_at' => $isPaid ? now() : $paymentTransaction->completed_at,
            'failed_at' => $isPaid ? $paymentTransaction->failed_at : now(),
            'failure_reason' => $reason,
            'raw_payload' => $this->stripePayload($session),
        ]);

        return $ticket;
    }

    public function showSuccess(string $booking_id)
    {
       $booking = TicketCounter::where('booking_id', $booking_id)
        ->where('payment_status', 'paid')
        ->firstOrFail();

        if (!$this->hasCheckoutAccess($booking)) {
            return redirect()->route('website.home.index');
        }

        if ($booking->booking_status !== TicketCounter::STATUS_CONFIRMED || !$booking->email_verified_at || !$booking->ticket_email_sent_at) {
            return redirect()
                ->route('website.events.checkout.verify', $booking->booking_id)
                ->with('warning', 'Please verify your email to receive your tickets.');
        }

        if (session()->has($this->checkoutSuccessViewedSessionKey($booking))) {
            return redirect()->route('website.home.index');
        }

        session()->put($this->checkoutSuccessViewedSessionKey($booking), true);

        $event = Event::find($booking->event_id);

        if ($event) {
            $this->clearVotingFlowSession($event);
        }

        $ticketType = TicketType::find($booking->ticket_type_id);

        return view('website.events.thank-you', compact(
            'booking',
            'event',
            'ticketType'
        ));
    }


    public function stripeCancel(Request $request)
    {
        $paymentTransaction = PaymentTransaction::find($request->input('payment_transaction_id'));

        if ($paymentTransaction && $paymentTransaction->status === PaymentTransaction::STATUS_INITIATED) {
            $payload = $paymentTransaction->raw_payload;

            if ($paymentTransaction->gateway_session_id) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $session = Session::retrieve($paymentTransaction->gateway_session_id);
                    $payload = $this->stripePayload($session);
                } catch (\Throwable $e) {
                    Log::warning('Unable to refresh Stripe session after cancellation', [
                        'payment_transaction_id' => $paymentTransaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $paymentTransaction->update([
                'status' => PaymentTransaction::STATUS_CANCELLED,
                'gateway_payment_status' => $paymentTransaction->gateway_payment_status ?? 'unpaid',
                'cancelled_at' => now(),
                'cancel_reason' => 'User cancelled Stripe checkout.',
                'raw_payload' => $payload,
            ]);
        }

        return redirect()
            ->route('website.events.index')
            ->with('error', 'Payment was cancelled.');
    }

}
