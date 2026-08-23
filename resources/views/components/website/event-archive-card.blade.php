@props(['event'])

@php
    $eventUrl = route('website.events.show', $event->slug);
    $imageAltText = $event->featured_image_alt_text ?: $event->title;
    $eventStartDate = $event->from_date ? \Illuminate\Support\Carbon::parse($event->from_date) : null;
    $startingTicketPrice = $event->starting_ticket_price;
    $formattedStartingTicketPrice = $startingTicketPrice !== null
        ? \App\Models\Currency::format($startingTicketPrice, $event)
        : null;
@endphp

<article {{ $attributes->merge(['class' => 'event-archive-card']) }}>
    <div class="date-tag">
       <h6>
            {{ $eventStartDate?->format('j') ?? '--' }}
            <br><span>{{ $eventStartDate ? strtolower($eventStartDate->format('M')) : '---' }}</span>
        </h6>
    </div>
    <a href="{{ $eventUrl }}" class="img-holder">
        <img src="{{ asset('storage/' . $event->featured_image) }}"
            alt="{{ $imageAltText }}" loading="lazy" decoding="async" />
    </a>

    <div class="text-holder">
        <a href="{{ $eventUrl }}" style="margin-bottom: 1.5rem;">
            <h3 class="title">{{ $event->title }}</h3>
        </a>

        <address>The Roundhouse, London</address>
        <p class="date-time">
            <time datetime="{{ $event->from_date->format('Y-m-d') }}">{{ $event->from_date->format('M j, Y') }}</time>
            @if ($event->to_date)
                <span>
                    -
                    <time datetime="{{ $event->to_date->format('Y-m-d') }}">{{ $event->to_date->format('M j, Y') }}</time>
                </span>
            @endif

            <time>{{ $event->from_time->format('g:i A') }}</time>
            @if ($event->to_time)
                <span>
                    TO
                    <time>{{ $event->to_time->format('g:i A') }}</time>
                </span>
            @endif
        </p>

        <div class="card-footer">
           <div class="price">
                {{ $formattedStartingTicketPrice ? 'From ' . $formattedStartingTicketPrice : 'Price TBA' }}
            </div>
           <div><a role="button" href="{{ $eventUrl }}" class="book-btn">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
           </a></div>
        </div>
    </div>
</article>
