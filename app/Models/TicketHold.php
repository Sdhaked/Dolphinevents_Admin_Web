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
        'service_items',
        'age_group_items',
        'parking_slots',
        'car_details',
        'checkout_otp_hash',
        'checkout_otp_expires_at',
        'checkout_otp_resend_available_at',
        'email_verified_at',
        'payment_started_at',
        'pending_ticket_counter_id',
        'checkout_started_at',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    // Optional: better date handling
    protected $casts = [
        'selected_seats' => 'array',
        'service_items' => 'array',
        'age_group_items' => 'array',
        'parking_slots' => 'integer',
        'car_details' => 'array',
        'expires_at' => 'datetime',
        'checkout_started_at' => 'datetime',
        'checkout_otp_expires_at' => 'datetime',
        'checkout_otp_resend_available_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'payment_started_at' => 'datetime',
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
