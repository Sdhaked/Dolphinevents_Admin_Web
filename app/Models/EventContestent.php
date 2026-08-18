<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventContestent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'image',
        'name',
        'email',
        'phone_prefix',
        'phone_number',
        'social_links',
        'votes',
        'created_by',
    ];

    protected $casts = [
        'social_links' => 'array',
        'votes' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function votesReceived(): HasMany
    {
        return $this->hasMany(EventContestentVote::class, 'event_contestent_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getFullPhoneAttribute(): ?string
    {
        if (!$this->phone_number) {
            return null;
        }

        return trim(($this->phone_prefix ?: '') . ' ' . $this->phone_number);
    }
}
