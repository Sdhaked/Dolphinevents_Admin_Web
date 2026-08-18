<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketCounter extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const REFUND_NOT_REQUIRED = 'not_required';
    public const REFUND_PENDING = 'pending';
    public const REFUND_REFUNDED = 'refunded';
    
    protected $fillable = [
        'booking_id',
        'event_id',
        'ticket_type_id',
        'qty',
        'bulk_discount_applied',
        'selected_seats',
        'coupon_applied',
        'coupon_code',
        'coupon_amount',
        'coupon_percentage',
        'total_amount',
        'name',
        'email',
        'email_verified_at',
        'ticket_email_sent_at',
        'checkout_otp_hash',
        'checkout_otp_expires_at',
        'checkout_otp_resend_available_at',
        'phone_prefix',
        'mobile_number',
        'country_id',
        'state_id',
        'payment_status',
        'booking_status',
        'refund_status',
        'refunded_at',
        'payment_method',
        'payment_transaction_id',
        'transaction_id',
        'gateway_session_id',
        'gateway_payment_intent_id',
        'payment_initiated_at',
        'payment_completed_at',
        'payment_failed_at',
        'payment_cancelled_at',
        'payment_failure_reason',
        'is_viewed',
        'created_by'
    ];

    protected $casts = [
        'selected_seats' => 'array',
        'bulk_discount_applied' => 'boolean',
        'coupon_amount' => 'decimal:2',
        'coupon_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'email_verified_at' => 'datetime',
        'ticket_email_sent_at' => 'datetime',
        'checkout_otp_expires_at' => 'datetime',
        'checkout_otp_resend_available_at' => 'datetime',
        'refunded_at' => 'datetime',
        'payment_initiated_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'payment_cancelled_at' => 'datetime',
    ];

    /**
     * Automatically generate booking_id
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->booking_id)) {
                $model->booking_id = self::generateBookingId();
            }
        });
    }

    /**
     * Generate a unique booking ID
     * Format: BK-YYYYMMDD-XXXXXX
     */
    public static function generateBookingId(): string
    {
        do {
            $bookingId = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('booking_id', $bookingId)->exists());

        return $bookingId;
    }

    /* ================= Relationships ================= */

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    /* ================= Scopes ================= */

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', self::STATUS_CONFIRMED);
    }

    public function scopeFailedOrPendingVerification($query)
    {
        return $query->whereIn('booking_status', [
            self::STATUS_PENDING_VERIFICATION,
            self::STATUS_FAILED,
        ]);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }
    
    public function parkings()
    {
        return $this->hasMany(TicketParking::class, 'ticket_counter_id');
    }

    public function bookedTickets(): HasMany
    {
        // Using 'ticket_counter_id' as the foreign key in the booked_tickets table
        return $this->hasMany(BookedTicket::class, 'ticket_counter_id');
    }

    public function contestentVotes(): HasMany
    {
        return $this->hasMany(EventContestentVote::class, 'ticket_counter_id');
    }

    public function coupon(): BelongsTo
    {
        // TicketCounter stores the coupon code, and DiscountCoupon uses coupon_code as a unique code.
        return $this->belongsTo(DiscountCoupon::class, 'coupon_code', 'coupon_code')->withTrashed();
    }

    public function C(): BelongsTo
    {
        // Backward-compatible alias for older code paths.
        return $this->coupon();
    }

    /**
     * Scope for unviewed tickets
     */
    public function scopeNew($query)
    {
        return $query->where('is_viewed', 0);
    }

    public function getBookingStatusLabelAttribute(): string
    {
        return match ($this->booking_status) {
            self::STATUS_PENDING_VERIFICATION => 'Pending Verification',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CONFIRMED => 'Confirmed',
            default => ucfirst((string) $this->booking_status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match (strtolower((string) $this->payment_status)) {
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
            'failed' => 'Failed',
            'cancelled', 'canceled' => 'Cancelled',
            'pending', 'processing' => 'Pending',
            default => $this->payment_status
                ? ucwords(str_replace('_', ' ', (string) $this->payment_status))
                : 'N/A',
        };
    }

    public function getRefundStatusLabelAttribute(): string
    {
        return match (strtolower((string) $this->refund_status)) {
            self::REFUND_PENDING => 'Pending',
            self::REFUND_REFUNDED => 'Refunded',
            self::REFUND_NOT_REQUIRED => 'Not Required',
            default => ucfirst((string) $this->refund_status),
        };
    }
}
