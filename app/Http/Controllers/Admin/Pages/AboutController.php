<?php

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Models\AboutPageContent;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $content = AboutPageContent::find(1);
        return view('admin.pages.about', compact('content'));
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
        $page = AboutPageContent::find(1);

        // breadcrumb_image_path
        $breadcrumb_image_path = $page?->breadcrumb_image_path;
        if ($request->hasFile('breadcrumb_image_path')) {
            $breadcrumb_image_path = $this->replacePublicFile(
                $breadcrumb_image_path,
                $request->file('breadcrumb_image_path'),
                'pages/about/'
            );
        }

        // about_featured_image_path
        $about_featured_image_path = $page?->about_featured_image_path;
        if ($request->hasFile('about_featured_image_path')) {
            $about_featured_image_path = $this->replacePublicFile(
                $about_featured_image_path,
                $request->file('about_featured_image_path'),
                'pages/about/'
            );
        }

        // owner_image_1_path
        $owner_image_1_path = $page?->owner_image_1_path;
        if ($request->hasFile('owner_image_1_path')) {
            $owner_image_1_path = $this->replacePublicFile(
                $owner_image_1_path,
                $request->file('owner_image_1_path'),
                'pages/about/owner'
            );
        }

        // owner_image_2_path
        $owner_image_2_path = $page?->owner_image_2_path;
        if ($request->hasFile('owner_image_2_path')) {
            $owner_image_2_path = $this->replacePublicFile(
                $owner_image_2_path,
                $request->file('owner_image_2_path'),
                'pages/about/owner'
            );
        }

        AboutPageContent::updateOrCreate(
            ['id' => 1],
            [
                'breadcrumb_image_path' => $breadcrumb_image_path,
                'breadcrumb_image_alt' => $request->input('breadcrumb_image_alt') ?? 'breadcrumb-image',
                'breadcrumb_heading_type' => $request->input('breadcrumb_heading_type'),
                'breadcrumb_heading_text' => $request->input('breadcrumb_heading_text'),
                'breadcrumb_description' => $request->input('breadcrumb_description'),

                'about_featured_image_path' => $about_featured_image_path,
                'about_featured_image_alt' => $request->input('about_featured_image_alt') ?? 'about-featured-image',
                'about_heading_type' => $request->input('about_heading_type'),
                'about_heading_text' => $request->input('about_heading_text'),
                'about_description' => $request->input('about_description') ?? $page?->about_description,
                'about_processed_description' => $request->input('about_processed_description') ?? $page?->about_processed_description,

                'owner_image_1_path' => $owner_image_1_path,
                'owner_image_1_alt' => $request->input('owner_image_1_alt') ?? 'about-owner-image',
                'owner_image_2_path' => $owner_image_2_path,
                'owner_image_2_alt' => $request->input('owner_image_2_alt') ?? 'about-owner-image',
                'owner_heading_1_type' => $request->input('owner_heading_1_type'),
                'owner_heading_1_text' => $request->input('owner_heading_1_text'),
                'owner_heading_2_type' => $request->input('owner_heading_2_type'),
                'owner_heading_2_text' => $request->input('owner_heading_2_text'),
                'owner_description' => $request->input('owner_description') ?? $page?->owner_description,
                'owner_processed_description' => $request->input('owner_processed_description') ?? $page?->owner_processed_description,

                'meta_data' => json_encode($request->input('meta_data'))
            ]
        );

        return redirect()->route('admin.pages.about.index')->with('success', "About page content updated successfully.");
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
