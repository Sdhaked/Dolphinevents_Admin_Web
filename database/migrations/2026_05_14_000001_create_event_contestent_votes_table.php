<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_contestent_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_contestent_id')->constrained('event_contestents')->cascadeOnDelete();
            $table->foreignId('ticket_counter_id')->nullable()->constrained('ticket_counters')->nullOnDelete();
            $table->string('booking_id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();

            $table->unique(['event_id', 'booking_id']);
            $table->index(['event_contestent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_contestent_votes');
    }
};
