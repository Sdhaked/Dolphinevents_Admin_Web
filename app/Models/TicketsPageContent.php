<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketsPageContent extends Model
{
    use HasFactory;

    protected $table = 'tickets_page_content';

    protected $fillable = [
        'meta_data'
    ];
}
