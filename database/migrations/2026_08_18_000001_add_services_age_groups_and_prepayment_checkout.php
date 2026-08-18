<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_services')) {
            Schema::create('event_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->index();
                $table->string('name');
                $table->unsignedInteger('available_quantity')->default(0);
                $table->unsignedInteger('max_buy_limit')->default(1);
                $table->decimal('price', 10, 2)->default(0);
                $table->boolean('is_mandatory')->default(false);
                $table->json('applicable_ticket_type_ids')->nullable();
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('ticket_counter_services')) {
            Schema::create('ticket_counter_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_counter_id')->index();
                $table->unsignedBigInteger('event_id')->index();
                $table->unsignedBigInteger('event_service_id')->nullable()->index();
                $table->string('service_name');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('service_code', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_type_age_groups')) {
            Schema::create('ticket_type_age_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_type_id')->index();
                $table->string('label');
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedInteger('total_tickets')->default(0);
                $table->unsignedInteger('max_quantity_per_booking')->default(20);
                $table->boolean('is_compulsory')->default(false);
                $table->unsignedInteger('order_index')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_counter_age_groups')) {
            Schema::create('ticket_counter_age_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_counter_id')->index();
                $table->unsignedBigInteger('ticket_type_age_group_id')->nullable()->index();
                $table->string('label');
                $table->unsignedInteger('quantity')->default(0);
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ticket_types') && !Schema::hasColumn('ticket_types', 'enable_age_group')) {
            Schema::table('ticket_types', function (Blueprint $table) {
                $table->boolean('enable_age_group')->default(false)->after('enable_extra_charges');
            });
        }

        if (Schema::hasTable('ticket_holds')) {
            Schema::table('ticket_holds', function (Blueprint $table) {
                if (!Schema::hasColumn('ticket_holds', 'service_items')) {
                    $table->json('service_items')->nullable()->after('coupon_code');
                }

                if (!Schema::hasColumn('ticket_holds', 'age_group_items')) {
                    $table->json('age_group_items')->nullable()->after('service_items');
                }

                if (!Schema::hasColumn('ticket_holds', 'parking_slots')) {
                    $table->unsignedInteger('parking_slots')->default(0)->after('age_group_items');
                }

                if (!Schema::hasColumn('ticket_holds', 'car_details')) {
                    $table->json('car_details')->nullable()->after('parking_slots');
                }

                if (!Schema::hasColumn('ticket_holds', 'checkout_otp_hash')) {
                    $table->string('checkout_otp_hash')->nullable()->after('car_details');
                }

                if (!Schema::hasColumn('ticket_holds', 'checkout_otp_expires_at')) {
                    $table->timestamp('checkout_otp_expires_at')->nullable()->after('checkout_otp_hash');
                }

                if (!Schema::hasColumn('ticket_holds', 'checkout_otp_resend_available_at')) {
                    $table->timestamp('checkout_otp_resend_available_at')->nullable()->after('checkout_otp_expires_at');
                }

                if (!Schema::hasColumn('ticket_holds', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('checkout_otp_resend_available_at');
                }

                if (!Schema::hasColumn('ticket_holds', 'payment_started_at')) {
                    $table->timestamp('payment_started_at')->nullable()->after('email_verified_at');
                }

                if (!Schema::hasColumn('ticket_holds', 'pending_ticket_counter_id')) {
                    $table->unsignedBigInteger('pending_ticket_counter_id')->nullable()->after('payment_started_at')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ticket_holds')) {
            Schema::table('ticket_holds', function (Blueprint $table) {
                $columns = [
                    'pending_ticket_counter_id',
                    'payment_started_at',
                    'email_verified_at',
                    'checkout_otp_resend_available_at',
                    'checkout_otp_expires_at',
                    'checkout_otp_hash',
                    'car_details',
                    'parking_slots',
                    'age_group_items',
                    'service_items',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('ticket_holds', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('ticket_types') && Schema::hasColumn('ticket_types', 'enable_age_group')) {
            Schema::table('ticket_types', function (Blueprint $table) {
                $table->dropColumn('enable_age_group');
            });
        }

        Schema::dropIfExists('ticket_counter_age_groups');
        Schema::dropIfExists('ticket_type_age_groups');
        Schema::dropIfExists('ticket_counter_services');
        Schema::dropIfExists('event_services');
    }
};
