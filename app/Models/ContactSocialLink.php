<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactSocialLink extends Model
{
    use HasFactory;

    protected $table = 'contact_social_links';

    protected $fillable = [
        'platform',
        'url'
    ];

    public function scopeVisible($query)
    {
        return $query->whereNotNull('url')
            ->where('url', '!=', '');
    }
}
