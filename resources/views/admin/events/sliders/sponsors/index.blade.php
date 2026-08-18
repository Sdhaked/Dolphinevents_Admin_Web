@extends('layouts.admin')

@section('head')
    <title>Event Sponsors - {{ $event->title }}</title>
    <meta name="description" content="Manage sponsors for {{ $event->title }}.">

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

            <div>
                <h4 class="hd-lg">Sponsors</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <button data-bs-toggle="modal" data-bs-target="#createSponsor" type="button"
                                class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add New
                        </button>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search"/>
                        <span class="search-base">Search By: Alt Text, URL</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="sponsorsTable">
                    @include('admin.events.sliders.sponsors._partials.table', ['sponsors' => $sponsors])
                </div>
            </div>
        </main>

        <!-- Create Modal -->
        <div class="modal fade" id="createSponsor" tabindex="-1" aria-labelledby="createSponsorLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Add Sponsor Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.sponsors.store') }}" class="needs-validation" novalidate enctype="multipart/form-data" method="POST">
                            @csrf
                            <!-- Upload Image -->
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x2">
                                    <span class="remove-img" style="display: none;"><i class="fa-solid fa-rectangle-xmark"></i></span>
                                </div>
                                <div class="mt-2">
                                    <input type="file" name="image" id="image" accept="image/*" class="d-none" required>
                                    <label for="image">
                                        <button type="button" class="btn-xs btn-sec-outline">
                                            <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                            Sponsor Image*
                                        </button>
                                    </label>
                                    <div class="invalid-feedback">
                                        Please Upload Image!!
                                    </div>
                                </div>
                            </div>

                            <!-- Alt Text -->
                            <div class="form-floating">
                                <input type="text" name="alt_text" class="form-control" id="altText"/>
                                <label for="altText">Alt Text</label>
                            </div>

                            <!-- Attach Link -->
                            <div class="form-floating">
                                <input type="url" name="url" class="form-control" id="sponsorUrl" required/>
                                <label for="sponsorUrl">Attach Link*</label>
                                <div class="invalid-feedback">
                                    Please enter a valid URL!
                                </div>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editSponsor" tabindex="-1" aria-labelledby="editSponsorLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Edit Sponsor Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="editSponsorForm" method="POST" action="" novalidate enctype="multipart/form-data">
                            @csrf
                            <!-- Upload Image -->
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img edit x2">
                                    <x-admin.media-remove label="event sponsor" />
                                </div>
                                <div class="mt-2">
                                    <input type="file" name="image" id="editImage" accept="image/*" class="d-none">
                                    <label for="editImage">
                                        <button type="button" class="btn-xs btn-sec-outline">
                                            <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                            Change Image
                                        </button>
                                    </label>
                                </div>
                            </div>

                            <!-- Alt Text -->
                            <div class="form-floating">
                                <input type="text" name="alt_text" class="form-control edit-alt-text" id="editAltText"/>
                                <label for="editAltText">Alt Text</label>
                            </div>

                            <!-- Attach Link -->
                            <div class="form-floating">
                                <input type="url" name="url" class="form-control edit-url" id="editSponsorUrl" required/>
                                <label for="editSponsorUrl">Attach Link*</label>
                                <div class="invalid-feedback">
                                    Please enter a valid URL!
                                </div>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="deleteEventSponsorModal" tabindex="-1" aria-labelledby="deleteEventSponsorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Delete Sponsor</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete this sponsor?</p>
                        <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteEventSponsorBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("sponsorsTable");
        const eventId = {{ $event->id }};
        const deleteEventSponsorModalElement = document.getElementById('deleteEventSponsorModal');
        const confirmDeleteEventSponsorBtn = document.getElementById('confirmDeleteEventSponsorBtn');

        let currentPage = 1;
        let deleteEventSponsorUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        // Modal reset functions
        function resetCreateModal() {
            const form = document.getElementById('createSponsorForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img:not(.edit)');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#createSponsor .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        function resetEditModal() {
            const form = document.getElementById('editSponsorForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img.edit');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#editSponsor .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        // fetch data
        function fetchData(page = 1) {
            const search = searchInput.value;
            currentPage = page;

            fetch(`/admin/events/sponsors?page=${page}&search=${encodeURIComponent(search)}`, {
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

        document.addEventListener("DOMContentLoaded", function () {
            const debouncedSearch = debounce(() => fetchData(1), 600);

            // search
            if (searchInput) {
                searchInput.addEventListener('input', debouncedSearch);
            }

            // handle pagination
            document.addEventListener("click", function (e) {
                const link = e.target.closest(".page-link-ajax");
                if (!link) return;

                e.preventDefault();
                const url = new URL(link.href);
                const page = url.searchParams.get("page") || 1;
                fetchData(page);
            });

            // Combined event handler for table container (delete, edit)
            tableContainer.addEventListener("click", function (e) {

                // Handle delete and edit buttons
                const deleteBtn = e.target.closest(".action-btn.delete");
                const editBtn = e.target.closest(".action-btn.edit");

                if (!deleteBtn && !editBtn) return;

                if (deleteBtn) {
                    deleteEventSponsorUrl = deleteBtn.getAttribute("data-url");
                    if (!deleteEventSponsorUrl) return console.error("Delete URL not found!");

                    const modal = new bootstrap.Modal(deleteEventSponsorModalElement);
                    modal.show();
                } else if (editBtn) {
                    const url = editBtn.getAttribute('data-url');
                    const image = editBtn.getAttribute('data-image');
                    const altText = editBtn.getAttribute('data-text');
                    const sponsorUrl = editBtn.getAttribute('data-link');
                    const mediaDeleteUrl = editBtn.getAttribute('data-media-delete-url');
                    const editRemoveBtn = document.querySelector('#editSponsor .js-media-remove');

                    document.querySelector('.preview.thumb-img.edit').setAttribute('src', image);
                    document.querySelector('.edit-alt-text').value = altText;
                    document.querySelector('.edit-url').value = sponsorUrl;
                    document.getElementById('editSponsorForm').setAttribute('action', url);

                    if (editRemoveBtn) {
                        editRemoveBtn.dataset.hasMedia = '1';
                        editRemoveBtn.dataset.deleteUrl = mediaDeleteUrl;
                        editRemoveBtn.style.display = 'inline-block';
                    }

                    const editModal = new bootstrap.Modal(document.getElementById('editSponsor'));
                    editModal.show();
                }
            });
        });

        confirmDeleteEventSponsorBtn.addEventListener('click', function() {
            if (!deleteEventSponsorUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteEventSponsorUrl, {
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
                    const modalInstance = bootstrap.Modal.getInstance(deleteEventSponsorModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {
                        createNotification("success", data.message || "Sponsor deleted successfully!", "");
                    } else {
                        createNotification("error", data.message || "Error deleting sponsor", "");
                    }
                    fetchData(currentPage);
                })
                .catch(err => {
                    console.error(err);
                    createNotification("error", "Something went wrong while deleting!", "");
                })
                .finally(() => {
                    confirmBtn.innerHTML = 'Delete';
                    confirmBtn.disabled = false;
                    deleteEventSponsorUrl = null;
                });
        });

        // edit sponsor
        const editSponsorForm = document.getElementById('editSponsorForm');
        editSponsorForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const editFormData = new FormData(editSponsorForm);
            const url = editSponsorForm.getAttribute('action');

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: editFormData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    createNotification("success", result.message || "Sponsor updated successfully!", "");
                    resetEditModal();
                    fetchData();

                    const modal = editSponsorForm.closest('.modal');
                    const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
                    if (dismissBtn) dismissBtn.click();
                } else {
                    createNotification("error", result.message || "Error updating sponsor", "");
                }
            } catch (error) {
                console.error("Update failed:", error);
                createNotification("error", "Error updating sponsor", "");
            }
        });


        // Modal event listeners for clean state
        const createModal = document.getElementById('createSponsor');
        const editModal = document.getElementById('editSponsor');

        if (createModal) {
            createModal.addEventListener('show.bs.modal', function () {
                resetCreateModal();
            });
        }

        if (editModal) {
            editModal.addEventListener('hidden.bs.modal', function () {
                resetEditModal();
            });
        }
    </script>
@endsection
