<?php

namespace App\Http\Controllers\Admin\Event\Slider;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InfoSliderController extends Controller
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

        $slides = EventSlider::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        })
            ->where('event_id', $eventId)
            ->where('type', EventSlider::TYPE_INFO)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.events.sliders.info_slider._partials.table', compact('slides', 'event'))->render();
        }

        return view('admin.events.sliders.info_slider.index', compact('slides', 'event'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $eventId = session('active_event_id');

        $image = null;
        $alt_text = 'slide';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('events/' . $eventId . '/info-sliders', 'public');
            $image = $path;

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $alt_text = $request->alt_text ?? ucfirst(trim(str_replace(['_', '-'], ' ', $originalName)));
        }

        $slider = EventSlider::create([
            'event_id' => $eventId,
            'type' => EventSlider::TYPE_INFO,
            'image' => $image,
            'alt_text' => $alt_text,
            'url' => $request->url
        ]);

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Slide created successfully.',
        // ]);
        return redirect()->route('admin.event.sliders.info.index')->with('success', 'Slide created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $eventId = session('active_event_id');

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255',
            'url' => 'nullable|url'
        ]);

        $slide = EventSlider::where('event_id', $eventId)
            ->where('type', EventSlider::TYPE_INFO)
            ->findOrFail($id);

        if ($request->hasFile('image')) {
            // Delete old file if exists
            if ($slide->image && Storage::disk('public')->exists($slide->image)) {
                Storage::disk('public')->delete($slide->image);
            }

            $path = $request->file('image')->store('events/' . $eventId . '/info-sliders', 'public');
            $slide->image = $path;
        }

        $slide->alt_text = $request->alt_text ?? $slide->alt_text;
        $slide->url = $request->url;
        $slide->save();

        return response()->json([
            'success' => true,
            'message' => 'Slide updated successfully!',
            'data' => $slide
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $eventId = session('active_event_id');

        $slide = EventSlider::where('event_id', $eventId)
            ->where('type', EventSlider::TYPE_INFO)
            ->findOrFail($id);

        if ($slide->image && Storage::disk('public')->exists($slide->image)) {
            Storage::disk('public')->delete($slide->image);
        }

        $slide->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slide deleted successfully.'
        ]);
    }
}
