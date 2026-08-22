@foreach ($events as $event)
    <x-website.event-archive-card :event="$event" />
@endforeach
