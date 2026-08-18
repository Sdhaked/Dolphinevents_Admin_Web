<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $eventId = session('active_event_id');
        $event = Event::findOrFail($eventId);

        $images = EventGallery::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('alt_text', 'like', "%{$search}%");
            });
        })
            ->where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.events.gallery._partials.table', compact('images', 'event'))->render();
        }

        return view('admin.events.gallery.index', compact('images', 'event'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $eventId = session('active_event_id');
        
        // Check if already 8 images exist
        $existingCount = EventGallery::where('event_id', $eventId)->count();
        if ($existingCount >= 8) {
            return redirect()->route('admin.event.gallery.index')
                ->with('error', 'Maximum 8 images allowed in gallery.');
        }
        
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255'
        ]);

        $image = $request->file('image');
        $imagePath = $image->store('events/' . $eventId . '/gallery', 'public');

        $altText = $request->alt_text;
        if (empty($altText)) {
            $altText = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        }

        $gallery = EventGallery::create([
            'event_id' => $eventId,
            'image' => $imagePath,
            'alt_text' => $altText
        ]);

        return redirect()->route('admin.event.gallery.index')->with('success', 'Image uploaded successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $eventId = session('active_event_id');

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255'
        ]);

        $gallery = EventGallery::where('event_id', $eventId)->findOrFail($id);

        if ($request->hasFile('image')) {
            // Delete old file if exists
            if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
                Storage::disk('public')->delete($gallery->image);
            }

            $imagePath = $request->file('image')->store('events/' . $eventId . '/gallery', 'public');
            $gallery->image = $imagePath;

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
            'message' => 'Image updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $eventId = session('active_event_id');
        $gallery = EventGallery::where('event_id', $eventId)->findOrFail($id);

        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }

        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully!'
        ]);
    }
}
