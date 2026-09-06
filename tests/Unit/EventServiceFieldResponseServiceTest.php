<?php

use App\Models\EventService;
use App\Models\TicketCounterService;
use App\Services\EventServiceFieldResponseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');

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

    (require database_path('migrations/2026_09_06_000003_create_event_service_fields_table.php'))->up();
    (require database_path('migrations/2026_09_06_000004_create_ticket_counter_service_field_values_table.php'))->up();
});

function responseTestService(): EventService
{
    $service = EventService::create([
        'event_id' => 1,
        'name' => 'Reserved Service',
        'available_quantity' => 10,
        'max_buy_limit' => 5,
        'price' => 20,
        'is_reserved' => true,
        'status' => true,
    ]);

    $service->fields()->create([
        'field_label' => 'Reference Number',
        'field_key' => 'reference_number',
        'field_type' => 'text',
        'is_required' => true,
        'validation_type' => 'custom',
        'validation_pattern' => '^[A-Z]{2}[0-9]{3}$',
        'error_message' => 'Enter a valid reference number.',
        'sort_order' => 0,
    ]);

    $service->fields()->create([
        'field_label' => 'Preference',
        'field_key' => 'preference',
        'field_type' => 'dropdown',
        'is_required' => false,
        'validation_type' => 'none',
        'options' => ['First', 'Second'],
        'sort_order' => 1,
    ]);

    return $service->load('fields');
}

it('validates and snapshots a separate field response for every purchased unit', function () {
    $service = responseTestService();
    [$reference, $preference] = $service->fields;

    $responses = app(EventServiceFieldResponseService::class)->validateAndNormalize(
        $service,
        2,
        [
            [$reference->id => 'AB123', $preference->id => 'First'],
            [$reference->id => 'CD456', $preference->id => 'Second'],
        ],
        'service_items.0'
    );

    expect($responses)->toHaveCount(4)
        ->and($responses[0]['unit_number'])->toBe(1)
        ->and($responses[0]['field_key'])->toBe('reference_number')
        ->and($responses[0]['value'])->toBe('AB123')
        ->and($responses[2]['unit_number'])->toBe(2)
        ->and($responses[2]['value'])->toBe('CD456');
});

it('allows empty required values during bill preview but rejects them on checkout submission', function () {
    $service = responseTestService();

    $preview = app(EventServiceFieldResponseService::class)->validateAndNormalize(
        $service,
        1,
        [],
        'service_items.0',
        strict: false
    );

    expect($preview)->toHaveCount(2)->and($preview[0]['value'])->toBeNull();

    try {
        app(EventServiceFieldResponseService::class)->validateAndNormalize(
            $service,
            1,
            [],
            'service_items.0'
        );
        $this->fail('Expected validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('service_items.0.field_values.0.'.$service->fields[0]->id);
    }
});

it('rejects invalid custom values, invalid options and fields from another service', function (string $case) {
    $service = responseTestService();
    [$reference, $preference] = $service->fields;
    [$resolvedValues, $errorFieldId] = match ($case) {
        'custom' => [[$reference->id => 'wrong', $preference->id => 'First'], $reference->id],
        'option' => [[$reference->id => 'AB123', $preference->id => 'Unknown'], $preference->id],
        'foreign' => [[$reference->id => 'AB123', 99999 => 'forged'], 99999],
    };

    try {
        app(EventServiceFieldResponseService::class)->validateAndNormalize(
            $service,
            1,
            [$resolvedValues],
            'service_items.3'
        );
        $this->fail('Expected validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('service_items.3.field_values.0.'.$errorFieldId);
    }
})->with([
    'custom pattern' => ['custom'],
    'dropdown option' => ['option'],
    'foreign field' => ['foreign'],
]);

it('persists immutable field snapshots against the purchased service', function () {
    $service = responseTestService();
    [$reference] = $service->fields;
    $responses = app(EventServiceFieldResponseService::class)->validateAndNormalize(
        $service,
        1,
        [$reference->id => 'AB123'],
        'service_items.0'
    );

    $purchase = TicketCounterService::create([
        'ticket_counter_id' => 10,
        'event_id' => 1,
        'event_service_id' => $service->id,
        'service_name' => $service->name,
        'quantity' => 1,
        'price' => 20,
        'total_amount' => 20,
    ]);

    app(EventServiceFieldResponseService::class)->sync($purchase, $responses);
    $reference->update(['field_label' => 'Renamed Later']);

    expect($purchase->fieldValues()->count())->toBe(2)
        ->and($purchase->fieldValues()->first()->field_label)->toBe('Reference Number')
        ->and($purchase->fieldValues()->first()->value)->toBe('AB123');
});
