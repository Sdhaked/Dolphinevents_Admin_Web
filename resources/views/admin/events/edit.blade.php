@extends('layouts.admin')

@section('head')
    <title>Edit Event</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!-- Quill 2 - Self-hosted library (no external CDN links) -->
    <link rel="stylesheet" href="{{ asset('style/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('style/admin/quill-editor.css') }}">

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
    <script src="{{ asset('javascript/pages/edit-event.js') }}" defer></script>

    <!-- Quill 2 - Self-hosted library + our init script -->
    <script src="{{ asset('javascript/quill/quill.js') }}" defer></script>
    <script src="{{ asset('javascript/admin/quill-editor.js') }}" defer></script>
@endsection

@section('body')
    <!-- PRELOADER -->
    @include('admin._partials.preloader')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <div class="d-flex justify-content-between flex-wrapp mb-4" style="gap: var(--card-gap)">            
                <div class="HDandP">
                    <h4 class="hd-lg">Edit Event Details</h4>
                    <p><i class="fa-solid fa-arrow-right-long i-mr"></i> {{$event?->status=='1' ? 'Published' : 'Draft'}}</p>
                </div>
                {{-- Delete Event Button & Form --}}
                <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" id="delete-event-form" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" name="status" value="1" class="btn-xs danger-fill-btn" data-bs-toggle="modal"
                        data-bs-target="#deleteModal">Delete Event</button>
                </form>
            </div>


            {{-- Edit event form --}}
            <form action="{{ route('admin.events.update') }}" class="needs-validation" novalidate=""
                class="grid-1 gap-card" id="main-form" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="d-flex flex-wrap gap-card">
                    <div>
                        <button type="button" class="check-btn">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                {{ $event?->is_featured ? 'checked' : '' }}>
                            <label for="is_featured"> Make it Featured</label>
                        </button>
                    </div>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Featured Video -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($event?->featured_video)"
                                :delete-url="route('admin.media.destroy', ['target' => 'event-featured-video'])"
                                label="featured video" />
                                    <!-- width="320" height="240" -->
                            <video  class="video preview" id="event_video" controls playsinline loop
                                autoplay
                                src="{{ $event?->featured_video ? asset('storage/' . $event->featured_video) : asset('images/uploadimg.svg') }}"
                                data-saved-src="{{ $event?->featured_video ? asset('storage/' . $event->featured_video) : '' }}"
                                data-placeholder-src="{{ asset('images/uploadimg.svg') }}"
                                aria-label="Event intro video"
                                poster="{{ $event?->thumbnail ? asset('storage/' . $event->thumbnail) : asset('images/uploadvideo.svg') }}"></video>
                        </div>
                        <div class="mt-4">
                            <label for="ds5v8d">Upload Featured Video</label>
                            <input type="file" data-max-file-size-kb="5000" name="featured_video"
                                class="form-control mt-1" id="ds5v8d" accept="video/*">
                        </div>
                    </div>

                    <!-- Video Thumbnail Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($event?->thumbnail)"
                                :delete-url="route('admin.media.destroy', ['target' => 'event-thumbnail'])"
                                label="video thumbnail" />
                            <img src="{{ $event?->thumbnail ? asset('storage/' . $event->thumbnail) : asset('images/uploadimg.svg') }}"
                                id="prev_thumbnail" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="dfcfc">Upload Video Thumbnail Image</label>
                            <input type="file" class="form-control mt-1" name="thumbnail" id="dfcfc"
                                accept="image/*">
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($event?->featured_image)"
                                :delete-url="route('admin.media.destroy', ['target' => 'event-featured-image'])"
                                label="featured image"
                                :required-after-delete="true" />
                            <img src="{{ $event?->featured_image ? asset('storage/' . $event->featured_image) : asset('images/uploadimg.svg') }}"
                                id="prev_featured_image" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="5d4fvfd5">Upload Featured Image <span class="text-danger">*</span></label>
                            <input type="file" name="featured_image" class="form-control mt-1" id="5d4fvfd5"
                                accept="image/*" {{ $event?->featured_image ? '' : 'required' }}>
                            <div class="label-spc mt-1">
                        <input type="text" name="featured_image_alt_text" id="featured_image_alt_text"
                            class="form-control" placeholder="Alt Text"
                            value="{{ old('featured_image_alt_text', $event?->featured_image_alt_text ?? '') }}">
                    </div>
                        </div>
                    </div>

                    <!-- Venue Layout -->
                    <div class="label-spc upload-box">
                        <div class="previewBox mt-2">
                            <x-admin.media-remove
                                :exists="filled($event?->venue_layout_image)"
                                :delete-url="route('admin.media.destroy', ['target' => 'event-venue-layout-image'])"
                                label="venue layout image"
                                :required-after-delete="true" />
                            <img src="{{ $event?->venue_layout_image ? asset('storage/' . $event->venue_layout_image) : asset('images/uploadimg.svg') }}"
                                id="prev_venue_layout_image" class="preview thumb-img x3">
                        </div>
                        <div class="mt-4">
                            <label for="fdvv4">Upload Venue Layout Image <span class="text-danger">*</span></label>
                            <input type="file" name="venue_layout_image" class="form-control mt-1" id="fdvv4"
                                accept="image/*" {{ $event?->venue_layout_image ? '' : 'required' }}>
                    <div class="label-spc mt-1">
                                <input type="text" name="venue_layout_image_alt_text" id="venue_layout_image_alt_text"
                                    class="form-control" placeholder="Alt Text"
                                    value="{{ old('venue_layout_image_alt_text', $event?->venue_layout_image_alt_text ?? '') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="form-floating">
                        <input type="text" name="title" class="form-control" id="title"
                            value="{{ old('title', $event?->title ?? '') }}" required>
                        <label for="title">Title <span class="text-danger">*</span></label>
                    </div>

                    <!-- Brought you by -->
                    <div class="form-floating">
                        <input type="text" name="brought_you_by" class="form-control" id="brought_you_by"
                            value="{{ old('brought_you_by', $event?->brought_you_by ?? '') }}">
                        <label for="brought_you_by">Brought you by</label>
                    </div>

                    <!-- Currency -->
                    <div class="form-floating">
                        <select name="currency_id" class="form-control" id="currency_id" required>
                            <option value="">Select Currency</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}"
                                    @selected((string) old('currency_id', $event?->currency_id ?? '') === (string) $currency->id)>
                                    {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                                </option>
                            @endforeach
                        </select>
                        <label for="currency_id">Currency <span class="text-danger">*</span></label>
                    </div>


                    <div class="i-holder">
                        <i class="fa-regular fa-calendar-days i-icon"></i>
                        <div class="form-floating">
                            <input type="date" name="from_date" class="form-control my-datepicker" id="fromDate"
                                value="{{ old('from_date', $event?->from_date?->format('Y-m-d') ?? '') }}" required>
                            <label for="fromDate">Event From Date <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <div class="i-holder">
                        <i class="fa-regular fa-calendar-days i-icon"></i>
                        <div class="form-floating">
                            <input type="date" name="to_date" class="form-control my-datepicker" id="toDate"
                                value="{{ old('to_date', $event?->to_date?->format('Y-m-d') ?? '') }}">
                            <label for="toDate">Event To Date</label>
                        </div>
                    </div>

                    <div class="i-holder">
                        <i class="fa-regular fa-clock i-icon"></i>
                        <div class="form-floating">
                            <input type="time" name="from_time" class="form-control my-datepicker"
                                id="event-from-time" value="{{ old('from_time', $event?->from_time?->format('H:i') ?? '') }}" required>
                            <label for="event-from-time">Event From Time <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <div class="i-holder">
                        <i class="fa-regular fa-clock i-icon"></i>
                        <div class="form-floating">
                            <input type="time" name="to_time" class="form-control my-datepicker" id="event-to-time"
                                value="{{ old('to_time', $event?->to_time?->format('H:i') ?? '') }}" required>
                            <label for="event-to-time">Event To Time <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <div class="i-holder">
                        <i class="fa-solid fa-ticket i-icon"></i>
                        <div class="form-floating">
                            <input type="datetime-local" name="sell_tickets_till" class="form-control my-datepicker"
                                id="sell-ticket-till" value="{{ old('sell_tickets_till', $event?->sell_tickets_till ?? '') }}" required>
                            <label for="sell-ticket-till">Sell Tickets Till <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <!-- Map Link -->
                    <div class="form-floating">
                        <input type="url" class="form-control" name="map_link" id="map_link"
                            value="{{ old('map_link', $event?->map_link ?? '') }}" required>
                        <label for="map_link">Map Link <span class="text-danger">*</span></label>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-floating">
                    <textarea name="address" id="address" class="form-control about-associate" style="height: 100px" required>{{ old('address', $event?->address ?? '') }}</textarea>
                    <label for="address">Address <span class="text-danger">*</span></label>
                </div>

                {{-- Event PDF sponser image --}}
                    <div class="style-box">
                        <h3 class="hd-sm">PDF Sponsor Image</h3>
                        <div class="label-spc upload-box">
                            <div class="previewBox mt-2">
                                {{-- If an image exists, show it; otherwise show the placeholder --}}
                                <x-admin.media-remove
                                    :exists="filled($event?->event_pdf_sponser_image)"
                                    :delete-url="route('admin.media.destroy', ['target' => 'event-pdf-sponsor-image'])"
                                    label="PDF sponsor image" />
                                @if(isset($event) && $event->event_pdf_sponser_image)
                                    <img src="{{ asset('storage/' . $event->event_pdf_sponser_image) }}" class="preview thumb-img x3" id="pdf_sponsor_preview">
                                @else
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x3" id="pdf_sponsor_preview">
                                @endif
                            </div>
                            
                            <div class="mt-4">
                                <label for="event_pdf_sponser_image">Update PDF Sponsor Image</label>
                                {{-- Removed 'required' so existing images aren't overwritten by accident --}}
                                <input type="file" 
                                    class="form-control mt-1" 
                                    id="event_pdf_sponser_image" 
                                    name="event_pdf_sponser_image" 
                                    accept="image/*"
                                    onchange="previewImage(this)">
                                <small class="text-100 mt-1 d-block">Note:- Leave blank to keep the current image.</small>
                            </div>
                        </div>
                    </div>

                <div class="style-box">
                    <div>
                        <button type="button" class="check-btn">
                            <input class="form-check-input" type="checkbox" id="isParking" name="enable_car_parking"
                                id="enable_car_parking" {{ $event?->enable_car_parking ? 'checked' : '' }}>
                            <label for="isParking"> Enable Car Parking</label>
                        </button>
                    </div>
                    <div class="grid-2 grid-sm-1 gap-card mt-4">
                        <!-- Total Parking Slots-->
                        <div class="form-floating">
                            <input type="number" name="car_parking_slots" value="{{ old('car_parking_slots', $event?->car_parking_slots ?? '') }}" class="form-control" id="parking-slots"
                                oninput="setMinMax({ min: 1, ele: event })">
                            <label for="parking-slots">Total Parking Slots (QTY)*</label>
                        </div>

                        <!-- Parking Price -->
                        <div class="input-group mb-3">
                            <span class="input-group-text" id="basic-addon1">{{ $event?->currency_symbol }}</span>
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="car_slot_price" value="{{ old('car_slot_price', $event?->car_slot_price ?? '') }}" class="form-control" id="parking-price"
                                    oninput="setMinMax({ min: 0, ele: event })">
                                <label for="parking-price">Price Per Slot*</label>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $oldInput = session()->getOldInput();
                    $votingEnabled = !empty($oldInput)
                        ? array_key_exists('enable_voting', $oldInput)
                        : (bool) $event?->enable_voting;
                @endphp

                <!-- -----VOTING SYSTEM --------- -->
                <div class="style-box">
                    <div>
                        <button type="button" class="check-btn">
                            <input class="form-check-input" type="checkbox" id="isVoting" name="enable_voting"
                                value="1" {{ $votingEnabled ? 'checked' : '' }}>
                            <label for="isVoting"> Enable Voting Module</label>
                        </button>
                    </div>
                    <div class="grid-2 grid-sm-1 gap-card mt-4">
                        <div class="form-floating">
                            <input type="text" name="voting_title" value="{{ old('voting_title', $event?->voting_title ?? '') }}" class="form-control voting-required-field" id="votingTitle"
                                {{ $votingEnabled ? 'required' : '' }}>
                            <label for="votingTitle">Voting Title <span class="text-danger voting-required-star" style="{{ $votingEnabled ? '' : 'display: none;' }}">*</span></label>
                        </div>

                        <div class="form-floating">
                            <input type="text" name="voting_btn_title" value="{{ old('voting_btn_title', $event?->voting_btn_title ?? '') }}" class="form-control voting-required-field" id="votingBtnTitle"
                                {{ $votingEnabled ? 'required' : '' }}>
                            <label for="votingBtnTitle">Voting Button Title <span class="text-danger voting-required-star" style="{{ $votingEnabled ? '' : 'display: none;' }}">*</span></label>
                        </div>
                    </div>

                    <div class="form-floating mt-4">
                        <textarea name="voting_des" id="voting_des" class="form-control about-associate voting-required-field" style="height: 100px" {{ $votingEnabled ? 'required' : '' }}>{{ old('voting_des', $event?->voting_des ?? '') }}</textarea>
                        <label for="voting_des">Voting Description <span class="text-danger voting-required-star" style="{{ $votingEnabled ? '' : 'display: none;' }}">*</span></label>
                    </div>
                </div>

                <div>
                       <!-- TEXT EDITOR -->
                <textarea name="editorData" id="editorData" hidden>{{ old('editorData', $event?->description ?? '') }}</textarea>
                <div class="admin-quill-editor" id="editor-container">
                    <div
                        id="editor"
                        data-quill-editor
                        data-input="#editorData"
                        data-upload-url="{{ route('admin.events.editor.upload_image') }}"
                    ></div>
                </div>
                </div>


               

                <div class="style-box">
                    <h4 class="hd-lg">SEO Settings</h4>

                    <div class="grid-1 gap-card">
                        <!-- Slug -->
                        <div class="form-floating">
                            <input type="text" class="form-control" name="slug" id="slug"
                                value="{{ old('slug', $event?->slug ?? '') }}" required="">
                            <label for="slug">Slug <span class="text-danger">*</span></label>
                        </div>

                        <!-- Meta Field -->
                        <div class="form-floating">
                            <textarea class="form-control metabox" name="meta_data" id="metabox"  required="">{{ old('meta_data', $event?->meta_data ?? '') }}</textarea>
                            <label for="metabox">Meta Box <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <button type="submit" id="publishBtn" name="status" value="1"
                        class="btn-md btn-prim mt-2">Publish</button>
                    <button type="submit" id="draftBtn" name="status" value="0"
                        class="btn-md btn-sec-outline mt-2">Save As
                        Draft
                    </button>
                </div>
            </form>
        </main>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">⚠️ Delete Event</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                     <p class="mb-3"><strong>PERMANENT DELETE:</strong> This will permanently remove the event and ALL associated data from the database.</p>
                     <p class="mb-2">This includes:</p>
                     <ul class="small mb-3 text-200">
                         <li>All ticket types and bookings</li>
                         <li>All ticket checkers and history</li>
                         <li>All parking records</li>
                         <li>All bulk discounts and coupons</li>
                         <li>All sponsors and gallery images</li>
                         <li>All information sliders</li>
                         <li>All event support details</li>
                     </ul>
                     <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong> Are you sure?</p>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="deleteEventActionBtn" onclick="confirmDelete()">Delete Event</button>
                    </div>
            </div>
        </div>
    </div>

    <script>
        // AJAX form submission with loader
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('main-form');
            const publishBtn = document.getElementById('publishBtn');
            const draftBtn = document.getElementById('draftBtn');
            const votingCheckbox = document.getElementById('isVoting');
            const votingFields = document.querySelectorAll('.voting-required-field');
            const votingRequiredStars = document.querySelectorAll('.voting-required-star');

            const syncVotingRequiredFields = () => {
                const isVotingEnabled = votingCheckbox?.checked === true;

                votingFields.forEach((field) => {
                    field.required = isVotingEnabled;

                    if (!isVotingEnabled) {
                        field.classList.remove('is-invalid');
                    }
                });

                votingRequiredStars.forEach((star) => {
                    star.style.display = isVotingEnabled ? '' : 'none';
                });
            };

            votingCheckbox?.addEventListener('change', syncVotingRequiredFields);
            syncVotingRequiredFields();

            const notify = (type, bold, msg = '') => {
                if (typeof createNotification === 'function') {
                    createNotification(type, bold, msg);
                }
            };

            const getFieldLabel = (field) => {
                if (!field) return 'This field';
                let label = '';

                if (field.id) {
                    const labelEl = document.querySelector(`label[for="${field.id}"]`);
                    if (labelEl) label = labelEl.textContent || '';
                }

                if (!label) {
                    const floatingLabel = field.closest('.form-floating')?.querySelector('label');
                    if (floatingLabel) label = floatingLabel.textContent || '';
                }

                return (label || field.name || 'This field').replace(/\*/g, '').trim();
            };

            const focusField = (field) => {
                if (!field || typeof field.focus !== 'function') return;
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                field.focus({ preventScroll: true });
            };

            const normalizeFieldName = (name) => {
                if (!name) return '';
                return name.replace(/\.(\d+)/g, '[$1]');
            };

            const findFieldByErrorKey = (fieldKey) => {
                if (!fieldKey) return null;

                const normalizedKey = normalizeFieldName(fieldKey);
                const elements = Array.from(form.elements || []).filter(el => el?.name);

                return elements.find((el) =>
                    el.name === fieldKey ||
                    el.name === normalizedKey ||
                    el.name.startsWith(`${normalizedKey}[`) ||
                    el.name.startsWith(`${fieldKey}[`)
                ) || null;
            };

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                syncVotingRequiredFields();

                // Check form validity
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    const firstInvalidField = form.querySelector(':invalid');
                    if (firstInvalidField) {
                        const label = getFieldLabel(firstInvalidField);
                        const reason = firstInvalidField.validationMessage || 'Please fill this field correctly.';
                        notify('error', `${label}: ${reason}`, '');
                        focusField(firstInvalidField);
                    } else {
                        notify('error', 'Please fix form errors and try again.', '');
                    }
                    return false;
                }

                const clickedBtn = e.submitter || publishBtn || draftBtn;

                if (clickedBtn) {
                    // Disable button and show loader
                    clickedBtn.disabled = true;
                    const originalText = clickedBtn.innerHTML;
                    clickedBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>' + (clickedBtn === publishBtn ? 'Publishing...' : 'Saving...');

                    // Clear previous invalid states
                    Array.from(form.elements || []).forEach((el) => el.classList?.remove('is-invalid'));

                    // Create FormData
                    const formData = new FormData(form);
                    // Ensure clicked submit button value (publish/draft) is always submitted
                    if (clickedBtn.name === 'status') {
                        formData.set('status', clickedBtn.value);
                    }
                    
                    // Submit via AJAX
                    fetch('{{ route("admin.events.update") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        if (!response.ok) {
                            const contentType = response.headers.get('content-type') || '';
                            let payload = {};

                            if (contentType.includes('application/json')) {
                                payload = await response.json();
                            } else {
                                payload.message = await response.text();
                            }

                            throw {
                                status: response.status,
                                payload
                            };
                        }

                        return response.text();
                    })
                    .then(data => {
                        clickedBtn.disabled = false;
                        clickedBtn.innerHTML = originalText;
                        notify('success', 'Event updated successfully', '');
                        // Reload page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 100);
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        if (error?.status === 422 && error?.payload?.errors) {
                            const errors = error.payload.errors;
                            const firstErrorEntry = Object.entries(errors)[0];

                            if (firstErrorEntry) {
                                const [fieldKey, messages] = firstErrorEntry;
                                const firstErrorMessage = Array.isArray(messages) ? messages[0] : String(messages);
                                notify('error', firstErrorMessage, '');

                                const targetField = findFieldByErrorKey(fieldKey);
                                if (targetField) {
                                    targetField.classList.add('is-invalid');
                                    focusField(targetField);
                                }
                            } else {
                                notify('error', error.payload.message || 'Please fix the validation errors.', '');
                            }
                        } else {
                            notify('error', error?.payload?.message || 'Failed to update event. Please check all fields.', '');
                        }

                        // Re-enable button
                        clickedBtn.disabled = false;
                        clickedBtn.innerHTML = originalText;
                    });
                }
            });
        });
    </script>

@endsection
