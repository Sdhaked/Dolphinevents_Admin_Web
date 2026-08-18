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

            <h5 class="hd-lg">Create Ticket Type</h5>

            <form action="{{ route('admin.ticket.types.store') }}" method="POST" novalidate=""
                class="grid-1 gap-card needs-validation" enctype="multipart/form-data">
                @csrf
                
                <input type="hidden" name="event_id" value="{{ session('active_event_id') }}">
                <!-- Ticket Img -->
                <div class="label-spc upload-box">
                    <div class="previewBox mt-2">
                        <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                        <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x3">
                    </div>
                    <div class="mt-4">
                        <label for="uploadf">Upload Featured Image</label>
                        <input type="file" name="featured_image" class="form-control mt-1" accept="image/*" required>
                        <div class="label-spc mt-1">
                            <input type="text" name="featured_image_alt_text" class="form-control"
                                placeholder="Alt Text">
                        </div>
                    </div>
                </div>


                <!-- Title -->
                <div class="form-floating">
                    <input type="text" name="title" class="form-control" id="title" required>
                    <label for="title">Title</label>
                </div>

                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Total Tickets -->
                    <div class="form-floating">
                        <input type="number" name="total_tickets" class="form-control" id="totaltickets" required>
                        <label for="totaltickets">Total Tickets (QTY)*</label>
                    </div>

                    <!-- Ticket Price -->
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1">{{ \App\Models\Currency::symbolForEvent($event ?? null) }}</span>
                        <div class="form-floating flex-grow-1">
                            <input type="number" name="ticket_price" class="form-control" id="ticket_price" required="">
                            <label for="ticket_price">Ticket Price (Per Ticket)*</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <textarea name="description" class="form-control" style="height: 100px"></textarea>
                    <label>Discription</label>
                </div>

                @include('admin.ticket_types._partials.age-groups', ['ticket' => null])

                <div>
                    <button type="button" class="check-btn">
                        <input class="form-check-input disc-check" name="enable_bulk_discount" type="checkbox"
                            value="enable-ticket-discount" id="enable_bulk_discount">
                        <label for="enable_bulk_discount"> Enable Bulk Ticket Discount</label>
                    </button>
                </div>

                <!-- TAX -->
                <div class="style-box">
                    <h6 class="hd-sm mb-2">Tax</h6>

                    <!-- Enable /Discable -->
                    <button type="button" class="check-btn my-3">
                        <input class="form-check-input disc-check" name="enable_tax" type="checkbox" value="enable-tax"
                            id="enable-tax">
                        <label for="enable-tax"> Enable Tax</label>
                    </button>

                    <!-- Tax Fields -->
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Tax Label -->
                        <div class="form-floating">
                            <input type="text" name="tax_label" class="form-control" id="tax-lable">
                            <label for="tax-lable">Tax Lable</label>
                        </div>

                        <!-- Tax Value -->
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="tax_value" class="form-control" id="tax-value">
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
                        <input class="form-check-input disc-check" name="enable_extra_charges" type="checkbox"
                            value="enable-extra-charges" id="enable-extra-charges">
                        <label for="enable-extra-charges"> Enable Extra charges</label>
                    </button>

                    <!-- Extra charge Fields -->
                    <div class="grid-2 grid-sm-1 gap-card">
                        <!-- Extra charges Label-->
                        <div class="form-floating">
                            <input type="text" name="extra_charges_label" class="form-control"
                                id="extra-charges-label">
                            <label for="extra-charges-label">Extra Charge Lable</label>
                        </div>

                        <!-- Extra Charge Value -->
                        <div class="input-group mb-3">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="extra_charges_value" class="form-control"
                                    id="extra-charges-value">
                                <label for="extra-charges-value">Extra Charge Value</label>
                            </div>
                            <span class="input-group-text" id="extra-charges-value">%</span>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-md btn-sec">Submit</button>
                </div>

            <hr class="my-5 d-none">

            <div class="style-box d-none">
                <button type="button" class="btn-xs btn-sec" data-bs-toggle="modal" data-bs-target="#modalID" id="addBulkDiscountBtn"><i
                        class="fa-solid fa-plus i-mr"></i> Add Bulk
                    Discount Slab</button>
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
                       </tbody>
                    </table>
                </div>
             </div>
            
            </form>
        </main>
    </section>

    <!-- Modal Box 🚗-->
    <div class="modal fade" id="modalID" tabindex="-1" aria-labelledby="modalIDLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Add Bulk Ticket Discount</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <form id="createBulkDiscountForm" class="grid-1 gap-card discount-box"
                        method="POST" novalidate>
                        @csrf
                        <!-- Min Order Qty -->
                        <div class="form-floating">
                            <input type="number" name="min_order_qty" class="form-control min-order-qty"
                                id="createMinOrderQty" required>
                            <label for="createMinOrderQty">Min Order Qty*</label>
                            <div class="invalid-feedback minQtyErr"></div>
                        </div>

                        <!-- Discount (%) -->
                        <div class="input-group">
                            <div class="form-floating flex-grow-1">
                                <input type="number" name="discount_percentage" class="form-control discount-input"
                                    id="createDiscountPercentage" step="0.01" min="0" max="20" required>
                                <label for="createDiscountPercentage">Discount (%)*</label>
                                <div class="invalid-feedback discountErr"></div>
                            </div>
                            <span class="input-group-text">%</span>
                        </div>

                        <button type="submit" class="btn-md btn-sec">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBulkDiscountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="hd-sm m-0">Edit Bulk Discount</h6>
                <button type="button" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editBulkDiscountForm" novalidate>
                    <input type="hidden" id="editRowIndex">
                    <input type="hidden" id="edit_bulk_id">

                    <div class="form-floating mb-3">
                        <input type="number" id="edit_min_qty" class="form-control" required>
                        <label for="edit_min_qty">Min Order Qty*</label>
                        <div class="invalid-feedback editMinQtyErr"></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating flex-grow-1">
                            <input type="number" id="editDiscountPercentage" class="form-control" step="0.01" required>
                            <label for="editDiscountPercentage">Discount (%)*</label>
                            <div class="invalid-feedback editdiscountErr"></div>
                        </div>
                        <span class="input-group-text">%</span>
                    </div>

                    <button type="submit" class="btn-md btn-sec">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="deleteBulkDiscountRowModal" tabindex="-1" aria-labelledby="deleteBulkDiscountRowModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Discount</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this discount?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteBulkDiscountRowBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- script to add,edit and delete bulk discount -->

 <script>
const tbody = document.getElementById("bulkDiscountTableBody");
const deleteBulkDiscountRowModalElement = document.getElementById('deleteBulkDiscountRowModal');
const confirmDeleteBulkDiscountRowBtn = document.getElementById('confirmDeleteBulkDiscountRowBtn');
let pendingBulkDiscountRow = null;

/* -----------------------------------------
   CREATE — Add New Bulk Discount Row
------------------------------------------- */
document.getElementById("createBulkDiscountForm").addEventListener("submit", function(e) {
    e.preventDefault();

    // Check for validation errors
    const hasErrors = document.querySelectorAll('#createBulkDiscountForm .is-invalid').length > 0;
    
    if (!this.checkValidity() || hasErrors) {
        return;
    }

    let minQty = document.querySelector('.min-order-qty').value;
    let discount = document.querySelector('.discount-input').value;

    let newRow = `
        <tr>
            <td>
                <div class="data-label">S.No</div>
                <div class="sno"></div>
            </td>

            <td>
                <div class="data-label">Min Order Qty</div>
                <div class="minQty">${minQty}</div>
                <input type="hidden" class="minQtyInp" name="bulk_discount_qty[]" value="${minQty}">
            </td>

            <td>
                <div class="data-label">Discount (%)</div>
                <div class="discountVal">${discount}% off</div>
                <input type="hidden" class="discountValInp" name="bulk_discount_value[]" value="${discount}">
            </td>

            <td>
                <div class="data-label">Action</div>
                <div>
                    <div class="action-row">
                        <button class="action-btn edit editBtn" type="button">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>

                        <button class="action-btn delete deleteBtn">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    `;

    tbody.insertAdjacentHTML("beforeend", newRow);
    updateSerialNumbers();

    bootstrap.Modal.getInstance(document.getElementById('modalID')).hide();
    this.reset();
    // Remove any validation classes
    this.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
        el.classList.remove('is-valid', 'is-invalid');
    });
});


/* -----------------------------------------
   UPDATE S.NO
------------------------------------------- */
function updateSerialNumbers() {
    document.querySelectorAll("#bulkDiscountTableBody tr").forEach((row, index) => {
        row.querySelector(".sno").innerText = index + 1;
    });
}


/* -----------------------------------------
   DELETE ROW
------------------------------------------- */
tbody.addEventListener("click", function (e) {

    const deleteBtn = e.target.closest(".deleteBtn");
    if (!deleteBtn) return; // EXIT if the clicked button is NOT delete

    pendingBulkDiscountRow = deleteBtn.closest("tr");

    const modal = new bootstrap.Modal(deleteBulkDiscountRowModalElement);
    modal.show();
});



/* -----------------------------------------
   OPEN EDIT MODAL (Prefill Data)
------------------------------------------- */
tbody.addEventListener("click", function(e) {
    if (e.target.closest(".editBtn")) {
        let row = e.target.closest("tr");

        document.getElementById("editRowIndex").value = [...tbody.children].indexOf(row);

        document.getElementById("edit_min_qty").value =
            row.querySelector(".minQty").innerText.trim();
        
        document.getElementById("edit_bulk_id").value = [...tbody.children].indexOf(row);

        document.getElementById("editDiscountPercentage").value =
            row.querySelector(".discountVal").innerText.replace("% off", "").trim();

        new bootstrap.Modal(document.getElementById("editBulkDiscountModal")).show();
    }
});

/* -----------------------------------------
   SAVE UPDATED DATA BACK TO ROW
------------------------------------------- */
document.getElementById("editBulkDiscountForm").addEventListener("submit", function (e) {
    e.preventDefault();

    // Check for validation errors
    const hasErrors = document.querySelectorAll('#editBulkDiscountForm .is-invalid').length > 0;
    
    if (hasErrors) {
        return;
    }

    let index = document.getElementById("editRowIndex").value;
    let row = tbody.querySelectorAll("tr")[index];

    let newQty = document.getElementById("edit_min_qty").value;
    let newDiscount = document.getElementById("editDiscountPercentage").value;

    // Update visible values
    row.querySelector(".minQty").innerText = newQty;
    row.querySelector(".discountVal").innerText = newDiscount + "% off";

    // Update hidden input values
    row.querySelector(".minQtyInp").value = newQty;
    row.querySelector(".discountValInp").value = newDiscount;

    // Close Modal
    bootstrap.Modal.getInstance(document.getElementById('editBulkDiscountModal')).hide();
});

confirmDeleteBulkDiscountRowBtn.addEventListener('click', function() {
    if (!pendingBulkDiscountRow) return;

    pendingBulkDiscountRow.remove();
    updateSerialNumbers();

    const modalInstance = bootstrap.Modal.getInstance(deleteBulkDiscountRowModalElement);
    if (modalInstance) {
        modalInstance.hide();
    }

    pendingBulkDiscountRow = null;
});

</script>

@endsection
