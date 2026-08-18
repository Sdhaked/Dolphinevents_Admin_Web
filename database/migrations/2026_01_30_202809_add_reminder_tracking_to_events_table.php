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
        Schema::table('events', function (Blueprint $table) {
            // Stores the date and time of the last blast
            $table->timestamp('last_reminder_sent_at')->nullable()->after('status');
            // Stores the name of the Admin who clicked the button
            $table->string('last_reminded_by')->nullable()->after('last_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['last_reminder_sent_at', 'last_reminded_by']);
        });
    }
};