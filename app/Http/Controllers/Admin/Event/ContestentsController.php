<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventContestent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContestentsController extends Controller
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

        $contestents = EventContestent::where('event_id', $eventId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.Contestents._partials.table', compact('contestents', 'event'))->render();
        }

        $totalContestents = EventContestent::where('event_id', $eventId)->count();
        $totalVotes = EventContestent::where('event_id', $eventId)->sum('votes');
        $winningContestent = EventContestent::where('event_id', $eventId)
            ->where('votes', '>', 0)
            ->orderByDesc('votes')
            ->orderByDesc('id')
            ->first();

        return view('admin.Contestents.index', compact(
            'contestents',
            'event',
            'totalContestents',
            'totalVotes',
            'winningContestent'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $event = Event::findOrFail(session('active_event_id'));
        $contestent = new EventContestent([
            'phone_prefix' => '+91',
        ]);
        $isEdit = false;

        return view('admin.Contestents.form', compact('contestent', 'event', 'isEdit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $eventId = session('active_event_id');
        Event::findOrFail($eventId);

        $validated = $request->validate($this->rules(), $this->messages());
        $socialLinks = $this->normalizeSocialLinks($request);

        try {
            $imagePath = $request->file('image')->store('events/' . $eventId . '/contestents', 'public');

            EventContestent::create([
                'event_id' => $eventId,
                'image' => $imagePath,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone_prefix' => $validated['phone_prefix'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'social_links' => $socialLinks,
                'created_by' => Auth::id(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contestent created successfully!',
                    'redirect' => route('admin.contestents.index'),
                ]);
            }

            return redirect()->route('admin.contestents.index')->with('success', 'Contestent created successfully!');
        } catch (\Exception $e) {
            Log::error('Contestents module failed to create contestent', [
                'module' => 'Contestents',
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while creating contestent.',
                ], 500);
            }

            return redirect()->route('admin.contestents.index')->with('error', 'Something went wrong while creating contestent.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($contestentId)
    {
        $eventId = session('active_event_id');
        $contestent = EventContestent::where('event_id', $eventId)->findOrFail($contestentId);
        $voters = $contestent->votesReceived()
            ->latest()
            ->get();

        return view('admin.Contestents.show', compact('contestent', 'voters'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($contestentId)
    {
        $eventId = session('active_event_id');
        $event = Event::findOrFail($eventId);
        $contestent = EventContestent::where('event_id', $eventId)->findOrFail($contestentId);
        $isEdit = true;

        return view('admin.Contestents.form', compact('contestent', 'event', 'isEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $contestentId)
    {
        $eventId = session('active_event_id');
        $contestent = EventContestent::where('event_id', $eventId)->findOrFail($contestentId);

        $validated = $request->validate($this->rules(true), $this->messages());
        $socialLinks = $this->normalizeSocialLinks($request);

        try {
            if ($request->hasFile('image')) {
                $this->deleteImage($contestent->image);
                $contestent->image = $request->file('image')->store('events/' . $eventId . '/contestents', 'public');
            }

            $contestent->fill([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone_prefix' => $validated['phone_prefix'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'social_links' => $socialLinks,
            ])->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contestent updated successfully!',
                    'redirect' => route('admin.contestents.index'),
                ]);
            }

            return redirect()->route('admin.contestents.index')->with('success', 'Contestent updated successfully!');
        } catch (\Exception $e) {
            Log::error('Contestents module failed to update contestent', [
                'module' => 'Contestents',
                'event_id' => $eventId,
                'contestent_id' => $contestentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while updating contestent.',
                ], 500);
            }

            return redirect()->route('admin.contestents.index')->with('error', 'Something went wrong while updating contestent.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $contestentId)
    {
        $eventId = session('active_event_id');
        $contestent = EventContestent::where('event_id', $eventId)->findOrFail($contestentId);

        try {
            $this->deleteImage($contestent->image);
            $contestent->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contestent deleted successfully.',
                ]);
            }

            return redirect()->route('admin.contestents.index')->with('success', 'Contestent deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Contestents module failed to delete contestent', [
                'module' => 'Contestents',
                'event_id' => $eventId,
                'contestent_id' => $contestentId,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while deleting contestent.',
                ], 500);
            }

            return redirect()->route('admin.contestents.index')->with('error', 'Something went wrong while deleting contestent.');
        }
    }

    protected function rules(bool $isUpdate = false): array
    {
        return [
            'image' => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_prefix' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'regex:/^[0-9]{4,12}$/'],
            'platform' => ['nullable', 'array'],
            'platform.*' => ['nullable', Rule::in(array_keys(config('entities.social_options', [])))],
            'url' => ['nullable', 'array'],
            'url.*' => ['nullable', 'url', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must contain only numbers and be between 4 and 12 digits.',
        ];
    }

    protected function normalizeSocialLinks(Request $request): array
    {
        $platforms = array_values($request->input('platform', []));
        $urls = array_values($request->input('url', []));
        $count = max(count($platforms), count($urls));
        $links = [];

        for ($index = 0; $index < $count; $index++) {
            $platform = $platforms[$index] ?? null;
            $url = $urls[$index] ?? null;
            $platform = is_string($platform) ? trim($platform) : $platform;
            $url = is_string($url) ? trim($url) : $url;

            if (blank($platform) && blank($url)) {
                continue;
            }

            if (blank($platform) || blank($url)) {
                throw ValidationException::withMessages([
                    'social_links' => 'Please select a platform and URL together for every social link.',
                ]);
            }

            $links[] = [
                'platform' => (int) $platform,
                'url' => $url,
            ];
        }

        return $links;
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
