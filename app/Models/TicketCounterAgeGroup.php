<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCounterAgeGroup extends Model
{
    protected $fillable = [
        'ticket_counter_id',
        'ticket_type_age_group_id',
        'label',
        'quantity',
        'price',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TicketCounter::class, 'ticket_counter_id');
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(TicketTypeAgeGroup::class, 'ticket_type_age_group_id');
    }
}
