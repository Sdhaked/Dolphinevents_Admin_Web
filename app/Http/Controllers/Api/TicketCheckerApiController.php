<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\TicketChecker;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TicketCheckerApiController extends Controller
{
    use ApiResponse;

    /**
     * 1. Login API - Verifies Password & Triggers OTP
     */
    public function login(Request $request)
    {
        // Password is required here, not OTP
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required', //
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($this->formatValidationErrors($validator), 422);
        }

        $checker = TicketChecker::where('email', $request->email)->first();

        // Check credentials based on ticket_checkers table
        if (!$checker || !Hash::check($request->password, $checker->password)) {
            return $this->errorResponse('Invalid Email or Password.', 401);
        }

        // Generate 6-digit OTP for the next screen
        $otp = rand(100000, 999999);
        $checker->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(15) //
        ]);

        // Send OTP via Mail
        Mail::to($checker->email)->send(new \App\Mail\LoginOtpMail($otp, $checker));

        return $this->successResponse([
            'email' => $checker->email
        ], 'OTP sent to your email');
    }

    /**
     * 2. Verify OTP API - Finalizes Login
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($this->formatValidationErrors($validator), 422);
        }

        // Include the event relationship immediately
        $checker = TicketChecker::with('event')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        // Check if OTP is valid and not expired
        if (!$checker || ($checker->otp_expires_at && now()->gt($checker->otp_expires_at))) {
            return $this->errorResponse('Invalid or expired OTP.', 401);
        }

        // Log device details and clear OTP
        $checker->update([
            'otp'           => null,
            'otp_expires_at' => null,
            'last_login_ip' => $request->ip(),
            'last_login_ua' => $request->userAgent(),
        ]);

        $token = $checker->createToken('checker_device')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user'  => [
                'name'  => $checker->name,
                'email' => $checker->email,
            ],
            'assigned_event' => $checker->event ? [
                'title'       => $checker->event->title,
                'from_date' => $checker->event->from_date,
                'to_date' => $checker->event->to_date,
                'from_time'  => $checker->event->from_time,
                'to_time'    => $checker->event->to_time,
                'address'    => $checker->event->address,
            ] : null,
        ], 'Login successful');
    }


    /**
     * View Profile API
     * Returns the profile details and assigned event for the authenticated checker.
     */
    public function viewProfile(Request $request)
    {
        // Get the authenticated user and load their assigned event
        $checker = $request->user();

        if (!$checker) {
            return $this->errorResponse('Unauthorized', 401);
        }

        return $this->successResponse([
            'user' => [
                'name'  => $checker->name,
                'email' => $checker->email,
            ],
            'assigned_event' => $checker->event ? [
                'title'     => $checker->event->title,
                'from_date' => $checker->event->from_date,
                'to_date'   => $checker->event->to_date,
                'from_time' => $checker->event->from_time,
                'to_time'   => $checker->event->to_time,
                'address'   => $checker->event->address,
            ] : null,
        ], 'Profile details retrieved successfully');
    }


    /**
     * Resend OTP API
     * Resend OTP on the email for the verification. 
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($this->formatValidationErrors($validator), 422);
        }

        $checker = TicketChecker::where('email', $request->email)->first();

        if (!$checker) {
            return $this->errorResponse('User not found.', 404);
        }

        // Generate new 6-digit OTP
        $otp = rand(100000, 999999);
        
        $checker->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(15)
        ]);

        // Re-send OTP via Mail
        try {
            Mail::to($checker->email)->send(new \App\Mail\LoginOtpMail($otp, $checker));
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send email. Please try again later.', 500);
        }

        return $this->successResponse([
            'email' => $checker->email
        ], 'A new OTP has been sent to your email.');
    }

    /**
     * Scan/Check Ticket API
     */
    public function checkTicket(Request $request)
    {
        // Get the authenticated user and load their assigned event
        $checker = $request->user();

        if (!$checker) {
            return $this->errorResponse('UNAUTHORIZED CHECKER', 401);
        }

        $validator = Validator::make($request->all(), [
            'ticket_number' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($this->formatValidationErrors($validator), 422);
        }

        // 1. Fetch the ticket with parent booking (including trashed ones)
        $ticket = \App\Models\BookedTicket::with([
            'booking' => function($query) {
                $query->withTrashed()->with(['event', 'ticketType']);
            }
        ])
        ->where('ticket_number', $request->ticket_number)
        ->first();

        // 2. EXISTENCE CHECK FIRST
        if (!$ticket || !$ticket->booking || !$ticket->booking->event) {
            return $this->errorResponse(['message' => 'INVALID TICKET'], 404);
        }

        // 3. EVENT START CHECK
        // if (!$this->hasEventStarted($ticket->booking->event)) {
        //     return $this->errorResponse(['message' => 'INVALID TICKET'], 404);
        // }

        // 3. TRASHED CHECK
        if ($ticket->booking && $ticket->booking->trashed()) {
            return $this->errorResponse(['message' => 'INVALID TICKET (ALERT)'], 404);
        }

        // 4. AUTHORIZATION CHECK
        if (!$ticket->booking || $ticket->booking->event_id !== $checker->event_id) {
            return $this->errorResponse('You\'re not authorized to scan this event ticket', 403);
        }

        // 5. STATUS CHECK (Already Scanned)
        if ($ticket->status === 'Scanned') {
            $mergedData = array_merge($ticket->toArray(), ['booking' => $ticket->booking]);
            return $this->successResponse(
                $mergedData, 
                'USED TICKET'
                //'Ticket is Valid! Already scanned at ' . ($ticket->scanned_at ? $ticket->scanned_at->format('h:i A') : 'N/A')
            );
        }

        // 6. UPDATE STATUS
        $ticket->update([
            'status'     => 'Scanned',
            'scanned_at' => now(),
            'scanned_by' => $checker->id,
        ]);

        $updatedData = array_merge($ticket->fresh(['booking.event', 'booking.ticketType'])->toArray(), [
            'booking' => $ticket->booking
        ]);

        return $this->successResponse($updatedData, 'VALID TICKET');
    }


    /**
     * Scan/Check Car Parking Ticket API
     */
    public function checkCarTicket(Request $request)
    {
        // Identify the checker via Authorization Bearer Token
        $checker = $request->user();

        if (!$checker) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $validator = Validator::make($request->all(), [
            'parking_code' => 'required', // Using parking_code as the unique identifier
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($this->formatValidationErrors($validator), 422);
        }

        // 1. Fetch the parking ticket with event and booking details
        // Assuming TicketParking has a 'booking' relationship
        $parkingTicket = \App\Models\TicketParking::with([
            'booking' => function($query) {
                $query->withTrashed()->with(['event']);
            }
        ])
        ->where('parking_code', $request->parking_code)
        ->first();

        // 2. EXISTENCE CHECK
        if (!$parkingTicket || !$parkingTicket->booking || !$parkingTicket->booking->event) {
            return $this->errorResponse(['message' => 'INVALID TICKET'], 404);
        }

        // if (!$this->hasEventStarted($parkingTicket->booking->event)) {
        //     return $this->errorResponse(['message' => 'Event has not started yet. Parking ticket cannot be scanned before the event start time.'], 403);
        // }

        // 3. TRASHED CHECK (Booking deleted/refunded)
        if ($parkingTicket->booking && $parkingTicket->booking->trashed()) {
            return $this->errorResponse(['message' => 'INVALID TICKET (ALERT)'], 404);
        }

        // 4. AUTHORIZATION CHECK (Event matching)
        if (!$parkingTicket->booking || $parkingTicket->booking->event_id !== $checker->event_id) {
            return $this->errorResponse('You\'re not authorized to scan for this event', 403);
        }

        // 5. STATUS CHECK (Already Scanned/Used)
        if ($parkingTicket->status === 'used') {
            $mergedData = array_merge($parkingTicket->toArray(), [
                'booking' => $parkingTicket->booking
            ]);
            
            return $this->successResponse(
                $mergedData, 
                'USED TICKET'
            );
        }

        // 6. UPDATE STATUS
        $parkingTicket->update([
            'status'     => 'used',
            'scanned_at' => now(),
            'scanned_by' => $checker->id,
        ]);

        // 7. PREPARE RESPONSE (Including fresh event data)
        $updatedData = array_merge($parkingTicket->fresh(['booking.event'])->toArray(), [
            'booking' => $parkingTicket->booking
        ]);

        return $this->successResponse($updatedData, 'Parking Ticket is Valid!');
    }

    protected function hasEventStarted(?Event $event): bool
    {
        $eventStartAt = $this->getEventStartAt($event);

        return $eventStartAt ? now()->greaterThanOrEqualTo($eventStartAt) : false;
    }

    protected function getEventStartAt(?Event $event): ?Carbon
    {
        if (!$event || !$event->from_date || !$event->from_time) {
            return null;
        }

        $date = $event->from_date instanceof Carbon
            ? $event->from_date->format('Y-m-d')
            : Carbon::parse($event->from_date)->format('Y-m-d');

        $time = $event->from_time instanceof Carbon
            ? $event->from_time->format('H:i:s')
            : Carbon::parse($event->from_time)->format('H:i:s');

        return Carbon::parse("{$date} {$time}");
    }

    //Logout API
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
