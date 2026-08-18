<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            
            // Core Relationships
            $table->foreignId('ticket_counter_id')->constrained('ticket_counters')->onDelete('cascade');
            $table->string('booking_id')->index();
            $table->integer('venue_layout_id')->nullable();
            
            // Ticket Identity & Status
            $table->string('ticket_number')->unique()->index();
            $table->enum('status', ['Scanned', 'Not Scanned'])->default('Not Scanned');
            $table->timestamp('scanned_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('ticket_checkers')->onDelete('set null');
           
            // Timestamps (created_at and updated_at)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booked_tickets');
    }
};