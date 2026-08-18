<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'id',
        'name',
        'iso2',
        'iso3',
        'phonecode',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function ticketCounters(): HasMany
    {
        return $this->hasMany(TicketCounter::class);
    }
}
