@extends('layouts.admin')

@section('head')
    <title>Information Slider</title>
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

            <div>
                <h4 class="hd-lg">Information Slider</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <button data-bs-toggle="modal" data-bs-target="#createSlide" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add New Slide
                        </button>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search" />
                        <span class="search-base">Search By: Alt Text, URL</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="slidesTable">
                    @include('admin.events.sliders.info_slider._partials.table', ['slides' => $slides])
                </div>
            </div>
        </main>

        <!-- Create Modal -->
        <div class="modal fade" id="createSlide" tabindex="-1" aria-labelledby="createSlideLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Add Info Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.event.sliders.info.store') }}" class="needs-validation" novalidate enctype="multipart/form-data" method="POST">
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
                                            Slide Image*
                                        </button>
                                    </label>
                                    <div class="invalid-feedback">
                                        Please Upload Image!!
                                    </div>
                                </div>
                            </div>

                            <!-- Alt Text -->
                            <div class="form-floating">
                                <input type="text" name="alt_text" class="form-control" id="altText" />
                                <label for="altText">Alt Text (optional)</label>
                            </div>

                            <!-- Attach Link -->
                            <div class="form-floating">
                                <input type="url" name="url" class="form-control" id="slideUrl" />
                                <label for="slideUrl">Attach Link</label>
                            </div>


                            <button type="submit" class="btn-md btn-sec">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editSlide" tabindex="-1" aria-labelledby="editSlideLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Edit Info Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="editSlideForm" method="POST" action="" novalidate enctype="multipart/form-data">
                            @csrf
                            <!-- Upload Image -->
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img edit x2">
                                    <x-admin.media-remove label="event info slide" />
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
                                <input type="text" name="alt_text" class="form-control edit-alt-text" id="editAltText" />
                                <label for="editAltText">Alt Text</label>
                            </div>

                            <!-- Attach Link -->
                            <div class="form-floating">
                                <input type="url" name="url" class="form-control edit-url" id="editSlideUrl" />
                                <label for="editSlideUrl">Attach Link</label>
                            </div>


                            <button type="submit" class="btn-md btn-sec">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="deleteEventInfoSlideModal" tabindex="-1" aria-labelledby="deleteEventInfoSlideModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Delete Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete this slide?</p>
                        <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteEventInfoSlideBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("slidesTable");
        const eventId = {{ $event->id }};
        const deleteEventInfoSlideModalElement = document.getElementById('deleteEventInfoSlideModal');
        const confirmDeleteEventInfoSlideBtn = document.getElementById('confirmDeleteEventInfoSlideBtn');

        let currentPage = 1;
        let deleteEventInfoSlideUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        // Modal reset functions
        function resetCreateModal() {
            const form = document.getElementById('createSlideForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img:not(.edit)');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#createSlide .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        function resetEditModal() {
            const form = document.getElementById('editSlideForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img.edit');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#editSlide .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        // fetch data
        function fetchData(page = 1) {
            const search = searchInput.value;
            currentPage = page;

            fetch(`/admin/events/sliders/info-slider?page=${page}&search=${encodeURIComponent(search)}`, {
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

            // handle pagination - revert to original data-page method
            tableContainer.addEventListener("click", function (e) {
                // Handle pagination clicks
                const paginationElement = e.target.closest(".pagination");
                if (paginationElement) {
                    const link = e.target.closest(".page-link-ajax");
                    if (link && link.hasAttribute('data-page')) {
                        e.preventDefault();
                        const page = link.getAttribute("data-page");
                        fetchData(page);
                        return;
                    }
                }
            });

            // Combined event handler for table container (delete, edit)
            tableContainer.addEventListener("click", function (e) {

                // Handle delete and edit buttons
                const deleteBtn = e.target.closest(".action-btn.delete");
                const editBtn = e.target.closest(".action-btn.edit");

                if (!deleteBtn && !editBtn) return;

                if (deleteBtn) {
                    deleteEventInfoSlideUrl = deleteBtn.getAttribute("data-url");
                    if (!deleteEventInfoSlideUrl) return console.error("Delete URL not found!");

                    const modal = new bootstrap.Modal(deleteEventInfoSlideModalElement);
                    modal.show();
                } else if (editBtn) {
                    const url = editBtn.getAttribute('data-url');
                    const image = editBtn.getAttribute('data-image');
                    const alt_text = editBtn.getAttribute('data-text');
                    const attach_url = editBtn.getAttribute('data-link');
                    const mediaDeleteUrl = editBtn.getAttribute('data-media-delete-url');
                    const editRemoveBtn = document.querySelector('#editSlide .js-media-remove');

                    document.querySelector('.preview.thumb-img.edit').setAttribute('src', image);
                    document.querySelector('.edit-alt-text').value = alt_text;
                    document.querySelector('.edit-url').value = attach_url;
                    document.getElementById('editSlideForm').setAttribute('action', url);

                    if (editRemoveBtn) {
                        editRemoveBtn.dataset.hasMedia = '1';
                        editRemoveBtn.dataset.deleteUrl = mediaDeleteUrl;
                        editRemoveBtn.style.display = 'inline-block';
                    }

                    const editModal = new bootstrap.Modal(document.getElementById('editSlide'));
                    editModal.show();
                }
            });
        });

        confirmDeleteEventInfoSlideBtn.addEventListener('click', function() {
            if (!deleteEventInfoSlideUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteEventInfoSlideUrl, {
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
                    const modalInstance = bootstrap.Modal.getInstance(deleteEventInfoSlideModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {
                        createNotification("success", data.message || "Slide deleted successfully!", "");
                    } else {
                        createNotification("error", data.message || "Error deleting slide", "");
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
                    deleteEventInfoSlideUrl = null;
                });
        });

        // edit slide
        const editSlideForm = document.getElementById('editSlideForm');
        editSlideForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const editFormData = new FormData(editSlideForm);
            const url = editSlideForm.getAttribute('action');

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
                    createNotification("success", result.message || "Slide updated successfully!", "");
                    resetEditModal();
                    fetchData();

                    const modal = editSlideForm.closest('.modal');
                    const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
                    if (dismissBtn) dismissBtn.click();
                } else {
                    createNotification("error", result.message || "Error updating slide", "");
                }
            } catch (error) {
                console.error("Update failed:", error);
                createNotification("error", "Error updating slide", "");
            }
        });

        // Modal event listeners for clean state
        const createModal = document.getElementById('createSlide');
        const editModal = document.getElementById('editSlide');

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
