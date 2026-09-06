<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCounterServiceFieldValue extends Model
{
    protected $fillable = [
        'ticket_counter_service_id',
        'event_service_field_id',
        'unit_number',
        'field_label',
        'field_key',
        'field_type',
        'value',
    ];

    protected $casts = [
        'unit_number' => 'integer',
        'value' => 'json',
    ];

    public function bookingService(): BelongsTo
    {
        return $this->belongsTo(TicketCounterService::class, 'ticket_counter_service_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(EventServiceField::class, 'event_service_field_id')->withTrashed();
    }
}
