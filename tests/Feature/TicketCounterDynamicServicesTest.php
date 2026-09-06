<?php

use App\Models\EventService;
use App\Models\TicketHold;
use App\Http\Controllers\Website\EventController as WebsiteEventController;
use App\Services\ServicePassService;
use App\Services\TicketPdfService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');

    Schema::create('currencies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code');
        $table->string('symbol');
        $table->unsignedInteger('decimal_places')->default(2);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('currency_id')->nullable();
        $table->string('title');
        $table->string('slug')->nullable();
        $table->boolean('enable_voting')->default(false);
        $table->boolean('status')->default(true);
        // Legacy columns deliberately remain in this isolated schema to prove
        // that ticket-counter requests no longer use them.
        $table->boolean('enable_car_parking')->default(false);
        $table->unsignedInteger('car_parking_slots')->nullable();
        $table->decimal('car_slot_price', 10, 2)->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('ticket_types', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('event_id');
        $table->string('title');
        $table->unsignedInteger('total_tickets')->default(0);
        $table->decimal('ticket_price', 10, 2)->default(0);
        $table->boolean('enable_bulk_discount')->default(false);
        $table->boolean('enable_tax')->default(false);
        $table->string('tax_label')->nullable();
        $table->decimal('tax_value', 10, 2)->default(0);
        $table->boolean('enable_extra_charges')->default(false);
        $table->string('extra_charges_label')->nullable();
        $table->decimal('extra_charges_value', 10, 2)->default(0);
        $table->boolean('enable_age_group')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('ticket_type_age_groups', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_type_id');
        $table->string('label');
        $table->decimal('price', 10, 2)->default(0);
        $table->unsignedInteger('total_tickets')->default(0);
        $table->unsignedInteger('max_quantity_per_booking')->default(20);
        $table->boolean('is_compulsory')->default(false);
        $table->unsignedInteger('order_index')->default(0);
        $table->timestamps();
    });
    Schema::create('ticket_counters', function (Blueprint $table) {
        $table->id();
        $table->string('booking_id')->unique();
        $table->unsignedBigInteger('event_id');
        $table->unsignedBigInteger('ticket_type_id');
        $table->unsignedInteger('qty');
        $table->json('selected_seats')->nullable();
        $table->boolean('bulk_discount_applied')->default(false);
        $table->boolean('coupon_applied')->default(false);
        $table->string('coupon_code')->nullable();
        $table->decimal('coupon_amount', 10, 2)->default(0);
        $table->decimal('coupon_percentage', 10, 2)->default(0);
        $table->decimal('total_amount', 10, 2)->default(0);
        $table->string('name');
        $table->string('email');
        $table->string('phone_prefix');
        $table->string('mobile_number');
        $table->unsignedBigInteger('country_id');
        $table->unsignedBigInteger('state_id');
        $table->string('payment_status');
        $table->string('booking_status');
        $table->string('refund_status')->nullable();
        $table->string('payment_method')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamp('ticket_email_sent_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('booked_tickets', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_counter_id');
        $table->string('booking_id');
        $table->string('ticket_number')->unique();
        $table->unsignedBigInteger('venue_layout_id')->nullable();
        $table->unsignedBigInteger('ticket_counter_age_group_id')->nullable();
        $table->unsignedBigInteger('ticket_type_age_group_id')->nullable();
        $table->string('sub_type_label')->nullable();
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('ticket_counter_age_groups', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_counter_id');
        $table->unsignedBigInteger('ticket_type_age_group_id')->nullable();
        $table->string('label');
        $table->unsignedInteger('quantity');
        $table->decimal('price', 10, 2);
        $table->decimal('total_amount', 10, 2);
        $table->timestamps();
    });
    Schema::create('event_services', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('event_id');
        $table->string('name');
        $table->unsignedInteger('available_quantity')->default(0);
        $table->unsignedInteger('max_buy_limit')->default(1);
        $table->decimal('price', 10, 2)->default(0);
        $table->boolean('is_mandatory')->default(false);
        $table->boolean('is_reserved')->default(false);
        $table->json('applicable_ticket_type_ids')->nullable();
        $table->boolean('status')->default(true);
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('ticket_counter_services', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_counter_id');
        $table->unsignedBigInteger('event_id');
        $table->unsignedBigInteger('event_service_id')->nullable();
        $table->string('service_name');
        $table->unsignedInteger('quantity');
        $table->decimal('price', 10, 2);
        $table->decimal('total_amount', 10, 2);
        $table->string('service_code')->nullable();
        $table->timestamps();
    });
    Schema::create('ticket_holds', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('event_id');
        $table->unsignedBigInteger('ticket_type_id');
        $table->unsignedInteger('quantity')->default(1);
        $table->json('selected_seats')->nullable();
        $table->decimal('total_amount', 10, 2)->nullable();
        $table->string('token')->unique();
        $table->json('service_items')->nullable();
        $table->json('age_group_items')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
    Schema::create('countries', function (Blueprint $table) {
        $table->unsignedBigInteger('id')->primary();
        $table->string('name');
    });
    Schema::create('states', function (Blueprint $table) {
        $table->unsignedBigInteger('id')->primary();
        $table->unsignedBigInteger('country_id');
        $table->string('name');
    });
    Schema::create('ticket_parkings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_counter_id');
        $table->string('car_number')->nullable();
    });

    (require database_path('migrations/2026_09_06_000003_create_event_service_fields_table.php'))->up();
    (require database_path('migrations/2026_09_06_000004_create_ticket_counter_service_field_values_table.php'))->up();

    DB::table('currencies')->insert([
        'id' => 1, 'name' => 'Euro', 'code' => 'EUR', 'symbol' => '€',
        'decimal_places' => 2, 'is_active' => true, 'is_default' => true,
    ]);
    DB::table('events')->insert([
        'id' => 10, 'currency_id' => 1, 'title' => 'Test Event', 'slug' => 'test-event',
        'enable_car_parking' => true, 'car_parking_slots' => 100, 'car_slot_price' => 999,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('ticket_types')->insert([
        'id' => 20, 'event_id' => 10, 'title' => 'General', 'total_tickets' => 100,
        'ticket_price' => 50, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('countries')->insert(['id' => 30, 'name' => 'Ireland']);
    DB::table('states')->insert(['id' => 40, 'country_id' => 30, 'name' => 'Dublin']);

    $this->mock(ServicePassService::class, function ($mock) {
        $mock->shouldReceive('ensurePassesForBooking')->andReturnNull();
    });
    $this->mock(TicketPdfService::class, function ($mock) {
        $mock->shouldReceive('sendTicketEmail')->andReturnNull();
    });
});

function createTicketCounterReservedService(): EventService
{
    $service = EventService::create([
        'event_id' => 10,
        'name' => 'Reserved Parking',
        'available_quantity' => 10,
        'max_buy_limit' => 3,
        'price' => 10,
        'is_reserved' => true,
        'status' => true,
    ]);
    $service->fields()->create([
        'field_label' => 'Vehicle Number',
        'field_key' => 'vehicle_number',
        'field_type' => 'text',
        'is_required' => true,
        'validation_type' => 'vehicle_number',
        'sort_order' => 0,
    ]);
    $service->fields()->create([
        'field_label' => 'Vehicle Type',
        'field_key' => 'vehicle_type',
        'field_type' => 'dropdown',
        'is_required' => true,
        'validation_type' => 'none',
        'options' => ['Car', 'Van'],
        'sort_order' => 1,
    ]);

    return $service->fresh('fields');
}

function ticketCounterBuyerPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'event_id' => 10,
        'ticket_type_id' => 20,
        'quantity' => 2,
        'name' => 'Counter Buyer',
        'email' => 'buyer@example.com',
        'phone_prefix' => '+353',
        'mobile_number' => '871234567',
        'country_id' => 30,
        'state_id' => 40,
    ], $overrides);
}

it('calculates only dynamic services and ignores legacy static parking input', function () {
    $service = createTicketCounterReservedService();
    $fieldIds = $service->fields->pluck('id')->values();

    $response = $this->postJson('/api/tickets/calculate-bill', ticketCounterBuyerPayload([
        'parking_slots' => 50,
        'car_details' => ['SHOULD-NOT-BE-USED'],
        'service_items' => [[
            'id' => $service->id,
            'quantity' => 2,
            'field_values' => [
                [$fieldIds[0] => '', $fieldIds[1] => ''],
                [$fieldIds[0] => '', $fieldIds[1] => ''],
            ],
        ]],
    ]));

    $response->assertOk()
        ->assertJsonPath('raw_subtotal', 100)
        ->assertJsonPath('raw_order_subtotal', 120)
        ->assertJsonPath('raw_total', 120)
        ->assertJsonMissingPath('parking_slots')
        ->assertJsonPath('service_items.0.name', 'Reserved Parking')
        ->assertJsonPath('service_items.0.quantity', 2);
});

it('validates and persists per-unit Reserved responses without creating static parking rows', function () {
    $service = createTicketCounterReservedService();
    [$vehicleNumberId, $vehicleTypeId] = $service->fields->pluck('id')->values()->all();

    $payload = ticketCounterBuyerPayload([
        'parking_slots' => 2,
        'car_details' => ['LEGACY-1', 'LEGACY-2'],
        'service_items' => [[
            'id' => $service->id,
            'quantity' => 2,
            'field_values' => [
                [$vehicleNumberId => 'DL01AB1234', $vehicleTypeId => 'Car'],
                [$vehicleNumberId => 'MH12ABC4321', $vehicleTypeId => 'Van'],
            ],
        ]],
    ]);

    $this->postJson('/api/tickets/purchase', $payload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('ticket_counter_services', [
        'event_service_id' => $service->id,
        'quantity' => 2,
        'total_amount' => 20,
    ]);
    $this->assertDatabaseHas('ticket_counter_service_field_values', [
        'unit_number' => 1,
        'field_key' => 'vehicle_number',
        'value' => json_encode('DL01AB1234'),
    ]);
    $this->assertDatabaseHas('ticket_counter_service_field_values', [
        'unit_number' => 2,
        'field_key' => 'vehicle_type',
        'value' => json_encode('Van'),
    ]);
    expect(DB::table('ticket_counter_service_field_values')->count())->toBe(4)
        ->and(DB::table('ticket_parkings')->count())->toBe(0);
});

it('rejects a Reserved purchase when any selected unit is missing a required response', function () {
    $service = createTicketCounterReservedService();
    [$vehicleNumberId, $vehicleTypeId] = $service->fields->pluck('id')->values()->all();

    $this->postJson('/api/tickets/purchase', ticketCounterBuyerPayload([
        'service_items' => [[
            'id' => $service->id,
            'quantity' => 2,
            'field_values' => [
                [$vehicleNumberId => 'DL01AB1234', $vehicleTypeId => 'Car'],
                [$vehicleNumberId => '', $vehicleTypeId => 'Van'],
            ],
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors("service_items.0.field_values.1.{$vehicleNumberId}");

    expect(DB::table('ticket_counters')->count())->toBe(0)
        ->and(DB::table('ticket_counter_services')->count())->toBe(0);
});

it('builds the website checkout quote from dynamic services and carries validated field snapshots', function () {
    $service = createTicketCounterReservedService();
    [$vehicleNumberId, $vehicleTypeId] = $service->fields->pluck('id')->values()->all();
    $event = \App\Models\Event::findOrFail(10);
    $ticketType = \App\Models\TicketType::findOrFail(20);
    $hold = TicketHold::create([
        'event_id' => 10,
        'ticket_type_id' => 20,
        'quantity' => 2,
        'token' => 'WEBSITE-CHECKOUT-HOLD',
        'expires_at' => now()->addMinutes(20),
    ]);

    $method = new ReflectionMethod(WebsiteEventController::class, 'prepareCheckoutQuote');
    $quote = $method->invoke(app(WebsiteEventController::class), $event, $ticketType, $hold, [
        'quantity' => 2,
        'service_items' => [[
            'id' => $service->id,
            'quantity' => 2,
            'field_values' => [
                [$vehicleNumberId => 'DL01AB1234', $vehicleTypeId => 'Car'],
                [$vehicleNumberId => 'MH12ABC4321', $vehicleTypeId => 'Van'],
            ],
        ]],
    ]);

    expect($quote['ticket_subtotal'])->toBe(100.0)
        ->and($quote['service_total'])->toBe(20.0)
        ->and($quote['final_amount'])->toBe(120.0)
        ->and($quote['service_items'][0]['field_values'][1][(string) $vehicleNumberId])->toBe('MH12ABC4321')
        ->and($quote['service_items'][0]['field_responses'])->toHaveCount(4)
        ->and($quote)->not->toHaveKeys(['parking_slots', 'parking_total', 'car_details']);
});
