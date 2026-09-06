<?php

use App\Models\EventService;
use App\Models\EventServiceField;
use App\Models\User;
use App\Http\Controllers\Admin\EventServiceController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Existing application migrations include MySQL-only statements. These tests
    // use an isolated in-memory schema and the new migration, never the local DB.
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');

    Schema::create('event_services', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('event_id');
        $table->string('name');
        $table->unsignedInteger('available_quantity');
        $table->unsignedInteger('max_buy_limit');
        $table->decimal('price', 10, 2);
        $table->boolean('is_mandatory')->default(false);
        $table->boolean('is_reserved')->default(false);
        $table->json('applicable_ticket_type_ids')->nullable();
        $table->boolean('status')->default(true);
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('slug');
    });
    Schema::create('role_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('permission_id');
    });
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->softDeletes();
    });
    Schema::create('ticket_types', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('event_id');
        $table->string('title');
        $table->softDeletes();
    });
    DB::table('events')->insert(['id' => 10]);

    (require database_path('migrations/2026_09_06_000003_create_event_service_fields_table.php'))->up();

    DB::table('permissions')->insert([
        ['id' => 1, 'slug' => 'event-services-manage-event-services'],
        ['id' => 2, 'slug' => 'event-services-manage-reserved'],
    ]);
    DB::table('role_permissions')->insert([
        ['role_id' => 1, 'permission_id' => 1],
        ['role_id' => 1, 'permission_id' => 2],
        ['role_id' => 2, 'permission_id' => 1],
    ]);

    $this->actingAs((new User)->forceFill(['id' => 1, 'role' => 1]));
    $this->withSession(['active_event_id' => 10]);
});

function reservedServicePayload(array $overrides = []): array
{
    return array_replace([
        'name' => 'Car Parking', 'available_quantity' => 100,
        'max_buy_limit' => 5, 'price' => '10.00', 'status' => 1,
        'is_reserved' => 1,
        'reserved_fields' => [[
            'field_label' => 'Car Number', 'field_type' => 'text',
            'is_required' => 1, 'validation_type' => 'custom',
            'validation_pattern' => '^[A-Z]{2}[0-9]{2}[A-Z]{1,2}[0-9]{4}$',
        ]],
    ], $overrides);
}

function createReservedServiceForTest(): EventService
{
    $service = EventService::create([
        'event_id' => 10, 'name' => 'Car Parking', 'available_quantity' => 100,
        'max_buy_limit' => 5, 'price' => '10.00', 'is_reserved' => true, 'status' => true,
    ]);
    $service->fields()->create([
        'field_label' => 'Car Number', 'field_key' => 'car_number',
        'field_type' => 'text', 'is_required' => true, 'validation_type' => 'none',
    ]);

    return $service;
}

it('saves Reserved fields with stable keys and ordered options', function () {
    $payload = reservedServicePayload();
    $payload['reserved_fields'][] = [
        'field_label' => 'Meal Preference', 'field_type' => 'dropdown',
        'validation_type' => 'none', 'options' => "Vegetarian\r\nVegan\nNon-Vegetarian",
    ];

    $this->post(route('admin.event.services.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();

    $service = EventService::firstOrFail();
    expect($service->is_reserved)->toBeTrue()
        ->and($service->fields)->toHaveCount(2)
        ->and($service->fields[0]->field_key)->toBe('car_number')
        ->and($service->fields[0]->is_required)->toBeTrue()
        ->and($service->fields[1]->options)->toBe(['Vegetarian', 'Vegan', 'Non-Vegetarian']);
});

it('requires fields when Reserved is enabled', function () {
    $this->post(route('admin.event.services.store'), reservedServicePayload(['reserved_fields' => []]))
        ->assertSessionHasErrors('reserved_fields');
    expect(EventService::count())->toBe(0);
});

it('rejects invalid configuration before saving any service changes', function (array $field, string $error) {
    $this->post(route('admin.event.services.store'), reservedServicePayload(['reserved_fields' => [$field]]))
        ->assertSessionHasErrors('reserved_fields.0.'.$error);
    expect(EventService::count())->toBe(0)->and(EventServiceField::count())->toBe(0);
})->with([
    'invalid regex' => [[
        'field_label' => 'Car Number', 'field_type' => 'text', 'validation_type' => 'custom',
        'validation_pattern' => '[',
    ], 'validation_pattern'],
    'empty dropdown' => [[
        'field_label' => 'Meal', 'field_type' => 'dropdown', 'validation_type' => 'none', 'options' => '',
    ], 'options'],
    'duplicate options' => [[
        'field_label' => 'Meal', 'field_type' => 'radio', 'validation_type' => 'none', 'options' => "Vegan\nVegan",
    ], 'options'],
    'reversed bounds' => [[
        'field_label' => 'Passengers', 'field_type' => 'number', 'validation_type' => 'number',
        'min_value' => 6, 'max_value' => 1,
    ], 'max_value'],
]);

it('keeps keys on rename and retains fields while Reserved is disabled', function () {
    $service = createReservedServiceForTest();
    $field = $service->fields()->first();
    $payload = reservedServicePayload(['reserved_fields' => [[
        'id' => $field->id, 'field_label' => 'Vehicle Registration Number',
        'field_type' => 'text', 'validation_type' => 'none', 'is_required' => 0,
    ]]]);
    $this->put(route('admin.event.services.update', $service), $payload)->assertSessionHasNoErrors();
    expect($field->fresh()->field_key)->toBe('car_number')
        ->and($field->fresh()->field_label)->toBe('Vehicle Registration Number');

    unset($payload['reserved_fields']);
    $payload['is_reserved'] = 0;
    $this->put(route('admin.event.services.update', $service), $payload)->assertSessionHasNoErrors();
    expect($service->fresh()->is_reserved)->toBeFalse()
        ->and($service->fields()->count())->toBe(1);
});

it('preserves Reserved configuration on a normal admin edit and rejects forged changes', function () {
    $service = createReservedServiceForTest();
    $this->actingAs((new User)->forceFill(['id' => 2, 'role' => 2]));
    $payload = reservedServicePayload(['name' => 'Updated Parking']);
    unset($payload['is_reserved'], $payload['reserved_fields']);

    $this->put(route('admin.event.services.update', $service), $payload)->assertSessionHasNoErrors();
    expect($service->fresh()->name)->toBe('Updated Parking')
        ->and($service->fresh()->is_reserved)->toBeTrue()
        ->and($service->fields()->first()->field_key)->toBe('car_number');

    $this->put(route('admin.event.services.update', $service), [...$payload, 'is_reserved' => 0])->assertForbidden();
    $this->put(route('admin.event.services.update', $service), [...$payload, 'reserved_fields' => []])->assertForbidden();
    $this->post(route('admin.event.services.store'), reservedServicePayload())->assertForbidden();
    expect(EventService::count())->toBe(1)->and($service->fresh()->is_reserved)->toBeTrue();
});

it('rejects a field belonging to another service and restores trusted edit metadata', function () {
    $service = createReservedServiceForTest();
    $otherService = createReservedServiceForTest();
    $payload = reservedServicePayload(['name' => 'Unwanted change', '_service_id' => $otherService->id]);
    $payload['reserved_fields'][0]['id'] = $otherService->fields()->first()->id;

    $this->put(route('admin.event.services.update', $service), $payload)
        ->assertSessionHasErrors('reserved_fields.0.id')
        ->assertSessionHas('_old_input._service_id', $service->id);
    expect($service->fresh()->name)->toBe('Car Parking')
        ->and($otherService->fields()->first()->field_label)->toBe('Car Number');
});

it('soft deletes removed fields and never reuses their keys', function () {
    $service = createReservedServiceForTest();
    $removedField = $service->fields()->first();

    $this->put(route('admin.event.services.update', $service), reservedServicePayload())->assertSessionHasNoErrors();

    expect($removedField->fresh()->trashed())->toBeTrue()
        ->and($service->fields()->first()->field_key)->toBe('car_number_2')
        ->and(EventServiceField::withTrashed()->count())->toBe(2);
});

it('loads retry fields only for an authorized admin and the active event', function () {
    $service = createReservedServiceForTest();
    $this->withSession(['_old_input' => ['_service_id' => $service->id]]);
    $request = Request::create('/admin/event-services');
    $request->setLaravelSession(app('session')->driver());

    $view = app(EventServiceController::class)->index($request)->getData();
    expect($view['reservedFieldsForRetry'][0]['field_key'])->toBe('car_number')
        ->and($view['services']->first()->relationLoaded('fields'))->toBeTrue();

    $this->actingAs((new User)->forceFill(['id' => 2, 'role' => 2]));
    $view = app(EventServiceController::class)->index($request)->getData();
    expect($view['reservedFieldsForRetry'])->toBe([])
        ->and($view['services']->first()->relationLoaded('fields'))->toBeFalse();

    $this->actingAs((new User)->forceFill(['id' => 1, 'role' => 1]));
    $service->update(['event_id' => 11]);
    $view = app(EventServiceController::class)->index($request)->getData();
    expect($view['reservedFieldsForRetry'])->toBe([]);
});
