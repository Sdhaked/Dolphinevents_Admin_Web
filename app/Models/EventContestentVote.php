<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventContestentVote extends Model
{
    protected $fillable = [
        'event_id',
        'event_contestent_id',
        'ticket_counter_id',
        'booking_id',
        'name',
        'email',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contestent(): BelongsTo
    {
        return $this->belongsTo(EventContestent::class, 'event_contestent_id')->withTrashed();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TicketCounter::class, 'ticket_counter_id');
    }
}
