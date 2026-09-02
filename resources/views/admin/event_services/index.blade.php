@extends('layouts.admin')

@section('head')
    <title>Event Services</title>
    @include('admin._partials.head.g-links')
    @include('admin._partials.head.g-css-files')
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @include('admin._partials.preloader')
    @include('admin._partials.sidebar')
    @include('admin._partials.header')

    <section class="wrapper">
        <main class="dash-content">
            @include('admin._partials.breadcrumb')

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-card mb-3">
                <div>
                    <h5 class="hd-lg mb-1">Event Services</h5>
                    <p class="mb-0">Create add-on services for {{ $event->title }}.</p>
                </div>
                <button type="button" class="btn-sm btn-sec" data-bs-toggle="modal" data-bs-target="#serviceModal">
                    <i class="fa-solid fa-plus i-mr"></i> Create Service
                </button>
            </div>

            <div class="table-responsive">
                <table class="table mob-view">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Limit</th>
                            <th>Mandatory</th>
                            <th>Ticket Types</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $index => $service)
                            <tr>
                                <td>{{ $services->firstItem() + $index }}</td>
                                <td>{{ $service->name }}</td>
                                <td>{{ \App\Models\Currency::symbolForEvent($event) }}{{ number_format((float) $service->price, 2) }}</td>
                                <td>{{ $service->available_quantity }}</td>
                                <td>{{ $service->max_buy_limit }}</td>
                                <td>{{ $service->is_mandatory ? 'Yes' : 'No' }}</td>
                                <td>
                                    @php
                                        $ids = array_map('intval', $service->applicable_ticket_type_ids ?? []);
                                    @endphp
                                    @if (empty($ids))
                                        All ticket types
                                    @else
                                        {{ $ticketTypes->whereIn('id', $ids)->pluck('title')->join(', ') }}
                                    @endif
                                </td>
                                <td>
                                    <div class="action-row">
                                        <button type="button" class="action-btn edit" data-bs-toggle="modal"
                                            data-bs-target="#serviceModal"
                                            data-action="{{ route('admin.event.services.update', $service) }}"
                                            data-name="{{ $service->name }}"
                                            data-quantity="{{ $service->available_quantity }}"
                                            data-limit="{{ $service->max_buy_limit }}"
                                            data-price="{{ $service->price }}"
                                            data-mandatory="{{ $service->is_mandatory ? 1 : 0 }}"
                                            data-status="{{ $service->status ? 1 : 0 }}"
                                            data-ticket-types='@json($service->applicable_ticket_type_ids ?? [])'>
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('admin.event.services.destroy', $service) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No event services found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($services->hasPages())
                {{ $services->links() }}
            @endif
        </main>
    </section>

    <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0" id="serviceModalTitle">Create Event Service</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    @php
                        $oldTicketTypeIds = array_map('intval', old('applicable_ticket_type_ids', []));
                        $oldStatusChecked = old('_token') ? old('status') : '1';
                    @endphp
                    <form id="serviceForm" action="{{ route('admin.event.services.store') }}" method="POST" class="grid-1 gap-card needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="_method" value="POST">

                        <div class="d-flex flex-wrap gap-card">
                            <button type="button" class="check-btn">
                                <input class="form-check-input" name="is_mandatory" type="checkbox" value="1"
                                    id="serviceMandatory" {{ old('is_mandatory') ? 'checked' : '' }}>
                                <label for="serviceMandatory">Mandatory Purchase</label>
                            </button>
                            <button type="button" class="check-btn">
                                <input class="form-check-input" name="status" type="checkbox" value="1"
                                    id="serviceStatus" {{ $oldStatusChecked ? 'checked' : '' }}>
                                <label for="serviceStatus">Active</label>
                            </button>
                        </div>

                        <div class="form-floating">
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                id="serviceName" value="{{ old('name') }}" maxlength="255" required>
                            <label for="serviceName">Service Name*</label>
                            <div class="invalid-feedback" data-feedback-for="serviceName">
                                @error('name')
                                    {{ $message }}
                                @else
                                    Service name is required.
                                @enderror
                            </div>
                        </div>

                        <div class="grid-2 grid-sm-1 gap-card">
                            <div class="form-floating">
                                <input type="number" name="available_quantity"
                                    class="form-control @error('available_quantity') is-invalid @enderror"
                                    id="serviceQty" value="{{ old('available_quantity') }}" min="1" max="999999"
                                    step="1" inputmode="numeric" required>
                                <label for="serviceQty">Available Quantity*</label>
                                <div class="invalid-feedback" data-feedback-for="serviceQty">
                                    @error('available_quantity')
                                        {{ $message }}
                                    @else
                                        Available quantity must be at least 1.
                                    @enderror
                                </div>
                            </div>
                            <div class="form-floating">
                                <input type="number" name="max_buy_limit"
                                    class="form-control @error('max_buy_limit') is-invalid @enderror"
                                    id="serviceLimit" value="{{ old('max_buy_limit') }}" min="1" max="999999"
                                    step="1" inputmode="numeric" required>
                                <label for="serviceLimit">Max Buy Limit*</label>
                                <div class="invalid-feedback" data-feedback-for="serviceLimit">
                                    @error('max_buy_limit')
                                        {{ $message }}
                                    @else
                                        Max buy limit cannot be more than available quantity.
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                                id="servicePrice" value="{{ old('price') }}" min="0" max="99999999.99" step="0.01"
                                inputmode="decimal" required>
                            <label for="servicePrice">Price*</label>
                            <div class="invalid-feedback" data-feedback-for="servicePrice">
                                @error('price')
                                    {{ $message }}
                                @else
                                    Price is required and can have up to 2 decimal places.
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-2">Applicable Ticket Types</label>
                            <div class="d-flex flex-wrap" style="gap: 0.5rem">
                                @foreach ($ticketTypes as $ticketType)
                                    <button type="button" class="check-btn">
                                        <input class="form-check-input service-ticket-type" type="checkbox"
                                            name="applicable_ticket_type_ids[]" value="{{ $ticketType->id }}"
                                            id="service_ticket_{{ $ticketType->id }}"
                                            {{ in_array((int) $ticketType->id, $oldTicketTypeIds, true) ? 'checked' : '' }}>
                                        <label for="service_ticket_{{ $ticketType->id }}">{{ $ticketType->title }}</label>
                                    </button>
                                @endforeach
                            </div>
                            @error('applicable_ticket_type_ids')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('applicable_ticket_type_ids.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-100" style="font-size: 0.6rem">Leave all unchecked to make this service available for every ticket type.</small>
                        </div>

                        

                        <button type="submit" class="btn-md btn-sec">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const serviceModal = document.getElementById('serviceModal');
        const serviceForm = document.getElementById('serviceForm');
        const methodInput = serviceForm.querySelector('input[name="_method"]');
        const serviceNameInput = document.getElementById('serviceName');
        const serviceQtyInput = document.getElementById('serviceQty');
        const serviceLimitInput = document.getElementById('serviceLimit');
        const servicePriceInput = document.getElementById('servicePrice');
        const hasServiceValidationErrors = @json($errors->any());
        const serviceStoreAction = @json(route('admin.event.services.store'));

        document.querySelectorAll('[data-feedback-for]').forEach((feedback) => {
            feedback.dataset.defaultMessage = feedback.textContent.trim();
        });

        function serviceFeedback(input) {
            return document.querySelector(`[data-feedback-for="${input.id}"]`);
        }

        function setServiceFeedback(input, message) {
            input.setCustomValidity(message || '');

            const feedback = serviceFeedback(input);
            if (feedback) {
                feedback.textContent = message || feedback.dataset.defaultMessage || '';
            }
        }

        function clearServiceFeedback() {
            setServiceFeedback(serviceNameInput, '');
            setServiceFeedback(serviceQtyInput, '');
            setServiceFeedback(serviceLimitInput, '');
            setServiceFeedback(servicePriceInput, '');
        }

        function resetServiceFormForCreate() {
            serviceNameInput.value = '';
            serviceQtyInput.value = '';
            serviceLimitInput.value = '';
            servicePriceInput.value = '';
            document.getElementById('serviceMandatory').checked = false;
            document.getElementById('serviceStatus').checked = true;
            document.querySelectorAll('.service-ticket-type').forEach((checkbox) => {
                checkbox.checked = false;
            });
        }

        function validateIntegerField(input, label) {
            if (!input.value) {
                setServiceFeedback(input, '');
                return;
            }

            if (!/^\d+$/.test(input.value)) {
                setServiceFeedback(input, `${label} must be a whole number.`);
                return;
            }

            if (Number(input.value) < 1) {
                setServiceFeedback(input, `${label} must be at least 1.`);
                return;
            }

            if (Number(input.value) > 999999) {
                setServiceFeedback(input, `${label} cannot be more than 999999.`);
                return;
            }

            setServiceFeedback(input, '');
        }

        function validateServiceForm() {
            validateIntegerField(serviceQtyInput, 'Available quantity');
            validateIntegerField(serviceLimitInput, 'Max buy limit');

            if (serviceQtyInput.value && /^\d+$/.test(serviceQtyInput.value) && Number(serviceQtyInput.value) >= 1) {
                serviceLimitInput.max = serviceQtyInput.value;
            } else {
                serviceLimitInput.removeAttribute('max');
            }

            const quantityIsValid = /^\d+$/.test(serviceQtyInput.value) && Number(serviceQtyInput.value) >= 1;
            const limitIsValidInteger = /^\d+$/.test(serviceLimitInput.value) && Number(serviceLimitInput.value) >= 1;
            if (quantityIsValid && limitIsValidInteger && Number(serviceLimitInput.value) > Number(serviceQtyInput.value)) {
                setServiceFeedback(serviceLimitInput, 'Max buy limit cannot be more than available quantity.');
            }

            if (!servicePriceInput.value) {
                setServiceFeedback(servicePriceInput, '');
            } else if (!/^\d+(\.\d{1,2})?$/.test(servicePriceInput.value)) {
                setServiceFeedback(servicePriceInput, 'Price can have a maximum of 2 decimal places.');
            } else if (Number(servicePriceInput.value) > 99999999.99) {
                setServiceFeedback(servicePriceInput, 'Price is too high.');
            } else {
                setServiceFeedback(servicePriceInput, '');
            }
        }

        serviceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            serviceForm.classList.remove('was-validated');
            serviceForm.querySelectorAll('.is-invalid').forEach((field) => {
                field.classList.remove('is-invalid');
            });
            clearServiceFeedback();
            serviceForm.action = serviceStoreAction;
            methodInput.value = 'POST';
            document.getElementById('serviceModalTitle').textContent = 'Create Event Service';

            if (button) {
                serviceForm.reset();
                resetServiceFormForCreate();
            }

            validateServiceForm();

            if (!button?.dataset.action) return;

            serviceForm.action = button.dataset.action;
            methodInput.value = 'PUT';
            document.getElementById('serviceModalTitle').textContent = 'Update Event Service';
            document.getElementById('serviceName').value = button.dataset.name || '';
            document.getElementById('serviceQty').value = button.dataset.quantity || 0;
            document.getElementById('serviceLimit').value = button.dataset.limit || 1;
            document.getElementById('servicePrice').value = button.dataset.price || 0;
            document.getElementById('serviceMandatory').checked = button.dataset.mandatory === '1';
            document.getElementById('serviceStatus').checked = button.dataset.status === '1';

            const ids = JSON.parse(button.dataset.ticketTypes || '[]').map(String);
            document.querySelectorAll('.service-ticket-type').forEach((checkbox) => {
                checkbox.checked = ids.includes(String(checkbox.value));
            });

            validateServiceForm();
        });

        serviceModal.addEventListener('hidden.bs.modal', function () {
            serviceForm.classList.remove('was-validated');
            clearServiceFeedback();
        });

        [serviceQtyInput, serviceLimitInput, servicePriceInput].forEach((input) => {
            input.addEventListener('input', validateServiceForm);
        });

        serviceForm.addEventListener('submit', function (event) {
            validateServiceForm();

            if (!serviceForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                serviceForm.classList.add('was-validated');

                const firstInvalid = serviceForm.querySelector(':invalid');
                firstInvalid?.focus();

                if (typeof createNotification === 'function') {
                    createNotification('error', firstInvalid?.validationMessage || 'Please fix the highlighted fields.', '');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            if (!hasServiceValidationErrors) return;

            validateServiceForm();
            serviceForm.classList.add('was-validated');
            bootstrap.Modal.getOrCreateInstance(serviceModal).show();

            if (typeof createNotification === 'function') {
                createNotification('error', 'Please fix the highlighted fields.', '');
            }
        });
    </script>
@endsection
