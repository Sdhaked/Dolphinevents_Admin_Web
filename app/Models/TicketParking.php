<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TicketParking extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_counter_id',
        'ticket_type_id',
        'parking_code',
        'car_number',
        'status',
        'scanned_at',
        'scanned_by',
    ];

    /**
     * The attributes that should be cast.
     * This allows you to use Carbon date methods directly on scanned_at
     */
    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the booking (TicketCounter) associated with the parking ticket.
     */
    public function booking()
    {
        return $this->belongsTo(TicketCounter::class, 'ticket_counter_id');
    }

    /**
     * Get the user (TicketChecker) who scanned the ticket.
     */
    public function scannedBy()
    {
        return $this->belongsTo(TicketChecker::class, 'scanned_by');
    }

    /**
     * Get the ticket type details.
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function setCarNumberAttribute($value): void
    {
        $this->attributes['car_number'] = filled($value)
            ? Str::upper(trim((string) $value))
            : $value;
    }
}
