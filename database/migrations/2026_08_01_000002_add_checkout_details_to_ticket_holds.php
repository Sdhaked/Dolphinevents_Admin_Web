<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_holds', function (Blueprint $table) {
            $table->string('name')->nullable()->after('token');
            $table->string('email')->nullable()->after('name');
            $table->string('phone_prefix', 20)->nullable()->after('email');
            $table->string('mobile_number')->nullable()->after('phone_prefix');
            $table->foreignId('country_id')->nullable()->after('mobile_number')->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
            $table->string('coupon_code', 50)->nullable()->after('state_id');
            $table->timestamp('checkout_started_at')->nullable()->after('coupon_code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_holds', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropIndex(['checkout_started_at']);
            $table->dropColumn([
                'name', 'email', 'phone_prefix', 'mobile_number', 'country_id',
                'state_id', 'coupon_code', 'checkout_started_at',
            ]);
        });
    }
};
