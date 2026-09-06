<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'available_quantity',
        'max_buy_limit',
        'price',
        'is_mandatory',
        'is_reserved',
        'applicable_ticket_type_ids',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'available_quantity' => 'integer',
        'max_buy_limit' => 'integer',
        'price' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_reserved' => 'boolean',
        'applicable_ticket_type_ids' => 'array',
        'status' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(EventServiceField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isApplicableToTicketType(int|string $ticketTypeId): bool
    {
        $ids = $this->applicable_ticket_type_ids ?: [];

        return empty($ids)
            || in_array((int) $ticketTypeId, array_map('intval', $ids), true);
    }
}
