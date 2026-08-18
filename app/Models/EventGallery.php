<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGallery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'image',
        'alt_text',
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
}
