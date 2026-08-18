<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_counters', 'booking_status')) {
                $table->string('booking_status', 40)->default('confirmed')->after('payment_status');
            }

            if (!Schema::hasColumn('ticket_counters', 'refund_status')) {
                $table->string('refund_status', 40)->default('not_required')->after('booking_status');
            }

            if (!Schema::hasColumn('ticket_counters', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_status');
            }
        });

        if (Schema::hasColumn('ticket_counters', 'booking_status')) {
            DB::table('ticket_counters')
                ->whereNull('booking_status')
                ->orWhere('booking_status', '')
                ->update(['booking_status' => 'confirmed']);

            if (Schema::hasColumn('ticket_counters', 'email_verified_at')) {
                DB::table('ticket_counters')
                    ->where('payment_method', 'stripe')
                    ->where('payment_status', 'paid')
                    ->whereNull('email_verified_at')
                    ->update(['booking_status' => 'pending_verification']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            foreach (['refunded_at', 'refund_status', 'booking_status'] as $column) {
                if (Schema::hasColumn('ticket_counters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
