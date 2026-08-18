<?php

namespace App\Http\Controllers\Admin\Slider;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;

class HeroSliderController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
       $this->perPage = config('constants.pagination.per_page', 10);
    }

    /**
     * To validate the required fields and return 
     */
    protected function validateSlide(Request $request, $isEdit = false)
    {
        return $request->validate([
            'alt_text' => 'required|string|max:255',
            'image'    => $isEdit ? 'nullable|image|mimes:jpg,jpeg,png,webp'
                                  : 'required|image|mimes:jpg,jpeg,png,webp',
            'url'      => 'nullable|url'
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);

        $slides = Slider::when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
                });
            })
            ->where('type', 1)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage, ['*'], 'page', $page);

        if ($request->ajax()) {
            return view('admin.sliders.hero_slider._partials.table', compact('slides'))->render();
        }

        return view('admin.sliders.hero_slider.index', compact('slides'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateSlide($request);
        
        $imagePath = null;

        if ($request->hasFile('image')) {
            $file   = $request->file('image');
            $imagePath = $file->store('admin/sliders/hero', 'public');

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        Slider::create([
            'type'     => 1,
            'image'    => $imagePath,
            'alt_text' => $request->alt_text,
            'page' => 1,
            'url'      => $request->url
        ]);

        return response()->json(['success' => true,'title'=>'Hero Slider', 'message'=>' added successfully']);
    }


    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     $slide = Slider::where('id', $id)->where('type', 1)->first();

    //     if (!$slide) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Slide not found!'
    //         ], 404);
    //     }

    //     try {
    //         // If image uploaded, replace
    //         if ($request->hasFile('image')) {
    //             // Delete old file if exists
    //             if ($slide->image && Storage::exists($slide->image)) {
    //                 Storage::delete($slide->image);
    //             }

    //             $path = $request->file('image')->store('admin/sliders/hero', 'public');
    //             $slide->image = $path;
    //         }

    //         $slide->alt_text = $request->input('alt_text') ?? $slide->alt_text;
    //         $slide->url = $request->input('url') ?? null;
    //         $slide->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Slide updated successfully!',
    //             'slide' => $slide
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function update(Request $request, $id)
    {
        $slide = Slider::where('id', $id)->where('type', 1)->first();
        if (!$slide) {
            return response()->json(['success' => false, 'message' => 'Slide not found!'], 404);
        }

        $this->validateSlide($request, true);

        if ($request->hasFile('image')) {
            $slide->image = $this->replacePublicFile(
                $slide->image,
                $request->file('image'),
                'admin/sliders/hero'
            );
        }

        $slide->alt_text = $request->alt_text;
        $slide->url      = $request->url;
        $slide->save();

        return response()->json([
            'success' => true,
            'slide'   => $slide,
            'page' => $request->page,
            'title'=>'Hero Slider', 
            'message'=>' updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
{
    $slide = Slider::where('id', $id)->where('type', 1)->first();

    if (!$slide) {
        return response()->json([
            'success' => false,
            'message' => 'Slide not found.'
        ], 404);
    }

    $this->deletePublicFile($slide->image);
    $slide->delete();

    // Get current pagination values
    $perPage      = $this->perPage;
    $currentPage  = (int) $request->page ?? 1;

    // Count remaining slides after deletion
    $totalSlides  = Slider::where('type', 1)->count();

    // Calculate remaining last page
    $lastPage = max(1, (int) ceil($totalSlides / $perPage));

    // If current page > last available page, move back one
    if ($currentPage > $lastPage) {
        $currentPage = $lastPage;
    }

    return response()->json([
        'success' => true,
        'message' => 'Slide deleted successfully.',
        'page'    => $currentPage   // Frontend will load this page
    ]);
}

}
