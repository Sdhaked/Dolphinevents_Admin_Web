<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens; 

class TicketChecker extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'last_login_ip',
        'last_login_ua',
        'plain_password',
        'created_by'
    ];

    protected $hidden = [
        'password',
        'otp',
        'plain_password'
    ];
    
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}