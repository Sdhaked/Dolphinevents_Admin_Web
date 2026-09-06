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
use App\Models\EventService;
use App\Models\TicketCounterAgeGroup;
use App\Models\TicketCounterService;
use App\Models\TicketTypeAgeGroup;

use Stripe\Stripe;
use Stripe\Checkout\Session;

use App\Services\TicketPdfService;
use App\Services\EventServiceFieldResponseService;
use Illuminate\Validation\ValidationException;


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

        $eventsQuery = Event::withCardData()
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
        $this->loadTicketTypesByStartingPrice($event);

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
        if (!$this->eventUsesSeatBooking($event)) {
            return redirect()->route('website.events.event_tickets', $event->slug);
        }

        $this->loadTicketTypesByStartingPrice($event);

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
        $this->loadTicketTypesByStartingPrice($event);


        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        if (!$event) {
            abort(404);
        }

        return view('website.events.event-tickets', compact('event'));
    }

    private function loadTicketTypesByStartingPrice(Event $event): void
    {
        $event->load([
            'ticketTypes' => function ($query) {
                $query->with([
                    'ageGroups',
                    'bulkDiscounts' => fn ($q) => $q->orderBy('min_order_qty', 'asc'),
                ]);
            },
        ]);

        $event->setRelation('ticketTypes', TicketType::sortByStartingPrice($event->ticketTypes));
    }

    private function eventUsesSeatBooking(Event $event): bool
    {
        return (int) ($event->type ?? 1) === 2
            && (bool) config('entities.event_booking_systems.show_selection', false);
    }

    private function checkoutHoldExpiresAt(): Carbon
    {
        return now()->copy()->addMinutes((int) config('entities.checkout_hold_minutes', 30));
    }

    private function activeBookingStatusesForAvailability(): array
    {
        return [
            TicketCounter::STATUS_CONFIRMED,
            TicketCounter::STATUS_PENDING_VERIFICATION,
            TicketCounter::STATUS_PENDING_PAYMENT,
        ];
    }

    private function availableTicketQuantity(TicketType $ticketType, ?int $excludeBookingId = null): int
    {
        $query = TicketCounter::where('ticket_type_id', $ticketType->id)
            ->whereIn('booking_status', $this->activeBookingStatusesForAvailability());

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return max(0, (int) $ticketType->total_tickets - (int) $query->sum('qty'));
    }

    private function quoteInputFromHold(TicketCounter $booking, TicketHold $hold): array
    {
        return [
            'quantity' => $booking->qty ?: $hold->quantity,
            'coupon_code' => $booking->coupon_code ?: $hold->coupon_code,
            'service_items' => $hold->service_items ?? [],
            'age_group_items' => $hold->age_group_items ?? [],
        ];
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

    private function findVotingBooking(Event $event, string $bookingId, bool $allowPendingPayment = false): ?TicketCounter
    {
        $query = TicketCounter::where('event_id', $event->id)
            ->where('booking_id', trim($bookingId))
            ->where(function ($query) use ($allowPendingPayment) {
                $query->where('payment_status', 'paid');

                if ($allowPendingPayment) {
                    $query->orWhere(function ($pendingQuery) {
                        $pendingQuery->where('payment_status', 'unpaid')
                            ->where('booking_status', TicketCounter::STATUS_PENDING_PAYMENT);
                    });
                }
            });

        return $query->first();
    }

    private function getVerifiedVotingBooking(Event $event): ?TicketCounter
    {
        $verified = session($this->verifiedVotingSessionKey($event));
        $bookingId = is_array($verified) ? ($verified['booking_id'] ?? null) : null;

        if (!$bookingId) {
            return null;
        }

        $checkoutVoting = session($this->checkoutVotingSessionKey($event));
        $allowPendingPayment = is_array($checkoutVoting)
            && ($checkoutVoting['booking_id'] ?? null) === $bookingId;

        $booking = $this->findVotingBooking($event, $bookingId, $allowPendingPayment);
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
            'quantity'       => 'nullable|integer|min:1|max:20',
            'selected_seats' => 'nullable|array', 
        ]);

        $event = Event::findOrFail($request->event_id);
        if ($event?->sell_tickets_till && now()->gt($event->sell_tickets_till)) {
            // If the current time is after the sell_tickets_till time, redirect to the event details page
            return redirect()->route('website.events.show', $event->slug);
        }
        $ticketType = $event->ticketTypes()->findOrFail($request->ticket_type_id);
        
        // Determine quantity: if seats are provided, count them; otherwise use quantity input
        $selectedSeats = $this->eventUsesSeatBooking($event)
            ? collect($request->input('selected_seats', []))
                ->filter(fn ($seatId) => filled($seatId))
                ->map(fn ($seatId) => (int) $seatId)
                ->values()
                ->all()
            : [];
        $requiresSeatSelection = $this->eventUsesSeatBooking($event) && DB::table('ticket_type_seats')
            ->where('event_id', $event->id)
            ->where('ticket_type_id', $ticketType->id)
            ->exists();

        if ($requiresSeatSelection && empty($selectedSeats)) {
            return back()
                ->withInput()
                ->with('error', 'Please select at least one seat to continue.');
        }

        $quantity = !empty($selectedSeats) ? count($selectedSeats) : $request->input('quantity', 1);

        if ($quantity < 1 || $quantity > 20) {
            return back()->with('error', 'Maximum 20 tickets are allowed per booking.');
        }
        
        // 1. Availability Check
        if ($this->availableTicketQuantity($ticketType) < $quantity) {
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
            'expires_at'     => $this->checkoutHoldExpiresAt(),
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
            }, 'ageGroups'])
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

        $checkout = [
            'event_id'       => $hold->event_id,
            'ticket_type_id' => $hold->ticket_type_id,
            'quantity'       => $hold->quantity,
            'token'          => $hold->token,
            'expires_at'     => $hold->expires_at,
            'selected_seats' => $selectedSeatLabels,
        ];

        $countries = Country::orderBy('name')->get(['id', 'name']);
        $eventServices = EventService::with('fields')
            ->where('event_id', $event->id)
            ->where('status', true)
            ->get()
            ->filter(fn (EventService $service) => $service->isApplicableToTicketType($ticketType->id))
            ->values();
        $ageGroups = $ticketType->enable_age_group ? $ticketType->ageGroups : collect();

        return view(
            'website.events.checkout',
            compact('event', 'ticketType', 'checkout', 'slabs', 'countries', 'eventServices', 'ageGroups')
        );
    }

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    private function startPrePaymentCheckout(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:ticket_holds,token',
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_prefix' => 'required|string|max:20',
            'phone' => ['required', 'regex:/^[0-9]{5,12}$/'],
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'quantity' => 'required|integer|min:1|max:20',
            'coupon_code' => 'nullable|string',
            'service_items' => 'nullable|array',
            'service_items.*.id' => 'nullable|integer|distinct',
            'service_items.*.quantity' => 'nullable|integer|min:0',
            'service_items.*.field_values' => 'nullable|array',
            'service_items.*.field_values.*' => 'nullable|array',
            'age_group_items' => 'nullable|array',
            'age_group_items.*.id' => 'nullable|integer',
            'age_group_items.*.quantity' => 'nullable|integer|min:0',
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

        try {
            $booking = DB::transaction(function () use ($request) {
                $hold = TicketHold::where('token', $request->token)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (!$hold) {
                    throw new \RuntimeException('Checkout session expired. Please start again.');
                }

                $booking = null;
                if ($hold->pending_ticket_counter_id) {
                    $booking = TicketCounter::whereKey($hold->pending_ticket_counter_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($booking && $booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED) {
                    return $booking;
                }

                $ticketType = TicketType::with(['bulkDiscounts', 'ageGroups'])->findOrFail($hold->ticket_type_id);
                $event = Event::findOrFail($ticketType->event_id);
                $quote = $this->prepareCheckoutQuote(
                    $event,
                    $ticketType,
                    $hold,
                    $request->all(),
                    $booking?->id
                );
                $selectedSeats = $this->normalizeSelectedSeats($hold->selected_seats);

                $bookingPayload = [
                    'event_id' => $hold->event_id,
                    'ticket_type_id' => $hold->ticket_type_id,
                    'qty' => $quote['quantity'],
                    'bulk_discount_applied' => $quote['bulk_discount_applied'],
                    'selected_seats' => $selectedSeats ?: null,
                    'coupon_applied' => $quote['coupon_applied'],
                    'coupon_code' => $quote['coupon_applied'] ? $quote['coupon_code'] : null,
                    'coupon_amount' => $quote['discount_amount'],
                    'coupon_percentage' => $quote['discount_percentage'],
                    'total_amount' => $quote['final_amount'],
                    'name' => $request->name,
                    'email' => $request->email,
                    'email_verified_at' => null,
                    'ticket_email_sent_at' => null,
                    'phone_prefix' => $request->phone_prefix,
                    'mobile_number' => $request->phone,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'payment_status' => 'unpaid',
                    'booking_status' => TicketCounter::STATUS_PENDING_PAYMENT,
                    'refund_status' => TicketCounter::REFUND_PENDING,
                    'payment_method' => $quote['final_amount'] <= 0 ? 'free' : 'stripe',
                    'payment_transaction_id' => null,
                    'transaction_id' => null,
                    'gateway_session_id' => null,
                    'gateway_payment_intent_id' => null,
                    'payment_initiated_at' => null,
                    'payment_completed_at' => null,
                    'payment_failed_at' => null,
                    'payment_cancelled_at' => null,
                    'payment_failure_reason' => null,
                ];

                if ($booking) {
                    $booking->update($bookingPayload);
                } else {
                    $booking = TicketCounter::create($bookingPayload);
                }

                $hold->update([
                    'quantity' => $quote['quantity'],
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_prefix' => $request->phone_prefix,
                    'mobile_number' => $request->phone,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'coupon_code' => $quote['coupon_code'],
                    'service_items' => $quote['service_items'],
                    'age_group_items' => $quote['age_group_items'],
                    'total_amount' => $quote['final_amount'],
                    'email_verified_at' => null,
                    'payment_started_at' => null,
                    'pending_ticket_counter_id' => $booking->id,
                    'checkout_started_at' => now(),
                    'expires_at' => $this->checkoutHoldExpiresAt(),
                ]);

                return $booking->fresh();
            });

            $this->rememberCheckoutAccess($booking);

            if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED) {
                return response()->json([
                    'url' => route('website.events.checkout.success.page', $booking->booking_id),
                    'message' => 'This booking has already been completed.',
                ]);
            }

            try {
                $this->sendCheckoutOtpForBooking($booking);
            } catch (\Throwable $e) {
                $booking->forceFill(['checkout_otp_resend_available_at' => null])->save();

                Log::error('Pre-payment checkout OTP email failed', [
                    'booking_id' => $booking?->booking_id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'url' => route('website.events.checkout.prepay.verify', $booking->booking_id),
                    'message' => 'Checkout saved, but OTP could not be sent. Please use resend OTP.',
                ]);
            }

            return response()->json([
                'url' => route('website.events.checkout.prepay.verify', $booking->booking_id),
                'message' => 'Please verify your email to continue.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'expired') ? 410 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\Throwable $e) {
            Log::error('Pre-payment checkout failed', [
                'token' => $request->token,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to start checkout right now. Please try again.'], 500);
        }
    }

    private function prepareCheckoutQuote(Event $event, TicketType $ticketType, TicketHold $hold, array $input, ?int $excludeBookingId = null): array
    {
        $selectedSeats = $this->normalizeSelectedSeats($hold->selected_seats);
        $quantity = max(1, (int) ($input['quantity'] ?? $hold->quantity ?? 1));

        if (!empty($selectedSeats)) {
            $quantity = count($selectedSeats);
        }

        if ($quantity > 20) {
            throw new \RuntimeException('Maximum 20 tickets are allowed per booking.');
        }

        $ageGroupItems = [];
        $ticketSubtotal = (float) $ticketType->ticket_price * $quantity;

        if ($ticketType->enable_age_group && empty($selectedSeats)) {
            $ageGroups = $ticketType->relationLoaded('ageGroups')
                ? $ticketType->ageGroups
                : $ticketType->ageGroups()->get();

            $requestedAgeGroups = collect($input['age_group_items'] ?? [])
                ->mapWithKeys(fn ($item) => [(int) ($item['id'] ?? 0) => max(0, (int) ($item['quantity'] ?? 0))]);

            foreach ($ageGroups as $ageGroup) {
                $requestedQuantity = (int) ($requestedAgeGroups[$ageGroup->id] ?? 0);

                if ($ageGroup->is_compulsory && $requestedQuantity <= 0) {
                    throw new \RuntimeException($ageGroup->label . ' age group is compulsory.');
                }

                if ($requestedQuantity <= 0) {
                    continue;
                }

                $maxPerBooking = max(1, (int) $ageGroup->max_quantity_per_booking);
                if ($requestedQuantity > $maxPerBooking) {
                    throw new \RuntimeException("Maximum {$maxPerBooking} {$ageGroup->label} tickets are allowed per booking.");
                }

                if ((int) $ageGroup->total_tickets > 0) {
                    $sold = TicketCounterAgeGroup::where('ticket_type_age_group_id', $ageGroup->id)
                        ->whereHas('booking', function ($query) {
                            $query->whereIn('booking_status', [
                                TicketCounter::STATUS_CONFIRMED,
                                TicketCounter::STATUS_PENDING_VERIFICATION,
                                TicketCounter::STATUS_PENDING_PAYMENT,
                            ]);
                        })
                        ->sum('quantity');

                    $available = max(0, (int) $ageGroup->total_tickets - (int) $sold);
                    if ($requestedQuantity > $available) {
                        throw new \RuntimeException("Only {$available} {$ageGroup->label} tickets remaining.");
                    }
                }

                $ageGroupItems[] = [
                    'id' => $ageGroup->id,
                    'label' => $ageGroup->label,
                    'quantity' => $requestedQuantity,
                    'price' => (float) $ageGroup->price,
                    'total' => round($requestedQuantity * (float) $ageGroup->price, 2),
                ];
            }

            if (empty($ageGroupItems)) {
                throw new \RuntimeException('Please select at least one age-group ticket.');
            }

            $quantity = array_sum(array_column($ageGroupItems, 'quantity'));
            if ($quantity > 20) {
                throw new \RuntimeException('Maximum 20 tickets are allowed per booking.');
            }

            $ticketSubtotal = array_sum(array_column($ageGroupItems, 'total'));
        }

        $availableTickets = $this->availableTicketQuantity($ticketType, $excludeBookingId);
        if ($quantity > $availableTickets) {
            throw new \RuntimeException("Only {$availableTickets} tickets remaining.");
        }

        $serviceItems = [];
        $requestedServices = collect($input['service_items'] ?? [])->values();
        $serviceQuery = EventService::with('fields')
            ->where('event_id', $event->id)
            ->where('status', true);

        if (DB::transactionLevel() > 0) {
            $serviceQuery->lockForUpdate();
        }

        $services = $serviceQuery->get()
            ->filter(fn (EventService $service) => $service->isApplicableToTicketType($ticketType->id));

        $servicesById = $services->keyBy('id');
        foreach ($requestedServices as $requestIndex => $requestedService) {
            $requestedId = (int) ($requestedService['id'] ?? 0);
            $requestedQuantity = max(0, (int) ($requestedService['quantity'] ?? 0));

            if ($requestedQuantity > 0 && !$servicesById->has($requestedId)) {
                throw ValidationException::withMessages([
                    "service_items.{$requestIndex}.id" => 'The selected event service is not available for this ticket.',
                ]);
            }
        }

        foreach ($services as $service) {
            $requestIndex = $requestedServices->search(
                fn ($item) => (int) ($item['id'] ?? 0) === (int) $service->id
            );
            $requestedService = $requestIndex === false ? [] : ($requestedServices[$requestIndex] ?? []);
            $requestedQuantity = max(0, (int) ($requestedService['quantity'] ?? 0));

            if ($service->is_mandatory) {
                $requestedQuantity = max(1, $requestedQuantity);
            }

            if ($requestedQuantity <= 0) {
                continue;
            }

            $maxServiceQuantity = max(1, (int) $service->max_buy_limit);
            if ($requestedQuantity > $maxServiceQuantity) {
                throw new \RuntimeException("Maximum {$maxServiceQuantity} {$service->name} services are allowed per booking.");
            }

            if ((int) $service->available_quantity > 0) {
                $sold = TicketCounterService::where('event_service_id', $service->id)
                    ->whereHas('booking', function ($query) {
                        $query->whereIn('booking_status', [
                            TicketCounter::STATUS_CONFIRMED,
                            TicketCounter::STATUS_PENDING_VERIFICATION,
                            TicketCounter::STATUS_PENDING_PAYMENT,
                        ]);
                    })
                    ->sum('quantity');

                $activeHeld = $this->activeHeldServiceQuantity($hold, $service->id);
                $available = max(0, (int) $service->available_quantity - (int) $sold - $activeHeld);
                if ($requestedQuantity > $available) {
                    throw new \RuntimeException("Only {$available} {$service->name} services remaining.");
                }
            }

            $responsePrefix = $requestIndex === false ? 'service_items' : "service_items.{$requestIndex}";
            $fieldResponses = app(EventServiceFieldResponseService::class)->validateAndNormalize(
                $service,
                $requestedQuantity,
                $requestedService['field_values'] ?? [],
                $responsePrefix,
                strict: true
            );

            $serviceItems[] = [
                'id' => $service->id,
                'name' => $service->name,
                'quantity' => $requestedQuantity,
                'price' => (float) $service->price,
                'total' => round($requestedQuantity * (float) $service->price, 2),
                'field_values' => $this->serviceFieldValuesFromResponses($fieldResponses),
                'field_responses' => $fieldResponses,
            ];
        }

        $serviceTotal = array_sum(array_column($serviceItems, 'total'));

        $discountPercentage = 0;
        $discountAmount = 0;
        $bulkDiscountApplied = false;
        $couponApplied = false;
        $appliedCouponCode = null;

        if ($ticketType->enable_bulk_discount) {
            $bulk = $ticketType->bulkDiscounts()
                ->where('min_order_qty', '<=', $quantity)
                ->orderByDesc('min_order_qty')
                ->first();

            if ($bulk) {
                $bulkDiscountApplied = true;
                $discountPercentage = (float) $bulk->discount_percentage;
                $discountAmount = ($ticketSubtotal * $discountPercentage) / 100;
            }
        }

        if (!$bulkDiscountApplied && !empty($input['coupon_code'])) {
            $coupon = \App\Models\DiscountCoupon::where('coupon_code', $input['coupon_code'])
                ->where('event_id', $event->id)
                ->first();

            $couponTicketTypes = $coupon?->ticket_type_ids ?? [];
            $couponApplies = $coupon && (empty($couponTicketTypes) || in_array($ticketType->id, array_map('intval', $couponTicketTypes), true));

            if ($couponApplies && $coupon->canBeUsed()) {
                $couponApplied = true;
                $appliedCouponCode = $coupon->coupon_code;
                $discountPercentage = (float) $coupon->discount;
                $discountAmount = ($ticketSubtotal * $discountPercentage) / 100;
            }
        }

        $ticketTotal = max(0, round($ticketSubtotal - $discountAmount, 2));
        $taxableBasis = $ticketTotal + $serviceTotal;
        $taxAmount = 0;

        if ($ticketType->enable_tax && (float) $ticketType->tax_value > 0) {
            $taxAmount = ($taxableBasis * (float) $ticketType->tax_value) / 100;
        }

        $extraChargesAmount = 0;
        if ($ticketType->enable_extra_charges && (float) $ticketType->extra_charges_value > 0) {
            $extraChargesAmount = (($taxableBasis + $taxAmount) * (float) $ticketType->extra_charges_value) / 100;
        }

        return [
            'quantity' => $quantity,
            'ticket_subtotal' => round($ticketSubtotal, 2),
            'ticket_total' => round($ticketTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_percentage' => round($discountPercentage, 2),
            'bulk_discount_applied' => $bulkDiscountApplied,
            'coupon_applied' => $couponApplied,
            'coupon_code' => $appliedCouponCode,
            'service_items' => $serviceItems,
            'service_total' => round($serviceTotal, 2),
            'age_group_items' => $ageGroupItems,
            'tax_amount' => round($taxAmount, 2),
            'extra_charges_amount' => round($extraChargesAmount, 2),
            'final_amount' => round($taxableBasis + $taxAmount + $extraChargesAmount, 2),
        ];
    }

    private function normalizeSelectedSeats($selectedSeats): array
    {
        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (!is_array($selectedSeats)) {
            return [];
        }

        return array_values(array_unique(array_filter($selectedSeats, fn ($seatId) => filled($seatId))));
    }

    private function activeHeldServiceQuantity(TicketHold $currentHold, int $serviceId): int
    {
        return (int) TicketHold::where('event_id', $currentHold->event_id)
            ->whereKeyNot($currentHold->id)
            ->where('expires_at', '>', now())
            ->get(['service_items'])
            ->sum(function (TicketHold $hold) use ($serviceId) {
                $items = is_array($hold->service_items)
                    ? $hold->service_items
                    : (json_decode((string) $hold->service_items, true) ?: []);

                return collect($items)
                    ->filter(fn ($item) => (int) ($item['id'] ?? 0) === $serviceId)
                    ->sum(fn ($item) => max(0, (int) ($item['quantity'] ?? 0)));
            });
    }

    private function serviceFieldValuesFromResponses(array $responses): array
    {
        $values = [];

        foreach ($responses as $response) {
            $unitIndex = max(0, (int) ($response['unit_number'] ?? 1) - 1);
            $fieldId = (int) ($response['event_service_field_id'] ?? 0);

            if ($fieldId > 0) {
                $values[$unitIndex][(string) $fieldId] = $response['value'] ?? null;
            }
        }

        ksort($values);

        return array_values($values);
    }

    private function normalizeAgeGroupItems($ageGroupItems): array
    {
        if (is_string($ageGroupItems)) {
            $ageGroupItems = json_decode($ageGroupItems, true) ?? [];
        }

        if (is_string($ageGroupItems)) {
            $ageGroupItems = json_decode($ageGroupItems, true) ?? [];
        }

        if (!is_array($ageGroupItems)) {
            return [];
        }

        return array_values(array_filter($ageGroupItems, function ($item) {
            return is_array($item) && max(0, (int) ($item['quantity'] ?? 0)) > 0;
        }));
    }

    private function syncBookingAgeGroups(TicketCounter $booking, $ageGroupItems)
    {
        $ageGroupItems = $this->normalizeAgeGroupItems($ageGroupItems);

        if (!$booking->ageGroups()->exists()) {
            foreach ($ageGroupItems as $ageGroupItem) {
                $ageQuantity = max(0, (int) ($ageGroupItem['quantity'] ?? 0));

                if ($ageQuantity <= 0) {
                    continue;
                }

                TicketCounterAgeGroup::create([
                    'ticket_counter_id' => $booking->id,
                    'ticket_type_age_group_id' => $ageGroupItem['id'] ?? null,
                    'label' => $ageGroupItem['label'] ?? 'Age Group',
                    'quantity' => $ageQuantity,
                    'price' => (float) ($ageGroupItem['price'] ?? 0),
                    'total_amount' => (float) ($ageGroupItem['total'] ?? ($ageQuantity * (float) ($ageGroupItem['price'] ?? 0))),
                ]);
            }
        }

        return $booking->ageGroups()->orderBy('id')->get();
    }

    private function expandAgeGroupTicketAssignments($ageGroupRows): array
    {
        $assignments = [];

        foreach ($ageGroupRows as $ageGroupRow) {
            $quantity = max(0, (int) $ageGroupRow->quantity);

            for ($i = 0; $i < $quantity; $i++) {
                $assignments[] = [
                    'ticket_counter_age_group_id' => $ageGroupRow->id,
                    'ticket_type_age_group_id' => $ageGroupRow->ticket_type_age_group_id,
                    'sub_type_label' => $ageGroupRow->label,
                ];
            }
        }

        return $assignments;
    }

    private function syncBookedTicketAgeGroupAssignments(TicketCounter $booking, array $ticketAgeGroupAssignments): void
    {
        if (empty($ticketAgeGroupAssignments)) {
            return;
        }

        $booking->bookedTickets()->orderBy('id')->get()->each(function ($ticket, $index) use ($ticketAgeGroupAssignments) {
            $assignment = $ticketAgeGroupAssignments[$index] ?? null;

            if (!$assignment) {
                return;
            }

            $ticket->forceFill([
                'ticket_counter_age_group_id' => $assignment['ticket_counter_age_group_id'] ?? null,
                'ticket_type_age_group_id' => $assignment['ticket_type_age_group_id'] ?? null,
                'sub_type_label' => $assignment['sub_type_label'] ?? null,
            ])->save();
        });
    }

    // Stripe
    public function createStripeCheckout(Request $request)
    {
        return $this->startPrePaymentCheckout($request);
    }

    public function prePaymentEmailVerification(string $booking_id)
    {
        $booking = $this->findPrePaymentBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return redirect()->route('website.home.index');
        }

        if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED && $booking->ticket_email_sent_at) {
            return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
        }

        if ($booking->email_verified_at) {
            return redirect($this->nextCheckoutStepAfterPrePaymentOtp($booking));
        }

        $event = Event::find($booking->event_id);
        $hold = TicketHold::where('pending_ticket_counter_id', $booking->id)
            ->where('expires_at', '>', now())
            ->first();
        $maskedEmail = $this->maskEmail($booking->email);
        $resendWaitSeconds = $this->checkoutOtpWaitSeconds($booking);
        $showOtpForm = true;
        $bookingId = $booking->booking_id;
        $verificationFlow = 'checkout';
        $allowEmailChange = true;
        $pageTitle = 'Verify Email';
        $eventTitle = $event?->title ?? 'Event';
        $backUrl = $hold?->token
            ? route('website.events.checkout', $hold->token)
            : ($event?->slug ? route('website.events.show', $event->slug) : route('website.events.index'));
        $sendOtpUrl = null;
        $verifyOtpUrl = route('website.events.checkout.prepay.verify_otp', $booking->booking_id);
        $resendOtpUrl = route('website.events.checkout.prepay.resend_otp', $booking->booking_id);
        $changeEmailUrl = route('website.events.checkout.prepay.change_email', $booking->booking_id);
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

    public function resendPrePaymentOtp(Request $request, string $booking_id)
    {
        $booking = $this->findPrePaymentBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->email_verified_at) {
            return $this->checkoutOtpJsonOrRedirect($request, [
                'success' => true,
                'message' => 'Your email is already verified.',
                'redirect' => $this->nextCheckoutStepAfterPrePaymentOtp($booking),
            ], $this->nextCheckoutStepAfterPrePaymentOtp($booking));
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

            Log::error('Pre-payment checkout OTP resend failed', [
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

    public function changePrePaymentEmail(Request $request, string $booking_id)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $booking = $this->findPrePaymentBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED) {
            return $this->checkoutOtpJsonOrRedirect($request, [
                'success' => false,
                'type' => 'warning',
                'message' => 'This booking has already been completed.',
                'redirect' => route('website.events.checkout.success.page', $booking->booking_id),
            ], route('website.events.checkout.success.page', $booking->booking_id));
        }

        $booking->update([
            'email' => $validated['email'],
            'email_verified_at' => null,
            'ticket_email_sent_at' => null,
        ]);

        TicketHold::where('pending_ticket_counter_id', $booking->id)->update([
            'email' => $validated['email'],
            'email_verified_at' => null,
        ]);

        $booking->refresh();

        try {
            $this->sendCheckoutOtpForBooking($booking);
            $booking->refresh();
        } catch (\Throwable $e) {
            $booking->forceFill(['checkout_otp_resend_available_at' => null])->save();

            Log::error('Pre-payment checkout email change OTP failed', [
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

    public function verifyPrePaymentOtp(Request $request, string $booking_id)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $booking = $this->findPrePaymentBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return $this->checkoutAccessDenied($request);
        }

        if ($booking->email_verified_at) {
            $redirectAfterOtp = $this->nextCheckoutStepAfterPrePaymentOtp($booking);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your email is already verified.',
                    'redirect' => $redirectAfterOtp,
                ]);
            }

            return redirect($redirectAfterOtp);
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

        $booking->update([
            'email_verified_at' => now(),
            'checkout_otp_hash' => null,
            'checkout_otp_expires_at' => null,
            'checkout_otp_resend_available_at' => null,
        ]);

        TicketHold::where('pending_ticket_counter_id', $booking->id)->update([
            'email_verified_at' => now(),
            'checkout_otp_hash' => null,
            'checkout_otp_expires_at' => null,
            'checkout_otp_resend_available_at' => null,
        ]);

        $booking->refresh();
        $redirectAfterOtp = $this->nextCheckoutStepAfterPrePaymentOtp($booking);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully.',
                'redirect' => $redirectAfterOtp,
            ]);
        }

        return redirect($redirectAfterOtp);
    }

    public function startVerifiedStripeCheckout(string $booking_id)
    {
        $booking = $this->findPrePaymentBooking($booking_id);

        if (!$this->hasCheckoutAccess($booking)) {
            return redirect()->route('website.home.index');
        }

        if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED) {
            return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
        }

        if (!$booking->email_verified_at) {
            return redirect()
                ->route('website.events.checkout.prepay.verify', $booking->booking_id)
                ->with('warning', 'Please verify your email before payment.');
        }

        $event = Event::find($booking->event_id);
        if ($event && $this->canShowVotingSection($event) && !$this->checkoutBookingHasVote($event, $booking)) {
            session()->put($this->verifiedVotingSessionKey($event), [
                'ticket_counter_id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'verified_at' => now()->toIso8601String(),
            ]);

            session()->put($this->checkoutVotingSessionKey($event), [
                'booking_id' => $booking->booking_id,
                'redirect' => route('website.events.checkout.payment', $booking->booking_id),
            ]);

            return redirect()->route('website.events.voting.show', $event->slug);
        }

        $hold = TicketHold::where('pending_ticket_counter_id', $booking->id)
            ->where('expires_at', '>', now())
            ->first();

        if (!$hold) {
            return redirect()->route('website.events.index')
                ->with('error', 'Checkout session expired. Please start again.');
        }

        if (!$event) {
            return redirect()->route('website.events.index')
                ->with('error', 'Event not found. Please start again.');
        }

        try {
            $ticketType = TicketType::with(['bulkDiscounts', 'ageGroups'])->findOrFail($booking->ticket_type_id);
            $quote = $this->prepareCheckoutQuote(
                $event,
                $ticketType,
                $hold,
                $this->quoteInputFromHold($booking, $hold),
                $booking->id
            );

            $booking->update([
                'qty' => $quote['quantity'],
                'bulk_discount_applied' => $quote['bulk_discount_applied'],
                'coupon_applied' => $quote['coupon_applied'],
                'coupon_code' => $quote['coupon_applied'] ? $quote['coupon_code'] : null,
                'coupon_amount' => $quote['discount_amount'],
                'coupon_percentage' => $quote['discount_percentage'],
                'total_amount' => $quote['final_amount'],
                'payment_method' => $quote['final_amount'] <= 0 ? 'free' : 'stripe',
            ]);

            $hold->update([
                'quantity' => $quote['quantity'],
                'coupon_code' => $quote['coupon_code'],
                'service_items' => $quote['service_items'],
                'age_group_items' => $quote['age_group_items'],
                'total_amount' => $quote['final_amount'],
                'expires_at' => $this->checkoutHoldExpiresAt(),
            ]);

            $booking->refresh();
            $hold->refresh();
        } catch (ValidationException $e) {
            return $this->renderPrePaymentIssue(
                $booking,
                $hold,
                collect($e->errors())->flatten()->first() ?: 'Additional service information is no longer valid.'
            );
        } catch (\RuntimeException $e) {
            return $this->renderPrePaymentIssue($booking, $hold, $e->getMessage());
        }

        $finalAmount = (float) $booking->total_amount;
        $currencyCode = Currency::codeForEvent($event);

        if ($finalAmount <= 0) {
            try {
                $booking = DB::transaction(function () use ($booking, $hold, $currencyCode) {
                    $lockedBooking = TicketCounter::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                    $lockedHold = TicketHold::whereKey($hold->id)->lockForUpdate()->firstOrFail();

                    return $this->finalizePendingCheckoutBooking($lockedBooking, $lockedHold, [
                        'payment_status' => 'paid',
                        'payment_method' => 'free',
                        'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                        'currency' => $currencyCode,
                    ]);
                });

                $this->sendConfirmedTicketEmail($booking);
                $this->rememberCheckoutAccess($booking);

                return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
            } catch (\Throwable $e) {
                Log::error('Free verified checkout finalization failed', [
                    'booking_id' => $booking->booking_id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('website.events.index')
                    ->with('error', 'Unable to complete booking right now. Please try again.');
            }
        }

        $stripeSecret = trim((string) config('services.stripe.secret'));
        if ($stripeSecret === '') {
            Log::error('Verified Stripe checkout blocked because Stripe secret is missing', [
                'booking_id' => $booking->booking_id,
            ]);

            return $this->renderPrePaymentIssue(
                $booking,
                $hold,
                'Payment gateway is not configured. Please contact support.'
            );
        }

        Stripe::setApiKey($stripeSecret);

        $paymentTransaction = PaymentTransaction::create([
            'ticket_counter_id' => $booking->id,
            'event_id' => $booking->event_id,
            'ticket_type_id' => $booking->ticket_type_id,
            'booking_id' => $booking->booking_id,
            'hold_token' => $hold->token,
            'gateway' => 'stripe',
            'status' => PaymentTransaction::STATUS_INITIATED,
            'currency' => strtoupper($currencyCode),
            'amount' => $finalAmount,
            'quantity' => (int) $booking->qty,
            'selected_seats' => $booking->selected_seats,
            'coupon_code' => $booking->coupon_code,
            'customer_name' => $booking->name,
            'customer_email' => $booking->email,
            'phone_prefix' => $booking->phone_prefix,
            'mobile_number' => $booking->mobile_number,
            'country_id' => $booking->country_id,
            'state_id' => $booking->state_id,
            'initiated_at' => now(),
        ]);

        try {
            $ticketType = TicketType::find($booking->ticket_type_id);
            $session = Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currencyCode,
                        'unit_amount' => (int) round($finalAmount * 100),
                        'product_data' => ['name' => ($ticketType?->title ?? 'Event Ticket')],
                    ],
                    'quantity' => 1,
                ]],
                'currency' => $currencyCode,
                'success_url' => route('website.events.checkout.stripe.success', ['paymentTransaction' => $paymentTransaction->id]),
                'cancel_url'  => route('website.events.checkout.stripe.cancel', ['paymentTransaction' => $paymentTransaction->id]),
                'metadata' => [
                    'payment_transaction_id' => (string) $paymentTransaction->id,
                    'pending_booking_id' => (string) $booking->id,
                    'booking_id' => $booking->booking_id,
                    'hold_token' => $hold->token,
                    'event_id' => (string) $booking->event_id,
                    'ticket_type_id' => (string) $booking->ticket_type_id,
                    'quantity' => (string) $booking->qty,
                    'checksum' => hash_hmac('sha256', $hold->token, config('app.key')),
                ],
            ]);

            $paymentTransaction->update([
                'gateway_session_id' => $session->id,
                'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
                'transaction_id' => $this->stripeTransactionId($session),
                'gateway_payment_status' => $session->payment_status ?? null,
                'raw_payload' => $this->stripePayload($session),
            ]);

            $booking->update([
                'payment_transaction_id' => $paymentTransaction->id,
                'gateway_session_id' => $session->id,
                'transaction_id' => $this->stripeTransactionId($session),
                'payment_initiated_at' => now(),
            ]);

            $hold->update(['payment_started_at' => now()]);
        } catch (\Throwable $e) {
            $paymentTransaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Verified Stripe checkout session creation failed', [
                'booking_id' => $booking->booking_id,
                'payment_transaction_id' => $paymentTransaction->id,
                'error' => $e->getMessage(),
            ]);

            return $this->renderPrePaymentIssue(
                $booking,
                $hold,
                'Unable to start payment right now. Please try again.'
            );
        }

        return redirect()->away($session->url);
    }

    private function renderPrePaymentIssue(TicketCounter $booking, ?TicketHold $hold, string $message)
    {
        $event = Event::find($booking->event_id);
        $maskedEmail = $this->maskEmail($booking->email);
        $resendWaitSeconds = 0;
        $showOtpForm = false;
        $bookingId = $booking->booking_id;
        $verificationFlow = 'checkout';
        $allowEmailChange = false;
        $pageTitle = 'Payment Issue';
        $eventTitle = $event?->title ?? 'Event';
        $backUrl = $hold?->token
            ? route('website.events.checkout', $hold->token)
            : ($event?->slug ? route('website.events.show', $event->slug) : route('website.events.index'));
        $sendOtpUrl = null;
        $verifyOtpUrl = null;
        $resendOtpUrl = null;
        $changeEmailUrl = null;
        $resetUrl = null;
        $paymentIssueMessage = $message;
        $paymentRetryUrl = route('website.events.checkout.payment', $booking->booking_id);

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
            'resetUrl',
            'paymentIssueMessage',
            'paymentRetryUrl'
        ));
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

    private function findPrePaymentBooking(string $bookingId): TicketCounter
    {
        return TicketCounter::where('booking_id', trim($bookingId))
            ->where(function ($query) {
                $query->where(function ($pendingQuery) {
                    $pendingQuery->where('payment_status', 'unpaid')
                        ->where('booking_status', TicketCounter::STATUS_PENDING_PAYMENT);
                })->orWhere(function ($paidQuery) {
                    $paidQuery->where('payment_status', 'paid')
                        ->where('booking_status', TicketCounter::STATUS_CONFIRMED);
                });
            })
            ->firstOrFail();
    }

    private function checkoutBookingHasVote(Event $event, TicketCounter $booking): bool
    {
        return EventContestentVote::where('event_id', $event->id)
            ->where(function ($query) use ($booking) {
                $query->where('ticket_counter_id', $booking->id)
                    ->orWhere('booking_id', $booking->booking_id);
            })
            ->exists();
    }

    private function nextCheckoutStepAfterPrePaymentOtp(TicketCounter $booking): string
    {
        if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED) {
            return route('website.events.checkout.success.page', $booking->booking_id);
        }

        $event = Event::find($booking->event_id);

        if ($event && $this->canShowVotingSection($event) && !$this->checkoutBookingHasVote($event, $booking)) {
            session()->put($this->verifiedVotingSessionKey($event), [
                'ticket_counter_id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'verified_at' => now()->toIso8601String(),
            ]);

            session()->put($this->checkoutVotingSessionKey($event), [
                'booking_id' => $booking->booking_id,
                'redirect' => route('website.events.checkout.payment', $booking->booking_id),
            ]);

            return route('website.events.voting.show', $event->slug);
        }

        return route('website.events.checkout.payment', $booking->booking_id);
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
        $selectedSeats = $data['selected_seats'] ?? $hold->selected_seats ?? [];

        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (!is_array($selectedSeats)) {
            $selectedSeats = [];
        }

        $serviceItems = is_array($hold->service_items)
            ? $hold->service_items
            : (json_decode((string) $hold->service_items, true) ?: []);
        $serviceTotal = collect($serviceItems)->sum(
            fn ($item) => max(0, (int) ($item['quantity'] ?? 0)) > 0
                ? (float) ($item['total'] ?? 0)
                : 0
        );

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
        $taxableBasis = $ticketTotal + $serviceTotal;
        $taxAmount = 0;
        if ($ticketType->enable_tax && $ticketType->tax_value > 0) {
            $taxAmount = ($taxableBasis * $ticketType->tax_value) / 100;
        }

        $extraChargesAmount = 0;
        if ($ticketType->enable_extra_charges && $ticketType->extra_charges_value > 0) {
            $extraChargesAmount = (($taxableBasis + $taxAmount) * $ticketType->extra_charges_value) / 100;
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

        $ageGroupRows = $this->syncBookingAgeGroups($booking, $data['age_group_items'] ?? $hold->age_group_items ?? []);
        $ticketAgeGroupAssignments = $this->expandAgeGroupTicketAssignments($ageGroupRows);

        for ($i = 0; $i < $quantity; $i++) {
            $seatId = $selectedSeats[$i] ?? null;
            $ticketNumber = $seatId
                ? $booking->booking_id . "-S" . $seatId
                : $booking->booking_id . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4));
            $ageGroupAssignment = $ticketAgeGroupAssignments[$i] ?? [];

            \App\Models\BookedTicket::create([
                'ticket_counter_id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'ticket_number' => $ticketNumber,
                'venue_layout_id' => $seatId,
                'ticket_counter_age_group_id' => $ageGroupAssignment['ticket_counter_age_group_id'] ?? null,
                'ticket_type_age_group_id' => $ageGroupAssignment['ticket_type_age_group_id'] ?? null,
                'sub_type_label' => $ageGroupAssignment['sub_type_label'] ?? null,
                'status' => 'Not Scanned',
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

        foreach ($serviceItems as $serviceItem) {
            $serviceQuantity = max(0, (int) ($serviceItem['quantity'] ?? 0));

            if ($serviceQuantity <= 0) {
                continue;
            }

            $bookingService = TicketCounterService::create([
                'ticket_counter_id' => $booking->id,
                'event_id' => $booking->event_id,
                'event_service_id' => $serviceItem['id'] ?? null,
                'service_name' => $serviceItem['name'] ?? 'Event Service',
                'quantity' => $serviceQuantity,
                'price' => (float) ($serviceItem['price'] ?? 0),
                'total_amount' => (float) ($serviceItem['total'] ?? ($serviceQuantity * (float) ($serviceItem['price'] ?? 0))),
                'service_code' => 'SV-' . strtoupper(Str::random(10)),
            ]);

            app(EventServiceFieldResponseService::class)->sync(
                $bookingService,
                $serviceItem['field_responses'] ?? []
            );
        }

        app(\App\Services\ServicePassService::class)->ensurePassesForBooking($booking);

        $hold->delete();

        return $booking->load(['services.fieldValues', 'services.passes', 'ageGroups', 'ticketType', 'event']);
    }

    private function finalizePendingCheckoutBooking(TicketCounter $booking, TicketHold $hold, array $data): TicketCounter
    {
        if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED && $booking->bookedTickets()->exists()) {
            return $booking;
        }

        if (($data['payment_status'] ?? 'paid') !== 'paid') {
            throw new \RuntimeException('Payment was not completed.');
        }

        $ticketType = TicketType::with(['bulkDiscounts', 'ageGroups'])->findOrFail($booking->ticket_type_id);
        $event = Event::findOrFail($booking->event_id);
        $selectedSeats = $this->normalizeSelectedSeats($hold->selected_seats);
        $quantity = max(1, (int) ($booking->qty ?: $hold->quantity));
        $serviceItems = is_array($hold->service_items) ? $hold->service_items : (json_decode((string) $hold->service_items, true) ?: []);
        $ageGroupItems = is_array($hold->age_group_items) ? $hold->age_group_items : (json_decode((string) $hold->age_group_items, true) ?: []);

        $quote = $this->prepareCheckoutQuote(
            $event,
            $ticketType,
            $hold,
            $this->quoteInputFromHold($booking, $hold),
            $booking->id
        );

        if (abs((float) $booking->total_amount - (float) $quote['final_amount']) > 0.01) {
            throw new \RuntimeException('Booking amount changed before confirmation. Please contact support for review.');
        }

        $quantity = $quote['quantity'];
        $serviceItems = $quote['service_items'];
        $ageGroupItems = $quote['age_group_items'];

        if ($booking->coupon_applied && $booking->coupon_code) {
            $coupon = \App\Models\DiscountCoupon::where('coupon_code', $booking->coupon_code)
                ->where('event_id', $booking->event_id)
                ->lockForUpdate()
                ->first();

            if ($coupon && $coupon->canBeUsed()) {
                $coupon->incrementUsage();
            }
        }

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
                'event_id' => $booking->event_id,
                'ticket_type_id' => $booking->ticket_type_id,
                'booking_id' => $booking->booking_id,
                'hold_token' => $hold->token,
                'gateway' => 'free',
                'transaction_id' => 'FREE-' . $booking->booking_id,
                'status' => PaymentTransaction::STATUS_COMPLETED,
                'gateway_payment_status' => 'paid',
                'currency' => strtoupper($data['currency'] ?? Currency::codeForEvent($booking->event)),
                'amount' => 0,
                'quantity' => $quantity,
                'selected_seats' => $selectedSeats,
                'coupon_code' => $booking->coupon_code,
                'customer_name' => $booking->name,
                'customer_email' => $booking->email,
                'phone_prefix' => $booking->phone_prefix,
                'mobile_number' => $booking->mobile_number,
                'country_id' => $booking->country_id,
                'state_id' => $booking->state_id,
                'initiated_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $booking->update([
            'qty' => $quantity,
            'selected_seats' => $selectedSeats ?: null,
            'payment_status' => 'paid',
            'booking_status' => TicketCounter::STATUS_CONFIRMED,
            'refund_status' => $data['refund_status'] ?? TicketCounter::REFUND_NOT_REQUIRED,
            'payment_method' => $data['payment_method'] ?? 'stripe',
            'payment_transaction_id' => $paymentTransaction?->id ?? $booking->payment_transaction_id,
            'transaction_id' => $data['transaction_id'] ?? $paymentTransaction?->transaction_id ?? $booking->transaction_id,
            'gateway_session_id' => $data['gateway_session_id'] ?? $paymentTransaction?->gateway_session_id ?? $booking->gateway_session_id,
            'gateway_payment_intent_id' => $data['gateway_payment_intent_id'] ?? $paymentTransaction?->gateway_payment_intent_id ?? $booking->gateway_payment_intent_id,
            'payment_initiated_at' => $data['payment_initiated_at'] ?? $booking->payment_initiated_at,
            'payment_completed_at' => $data['payment_completed_at'] ?? now(),
            'payment_failed_at' => null,
            'payment_cancelled_at' => null,
            'payment_failure_reason' => null,
            'email_verified_at' => $booking->email_verified_at ?? $hold->email_verified_at ?? now(),
            'checkout_otp_hash' => null,
            'checkout_otp_expires_at' => null,
            'checkout_otp_resend_available_at' => null,
        ]);

        $ageGroupRows = $this->syncBookingAgeGroups($booking, $ageGroupItems);
        $ticketAgeGroupAssignments = $this->expandAgeGroupTicketAssignments($ageGroupRows);

        if (!$booking->bookedTickets()->exists()) {
            for ($i = 0; $i < $quantity; $i++) {
                $seatId = $selectedSeats[$i] ?? null;
                $ticketNumber = $seatId
                    ? $booking->booking_id . "-S" . $seatId
                    : $booking->booking_id . "-T" . ($i + 1) . "-" . strtoupper(Str::random(4));
                $ageGroupAssignment = $ticketAgeGroupAssignments[$i] ?? [];

                \App\Models\BookedTicket::create([
                    'ticket_counter_id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'ticket_number' => $ticketNumber,
                    'venue_layout_id' => $seatId,
                    'ticket_counter_age_group_id' => $ageGroupAssignment['ticket_counter_age_group_id'] ?? null,
                    'ticket_type_age_group_id' => $ageGroupAssignment['ticket_type_age_group_id'] ?? null,
                    'sub_type_label' => $ageGroupAssignment['sub_type_label'] ?? null,
                    'status' => 'Not Scanned',
                ]);
            }
        } else {
            $this->syncBookedTicketAgeGroupAssignments($booking, $ticketAgeGroupAssignments);
        }

        if (!empty($selectedSeats)) {
            DB::table('ticket_type_seats')
                ->where('ticket_type_id', $booking->ticket_type_id)
                ->whereIn('venue_layout_id', $selectedSeats)
                ->update([
                    'is_booked' => true,
                    'ticket_counter_id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'booked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        if (!$booking->services()->exists()) {
            foreach ($serviceItems as $serviceItem) {
                $serviceQuantity = max(0, (int) ($serviceItem['quantity'] ?? 0));

                if ($serviceQuantity <= 0) {
                    continue;
                }

                $bookingService = TicketCounterService::create([
                    'ticket_counter_id' => $booking->id,
                    'event_id' => $booking->event_id,
                    'event_service_id' => $serviceItem['id'] ?? null,
                    'service_name' => $serviceItem['name'] ?? 'Event Service',
                    'quantity' => $serviceQuantity,
                    'price' => (float) ($serviceItem['price'] ?? 0),
                    'total_amount' => (float) ($serviceItem['total'] ?? ($serviceQuantity * (float) ($serviceItem['price'] ?? 0))),
                    'service_code' => 'SV-' . strtoupper(Str::random(10)),
                ]);

                app(EventServiceFieldResponseService::class)->sync(
                    $bookingService,
                    $serviceItem['field_responses'] ?? []
                );
            }
        }

        app(\App\Services\ServicePassService::class)->ensurePassesForBooking($booking);

        $hold->delete();

        return $booking->fresh(['services.fieldValues', 'services.passes', 'ageGroups', 'ticketType', 'event']);
    }

    private function sendConfirmedTicketEmail(TicketCounter $booking): void
    {
        if ($booking->ticket_email_sent_at) {
            return;
        }

        app(TicketPdfService::class)->sendTicketEmail($booking);

        $booking->update([
            'ticket_email_sent_at' => now(),
        ]);
    }

    // Payment complete and record the tickets in the table, then verify email before ticket mail.
    public function stripeSuccess(Request $request, ?PaymentTransaction $paymentTransaction = null)
    {
    $paymentTransactionId = $paymentTransaction?->id ?? $request->input('payment_transaction_id');
    $sessionId = $request->input('session_id') ?: $paymentTransaction?->gateway_session_id;

    if (!$sessionId) {
        return redirect()->route('website.events.index')->with('error', 'Missing payment session.');
    }

    Stripe::setApiKey(config('services.stripe.secret'));
    $session = Session::retrieve($sessionId);
    $paymentTransaction = $this->findPaymentTransactionForSession($session, $paymentTransactionId);
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

    $pendingBookingId = $session->metadata->pending_booking_id ?? null;
    if ($pendingBookingId) {
        $token = $session->metadata->hold_token ?? null;
        $checksum = $session->metadata->checksum ?? null;

        if (!$token || hash_hmac('sha256', $token, config('app.key')) !== $checksum) {
            abort(403, 'Invalid payment metadata');
        }

        if (($session->payment_status ?? null) !== 'paid') {
            TicketCounter::whereKey($pendingBookingId)->update([
                'payment_status' => $session->payment_status ?? 'failed',
                'booking_status' => TicketCounter::STATUS_FAILED,
                'refund_status' => TicketCounter::REFUND_PENDING,
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'gateway_session_id' => $session->id,
                'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
                'payment_failed_at' => now(),
                'payment_failure_reason' => 'Stripe returned payment_status=' . ($session->payment_status ?? 'unknown'),
            ]);

            return redirect()->route('website.events.index')
                ->with('error', 'Payment was not completed.');
        }

        try {
            $booking = DB::transaction(function () use ($pendingBookingId, $token, $session, $paymentTransaction, $transactionId, $paymentCompletedAt) {
                $booking = TicketCounter::whereKey($pendingBookingId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->payment_status === 'paid' && $booking->booking_status === TicketCounter::STATUS_CONFIRMED && $booking->bookedTickets()->exists()) {
                    return $booking;
                }

                $hold = TicketHold::where('pending_ticket_counter_id', $booking->id)
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->first();

                if (!$hold) {
                    throw new \RuntimeException('Checkout session expired. Please start again.');
                }

                return $this->finalizePendingCheckoutBooking($booking, $hold, [
                    'payment_status' => $session->payment_status,
                    'payment_method' => 'stripe',
                    'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'transaction_id' => $transactionId,
                    'gateway_session_id' => $session->id,
                    'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
                    'payment_initiated_at' => $paymentTransaction->initiated_at,
                    'payment_completed_at' => $paymentCompletedAt ?? now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Pending checkout finalization failed after Stripe', [
                'pending_booking_id' => $pendingBookingId,
                'payment_transaction_id' => $paymentTransaction->id,
                'error' => $e->getMessage(),
            ]);

            TicketCounter::whereKey($pendingBookingId)->update([
                'payment_status' => 'paid',
                'booking_status' => TicketCounter::STATUS_FAILED,
                'refund_status' => TicketCounter::REFUND_PENDING,
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'gateway_session_id' => $session->id,
                'gateway_payment_intent_id' => $this->stripeObjectId($session->payment_intent ?? null),
                'payment_completed_at' => $paymentCompletedAt ?? now(),
                'payment_failure_reason' => 'System Error: ' . $e->getMessage(),
            ]);

            return redirect()->route('website.events.index')
                ->with('error', 'A system error occurred. Our admin will review your payment for a refund.');
        }

        $this->rememberCheckoutAccess($booking);

        try {
            $this->sendConfirmedTicketEmail($booking);
        } catch (\Throwable $e) {
            Log::error('Ticket email failed after Stripe finalization', [
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('website.events.checkout.success.page', $booking->booking_id)
                ->with('warning', 'Payment successful, but ticket email could not be sent. Please contact support.');
        }

        return redirect()->route('website.events.checkout.success.page', $booking->booking_id);
    }

    $token      = $session->metadata->hold_token ?? null;
    $checksum   = $session->metadata->checksum ?? null;
    $metaCoupon = $session->metadata->coupon_code ?? null; 
    $phone      = $session->metadata->phone ?? null;
    $phonePrefix = $session->metadata->phone_prefix ?? null;
    $email      = $session->metadata->email ?? null;
    $name       = $session->metadata->name ?? null;
    $countryId  = $session->metadata->country_id ?? null;
    $stateId    = $session->metadata->state_id ?? null;
    $quantity = $session->metadata->quantity ?? 0;
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
    $bookingId = DB::transaction(function () use ($session, $token, $name, $email, $phonePrefix, $phone, $countryId, $stateId, $metaCoupon, $quantity, $selectedSeats, $paymentTransaction, $transactionId, $paymentCompletedAt) {
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

        if ($booking->booking_status !== TicketCounter::STATUS_CONFIRMED || !$booking->email_verified_at) {
            return redirect()
                ->route('website.events.checkout.prepay.verify', $booking->booking_id)
                ->with('warning', 'Please verify your email before viewing booking confirmation.');
        }

        if (!$booking->ticket_email_sent_at) {
            try {
                $this->sendConfirmedTicketEmail($booking);
                $booking->refresh();
            } catch (\Throwable $e) {
                Log::error('Ticket email retry failed on thank-you page', [
                    'booking_id' => $booking->booking_id,
                    'error' => $e->getMessage(),
                ]);

                session()->flash('warning', 'Booking confirmed, but ticket email could not be sent. Please contact support.');
            }
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


    public function stripeCancel(Request $request, ?PaymentTransaction $paymentTransaction = null)
    {
        $paymentTransaction ??= PaymentTransaction::find($request->input('payment_transaction_id'));

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
