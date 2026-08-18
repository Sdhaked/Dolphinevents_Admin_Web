<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AboutPageContent extends Model
{
    use HasFactory;

    protected $table = 'about_page_content';
    
    protected $fillable = [
        'breadcrumb_image_path',
        'breadcrumb_image_alt',
        'breadcrumb_heading_type',
        'breadcrumb_heading_text',
        'breadcrumb_description',

        'about_featured_image_path',
        'about_featured_image_alt',
        'about_heading_type',
        'about_heading_text',
        'about_description',
        'about_processed_description',

        'owner_image_1_path',
        'owner_image_1_alt',
        'owner_image_2_path',
        'owner_image_2_alt',
        'owner_heading_1_type',
        'owner_heading_1_text',
        'owner_heading_2_type',
        'owner_heading_2_text',
        'owner_description',
        'owner_processed_description',

        'meta_data'
    ];
}
