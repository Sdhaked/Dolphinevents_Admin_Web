<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHold extends Model
{
    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'quantity',
        'selected_seats',
        'total_amount',
        'token',
        'name',
        'email',
        'phone_prefix',
        'mobile_number',
        'country_id',
        'state_id',
        'coupon_code',
        'checkout_started_at',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    // Optional: better date handling
    protected $casts = [
        'selected_seats' => 'array',
        'expires_at' => 'datetime',
        'checkout_started_at' => 'datetime',
    ];

    // Optional relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }
}
