<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->string('phone_prefix', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->dropColumn('phone_prefix');
        });
    }
};
