<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        "event_id",
        "phone_prefix",
        "phone_number",
        "secondary_phone_prefix",
        "secondary_phone_number",
        "email",
        "address",
    ];

    public function socialLinks(): HasMany
    {
        return $this->hasMany(EventSupportSocialLink::class);
    }
}
