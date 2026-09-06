<?php

use App\Mail\EventTicketMail;
use App\Models\Currency;
use App\Models\Event;
use App\Models\TicketCounter;
use App\Models\TicketCounterService;
use App\Models\TicketCounterServiceFieldValue;
use App\Models\TicketCounterServicePass;
use Illuminate\Support\Facades\Route;

it('keeps legacy static parking fields out of event input and JSON output', function () {
    $event = new Event();
    $event->fill([
        'title' => 'Dynamic services event',
        'enable_car_parking' => true,
        'car_parking_slots' => 20,
        'car_slot_price' => 50,
    ]);

    expect($event->getAttributes())
        ->toHaveKey('title')
        ->not->toHaveKeys(['enable_car_parking', 'car_parking_slots', 'car_slot_price']);

    $event->setRawAttributes([
        'id' => 1,
        'title' => 'Legacy event',
        'enable_car_parking' => 1,
        'car_parking_slots' => 20,
        'car_slot_price' => 50,
    ]);

    expect($event->toArray())
        ->not->toHaveKeys(['enable_car_parking', 'car_parking_slots', 'car_slot_price']);
});

it('does not expose static parking controls or a parking scanner endpoint', function () {
    $eventForm = file_get_contents(resource_path('views/admin/events/edit.blade.php'));
    $legacyRoute = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/checker/check-car-ticket');

    expect($eventForm)
        ->not->toContain('name="enable_car_parking"')
        ->not->toContain('name="car_parking_slots"')
        ->not->toContain('name="car_slot_price"')
        ->and($legacyRoute)->toBeNull();
});

it('attaches only the event ticket and dynamic service pass PDFs', function () {
    $ticketPath = tempnam(sys_get_temp_dir(), 'event-ticket-');
    $servicePath = tempnam(sys_get_temp_dir(), 'service-pass-');

    try {
        $mail = (new EventTicketMail(
            (object) ['booking_id' => 'BOOK-1'],
            (object) ['title' => 'Dynamic Services Event'],
            null,
            $ticketPath,
            $servicePath,
        ))->build();

        $attachmentNames = collect($mail->attachments)
            ->pluck('options.as')
            ->values()
            ->all();

        expect($attachmentNames)->toBe([
            'Tickets-BOOK-1.pdf',
            'Service-Passes-BOOK-1.pdf',
        ]);
    } finally {
        @unlink($ticketPath);
        @unlink($servicePath);
    }
});

it('renders purchased dynamic service field values on each service pass', function () {
    $event = (new Event())->forceFill([
        'title' => 'Dynamic Services Event',
        'from_date' => '2026-09-10',
        'to_date' => '2026-09-10',
        'from_time' => '10:00:00',
        'to_time' => '12:00:00',
        'address' => 'Test Venue',
    ]);
    $event->setRelation('currency', (new Currency())->forceFill([
        'symbol' => '₹',
        'code' => 'INR',
        'decimal_places' => 2,
    ]));

    $booking = (new TicketCounter())->forceFill([
        'booking_id' => 'BOOK-1',
        'name' => 'Test Buyer',
        'email' => 'buyer@example.test',
        'mobile_number' => '9876543210',
        'created_at' => now(),
    ]);

    $service = (new TicketCounterService())->forceFill([
        'id' => 10,
        'service_name' => 'Vehicle Access',
        'quantity' => 1,
        'price' => 100,
        'service_code' => 'SV-TEST',
    ]);
    $service->setRelation('passes', collect([
        (new TicketCounterServicePass())->forceFill([
            'service_code' => 'SV-TEST',
            'unit_number' => 1,
        ]),
    ]));
    $service->setRelation('fieldValues', collect([
        (new TicketCounterServiceFieldValue())->forceFill([
            'unit_number' => 1,
            'field_label' => 'Vehicle Registration',
            'field_type' => 'text',
            'value' => 'RJ14AB1234',
        ]),
    ]));

    $html = view('website.events.event-service-pdf', [
        'booking' => $booking,
        'event' => $event,
        'ticketType' => null,
        'services' => collect([$service]),
    ])->render();

    expect($html)
        ->toContain('Vehicle Access')
        ->toContain('Vehicle Registration')
        ->toContain('RJ14AB1234');
});
