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
        Schema::table('ticket_type_seats', function (Blueprint $table) {
            // Foreign key to the main bookings table (ticket_counters)
            $table->foreignId('ticket_counter_id')
                  ->nullable()
                  ->constrained('ticket_counters')
                  ->onDelete('set null'); // Keeps the seat record even if booking is deleted for history

            // Redundant but useful for quick lookups and receipts
            $table->string('booking_id')->nullable()->index(); 
            
            // Timestamp of when the seat was officially taken
            $table->timestamp('booked_at')->nullable();

            // Ensure is_booked exists (if you haven't added it yet)
            if (!Schema::hasColumn('ticket_type_seats', 'is_booked')) {
                $table->boolean('is_booked')->default(false)->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_type_seats', function (Blueprint $table) {
            $table->dropForeign(['ticket_counter_id']);
            $table->dropColumn(['ticket_counter_id', 'booking_id', 'booked_at', 'is_booked']);
        });
    }
};