@extends('layouts.admin')

@section('head')
    <title>Edit Ticket Type</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')

    <script src="{{ asset('javascript/pages/create-ticket-type.js') }}" defer></script>

    <script>
    window.stadiumData = {
        lwdata: @json($lwdata),
        clwdata: @json($clwdata),
        crwdata: @json($crwdata),
        rwdata: @json($rwdata),
        otherIds: @json($otherIds ?? []),
        currentIds: @json($currentIds ?? []),
        seatAssignments: @json($seatAssignments ?? [])
    };
    </script>
    <script src="{{ asset('javascript/pages/stadium/seat-selection.js') }}" defer></script>
    <style>
        #actionLoader {
            opacity: 0.7 !important;
        }

        .bulk-discount-table-wrap {
            position: relative;
            min-height: 180px;
        }

        .bulk-discount-table-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.7);
            z-index: 2;
            transition: opacity 0.2s ease;
        }

        .bulk-discount-table-loader[hidden] {
            display: none !important;
        }

        .bulk-discount-table-loader .loader-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--color-dark, #1f2937);
            font-weight: 600;
        }
    </style>
@endsection

@section('body')

    <!-- PRELOADER -->
    @include('admin._partials.preloader')
    @include('admin._partials.preloader002')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')


    <section class="wrapper">
        <main class="dash-content">
            @include('admin._partials.breadcrumb')

            <h5 class="hd-lg">Edit Ticket Type: {{ $ticket->title }}</h5>

            <div>
                <button class="btn-sm btn-sec" data-bs-toggle="modal" data-bs-target="#modalID">
                    Venue Layout
                </button>
            </div>

            {{-- UPDATE ACTION --}}
            <form action="{{ route('admin.ticket.types.update', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                @method('POST')
                <input type="hidden" name="seat_selection_form" value="1">
                
                <x-admin.create-stadium />

                <input type="hidden" name="event_id" value="{{ $ticket->event_id }}">
                
                <div class="label-spc upload-box">
                    <div class="previewBox mt-2">
                        <x-admin.media-remove
                            :exists="filled($ticket?->featured_image)"
                            :delete-url="route('admin.media.destroy', ['target' => 'ticket-type-featured-image', 'id' => $ticket->id])"
                            label="ticket type image" />
                        <img src="{{ $ticket->featured_image ? asset('storage/' . $ticket->featured_image) : asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                    </div>
                    <div class="mt-4">
                        <label for="uploadf">Upload Featured Image</label>
                        <input type="file" name="featured_image" class="form-control mt-1" accept="image/*">
                        <div class="label-spc mt-1">
                            <input type="text" name="featured_image_alt_text" class="form-control"
                                value="{{ old('featured_image_alt_text', $ticket->featured_image_alt_text) }}" placeholder="Alt Text">
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="text" name="title" class="form-control" id="title" value="{{ old('title', $ticket->title) }}" required>
                    <label for="title">Title</label>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <div class="form-floating">
                        <input type="color" class="form-control" name="ticket_type_color" id="ticketcolor" value="{{ old('ticket_type_color', $ticket->ticket_type_color) }}" required>
                        <label for="ticketcolor">Color*</label>
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text">{{ \App\Models\Currency::symbolForEvent($event ?? null) }}</span>
                        <div class="form-floating flex-grow-1">
                            <input type="number" name="ticket_price" class="form-control" id="ticket_price" value="{{ old('ticket_price', $ticket->ticket_price) }}" required>
                            <label for="ticket_price">Ticket Price (Per Ticket)*</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <textarea name="description" class="form-control" style="height: 100px">{{ old('description', $ticket->description) }}</textarea>
                    <label>Description</label>
                </div>

                <div>
                    <button type="button" class="check-btn">
                        <input class="form-check-input disc-check" name="enable_bulk_discount" type="checkbox"
                            value="1" id="enable_bulk_discount" {{ $ticket->enable_bulk_discount ? 'checked' : '' }}>
                        <label for="enable_bulk_discount"> Enable Bulk Ticket Discount</label>
                    </button>
                </div>

                <div class="style-box">
                    <h6 class="hd-sm mb-2">Tax</h6>
                    <button type="button" class="check-btn my-3">
                        <input class="form-check-input disc-check" name="enable_tax" type="checkbox" value="1"
                            id="enable-tax" {{ $ticket->enable_tax ? 'checked' : '' }}>
                        <label for="enable-tax"> Enable Tax</label>
                    </button>

                    <div class="grid-2 grid-sm-1 gap-card">
                        <div class="form-floating">
                            <input type="text" name="tax_label" class="form-control" id="tax-lable" value="{{ old('tax_label', $ticket->tax_label) }}">
                            <label for="tax-lable">Tax Label</label>
                        </div>
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="tax_value" class="form-control" id="tax-value" value="{{ old('tax_value', $ticket->tax_value) }}">
                                <label for="tax-value">Tax Value</label>
                            </div>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="style-box">
                    <h6 class="hd-sm mb-2">Extra charges</h6>
                    <button type="button" class="check-btn my-3">
                        <input class="form-check-input disc-check" name="enable_extra_charges" type="checkbox"
                            value="1" id="enable-extra-charges" {{ $ticket->enable_extra_charges ? 'checked' : '' }}>
                        <label for="enable-extra-charges"> Enable Extra charges</label>
                    </button>

                    <div class="grid-2 grid-sm-1 gap-card">
                        <div class="form-floating">
                            <input type="text" name="extra_charges_label" class="form-control" id="extra-charges-label" value="{{ old('extra_charges_label', $ticket->extra_charges_label) }}">
                            <label for="extra-charges-label">Extra Charge Label</label>
                        </div>
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="extra_charges_value" class="form-control" id="extra-charges-value" value="{{ old('extra_charges_value', $ticket->extra_charges_value) }}">
                                <label for="extra-charges-value">Extra Charge Value</label>
                            </div>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-md btn-sec">Update Ticket Type</button>
                    <a href="{{ route('admin.ticket.types.index') }}" class="btn-md btn-outline-sec">Cancel</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <hr class="my-5">

            <div class="style-box">
                <button type="button" class="btn-xs btn-sec" data-bs-toggle="modal" data-bs-target="#addBulkDiscountModal" id="addBulkDiscountBtn"><i
                        class="fa-solid fa-plus i-mr"></i> Add Bulk
                    Discount Slab</button>
                <!-- Data Table -->
                <div class="table-responsive mt-4 bulk-discount-table-wrap" id="bulkDiscountTableWrapper">
                    <div class="bulk-discount-table-loader" id="bulkDiscountTableLoader">
                        <div class="loader-box">
                            <span class="spinner-border text-secondary" role="status" aria-hidden="true"></span>
                            <span>Loading bulk discounts...</span>
                        </div>
                    </div>
                    <table class="table mob-view">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Min Order Qty</th>
                                <th>Discount (%)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="bulkDiscountTableBody">
                            @include('admin.bulk-discount._partials.table', ['bulkDiscounts' => $bulkDiscounts])
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <!-- Add Modal Box -->
    <div class="modal fade" id="addBulkDiscountModal" tabindex="-1" aria-labelledby="addBulkDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="m-0" id="addBulkDiscountModalLabel">Add Bulk Discount</h6>
                <button type="button" class="btn" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <form id="addBulkForm" class="needs-validation" novalidate="">
                    @csrf
                    <input type="hidden" name="ticket_type_id" value="{{ $ticket->id }}">

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="minOrderQty" name="min_order_qty" required>
                        <label>Min Order Qty*</label>
                        <div class="invalid-feedback minQtyErr"></div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="discount_percentage" id="discountPercentage" required>
                        <label>Discount (%)*</label>
                        <div class="invalid-feedback discountErr"></div>
                    </div>

                    <button class="btn-md btn-sec w-100" id="addBulkSubmitBtn">
                        <span class="btn-text">Create</span>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


    <!-- Edit Modal -->
    <div class="modal fade" id="editBulkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="m-0">Edit Bulk Discount</h6>
                <button type="button" class="btn" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <form id="editBulkForm">
                    @csrf

                    <input type="hidden" id="edit_bulk_id">

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="min_order_qty" id="edit_min_qty" required>
                        <label>Min Order Qty*</label>
                        <div class="invalid-feedback editMinQtyErr"></div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="discount_percentage" id="edit_discount" required>
                        <label>Discount (%)*</label>
                        <div class="invalid-feedback editDiscountErr"></div>
                    </div>

                    <button class="btn-md btn-sec w-100" id="editBulkSubmitBtn">
                        <span class="btn-text">Update</span>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="deleteBulkDiscountSeatModal" tabindex="-1" aria-labelledby="deleteBulkDiscountSeatModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Discount</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this discount?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteBulkDiscountSeatBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
   
<!-- Vanue Modal -->
     <x-admin.venue-modal />
</body>

  <!-- script to add,edit and delete bulk discount -->
<script>
const tableBody = document.getElementById("bulkDiscountTableBody");
const bulkDiscountTableLoader = document.getElementById("bulkDiscountTableLoader");
const actionLoader = document.getElementById('actionLoader');
let pendingBulkDiscountSeatId = null;
const deleteBulkDiscountSeatModalElement = document.getElementById('deleteBulkDiscountSeatModal');
const confirmDeleteBulkDiscountSeatBtn = document.getElementById('confirmDeleteBulkDiscountSeatBtn');
const addBulkForm = document.getElementById("addBulkForm");
const editBulkForm = document.getElementById("editBulkForm");
const addBulkSubmitBtn = document.getElementById("addBulkSubmitBtn");
const editBulkSubmitBtn = document.getElementById("editBulkSubmitBtn");

// Build base URL with ticket_type_id
const baseUrl = new URL(`{{ route('admin.bulk-discount.index') }}?ticket_type_id={{ $ticket->id }}`);

function setActionLoaderState(show) {
    if (!actionLoader) {
        return;
    }

    actionLoader.style.display = show ? 'flex' : 'none';
}

function setTableLoaderState(show) {
    if (!bulkDiscountTableLoader) {
        return;
    }

    bulkDiscountTableLoader.hidden = !show;
}

function setButtonLoadingState(button, show, loadingText) {
    if (!button) {
        return;
    }

    const text = button.querySelector('.btn-text');
    const spinner = button.querySelector('.spinner-border');

    button.disabled = show;

    if (text) {
        text.textContent = show ? loadingText : text.dataset.defaultText;
    }

    if (spinner) {
        spinner.style.display = show ? 'inline-block' : 'none';
    }
}

function initializeButtonText(button) {
    if (!button) {
        return;
    }

    const text = button.querySelector('.btn-text');
    if (text && !text.dataset.defaultText) {
        text.dataset.defaultText = text.textContent.trim();
    }
}

initializeButtonText(addBulkSubmitBtn);
initializeButtonText(editBulkSubmitBtn);

/* =====================================================
   RELOAD TABLE PARTIAL
===================================================== */
function reloadBulkTable() {
    setTableLoaderState(true);

    return fetch(baseUrl.href)
        .then(res => res.json())
        .then(data => {
            tableBody.innerHTML = data.html;
            attachRowEvents();
        })
        .catch(err => console.error("Reload error:", err))
        .finally(() => {
            setTableLoaderState(false);
        });
}

function attachRowEvents() {
    // Event delegation below handles row actions after table reload.
}



/* =====================================================
   ADD BULK DISCOUNT
===================================================== */
addBulkForm.addEventListener("submit", function (e) {
    e.preventDefault();

    let form = this;
    let formData = new FormData(form);
    setActionLoaderState(true);
    setButtonLoadingState(addBulkSubmitBtn, true, 'Creating...');

    fetch("{{ route('admin.bulk-discount.store') }}", {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        }
    })
        .then(async res => {
            const text = await res.text();

            try {
                return JSON.parse(text);
            } catch (error) {
                console.error("HTML instead of JSON:", text);
                throw error;
            }
        })
        .then(data => {
            if (data.success) {
                let modalEl = document.getElementById('addBulkDiscountModal');
                let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();

                form.reset();
                form.classList.remove("was-validated");
                document.querySelector(".minQtyErr").innerText = "";
                document.querySelector(".discountErr").innerText = "";

                return reloadBulkTable();
            }

            if (data.errors) {
                form.classList.add("was-validated");

                document.querySelector(".minQtyErr").innerText =
                    data.errors.min_order_qty ? data.errors.min_order_qty[0] : "";

                document.querySelector(".discountErr").innerText =
                    data.errors.discount_percentage ? data.errors.discount_percentage[0] : "";
            }
        })
        .catch(err => console.error("Add bulk discount error:", err))
        .finally(() => {
            setActionLoaderState(false);
            setButtonLoadingState(addBulkSubmitBtn, false);
        });
});



/* =====================================================
   OPEN EDIT MODAL
===================================================== */
document.addEventListener("click", function (e) {
    const editBtn = e.target.closest(".edit");
    if (editBtn) {
        // Get values directly from button's data attributes
        const id = editBtn.dataset.id;
        const minQty = editBtn.dataset.minQty;
        const discount = editBtn.dataset.discount;

        // Fill modal fields
        document.getElementById("edit_bulk_id").value = id;
        document.getElementById("edit_min_qty").value = minQty;
        document.getElementById("edit_discount").value = discount;

        // Show modal
        new bootstrap.Modal(document.getElementById("editBulkModal")).show();
    }
});


/* =====================================================
  DELETE handling
===================================================== */
document.addEventListener("click", function (e) {
    if (e.target.closest(".delete")) {

        pendingBulkDiscountSeatId = e.target.closest("button").dataset.id;

        const modal = new bootstrap.Modal(deleteBulkDiscountSeatModalElement);
        modal.show();
    }
});

confirmDeleteBulkDiscountSeatBtn.addEventListener('click', function() {
    if (!pendingBulkDiscountSeatId) return;

    const confirmBtn = this;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    confirmBtn.disabled = true;
    setActionLoaderState(true);
    setTableLoaderState(true);

    fetch("{{ url('admin/bulk-discount') }}/" + pendingBulkDiscountSeatId, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        }
    })
        .then(res => res.json())
        .then(data => {
            const modalInstance = bootstrap.Modal.getInstance(deleteBulkDiscountSeatModalElement);
            if (modalInstance) {
                modalInstance.hide();
            }

            if (data.success) {
                return reloadBulkTable();
            }
        })
        .catch(err => console.error("Delete bulk discount error:", err))
        .finally(() => {
            confirmBtn.innerHTML = 'Delete';
            confirmBtn.disabled = false;
            pendingBulkDiscountSeatId = null;
            setActionLoaderState(false);
            setTableLoaderState(false);
        });
});


/* =====================================================
   SUBMIT EDIT FORM
===================================================== */
editBulkForm.addEventListener("submit", function (e) {
    e.preventDefault();

    let id = document.getElementById("edit_bulk_id").value;
    let formData = new FormData(this);
    formData.append("_method", "PUT");
    setActionLoaderState(true);
    setButtonLoadingState(editBulkSubmitBtn, true, 'Updating...');

    fetch("{{ url('admin/bulk-discount') }}/" + id, {
        method: "POST",
        body: formData,
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            // Close modal
            const modalEl = document.getElementById("editBulkModal");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();

            // FIX leftover backdrop issue
            setTimeout(() => {
                document.body.classList.remove("modal-open");
                document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
                modalEl.classList.remove("show");
                modalEl.style.display = "none";
            }, 300);

            return reloadBulkTable();

        } else if (data.errors) {
            document.querySelector(".editMinQtyErr").innerText = data.errors.min_order_qty ?? "";
            document.querySelector(".editDiscountErr").innerText = data.errors.discount_percentage ?? "";
        }
    })
    .catch(err => console.error("Edit bulk discount error:", err))
    .finally(() => {
        setActionLoaderState(false);
        setButtonLoadingState(editBulkSubmitBtn, false);
    });
});

reloadBulkTable();

</script>

@endsection
