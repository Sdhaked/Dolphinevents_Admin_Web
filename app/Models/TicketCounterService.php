<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCounterService extends Model
{
    protected $fillable = [
        'ticket_counter_id',
        'event_id',
        'event_service_id',
        'service_name',
        'quantity',
        'price',
        'total_amount',
        'service_code',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(EventService::class, 'event_service_id');
    }

    public function passes(): HasMany
    {
        return $this->hasMany(TicketCounterServicePass::class, 'ticket_counter_service_id');
    }
}
