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
        // Adding the column to store the path of the sponsor image
        $table->string('event_pdf_sponser_image')->nullable()->after('venue_layout_image_alt_text');
    });
}

public function down(): void
{
    Schema::table('events', function (Blueprint $table) {
        $table->dropColumn('event_pdf_sponser_image');
    });
}
};
