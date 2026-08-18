<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSupportSocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        "event_support_id",
        "platform",
        "url"
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EventSupport::class);
    }
}
