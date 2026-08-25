<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Ticket</title>
    <style>
        body {font-family: "Segoe UI", sans-serif; padding: 20px; font-size: 14px; background-color: #f9f4ee; }
        .pdf-container { padding: 20px; }
        .ticket { width: 100%; background: #f9f4ee; }
        img { max-width: 100%; }
        p { margin: 0; }
        .main-hd { font-size: 18px; font-weight: bold; color: #08255B; margin: 0; padding-right: 20px; }
        .logo { width:150px; }
        .ticket-body { width: 100%; margin-top: 20px; }
        .left { width: 35%; display: inline-block; vertical-align: top; }
        .right { width: 60%; display: inline-block; vertical-align: top; margin-left: 4%; }
        .qr-box { background: #ffffff; padding: 10px; border-radius: 8px; margin-bottom: 20px; display: inline-block; }
        .event-detail-box { border: 3px dashed #08255B; padding: 20px; border-radius: 8px; }
        .details { margin-bottom: 15px; }
        .details h6 { font-family: "Segoe UI", sans-serif; margin: 0 0 5px; font-size: 14px; font-weight: 500; }
        .details p { font-weight: bold; }
        .footer { background: #ffffff; padding: 10px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="pdf-container">
    @php
        // 1. Fetch the individual tickets created in the controller
        $individualTickets = \App\Models\BookedTicket::where('ticket_counter_id', $booking->id)->orderBy('id')->get();
        $totalItems = $individualTickets->count();
        $ageGroupRows = collect($booking->ageGroups ?? [])
            ->filter(fn ($ageGroup) => filled($ageGroup->label) && (int) $ageGroup->quantity > 0)
            ->sortBy('id')
            ->values();
        $ageGroupSubTypeLabels = $ageGroupRows
            ->flatMap(fn ($ageGroup) => array_fill(0, (int) $ageGroup->quantity, trim((string) $ageGroup->label)))
            ->values();
        $ageGroupFallbackLabel = $ageGroupRows
            ->pluck('label')
            ->map(fn ($label) => trim((string) $label))
            ->unique()
            ->implode(', ');
    @endphp

    @foreach($individualTickets as $index => $ticket)
        @php
            // 2. Resolve the seat label if this is a seating event
            $seatLabel = null;
            if ($ticket->venue_layout_id) {
                $seatInfo = \App\Models\VenueLayout::find($ticket->venue_layout_id);
                $seatLabel = $seatInfo ? "{$seatInfo->wing}-{$seatInfo->row}{$seatInfo->seat_number}" : "Seat #{$ticket->venue_layout_id}";
            }
            $ticketSubTypeLabel = $ticket->sub_type_label ?: ($ageGroupSubTypeLabels->get($index) ?: $ageGroupFallbackLabel);
        @endphp
        <div class="ticket" style="{{ ($index + 1) < $totalItems ? 'page-break-after: always;' : '' }} margin-bottom: 30px;">
            <!-- <div class="hd-top-holder">
                <span class="main-hd">{{ $event->title }}</span>
                <img class="logo" src="{{ public_path('website/images/logo.svg') }}">
                <div class="clear"></div>
            </div> -->

            <table style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="text-align: left; vertical-align: bottom;">
                        <span class="main-hd">{{ $event->title }}</span>
                    </td>
                    <td style="text-align: right; width: 100px;">
                        <img class="logo" src="{{ public_path('website/images/logo.svg') }}">
                    </td>
                </tr>
            </table>

            <div class="ticket-body">
                <div class="left">
                    <div>
                        <div class="qr-box">
                            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($ticket->ticket_number, 'QRCODE') }}" alt="QR" width="100" height="100">
                        </div>
                    </div>
                    <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #333;">EVENT TICKET</h4>
                    <div class="details">
                        <h6>Ticket Number</h6>
                        <p style="font-size: 12px;">{{ $ticket->ticket_number }}</p>
                    </div>
                    <div class="details">
                        <h6>Booking ID</h6>
                        <p>{{ $booking->booking_id }}</p>
                    </div>
                    <div class="details">
                        <h6>Ticket Booked On</h6>
                        <p>{{ $booking->created_at->format('d M Y \a\t h:i A') }}</p>
                    </div>
                </div>

                <div class="right">
                    <div class="event-detail-box">
                        <div class="details">
                            <h6>Event Date</h6>
                            <p>{{ \Carbon\Carbon::parse($event->from_date)->format('d M Y') }} @if($event->to_date) To {{ \Carbon\Carbon::parse($event->to_date)->format('d M Y') }} @endif</p>
                        </div>
                        <div class="details">
                            <h6>Event Time</h6>
                            <p>{{ \Carbon\Carbon::parse($event->from_time)->format('h:i A') }} @if($event->to_time) To {{ \Carbon\Carbon::parse($event->to_time)->format('h:i A') }} @endif</p>
                        </div>
                        <div class="details">
                            <h6>Event Location</h6>
                            <p>{{ $event->address ?? 'Venue TBA' }}</p>
                        </div>
                        <div class="details">
                            <h6>Ticket Type</h6>
                            <p style="color: #08255B;">{{ $ticketType->title }}</p>
                        </div>
                        @if($ticketSubTypeLabel)
                            <div class="details">
                                <h6>Sub Type</h6>
                                <p>{{ $ticketSubTypeLabel }}</p>
                            </div>
                        @endif
                        @if(($booking->services?->count() ?? 0) > 0)
                        <div class="details">
                            <h6>Event Services</h6>
                            @foreach($booking->services as $service)
                                <p>{{ $service->service_name }}: {{ $service->quantity }}</p>
                            @endforeach
                        </div>
                        @endif
                        @if($seatLabel)
                        <div class="details">
                            <h6>Seat Assignment</h6>
                            <p style="font-size: 14px; color: #dc2926;">{{ $seatLabel }}</p>
                        </div>
                        @else
                        {{-- <div class="details">
                            <h6>Ticket Entry</h6>
                            <p style="font-size: 16px;">General Admission ({{ $index + 1 }} of {{ $totalItems }})</p>
                        </div> --}}
                        @endif
                        <div class="details" style="margin-bottom: 0;">
                            <h6>Purchased By</h6>
                            <p>Name: {{ $booking->name }}</p>
                            <p>Email: {{ $booking->email }}</p>
                            <p>Phone: {{ $booking->mobile_number }}</p>
                        </div>
                    </div>
                   
                </div>
                <div>
                     <p style="margin-top: 20px; line-height: 1.4;">
                        This is your event ticket. Ticket holders must present their tickets on entry. 
                        Note that this ticket is non-refundable and non-transferable.
                    </p>
                </div>
                <div class="clear"></div>
            </div>
            {{-- FOOTER --}}
            @if($event->event_pdf_sponser_image)
                <div class="footer">
                    @php
                        $path = public_path('storage/' . $event->event_pdf_sponser_image);
                    @endphp

                    @if(file_exists($path))
                        <img src="data:image/{{ pathinfo($path, PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents($path)) }}" style="width: 100%; height: auto;">
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
</body></html>
