@extends('layouts.website')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('website._partials.head.meta-data', ['metaData' => $event?->meta_data, 'fallbackTitle' => 'Event Venue layout'])

    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}" />
    <link rel="stylesheet" href="{{ asset('website/style/offer-bar.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Checkout JS -->
    <script src="{{ asset('website/js/page-js/checkout.js') }}" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
    <style>
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .select-box select.is-invalid {
            border-color: #dc3545;
        }

        .select-box select.is-invalid:focus {
            outline: none;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.18);
        }

        .checkout-btn-spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: checkoutSpin 0.75s linear infinite;
            vertical-align: -0.15rem;
        }

        #checkoutSubmitBtn.is-loading .checkout-btn-spinner {
            display: inline-block;
        }

        .checkout-page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.58);
            backdrop-filter: blur(2px);
        }

        .checkout-page-loader.is-visible {
            display: flex;
        }

        .checkout-loader-card {
            min-width: 180px;
            padding: 1.5rem 1.75rem;
            text-align: center;
            color: #14172b;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(20, 23, 43, 0.18);
        }

        .checkout-loader-spinner {
            width: 2.4rem;
            height: 2.4rem;
            margin: 0 auto 0.75rem;
            border: 3px solid rgba(226, 35, 38, 0.18);
            border-top-color: #e22326;
            border-radius: 50%;
            animation: checkoutSpin 0.8s linear infinite;
        }

        @keyframes checkoutSpin {
            to {
                transform: rotate(360deg);
            }
        }


       
    </style>


@endsection

@section('body')
    @php
        $selectedCountry = $countries->first(fn ($country) => strcasecmp($country->name, 'United Kingdom') === 0);
        $defaultCountryId = old('country_id', $selectedCountry?->id);
        $defaultStateId = old('state_id');
    @endphp
    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## 🥗 HEADER 🥗 ##########-->
    @include('website._partials.nav')

    <!-- Venue Layout Popup Modal -->
    @include('website.components.venue-layout-popup')

    <!-- ==> Animation Canvas -->
    <canvas id="confetti-canvas"></canvas>

    <!-- MAIN BODY -->
    <main>
        <!--==================================================
                  Event Detail SECTION
        ======================================================-->
        <section class="container-fluid spc-y-half main-sec">
            <div class="container">
                <!-- Header -->
                <div class="head-box" style="margin: 0;">
                    <!-- Headings -->
                    <div>
                        <h1 class="hd-big" data-aos="fade-in">Checkout</h1>

                        <!-- Back Box -->
                        <div class="back-box">
                            <button class="btm-md btn-link" onclick="history.back()">
                                <i class="fa-solid fa-arrow-left-long i-mr"></i> Back
                            </button>
                        </div>
                    </div>
        
                    <!-- Venue Layout Modal Trigger btn-->
                    <div>
                        <button onclick="showElement(`#venue-layout-pop`)" class="btn-md btn-lite-outline hover-lite">
                                    <i class="fa-solid fa-layer-group i-mr"></i> Venue Layout
                        </button>
                    </div>
                </div>

                

                <!-- Offer Bar -->
                <div class="offer-comp"></div>
                {{-- <button id="dummy-btn">1 +</button>
                <button id="dummy-btn2" style="margin-left: 20px;">-</button> --}}


                <!-- MAIN CONTENT -->
                <div class="grid-sec-60-40 gap-card">
                    <!-- Col 1 👩‍🦯-->
                    <div>
                        <form id="checkoutForm" style="width:100%" class="needs-validation" novalidate="">
                            <!-- Tickets -->
                            <div class="head-box-mini">
                                <h1 class="hd-sub">Select Tickets</h1>
                                <p>Choose the tiers that best fit your experience.</p>
                            </div>

                            <div class="tickets-box">
                            @if(!empty($checkout['selected_seats']))
                                {{-- <label>Your Selected Seats</label>
                                <div class="tag-box mt-2">
                                    @foreach($checkout['selected_seats'] as $label)
                                        <div class="tag">{{ $label }}</div>
                                    @endforeach
                                </div> --}}
                                {{-- Hidden input to keep JS logic for billing working --}}
                                <input type="hidden" id="quantity" value="{{ $checkout['quantity'] }}">
                            @elseif(!empty($ageGroups) && $ageGroups->count())
                                <input type="hidden" id="quantity" value="{{ $checkout['quantity'] }}">
                                <h6 class="hd-prim" style="text-transform: uppercase; color:  var(--my-primary);">Sub Tickets</h6>
                                <div class="grid-1 gap-card">
                                    @foreach($ageGroups as $ageGroup)
                                        <div class="sub-ticket">
                                            <div>
                                                <h6 class="ticket-name">
                                                    {{ $ageGroup->label }}
                                                    @if($ageGroup->is_compulsory)
                                                        <span class="ticket-mendatory">Mandatory</span>
                                                    @endif
                                                </h6>
                                                <p class="ticket-price">{{ $event->currency_symbol }}{{number_format((float) $ageGroup->price, 2) }}/- </p>
                                                
                                            </div>
                                            <div class="select-box" style="min-width: 5rem;">
                                                <i class="fa-solid fa-angle-down arrow-i"></i>
                                                <select class="age-group-qty sm" data-id="{{ $ageGroup->id }}"
                                                        data-label="{{ $ageGroup->label }}" data-compulsory="{{$ageGroup->is_compulsory ? 1 : 0 }}">
                                                        @for($i = $ageGroup->is_compulsory ? 1 : 0; $i <= min((int)$ageGroup->max_quantity_per_booking, 20); $i++)
                                                            <option value="{{ $i }}">Qty {{ $i }}</option>
                                                        @endfor
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="invalid-feedback" data-feedback-for="quantity"></div>
                            @else
                                <h6 class="hd-prim" style="text-transform: uppercase; color:  var(--my-primary);">Tickets</h6>

                                <label for="qty">Ticket Qty *</label>
                                <div class="select-box">
                                    <i class="fa-solid fa-angle-down arrow-i"></i>
                                    <select name="qty" id="quantity" required>
                                        {{-- Options filled by JS fetchAvailableQuantity --}}
                                    </select>
                                </div>
                                <div class="invalid-feedback" data-feedback-for="quantity"></div>
                            @endif
                            </div>

                            {{-- Car slots --}}
                            @if($event->enable_car_parking)
                             <div class="car-slot">
                                <div class="car-head">
                                    <div>
                                        <h2 class="text-prim">Book Parking</h2>
                                        <p>{{ $event->currency_symbol }} {{ $event->car_slot_price}}/- (per Vehicle) [{{ $remainingSlots}} Slots Available]</p>
                                    </div>
                                    <div>
                                        <button type="button" class="btn-md btn-prim hover-prim-outline"
                                            id="car-slot-btn-js">
                                            <i class="fa-solid fa-plus i-mr"></i> Add Vehicle
                                        </button>
                                    </div>
                                </div>

                                <div class="tag-box">
                                    <div class="tag">Selected Slots: <span class="selected-slot">0</span>
                                    </div>
                                </div>

                                <div class="car-slot-container">
                                </div>
                                <hr style="border-color: var(--color-border-100); margin-top: 1rem;">
                            </div>
                            @endif

                            @if(!empty($eventServices) && $eventServices->count())
                            <div>
                                <div class="head-box-mini">
                                    <h1 class="hd-sub">Additional Services</h1>
                                    <p>Enhance your event experience with these premium add-ons.</p>
                                </div>

                                <div class="tickets-box">
                                     <h6 class="hd-prim" style="text-transform: uppercase; color:  var(--my-primary);">Choose Services</h6>
                                    <div class="grid-1 gap-card">
                                        @foreach($eventServices as $service)
                                                <div class="sub-ticket">
                                                    <div>
                                                        <h6 class="ticket-name">{{ $service->name }}
                                                              @if($service->is_mandatory)
                                                              <span class="ticket-mendatory">Mandatory</span>
                                                             @endif
                                                        </h6>
                                                        <p class="ticket-price">
                                                            {{ $event->currency_symbol }}{{ number_format((float) $service->price, 2) }}/-
                                                        </p>
                                                    </div>
                                                    <div class="select-box" style="min-width: 5rem;">
                                                        <i class="fa-solid fa-angle-down arrow-i"></i>
                                                        <select class="event-service-qty sm" data-id="{{ $service->id }}" data-mandatory="{{ $service->is_mandatory ? 1 : 0 }}">
                                                            @for($i = $service->is_mandatory ? 1 : 0; $i <= min((int) $service->max_buy_limit, 20); $i++)
                                                                <option value="{{ $i }}">{{ $i }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif

                        <div>
                            <div class="head-box-mini">
                                <h1 class="hd-sub">Attendee Information</h1>
                                <p>Information will be linked to your digital tickets.</p>
                            </div>
                            
                            <div class="grid-1 gap-form tickets-box">
                                <!-- Name -->
                                <div>
                                    <label for="name">Full Name *</label>
                                    <input type="text" class="form-control" id="name" placeholder="Enter your Full Name"
                                        required>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" placeholder="Enter your Email"
                                        required>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="ph">Phone No. *</label>
                                    <div class="flex" style="gap:7px;">
                                        <div class="select-box" style="max-width:fit-content">
                                            <i class="fa-solid fa-angle-down arrow-i"></i>
                                            <select name="phone_prefix">
                                                @include('website.components.country-codes')
                                            </select>
                                        </div>

                                        <div style="flex-grow:1">
                                            <input type="tel" name="phone" class="form-control" id="ph"
                                                placeholder="Enter your Phone No." required inputmode="numeric" pattern="[0-9]{5,12}" minlength="5" maxlength="12" autocomplete="tel">
                                        </div>
                                    </div>
                                </div>

                                <!-- Country / State -->
                                <div class="grid-auto gap-card">
                                    <div>
                                        <label for="countryId">Country *</label>
                                        <div class="select-box">
                                            <i class="fa-solid fa-angle-down arrow-i"></i>
                                            <select id="countryId" name="country_id" required>
                                                <option value="">Select Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}" @selected((string) $defaultCountryId === (string) $country->id)>{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="invalid-feedback" data-feedback-for="countryId"></div>
                                    </div>

                                    <div>
                                        <label for="stateId">County * (State)</label>
                                        <div class="select-box">
                                            <i class="fa-solid fa-angle-down arrow-i"></i>
                                            <select id="stateId" name="state_id" required disabled data-selected-state="{{ $defaultStateId }}">
                                                <option value="">Select County</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>

                    <!-- Bill Col 👩‍🦯-->
                    <div class="bill-col">
                        <!-- Coupon Box  -->
                       <!-- Apply Coupon -->
                        <div id="couponApplyBox">
                            <label>Coupon Code</label>
                            <div class="coupon-box">
                                <input type="text" class="form-control" id="coupon"
                                    placeholder="Enter your coupon code">
                                <span>
                                    <button type="button" id="applyCouponBtn"
                                        class="btn-md btn-prim"
                                        onclick="applyCoupon()">
                                        Apply
                                    </button>
                                </span>
                            </div>
                            <p style="font-size:11px; margin-top:4px">
                                <b>Note:</b> Coupon cannot be used when bulk discount is active.
                            </p>
                            <p id="couponMessage" style="font-size:12px; margin-top:6px; display:none"></p>
                        </div>

                        <!-- Active Coupon -->
                        <div id="couponActiveBox" style="display:none">
                            <label>Coupon Code</label>
                            <div class="coupon-box">
                                <input type="text" class="form-control active"
                                    id="activeCouponInput" readonly>
                                <span>
                                    <button type="button"
                                        class="btn-md btn-prim"
                                        onclick="removeCoupon()">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                    </button>
                                </span>
                            </div>
                        </div>


                        <!-- Bill 📄 -->
                        <div class="bill">
                          <p>Loading bill..</p>
                        </div>

                        <button type="submit" form="checkoutForm" class="btn-md btn-sec btn-w-full" id="checkoutSubmitBtn">
                            <span class="checkout-btn-spinner" aria-hidden="true"></span>
                            <span class="checkout-btn-label">Continue to Email Verification</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="checkout-page-loader" id="checkoutPageLoader" aria-hidden="true">
            <div class="checkout-loader-card">
                <div class="checkout-loader-spinner"></div>
                <strong>Please wait...</strong>
                <p class="mb-0">Starting email verification</p>
            </div>
        </div>
    </main>

    <!-- Remove selected Seat Modal -->
    <div class="popup-container pop-boxJS" id="remove-ticket-pop">
            <div class="popup w-s popJS">
                <!-- Header -->
                <div class="close-box">
                    <div class="title-box">
                        <h5 class="hd-sub">Remove Seat</h5>
                    </div>
                    <button class="btn-lg btn-close">
                        <i class="fa-regular fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="module-body">
                    <p class="mb-0 text-center" >Are you sure you want to remove this seat from your booking?</p>

                        <div class="modal-footer flex-center">
                            <button type="button" class="btn-sm btn-lite hover-lite-outline btn-close">Cancel</button>
                            <button type="button" class="btn-sm btn-prim hover-prim-outline btn-close"         id="confirmRemoveCheckoutSeatBtn">Remove</button>
                        </div>
                </div>
            </div>
    </div>

    <!-- Somethging Wents Wrong Popup Box -->
        <div class="popup-container pop-boxJS" id="checkoutAlertModal">
            <div class="popup w-s popJS">
                <!-- Header -->
                <div class="close-box">
                    <div class="title-box">
                        <h5 class="hd-sub" id="checkoutAlertTitle">Process Failed!</h5>
                    </div>
                    <button class="btn-lg btn-close">
                        <i class="fa-regular fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="module-body">
                    <p class="mb-0 text-center" id="checkoutAlertMessage">Something went wrong!! Please try again.</p>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn-sm btn-prim hover-prim-outline btn-close">Ok</button>
                        </div>
                </div>
            </div>
        </div>


    <!-- ####### FOOTER ####### -->

 @include('website._partials.Footer')

{{-- Stripe checkout script start --}}
<script>
    window.CHECKOUT_TOKEN = "{{ $checkout['token'] }}";
    window.checkoutSeats = @json($checkout['selected_seats'] ?? []);
    window.checkoutAgeGroupsEnabled = @json(!empty($ageGroups) && $ageGroups->count() > 0);
</script>

<script>
/* ===============================
   CONFIG (UNCHANGED)
================================ */
const API_BASE = "{{ url('/api/tickets') }}";
const EVENT_ID = {{ $checkout['event_id'] }};
const TICKET_TYPE_ID = {{ $checkout['ticket_type_id'] }};
const STATES_ENDPOINT_TEMPLATE = "{{ route('website.events.checkout.states', ['countryId' => '__COUNTRY__']) }}";
const CHECKOUT_CSRF_REFRESH_URL = @json(route('website.csrf_token'));

let currentQuantity = Number(@json((int) ($checkout['quantity'] ?? 1))) || 1;
let lastAvailableTickets = 0;
let appliedCoupon = null;
let bulkDiscountActive = false;
let pendingSeatToRemove = null;
let checkoutCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
const checkoutForm = document.getElementById('checkoutForm');
const checkoutSubmitBtn = document.getElementById('checkoutSubmitBtn');
const checkoutSubmitLabel = checkoutSubmitBtn?.querySelector('.checkout-btn-label');
const checkoutPageLoader = document.getElementById('checkoutPageLoader');
const removeCheckoutSeatModalElement = document.getElementById('removeCheckoutSeatModal');
const confirmRemoveCheckoutSeatBtn = document.getElementById('confirmRemoveCheckoutSeatBtn');
const checkoutAlertModalElement = document.getElementById('checkoutAlertModal');
const checkoutAlertTitle = document.getElementById('checkoutAlertTitle');
const checkoutAlertMessage = document.getElementById('checkoutAlertMessage');
let checkoutSubmitting = false;
let checkoutRedirecting = false;

function collectAgeGroupItems() {
    return Array.from(document.querySelectorAll('.age-group-qty'))
        .map((select) => ({
            id: Number(select.dataset.id),
            quantity: Number(select.value || 0),
        }))
        .filter((item) => item.id && item.quantity > 0);
}

function collectServiceItems() {
    return Array.from(document.querySelectorAll('.event-service-qty'))
        .map((select) => ({
            id: Number(select.dataset.id),
            quantity: Number(select.value || 0),
        }))
        .filter((item) => item.id && item.quantity > 0);
}

function resolveCheckoutQuantity() {
    if (window.checkoutAgeGroupsEnabled) {
        const total = Array.from(document.querySelectorAll('.age-group-qty'))
            .reduce((sum, select) => sum + Number(select.value || 0), 0);
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) quantityInput.value = total;
        currentQuantity = total;
        return total;
    }

    const quantity = Number(document.getElementById('quantity')?.value || 0);
    currentQuantity = quantity;
    return quantity;
}

function setCheckoutLoading(isLoading) {
    checkoutSubmitting = isLoading;

    if (checkoutSubmitBtn) {
        checkoutSubmitBtn.disabled = isLoading;
        checkoutSubmitBtn.classList.toggle('is-loading', isLoading);
        checkoutSubmitBtn.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    if (checkoutSubmitLabel) {
        checkoutSubmitLabel.textContent = isLoading ? 'Please wait...' : 'Continue to Email Verification';
    }

    checkoutPageLoader?.classList.toggle('is-visible', isLoading);
    checkoutPageLoader?.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
}

function getCheckoutValidationMessage(field) {
    const messages = {
        quantity: 'Ticket quantity is required.',
        name: 'Full name is required.',
        email: 'Email is required.',
        ph: 'Phone number is required.',
        countryId: 'Country is required.',
        stateId: 'County is required.',
    };

    if (field.validity.valueMissing) {
        return messages[field.id] || 'This field is required.';
    }

    if (field.id === 'ph') {
        const digitsOnly = field.value.replace(/\D/g, '');

        if (digitsOnly.length < 5) {
            return 'Phone number must be at least 5 digits.';
        }

        if (digitsOnly.length > 12) {
            return 'Phone number must not exceed 12 digits.';
        }
    }

    if (field.id === 'email' && field.validity.typeMismatch) {
        return 'Please enter a valid email address.';
    }

    return field.validationMessage || 'Please fill this field correctly.';
}

function getCheckoutFeedbackElement(field) {
    return document.querySelector(`[data-feedback-for="${field.id}"]`);
}

function clearCheckoutFieldState(field) {
    if (!field) return;

    field.classList.remove('is-invalid');

    const feedback = getCheckoutFeedbackElement(field);
    if (feedback) {
        feedback.textContent = '';
        feedback.style.display = 'none';
    }
}

function showCheckoutFieldError(field, message) {
    if (!field) return;

    field.classList.add('is-invalid');

    const feedback = getCheckoutFeedbackElement(field);
    if (feedback) {
        feedback.textContent = message;
        feedback.style.display = 'block';
    }
}

function validateCheckoutField(field) {
    if (!field || field.disabled) {
        clearCheckoutFieldState(field);
        return true;
    }

    field.setCustomValidity('');
    const message = getCheckoutValidationMessage(field);

    if (!field.checkValidity()) {
        field.setCustomValidity(message);
        showCheckoutFieldError(field, message);
        return false;
    }

    clearCheckoutFieldState(field);
    return true;
}

function validateCheckoutForm() {
    if (!checkoutForm) return true;

    checkoutForm.classList.add('was-validated');

    const fields = checkoutForm.querySelectorAll('#quantity, #name, #email, #ph, #countryId, #stateId');
    let firstInvalidField = null;

    fields.forEach((field) => {
        const isValid = validateCheckoutField(field);
        if (!isValid && !firstInvalidField) {
            firstInvalidField = field;
        }
    });

    if (window.checkoutAgeGroupsEnabled && resolveCheckoutQuantity() <= 0) {
        const quantityField = document.getElementById('quantity');
        showCheckoutFieldError(quantityField, 'Please select at least one age-group ticket.');
        firstInvalidField = firstInvalidField || quantityField;
    }

    if (firstInvalidField) {
        firstInvalidField.focus();
        return false;
    }

    return true;
}

function initializeCheckoutValidation() {
    if (!checkoutForm) return;

    const fields = checkoutForm.querySelectorAll('#quantity, #name, #email, #ph, #countryId, #stateId');

    fields.forEach((field) => {
        const eventName = field.tagName === 'SELECT' ? 'change' : 'input';

        field.addEventListener(eventName, () => {
            if (checkoutForm.classList.contains('was-validated')) {
                validateCheckoutField(field);
            } else {
                clearCheckoutFieldState(field);
            }
        });

        field.addEventListener('blur', () => {
            if (checkoutForm.classList.contains('was-validated')) {
                validateCheckoutField(field);
            }
        });
    });

    checkoutForm.addEventListener('submit', function(event) {
        event.preventDefault();

        if (checkoutSubmitting) {
            return;
        }

        if (!validateCheckoutForm()) {
            return;
        }

        startCheckout();
    });
}

function initializePhoneField() {
    const phoneInput = document.getElementById('ph');
    if (!phoneInput) return;

    const syncPhoneValue = () => {
        const digitsOnly = phoneInput.value.replace(/\D/g, '').slice(0, 12);
        if (phoneInput.value !== digitsOnly) {
            phoneInput.value = digitsOnly;
        }
    };

    phoneInput.addEventListener('input', syncPhoneValue);
    phoneInput.addEventListener('paste', () => setTimeout(syncPhoneValue, 0));
    phoneInput.addEventListener('keydown', (event) => {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
        if (allowedKeys.includes(event.key) || event.ctrlKey || event.metaKey) {
            return;
        }

        if (!/^\d$/.test(event.key) || phoneInput.value.length >= 12) {
            event.preventDefault();
        }
    });

    syncPhoneValue();
}

function initializeLocationSelectors() {
    const countrySelect = document.getElementById('countryId');
    const stateSelect = document.getElementById('stateId');

    if (!countrySelect || !stateSelect) return;

    resetStateOptions();

    countrySelect.addEventListener('change', function() {
        const countryId = this.value;

        if (!countryId) {
            resetStateOptions();
            return;
        }

        loadStatesForCountry(countryId);
    });

    if (countrySelect.value) {
        loadStatesForCountry(countrySelect.value, stateSelect.dataset.selectedState || '');
    }
}

function resetStateOptions(placeholder = 'Select County') {
    const stateSelect = document.getElementById('stateId');
    if (!stateSelect) return;

    stateSelect.innerHTML = `<option value="">${placeholder}</option>`;
    stateSelect.disabled = true;
    clearCheckoutFieldState(stateSelect);
}

function loadStatesForCountry(countryId, selectedStateId = '') {
    const stateSelect = document.getElementById('stateId');
    if (!stateSelect || !countryId) return;

    resetStateOptions('Loading states...');

    fetch(STATES_ENDPOINT_TEMPLATE.replace('__COUNTRY__', countryId), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
        .then(response => response.json())
        .then(states => {
            stateSelect.innerHTML = '<option value="">Select County</option>';

            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                if (String(selectedStateId) === String(state.id)) {
                    option.selected = true;
                }
                stateSelect.appendChild(option);
            });

            stateSelect.disabled = false;
        })
        .catch(error => {
            console.error('Failed to load states:', error);
            resetStateOptions('Unable to load states');
        });
}

function showCheckoutAlert(message, title = 'Notice') {
    checkoutAlertTitle.textContent = title;
    checkoutAlertMessage.textContent = message;
    showElement("#checkoutAlertModal");
}


//Remove selected seat
function removeSeatFromHold(seatId) {
    pendingSeatToRemove = seatId;
    // const modal = new bootstrap.Modal(removeCheckoutSeatModalElement);
    // modal.show();
    showElement("#remove-ticket-pop");

}

confirmRemoveCheckoutSeatBtn.addEventListener('click', function() {
    if (!pendingSeatToRemove) return;

    const confirmBtn = this;
    const seatId = pendingSeatToRemove;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Removing...';
    confirmBtn.disabled = true;

    fetchWithCheckoutCsrf("{{ route('website.events.checkout.remove_seat') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            token: window.CHECKOUT_TOKEN,
            seat_id: seatId
        })
    })
    .then(res => {
        if (!res.ok) return res.text().then(text => { throw new Error(text) });
        return res.json();
    })
    .then(data => {
        if (data.success) {
            // 1. Only now update the local JS data
            window.checkoutSeats = window.checkoutSeats.filter(seat => seat.id != seatId);

            // 2. Sync the hidden quantity input used by calculateBill
            const qtyInput = document.getElementById('quantity');
            if (qtyInput) {
                qtyInput.value = window.checkoutSeats.length;
                // Update global quantity variable if defined in your checkout.js
                if (typeof currentQuantity !== 'undefined') {
                    currentQuantity = window.checkoutSeats.length;
                }
            }

            // 3. Update the Seat UI (Labels)
            updateSeatsSectionUI();

            // 4. Trigger Bill Recalculation
            // This calls the API, which returns the new totals and triggers renderBill()
            if (typeof calculateBill === "function") {
                calculateBill();
            }
        } else {
            showCheckoutAlert(data.message || "Error removing seat.", 'Remove Seat');
        }
    })
    .catch(err => {
        console.error("Server Error:", err);
        showCheckoutAlert("Communication error with server.", 'Remove Seat');
    })
    .finally(() => {
        clodeIt(`#remove-ticket-pop`);
        confirmBtn.innerHTML = 'Remove';
        confirmBtn.disabled = false;
        pendingSeatToRemove = null;
    });
});

function updateSeatsSectionUI() {
    // Target the tag-box in the left form column
    const leftTags = document.querySelector('.selected-seats');
    if (!leftTags) return;

    if (window.checkoutSeats.length > 0) {
        leftTags.innerHTML = window.checkoutSeats.map(seat =>
            `<div class="tag">${seat.label}</div>`
        ).join('');
    }
}



// Save checkout details and start email verification before payment.
function startCheckout() {
    if (checkoutSubmitting) {
        return;
    }

    //Ticket qty
    const qty = resolveCheckoutQuantity();
    setCheckoutLoading(true);

    // Collect Car Registration Numbers
    const carNumbers = [];
    document.querySelectorAll(".car-slot-container input").forEach(input => {
        if (input.value.trim() !== "") carNumbers.push(input.value.trim());
    });

    //Get parking slots
    const activeSlotsCount = document.querySelectorAll('.car-slot-container .car-slot-item').length;

    const couponCode = appliedCoupon ? appliedCoupon.coupon_code : null;

    const payload = {
        token: window.CHECKOUT_TOKEN,
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('ph').value.trim(),
        country_id: document.getElementById('countryId').value,
        state_id: document.getElementById('stateId').value,
        quantity: qty,
        coupon_code: couponCode,
        parking_slots: activeSlotsCount || 0,
        car_details: carNumbers,
        service_items: collectServiceItems(),
        age_group_items: collectAgeGroupItems()
    };
    payload.phone_prefix = document.querySelector('select[name="phone_prefix"]')?.value || '';
    return fetchWithCheckoutCsrf("{{ route('website.events.checkout.stripe') }}", {
         method: "POST",
         headers: {
             "Content-Type": "application/json",
             "Accept": "application/json"
         },
         body: JSON.stringify(payload)
     })
     .then(async (res) => {
         const raw = await res.text();
         let data = {};
         try {
             data = raw ? JSON.parse(raw) : {};
         } catch (parseError) {
             throw new Error('Server returned invalid response. Please refresh and try again.');
         }

         if (!res.ok) {
             if (res.status === 422 && data.errors) {
                 Object.entries(data.errors).forEach(([field, messages]) => {
                     const fieldMap = {
                         quantity: document.getElementById('quantity'),
                         name: document.getElementById('name'),
                         email: document.getElementById('email'),
                         phone: document.getElementById('ph'),
                         country_id: document.getElementById('countryId'),
                         state_id: document.getElementById('stateId'),
                     };

                     const targetField = fieldMap[field];
                     if (targetField) {
                         showCheckoutFieldError(targetField, Array.isArray(messages) ? messages[0] : messages);
                     }
                 });

                 checkoutForm?.classList.add('was-validated');
                 return null;
             }

             throw new Error(data.message || 'Unable to continue checkout. Please try again.');
         }

         return data;
     })
     .then(data => {
         if (!data) return;

         if (data.url) {
             checkoutRedirecting = true;
             window.location.href = data.url;
         } else {
            showCheckoutAlert('Unable to continue checkout. Please try again.', 'Checkout');
         }
     })
     .catch((error) => {
         console.error('Checkout error:', error);
         showCheckoutAlert(error.message || 'Unable to continue checkout. Please try again.', 'Checkout');
     })
     .finally(() => {
         if (!checkoutRedirecting) {
             setCheckoutLoading(false);
         }
     });
}

/* ===============================
   Car Parking
================================ */
/* ======================================================
   INTEGRATION SCRIPT (Parking to Billing & Checkout)
   ====================================================== */
/* ======================================================
   PARKING & BILLING SYNC
   ====================================================== */
(function() {
    // 1. Hook into slot changes to refresh the bill
    document.addEventListener('click', (e) => {
        if (e.target.closest("#car-slot-btn-js") || e.target.closest(".delete-slot")) {
            // Small delay to allow your slotChecker to update ActiveSlots first

            setTimeout(() => {
                if (typeof calculateBill === "function") {
                    calculateBill();
                }
            }, 100);
        }
    });

})();

/* ===============================
   QUANTITY
================================ */
function fetchAvailableQuantity() {
    fetch(`${API_BASE}/available/${TICKET_TYPE_ID}?event_id=${EVENT_ID}`)
        .then(r => r.json())
        .then(data => {
            lastAvailableTickets = data.available_tickets;
            updateQuantityOptions(data.available_tickets);

            if (currentQuantity) {
                // For seat-based checkout, always set quantity and check discount
                if (window.checkoutSeats.length > 0) {
                    document.getElementById('quantity').value = currentQuantity;
                    checkBulkDiscount();
                } else if (currentQuantity <= data.available_tickets) {
                    // For normal checkout, check availability
                    document.getElementById('quantity').value = currentQuantity;
                    checkBulkDiscount();
                }
            }
        })
        .catch(err => {
            console.error('❌ fetchAvailableQuantity error:', err);
        });
}

function updateQuantityOptions(available) {
    const select = document.getElementById('quantity');
    if (window.checkoutAgeGroupsEnabled || select?.type === 'hidden') {
        return;
    }
    const preferredQuantity = Number(select.value || currentQuantity || 1);

    select.innerHTML = '<option value="">Select Quantity</option>';

    if (available <= 0) {
        select.innerHTML = '<option>No tickets available</option>';
        select.disabled = true;
        return;
    }

    for (let i = 1; i <= available; i++) {
        select.innerHTML += `<option value="${i}">${i}</option>`;
    }

    select.disabled = false;
    const nextQuantity = Math.min(Math.max(preferredQuantity, 1), available);
    select.value = String(nextQuantity);
    currentQuantity = nextQuantity;
}

function handleQuantityChange() {
    const qty = resolveCheckoutQuantity();

    // Invalid or zero quantity → reset & stop
    if (!Number.isInteger(qty) || qty <= 0) {
        resetBill();
        return;
    }

    currentQuantity = qty;

    // Update offer bar only if function exists
    if (typeof window.debouncedOfferBarFun === "function") {
        window.debouncedOfferBarFun(qty);
    }

    // Reset coupon when quantity changes
    appliedCoupon = null;

    // Re-check bulk discount
    if (typeof checkBulkDiscount === "function") {
        checkBulkDiscount();
    }
}


document
    .getElementById('quantity')
    .addEventListener('change', handleQuantityChange);

document.querySelectorAll('.age-group-qty, .event-service-qty').forEach((field) => {
    field.addEventListener('change', () => {
        if (field.classList.contains('age-group-qty')) {
            handleQuantityChange();
            return;
        }

        checkBulkDiscount();
    });
});

/* ===============================
   BULK DISCOUNT
================================ */
function checkBulkDiscount() {
    fetchWithCheckoutCsrf(`${API_BASE}/check-bulk-discount`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: EVENT_ID,
            ticket_type_id: TICKET_TYPE_ID,
            quantity: resolveCheckoutQuantity(),
            age_group_items: collectAgeGroupItems()
        })
    })
    .then(r => r.json())
    .then(d => {
        bulkDiscountActive = d.disable_coupon === true;
        toggleCouponUI(bulkDiscountActive);
        calculateBill();
    })
    .catch(err => {
        console.error('❌ checkBulkDiscount error:', err);
    });
}

/* ===============================
   COUPON
================================ */


// UPDATE COUPON UI
function updateCouponUI(response) {
    clearCouponMessage();
    const applyBox  = document.getElementById('couponApplyBox');
    const activeBox = document.getElementById('couponActiveBox');
    const applyBtn  = document.getElementById('applyCouponBtn');
    const activeInput = document.getElementById('activeCouponInput');

    bulkDiscountActive = response.bulk_discount_applied === true;

    // 🔴 Bulk discount active
    if (bulkDiscountActive) {
        applyBox.style.display = 'block';
        activeBox.style.display = 'none';
        applyBtn.disabled = true;
        applyBtn.innerText = 'Disabled';
        return;
    }

    // 🟢 Coupon applied
    if (response.coupon_applied) {
        applyBox.style.display = 'none';
        activeBox.style.display = 'block';
        activeInput.value = response.coupon_code;
        applyBtn.disabled = false;
        applyBtn.innerText = 'Apply';
        return;
    }

    // ⚪ Default state
    applyBox.style.display = 'block';
    activeBox.style.display = 'none';
    applyBtn.disabled = false;
    applyBtn.innerText = 'Apply';
}


function showCouponMessage(message, type = 'error') {
    const el = document.getElementById('couponMessage');
    el.innerText = message;
    el.style.display = 'block';
    el.style.color = type === 'success' ? 'green' : 'red';
}

function clearCouponMessage() {
    const el = document.getElementById('couponMessage');
    el.innerText = '';
    el.style.display = 'none';
}

function applyCoupon() {
    clearCouponMessage();

    if (bulkDiscountActive) {
        showCouponMessage(
            'Bulk discount is active. Coupons cannot be applied.'
        );
        return;
    }

    const code = document.getElementById('coupon').value.trim();

    if (!code) {
        showCouponMessage('Please enter a coupon code.');
        return;
    }

    fetchWithCheckoutCsrf(`${API_BASE}/apply-coupon`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: EVENT_ID,
            ticket_type_id: TICKET_TYPE_ID,
            coupon_code: code,
            quantity: resolveCheckoutQuantity(),
            age_group_items: collectAgeGroupItems()
        })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            showCouponMessage(d.message);
            return;
        }

        appliedCoupon = d;
        showCouponMessage('Coupon applied successfully!', 'success');
        calculateBill();
    })
    .catch(() => {
        showCouponMessage('Something went wrong. Please try again.');
    });
}


/**
 * ❌ REMOVE COUPON
 */
function removeCoupon() {
    appliedCoupon = null;
    document.getElementById('coupon').value = '';
    clearCouponMessage();
    calculateBill();
}

/* ===============================
   BILL
================================ */
function calculateBill() {
    const qty = resolveCheckoutQuantity();

    if (!Number.isInteger(qty) || qty <= 0) {
        resetBill();
        return;
    }

    // Collect Car Registration Numbers
    const carNumbers = [];
    document.querySelectorAll(".car-slot-container input").forEach(input => {
        if (input.value.trim() !== "") carNumbers.push(input.value.trim());
    });

    //Get parking slots
    const activeSlotsCount = document.querySelectorAll('.car-slot-container .car-slot-item').length;

    const payload = {
        event_id: EVENT_ID,
        ticket_type_id: TICKET_TYPE_ID,
        quantity: qty,
        coupon_code: appliedCoupon?.coupon_code ?? null,
        parking_slots: activeSlotsCount || 0,
        car_details: carNumbers,
        service_items: collectServiceItems(),
        age_group_items: collectAgeGroupItems()
    };

    fetchWithCheckoutCsrf(`${API_BASE}/calculate-bill`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(response => {
        // Keep coupon UI in sync with backend
        updateCouponUI(response);
        // Render price breakdown
        renderBill(response);
    })
    .catch(err => {
        console.error('❌ calculateBill error:', err);
    });
}

function resetBill() {
    document.querySelector('.bill').innerHTML = '';
}

/* ===============================
   HELPERS
================================ */
function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': checkoutCsrfToken
    };
}

function updateCheckoutCsrfToken(token) {
    checkoutCsrfToken = token;
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    document.querySelectorAll('input[name="_token"]').forEach(input => {
        input.value = token;
    });
}

function refreshCheckoutCsrfToken() {
    return fetch(CHECKOUT_CSRF_REFRESH_URL, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Unable to refresh session.');
        }

        return response.json();
    })
    .then(data => {
        if (!data.token) {
            throw new Error('Unable to refresh session.');
        }

        updateCheckoutCsrfToken(data.token);
        return data.token;
    });
}

function fetchWithCheckoutCsrf(url, options = {}, retried = false) {
    const headers = {
        ...(options.headers || {}),
        'X-CSRF-TOKEN': checkoutCsrfToken,
    };

    return fetch(url, {
        ...options,
        headers,
    }).then(response => {
        if (response.status !== 419 || retried) {
            return response;
        }

        return refreshCheckoutCsrfToken().then(() => {
            const retryHeaders = {
                ...(options.headers || {}),
                'X-CSRF-TOKEN': checkoutCsrfToken,
            };

            return fetch(url, {
                ...options,
                headers: retryHeaders,
            });
        });
    });
}

function toggleCouponUI(disable) {
    const btn = document.getElementById('applyCouponBtn');
    if (!btn) return;
    btn.disabled = disable;
    btn.style.opacity = disable ? '0.6' : '1';
}

/* ===============================
   RENDER BILL (UNCHANGED STRUCTURE)
================================ */

function renderBill(d) {
    // Check if the current hold has selected seats (Seating Event Type 2)
    const isSeatingEvent = window.checkoutSeats.length > 0;
    const hasAgeGroupItems = Array.isArray(d.age_group_items) && d.age_group_items.length > 0;

    let billingHtml = '';

    if (hasAgeGroupItems) {
        billingHtml = `
            <tr>
                <th colspan="2">
                    <h6 style="color: {{ $ticketType->ticket_type_color ?? 'orange' }}">${d.ticket_title}</h6>
                    <p>${d.quantity} Tickets</p>
                </th>
            </tr>
            ${d.age_group_items.map(item => `
                <tr>
                    <th>
                        ${item.label}
                        <p>${item.price}/- <i class="fa-solid fa-xmark i-mr i-ml"></i> ${item.quantity}</p>
                    </th>
                    <td>${item.total}/-</td>
                </tr>
            `).join('')}
        `;
    } else if (isSeatingEvent) {
        // --- SEATING EVENT ROWS (First + Second Row) ---
        billingHtml = `
            <tr>
                <th colspan="2">
                    <h6 style="color: {{ $ticketType->ticket_type_color ?? 'orange' }}">${d.ticket_title}</h6>
                    <div class="tag-box selected-seats">
                        ${window.checkoutSeats.map(seat => `
                            <div class="tag">${seat.label}
                                <button type="button" class="i-ml" onclick="removeSeatFromHold(${seat.id})">
                                    <i class="fa-regular fa-circle-xmark"></i>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                </th>
            </tr>
            <tr>
                <th>
                    <p>${d.ticket_price}/-
                       <i class="fa-solid fa-xmark i-mr i-ml"></i> ${d.quantity} Tickets
                    </p>
                </th>
                <td>${d.subtotal}/-</td>
            </tr>
        `;
    } else {
        // --- NORMAL EVENT ROW (Standard Layout) ---
        billingHtml = `
            <tr>
                <th>
                    <h6 style="color: {{ $ticketType->ticket_type_color ?? 'orange' }}">${d.ticket_title}</h6>
                    <p>${d.ticket_price}/-
                       <i class="fa-solid fa-xmark i-mr i-ml"></i> ${d.quantity} Tickets
                    </p>
                </th>
                <td>${d.subtotal}/-</td>
            </tr>
            ${d.bulk_discount_applied ? `
            <tr style="color: green;">
                <th>Bulk Discount (${d.bulk_discount_percentage}%)</th>
                <td>- ${d.bulk_discount_amount}/-</td>
            </tr>` : ''}

            ${d.coupon_applied ? `
            <tr style="color: green;">
                <th>Coupon: ${d.coupon_code} (${d.coupon_percentage}%)</th>
                <td>- ${d.coupon_amount}/-</td>
            </tr>` : ''}
        `;
    }

    // Combine with the rest of the bill (Parking, Discounts, Total)
    document.querySelector('.bill').innerHTML = `
        <table>
            ${billingHtml}

            ${d.parking_slots > 0 ? `
            <tr>
                <th colspan="1">
                    <h6 style="color: var(--my-primary)">Parking Slot</h6>
                    <p>${d.parking_price}/- <i class="fa-solid fa-xmark i-mr i-ml"></i> ${d.parking_slots} Slots</p>
                </th>
                <td>${d.parking_total}/-</td>
            </tr>` : ''}

            ${Array.isArray(d.service_items) && d.service_items.length ? `
            <tr>
                <th colspan="2" style="border: none; padding-bottom: 0;">
                   <h6 style="color: var(--my-primary)">Additional Services</h6>
                </th>
            </tr>
            ${d.service_items.map(service => `
                <tr>
                    <th style="border-color: #e5e5e5;">${service.name}<p>${service.price}/- <i class="fa-solid fa-xmark i-mr i-ml"></i> ${service.quantity}</p></th>
                    <td style="border-color: #e5e5e5;">${service.total}/-</td>
                </tr>
            `).join('')}` : ''}

            <tr>
                <th>Subtotal</th>
                <td>${d.order_subtotal ?? d.subtotal}/-</td>
            </tr>

            ${d.bulk_discount_applied ? `
            <tr style="color: green;">
                <th>Bulk Discount (${d.bulk_discount_percentage}%)</th>
                <td>- ${d.bulk_discount_amount}/-</td>
            </tr>` : ''}

            ${d.coupon_applied ? `
            <tr style="color: green;">
                <th>Coupon: ${d.coupon_code} (${d.coupon_percentage}%)</th>
                <td>- ${d.coupon_amount}/-</td>
            </tr>` : ''}

            ${d.enable_tax ? `
            <tr class="fee-row">
                <th>${d.tax_label} (${d.tax_value}%)</th>
                <td>+ ${d.tax_amount}</td>
            </tr>` : ''}

            ${d.enable_extra_charges ? `
            <tr class="fee-row">
                <th>${d.extra_charges_label} (${d.extra_charges_value}%)</th>
                <td>+ ${d.extra_charges_amount}</td>
            </tr>` : ''}

            <tr class="total-row">
                <th><h5>Total</h5></th>
                <td><h5>${d.total_amount}/-</h5></td>
            </tr>
        </table>
    `;
}

/* ===============================
   INIT
================================ */
fetchAvailableQuantity();
initializeLocationSelectors();
initializeCheckoutValidation();
initializePhoneField();
</script>

{{-- Offer bar handling --}}

<script>
    window.bulkDiscountSlabs = @json($slabs);
    window.hasSelectedSeats = @json(!empty($checkout['selected_seats']));
</script>
<script type="module">
    import { createOfferBar, offerBarFun, debounce }
    from "{{ asset('website/js/offer-bar.js') }}";

    function initOfferBar() {
        // --- CONDITION: If selected_seats are present, do not function ---
        if (window.hasSelectedSeats) {
            console.log("Offer Bar disabled for seating-based checkout.");
            return;
        }

        const slabs = window.bulkDiscountSlabs || [];
        const offerComp = document.querySelector(".offer-comp");

        if (!offerComp || slabs.length === 0) {
            offerComp?.classList.add("hidden");
            return; //  valid inside function
        }

        window.debouncedOfferBarFun = debounce((selectedTickets) => {
            offerBarFun(
                selectedTickets,
                slabs,
                offerComp.querySelector(".offer-bar")
            );
        }, 200);

        createOfferBar(offerComp, slabs);
        window.debouncedOfferBarFun(resolveCheckoutQuantity());
    }

    initOfferBar();
</script>

@endsection
