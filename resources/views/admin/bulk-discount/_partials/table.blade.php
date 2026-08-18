@forelse($bulkDiscounts as $index => $discount)
    <tr>
        <td>
            <div class="data-label">S.No</div>
            <div>{{ $index + 1 }}</div>
        </td>
        <td>
            <div class="data-label">Min Order Qty</div>
            <div>{{ $discount->min_order_qty }}</div>
        </td>
        <td>
            <div class="data-label">Discount (%)</div>
            <div>{{ $discount->discount_percentage }}% off</div>
        </td>
        <td>
            <div class="data-label">Action</div>
            <div>
                <div class="action-row">
                    <button class="action-btn edit" type="button" data-id="{{ $discount->id }}"
                        data-min-qty="{{ $discount->min_order_qty }}" data-discount="{{ $discount->discount_percentage }}"
                        data-bs-toggle="modal" data-bs-target="#editBulkModal">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button class="action-btn delete" data-id="{{ $discount->id }}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center">No bulk discounts found.</td>
    </tr>
@endforelse