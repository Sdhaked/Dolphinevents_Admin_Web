<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Service Pass</title>

    <style>
        body { font-family: "Segoe UI", sans-serif; padding: 20px; font-size: 14px; background-color: #f9f4ee; }
        .pdf-container { padding: 20px; }
        .ticket { width: 100%; background: #f9f4ee; }
        img { max-width: 100%; }
        p { margin: 0; }
        .main-hd { font-size: 18px; font-weight: bold; color: #08255B; margin: 0; padding-right: 20px; }
        .logo { width:150px; }
        .clear { clear: both; }
        .ticket-body { width: 100%; margin-top: 20px; }
        .left { width: 35%; display: inline-block; vertical-align: top; }
        .right { width: 60%; display: inline-block; vertical-align: top; margin-left: 4%; }
        .qr-box { background: #ffffff; padding: 10px; border-radius: 8px; margin-bottom: 20px; display: inline-block; }
        .event-detail-box { border: 3px dashed #08255B; padding: 20px; border-radius: 8px; }
        .details { margin-bottom: 15px; }
        .details h6 { margin: 0 0 5px; font-size: 14px; font-weight: 500; }
        .details p { font-weight: bold; margin: 0; }
        .footer { background: #ffffff; padding: 10px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>

<body>
<div class="pdf-container">
    @php
        $currency = \App\Models\Currency::symbolForEvent($event);
        $servicePasses = collect();
        $hasServicePassTable = \Illuminate\Support\Facades\Schema::hasTable('ticket_counter_service_passes');

        foreach ($services as $service) {
            $quantity = max(0, (int) $service->quantity);
            $baseCode = $service->service_code ?: ('SV-' . $booking->booking_id . '-' . $service->id);
            $passes = $hasServicePassTable
                ? $service->passes()->orderBy('unit_number')->orderBy('id')->get()
                : collect();

            if ($passes->isNotEmpty()) {
                foreach ($passes as $passRow) {
                    $servicePasses->push([
                        'service' => $service,
                        'service_code' => $passRow->service_code,
                        'unit' => $passRow->unit_number,
                        'quantity' => $quantity,
                    ]);
                }

                continue;
            }

            for ($unit = 1; $unit <= $quantity; $unit++) {
                $servicePasses->push([
                    'service' => $service,
                    'service_code' => $quantity > 1 ? $baseCode . '-' . str_pad((string) $unit, 2, '0', STR_PAD_LEFT) : $baseCode,
                    'unit' => $unit,
                    'quantity' => $quantity,
                ]);
            }
        }

        $totalItems = $servicePasses->count();
    @endphp

    @foreach($servicePasses as $index => $pass)
        @php
            $service = $pass['service'];
            $price = (float) $service->price;
            $serviceCode = $pass['service_code'];
        @endphp

        <div class="ticket" style="{{ ($index + 1) < $totalItems ? 'page-break-after: always;' : '' }}">
            <table style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="text-align: left;">
                        <span class="main-hd">{{ $event->title }}</span>
                    </td>
                    <td style="text-align: right; width: 160px;">
                        <img class="logo" src="{{ public_path('website/images/logo.svg') }}">
                    </td>
                </tr>
            </table>

            <div class="ticket-body">
                <div class="left">
                    <div>
                        <div class="qr-box">
                            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($serviceCode, 'QRCODE') }}" alt="Service QR" width="100" height="100">
                        </div>
                    </div>
                    <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #333;">SERVICE PASS</h4>

                    <div class="details">
                        <h6>Service Code</h6>
                        <p style="font-size: 12px;">{{ $serviceCode }}</p>
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
                            <h6>Service Name</h6>
                            <p style="color: #08255B;">{{ $service->service_name }}</p>
                        </div>

                        <div class="details">
                            <h6>Service Quantity</h6>
                            <p>{{ $pass['quantity'] > 1 ? $pass['unit'] . ' of ' . $pass['quantity'] : 1 }}</p>
                        </div>

                        <div class="details">
                            <h6>Service Amount</h6>
                            <p>{{ $currency }}{{ number_format($price, 2) }}/-</p>
                        </div>

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
                        This is your event service pass. Please present this pass at the service counter.
                    </p>
                </div>
                <div class="clear"></div>
            </div>

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
</body>
</html>
