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
                                    @php($ids = array_map('intval', $service->applicable_ticket_type_ids ?? []))
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
                    <form id="serviceForm" action="{{ route('admin.event.services.store') }}" method="POST" class="grid-1 gap-card needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="_method" value="POST">

                        <div class="form-floating">
                            <input type="text" name="name" class="form-control" id="serviceName" required>
                            <label for="serviceName">Service Name*</label>
                        </div>

                        <div class="grid-2 grid-sm-1 gap-card">
                            <div class="form-floating">
                                <input type="number" name="available_quantity" class="form-control" id="serviceQty" min="0" required>
                                <label for="serviceQty">Available Quantity*</label>
                            </div>
                            <div class="form-floating">
                                <input type="number" name="max_buy_limit" class="form-control" id="serviceLimit" min="1" required>
                                <label for="serviceLimit">Max Buy Limit*</label>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="number" name="price" class="form-control" id="servicePrice" min="0" step="0.01" required>
                            <label for="servicePrice">Price*</label>
                        </div>

                        <div>
                            <label class="mb-2">Applicable Ticket Types</label>
                            <div class="grid-2 grid-sm-1 gap-card">
                                @foreach ($ticketTypes as $ticketType)
                                    <button type="button" class="check-btn">
                                        <input class="form-check-input service-ticket-type" type="checkbox"
                                            name="applicable_ticket_type_ids[]" value="{{ $ticketType->id }}"
                                            id="service_ticket_{{ $ticketType->id }}">
                                        <label for="service_ticket_{{ $ticketType->id }}">{{ $ticketType->title }}</label>
                                    </button>
                                @endforeach
                            </div>
                            <small>Leave all unchecked to make this service available for every ticket type.</small>
                        </div>

                        <div class="d-flex flex-wrap gap-card">
                            <button type="button" class="check-btn">
                                <input class="form-check-input" name="is_mandatory" type="checkbox" value="1" id="serviceMandatory">
                                <label for="serviceMandatory">Mandatory Purchase</label>
                            </button>
                            <button type="button" class="check-btn">
                                <input class="form-check-input" name="status" type="checkbox" value="1" id="serviceStatus" checked>
                                <label for="serviceStatus">Active</label>
                            </button>
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

        serviceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            serviceForm.reset();
            serviceForm.action = @json(route('admin.event.services.store'));
            methodInput.value = 'POST';
            document.getElementById('serviceModalTitle').textContent = 'Create Event Service';
            document.getElementById('serviceStatus').checked = true;

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
        });
    </script>
@endsection
