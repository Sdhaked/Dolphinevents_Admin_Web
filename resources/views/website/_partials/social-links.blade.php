@php
    use App\Models\ContactSocialLink;
    $social_links = ContactSocialLink::orderBy('id')->take(4)->get() ?? collect();
@endphp

@if ($social_links->isNotEmpty())
    <div class="social-list">
        <ul>
            @foreach ($social_links as $link)
                <li data-aos="zoom-in">
                    <a href="{{ $link->url }}" target="_blank">
                        <i class="{{ config('entities.social_options')[$link->platform]['icon'] }}"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
