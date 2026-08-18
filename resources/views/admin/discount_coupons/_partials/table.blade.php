<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Title</th>
                <th>Discount</th>
                <th>Ticket Types</th>
                <th>Admin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($coupons as $index => $coupon)
                <!-- TR {{ $index + 1 }} -->
                <tr>
                    <td>
                        <div class="data-label">S No.</div>
                        <div>
                            {{ $coupons->firstItem() + $index }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Title</div>
                        <div>
                            {{ $coupon->title }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Discount</div>
                        <div>
                            {{ $coupon->discount }}% Off
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Ticket Types</div>
                        <div class="list-span">
                            @if($coupon->ticket_type_ids && count($coupon->ticket_type_ids) > 0)
                                @php
                                    $ticketTypes = \App\Models\TicketType::whereIn('id', $coupon->ticket_type_ids)->get();
                                @endphp
                                @foreach($ticketTypes as $ticketType)
                                    <span>{{ $ticketType->title }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No ticket types</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Admin</div>
                        <div>
                            @if($coupon->creator)
                                <div style="font-weight: 500;">{{ $coupon->creator->name }}</div>
                                <div style="font-size: 0.85em; margin-top: 2px;" class="text-break">{{ $coupon->creator->email }}</div>
                            @else
                                <div>N/A</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <a href="{{ route('admin.discount.coupons.show', $coupon->id) }}"
                                    class="action-btn universal">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.discount.coupons.edit', $coupon->id) }}" role="button"
                                    class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <button class="action-btn delete"
                                    data-url="{{ route('admin.discount.coupons.destroy', $coupon->id) }}"
                                    data-title="{{ $coupon->coupon_code ?: $coupon->title }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteDiscountCouponModal">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="text-center">No discount coupons found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($coupons->hasPages())
    <div class="pagination">
        <ul>
            {{ $coupons->links() }}
        </ul>
    </div>
@endif
