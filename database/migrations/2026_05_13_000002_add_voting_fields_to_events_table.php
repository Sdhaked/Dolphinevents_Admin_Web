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
            if (!Schema::hasColumn('events', 'enable_voting')) {
                $table->boolean('enable_voting')->default(false)->after('enable_car_parking');
            }

            if (!Schema::hasColumn('events', 'voting_title')) {
                $table->string('voting_title')->nullable()->after('enable_voting');
            }

            if (!Schema::hasColumn('events', 'voting_btn_title')) {
                $table->string('voting_btn_title')->nullable()->after('voting_title');
            }

            if (!Schema::hasColumn('events', 'voting_des')) {
                $table->text('voting_des')->nullable()->after('voting_btn_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('events', 'voting_des') ? 'voting_des' : null,
                Schema::hasColumn('events', 'voting_btn_title') ? 'voting_btn_title' : null,
                Schema::hasColumn('events', 'voting_title') ? 'voting_title' : null,
                Schema::hasColumn('events', 'enable_voting') ? 'enable_voting' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
