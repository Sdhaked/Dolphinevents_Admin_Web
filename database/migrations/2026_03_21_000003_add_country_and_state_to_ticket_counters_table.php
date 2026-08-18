<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('mobile_number')->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
