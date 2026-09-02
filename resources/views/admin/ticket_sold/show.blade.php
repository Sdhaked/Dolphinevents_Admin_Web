@extends('layouts.admin')

@section('head')

    <title>View Ticket Sold</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

   
    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @php($currency = \App\Models\Currency::symbolForEvent($ticket->event ?? null))
    @php($contestentVote = $ticket->contestentVotes->first())
    @php($showVotedSection = $showVotedSection ?? (bool) $ticket->event?->enable_voting)
    <!-- PRELOADER -->
    @include('admin._partials.preloader')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT ðŸ¥— -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <div class="HDandP">
                <h4 class="hd-lg"><span>{{ $ticket->name }}'s</span> Purchased Ticket</h4>
                <p><i class="fa-solid fa-arrow-right-long i-mr"></i> View Sold Ticket Detail</p>
            </div>

            <div class="grid-2 grid-sm-1 gap-col">
                {{-- LEFT COLUMN: Main Booking Details --}}
                <div class="table-responsive">
                    <table class="table view-table">
                        <tbody>
                           <tr>
                            <th>Booking Id</th>
                            <td>{{ $ticket->booking_id }}</td>
                            </tr>
                            <tr>
                                <th>Mode Of Sale</th>
                                <td>{{ strtolower($ticket->payment_method) == 'stripe' ? 'Online' : 'Offline' }}</td>
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
                                <th>Event Name</th>
                                <td>{{ $ticket->event->title ?? 'N/A' }}</td>
                            </tr>
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
                                <th>Country</th>
                                <td>{{ $ticket->country->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>State</th>
                                <td>{{ $ticket->state->name ?? 'N/A' }}</td>
                            </tr>
                            @if($showVotedSection)
                                <tr>
                                    <th>Voted</th>
                                    <td class="{{ $contestentVote ? 'green' : 'red' }}">{{ $contestentVote ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endif
                            @if($showVotedSection && $contestentVote)
                                <tr>
                                    <th>Voted To</th>
                                    <td>{{ $contestentVote->contestent?->name ?? 'N/A' }}</td>
                                </tr>
                            @endif
                            {{-- Bulk Discount Logic --}}
                            @if($ticket->bulk_discount_applied)
                            <tr>
                                <th>Bulk Ticket Discount</th>
                                <td>{{ $ticket->coupon_percentage }}% off <span class="red">[{{ $currency }}{{ number_format((float)$ticket->getRawOriginal('coupon_amount') ?: 0, 2) }}/-]</span></td>
                            </tr>
                            @endif

                            <tr>
                                <th>Ticket Amount (Paid)</th>
                                <td class="green">{{ $currency }}{{ number_format((float)$ticket->getRawOriginal('total_amount') ?: 0, 2) }}/-</td>
                            </tr>
                            <tr>
                                <th>Event Ticket PDF</th>
                                <td>
                                    @if(\Illuminate\Support\Facades\Storage::disk('public')->exists('tickets/' . $ticket->booking_id . '/Tickets_' . $ticket->booking_id . '.pdf'))
                                        <a href="{{ asset('storage/tickets/' . $ticket->booking_id . '/Tickets_' . $ticket->booking_id . '.pdf') }}" target="_blank" class="text-prim me-3">
                                            <i class="fa-solid fa-file-pdf me-1"></i>&nbsp;View&nbsp;PDF
                                        </a>
                                    @else
                                        <span class="text-muted me-3">
                                            <i class="fa-solid fa-exclamation-triangle me-1"></i>PDF not found
                                        </span>
                                    @endif
                                                                       
                                </td>
                            </tr>

                            @if($ticket->parkings && $ticket->parkings->count() > 0)
                                <tr>
                                    <th>Car Ticket PDF</th>
                                    <td>
                                        @if(\Illuminate\Support\Facades\Storage::disk('public')->exists('tickets/' . $ticket->booking_id . '/Parking_Passes_' . $ticket->booking_id . '.pdf'))
                                            <a href="{{ asset('storage/tickets/' . $ticket->booking_id . '/Parking_Passes_' . $ticket->booking_id . '.pdf') }}" target="_blank" class="text-prim me-3">
                                                <i class="fa-solid fa-file-pdf me-1"></i>&nbsp;View&nbsp;PDF
                                            </a>
                                        @else
                                            <span class="text-muted me-3">
                                                <i class="fa-solid fa-exclamation-triangle me-1"></i>PDF not found
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if($ticket->services && $ticket->services->count() > 0)
                                <tr>
                                    <th>Service Ticket PDF</th>
                                    <td>
                                        @if(\Illuminate\Support\Facades\Storage::disk('public')->exists('tickets/' . $ticket->booking_id . '/Service_Passes_' . $ticket->booking_id . '.pdf'))
                                            <a href="{{ asset('storage/tickets/' . $ticket->booking_id . '/Service_Passes_' . $ticket->booking_id . '.pdf') }}" target="_blank" class="text-prim me-3">
                                                <i class="fa-solid fa-file-pdf me-1"></i>&nbsp;View&nbsp;PDF
                                            </a>
                                        @else
                                            <span class="text-muted me-3">
                                                <i class="fa-solid fa-exclamation-triangle me-1"></i>PDF not found
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Operations</th>
                                <td>
                                    <div class="action-row"> 
                                        @if($ticket->trashed())
                                            <button type="button" class="btn btn-outline-warning" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem; font-weight: 500;" onclick="restoreTicket({{ $ticket->id }}, this)">
                                                <i class="fa-solid fa-recycle me-1"></i> Restore
                                            </button>
                                        @endif

                                        <!-- <button type="button" class="btn btn-sm btn-success ms-2" onclick="regenerateAndSendEmail({{ $ticket->id }})">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Regenerate PDF & Send Email
                                        </button>                                       -->
                                        @if(!$ticket->trashed())
                                            <form action="{{ route('admin.ticket.sold.destroy', $ticket->id) }}" method="POST"
                                                  onsubmit="return confirm('Are you sure you want to move this ticket to trash?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if(!$ticket->trashed())
                                <tr>
                                    <th>Emergency</th>
                                    <td>
                                       <button type="button" class="btn-xs btn-sec" onclick="regenerateAndSendEmail({{ $ticket->id }}, this)">
                                           <i class="fa-solid fa-paper-plane me-1"></i> Regenerate PDF & Send Email
                                       </button>                                      
                                    </td>
                                </tr>
                            @endif                                    
                        </tbody>
                    </table>
                </div>

                {{-- Coupon section --}}
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
                                    <th>Coupon Discount</th>
                                    <td>{{ $ticket->coupon_percentage }}% off</td>
                                </tr>
                                <tr>
                                    <th>Discount Amount</th>
                                    <td class="red">{{ $currency }}{{ number_format((float)$ticket->getRawOriginal('coupon_amount') ?: 0, 2) }}/-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <h6 class="hd-sm">No Coupon Code Applied</h6>
                @endif
                </div>
        </div>

            {{-- Ticker history/list --}}
            <div class="style-box mt-4">
                <h4 class="hd-sm">Event Tickets</h4>

                <!-- Table -->
                <table class="table mob-view">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Ticket Number</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                 
                    @foreach($ticket->bookedTickets as $index => $subTicket)
                    <tr class="{{ $subTicket->status === 'Not Scanned' ? 'new' : '' }}">
                        <td>
                            <div class="data-label">S.No</div>
                            <div>{{ $index + 1 }}.</div>
                        </td>
                        <td>
                            <div class="data-label">Ticket Number</div>
                            <div>{{ $subTicket->ticket_number }}</div>
                        </td>
                        <td>
                           <div class="data-label">Status</div>
                           <div>{{ $subTicket->status }}</div>
                        </td>
                        <td>
                            <div class="data-label">Scan Date</div>
                            <div>{{ $subTicket->scanned_at ? $subTicket->scanned_at->format('d M Y') : '-' }}</div>
                        </td>
                        <td>
                            <div class="data-label">Scan Time</div>
                            <div>{{ $subTicket->scanned_at ? $subTicket->scanned_at->format('h:i A') : '-' }}</div>
                        </td>
                    </tr>
                    @endforeach

                    </tbody>
                </table>


            </div>
            
            @if($ticket->parkings && $ticket->parkings->count() > 0)
                <div class="style-box mt-4">
                    <h4 class="hd-sm">Parking Tickets</h4>
                    <!-- Table -->
                    <table class="table mob-view">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Parking Code</th>
                            <th>Car Number</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticket->parkings as $index => $parking)
                            <tr class="{{ strtolower((string)$parking->status) === 'unused' ? 'new' : '' }}">
                                <td>
                                    <div class="data-label">S.No</div>
                                    <div>{{ $index + 1 }}.</div>
                                </td>
                                <td>
                                    <div class="data-label">Parking Code</div>
                                    <div>{{ $parking->parking_code ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Car Number</div>
                                    <div>{{ $parking->car_number ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Status</div>
                                    <div>{{ strtolower((string)$parking->status) === 'unused' ? 'Not Scanned' : 'Scanned' }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Scan Date</div>
                                    <div>{{ $parking->scanned_at ? $parking->scanned_at->format('d M Y') : '-' }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Scan Time</div>
                                    <div>{{ $parking->scanned_at ? $parking->scanned_at->format('h:i A') : '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No car tickets found for this booking.</td>
                            </tr>
                        @endforelse

                    </tbody>
                    </table>
                </div>
            @endif

            @if($ticket->services && $ticket->services->count() > 0)
                <div class="style-box mt-4">
                    <h4 class="hd-sm">Service Tickets</h4>
                    <table class="table mob-view">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Service Code</th>
                            <th>Service Name</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ticket->services as $index => $service)
                            <tr class="new">
                                <td>
                                    <div class="data-label">S.No</div>
                                    <div>{{ $index + 1 }}.</div>
                                </td>
                                <td>
                                    <div class="data-label">Service Code</div>
                                    <div>
                                        @if($service->passes && $service->passes->count() > 0)
                                            @foreach($service->passes->sortBy('unit_number') as $pass)
                                                <div>{{ $pass->service_code }} - {{ ucfirst($pass->status) }}</div>
                                            @endforeach
                                        @else
                                            {{ $service->service_code ?? '-' }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="data-label">Service Name</div>
                                    <div>{{ $service->service_name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Qty</div>
                                    <div>{{ $service->quantity }}</div>
                                </td>
                                <td>
                                    <div class="data-label">Price</div>
                                    <div>{{ $currency }}{{ number_format((float) $service->price, 2) }}/-</div>
                                </td>
                                <td>
                                    <div class="data-label">Total</div>
                                    <div>{{ $currency }}{{ number_format((float) $service->total_amount, 2) }}/-</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>
            @endif

            <!-- Previous Pg Btn -->
            <div class="d-flex justify-content-end my-5">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>
        </main>
    </section>

    <div class="modal fade" id="restoreTicketModal" tabindex="-1" aria-labelledby="restoreTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Restore Ticket</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Do you want to restore this ticket to the active list?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs btn-sec" id="confirmRestoreTicketBtn">Restore</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const restoreTicketModalElement = document.getElementById('restoreTicketModal');
        const confirmRestoreTicketBtn = document.getElementById('confirmRestoreTicketBtn');
        let pendingRestoreTicketId = null;
        let pendingRestoreButton = null;

        function regenerateAndSendEmail(ticketId, triggerButton) {
            const button = triggerButton;
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Processing...';
            
            fetch(`/admin/ticket-sold/resend-email/${ticketId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof createNotification === 'function') {
                        createNotification('success', data.message, '');
                    }
                } else {
                    if (typeof createNotification === 'function') {
                        createNotification('error', data.message || 'Failed to send email', '');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof createNotification === 'function') {
                    createNotification('error', 'An error occurred while processing request', '');
                }
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }

        function restoreTicket(ticketId, button) {
            pendingRestoreTicketId = ticketId;
            pendingRestoreButton = button;

            const modal = new bootstrap.Modal(restoreTicketModalElement);
            modal.show();
        }

        confirmRestoreTicketBtn.addEventListener('click', function() {
            if (!pendingRestoreTicketId || !pendingRestoreButton) return;

            const button = pendingRestoreButton;
            const originalText = button.innerHTML;
            const confirmBtn = this;

            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Restoring...';
            confirmBtn.disabled = true;
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Restoring...';

            fetch(`/admin/ticket-sold/restore/${pendingRestoreTicketId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const modalInstance = bootstrap.Modal.getInstance(restoreTicketModalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }

                if (data.success) {
                    if (typeof createNotification === 'function') {
                        createNotification('success', data.message || 'Ticket restored successfully', '');
                    }
                    setTimeout(() => {
                        window.location.href = `{{ route('admin.ticket.sold.index') }}`;
                    }, 800);
                } else {
                    if (typeof createNotification === 'function') {
                        createNotification('error', data.message || 'Failed to restore ticket', '');
                    }
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof createNotification === 'function') {
                    createNotification('error', 'An error occurred while restoring ticket', '');
                }
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
                confirmBtn.innerHTML = 'Restore';
                confirmBtn.disabled = false;
                pendingRestoreTicketId = null;
                pendingRestoreButton = null;
            });
        });
    </script>
</body>

</html>


@endsection
