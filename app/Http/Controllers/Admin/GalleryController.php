<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gallery;

class GalleryController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $galleries = Gallery::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('alt_text', 'like', "%{$search}%")
                    ->orWhere('image_path', 'like', "%{$search}%");
            });
        })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.gallery._partials.table', compact('galleries'))->render();
        }

        return view('admin.gallery.index', compact('galleries'));
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
        $validated = $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:200', // 200KB max
        ]);

        $uploadedImages = [];

        foreach ($validated['images'] as $index => $image) {
            // Store file
            $imagePath = $image->store('images/gallery', 'public');

            // Use original filename (without extension) as alt text
            $altText = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

            // Create gallery record
            $gallery = Gallery::create([
                'image_path' => $imagePath,
                'alt_text' => $altText,
            ]);

            $uploadedImages[] = $gallery;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully!',
            'data' => $uploadedImages,
        ]);
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
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255'
        ]);

        $gallery = Gallery::findOrFail($id);

        if ($request->hasFile('image')) {
            $gallery->image_path = $this->replacePublicFile(
                $gallery->image_path,
                $request->file('image'),
                'images/gallery'
            );

            // If no alt text provided with new image, use filename
            $gallery->alt_text = empty($request->alt_text)
                ? pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME)
                : $request->alt_text;
        } else {
            // If no new image, only update alt text if provided
            if (!empty($request->alt_text)) {
                $gallery->alt_text = $request->alt_text;
            }
        }

        $gallery->save();

        return response()->json([
            'success' => true,
            'message' => 'Image updated successfully!',
            'data' => $gallery
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->deletePublicFile($gallery->image_path);
        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully!'
        ]);
    }
}
