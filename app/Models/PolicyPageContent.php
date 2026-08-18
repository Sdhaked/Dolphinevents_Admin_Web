<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PolicyPageContent extends Model
{
    use HasFactory;

    protected $table = 'policy_page_content';

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
