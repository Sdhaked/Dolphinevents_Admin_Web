@extends('layouts.admin')

@section('head')
    <title>View Failed Ticket Sale</title>
    <meta name="description" content="View failed or pending verification ticket sale.">

    @include('admin._partials.head.g-links')
    @include('admin._partials.head.g-css-files')
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @php($currency = \App\Models\Currency::symbolForEvent($ticket->event ?? null))

    @include('admin._partials.preloader')
    @include('admin._partials.sidebar')
    @include('admin._partials.header')

    <section class="wrapper">
        <main class="dash-content">
            @include('admin._partials.breadcrumb')

            <div class="HDandP">
                <h4 class="hd-lg"><span>{{ $ticket->name }}'s</span> Purchased Ticket</h4>
                <p><i class="fa-solid fa-arrow-right-long i-mr"></i> View Failed Ticket Detail</p>
            </div>

            <div class="grid-2 grid-sm-1 gap-col">
                <div class="table-responsive">
                    <table class="table view-table">
                        <tbody>
                            <tr>
                                <th>Booking Id</th>
                                <td>{{ $ticket->booking_id }}</td>
                            </tr>
                            <tr>
                                <th>Mode Of Sale</th>
                                <td>{{ strtolower((string) $ticket->payment_method) === 'stripe' ? 'Online' : 'Offline' }}</td>
                            </tr>
                            <tr>
                                <th>Transaction ID</th>
                                <td>{{ $ticket->transaction_id ?? $ticket->paymentTransaction?->transaction_id ?? $ticket->gateway_payment_intent_id ?? $ticket->gateway_session_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Initiated On</th>
                                <td>{{ $ticket->payment_initiated_at ? $ticket->payment_initiated_at->format('d-m-Y | h:i:s A') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Completed On</th>
                                <td>{{ $ticket->payment_completed_at ? $ticket->payment_completed_at->format('d-m-Y | h:i:s A') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Failed/Cancelled On</th>
                                <td>
                                    @if($ticket->payment_failed_at)
                                        {{ $ticket->payment_failed_at->format('d-m-Y | h:i:s A') }}
                                    @elseif($ticket->payment_cancelled_at)
                                        {{ $ticket->payment_cancelled_at->format('d-m-Y | h:i:s A') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Payment Reason</th>
                                <td>{{ $ticket->payment_failure_reason ?? $ticket->paymentTransaction?->failure_reason ?? $ticket->paymentTransaction?->cancel_reason ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Sold By (Admin)</th>
                                <td>{{ $ticket->creator->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Event Name</th>
                                <td>{{ $ticket->event->title ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>{{ $ticket->event->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Sold On</th>
                                <td>{{ $ticket->created_at->format('d-m-Y | h:i:s A') }}</td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>{{ $ticket->ticketType->title ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Customer Name</th>
                                <td>{{ $ticket->name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td class="text-break">{{ $ticket->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>({{ filled($ticket->phone_prefix) ? $ticket->phone_prefix : ($ticket->country?->phonecode ? '+' . ltrim((string) $ticket->country->phonecode, '+') : 'N/A') }}) {{ $ticket->mobile_number }}</td>
                            </tr>
                            <tr>
                                <th>Booking Status</th>
                                <td>{{ $ticket->booking_status_label }}</td>
                            </tr>
                            @if($ticket->bulk_discount_applied)
                                <tr>
                                    <th>Bulk Ticket Discount</th>
                                    <td>{{ $ticket->coupon_percentage }}% off <span class="red">[{{ $currency }}{{ number_format((float) $ticket->getRawOriginal('coupon_amount') ?: 0, 2) }}/-]</span></td>
                                </tr>
                            @endif
                            <tr>
                                <th>Ticket Amount (Paid)</th>
                                <td class="green">{{ $currency }}{{ number_format((float) $ticket->getRawOriginal('total_amount') ?: 0, 2) }}/-</td>
                            </tr>
                            <tr>
                                <th>Refund Status</th>
                                <td>
                                    @php($isRefunded = strtolower((string) $ticket->refund_status) === \App\Models\TicketCounter::REFUND_REFUNDED)
                                    @if($isRefunded)
                                        <span class="green" id="refundStatusText">{{ $ticket->refund_status_label }}</span>
                                    @else
                                        <button class="btn-xs btn-prim" id="refundStatusBtn" data-bs-toggle="modal" data-bs-target="#confirmRefundBox">
                                            <span id="refundStatusText">{{ $ticket->refund_status_label }}</span>
                                            <i class="fa-solid fa-rotate i-ml"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Refund On</th>
                                <td id="refundedAtText">{{ $ticket->refunded_at ? $ticket->refunded_at->format('d M Y \\A\\t h:i A') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Operations</th>
                                <td>
                                    <div class="action-row">
                                        <button class="action-btn delete" id="deleteFailedTicketBtn" data-url="{{ route('admin.ticket.failed.destroy', $ticket->id) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    @if($ticket->coupon_applied)
                        <h5 class="hd-sec">Coupon Detail</h5>
                        <div class="table-responsive view-data-tbl">
                            <table class="table view-table">
                                <tbody>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $ticket->coupon->creator->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Associated by</th>
                                        <td>{{ $ticket->coupon->associate_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>About Associated Person</th>
                                        <td>{{ $ticket->coupon->also_associate ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Coupon Code</th>
                                        <td>{{ $ticket->coupon_code }}</td>
                                    </tr>
                                    <tr>
                                        <th>Coupon Code Discount</th>
                                        <td>{{ $ticket->coupon_percentage }}% off</td>
                                    </tr>
                                    <tr>
                                        <th>Discount Amount</th>
                                        <td class="red">{{ $currency }}{{ number_format((float) $ticket->getRawOriginal('coupon_amount') ?: 0, 2) }}/-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <h6 class="hd-sm">No Coupon Code Applied</h6>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end my-5">
                <button class="btn-sm btn-sec" onclick="window.history.back()">Back <i class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>

            <div class="modal fade" id="confirmRefundBox" tabindex="-1" aria-labelledby="confirmRefundBoxLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="hd-sm m-0">Change Status To Refunded</h6>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                <strong>Are you sure?</strong> You want to change status to <strong>Refunded?</strong> Once changed, you can not change it again.
                            </div>

                            <p>Type "Refunded" to change status</p>
                            <div class="form-floating">
                                <input type="text" class="form-control" id="refundConfirmText" required="">
                                <label for="refundConfirmText">Refunded</label>
                            </div>

                            <div class="mt-4">
                                <button type="button" class="btn-sm btn-prim w-100 sub-btn" id="markRefundedBtn" disabled>Change Status</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="ticketDeleteModal" tabindex="-1" aria-labelledby="ticketDeleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="hd-sm m-0" id="ticketDeleteModalLabel">Delete Ticket</h6>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Are you sure you want to move this ticket to trash?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-xs danger-fill-btn" id="ticketDeleteConfirmBtn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <script>
        const statusInp = document.querySelector('#refundConfirmText');
        const markRefundedBtn = document.querySelector('#markRefundedBtn');
        const refundModalElement = document.getElementById('confirmRefundBox');
        const deleteModalElement = document.getElementById('ticketDeleteModal');
        const deleteBtn = document.getElementById('deleteFailedTicketBtn');
        const deleteConfirmBtn = document.getElementById('ticketDeleteConfirmBtn');
        const deleteModal = deleteModalElement && window.bootstrap?.Modal ? new bootstrap.Modal(deleteModalElement) : null;

        ['paste', 'copy', 'cut', 'drop', 'contextmenu'].forEach((eventName) => {
            statusInp?.addEventListener(eventName, (e) => e.preventDefault());
        });

        statusInp?.addEventListener('input', () => {
            markRefundedBtn.disabled = statusInp.value !== 'Refunded';
        });

        markRefundedBtn?.addEventListener('click', function () {
            const originalText = markRefundedBtn.innerHTML;
            markRefundedBtn.disabled = true;
            markRefundedBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Updating...';

            fetch(`{{ route('admin.ticket.failed.refund', $ticket->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirm_text: statusInp.value,
                }),
            })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw data;
                    return data;
                })
                .then(data => {
                    const refundButton = document.getElementById('refundStatusBtn');
                    if (refundButton) {
                        refundButton.outerHTML = `<span class="green" id="refundStatusText">${data.refund_status || 'Refunded'}</span>`;
                    } else {
                        document.getElementById('refundStatusText').textContent = data.refund_status || 'Refunded';
                    }
                    document.getElementById('refundedAtText').textContent = data.refunded_at || '-';
                    bootstrap.Modal.getInstance(refundModalElement)?.hide();
                    createNotification('success', data.message || 'Refund status updated successfully.', '');
                })
                .catch(data => {
                    createNotification('error', data.message || 'Failed to update refund status.', '');
                    markRefundedBtn.disabled = false;
                })
                .finally(() => {
                    markRefundedBtn.innerHTML = originalText;
                });
        });

        deleteBtn?.addEventListener('click', function () {
            if (deleteModal) {
                deleteModal.show();
            }
        });

        deleteConfirmBtn?.addEventListener('click', function () {
            const originalText = deleteConfirmBtn.innerHTML;
            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Deleting...';

            fetch(deleteBtn.dataset.url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        createNotification('success', data.message || 'Ticket moved to trash successfully', '');
                        setTimeout(() => window.location.href = `{{ route('admin.ticket.failed.index') }}`, 700);
                    } else {
                        createNotification('error', data.message || 'Failed to delete ticket', '');
                    }
                })
                .catch(() => createNotification('error', 'Something went wrong while deleting!', ''))
                .finally(() => {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.innerHTML = originalText;
                });
        });
    </script>
@endsection
