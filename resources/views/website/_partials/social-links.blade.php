@php
    use App\Models\ContactSocialLink;
    $social_links = ContactSocialLink::visible()->orderBy('id')->take(4)->get() ?? collect();
    $socialOptions = config('entities.social_options', []);
@endphp

@if ($social_links->isNotEmpty())
    <div class="social-list">
        <ul>
            @foreach ($social_links as $link)
                @php
                    $social = $socialOptions[$link->platform] ?? null;
                @endphp
                @if ($social && filled($link->url))
                    <li data-aos="zoom-in">
                        <a href="{{ $link->url }}" target="_blank">
                            <i class="{{ $social['icon'] }}"></i>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
@endif
