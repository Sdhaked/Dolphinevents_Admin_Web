<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketType extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_types';

    protected $fillable = [
        'event_id',
        'featured_image',
        'featured_image_alt_text',
        'title',
        'ticket_type_color',
        'total_tickets',
        'ticket_price',
        'description',
        'enable_bulk_discount',
        'enable_tax',
        'tax_label',
        'tax_value',
        'enable_extra_charges',
        'extra_charges_label',
        'extra_charges_value',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enable_bulk_discount' => 'boolean',
        'enable_tax' => 'boolean',
        'enable_extra_charges' => 'boolean',
    ];



    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketCounters(): HasMany
    {
        return $this->hasMany(TicketCounter::class, 'ticket_type_id');
    }

    // Get available tickets count
    public function getAvailableTicketsAttribute(): int
    {
        $soldCount = \App\Models\TicketCounter::where('ticket_type_id', $this->id)
            ->whereIn('booking_status', [TicketCounter::STATUS_CONFIRMED, TicketCounter::STATUS_PENDING_VERIFICATION])
            ->sum('qty');
        return $this->total_tickets - $soldCount;
    }

    // Check if tickets are available
    public function hasAvailableTickets($quantity = 1): bool
    {
        return $this->available_tickets >= $quantity;
    }

    // Get sold count from ticket counters
    public function getSoldCountAttribute(): int
    {
        return \App\Models\TicketCounter::where('ticket_type_id', $this->id)
            ->whereIn('booking_status', [TicketCounter::STATUS_CONFIRMED, TicketCounter::STATUS_PENDING_VERIFICATION])
            ->sum('qty');
    }

    // Get percentage sold
    public function getSoldPercentageAttribute(): float
    {
        if ($this->total_tickets == 0) return 0;
        return ($this->sold_count / $this->total_tickets) * 100;
    }

    //get bulk discounts
    public function bulkDiscounts()
    {
        return $this->hasMany(\App\Models\BulkDiscount::class, 'ticket_type_id', 'id')
                    ->orderBy('min_order_qty', 'asc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
