<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_service_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_service_id')->constrained('event_services')->cascadeOnDelete();
            $table->string('field_label');
            $table->string('field_key');
            $table->string('field_type', 32);
            $table->boolean('is_required')->default(false);
            $table->string('validation_type', 32)->default('none');
            $table->text('validation_pattern')->nullable();
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('min_value', 16, 4)->nullable();
            $table->decimal('max_value', 16, 4)->nullable();
            $table->unsignedInteger('max_length')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['event_service_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_service_fields');
    }
};
