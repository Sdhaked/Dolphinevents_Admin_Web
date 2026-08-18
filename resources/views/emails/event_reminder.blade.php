<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Reminder Email</title>
</head>
<body style="font-family: Arial, sans-serif; margin:0; padding:0; color:#e5e5e5; background:#0d0d0d;">
    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
        <tr>
            <td>
                <div style="position: relative; height: 200px; background: linear-gradient(90deg, #2e2e2ecb, #58565691), url({{ url('website/images/event-reminder.jpg') }}); background-size: cover; background-position: center; background-repeat: no-repeat;">
                    <img src="{{ url('website/images/logo.svg') }}" alt="BookMySeats Logo"
                        style="max-width:240px; width:95%; height:auto; position:absolute; z-index:2; top:50%; left:50%; transform:translate(-50%, -50%);">
                </div>
            </td>
        </tr>

        <tr>
            <td align="center" style="background:#172448; text-align:center; padding:24px; color:#fff;">
                <div style="background: linear-gradient(90deg, #37B6FF, #8961F9); text-align: center; color: #fff; border-radius:6px; padding:32px 16px;">
                    <div style="font-size:26px; font-weight:bold; margin-bottom:27px;">{{ $countdown }} to Go!</div>
                    <div style="font-size:14px; font-weight:500; color:#fff;">Don't Miss Out!</div>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:32px;">
                <h2 style="font-size:24px; font-weight:bold; margin-bottom:16px; color:#fff;">Dear {{ $customer->name ?? 'Guest' }},</h2>
                <p style="font-size:18px; line-height:1.6; color:#bbb;">
                    Just a friendly reminder <span style="font-weight:bold; color:#00bfff;">{{ $event->title }}</span> is only
                    <span style="font-weight:bold; color:#00bfff;">{{ $countdown }}</span> away!
                    Get ready for an unforgettable event.
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding:0 32px 24px;">
                <div style="background:#1a1a1a; border:1px solid #333; border-radius:8px; padding:24px;">
                    <h3 style="margin:0 0 8px; font-weight:600; color:#fff;">Event Details</h3>
                    <p style="margin:4px 0; color:#ccc; font-size:14px;"><strong style="font-weight:500;">Date:</strong>
                        {{ \Carbon\Carbon::parse($event->from_date)->format('d-M-Y') }}</p>
                    <p style="margin:4px 0; color:#ccc; font-size:14px;"><strong style="font-weight:500;">Time:</strong>
                        {{ $event->from_time }} to {{ $event->to_time }}</p>
                    <p style="margin:4px 0; color:#ccc; font-size:14px;"><strong style="font-weight:500;">Event Name:</strong> 
                        {{ $event->title }}</p>
                    <p style="margin:4px 0; color:#ccc; font-size:14px;"><strong style="font-weight:500;">Location:</strong> 
                        {{ $event->address }}</p>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:0 32px 24px;">
                <div style="padding:16px; border-radius:8px; margin-bottom:16px; border:1px solid rgba(0,128,255,0.3); background:rgba(0,128,255,0.1); color:#ddd;">
                    <table>
                        <tr>
                            <td style="width:40px;">&#128205;</td>
                            <td>Please plan to arrive early to allow time for entry procedures and wristband collection.</td>
                        </tr>
                    </table>
                </div>
                <div style="padding:16px; border-radius:8px; margin-bottom:16px; border:1px solid rgba(0,200,0,0.3); background:rgba(0,200,0,0.1); color:#ddd;">
                    <table>
                        <tr>
                            <td style="width:40px;">&#127915;</td>
                            <td>Don't forget to bring the <strong>QR code</strong> you received via email for entry.</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:24px 32px; border-top:1px solid #333; text-align:center; color:#aaa;">
                <p>With warm regards,</p>
                <p style="font-weight:600; color:#fff;">Team BookMySeats</p>
            </td>
        </tr>
    </table>
</body>
</html>