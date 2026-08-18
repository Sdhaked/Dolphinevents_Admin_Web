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
            // Adding the column after 'qty' for better table organization
            $table->json('selected_seats')->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_counters', function (Blueprint $table) {
            $table->dropColumn('selected_seats');
        });
    }
};