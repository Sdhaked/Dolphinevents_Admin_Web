<?php

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Models\EventArchivePageContent;
use Illuminate\Http\Request;

class EventArchiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $content = EventArchivePageContent::find(1);
        return view('admin.pages.event_archive', compact('content'));
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
        $page = EventArchivePageContent::find(1);

        // $breadcrumb_image_path
        $breadcrumb_image_path = $page?->breadcrumb_image_path;
        if ($request->hasFile('breadcrumb_image_path')) {
            $breadcrumb_image_path = $this->replacePublicFile(
                $breadcrumb_image_path,
                $request->file('breadcrumb_image_path'),
                'pages/event_archive/'
            );
        }

        EventArchivePageContent::updateOrCreate(
            ['id' => 1],
            [
                'breadcrumb_image_path' => $breadcrumb_image_path,
                'breadcrumb_image_alt' => $request->input('breadcrumb_image_alt') ?? 'breadcrumb-image',
                'breadcrumb_heading_type' => $request->input('breadcrumb_heading_type'),
                'breadcrumb_heading_text' => $request->input('breadcrumb_heading_text'),
                'breadcrumb_description' => $request->input('breadcrumb_description'),
                'meta_data' => json_encode($request->input('meta_data'))
            ]
        );

        return redirect()->route('admin.pages.event_archive.index')->with('success', "Event archive page content updated successfully.");
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
