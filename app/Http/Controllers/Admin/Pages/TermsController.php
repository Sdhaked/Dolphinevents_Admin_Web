<?php

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Models\TermsPageContent;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $content = TermsPageContent::find(1);
        return view('admin.pages.terms', compact('content'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $page = TermsPageContent::find(1);

        // breadcrumb_image_path
        $breadcrumb_image_path = $page?->breadcrumb_image_path;
        if ($request->hasFile('breadcrumb_image_path')) {
            $breadcrumb_image_path = $this->replacePublicFile(
                $breadcrumb_image_path,
                $request->file('breadcrumb_image_path'),
                'pages/terms/'
            );
        }

        TermsPageContent::updateOrCreate(
            ['id' => 1],
            [
                'breadcrumb_image_path' => $breadcrumb_image_path,
                'breadcrumb_image_alt' => $request->input('breadcrumb_image_alt') ?? 'breadcrumb-image',
                'breadcrumb_heading_type' => $request->input('breadcrumb_heading_type'),
                'breadcrumb_heading_text' => $request->input('breadcrumb_heading_text'),
                'breadcrumb_description' => $request->input('breadcrumb_description'),

                'main_content' => $request->input('main_content') ?? $page?->main_content,
                'processed_main_content' => $request->input('processed_main_content') ?? $page?->processed_main_content,

                'meta_data' => json_encode($request->input('meta_data'))
            ]
        );

        return redirect()->route('admin.pages.terms')->with('success', 'Terms page content updated successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
