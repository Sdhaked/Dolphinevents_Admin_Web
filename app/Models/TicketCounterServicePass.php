<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCounterServicePass extends Model
{
    public const STATUS_UNUSED = 'unused';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'ticket_counter_service_id',
        'ticket_counter_id',
        'event_id',
        'event_service_id',
        'service_code',
        'unit_number',
        'status',
        'scanned_at',
        'scanned_by',
    ];

    protected $casts = [
        'unit_number' => 'integer',
        'scanned_at' => 'datetime',
    ];

    public function bookingService(): BelongsTo
    {
        return $this->belongsTo(TicketCounterService::class, 'ticket_counter_service_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TicketCounter::class, 'ticket_counter_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(EventService::class, 'event_service_id');
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(TicketChecker::class, 'scanned_by');
    }
}
