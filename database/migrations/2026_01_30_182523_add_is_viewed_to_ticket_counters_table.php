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
        Schema::table('ticket_counters', function (Blueprint $table) {
           // This correctly adds the boolean flag
           $table->boolean('is_viewed')->default(0)->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            // Added: Remove the column if migration is rolled back
            $table->dropColumn('is_viewed');
        });
    }
};