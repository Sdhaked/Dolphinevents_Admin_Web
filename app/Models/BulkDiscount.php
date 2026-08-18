<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkDiscount extends Model
{
   // use SoftDeletes;

    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'min_order_qty',
        'discount_percentage',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }
    protected static function booted()
    {
        static::addGlobalScope('force_no_soft_deletes', function ($builder) {
            $builder->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
        });
    }
}
