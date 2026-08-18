<?php

namespace App\Http\Controllers\Admin\Slider;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;

class InfoSliderController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    protected function validateSlide(Request $request, bool $isEdit = false): array
    {
        return $request->validate([
            'alt_text' => 'required|string|max:255',
            'image' => $isEdit
                ? 'nullable|image|mimes:jpg,jpeg,png,webp'
                : 'required|image|mimes:jpg,jpeg,png,webp',
            'url' => 'required|url|max:2048',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $slides = Slider::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        })
            ->where('type', 2)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.sliders.info_slider._partials.table', compact('slides'))->render();
        }

        return view('admin.sliders.info_slider.index', compact('slides'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateSlide($request);

        $image = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('admin/sliders/info', 'public');
            $image = $path;
        }

        $slider = Slider::create([
            'type' => 2,
            'image' => $image,
            'alt_text' => $validated['alt_text'],
            'url' => $validated['url']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Slide created successfully.'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $this->validateSlide($request, true);

        $slide = Slider::where('id', $id)->where('type', 2)->first();

        if (!$slide) {
            return response()->json([
                'success' => false,
                'message' => 'Slide not found!'
            ], 404);
        }

        try {
            // If image uploaded, replace
            if ($request->hasFile('image')) {
                $slide->image = $this->replacePublicFile(
                    $slide->image,
                    $request->file('image'),
                    'admin/sliders/info'
                );
            }

            $slide->alt_text = $validated['alt_text'];
            $slide->url = $validated['url'];
            $slide->save();

            return response()->json([
                'success' => true,
                'message' => 'Slide updated successfully!',
                'slide' => $slide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $slide = Slider::where('id', $id)->where('type', 2)->first();

        if ($slide) {
            $this->deletePublicFile($slide->image);
            $slide->delete();
            return response()->json([
                'success' => true,
                'message' => 'Slide deleted successfully.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Slide not found.'
        ], 404);
    }
}
