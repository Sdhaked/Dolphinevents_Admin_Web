<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = [
        'id',
        'country_id',
        'name',
        'code',
        'type',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function ticketCounters(): HasMany
    {
        return $this->hasMany(TicketCounter::class);
    }
}
