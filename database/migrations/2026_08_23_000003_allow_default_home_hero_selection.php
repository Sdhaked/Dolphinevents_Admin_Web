<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('home_page_content') || !Schema::hasColumn('home_page_content', 'show_what')) {
            return;
        }

        DB::statement("ALTER TABLE `home_page_content` MODIFY `show_what` ENUM('default', 'slider', 'video') NOT NULL DEFAULT 'default'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('home_page_content') || !Schema::hasColumn('home_page_content', 'show_what')) {
            return;
        }

        DB::table('home_page_content')
            ->where('show_what', 'default')
            ->update(['show_what' => 'slider']);

        DB::statement("ALTER TABLE `home_page_content` MODIFY `show_what` ENUM('slider', 'video') NOT NULL DEFAULT 'slider'");
    }
};
