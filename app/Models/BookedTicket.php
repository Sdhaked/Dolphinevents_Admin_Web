<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookedTicket extends Model
{
    protected $fillable = [
        'ticket_counter_id',
        'ticket_number',
        'booking_id',
        'venue_layout_id',
        'ticket_counter_age_group_id',
        'ticket_type_age_group_id',
        'sub_type_label',
        'status',
        'scanned_at',
        'scanned_by'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the ticket checker who scanned this ticket.
     */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(TicketChecker::class, 'scanned_by');
    }

    /**
     * Get the main booking associated with this ticket.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(TicketCounter::class, 'ticket_counter_id');
    }

    public function counterAgeGroup(): BelongsTo
    {
        return $this->belongsTo(TicketCounterAgeGroup::class, 'ticket_counter_age_group_id');
    }

    public function ticketTypeAgeGroup(): BelongsTo
    {
        return $this->belongsTo(TicketTypeAgeGroup::class, 'ticket_type_age_group_id');
    }
}
