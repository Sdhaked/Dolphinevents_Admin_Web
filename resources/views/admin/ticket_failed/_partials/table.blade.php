<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Sold On</th>
                <th>Type</th>
                <th>Booking ID</th>
                <th>Email</th>
                <th>Customer</th>
                <th>Associate By</th>
                <th>Payment Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $index => $ticket)
                <tr class="{{ !$ticket->is_viewed ? 'new' : '' }}">
                    <td>
                        <div class="data-label">S No.</div>
                        <div>{{ $tickets->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label">Sold On</div>
                        <div>{{ $ticket->created_at->format('d M Y | g:i A') }}</div>
                    </td>
                    <td>
                        <div class="data-label">Type</div>
                        <div>{{ $ticket->ticketType->title ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Booking ID</div>
                        <div class="text-break">{{ $ticket->booking_id }}</div>
                    </td>
                    <td>
                        <div class="data-label">Email</div>
                        <div class="text-break">{{ $ticket->email }}</div>
                    </td>
                    <td>
                        <div class="data-label">Customer</div>
                        <div>{{ $ticket->name }}</div>
                    </td>
                    <td>
                        <div class="data-label">Associate By</div>
                        <div>{{ $ticket->coupon->associate_name ?? $ticket->coupon->also_associate ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Payment Status</div>
                        <div>{{ $ticket->payment_status_label }}</div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <a href="{{ route('admin.ticket.failed.show', $ticket->id) }}" class="action-btn universal">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <button class="action-btn delete soft-delete-btn"
                                    data-url="{{ route('admin.ticket.failed.destroy', $ticket->id) }}"
                                    title="Move to Trash">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No failed tickets found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($tickets->hasPages())
    <div class="pagination">
        {{ $tickets->links() }}
    </div>
@endif
