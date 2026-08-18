<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ContactPageContent;
use App\Models\ContactSocialLink;

class ContactController extends Controller
{
    /**
     * index
     */
    public function index() {
        $content = ContactPageContent::where('id', 1)->first();
        if ($content) {
            $content->social_links = ContactSocialLink::all();
        }
        return view('website.contact.index', compact('content'));
    }
}
