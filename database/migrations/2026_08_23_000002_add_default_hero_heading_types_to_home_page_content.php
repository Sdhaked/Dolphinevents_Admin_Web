<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('home_page_content')) {
            return;
        }

        Schema::table('home_page_content', function (Blueprint $table) {
            if (!Schema::hasColumn('home_page_content', 'default_hero_heading_type_1')) {
                $table->string('default_hero_heading_type_1', 10)->default('h3');
            }

            if (!Schema::hasColumn('home_page_content', 'default_hero_heading_type_2')) {
                $table->string('default_hero_heading_type_2', 10)->default('h1');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('home_page_content')) {
            return;
        }

        Schema::table('home_page_content', function (Blueprint $table) {
            foreach ([
                'default_hero_heading_type_1',
                'default_hero_heading_type_2',
            ] as $column) {
                if (Schema::hasColumn('home_page_content', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
