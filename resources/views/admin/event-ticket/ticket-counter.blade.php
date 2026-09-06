@extends('layouts.admin')

@section('head')
    <title>Ticket Counter</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')
    <link rel="stylesheet" href="{{ asset('style/page/ticket-counter.css') }}" />

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @php
        $currency = \App\Models\Currency::symbolForEvent($event ?? null);
        $irelandCountry = $countries->first(fn ($country) => strcasecmp($country->name, 'Ireland') === 0);
        $defaultCountryId = old('country_id', $irelandCountry?->id);
        $defaultStateId = old('state_id');
    @endphp
    <!-- PRELOADER -->
    @include('admin._partials.preloader')
    @include('admin._partials.preloader002')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <h5 class="hd-lg">Ticket Counter</h5>
            
            @if(!session('active_event_id'))
                <div class="alert alert-warning">
                    <strong>Warning:</strong> No active event selected. Please select an event first.
                </div>
            @endif
            
            {{-- @if(isset($ticketTypes))
                <small class="text-muted">Debug: Found {{ $ticketTypes->count() }} ticket types for event ID: {{ session('active_event_id') }}</small>
            @endif --}}

            <!-- Coupon Box -->
            <div class="coupon-section">

                <label for="coupon">Discount Coupon</label>

                <div class="coupon-box">
                    <input
                        type="text"
                        class="form-control"
                        id="coupon"
                        placeholder="Enter Coupon Code"
                        disabled
                    >

                    <button
                        type="button"
                        class="btn-sm btn-sec"
                        id="applyCouponBtn"
                        disabled
                    >
                        Apply
                    </button>
                    
                    <button
                        type="button"
                        class="btn-sm btn-sec"
                        id="removeCouponBtn"
                        style="display: none;"
                    >
                        <i class="fa-regular fa-circle-xmark"></i> 
                    </button>
                </div>

                <!-- 🔴 Coupon error message -->
                <small
                    id="couponErrorMsg"
                    class="text-danger"
                    style="display:none;margin-top:6px;"
                ></small>

                <!-- 🔴 Bulk discount message -->
                <small
                    id="bulkCouponMsg"
                    class="text-danger"
                    style="display:none;margin-top:6px;"
                >
                    Bulk discount active, cannot apply coupon code
                </small>

                <!-- ✅ Coupon success message -->
                <label
                    class="text-success"
                    id="couponSuccess"
                    style="display:none;margin-top:6px;"
                >
                    Coupon Applied
                    <i class="fa-solid fa-circle-check"></i>
                </label>

                <hr class="my-4" style="color: var(--color-border-200);">

            </div>


            <div class="grid-2 grid-sm-1 gap-col" style="align-items: flex-start;">
                <div>
                    <div class="promote-msg" id="promoteMsg" style="display: none;">
                        <p id="promoteMsgText"><b></b></p>
                    </div>

                    <!-- Form -->
                    <form class="needs-validation" id="ticketForm" novalidate="">
                        @csrf
                        <input type="hidden" id="couponValid" name="coupon_valid" value="false">
                        <input type="hidden" id="couponCode" name="coupon_code" value="">
                        <input type="hidden" id="couponAmount" name="coupon_amount" value="0">
                        <input type="hidden" id="couponPercentage" name="coupon_percentage" value="0">
                        @include('admin.event-ticket._partials.candidate-voting')
                        <div class="grid-1 gap-card">
                            <!-- Ticket Type -->
                            <div class="form-floating">
                                <select class="form-select" id="ticketType" name="ticket_type_id" required="">
                                    <option value="">Select Ticket Type</option>
                                    @if(isset($ticketTypes) && $ticketTypes->count() > 0)
                                        @foreach ($ticketTypes as $ticketType)
                                            @php
                                                $displayTicketPrice = $ticketType->enable_age_group && $ticketType->ageGroups->count() ? $ticketType->ageGroups->min('price') : $ticketType->ticket_price;
                                            @endphp
                                            <option value="{{ $ticketType->id }}" data-price="{{ $displayTicketPrice }}"
                                                data-title="{{ $ticketType->title }}"
                                                data-enable-age-group="{{ $ticketType->enable_age_group ? 1 : 0 }}">
                                                {{ $ticketType->title }} - {{ $currency }}{{ number_format((float) $displayTicketPrice, 2) }}{{ $ticketType->enable_age_group ? ' onwards' : '' }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option disabled>No ticket types available for this event</option>
                                    @endif
                                </select>
                                <label for="ticketType">Ticket Type</label>
                            </div>

                            <!-- Qty -->
                            <div class="form-floating" id="quantityField">
                                <select class="form-select" id="quantity" name="quantity" required="">
                                    <option value="">Select Quantity</option>
                                </select>
                                <label for="quantity">Qty</label>
                            </div>

                            <div class="style-box" id="ageGroupSection" style="display: none;">
                                <h6 class="hd-sm mb-2">Ticket Age Groups</h6>
                                <div class="grid-1 gap-card" id="ageGroupRows"></div>
                                <small class="text-danger" id="ageGroupError" style="display:none;margin-top:6px;"></small>
                            </div>

                            <!-- Name -->
                            <div class="form-floating">
                                <input type="text" class="form-control" id="xname" name="name" required>
                                <label for="xname">Name</label>
                            </div>

                            <!-- Email -->
                            <div class="form-floating">
                                <input type="email" class="form-control" id="Emailx" name="email" required>
                                <label for="Emailx">Email</label>
                            </div>

                            <!-- Mobile -->
                            <div class="d-flex g-2">
                                <div class="form-floating flex-shrink-0 me-2">
                                    <select class="form-select" id="phonePrefix" name="phone_prefix">
                                        @include('admin._partials.options.prefix-options', ['selected' => old('phone_prefix','+353')])
                                    </select>
                                    <label for="phonePrefix">Prefix</label>
                                </div>
                            
                                <div class="form-floating flex-grow-1">
                                    <input type="text" class="form-control" id="phno" name="mobile_number" required inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" autocomplete="tel" />
                                    <label for="phno">Mobile Number</label>
                                </div>
                            </div>

                            <!-- Country -->
                            <div class="form-floating">
                                <select class="form-select" id="countryId" name="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $defaultCountryId === (string) $country->id)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                                <label for="countryId">Country</label>
                            </div>

                            <!-- State -->
                            <div class="form-floating">
                                <select class="form-select" id="stateId" name="state_id" required disabled data-selected-state="{{ $defaultStateId }}">
                                    <option value="">Select State</option>
                                </select>
                                <label for="stateId">State</label>
                            </div>

                            @include('admin.event-ticket._partials.additional-services')

                            <button type="submit" class="btn-md btn-sec" id="buyTicketBtn">
                                <span class="btn-text">Buy Ticket <i class="fa-solid fa-ticket"></i></span>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div>
                    <h4 class="hd-md text-center text-uppercase">Customer's Bill</h4>
                    <table class="table view-table bill-table">
                        <tbody id="billTableBody">
                            <tr>
                                <th colspan="2">
                                    <div>
                                        <h6 class="text-center"><i class="fa-solid fa-ticket"></i>
                                            Ticket: <span style="color: var(--color-primary);" id="billTicketName">Select
                                                Ticket Type</span> </h6>
                                    </div>
                                </th>
                            </tr>
                            <tr id="ticketPriceRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6>Ticket Price</h6>
                                        <p id="ticketPriceDetails">{{ $currency }}250/- <i class="fa-solid fa-xmark mx-2"></i> 2 pcs</p>
                                    </div>
                                </th>
                                <td id="ticketPriceAmount">{{ $currency }}500/-</td>
                            </tr>
                            <tr id="servicesRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6>Additional Services</h6>
                                        <p id="serviceDetails"></p>
                                    </div>
                                </th>
                                <td id="serviceAmount"></td>
                            </tr>
                            <tr id="subtotalRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6>Subtotal</h6>
                                    </div>
                                </th>
                                <td id="subtotalAmount"></td>
                            </tr>
                            <tr id="bulkDiscountRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6>Bulk Ticket Discount</h6>
                                        <p id="bulkDiscountDetails">20% off</p>
                                    </div>
                                </th>
                                <td class="text-danger" id="bulkDiscountAmount"><s>{{ $currency }}100/-</s></td>
                            </tr>
                            <tr id="couponAppliedRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6>Coupon Applied</h6>
                                        <p id="couponAppliedDetails">[VFFS55] 10% off</p>
                                    </div>
                                </th>
                                <td class="text-danger" id="couponAppliedAmount"><s>{{ $currency }}100/-</s></td>
                            </tr> 
                            
                            <tr id="extraChargesRow" style="display: none;">
                                <th><h6 id="extraChargesLabel">Extra Charges</h6></th>
                                <td id="extraChargesAmount"></td>
                            </tr>

                            <tr id="taxRow" style="display: none;">
                                <th><h6 id="taxLabel">Tax</h6></th>
                                <td id="taxAmount"></td>
                            </tr>

                            <tr id="totalAmountRow" style="display: none;">
                                <th>
                                    <div>
                                        <h6 style="color: var(--color-primary);">Total Amount</h6>
                                    </div>
                                </th>
                                <td style="color: var(--color-primary);" id="totalAmount">{{ $currency }}400/-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

<script>
const API_BASE = "{{ url('/api/tickets') }}";
const EVENT_ID = "{{ session('active_event_id') }}";
const STATES_ENDPOINT_TEMPLATE = "{{ route('admin.ticket.counter.api.states', ['countryId' => '__COUNTRY__']) }}";
</script>

<script>
let currentTicketType = null;
let currentQuantity = 0;
let appliedCoupon = null;
let pollInterval = null;
let lastAvailableTickets = null;
let currentAgeGroups = [];

document.addEventListener('DOMContentLoaded', () => {
    if (!EVENT_ID) {
        createNotification("error", "Event not selected", "");
        return;
    }
    initializeLocationSelectors();
    initializeEventListeners();
    startPolling();
});

/* ---------------- INIT ---------------- */

function initializeEventListeners() {
    document.getElementById('ticketType').addEventListener('change', handleTicketTypeChange);
    document.getElementById('quantity').addEventListener('change', handleQuantityChange);
    document.getElementById('applyCouponBtn').addEventListener('click', applyCoupon);
    document.getElementById('removeCouponBtn').addEventListener('click', removeCoupon);
    document.getElementById('ticketForm').addEventListener('submit', handleFormSubmit);
    document.querySelectorAll('.admin-event-service-qty').forEach((field) => {
        field.addEventListener('change', () => {
            syncReservedServiceFields(field.closest('.admin-event-service-row'));
            if (currentTicketType && resolveTicketQuantity() > 0) {
                calculateBill();
            }
        });
    });
    initializeMobileNumberField();
}

function initializeMobileNumberField() {
    const mobileInput = document.getElementById('phno');
    if (!mobileInput) return;

    const syncMobileValue = () => {
        const digitsOnly = mobileInput.value.replace(/\D/g, '').slice(0, 12);
        if (mobileInput.value !== digitsOnly) {
            mobileInput.value = digitsOnly;
        }

        if (!digitsOnly.length) {
            mobileInput.setCustomValidity('Mobile number is required.');
        } else if (digitsOnly.length > 12) {
            mobileInput.setCustomValidity('Mobile number must not exceed 12 digits.');
        } else {
            mobileInput.setCustomValidity('');
        }
    };

    mobileInput.addEventListener('input', syncMobileValue);
    mobileInput.addEventListener('paste', () => setTimeout(syncMobileValue, 0));
    mobileInput.addEventListener('blur', syncMobileValue);
    mobileInput.addEventListener('keydown', (event) => {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
        if (allowedKeys.includes(event.key) || (event.ctrlKey || event.metaKey)) {
            return;
        }

        if (!/^\d$/.test(event.key) || mobileInput.value.length >= 12) {
            event.preventDefault();
        }
    });

    syncMobileValue();
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

function resetStateOptions(placeholder = 'Select State') {
    const stateSelect = document.getElementById('stateId');
    if (!stateSelect) return;

    stateSelect.innerHTML = `<option value="">${placeholder}</option>`;
    stateSelect.disabled = true;
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
            stateSelect.innerHTML = '<option value="">Select State</option>';

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

function startPolling() {
    pollInterval = setInterval(updateAvailableQuantities, 3000);
}

/* ---------------- POLLING ---------------- */

function updateAvailableQuantities() {
    if (!currentTicketType) return;

    fetch(`${API_BASE}/available/${currentTicketType}?event_id=${EVENT_ID}`)
        .then(r => r.json())
        .then(data => {
            if (data.available_tickets !== lastAvailableTickets) {
                lastAvailableTickets = data.available_tickets;
                updateQuantityOptions(data.available_tickets);
            }
        })
        .catch(console.error);
}

/* ---------------- TICKET TYPE ---------------- */

function handleTicketTypeChange() {
    const select = document.getElementById('ticketType');
    const option = select.options[select.selectedIndex];

    if (!select.value) {
        renderAgeGroupFields([]);
        resetAll();
        disableCouponSection();
        return;
    }

    currentTicketType = select.value;
    lastAvailableTickets = null;
    syncServiceFields();

    document.getElementById('billTicketName').innerText = option.dataset.title;

    fetchAvailableQuantity();
    resetCoupon();
    
    // Keep coupon disabled until quantity is also selected
    disableCouponSection();
}

/* ---------------- QUANTITY ---------------- */

function fetchAvailableQuantity() {
    fetch(`${API_BASE}/available/${currentTicketType}?event_id=${EVENT_ID}`)
        .then(r => r.json())
        .then(data => {
            lastAvailableTickets = data.available_tickets;
            renderAgeGroupFields(data.enable_age_group ? data.age_groups : []);
            updateQuantityOptions(data.available_tickets);
            resetBill();
            hidePromoteMessage();
        })
        .catch(console.error);
}

function updateQuantityOptions(available) {
    const select = document.getElementById('quantity');
    const prev = select.value;

    if (isAgeGroupMode()) {
        select.innerHTML = '<option value="">Age group quantity</option>';
        select.disabled = true;
        select.required = false;
        return;
    }

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
    if (prev && prev <= available) select.value = prev;
}

function handleQuantityChange() {
    const qty = resolveTicketQuantity();
    if (!qty || !currentTicketType) {
        disableCouponSection();
        return resetBill();
    }

    if (!validateAgeGroupSelection()) {
        disableCouponSection();
        return resetBill();
    }
    
    // Enable coupon section when both ticket type and quantity are selected
    enableCouponSection();

    checkBulkDiscount();
    calculateBill();
}

function enableCouponSection() {
    const couponInput = document.getElementById('coupon');
    const applyBtn = document.getElementById('applyCouponBtn');
    
    couponInput.disabled = false;
    applyBtn.disabled = false;
}

function disableCouponSection() {
    const couponInput = document.getElementById('coupon');
    const applyBtn = document.getElementById('applyCouponBtn');
    
    couponInput.disabled = true;
    applyBtn.disabled = true;
    
    // Reset coupon if it was applied
    resetCoupon();
}

function resetQuantityOptions() {
    currentAgeGroups = [];
    const quantitySelect = document.getElementById('quantity');
    quantitySelect.innerHTML = '<option value="">Select Quantity</option>';
    quantitySelect.disabled = false;
    quantitySelect.required = true;
    document.getElementById('quantityField').style.display = '';
}

function isAgeGroupMode() {
    return currentAgeGroups.length > 0;
}

function collectAgeGroupItems() {
    return Array.from(document.querySelectorAll('.admin-age-group-qty'))
        .map((select) => ({
            id: Number(select.dataset.id),
            quantity: Number(select.value || 0),
        }))
        .filter((item) => item.id && item.quantity > 0);
}

function collectServiceItems() {
    return Array.from(document.querySelectorAll('.admin-event-service-row'))
        .map((row) => {
            const select = row.querySelector('.admin-event-service-qty:not(:disabled)');
            const quantity = Number(select?.value || 0);

            return {
                id: Number(select?.dataset.id || 0),
                quantity,
                field_values: collectReservedServiceFieldValues(row),
            };
        })
        .filter((item) => item.id && item.quantity > 0);
}

function collectReservedServiceFieldValues(row) {
    return Array.from(row?.querySelectorAll('.admin-event-service-unit') || []).map((unit) => {
        const values = {};

        unit.querySelectorAll('.admin-event-service-field-group').forEach((group) => {
            const fieldId = group.dataset.fieldId;
            const fieldType = group.dataset.fieldType;
            const controls = Array.from(group.querySelectorAll('.admin-event-service-field'));

            if (fieldType === 'radio') {
                values[fieldId] = controls.find((control) => control.checked)?.value || '';
            } else if (fieldType === 'checkbox') {
                values[fieldId] = controls[0]?.checked ? 1 : 0;
            } else {
                values[fieldId] = controls[0]?.value ?? '';
            }
        });

        return values;
    });
}

function syncReservedServiceFields(row) {
    if (!row || row.dataset.reserved !== '1') return;

    const container = row.querySelector('.admin-event-service-fields');
    const template = row.querySelector('.admin-event-service-unit-template');
    const select = row.querySelector('.admin-event-service-qty');
    if (!container || !template || !select) return;

    const existingValues = collectReservedServiceFieldValues(row);
    const quantity = select.disabled ? 0 : Number(select.value || 0);
    container.innerHTML = '';
    container.style.display = quantity > 0 ? '' : 'none';

    for (let unitIndex = 0; unitIndex < quantity; unitIndex++) {
        const fragment = template.content.cloneNode(true);
        const unit = fragment.querySelector('.admin-event-service-unit');
        const title = fragment.querySelector('.admin-event-service-unit-title');
        const serviceId = Number(select.dataset.id);
        title.textContent = quantity > 1 ? `Details for unit ${unitIndex + 1}` : 'Service details';
        unit.dataset.unitIndex = unitIndex;

        unit.querySelectorAll('.admin-event-service-field-group').forEach((group) => {
            const fieldId = group.dataset.fieldId;
            const fieldType = group.dataset.fieldType;
            const required = group.dataset.required === '1';
            const controls = Array.from(group.querySelectorAll('.admin-event-service-field'));
            const savedValue = existingValues[unitIndex]?.[fieldId];
            const fieldName = `service_response_${serviceId}_${unitIndex}_${fieldId}`;

            controls.forEach((control, optionIndex) => {
                control.disabled = false;
                control.required = required;
                control.name = fieldType === 'radio' ? fieldName : '';
                control.id = `${fieldName}_${optionIndex}`;

                if (savedValue === undefined) return;
                if (fieldType === 'radio') {
                    control.checked = String(control.value) === String(savedValue);
                } else if (fieldType === 'checkbox') {
                    control.checked = Boolean(Number(savedValue));
                } else {
                    control.value = savedValue ?? '';
                }
            });
        });

        container.appendChild(fragment);
    }
}

function serviceAppliesToCurrentTicket(row) {
    const ticketTypes = JSON.parse(row.dataset.ticketTypes || '[]');
    return ticketTypes.length === 0 || ticketTypes.includes(Number(currentTicketType));
}

function syncServiceFields() {
    const section = document.getElementById('eventServiceSection');
    if (!section) return;

    let hasVisibleService = false;

    section.querySelectorAll('.admin-event-service-row').forEach((row) => {
        const select = row.querySelector('.admin-event-service-qty');
        const applies = currentTicketType && serviceAppliesToCurrentTicket(row);

        row.style.display = applies ? '' : 'none';
        if (select) {
            select.disabled = !applies;
            if (applies && select.dataset.mandatory === '1' && Number(select.value || 0) <= 0) {
                select.value = '1';
            }
        }

        syncReservedServiceFields(row);

        hasVisibleService = hasVisibleService || Boolean(applies);
    });

    section.style.display = hasVisibleService ? '' : 'none';
}

function resolveTicketQuantity() {
    if (isAgeGroupMode()) {
        currentQuantity = collectAgeGroupItems().reduce((sum, item) => sum + item.quantity, 0);
        return currentQuantity;
    }

    currentQuantity = parseInt(document.getElementById('quantity').value || 0, 10);
    return currentQuantity;
}

function renderAgeGroupFields(ageGroups = []) {
    currentAgeGroups = Array.isArray(ageGroups) ? ageGroups.filter(group => Number(group.available_tickets || 0) > 0) : [];

    const section = document.getElementById('ageGroupSection');
    const rows = document.getElementById('ageGroupRows');
    const quantityField = document.getElementById('quantityField');
    const quantitySelect = document.getElementById('quantity');
    const error = document.getElementById('ageGroupError');

    rows.innerHTML = '';
    error.style.display = 'none';

    if (!isAgeGroupMode()) {
        section.style.display = 'none';
        quantityField.style.display = '';
        quantitySelect.disabled = false;
        quantitySelect.required = true;
        return;
    }

    section.style.display = 'block';
    quantityField.style.display = 'none';
    quantitySelect.disabled = true;
    quantitySelect.required = false;

    rows.innerHTML = currentAgeGroups.map((group) => {
        const maxQty = Math.min(Number(group.max_quantity_per_booking || 20), Number(group.available_tickets || 0), 20);
        const minQty = group.is_compulsory ? 1 : 0;
        const options = Array.from({ length: Math.max(0, maxQty - minQty + 1) }, (_, index) => index + minQty)
            .map(qty => `<option value="${qty}">${qty}</option>`)
            .join('');

        return `
            <div class="style-box">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-card">
                    <div>
                        <h6 class="hd-sm mb-1">${group.label}</h6>
                        <p class="mb-0">{{ $currency }}${Number(group.price || 0).toFixed(2)} /- [${group.available_tickets} Available]</p>
                    </div>
                    <div class="form-floating" style="min-width: 140px;">
                        <select class="form-select admin-age-group-qty" data-id="${group.id}" required>
                            ${options}
                        </select>
                        <label>Qty</label>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    rows.querySelectorAll('.admin-age-group-qty').forEach((field) => {
        field.addEventListener('change', handleQuantityChange);
    });
}

function validateAgeGroupSelection() {
    const error = document.getElementById('ageGroupError');

    if (!isAgeGroupMode()) {
        return true;
    }

    const quantity = resolveTicketQuantity();
    if (quantity <= 0) {
        error.textContent = 'Please select at least one age-group ticket.';
        error.style.display = 'block';
        return false;
    }

    if (quantity > 20) {
        error.textContent = 'Maximum 20 tickets allowed in one booking.';
        error.style.display = 'block';
        return false;
    }

    error.style.display = 'none';
    return true;
}

/* ---------------- BULK DISCOUNT ---------------- */
function checkBulkDiscount() {
    fetch(`${API_BASE}/check-bulk-discount`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: EVENT_ID,
            ticket_type_id: currentTicketType,
            quantity: resolveTicketQuantity(),
            age_group_items: collectAgeGroupItems()
        })
    })
    .then(r => r.json())
    .then(d => {
       
        const applyBtn = document.getElementById('applyCouponBtn');
        const couponBox = applyBtn.closest('.coupon-box');

        let msg = document.getElementById('bulkCouponMsg');
        if (!msg) {
            msg = document.createElement('small');
            msg.id = 'bulkCouponMsg';
            msg.style.color = '#d9534f';
            msg.style.display = 'none';
            couponBox.after(msg);
        }

        if (d.disable_coupon) {
            // 🔴 Bulk discount ACTIVE
            applyBtn.disabled = true;
            applyBtn.classList.add('disabled');

            msg.textContent = 'Bulk discount active, cannot apply coupon code';
            msg.style.display = 'block';

            // Auto-remove coupon if already applied
            resetCoupon();
        } else {
            // 🟢 Bulk discount NOT active
            applyBtn.disabled = false;
            applyBtn.classList.remove('disabled');
            msg.style.display = 'none';
        }

        d.has_bulk_discount ? showPromoteMessage(d) : hidePromoteMessage();
    })
    .catch(console.error);
}

/* ---------------- COUPON ---------------- */
function applyCoupon() {
    const applyBtn = document.getElementById('applyCouponBtn');
    const errorMsg = document.getElementById('couponErrorMsg');
    
    // Clear previous error
    errorMsg.style.display = 'none';

    if (applyBtn.disabled) {
        errorMsg.textContent = 'Bulk discount active, cannot apply coupon code';
        errorMsg.style.display = 'block';
        return;
    }

    const code = document.getElementById('coupon').value.trim();
    if (!code) {
        errorMsg.textContent = 'Enter coupon code';
        errorMsg.style.display = 'block';
        return;
    }

    fetch(`${API_BASE}/apply-coupon`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: EVENT_ID,
            ticket_type_id: currentTicketType,
            coupon_code: code,
            quantity: resolveTicketQuantity(),
            age_group_items: collectAgeGroupItems()
        })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            errorMsg.textContent = d.message;
            errorMsg.style.display = 'block';
            return;
        }

        appliedCoupon = d;
        
        // Show success state
        document.getElementById('couponSuccess').style.display = 'block';
        document.getElementById('applyCouponBtn').style.display = 'none';
        document.getElementById('removeCouponBtn').style.display = 'inline-block';
        document.getElementById('coupon').disabled = true;
        
        // Add green validation styling
        const couponInput = document.getElementById('coupon');
        couponInput.classList.add('is-valid');
        couponInput.classList.remove('is-invalid');
        couponInput.style.color = '#198754 !important'; // Bootstrap success green color
        couponInput.style.borderColor = '#198754 !important'; // Green border
        couponInput.style.backgroundColor = '#d1e7dd !important'; // Light green background
        
        calculateBill();
    })
    .catch(console.error);
}

function removeCoupon() {
    appliedCoupon = null;
    const couponInput = document.getElementById('coupon');
    
    // Reset UI state
    couponInput.value = '';
    couponInput.disabled = false;
    couponInput.style.color = ''; // Reset text color
    couponInput.style.borderColor = ''; // Reset border color
    couponInput.style.backgroundColor = ''; // Reset background color
    couponInput.classList.remove('is-valid', 'is-invalid'); // Remove validation classes
    
    document.getElementById('couponSuccess').style.display = 'none';
    document.getElementById('couponErrorMsg').style.display = 'none';
    document.getElementById('applyCouponBtn').style.display = 'inline-block';
    document.getElementById('removeCouponBtn').style.display = 'none';
    
    // Recalculate bill without coupon
    if (currentTicketType && currentQuantity) {
        calculateBill();
    }
}

/* ---------------- BILL ---------------- */

function calculateBill() {
    fetch(`${API_BASE}/calculate-bill`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: EVENT_ID,
            ticket_type_id: currentTicketType,
            quantity: resolveTicketQuantity(),
            coupon_code: appliedCoupon?.coupon_code || null,
            service_items: collectServiceItems(),
            age_group_items: collectAgeGroupItems()
        })
    })
    .then(r => r.json())
    .then(updateBillDisplay)
    .catch(console.error);
}

/* ---------------- Update Bill Display ---------------- */

function updateBillDisplay(data) {
    if (data.success === false) {
        createNotification("error", data.message || "Unable to calculate bill", "");
        resetBill();
        return;
    }

    //console.log(data);
    // 1. Ticket Base Price
    document.getElementById('ticketPriceRow').style.display = 'table-row';

    if (Array.isArray(data.age_group_items) && data.age_group_items.length) {
        document.getElementById('ticketPriceDetails').innerHTML = data.age_group_items.map(item =>
            `${item.label}: ${item.price}/- <i class="fa-solid fa-xmark mx-2"></i> ${item.quantity} pcs`
        ).join('<br>');
    } else {
        document.getElementById('ticketPriceDetails').innerHTML =
            `${data.ticket_price}/- <i class="fa-solid fa-xmark mx-2"></i> ${data.quantity} pcs`;
    }
    document.getElementById('ticketPriceAmount').textContent = `${data.subtotal}/-`;

    // 2. Additional Services
    const servicesRow = document.getElementById('servicesRow');
    if (servicesRow) {
        if (Array.isArray(data.service_items) && data.service_items.length) {
            servicesRow.style.display = 'table-row';
            document.getElementById('serviceDetails').innerHTML = data.service_items.map(service =>
                `${service.name}: ${service.price}/- <i class="fa-solid fa-xmark mx-2"></i> ${service.quantity}`
            ).join('<br>');
            document.getElementById('serviceAmount').innerHTML = data.service_items.map(service =>
                `${service.total}/-`
            ).join('<br>');
        } else {
            servicesRow.style.display = 'none';
        }
    }

    const subtotalRow = document.getElementById('subtotalRow');
    if (subtotalRow) {
        subtotalRow.style.display = 'table-row';
        document.getElementById('subtotalAmount').textContent = `${data.order_subtotal || data.subtotal}/-`;
    }

     // 4. Bulk Discount Row
    if (data.bulk_discount_applied) {
        document.getElementById('bulkDiscountRow').style.display = 'table-row';
        document.getElementById('bulkDiscountDetails').textContent = `${data.bulk_discount_percentage}% off`;
        document.getElementById('bulkDiscountAmount').innerHTML =
            `<span class="text-danger">-${data.bulk_discount_amount}/-</span>`;
    } else {
        document.getElementById('bulkDiscountRow').style.display = 'none';
    }

    // 4. Coupon Row (Hidden if Bulk is applied)
    if (data.coupon_applied && !data.bulk_discount_applied) {
        document.getElementById('couponAppliedRow').style.display = 'table-row';
        document.getElementById('couponAppliedDetails').textContent =
            `[${data.coupon_code}] ${data.coupon_percentage}% off`;
        document.getElementById('couponAppliedAmount').innerHTML =
            `<span class="text-danger">${data.coupon_amount}/-</span>`;
        // Update hidden field with calculated coupon amount
        document.getElementById('couponAmount').value = data.coupon_amount || 0;
    } else {
        document.getElementById('couponAppliedRow').style.display = 'none';
        // Reset coupon amount when not applied or when bulk discount takes priority
        document.getElementById('couponAmount').value = 0;
    }

    // 5. Extra Charges (e.g., Platform Fee)
    const extraRow = document.getElementById('extraChargesRow');
    if (extraRow) {
        if (data.enable_extra_charges && parseFloat(data.extra_charges_value) > 0) {
            extraRow.style.display = 'table-row';
            // Dynamically set the label (e.g., "platform fee")
            document.getElementById('extraChargesLabel').textContent = `${data.extra_charges_label} (${data.extra_charges_value}%)`;
            document.getElementById('extraChargesAmount').textContent = `${data.extra_charges_amount}/-`;
        } else {
            extraRow.style.display = 'none';
        }
    }

    // 6. Tax (e.g., Service Fee)
    const taxRow = document.getElementById('taxRow');
    if (taxRow) {
        if (data.enable_tax && parseFloat(data.tax_value) > 0) {
            taxRow.style.display = 'table-row';
            // Dynamically set the label (e.g., "service fee")
            document.getElementById('taxLabel').textContent = `${data.tax_label} (${data.tax_value}%)`;
            document.getElementById('taxAmount').textContent = `${data.tax_amount}/-`;
        } else {
            taxRow.style.display = 'none';
        }
    }

    // 7. Final Total
    document.getElementById('totalAmountRow').style.display = 'table-row';
    document.getElementById('totalAmount').textContent = `${data.total_amount}/-`;
}



/* ---------------- SUBMIT ---------------- */

function handleFormSubmit(e) {
    e.preventDefault();

    const mobileInput = document.getElementById('phno');
    if (mobileInput) {
        mobileInput.dispatchEvent(new Event('input'));
    }

    if (!e.target.checkValidity()) {
        e.target.classList.add('was-validated');
        const firstInvalidField = e.target.querySelector(':invalid');
        if (firstInvalidField) {
            firstInvalidField.focus();
            createNotification("error", firstInvalidField.validationMessage, "");
        }
        return;
    }

    if (!validateAgeGroupSelection()) {
        return;
    }

    const btn = document.getElementById('buyTicketBtn');
    const btnText = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner-border');
    const actionLoader = document.getElementById('actionLoader');
    
    // Show loader
    if (actionLoader) {
        actionLoader.style.display = 'flex';
    }
    btn.disabled = true;
    btnText.style.display = 'none';
    spinner.style.display = 'inline-block';

    const formData = new FormData(e.target);
    formData.set('coupon_code', appliedCoupon?.coupon_code || '');
    formData.set('coupon_valid', appliedCoupon ? 'true' : 'false');
    formData.set('quantity', resolveTicketQuantity() || '');
    formData.append('event_id', EVENT_ID);

    collectAgeGroupItems().forEach((item, index) => {
        formData.append(`age_group_items[${index}][id]`, item.id);
        formData.append(`age_group_items[${index}][quantity]`, item.quantity);
    });

    collectServiceItems().forEach((item, index) => {
        formData.append(`service_items[${index}][id]`, item.id);
        formData.append(`service_items[${index}][quantity]`, item.quantity);
        item.field_values.forEach((unitValues, unitIndex) => {
            Object.entries(unitValues).forEach(([fieldId, value]) => {
                formData.append(`service_items[${index}][field_values][${unitIndex}][${fieldId}]`, value);
            });
        });
    });
    
    fetch(`${API_BASE}/purchase`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            createNotification("error", d.message, "");
            return;
        }

        createNotification("success", "Ticket purchased successfully!", "");
        resetAll();
        if (typeof window.resetAdminContestentSelection === 'function') {
            window.resetAdminContestentSelection();
        }
        e.target.reset();
        resetStateOptions();
        
        // Remove validation classes to prevent red glow
        e.target.classList.remove('was-validated');
        const inputs = e.target.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
          setTimeout(() => {
                    window.location.href = window.location.href;
                }, 500);
    })
    .catch(console.error)
    .finally(() => {
        // Hide loader
        if (actionLoader) {
            actionLoader.style.display = 'none';
        }
        btn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
    });
}

/* ---------------- UI HELPERS ---------------- */

function showPromoteMessage(data) {
    const el = document.getElementById('promoteMsg');
    document.getElementById('promoteMsgText').innerHTML = `${data.message}`;
        // ? '<b>Perfect Choice :)</b>'
        // : `<b>${data.message} tickets</b> away from <b>${data.discount_percentage}% discount</b>`;
    el.style.display = 'block';
}

function hidePromoteMessage() {
    document.getElementById('promoteMsg').style.display = 'none';
}

function resetCoupon() {
    appliedCoupon = null;
    const couponInput = document.getElementById('coupon');
    
    couponInput.value = '';
    couponInput.style.color = ''; // Reset text color
    couponInput.style.borderColor = ''; // Reset border color
    couponInput.style.backgroundColor = ''; // Reset background color
    couponInput.classList.remove('is-valid', 'is-invalid'); // Remove validation classes
    
    document.getElementById('couponSuccess').style.display = 'none';
    document.getElementById('couponErrorMsg').style.display = 'none';
    document.getElementById('applyCouponBtn').style.display = 'inline-block';
    document.getElementById('removeCouponBtn').style.display = 'none';
}

function resetBill() {
    ['ticketPriceRow','servicesRow','subtotalRow','bulkDiscountRow','couponAppliedRow','extraChargesRow','taxRow','totalAmountRow']
        .forEach(id => document.getElementById(id).style.display = 'none');
}

function resetAll() {
    resetBill();
    resetCoupon();
    hidePromoteMessage();
    resetQuantityOptions();
    currentTicketType = null;
    syncServiceFields();
    disableCouponSection();
}

/* ---------------- UTILS ---------------- */

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
}

window.addEventListener('beforeunload', () => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

@endsection
