@php
    use App\Models\Event;
    $events = Event::orderBy('created_at', 'desc')->get() ?? collect();
    $active_event_id = session('active_event_id');
@endphp

<select class="form-select" id="dfg8" name="events" onchange="chooseEvent(event)">
    @forelse ($events as $event)
        <option value="{{ $event->id }}" {{ $active_event_id == $event->id ? 'selected' : '' }}>{{ $event->title }} {!! $event->is_featured ? '&#9733;' : '' !!}</option>
    @empty
        <option value="">No event found!</option>
    @endforelse
</select>