<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCoupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'title',
        'coupon_code',
        'associate_name',
        'discount',
        'also_associate',
        'ticket_type_ids',
        'created_by'
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'ticket_type_ids' => 'array'
    ];

    // Scope for active coupons
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for valid coupons (active and not expired)
    public function scopeValid($query)
    {
        return $query->where('is_active', true);
    }

    // Check if coupon is valid for a specific event
    public function isValidForEvent($eventId): bool
    {
        return $this->event_id == $eventId;
    }

    // Check if coupon is valid for given ticket types
    public function isValidForTicketTypes($ticketTypeIds): bool
    {
        if ($this->apply_to_all_tickets) {
            return true;
        }

        if (empty($this->selected_ticket_types)) {
            return false;
        }

        $ticketTypeIds = is_array($ticketTypeIds) ? $ticketTypeIds : [$ticketTypeIds];

        // Get ticket type details to check both IDs and titles
        $ticketTypes = \App\Models\TicketType::whereIn('id', $ticketTypeIds)->get(['id', 'title']);

        foreach ($ticketTypes as $ticketType) {
            // Check if the coupon's selected_ticket_types contains either the ID or title
            if (in_array($ticketType->id, $this->selected_ticket_types) ||
                in_array((string)$ticketType->id, $this->selected_ticket_types) ||
                in_array($ticketType->title, $this->selected_ticket_types)) {
                return true;
            }
        }

        return false;
    }

    // Calculate discount amount (as percentage)
    public function calculateDiscount($orderAmount): float
    {
        return ($orderAmount * $this->discount) / 100;
    }

    // Check if coupon has reached usage limit
    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit && $this->usage_count >= $this->usage_limit;
    }

    // Check if coupon can be used (active, not at limit)
    public function canBeUsed(): bool
    {
        return $this->is_active && !$this->hasReachedUsageLimit();
    }

    // Increment usage count
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    // Get remaining usage count
    public function getRemainingUsage(): ?int
    {
        return $this->usage_limit ? $this->usage_limit - $this->usage_count : null;
    }

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'discount_coupon_ticket_type', 'discount_coupon_id', 'ticket_type_id');
    }

    // Get ticket types based on stored IDs
    public function getTicketTypesAttribute()
    {
        if (empty($this->ticket_type_ids)) {
            return collect();
        }

        return TicketType::whereIn('id', $this->ticket_type_ids)->get();
    }

    // Scope for filtering by event
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
