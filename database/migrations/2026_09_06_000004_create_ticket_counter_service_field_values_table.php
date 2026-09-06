<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_counter_service_field_values')) {
            return;
        }

        Schema::create('ticket_counter_service_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_counter_service_id');
            $table->unsignedBigInteger('event_service_field_id')->nullable();
            $table->unsignedInteger('unit_number')->default(1);
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type', 32);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['ticket_counter_service_id', 'unit_number', 'field_key'],
                'service_field_value_per_unit_unique'
            );
            $table->foreign('ticket_counter_service_id', 'svc_field_values_purchase_fk')
                ->references('id')
                ->on('ticket_counter_services')
                ->cascadeOnDelete();
            $table->foreign('event_service_field_id', 'svc_field_values_definition_fk')
                ->references('id')
                ->on('event_service_fields')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_counter_service_field_values');
    }
};
