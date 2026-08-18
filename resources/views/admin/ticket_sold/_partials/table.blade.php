@php
    $showVotedColumn = $showVotedColumn ?? false;
    $isTicketSoldTrashPage = request()->routeIs('admin.ticket.sold.trash');
    $permissionTablesReady = \Illuminate\Support\Facades\Schema::hasTable('permissions')
        && \Illuminate\Support\Facades\Schema::hasTable('role_permissions');
    $ticketSoldPermissionSlugs = collect();
    $authUser = auth()->user();

    if ($isTicketSoldTrashPage && $permissionTablesReady && $authUser?->role) {
        $ticketSoldPermissionSlugs = \App\Models\Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $authUser->role)
            ->pluck('permissions.slug');
    }

    $hasTicketSoldPermission = function (array $permissions) use ($isTicketSoldTrashPage, $ticketSoldPermissionSlugs) {
        if (!$isTicketSoldTrashPage) {
            return true;
        }

        return $ticketSoldPermissionSlugs->intersect($permissions)->isNotEmpty();
    };

    $canViewSoldTicket = $hasTicketSoldPermission([
        'ticket-sold-manage-ticket-sold',
        'ticket-sold-view-sold-tickets',
        'ticket-sold-manage-ticket-sold-trash',
        'ticket-sold-view-ticket-sold-trash',
    ]);
    $canRestoreSoldTicket = $hasTicketSoldPermission([
        'ticket-sold-manage-ticket-sold-trash',
        'ticket-sold-restore-sold-tickets',
    ]);
    $canForceDeleteSoldTicket = $hasTicketSoldPermission([
        'ticket-sold-manage-ticket-sold-trash',
        'ticket-sold-delete-trash-records',
        'ticket-sold-permanently-delete-sold-tickets',
    ]);
    $canSoftDeleteSoldTicket = $hasTicketSoldPermission([
        'ticket-sold-manage-ticket-sold',
        'ticket-sold-delete-sold-tickets',
    ]);
@endphp

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
                @if($showVotedColumn)
                    <th>Voted</th>
                @endif
                <th>Associate By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $index => $ticket)
                <tr class="{{ (!$isTicketSoldTrashPage && !$ticket->is_viewed) ? 'new' : '' }}">
                    <td>
                        <div class="data-label">S No.</div>
                        <div>
                            {{ $tickets->firstItem() + $index }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Sold On</div>
                        <div>
                            {{ $ticket->created_at->format('d M Y | g:i A') }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Type</div>
                        <div>
                            {{ $ticket->ticketType->title ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Booking ID</div>
                        <div class="text-break">
                            {{ $ticket->booking_id }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Email</div>
                        <div class="text-break">
                            {{ $ticket->email }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Customer</div>
                        <div>
                            {{ $ticket->name }}
                        </div>
                    </td>
                    @if($showVotedColumn)
                        <td>
                            <div class="data-label">Voted</div>
                            <div class="{{ $ticket->contestentVotes->isNotEmpty() ? 'green' : 'red' }}">
                                {{ $ticket->contestentVotes->isNotEmpty() ? 'Yes' : 'No' }}
                            </div>
                        </td>
                    @endif
                    <td>
                        <div class="data-label">Associate By</div>
                        <div>
                            {{ $ticket->coupon->associate_name ?? $ticket->coupon->also_associate ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                @if($canViewSoldTicket)
                                    <a href="{{ route('admin.ticket.sold.show', $ticket->id) }}" class="action-btn universal">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                @endif
                                @if($ticket->trashed())
                                    {{-- 2. Restore Button: Only visible in Trash List --}}
                                    @if($canRestoreSoldTicket)
                                        <button class="action-btn edit restore-btn"
                                                data-url="{{ route('admin.ticket.sold.restore', $ticket->id) }}"
                                                title="Restore Ticket">
                                            <i class="fa-solid fa-recycle"></i>
                                        </button>
                                    @endif

                                    {{-- 3. Permanent Delete: Only visible in Trash List --}}
                                    @if($canForceDeleteSoldTicket)
                                        <button class="action-btn delete permanent-delete-btn"
                                                data-url="{{ route('admin.ticket.sold.force_delete', $ticket->id) }}"
                                                title="Delete Permanently">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                @else
                                    {{-- 4. Normal Delete (Move to Trash): Only visible in Normal List --}}
                                    @if($canSoftDeleteSoldTicket)
                                        <button class="action-btn delete soft-delete-btn"
                                                data-url="{{ route('admin.ticket.sold.destroy', $ticket->id) }}"
                                                title="Move to Trash">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showVotedColumn ? 9 : 8 }}" class="text-center">No tickets found</td>
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
