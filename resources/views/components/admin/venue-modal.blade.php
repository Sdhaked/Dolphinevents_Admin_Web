@php
    use App\Models\Event;
    $active_event_id = session('active_event_id');
    $event = Event::find($active_event_id);
@endphp
<!-- Modal Box 🚗-->
<div class="modal fade" id="modalID" tabindex="-1" aria-labelledby="modalIDLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="hd-sm m-0">Venue Layout</h6>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <img src="{{ asset('storage/' . $event?->venue_layout_image) }}" alt="{{ $event?->venue_layout_image_alt_text }}">
            </div>
        </div>
    </div>
</div>