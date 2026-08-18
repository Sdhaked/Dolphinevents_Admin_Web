@foreach ($images as $gallery)
    <div class="g-img-card">
        <a href="{{ asset('storage/' . $gallery->image_path) }}" class="fancybox" data-fancybox="gallery1">
            <img src="{{ asset('storage/' . $gallery->image_path) }}" loading="lazy" alt="{{ $gallery->alt_text }}" decoding="async" />
        </a>
    </div>
@endforeach
