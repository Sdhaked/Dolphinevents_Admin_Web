<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('about_page_content')) {
            return;
        }

        Schema::table('about_page_content', function (Blueprint $table) {
            if (!Schema::hasColumn('about_page_content', 'about_sub_heading_type')) {
                $table->string('about_sub_heading_type', 10)->nullable()->after('about_heading_text');
            }

            if (!Schema::hasColumn('about_page_content', 'about_sub_heading_text')) {
                $table->string('about_sub_heading_text')->nullable()->after('about_sub_heading_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('about_page_content')) {
            return;
        }

        Schema::table('about_page_content', function (Blueprint $table) {
            foreach ([
                'about_sub_heading_type',
                'about_sub_heading_text',
            ] as $column) {
                if (Schema::hasColumn('about_page_content', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
