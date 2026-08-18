<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSlider extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_HERO = 1;
    const TYPE_INFO = 2;

    protected $fillable = [
        'event_id',
        'type',
        'image',
        'alt_text',
        'url',
        'order'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function scopeHero($query)
    {
        return $query->where('type', self::TYPE_HERO);
    }

    public function scopeInfo($query)
    {
        return $query->where('type', self::TYPE_INFO);
    }
}
