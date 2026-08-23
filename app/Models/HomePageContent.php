<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomePageContent extends Model
{
    use HasFactory;

    protected $table = 'home_page_content';

    protected $fillable = [
        'show_what',
        'default_hero_heading_type_1',
        'default_hero_heading_1',
        'default_hero_heading_type_2',
        'default_hero_heading_2',
        'default_hero_description',
        'default_hero_processed_description',
        'hero_video_path',
        'hero_video_poster',
        'about_image_path',
        'about_image_alt',
        'about_heading_type_1',
        'about_heading_text_1',
        'about_heading_type_2',
        'about_heading_text_2',
        'about_description',
        'about_processed_description',
        'meta_data',
    ];
}
