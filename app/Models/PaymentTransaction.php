<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ticket_counter_id',
        'event_id',
        'ticket_type_id',
        'booking_id',
        'hold_token',
        'gateway',
        'gateway_session_id',
        'gateway_payment_intent_id',
        'gateway_charge_id',
        'transaction_id',
        'status',
        'gateway_payment_status',
        'currency',
        'amount',
        'quantity',
        'selected_seats',
        'parking_slots',
        'car_details',
        'coupon_code',
        'customer_name',
        'customer_email',
        'phone_prefix',
        'mobile_number',
        'country_id',
        'state_id',
        'initiated_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'failure_reason',
        'cancel_reason',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'selected_seats' => 'array',
        'parking_slots' => 'integer',
        'car_details' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function ticketCounter(): BelongsTo
    {
        return $this->belongsTo(TicketCounter::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }
}
