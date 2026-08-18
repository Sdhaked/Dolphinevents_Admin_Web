<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventService;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventServiceController extends Controller
{
    public function index(Request $request)
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        if (!$event) {
            return redirect()->route('admin.dashboard.index')->with('error', 'Please select an event first.');
        }

        $services = EventService::where('event_id', $eventId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest('id')
            ->paginate(config('constants.pagination.per_page', 10));

        $ticketTypes = TicketType::where('event_id', $eventId)->orderBy('title')->get();

        return view('admin.event_services.index', compact('event', 'services', 'ticketTypes'));
    }

    public function store(Request $request)
    {
        $eventId = session('active_event_id');
        $validated = $this->validatedData($request);

        EventService::create([
            ...$validated,
            'event_id' => $eventId,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'status' => $request->boolean('status', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Event service created successfully.');
    }

    public function update(Request $request, EventService $eventService)
    {
        $this->abortIfWrongEvent($eventService);

        $validated = $this->validatedData($request);
        $eventService->update([
            ...$validated,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'status' => $request->boolean('status'),
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Event service updated successfully.');
    }

    public function destroy(EventService $eventService)
    {
        $this->abortIfWrongEvent($eventService);
        $eventService->delete();

        return back()->with('success', 'Event service deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'available_quantity' => ['required', 'integer', 'min:0'],
            'max_buy_limit' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'applicable_ticket_type_ids' => ['nullable', 'array'],
            'applicable_ticket_type_ids.*' => ['integer', 'exists:ticket_types,id'],
        ]);
    }

    private function abortIfWrongEvent(EventService $eventService): void
    {
        abort_unless((int) $eventService->event_id === (int) session('active_event_id'), 404);
    }
}
