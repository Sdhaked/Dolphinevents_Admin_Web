@if ($event?->venue_layout_image)
    <!-- Popup Box -->
    <div class="popup-container pop-boxJS" id="venue-layout-pop">
        <div class="popup w-m popJS">
            <!-- Header -->
            <div class="close-box">
                <div class="title-box">
                    <h5 class="hd-sub">Venue Layout</h5>
                </div>
                <button class="btn-lg btn-close">
                    <i class="fa-regular fa-circle-xmark"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="module-body">
                <img src="{{ asset('storage/' . $event?->venue_layout_image) }}" alt="{{ $event?->venue_layout_image_alt_text }}" loading="lazy" decoding="async" data-aos="zoom-in" />
            </div>
        </div>
    </div>
@endif
