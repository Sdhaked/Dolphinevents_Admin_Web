<div>
    <h2>Voting Verification OTP</h2>
    <div>
        <b>Event:</b> {{ $event->title ?? 'Event' }}<br>
        <b>Booking Id:</b> {{ $booking->booking_id ?? 'N/A' }}<br>
        <b>Name:</b> {{ $booking->name ?? 'User' }}<br>
        <b>Email:</b> {{ $booking->email ?? 'N/A' }}<br>
        <b style="font-size: 1.2em; color: #2d3748;">OTP: {{ $otp }}</b>
    </div>
    <div style="margin-top: 20px; color: #718096;">
        <p>Note: Please do not reply back to this email. This code will expire in 15 minutes.</p>
    </div>
</div>
