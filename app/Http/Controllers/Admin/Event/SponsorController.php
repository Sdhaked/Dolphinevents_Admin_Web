<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSponsor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SponsorController extends Controller
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

        $sponsors = EventSponsor::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        })
            ->where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.events.sliders.sponsors._partials.table', compact('sponsors', 'event'))->render();
        }

        return view('admin.events.sliders.sponsors.index', compact('sponsors', 'event'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255',
            'url' => 'required|url'
        ]);

        $eventId = session('active_event_id');
        $image = null;
        $alt_text = 'sponsor';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('events/' . $eventId . '/sponsors', 'public');
            $image = $path;

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $alt_text = $request->alt_text ?? ucfirst(trim(str_replace(['_', '-'], ' ', $originalName)));
        }

        $sponsor = EventSponsor::create([
            'event_id' => $eventId,
            'image' => $image,
            'alt_text' => $alt_text,
            'url' => $request->url
        ]);

        return redirect()->route('admin.sponsors.index')->with('success', 'New sponser created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255',
            'url' => 'required|url'
        ]);

        $eventId = session('active_event_id');
        $sponsor = EventSponsor::where('event_id', $eventId)->findOrFail($id);

        if ($request->hasFile('image')) {
            // Delete old file if exists
            if ($sponsor->image && Storage::disk('public')->exists($sponsor->image)) {
                Storage::disk('public')->delete($sponsor->image);
            }

            $path = $request->file('image')->store('events/' . $eventId . '/sponsors', 'public');
            $sponsor->image = $path;
        }

        $sponsor->alt_text = $request->input('alt_text') ?? $sponsor->alt_text;
        $sponsor->url = $request->input('url') ?? null;
        $sponsor->save();

        return response()->json([
            'success' => true,
            'message' => 'Sponsor updated successfully!',
            'data' => $sponsor
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $eventId = session('active_event_id');
        $sponsor = EventSponsor::where('event_id', $eventId)->findOrFail($id);

        if ($sponsor->image && Storage::disk('public')->exists($sponsor->image)) {
            Storage::disk('public')->delete($sponsor->image);
        }

        $sponsor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sponsor deleted successfully.'
        ]);
    }
}
