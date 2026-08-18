<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactPageContent extends Model
{
    use HasFactory;

    protected $table = 'contact_page_content';

    protected $fillable = [
        'breadcrumb_image_path',
        'breadcrumb_image_alt',
        'breadcrumb_heading_type',
        'breadcrumb_heading_text',
        'breadcrumb_description',
        'phone_prefix_1',
        'phone_number_1',
        'phone_prefix_2',
        'phone_number_2',
        'email',
        'address',
        'map_link',
        'map_embed_link',
        'meta_data'
    ];
}
