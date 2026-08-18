<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_checkers', function (Blueprint $table) {
            // Field for the 6-digit OTP [cite: 11]
            $table->string('otp')->nullable(); 
            $table->timestamp('otp_expires_at')->nullable();

            // Device logging fields
            $table->string('last_login_ip')->nullable(); 
            $table->text('last_login_ua')->nullable(); 
            
            // Standard timestamps if not already present
            if (!Schema::hasColumn('ticket_checkers', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('ticket_checkers', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_expires_at', 'last_login_ip', 'last_login_ua']);
        });
    }
};