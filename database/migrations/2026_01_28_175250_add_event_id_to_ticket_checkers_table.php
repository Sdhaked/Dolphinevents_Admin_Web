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
        Schema::table('ticket_checkers', function (Blueprint $table) {
            // Adding the event_id column after 'id'
            // Using constrained() assumes your table is named 'events'
            $table->foreignId('event_id')
                  ->after('id') 
                  ->nullable() // Set to nullable if you have existing data
                  ->constrained('events')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_checkers', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};