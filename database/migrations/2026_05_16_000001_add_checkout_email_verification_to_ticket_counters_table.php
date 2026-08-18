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
            if (!Schema::hasColumn('ticket_counters', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }

            if (!Schema::hasColumn('ticket_counters', 'ticket_email_sent_at')) {
                $table->timestamp('ticket_email_sent_at')->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('ticket_counters', 'checkout_otp_hash')) {
                $table->string('checkout_otp_hash')->nullable()->after('ticket_email_sent_at');
            }

            if (!Schema::hasColumn('ticket_counters', 'checkout_otp_expires_at')) {
                $table->timestamp('checkout_otp_expires_at')->nullable()->after('checkout_otp_hash');
            }

            if (!Schema::hasColumn('ticket_counters', 'checkout_otp_resend_available_at')) {
                $table->timestamp('checkout_otp_resend_available_at')->nullable()->after('checkout_otp_expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $columns = [
                'checkout_otp_resend_available_at',
                'checkout_otp_expires_at',
                'checkout_otp_hash',
                'ticket_email_sent_at',
                'email_verified_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ticket_counters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
