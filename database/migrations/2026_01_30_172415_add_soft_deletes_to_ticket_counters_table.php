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
    Schema::table('ticket_counters', function (Blueprint $table) {
        $table->softDeletes(); // Adds the deleted_at column
    });
}

public function down(): void
{
    Schema::table('ticket_counters', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
};
