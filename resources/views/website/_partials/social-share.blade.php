@php
    $shareUrl = request()->url();
    $shareTitle = ($event ?? null)?->title ?? ($blog ?? null)?->title ?? config('app.name');
    $shareImage = ($event ?? null)?->featured_image ?? ($blog ?? null)?->featured_image ?? null;
    $shareImageUrl = $shareImage ? asset('storage/' . $shareImage) : null;
    $shareText = trim($shareTitle . ($shareImageUrl ? ' ' . $shareImageUrl : ''));

    $shareLinks = [
        [
            'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl)
                . '&quote=' . urlencode($shareTitle)
                . ($shareImageUrl ? '&picture=' . urlencode($shareImageUrl) : ''),
            'icon' => 'fa-brands fa-facebook-f',
        ],
        [
            'url' => 'https://twitter.com/intent/tweet?url=' . urlencode($shareUrl)
                . '&text=' . urlencode($shareText),
            'icon' => 'fab fa-twitter',
        ],
        [
            'url' => 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($shareUrl)
                . '&title=' . urlencode($shareTitle)
                . ($shareImageUrl ? '&summary=' . urlencode($shareImageUrl) : ''),
            'icon' => 'fa-brands fa-linkedin-in',
        ],
        [
            'url' => 'https://wa.me/?text=' . urlencode($shareText),
            'icon' => 'fa-brands fa-whatsapp',
        ],
        [
            'url' => 'https://t.me/share/url?url=' . urlencode($shareUrl)
                . '&text=' . urlencode($shareTitle),
            'icon' => 'fa-brands fa-telegram',
        ],
    ];
@endphp
    
<div class="social-list">
    <ul>
         @foreach ($shareLinks as $link)
            <li data-aos="zoom-in">
                <a href="{{ $link['url'] }}" target="_blank" style="font-size: 0.9rem;">
                  <i class="{{ $link['icon'] }}"></i>
                </a>
            </li>
        @endforeach
    </ul>
</div>
