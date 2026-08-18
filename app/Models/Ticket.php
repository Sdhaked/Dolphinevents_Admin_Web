<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{

    protected $fillable = [
        'ticket_number',
        'event_id',
        'ticket_type_id',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'quantity',
        'ticket_price',
        'subtotal',
        'bulk_discount_percentage',
        'bulk_discount_amount',
        'coupon_id',
        'coupon_code',
        'coupon_discount_percentage',
        'coupon_discount_amount',
        'tax_applied',
        'tax_label',
        'tax_percentage',
        'tax_amount',
        'extra_charges_applied',
        'extra_charges_label',
        'extra_charges_percentage',
        'extra_charges_amount',
        'total_discount_amount',
        'total_charges_amount',
        'final_total',
        'purchase_type',
        'purchased_at',
        'purchase_details',
        'is_permanently_deleted'
    ];

    protected $casts = [
        'ticket_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'bulk_discount_percentage' => 'decimal:2',
        'bulk_discount_amount' => 'decimal:2',
        'coupon_discount_percentage' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'extra_charges_percentage' => 'decimal:2',
        'extra_charges_amount' => 'decimal:2',
        'total_discount_amount' => 'decimal:2',
        'total_charges_amount' => 'decimal:2',
        'final_total' => 'decimal:2',
        'tax_applied' => 'boolean',
        'extra_charges_applied' => 'boolean',
        'purchased_at' => 'datetime',
        'purchase_details' => 'array',
        'is_permanently_deleted' => 'boolean'
    ];

    // Generate unique ticket number
    public static function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(\Modules\EventsModule\Models\Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    // Scopes

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByCustomerEmail($query, $email)
    {
        return $query->where('customer_email', $email);
    }

    // Status management methods
    public function getCurrentStatus()
    {
        return TicketStatusHistory::getCurrentStatus($this->id);
    }

    public function changeStatus($newStatus, $reason = null, $metadata = null)
    {
        return TicketStatusHistory::createStatusChange($this->id, $newStatus, $reason, $metadata);
    }

    public function isActive()
    {
        return $this->getCurrentStatus() === 'active';
    }

    public function isCancelled()
    {
        return $this->getCurrentStatus() === 'cancelled';
    }

    public function isRefunded()
    {
        return $this->getCurrentStatus() === 'refunded';
    }

    public function isDeleted()
    {
        return $this->getCurrentStatus() === 'deleted';
    }

    // Get complete metadata history for this ticket
    public function getMetadataHistory()
    {
        return TicketStatusHistory::getMetadataHistory($this->id);
    }

    // Get current action metadata for this ticket
    public function getCurrentActionMetadata()
    {
        return TicketStatusHistory::getCurrentActionMetadata($this->id);
    }

    // Override the active scope to use status history
    public function scopeActiveStatus($query)
    {
        return $query->whereHas('statusHistory', function($q) {
            $q->where('status', 'active');
        });
    }

    // Model events
    protected static function boot()
    {
        parent::boot();

        // Automatically create status history when ticket is created
        static::created(function ($ticket) {
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'status' => 'active',
                'previous_status' => null,
                'reason' => $ticket->purchase_type === 'admin'
                    ? 'Ticket purchased from admin counter'
                    : 'Ticket purchased by customer',
                'changed_by_user_id' => auth()->id(),
                'changed_by_email' => auth()->user()->email ?? 'system',
                'changed_at' => $ticket->purchased_at ?? now(),
                'metadata' => [
                    'purchase_type' => $ticket->purchase_type,
                    'initial_purchase' => true,
                    'auto_created' => true
                ]
            ]);
        });
    }

    /**
     * Check if ticket can be safely restored
     */
    public function canBeRestored()
    {
        $issues = [];

        // Check if ticket is deleted
        if (!$this->isDeleted()) {
            $issues[] = 'Ticket is not deleted';
            return ['can_restore' => false, 'issues' => $issues];
        }

        // Check event status
        if (!$this->event || !$this->event->isPublished()) {
            $issues[] = 'Event is no longer active';
        }

        // Check if event has ended
        if ($this->event && $this->event->to_date && $this->event->to_date < now()) {
            $issues[] = 'Event has already ended';
        }

        // Check ticket type
        if (!$this->ticketType || !$this->ticketType->is_active) {
            $issues[] = 'Ticket type is no longer active';
        }

        // Check capacity
        if ($this->ticketType) {
            $availableCapacity = $this->ticketType->available_tickets;
            if ($availableCapacity < $this->quantity) {
                $issues[] = "Insufficient seats available. All tickets for this type have been sold.";
            }
        }

        // Check coupon if used
        if ($this->coupon_id && $this->coupon) {
            if (!$this->coupon->is_active) {
                $issues[] = 'Coupon is no longer active';
            }
            if ($this->coupon->hasReachedUsageLimit()) {
                $issues[] = 'Coupon has reached usage limit';
            }
        }

        return [
            'can_restore' => empty($issues),
            'issues' => $issues,
            'warnings' => $this->getRestoreWarnings()
        ];
    }

    /**
     * Get restore warnings (non-blocking issues)
     */
    private function getRestoreWarnings()
    {
        $warnings = [];

        // Check for ticket number conflicts
        $existingTicket = self::where('ticket_number', $this->ticket_number)
                             ->where('id', '!=', $this->id)
                             ->activeStatus()
                             ->first();

        if ($existingTicket) {
            $warnings[] = 'Ticket number conflict - new number will be generated';
        }

        // Check coupon issues (non-blocking)
        if ($this->coupon_id) {
            if (!$this->coupon) {
                $warnings[] = 'Coupon no longer exists';
            } elseif (!$this->coupon->is_active) {
                $warnings[] = 'Coupon is inactive';
            }
        }

        return $warnings;
    }
}
