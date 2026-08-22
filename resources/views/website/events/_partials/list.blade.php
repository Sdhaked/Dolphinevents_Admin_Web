@if ($events->count())
    <div class="grid-archive-4 gap-card">
        @foreach ($events as $event)
            <x-website.event-archive-card :event="$event" />
        @endforeach
    </div>
@else
    <div class="no-data-box text-center py-5">
        <p>No events found for {{ $now->format('F, Y') }}.</p>
    </div>
@endif
