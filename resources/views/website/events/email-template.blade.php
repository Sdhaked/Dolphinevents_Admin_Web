<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Confirmation - {{ $event->title }}</title>
    <style>
    body {
        background-color: #f8fafc;
        padding: 32px 16px;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        color: #1e293b;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        background-color: #f8fafc;
    }

    .card {
        background-color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .header {
        background: linear-gradient(135deg, #3b3b3bff, #1f1f1fff);
        color: white;
        padding: 24px;
        border-radius: 12px;
    }

    .info-row {
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 0;
    }

    .bill-table {
        font-size: 13px;
    }

    .bill-table thead tr th {
        background: #272727ff;
        color: white;
        padding: 0.8rem;
        font-weight: 600;
        text-align: left;
    }

    .bill-table tbody tr td {
        background-color: #f8fafc;
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
    }

    .bill-total {
        color: #1b1b2fff;
        padding: 16px;
        font-weight: 700;
    }

    .badge {
        background-color: #029a60ff;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
    }

    .ticket-type {
        background-color: #f1f5f9;
        color: #0f172a;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        border: 1px solid #e2e8f0;
    }

    .seat {
        background-color: #f1f5f9;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 14px;
        margin-right: 8px;
        display: inline-block;
    }

    .thankyou {
        background: linear-gradient(135deg, #01a96cff, #955cf9ff);
        border-radius: 12px;
        color: white;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        text-align: center;
        padding: 32px;
    }

    .notes {
        background-color: #f0f9ff;
        border-radius: 12px;
        padding: 24px;
    }

    .note-item {
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        line-height: 1.6;
    }

    .note-dot {
        width: 8px;
        height: 8px;
        background-color: #3b82f6;
        border-radius: 50%;
        margin-top: 8px;
        flex-shrink: 0;
    }

    .label {
        font-size: 12px;
        color: #64748b;
    }

    .value {
        font-size: 14px
    }

    .card-hd {
        margin: 0 0 16px 0;
        font-size: 18px;
        font-weight: 600;
    }
    </style>
</head>

<body>
    <div class="container">

        <div style="margin-bottom: 2rem;">
            <table style="margin-bottom: 1rem">
                <tr>
                    <td><img src="{{ public_path('website/images/logo.svg') }}" alt="{{ config('app.name') }}"
                            style="height: auto; width: 12rem;"></td>
                    <td style="text-align: right;"><span class="badge">🎫 Tickets Confirmed</span></td>
                </tr>
            </table>
            <div class="header">
                <h1 style="margin: 0 0 8px 0; font-size: 24px;">Dear {{ $booking->name }}, thank you for your booking! 🎉</h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px;">Your seats have been confirmed. Please find your
                    reservation details below.</p>
            </div>
        </div>

        @php
            $currency = $event?->currency_symbol ?? \App\Models\Currency::symbolForEvent($booking->event ?? null);
            $ticketPrice = (float) ($ticketType?->ticket_price ?? $booking->ticketType?->ticket_price ?? 0);
            $ticketQuantity = (int) ($booking->qty ?? 0);
            $parkingCount = $booking->parkings?->count() ?? 0;
            $parkingPrice = (float) ($event?->car_slot_price ?? 0);
            $subtotal = $ticketPrice * $ticketQuantity;
            $parkingTotal = $parkingCount * $parkingPrice;
            $discountAmount = (float) ($booking->coupon_amount ?? 0);
            $discountedTickets = max(0, $subtotal - $discountAmount);
            $taxableBasis = $discountedTickets + $parkingTotal;
            $taxAmount = 0;
            $extraChargesAmount = 0;

            if (($ticketType?->enable_tax ?? false) && (float) ($ticketType->tax_value ?? 0) > 0) {
                $taxAmount = ($taxableBasis * (float) $ticketType->tax_value) / 100;
            }

            if (($ticketType?->enable_extra_charges ?? false) && (float) ($ticketType->extra_charges_value ?? 0) > 0) {
                $extraChargesAmount = ($taxableBasis * (float) $ticketType->extra_charges_value) / 100;
            }

            $contestentVote = null;
            $votedContestent = null;

            if (($event?->enable_voting ?? false) && $booking?->booking_id) {
           
                $contestentVote = $booking->contestentVotes()
                    ->with('contestent')
                    ->where('event_id', $event->id)
                    ->latest()
                    ->first();

                $votedContestent = $contestentVote?->contestent;
            }
        @endphp
        <div class="card">
            <h2 class="card-hd">Booking Details</h2>
            <table>
                <tr>
                    <td class="info-row" style="width: 50%;">
                        <div class="label">Booking ID</div>
                        <div class="value">{{ $booking->booking_id }}</div>
                    </td>
                    <td class="info-row">
                        <div class="label">Booking Date & Time</div>
                        <div class="value">{{ $booking->created_at->format('jS F Y, g:i A') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="info-row">
                        <div class="label">Ticket Type</div>
                        <span class="ticket-type">{{ $ticketType->title }}</span>
                    </td>
                    <td class="info-row">
                        <div class="label">Number of Tickets</div>
                        <div class="value">{{ $booking->qty }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="info-row">
                        <div class="label">Ticket Seats</div>
                        <span class="seat">General Seating</span>
                    </td>
                    <td class="info-row">
                        <div class="label">Tickets</div>
                        <div class="value" style="font-weight: 600; color: #3b82f6;">PDF Attached Below</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
           <h2 class="card-hd">Event Details</h2>
           <table>
            <tr>
                <td class="info-row" style="width: 50%;">
                    <div class="label">Event Name</div>
                    <div class="value" style="font-weight: 600;">{{ $event->title }}</div>
                </td>
                <td class="info-row">
                    <div class="label">📅 Event Date</div>
                    <div class="value">
                        {{ \Carbon\Carbon::parse($event->from_date)->format('jS F Y') }} 
                        @if($event->to_date && $event->to_date != $event->from_date)
                            - {{ \Carbon\Carbon::parse($event->to_date)->format('jS F Y') }}
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td class="info-row">
                    <div class="label">🕒 Event Time</div>
                    <div class="value">
                        {{ \Carbon\Carbon::parse($event->from_time)->format('h:i A') }} - 
                        {{ \Carbon\Carbon::parse($event->to_time)->format('h:i A') }}
                    </div>
                </td>
                <td class="info-row">
                    <div class="label">📍 Event Location</div>
                    <div class="value">{{ $event->address ?? 'N/A' }}</div>
                </td>
            </tr>
            @if($contestentVote)
                <tr>
                    <td class="info-row" colspan="2">
                        <div class="label">You Voted</div>
                        <div class="value">
                            <div>{{ $votedContestent?->name ?? 'N/A' }}</div>
                            @if ($votedContestent?->email)
                                <div>{{ $votedContestent?->email ?? '' }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endif
            @if (($event?->enable_voting ?? false) && $booking?->booking_id)                
                <tr>
                    <td class="info-row" colspan="2">
                        <div class="value">
                            you can change your by clicking <a href="{{ config('app.url') }}events/voting/{{ $event->slug }}/verify" style="color: #3b82f6; font-weight: 600;">here</a>
                        </div>
                    </td>
                </tr>
            @endif
        </table>
        </div>

        <table>
            <tr>
                <td style="width: 48%; vertical-align: top;">
                    <div class="card">
                        <h2 class="card-hd">Event Support</h2>
                        <div class="info-row">
                            <div class="label">📞 Phone 1</div>
                            <div class="value">
                                @if($event->support && $event->support->phone_number)
                                    ({{ $event->support->phone_prefix ?? '+353' }}) {{ $event->support->phone_number }}
                                @else
                                    (+353) 1 234 5678
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="label">📞 Phone 2</div>
                            <div class="value">
                                @if($event->support && $event->support->secondary_phone_number)
                                    ({{ $event->support->secondary_phone_prefix ?? '+353' }}) {{ $event->support->secondary_phone_number }}
                                @else
                                    (+353) 87 654 3210
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="label">✉️ Email</div>
                            <div class="value">{{ $event->support->email ?? $event->support_email ?? 'support@bookmyseats.ie' }}</div>
                        </div>
                        <div>
                            <div class="label">📍 Address</div>
                            <div class="value">{{ $event->support->address ?? $event->address ?? 'Dublin, Ireland' }}</div>
                        </div>
                    </div>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top;">
                    <div class="card">
                        <h2 class="card-hd">Ticket Booked By</h2>
                        <div class="info-row">
                            <div class="label">👤 Name</div>
                            <div class="value">{{ $booking->name }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">📞 Phone</div>
                            <div class="value">{{ $booking->mobile_number }}</div>
                        </div>
                        <div>
                            <div class="label">✉️ Email</div>
                            <div class="value">{{ $booking->email }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="card">
            <h2 class="card-hd">💳 Bill Summary</h2>
            <table class="bill-table">
                <thead>
                    <tr>
                        <th>Ticket Name</th>
                        <th>Ticket QTY</th>
                        <th>Price ({{ $currency }})</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Original Subtotal Row --}}
                    <tr>
                        <td>{{ $ticketType->title }}</td>
                        <td>{{ $ticketQuantity }}</td>
                        <td>{{ $currency }}{{ number_format($ticketPrice, 2) }}</td>
                        <td>{{ $currency }}{{ number_format($subtotal, 2) }}</td>
                    </tr>

                    {{-- Subtotal Label --}}
                    <tr>
                        <td class="bill-total" colspan="3">Subtotal</td>
                        <td class="bill-total">{{ $currency }}{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    
                    {{-- Bulk Discount Row --}}
                    @if($booking->bulk_discount_applied == 1)
                    <tr>
                        <td class="bill-total" colspan="3">
                            Bulk Ticket Discount ({{ $booking->coupon_percentage }}% off)
                        </td>
                        <td class="bill-total" style="color: red;">
                            - {{ $currency }}{{ number_format((float)$booking->coupon_amount, 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Coupon Discount Row --}}
                    {{-- Checking if coupon_applied is not null based on your DB schema --}}
                    @if(!empty($booking->coupon_applied))
                    <tr>
                        <td class="bill-total" colspan="3">
                            Coupon: {{ $booking->coupon_code }} ({{ $booking->coupon_percentage }}% off)
                        </td>
                        <td class="bill-total" style="color: red;">
                            - {{ $currency }}{{ number_format((float)$booking->coupon_amount, 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Car Parking Summary --}}
                    @if($parkingCount > 0)
                    <tr>
                        <td class="bill-total" colspan="3">
                            Car Parking ({{ $parkingCount }} {{ Str::plural('Slot', $parkingCount) }})
                        </td>
                        <td class="bill-total" style="text-align: right;">
                            {{ $currency }}{{ number_format($parkingTotal, 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Tax Row --}}
                    @if($ticketType->enable_tax && $taxAmount > 0)
                    <tr>
                        <td class="bill-total" colspan="3">
                            {{ $ticketType->tax_label }} ({{ $ticketType->tax_value }}%)
                        </td>
                        <td class="bill-total" style="text-align: right;">
                            + {{ $currency }}{{ number_format($taxAmount, 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Extra Charges Row --}}
                    @if($ticketType->enable_extra_charges && $extraChargesAmount > 0)
                    <tr>
                        <td class="bill-total" colspan="3">
                            {{ $ticketType->extra_charges_label }} ({{ $ticketType->extra_charges_value }}%)
                        </td>
                        <td class="bill-total" style="text-align: right;">
                            + {{ $currency }}{{ number_format($extraChargesAmount, 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Grand Total --}}
                    <tr>
                        <td class="bill-total-final" colspan="3" style="font-weight: bold;">Total</td>
                        <td class="bill-total-final" style="text-align: right; font-weight: bold;">
                            {{ $currency }}{{ number_format($booking->total_amount, 2) }}
                        </td>
                    </tr>

                    {{-- Final Grand Total --}}
                    <tr>
                        <td class="bill-total" colspan="3" style="background: #edededff;">Grand Total</td>
                        <td class="bill-total" style="background: #edededff; font-size: 16px;">
                             {{ $currency }}{{ number_format($booking->total_amount, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="thankyou">
            <h2 style="margin: 0 0 8px 0; font-size: 24px;">⭐ Thank You for choosing us! ⭐</h2>
            <p style="margin: 0; opacity: 0.9; font-size: 16px;">We're excited to see you at the event!</p>
        </div>

        <div class="notes" style="margin-top: 16px;">
            <h2 class="card-hd">ℹ️ Important Notes</h2>
            <div class="note-item">
                <div class="note-dot"></div> <span>Please arrive at least 30 minutes before the event starts.</span>
            </div>
            <div class="note-item">
                <div class="note-dot"></div> <span>Bring a printed copy of tickets below or show it on your mobile
                    device at the entrance.</span>
            </div>
            <div class="note-item">
                <div class="note-dot"></div> <span>For any inquiries, contact us at {{ $event->support_email ?? 'support@bookmyseats.ie' }}</span>
            </div>
        </div>

    </div>
</body>

</html>
