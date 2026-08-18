@extends('layouts.admin')

@section('head')
    <title>View Ticket Type</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')
    <style>
        .ticket-type-color-preview {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .ticket-type-color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.15);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }
    </style>

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
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

            <div class="HDandP">
                <h4 class="hd-lg"><span>{{$ticket->title??''}}</span> Ticket Type</h4>
                <p><i class="fa-solid fa-arrow-right-long i-mr"></i> View Ticket Type Detail</p>
            </div>

            <div class="table-responsive">
                <table class="table view-table">
                    <tbody>
                        {{-- <tr>
                            <th>ID</th>
                            <td>#{{ $ticket->id }}</td>
                        </tr> --}}
                        <tr>
                            <th>Created At</th>
                            <td>{{ $ticket->created_at->format('d-m-Y | h:i:s A') }}</td>
                        </tr>
                        <tr>
                            <th>Title</th>
                            <td>{{ $ticket->title }}</td>
                        </tr>
                         @if($ticket->ticket_type_color)
                        <tr>
                            <th>Ticket Type Color</th>
                            <td>                               
                                <div class="ticket-type-color-preview">
                                    <span class="ticket-type-color-swatch" style="background-color: {{ $ticket->ticket_type_color }};"></span>
                                    <span>{{ strtoupper($ticket->ticket_type_color) }}</span>
                                </div>                                
                            </td>
                        </tr>
                         @endif
                        <tr>
                            <th>Featured Image</th>
                            <td><img src="{{ asset('storage/' . $ticket->featured_image) }}"
                                    alt="{{ $ticket->featured_image_alt_text }}" style="max-width: 7rem;"></td>
                        </tr>
                        <tr>
                            <th>Total Tickets (QTY)</th>
                            <td>{{ $ticket->total_tickets }} Tickets</td>
                        </tr>
                        <tr>
                            <th>Ticket Price (Per Ticket)</th>
                            <td>${{ $ticket->ticket_price }}/- per ticket</td>
                        </tr>
                        <tr>
                            <th>Tickets Sold (QTY)</th>
                            <td>{{ $ticket->tickets_sold?? 0 }} Tickets</td>
                        </tr>
                        <tr>
                            <th>Bulk Ticket Discount</th>
                            <td>{{ $ticket->enable_bulk_discount ? 'Enable' : 'Disable' }}</td>
                        </tr>
                        <tr>
                            <th>Coupons Active</th>
                            <td>
                                <div class="list-span">
                                    @forelse($discountCoupons as $coupon)
                                        <span>{{ $coupon->coupon_code }}</span>
                                    @empty
                                        <span>N/A</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                      
                        <tr>
                            <th>Created By (Admin)</th>
                            <td>
                                @if($ticket->creator)
                                    <div style="font-weight: 500;">{{ $ticket->creator->name }}</div>
                                    <small class="mt-1 text-break">{{ $ticket->creator->email }}</small>
                                @else
                                    <div>N/A</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Latest Updated By</th>
                            <td>
                            @if($ticket->updater)
                                    <div style="font-weight: 500;">{{ $ticket->updater->name }}</div>
                                    <small class="mt-1 text-break">{{ $ticket->updater->email }}</small><br/>
                                    <small class="mt-1 text-break">On {{ $ticket->updated_at->format('d-m-Y \A\t g:i A') }}</small>
                                @else
                                    <div>N/A</div>
                                @endif    
                        </tr>
                        <tr>
                            <th>Operations</th>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.ticket.types.edit', $ticket->id) }}" class="action-btn edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    @if(($ticket->tickets_sold ?? 0) == 0)
                                    <button class="action-btn delete"
                                        data-url="{{ route('admin.ticket.types.destroy', $ticket->id) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

             @if ($bulkDiscounts->count() > 0)
            <div class="style-box mt-4">
                <h3 class="hd-md">Bulk Ticket Discount Slabs</h3>
                <!-- Data Table -->
                <div class="table-responsive mt-4">
                    <table class="table mob-view">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Min Order Qty</th>
                                <th>Discount (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                                @foreach ($bulkDiscounts as $index => $bulkDiscount)
                                    <tr>
                                        <td>
                                            <div class="data-label">S.No</div>
                                            <div>{{ $index + 1 }}</div>
                                        </td>
                                        <td>
                                            <div class="data-label">Min Order Qty</div>
                                            <div>{{ $bulkDiscount->min_order_qty }}</div>
                                        </td>
                                        <td>
                                            <div class="data-label">Discount (%)</div>
                                            <div>{{ $bulkDiscount->discount_percentage }}% off</div>
                                        </td>
                                    </tr>
                                @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Previous Pg Btn -->
            <div class="d-flex justify-content-end my-5">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>

        </main>
    </section>

    <div class="modal fade" id="deleteTicketTypeModal" tabindex="-1" aria-labelledby="deleteTicketTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Ticket Type</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this ticket type?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteTicketTypeBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const deleteTicketTypeModalElement = document.getElementById('deleteTicketTypeModal');
        const confirmDeleteTicketTypeBtn = document.getElementById('confirmDeleteTicketTypeBtn');
        let deleteTicketTypeUrl = null;

        document.addEventListener("click", function(e) {
            const deleteBtn = e.target.closest(".action-btn.delete");
            if (!deleteBtn) return;

            deleteTicketTypeUrl = deleteBtn.getAttribute("data-url");
            if (!deleteTicketTypeUrl) return console.error("Delete URL not found!");

            const modal = new bootstrap.Modal(deleteTicketTypeModalElement);
            modal.show();
        });

        confirmDeleteTicketTypeBtn.addEventListener('click', function() {
            if (!deleteTicketTypeUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteTicketTypeUrl, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                            "content"),
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                    },
                })
                .then(res => res.json())
                .then(data => {
                    const modalInstance = bootstrap.Modal.getInstance(deleteTicketTypeModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {
                        window.location.href = "{{ route('admin.ticket.types.index') }}";
                    } else {
                        createNotification("error", "Failed to delete ticket type.", "");
                    }
                })
                .catch(err => {
                    console.error(err);
                    createNotification("error", "Something went wrong while deleting!", "");
                })
                .finally(() => {
                    confirmBtn.innerHTML = 'Delete';
                    confirmBtn.disabled = false;
                });
        });
    </script>
@endsection
