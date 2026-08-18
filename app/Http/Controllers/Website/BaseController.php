<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\PolicyPageContent;
use App\Models\TermsPageContent;

class BaseController extends Controller
{
    /**
     * Policy
     */
    public function policy() {
        $content = PolicyPageContent::where('id', 1)->first();
        return view('website.policy', compact('content'));
    }

    /**
     * Tearms & Conditions
     */
    public function termsConditions() {
        $content = TermsPageContent::where('id', 1)->first();
        return view('website.tearms_conditions', compact('content'));
    }
}
