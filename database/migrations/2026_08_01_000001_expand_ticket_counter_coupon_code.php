<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Discount coupons allow codes up to 50 characters. Keep the booking
        // snapshot column in sync so valid coupons can always be persisted.
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->string('coupon_code', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->string('coupon_code', 20)->nullable()->change();
        });
    }
};
