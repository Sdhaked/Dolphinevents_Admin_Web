@extends('layouts.admin')

@section('head')
    <title>Discount Coupons</title>
    <meta name="description" content="Manage discount coupons for your event.">

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

            <h4 class="hd-lg">Discount Coupons</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span>{{ $coupons->total() }}</span></h6>
                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <a href="{{ route('admin.discount.coupons.create') }}" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i> Create New
                        </a>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" id="search" class="form-control" placeholder="Search">
                        <span class="search-base">Search By: Ticket Type</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="discountCouponsTable">
                    @include('admin.discount_coupons._partials.table', ['coupons' => $coupons])
                </div>
            </div>

        </main>
    </section>

    <div class="modal fade" id="deleteDiscountCouponModal" tabindex="-1" aria-labelledby="deleteDiscountCouponModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Discount Coupon</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete <strong id="discountCouponName"></strong>?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteDiscountCouponBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("discountCouponsTable");
        const deleteDiscountCouponModalElement = document.getElementById('deleteDiscountCouponModal');
        const confirmDeleteDiscountCouponBtn = document.getElementById('confirmDeleteDiscountCouponBtn');

        let currentPage = 1;
        let deleteUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }
        // fetch data
        function fetchData(page = 1) {
            const search = searchInput.value;
            currentPage = page;

            fetch(`{{ route('admin.discount.coupons.index') }}?page=${page}&search=${encodeURIComponent(search)}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const debouncedSearch = debounce(() => fetchData(1), 600);

            // search
            if (searchInput) {
                searchInput.addEventListener('input', debouncedSearch);
            }

            // handle pagination
            document.addEventListener("click", function(e) {
                const link = e.target.closest(".page-link-ajax");
                if (!link) return;

                e.preventDefault();
                const url = new URL(link.href);
                const page = url.searchParams.get("page") || 1;
                fetchData(page);
            });
        });

        tableContainer.addEventListener("click", function(e) {
            const deleteBtn = e.target.closest(".action-btn.delete");
            if (!deleteBtn) return;

            deleteUrl = deleteBtn.getAttribute("data-url");
            const couponName = deleteBtn.getAttribute("data-title") || "this discount coupon";
            if (!deleteUrl) return console.error("Delete URL not found!");

            document.getElementById('discountCouponName').textContent = couponName;
        });

        confirmDeleteDiscountCouponBtn.addEventListener('click', function() {
            if (!deleteUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteUrl, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                    },
                })
                .then(res => res.json())
                .then(data => {
                    const modalInstance = bootstrap.Modal.getInstance(deleteDiscountCouponModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    createNotification("success", data.message || "Discount coupon deleted successfully", "");
                    fetchData(currentPage);
                })
                .catch(err => {
                    console.error(err);
                    createNotification("error", "Something went wrong while deleting!", "");
                })
                .finally(() => {
                    confirmBtn.innerHTML = 'Delete';
                    confirmBtn.disabled = false;
                    deleteUrl = null;
                });
        });
    </script>
@endsection
