@extends('layouts.admin')

@section('head')
    <title>View Discount Coupon</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

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
                <h4 class="hd-lg"><span>Coupon</span> view page</h4>
            </div>

            <div class="grid-1 gap-card">
                <div class="table-responsive">
                    <table class="table view-table">
                        <tbody>
                            <tr>
                                <th>Title</th>
                                <td>{{ $coupon->title }}</td>
                            </tr>
                            <tr>
                                <th>Coupon Code</th>
                                <td>{{ $coupon->coupon_code }}</td>
                            </tr>

                            <tr>
                                <th>Discount</th>
                                <td>{{ $coupon->discount }}% Off</td>
                            </tr>
                            <tr>
                                <th>Event</th>
                                <td>{{ $coupon->event->title ?? 'VIP SEATING' }}</td>
                            </tr>
                            <tr>
                                <th>Associate Name</th>
                                <td>{{ $coupon->associate_name }}</td>
                            </tr>
                            <tr>
                                <th>About Associate</th>
                                <td>{{ $coupon->also_associate ?? 'Lorem ipsum dolor sit amet consectetur adipisicing elit.' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Apply To Ticket Type</th>
                               <td>
                                  @php
                                      $ticketTypes = collect();
                                      if (!empty($coupon->ticket_type_ids)) {
                                          $ticketTypes = \App\Models\TicketType::whereIn('id', $coupon->ticket_type_ids)->get();
                                      }
                                  @endphp
                              
                                  @if($ticketTypes->isNotEmpty())
                                      <div class="list-span">
                                          @foreach ($ticketTypes as $ticketType)
                                              <span>{{ $ticketType->title }}</span>
                                          @endforeach
                                      </div>
                                  @else
                                      <span>N/A</span>
                                  @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created By Admin</th>
                                <td>  
                                    @if($coupon->creator)
                                        <div style="font-weight: 500;">{{ $coupon->creator->name }}</div>
                                        <div style="font-size: 0.85em; margin-top: 2px;" class="text-break">{{ $coupon->creator->email }}</div>
                                    @else
                                        <div>N/A</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Operations</th>
                                <td>
                                    <div class="action-row">
                                        <a href="{{ route('admin.discount.coupons.edit', $coupon->id) }}" class="action-btn edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('admin.discount.coupons.destroy', $coupon->id) }}" method="POST" style="display: inline;" id="deleteDiscountCouponForm">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="action-btn delete" id="openDeleteDiscountCouponModal">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Previous Pg Btn -->
            <div class="d-flex justify-content-end my-4">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>
        </main>
    </section>

    <div class="modal fade" id="deleteDiscountCouponShowModal" tabindex="-1" aria-labelledby="deleteDiscountCouponShowModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Discount Coupon</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this coupon?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteDiscountCouponShowBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const deleteDiscountCouponForm = document.getElementById('deleteDiscountCouponForm');
        const openDeleteDiscountCouponModal = document.getElementById('openDeleteDiscountCouponModal');
        const deleteDiscountCouponShowModalElement = document.getElementById('deleteDiscountCouponShowModal');
        const confirmDeleteDiscountCouponShowBtn = document.getElementById('confirmDeleteDiscountCouponShowBtn');

        openDeleteDiscountCouponModal?.addEventListener('click', function() {
            const modal = new bootstrap.Modal(deleteDiscountCouponShowModalElement);
            modal.show();
        });

        confirmDeleteDiscountCouponShowBtn?.addEventListener('click', function() {
            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;
            deleteDiscountCouponForm.submit();
        });
    </script>
@endsection
