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
    <script>
        window.stadiumData = {
            lwdata: @json($lwdata),
            clwdata: @json($clwdata),
            crwdata: @json($crwdata),
            rwdata: @json($rwdata),
            seatAssignments: @json($seatAssignments), // Contains colors and ticket_type_ids
            heldSeatIds: @json($heldSeatIds), // Disable the held tickets
            targetTicketTypeId: @json($targetTicketTypeId) // The specific type the user chose
        };

    </script>
    
    <script src="{{ asset('javascript/pages/stadium/seat-selection.js') }}" defer></script>

    <script src="{{ asset('javascript/parking-form.js') }}" defer></script>
@endsection

@section('body')
    @php($currency = \App\Models\Currency::symbolForEvent($event ?? null))
    @php($hasSelectedTicketType = filled($targetTicketTypeId))
    @php($irelandCountry = $countries->first(fn ($country) => strcasecmp($country->name, 'Ireland') === 0))
    @php($defaultCountryId = old('country_id', $irelandCountry?->id))
    @php($defaultStateId = old('state_id'))
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

            <h5 class="hd-lg">Ticket Counter Seat</h5>

            <div>
                <button class="btn-sm btn-sec" data-bs-toggle="modal" data-bs-target="#modalID">
                    Venue Layout
                </button>
            </div>
            
         <form class="needs-validation" id="ticketForm" novalidate="">
            @csrf
            <input type="hidden" id="couponValid" name="coupon_valid" value="false">
            <input type="hidden" id="couponCode" name="coupon_code" value="">
            <input type="hidden" id="couponAmount" name="coupon_amount" value="0">
            <input type="hidden" id="couponPercentage" name="coupon_percentage" value="0">
            
            <!-- stadium Layout -->
             @if($hasSelectedTicketType) <x-admin.create-stadium /> @endif
           
       
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

                <!-- Coupon error message -->
                <small
                    id="couponErrorMsg"
                    class="text-danger"
                    style="display:none;margin-top:6px;"
                ></small>

                <!-- Bulk discount message -->
                <small
                    id="bulkCouponMsg"
                    class="text-danger"
                    style="display:none;margin-top:6px;"
                >
                    Bulk discount active, cannot apply coupon code
                </small>

                <!-- Coupon success message -->
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

                    @include('admin.event-ticket._partials.candidate-voting')
                    
                    <!-- Form -->
                        <div class="grid-1 gap-card">
                            <!-- Ticket Type -->
                            <div class="form-floating">
                                <select class="form-select" id="ticketType" name="ticket_type_id" required="">
                                    <option value="">Select Ticket Type</option>
                                    @if(isset($ticketTypes) && $ticketTypes->count() > 0)
                                        @foreach ($ticketTypes as $ticketType)
                                            <option value="{{ $ticketType->id }}"  data-price="{{ $ticketType->ticket_price }}"
                                                data-title="{{ $ticketType->title }}">
                                                {{ $ticketType->title }} - {{ $currency }}{{ number_format($ticketType->ticket_price, 2) }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option disabled>No ticket types available for this event</option>
                                    @endif
                                </select>
                                <label for="selectit">Ticket Type</label>
                            </div>

                            <!-- Name -->
                            <div class="form-floating">
                                <input type="text" class="form-control" id="dfg5" name="name" required>
                                <label for="dfg5">Name</label>
                            </div>

                            <!-- Email -->
                            <div class="form-floating">
                                <input type="email" class="form-control" id="dg58" name="email" required>
                                <label for="dg58">Email</label>
                            </div>

                            <!-- Mobile -->
                            <div class="d-flex g-2">
                                <div class="form-floating flex-shrink-0 me-2">
                                    <select class="form-select" id="seatPhonePrefix" name="phone_prefix">
                                        @include('admin._partials.options.prefix-options', ['selected' => old('phone_prefix','+353')])
                                    </select>
                                    <label for="seatPhonePrefix">Prefix</label>
                                </div>
                                <div class="form-floating flex-grow-1">
                                    <input type="text" class="form-control" id="dfg4" name="mobile_number" requiredinputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" autocomplete="tel" />
                                    <label for="dfg4">Mobile Number</label>
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

                            
                            {{-- Dynamic Car Slots Section --}}
                            @if($event->enable_car_parking)
                             <div class="car-slots-section">
                                <div class="car-header">
                                    <div>
                                        <h1 class="hd-md mb-1">Book Car Parking</h1>
                                        <p class="">{{ $currency }}{{ number_format($event->car_slot_price, 2) }}/- (per slot) [{{ $remainingSlots ?? 0 }} Slots Available]</p>

                                        <div class="car-tags-row text-white">
                                            <span>Selected Slots: <b class="selected-slot">0</b></span>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn-xs btn-prim" id="car-slot-btn-js">
                                            <i class="fa-solid fa-plus me-1"></i> Add Car Slot
                                        </button>
                                    </div>
                                </div>
                                <div class="car-slots-container">
                                    {{-- Slots will be prepended here via JS --}}
                                </div>
                            </div>
                            @endif

                            <button type="submit" class="btn-md btn-sec" id="buyTicketBtn">
                                <span class="btn-text">Buy Ticket <i class="fa-solid fa-ticket"></i></span>
                                <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                            </button>
                        </div>
                   
                </div>
            </form>
                <div>
                    <h4 class="hd-md text-center text-uppercase">Customer's Bill</h4>
                    <table class="table view-table bill-table">
                        <tbody>
                            <tr>
                                <th colspan="2">
                                    <h6 class="text-center"><i class="fa-solid fa-ticket"></i>
                                    Ticket: <span id="billTicketName" style="color: var(--color-primary);">Select Type</span> </h6>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <div class="tags-row">
                                        </div>
                                </th>
                            </tr>
                            <tr id="ticketPriceRow" style="display: none;">
                                <th>
                                    <h6>Ticket Price</h6>
                                    <p id="ticketPriceDetails"></p>
                                </th>
                                <td id="ticketPriceAmount"></td>
                            </tr>
                            <tr id="parkingRow" style="display: none;">
                                <td id="parkingDetails">
                                    </td>
                                <td id="parkingAmount"></td>
                            </tr>
                            <tr id="bulkDiscountRow" style="display: none;">
                                <th>
                                    <h6>Bulk Ticket Discount</h6>
                                    <p id="bulkDiscountDetails"></p>
                                </th>
                                <td class="text-danger" id="bulkDiscountAmount"></td>
                            </tr>
                            <tr id="couponAppliedRow" style="display: none;">
                                <th>
                                    <h6>Coupon Applied</h6>
                                    <p id="couponAppliedDetails"></p>
                                </th>
                                <td class="text-danger" id="couponAppliedAmount"></td>
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
                                    <h6 style="color: var(--color-primary);">Total Amount</h6>
                                </th>
                                <td id="totalAmount" style="color: var(--color-primary);">{{ $currency }}0/-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <!-- Venue Modal -->
     <x-admin.venue-modal />

</body>

<script>
    document.getElementById('ticketType').addEventListener('change', function() {
        const typeId = this.value;
        const url = new URL(window.location.href);

        if (typeId) {
            // Set the ticket_type_id in the URL and reload
            url.searchParams.set('ticket_type_id', typeId);
        } else {
            // Remove the parameter if "Select Ticket Type" is chosen
            url.searchParams.delete('ticket_type_id');
        }

        window.location.href = url.toString();
    });
</script>
<script>
// 1. Core Configuration (Global Scope)
window.API_BASE = "{{ url('/api/tickets') }}";
window.EVENT_ID = "{{ session('active_event_id') }}";
window.STATES_ENDPOINT_TEMPLATE = "{{ route('admin.ticket.counter.api.states', ['countryId' => '__COUNTRY__']) }}";
window.currentTicketType = @json($targetTicketTypeId) ?? null;
window.currentQuantity = 0;
window.appliedCoupon = null;
window.pollInterval = null;
window.lastAvailableTickets = null;

// Helper for JSON headers
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
});

document.addEventListener('DOMContentLoaded', () => {
    if (!window.EVENT_ID) return;

    initializeLocationSelectors();
    // Initialize Seat Selection & Coupon Listeners
    initializeEventListeners();
    
    // Sync the Bill UI if a ticket type is already active
    if (window.currentTicketType) {
        syncUIWithTicketType();
    }

    startPolling();
});

/* ---------------- INIT ---------------- */

function initializeEventListeners() {
    // We only need the coupon and form listeners here
    document.getElementById('applyCouponBtn')?.addEventListener('click', applyCoupon);
    document.getElementById('removeCouponBtn')?.addEventListener('click', removeCoupon);
    document.getElementById('ticketForm')?.addEventListener('submit', handleFormSubmit);
    initializeMobileNumberField();

    // Listen for seat changes in the stadium layout
    document.addEventListener('change', (e) => {
        if (e.target.closest('.stadium') && e.target.type === 'checkbox') {
            handleSeatSelectionChange(e);
        }
    });
}

function initializeMobileNumberField() {
    const mobileInput = document.getElementById('dfg4');
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

    fetch(window.STATES_ENDPOINT_TEMPLATE.replace('__COUNTRY__', countryId), {
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

function syncUIWithTicketType() {
    const select = document.getElementById('ticketType');
    if (select) {
        select.value = window.currentTicketType;
        const option = select.options[select.selectedIndex];
        if (option) {
            document.getElementById('billTicketName').innerText = option.dataset.title;
        }
    }
}

/* ---------------- SEAT LOGIC ---------------- */
function handleSeatSelectionChange(e) {
    const MAX_TICKETS = 20;
    const selectedCheckboxes = document.querySelectorAll('.stadium input[type="checkbox"]:checked:not(:disabled)');

    // Hard limit: max 20 seats
    if (selectedCheckboxes.length > MAX_TICKETS && e?.target) {
        e.target.checked = false;
        if (typeof createNotification === "function") {
            createNotification("error", `Maximum ${MAX_TICKETS} tickets allowed in one booking.`, "");
        }
        setTimeout(() => handleSeatSelectionChange(), 0);
        return;
    }

    window.currentQuantity = selectedCheckboxes.length;
    
    // Create an array of objects containing both ID and Label
    const selectedSeats = Array.from(selectedCheckboxes).map(cb => ({
        id: cb.value,
        label: cb.getAttribute('data-seat-label') || cb.value // Fallback to ID if label missing
    }));
    
    // Update the Seat Tags UI in the bill
    const tagsRow = document.querySelector('.tags-row');
    if (tagsRow) {
        tagsRow.innerHTML = selectedSeats.map(s => `
            <span class="seat-tag">
                ${s.label}
                <button type="button" onclick="unselectSeat('${s.id}')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `).join('');
    }

    if (!window.currentTicketType || window.currentQuantity === 0) {
        resetBill();
        disableCouponSection();
        return;
    }

    enableCouponSection();
    calculateBill();
    checkBulkDiscount();
}

function unselectSeat(seatValue) {
    const cb = document.querySelector(`.stadium input[value="${seatValue}"]`);
    if (cb) {
        cb.checked = false;
        // Manually trigger change to update the bill
        cb.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

/* ---------------- BILLING & BULK DISCOUNT ---------------- */

function calculateBill() {
    
    // Collect Car Registration Numbers
    const carNumbers = [];
    document.querySelectorAll(".car-slots-container input").forEach(input => {
        if (input.value.trim() !== "") carNumbers.push(input.value.trim());
    });
    //Get parking slots
    const activeSlotsCount = document.querySelectorAll('.car-slots-container .car-slot-item').length;


    fetch(`${window.API_BASE}/calculate-bill`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: window.EVENT_ID,
            ticket_type_id: window.currentTicketType,
            quantity: window.currentQuantity,
            coupon_code: window.appliedCoupon?.coupon_code || null,
            parking_slots: activeSlotsCount || 0,
            car_details: carNumbers
        })
    })
    .then(r => r.json())
    .then(updateBillDisplay)
    .catch(console.error);
}

function checkBulkDiscount() {
    fetch(`${window.API_BASE}/check-bulk-discount`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: window.EVENT_ID,
            ticket_type_id: window.currentTicketType,
            quantity: window.currentQuantity
        })
    })
    .then(r => r.json())
    .then(d => {
        const applyBtn = document.getElementById('applyCouponBtn');
        if (!applyBtn) return;

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
            applyBtn.disabled = true;
            applyBtn.classList.add('disabled');
            msg.textContent = 'Bulk discount active, cannot apply coupon code';
            msg.style.display = 'block';
            resetCoupon(); // Auto-remove coupon if bulk is active
        } else {
            applyBtn.disabled = false;
            applyBtn.classList.remove('disabled');
            msg.style.display = 'none';
        }

        d.has_bulk_discount ? showPromoteMessage(d) : hidePromoteMessage();
    })
    .catch(console.error);
}

/* ---------------- COUPON MANAGEMENT ---------------- */

function applyCoupon() {
    const code = document.getElementById('coupon').value.trim();
    const errorMsg = document.getElementById('couponErrorMsg');
    errorMsg.style.display = 'none';

    if (!code) return;

    fetch(`${window.API_BASE}/apply-coupon`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
            event_id: window.EVENT_ID,
            ticket_type_id: window.currentTicketType,
            coupon_code: code,
            quantity: window.currentQuantity
        })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            errorMsg.textContent = d.message;
            errorMsg.style.display = 'block';
            return;
        }
        window.appliedCoupon = d;
        updateCouponUI(true);
        calculateBill();
    });
}

function updateCouponUI(isApplied) {
    const input = document.getElementById('coupon');
    document.getElementById('couponSuccess').style.display = isApplied ? 'block' : 'none';
    document.getElementById('applyCouponBtn').style.display = isApplied ? 'none' : 'inline-block';
    document.getElementById('removeCouponBtn').style.display = isApplied ? 'inline-block' : 'none';
    input.disabled = isApplied;
    
    if (isApplied) {
        input.classList.add('is-valid');
        input.style.backgroundColor = '#d1e7dd';
    } else {
        input.value = '';
        input.classList.remove('is-valid');
        input.style.backgroundColor = '';
    }
}

function removeCoupon() {
    window.appliedCoupon = null;
    updateCouponUI(false);
    calculateBill();
}

/* ---------------- POLLING & UI HELPERS ---------------- */

function startPolling() {
    window.pollInterval = setInterval(() => {
        if (!window.currentTicketType) return;
        fetch(`${window.API_BASE}/available/${window.currentTicketType}?event_id=${window.EVENT_ID}`)
            .then(r => r.json())
            .then(data => {
                if (data.available_tickets !== window.lastAvailableTickets) {
                    window.lastAvailableTickets = data.available_tickets;
                }
            });
    }, 5000);
}

function updateBillDisplay(data) {
    //console.log("Billing Data:", data);

    // 1. Ticket Base Price
    document.getElementById('ticketPriceRow').style.display = 'table-row';
    document.getElementById('ticketPriceDetails').innerHTML = 
        `${data.ticket_price}/- <i class="fa-solid fa-xmark mx-2"></i> ${data.quantity} pcs`;
    document.getElementById('ticketPriceAmount').textContent = `${data.subtotal}/-`;

    // 2. Parking tickets
    const parkingRow = document.getElementById('parkingRow');
    if (parkingRow) {
        if (data.parking_slots > 0) {
            parkingRow.style.display = 'table-row';
            document.getElementById('parkingDetails').innerHTML = 
                `<span class="text-primary">Car Slot</span><br>
                 ${data.parking_price}/- x ${data.parking_slots} Slots`;
            document.getElementById('parkingAmount').textContent = `${data.parking_total}/-`;
        } else {
            parkingRow.style.display = 'none';
        }
    }

    // 3. Bulk Discount Row
    const bulkRow = document.getElementById('bulkDiscountRow');
    if (data.bulk_discount_applied) {
        bulkRow.style.display = 'table-row';
        document.getElementById('bulkDiscountDetails').textContent = `${data.bulk_discount_percentage}% off`;
        document.getElementById('bulkDiscountAmount').innerHTML = `<span class="text-danger">-${data.bulk_discount_amount}/-</span>`;
    } else {
        bulkRow.style.display = 'none';
    }

    // 4. Coupon Row (Hidden if Bulk is applied)
    const couponRow = document.getElementById('couponAppliedRow');
    if (data.coupon_applied && !data.bulk_discount_applied) {
        couponRow.style.display = 'table-row';
        document.getElementById('couponAppliedDetails').textContent = `[${data.coupon_code}] ${data.coupon_percentage}% off`;
        document.getElementById('couponAppliedAmount').innerHTML = `<span class="text-danger">-${data.coupon_amount}/-</span>`;
    } else {
        couponRow.style.display = 'none';
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

function showPromoteMessage(data) {
    const el = document.getElementById('promoteMsg');
    if (el) {
        document.getElementById('promoteMsgText').innerHTML = data.message;
        el.style.display = 'block';
    }
}

function hidePromoteMessage() {
    const el = document.getElementById('promoteMsg');
    if (el) el.style.display = 'none';
}

function resetBill() {
    ['ticketPriceRow','bulkDiscountRow','couponAppliedRow','extraChargesRow','taxRow','totalAmountRow']
        .forEach(id => document.getElementById(id).style.display = 'none');
    const tagsRow = document.querySelector('.tags-row');
    if (tagsRow) tagsRow.innerHTML = '';
}

function resetCoupon() {
    window.appliedCoupon = null;
    updateCouponUI(false);
}

function enableCouponSection() {
    document.getElementById('coupon').disabled = false;
    document.getElementById('applyCouponBtn').disabled = false;
}

function disableCouponSection() {
    document.getElementById('coupon').disabled = true;
    document.getElementById('applyCouponBtn').disabled = true;
    resetCoupon();
}


/* ---------------- Parking Seat ---------------- */
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


/* ---------------- Handle form submit ---------------- */


function handleFormSubmit(e) {
    e.preventDefault();

    const mobileInput = document.getElementById('dfg4');
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
    
    const btn = document.getElementById('buyTicketBtn');
    const btnText = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner-border');
    const actionLoader = document.getElementById('actionLoader');

    // Hard limit: max 20 seats
    const selectedCheckboxes = document.querySelectorAll('.stadium input[type="checkbox"]:checked:not(:disabled)');
    if (selectedCheckboxes.length > 20) {
        if (typeof createNotification === "function") {
            createNotification("error", "Maximum 20 tickets allowed in one booking.", "");
        }
        return;
    }
    
    // Show loader
    if (actionLoader) actionLoader.style.display = 'flex';
    btn.disabled = true;
    btn.style.opacity = '0.7';
    btn.style.cursor = 'not-allowed';
    btnText.innerHTML = 'Processing...';
    spinner.style.display = 'inline-block';
    
    const formData = new FormData(e.target);
    formData.set('coupon_code', window.appliedCoupon?.coupon_code || '');
    formData.set('coupon_valid', window.appliedCoupon ? 'true' : 'false');

    // Selected seats are already part of FormData via checkbox name="selected_seats[]".
    // Only sync quantity to the selected seat count.
    if (selectedCheckboxes.length > 0) {
        // Force the quantity to match the number of seats
        formData.set('quantity', selectedCheckboxes.length);
    }

    formData.append('event_id', window.EVENT_ID);
    formData.append('ticket_type_id', window.currentTicketType);

    //Parking slots
    const activeSlotsCount = document.querySelectorAll('.car-slots-container .car-slot-item').length;
    formData.append('parking_slots', activeSlotsCount);

    fetch(`${window.API_BASE}/purchase`, { 
        method: 'POST', 
        body: formData,
        headers: {
            // CRITICAL: Tells Laravel to return JSON errors instead of HTML pages
            'Accept': 'application/json',
            // CRITICAL: Prevents 419 Page Expired error
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : { success: false, message: 'The server returned an invalid response.' };

        if (!response.ok) {
            throw new Error(data.message || 'Unable to complete the booking.');
        }

        return data;
    })
    .then(d => {
        if (!d.success) {
            createNotification("error", d.message, "");
            return;
        }
        createNotification("success", "Booking successful!", "");
        if (typeof window.resetAdminContestentSelection === 'function') {
            window.resetAdminContestentSelection();
        }
        // Short delay so they see the success message before reload
        setTimeout(() => location.reload(), 1500); 
    })
    .catch(err => {
        console.error("Submission Error:", err);
        createNotification("error", err.message || "An unexpected error occurred. Please try again.", "");
    })
    .finally(() => {
        // Hide loader
        if (actionLoader) actionLoader.style.display = 'none';
        btn.disabled = false;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'pointer';
        btnText.innerHTML = 'Buy Ticket <i class="fa-solid fa-ticket"></i>';
        spinner.style.display = 'none';
    });
}

window.addEventListener('beforeunload', () => { if (window.pollInterval) clearInterval(window.pollInterval); });
</script>

@endsection
