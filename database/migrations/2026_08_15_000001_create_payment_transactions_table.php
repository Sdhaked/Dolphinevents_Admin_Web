<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_counter_id')->nullable()->constrained('ticket_counters')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            $table->string('booking_id', 50)->nullable()->index();
            $table->string('hold_token')->nullable()->index();
            $table->string('gateway', 40)->default('stripe')->index();
            $table->string('gateway_session_id')->nullable()->unique();
            $table->string('gateway_payment_intent_id')->nullable()->index();
            $table->string('gateway_charge_id')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('status', 40)->default('initiated')->index();
            $table->string('gateway_payment_status', 40)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedInteger('quantity')->nullable();
            $table->json('selected_seats')->nullable();
            $table->unsignedInteger('parking_slots')->nullable();
            $table->json('car_details')->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable()->index();
            $table->string('phone_prefix', 20)->nullable();
            $table->string('mobile_number')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::table('ticket_counters', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_counters', 'payment_transaction_id')) {
                $table->foreignId('payment_transaction_id')->nullable()->after('payment_method')->constrained('payment_transactions')->nullOnDelete();
            }

            if (!Schema::hasColumn('ticket_counters', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_transaction_id')->index();
            }

            if (!Schema::hasColumn('ticket_counters', 'gateway_session_id')) {
                $table->string('gateway_session_id')->nullable()->after('transaction_id')->index();
            }

            if (!Schema::hasColumn('ticket_counters', 'gateway_payment_intent_id')) {
                $table->string('gateway_payment_intent_id')->nullable()->after('gateway_session_id')->index();
            }

            if (!Schema::hasColumn('ticket_counters', 'payment_initiated_at')) {
                $table->timestamp('payment_initiated_at')->nullable()->after('gateway_payment_intent_id');
            }

            if (!Schema::hasColumn('ticket_counters', 'payment_completed_at')) {
                $table->timestamp('payment_completed_at')->nullable()->after('payment_initiated_at');
            }

            if (!Schema::hasColumn('ticket_counters', 'payment_failed_at')) {
                $table->timestamp('payment_failed_at')->nullable()->after('payment_completed_at');
            }

            if (!Schema::hasColumn('ticket_counters', 'payment_cancelled_at')) {
                $table->timestamp('payment_cancelled_at')->nullable()->after('payment_failed_at');
            }

            if (!Schema::hasColumn('ticket_counters', 'payment_failure_reason')) {
                $table->text('payment_failure_reason')->nullable()->after('payment_cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_counters', 'payment_transaction_id')) {
                $table->dropForeign(['payment_transaction_id']);
            }

            foreach ([
                'payment_failure_reason',
                'payment_cancelled_at',
                'payment_failed_at',
                'payment_completed_at',
                'payment_initiated_at',
                'gateway_payment_intent_id',
                'gateway_session_id',
                'transaction_id',
                'payment_transaction_id',
            ] as $column) {
                if (Schema::hasColumn('ticket_counters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('payment_transactions');
    }
};
