@foreach ($events as $event)
    <div class="event-archive-card">
        <a href="{{ route('website.events.show', $event->slug) }}" class="img-holder">
            <img src="{{ asset('storage/' . $event->featured_image) }}"
                alt="{{ $event->featured_image_alt_text }}" loading="lazy" decoding="async" />
        </a>

        <div class="text-holder">
            <a href="{{ route('website.events.show', $event->slug) }}">
                <h3 class="title">{{ $event?->title }}</h3>
            </a>
            <p class="date-time">
                <i class="fa-solid fa-calendar-days i-mr"></i>
                <time
                    datetime="{{ $event?->from_date->format('Y-m-d') }}">{{ $event?->from_date->format('M j, Y') }}</time>
                @if ($event?->to_date)
                    <span>
                        -
                        <time
                            datetime="{{ $event?->to_date->format('Y-m-d') }}">{{ $event?->to_date->format('M j, Y') }}</time>
                    </span>
                @endif
            </p>
            <p class="date-time">
                <i class="fa-solid fa-clock i-mr"></i>
                <time>{{ $event?->from_time->format('g:i A') }}</time>
                @if ($event?->to_time)
                    <span>
                        TO
                        <time>{{ $event?->to_time->format('g:i A') }}</time>
                    </span>
                @endif
            </p>
        </div>
    </div>
@endforeach
