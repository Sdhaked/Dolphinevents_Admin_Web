<div class="table-responsive mt-4">
    @php
        $currency = \App\Models\Currency::symbolForEvent($event ?? null);
    @endphp
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Type</th>
                <th>@Price ({{ $currency }})</th>
                <th>Total Tickets</th>
                <th>Tickets Sold</th>
                <th>Bulk Discount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $index => $ticket)
                <!-- TR 1 -->
                <tr>
                    <td>
                        <div class="data-label">S No.</div>
                        <div>
                            {{ $tickets->firstItem() + $index }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Type</div>
                        <div>
                            {{ $ticket->title }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">@Price ({{ $currency }})</div>
                        <div>
                            {{ $currency }}{{ $ticket->ticket_price }}/-
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Total Tickets</div>
                        <div>
                            {{ $ticket->total_tickets }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Tickets Sold</div>
                        <div>
                            {{ $ticket->tickets_sold ?? 0 }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Bulk Discount</div>
                        <div>
                            {{ $ticket->enable_bulk_discount ? 'Enable' : 'Disable' }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <a href="{{ route('admin.ticket.types.show', $ticket->id) }}"
                                    class="action-btn universal">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                
                                @if(isset($event) && $event->type == 2)
                                <a href="{{ route('admin.ticket.types.editSeats', $ticket->id) }}" role="button"
                                    class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                
                                @else
                                <a href="{{ route('admin.ticket.types.edit', $ticket->id) }}" role="button"
                                    class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                
                                @endif

                                @if(($ticket->tickets_sold ?? 0) == 0)
                                <button class="action-btn delete"
                                    data-url="{{ route('admin.ticket.types.destroy', $ticket->id) }}"
                                    data-title="{{ $ticket->title }}" data-bs-toggle="modal" data-bs-target="#deleteTicketTypeModal">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="text-center">No data found!</div>
                    </td>
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
