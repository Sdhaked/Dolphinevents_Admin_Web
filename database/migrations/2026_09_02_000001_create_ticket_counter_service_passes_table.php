<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_counter_service_passes')) {
            return;
        }

        Schema::create('ticket_counter_service_passes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_counter_service_id')->index();
            $table->unsignedBigInteger('ticket_counter_id')->index();
            $table->unsignedBigInteger('event_id')->index();
            $table->unsignedBigInteger('event_service_id')->nullable()->index();
            $table->string('service_code', 60)->unique();
            $table->unsignedInteger('unit_number')->default(1);
            $table->string('status', 20)->default('unused')->index();
            $table->timestamp('scanned_at')->nullable();
            $table->unsignedBigInteger('scanned_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_counter_service_passes');
    }
};
