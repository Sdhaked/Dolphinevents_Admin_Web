<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AboutPageContent;

class AboutController extends Controller
{
    /**
     * index
     */
    public function index() {
        $content = AboutPageContent::where('id', 1)->first();
        return view('website.about.index', compact('content'));
    }
}
