<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTypeAgeGroup extends Model
{
    protected $fillable = [
        'ticket_type_id',
        'label',
        'price',
        'total_tickets',
        'max_quantity_per_booking',
        'is_compulsory',
        'order_index',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_tickets' => 'integer',
        'max_quantity_per_booking' => 'integer',
        'is_compulsory' => 'boolean',
        'order_index' => 'integer',
    ];

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }
}
