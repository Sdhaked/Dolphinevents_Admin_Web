@extends('layouts.admin')

@section('head')
    <title>Create Ticket Type</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
    <script src="{{ asset('javascript/pages/create-ticket-type.js') }}" defer></script>
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

            <h5 class="hd-lg">Update Ticket Type</h5>
            <form action="{{ route('admin.ticket.types.update', $ticket->id) }}" method="POST" class="needs-validation" novalidate="" class="grid-1 gap-card" enctype="multipart/form-data">
                @csrf
                <!-- Ticket Img -->
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
                            <input type="text" value="{{ $ticket->featured_image_alt_text }}" name="featured_image_alt_text" class="form-control" placeholder="Alt Text">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="event_id" value="{{ session('active_event_id') }}">

                <!-- Title -->
                <div class="form-floating">
                    <input type="text" name="title" class="form-control" id="title" value="{{ $ticket->title }}" required>
                    <label for="title">Title</label>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Total Tickets -->
                    <div class="form-floating">
                        <input type="number" name="total_tickets" class="form-control" value="{{ $ticket->total_tickets }}" id="totaltickets" required>
                        <label for="totaltickets">Total Tickets (QTY)*</label>
                    </div>

                    <!-- Ticket Price -->
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1">{{ \App\Models\Currency::symbolForEvent($event ?? null) }}</span>
                        <div class="form-floating flex-grow-1">
                            <input type="number" name="ticket_price" value="{{ $ticket->ticket_price }}" class="form-control" id="ticket_price" required="">
                            <label for="ticket_price">Ticket Price (Per Ticket)*</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <textarea name="description" class="form-control" style="height: 100px">{{ $ticket->description }}</textarea>
                    <label>Discription</label>
                </div>

                <div>
                    <button type="button" class="check-btn">
                        <input class="form-check-input disc-check" name="enable_bulk_discount" type="checkbox" value="enable-ticket-discount"
                            id="enable_bulk_discount" {{ $ticket->enable_bulk_discount ? 'checked' : '' }}>
                        <label for="enable_bulk_discount"> Enable Bulk Ticket Discount</label>
                    </button>
                </div>

                <!-- TAX -->
                <div class="style-box">
                    <h6 class="hd-sm mb-2">Tax</h6>

                    <!-- Enable /Discable -->
                    <button type="button" class="check-btn my-3">
                        <input class="form-check-input disc-check" name="enable_tax" type="checkbox" value="enable-tax" id="enable-tax" {{ $ticket->enable_tax ? 'checked' : '' }}>
                        <label for="enable-tax"> Enable Tax</label>
                    </button>

                    <!-- Tax Fields -->
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Tax Label -->
                        <div class="form-floating">
                            <input type="text" name="tax_label" value="{{ $ticket->tax_label }}" class="form-control" id="tax-lable">
                            <label for="tax-lable">Tax Lable</label>
                        </div>

                        <!-- Tax Value -->
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="tax_value" value="{{ $ticket->tax_value }}" class="form-control" id="tax-value">
                                <label for="tax-value">Tax Value</label>
                            </div>
                            <span class="input-group-text" id="tax-value">%</span>
                        </div>
                    </div>
                </div>

                <!-- Extra charges -->
                <div class="style-box">
                    <h6 class="hd-sm mb-2">Extra charges</h6>

                    <!-- Enable /Discable -->
                    <button type="button" class="check-btn my-3">
                        <input class="form-check-input disc-check" name="enable_extra_charges" type="checkbox" value="enable-extra-charges"
                            id="enable-extra-charges" {{ $ticket->enable_extra_charges ? 'checked' : '' }}>
                        <label for="enable-extra-charges"> Enable Extra charges</label>
                    </button>

                    <!-- Extra charge Fields -->
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Extra charges Label-->
                        <div class="form-floating">
                            <input type="text" name="extra_charges_label" value="{{ $ticket->extra_charges_label }}" class="form-control" id="extra-charges-label">
                            <label for="extra-charges-label">Extra Charge Lable</label>
                        </div>

                        <!-- Extra Charge Value -->
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="extra_charges_value" value="{{ $ticket->extra_charges_value }}" class="form-control" id="extra-charges-value">
                                <label for="extra-charges-value">Extra Charge Value</label>
                            </div>
                            <span class="input-group-text" id="extra-charges-value">%</span>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec">Submit</button>
                </div>
            </form>

            <hr class="my-5">

            <div class="style-box">
                <button type="button" class="btn-xs btn-sec" data-bs-toggle="modal" data-bs-target="#modalID" id="addBulkDiscountBtn"><i
                        class="fa-solid fa-plus i-mr"></i> Add Bulk Discount Slab</button>
                <!-- Data Table -->
                <div class="table-responsive mt-4">
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
    <div class="modal fade" id="modalID" tabindex="-1" aria-labelledby="modalIDLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="m-0">Add Bulk Discount</h6>
                <button type="button" class="btn" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <form id="addBulkForm" class="grid-1 gap-card discount-box" novalidate>
                    @csrf
                    <input type="hidden" name="ticket_type_id" value="{{ $ticket->id }}">

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="minOrderQty" name="min_order_qty" required>
                        <label>Min Order Qty*</label>
                        <div class="invalid-feedback minQtyErr"></div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="discountPercentage" name="discount_percentage" step="0.01" required>
                        <label>Discount (%)*</label>
                        <div class="invalid-feedback discountErr"></div>
                    </div>

                    <button class="btn-md btn-sec w-100">Create</button>
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
                <form id="editBulkForm" novalidate>
                    @csrf

                    <input type="hidden" id="edit_bulk_id">

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="min_order_qty" id="edit_min_qty" required>
                        <label>Min Order Qty*</label>
                        <div class="invalid-feedback editMinQtyErr"></div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="discount_percentage" id="edit_discount" step="0.01" required>
                        <label>Discount (%)*</label>
                        <<div class="invalid-feedback editdiscountErr"></div>
                    </div>

                    <button class="btn-md btn-sec w-100">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="deleteBulkDiscountModal" tabindex="-1" aria-labelledby="deleteBulkDiscountModalLabel" aria-hidden="true">
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
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteBulkDiscountBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
<script>
const tableBody = document.getElementById("bulkDiscountTableBody");
let pendingBulkDiscountId = null;
const deleteBulkDiscountModalElement = document.getElementById('deleteBulkDiscountModal');
const confirmDeleteBulkDiscountBtn = document.getElementById('confirmDeleteBulkDiscountBtn');

// Build base URL with ticket_type_id
const baseUrl = new URL(`{{ route('admin.bulk-discount.index') }}?ticket_type_id={{ $ticket->id }}`);

/* =====================================================
   RELOAD TABLE PARTIAL
===================================================== */
function reloadBulkTable() {
    fetch(baseUrl.href)
        .then(res => res.json())
        .then(data => {
           // console.log('table reload triggered',data);
            tableBody.innerHTML = data.html;
            attachRowEvents();
        })
        .catch(err => console.error("Reload error:", err));
}

function attachRowEvents() {

    // Edit Buttons
    document.querySelectorAll(".action-btn.edit").forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("edit_min_qty").value = this.dataset.minQty;
            document.getElementById("edit_discount").value = this.dataset.discount;
            document.getElementById("edit_id").value = this.dataset.id;
        });
    });

    // Delete Buttons
    document.querySelectorAll(".action-btn.delete").forEach(btn => {
        btn.addEventListener("click", function () {
            pendingBulkDiscountId = this.dataset.id;

            const modal = new bootstrap.Modal(deleteBulkDiscountModalElement);
            modal.show();
        });
    });
}



/* =====================================================
   ADD BULK DISCOUNT
===================================================== */
document.getElementById("addBulkForm").addEventListener("submit", function (e) {
    e.preventDefault();

    // Check for validation errors
    const hasErrors = document.querySelectorAll('#addBulkForm .is-invalid').length > 0;
    
    if (!this.checkValidity() || hasErrors) {
        return;
    }

    let form = this;
    let formData = new FormData(form);

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
            console.log("", text);
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("HTML instead of JSON:", text);
            throw e;
        }
    })
    .then(data => {

        // SUCCESS RESPONSE
        if (data.success) {

            reloadBulkTable();

            let modalEl = document.getElementById('modalID');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();

            form.reset();
            // Remove any validation classes
            form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
            document.querySelector("#addminQtyErr").innerText = "";
            document.querySelector("#adddiscountErr").innerText = "";
            return;
        }

        // ERROR RESPONSE (VALIDATION)
        if (data.errors) {
            form.classList.add("was-validated");

            document.querySelector("#addminQtyErr").innerText =
                data.errors.min_order_qty ? data.errors.min_order_qty[0] : "";

            document.querySelector("#adddiscountErr").innerText =
                data.errors.discount_percentage ? data.errors.discount_percentage[0] : "";
        }

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

        pendingBulkDiscountId = e.target.closest("button").dataset.id;

        const modal = new bootstrap.Modal(deleteBulkDiscountModalElement);
        modal.show();
    }
});

confirmDeleteBulkDiscountBtn.addEventListener('click', function() {
    if (!pendingBulkDiscountId) return;

    const confirmBtn = this;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    confirmBtn.disabled = true;

    fetch("{{ url('admin/bulk-discount') }}/" + pendingBulkDiscountId, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        }
    })
        .then(res => res.json())
        .then(data => {
            const modalInstance = bootstrap.Modal.getInstance(deleteBulkDiscountModalElement);
            if (modalInstance) {
                modalInstance.hide();
            }

            reloadBulkTable();
            if (data.success) {
                reloadBulkTable();
            }
        })
        .finally(() => {
            confirmBtn.innerHTML = 'Delete';
            confirmBtn.disabled = false;
            pendingBulkDiscountId = null;
        });
});


/* =====================================================
   MODAL CLOSE CLEANUP
===================================================== */
document.getElementById('editBulkModal').addEventListener('hidden.bs.modal', function () {
    // Clean modal cleanup
    setTimeout(() => {
        document.body.classList.remove("modal-open");
        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
    }, 100);
});

document.getElementById('modalID').addEventListener('hidden.bs.modal', function () {
    // Clean modal cleanup
    setTimeout(() => {
        document.body.classList.remove("modal-open");
        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
    }, 100);
});

/* =====================================================
   SUBMIT EDIT FORM
===================================================== */
document.getElementById("editBulkForm").addEventListener("submit", function (e) {
    e.preventDefault();

    // Check for validation errors
    const hasErrors = document.querySelectorAll('#editBulkForm .is-invalid').length > 0;
    
    if (hasErrors) {
        return;
    }

    let id = document.getElementById("edit_bulk_id").value;
    let formData = new FormData(this);
    formData.append("_method", "PUT");

    fetch("{{ url('admin/bulk-discount') }}/" + id, {
        method: "POST",
        body: formData,
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            reloadBulkTable();  // Refresh table

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

        } else if (data.errors) {
            document.querySelector("#editMinQtyErr").innerText = data.errors.min_order_qty ?? "";
            document.querySelector("#editDiscountErr").innerText = data.errors.discount_percentage ?? "";
        }
    });
});


</script>

@endsection
