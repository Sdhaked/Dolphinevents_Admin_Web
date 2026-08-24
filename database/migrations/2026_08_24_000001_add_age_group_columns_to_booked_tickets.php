<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('booked_tickets', 'ticket_counter_age_group_id')) {
                $table->unsignedBigInteger('ticket_counter_age_group_id')->nullable()->after('venue_layout_id')->index();
            }

            if (!Schema::hasColumn('booked_tickets', 'ticket_type_age_group_id')) {
                $table->unsignedBigInteger('ticket_type_age_group_id')->nullable()->after('ticket_counter_age_group_id')->index();
            }

            if (!Schema::hasColumn('booked_tickets', 'sub_type_label')) {
                $table->string('sub_type_label')->nullable()->after('ticket_type_age_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('booked_tickets', 'sub_type_label')) {
                $table->dropColumn('sub_type_label');
            }

            if (Schema::hasColumn('booked_tickets', 'ticket_type_age_group_id')) {
                $table->dropColumn('ticket_type_age_group_id');
            }

            if (Schema::hasColumn('booked_tickets', 'ticket_counter_age_group_id')) {
                $table->dropColumn('ticket_counter_age_group_id');
            }
        });
    }
};
