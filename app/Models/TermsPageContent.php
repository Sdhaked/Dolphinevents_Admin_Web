<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TermsPageContent extends Model
{
    use HasFactory;

    protected $table = 'terms_page_content';

    protected $fillable = [
        'breadcrumb_image_path',
        'breadcrumb_image_alt',
        'breadcrumb_heading_type',
        'breadcrumb_heading_text',
        'breadcrumb_description',
        'main_content',
        'processed_main_content',
        'meta_data',
    ];
}
