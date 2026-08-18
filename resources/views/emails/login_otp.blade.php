<div>
    <h2>Checker's Account Verification Mail</h2>
    <div>
        <b>Name:</b> {{ $checker->name ?? 'User' }}<br>
        <b>Email:</b> {{ $checker->email ?? 'N/A' }}<br>
        <b style="font-size: 1.2em; color: #2d3748;">OTP: {{ $otp }}</b>
    </div>
    <div style="margin-top: 20px; color: #718096;">
        <p>Note: Please do not reply back to this email. This code will expire in 15 minutes.</p>
    </div>
</div>