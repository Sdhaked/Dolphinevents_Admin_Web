<?php

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $content = HomePageContent::find(1);
        return view('admin.pages.home', compact('content'));
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
        $page = HomePageContent::find(1);

        // upload hero video
        $hero_video_path = $page?->hero_video_path;
        if ($request->hasFile('hero_video_path')) {
            $hero_video_path = $this->replacePublicFile(
                $hero_video_path,
                $request->file('hero_video_path'),
                'pages/home/'
            );
        }

        // about_image_path
        $about_image_path = $page?->about_image_path;
        if ($request->hasFile('about_image_path')) {
            $about_image_path = $this->replacePublicFile(
                $about_image_path,
                $request->file('about_image_path'),
                'pages/home/'
            );
        }

        HomePageContent::updateOrCreate(
            ['id' => 1],
            [
                'show_what' => $request->input('show_what'),
                'hero_video_path' => $hero_video_path,
                'about_image_path' => $about_image_path,
                'about_image_alt' => $request->input('about_image_alt') ?? 'home-page-about-image',
                'about_heading_type_1' => $request->input('about_heading_type_1'),
                'about_heading_text_1' => $request->input('about_heading_text_1'),
                'about_heading_type_2' => $request->input('about_heading_type_2'),
                'about_heading_text_2' => $request->input('about_heading_text_2'),
                'about_description' => $request->input('about_description') ?? $page?->about_description,
                'about_processed_description' => $request->input('about_processed_description') ?? $page?->about_processed_description,
                'meta_data' => json_encode($request->input('meta_data')),
            ]
        );

        return redirect()->route('admin.pages.home.index')->with('success', "Home page content updated successfully.");
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
