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
            if (!Schema::hasColumn('home_page_content', 'default_hero_heading_1')) {
                $table->string('default_hero_heading_1')->nullable();
            }

            if (!Schema::hasColumn('home_page_content', 'default_hero_heading_2')) {
                $table->string('default_hero_heading_2')->nullable();
            }

            if (!Schema::hasColumn('home_page_content', 'default_hero_description')) {
                $table->text('default_hero_description')->nullable();
            }

            if (!Schema::hasColumn('home_page_content', 'default_hero_processed_description')) {
                $table->longText('default_hero_processed_description')->nullable();
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
                'default_hero_heading_1',
                'default_hero_heading_2',
                'default_hero_description',
                'default_hero_processed_description',
            ] as $column) {
                if (Schema::hasColumn('home_page_content', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
