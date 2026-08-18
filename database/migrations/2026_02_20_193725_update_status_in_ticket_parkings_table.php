<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_parkings', function (Blueprint $table) {
          

            // 1. Add the tracking columns if they don't exist
            if (!Schema::hasColumn('ticket_parkings', 'scanned_at')) {
                $table->timestamp('scanned_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('ticket_parkings', 'scanned_by')) {
                $table->unsignedBigInteger('scanned_by')->nullable()->after('scanned_at');
                
                $table->foreign('scanned_by')->references('id')->on('ticket_checkers');
            }
        });
    }

    public function down()
    {
        Schema::table('ticket_parkings', function (Blueprint $table) {
           
            $table->dropColumn(['scanned_at', 'scanned_by']);
        });
    }
};