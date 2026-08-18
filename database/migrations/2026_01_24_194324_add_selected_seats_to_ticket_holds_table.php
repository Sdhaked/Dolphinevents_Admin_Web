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
    Schema::table('ticket_holds', function (Blueprint $table) {
        // Store the array of seat IDs as JSON
        $table->json('selected_seats')->after('quantity')->nullable();
        
        // Store the final calculated price after discounts
        $table->decimal('total_amount', 10, 2)->after('selected_seats')->nullable();
    });
}

public function down(): void
{
    Schema::table('ticket_holds', function (Blueprint $table) {
        $table->dropColumn(['selected_seats', 'total_amount']);
    });
}
};
