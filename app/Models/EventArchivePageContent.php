<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventArchivePageContent extends Model
{
    use HasFactory;

    protected $table = 'event_archive_page_content';

    protected $fillable = [
        'breadcrumb_image_path',
        'breadcrumb_image_alt',
        'breadcrumb_heading_type',
        'breadcrumb_heading_text',
        'breadcrumb_description',
        'meta_data'
    ];
}
