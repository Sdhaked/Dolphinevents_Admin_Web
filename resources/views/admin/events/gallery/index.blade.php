@extends('layouts.admin')

@section('head')
    <title>Gallery</title>
    <meta name="description" content="Manage information slider for {{ $event->title }}.">

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
                <h4 class="hd-lg">Gallery</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        @if($images->total() < 8)
                        <button data-bs-toggle="modal" data-bs-target="#galleryji" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add Image
                        </button>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fa-solid fa-info-circle"></i> Maximum 8 images allowed
                        </div>
                        @endif
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search" />
                        <span class="search-base">Search By: Alt Text</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="imagesTable">
                    @include('admin.events.gallery._partials.table', ['images' => $images])
                </div>
            </div>
        </main>

        <!-- Create Modal -->
        <div class="modal fade" id="galleryji" tabindex="-1" aria-labelledby="galleryjiLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Add Gallery Image</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.event.gallery.store') }}" class="needs-validation" novalidate enctype="multipart/form-data" method="POST">
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
                                            Category Image*
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
                                <label for="altText">Alt Text*</label>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editGalleryImage" tabindex="-1" aria-labelledby="editGalleryImageLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Edit Gallery Image</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="editImageForm" method="POST" action="" novalidate enctype="multipart/form-data">
                            @csrf
                            <!-- Upload Image -->
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img edit x2">
                                    <x-admin.media-remove label="event gallery image" />
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

                            <button type="submit" class="btn-md btn-sec">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="deleteEventGalleryImageModal" tabindex="-1" aria-labelledby="deleteEventGalleryImageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Delete Image</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete this image?</p>
                        <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteEventGalleryImageBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("imagesTable");
        const eventId = {{ $event->id }};
        const deleteEventGalleryImageModalElement = document.getElementById('deleteEventGalleryImageModal');
        const confirmDeleteEventGalleryImageBtn = document.getElementById('confirmDeleteEventGalleryImageBtn');

        let currentPage = 1;
        let deleteEventGalleryImageUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        // Modal reset functions
        function resetCreateModal() {
            const form = document.getElementById('galleryjiForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img:not(.edit)');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#galleryji .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        function resetEditModal() {
            const form = document.getElementById('editImageForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset image preview to default
            const preview = document.querySelector('.preview.thumb-img.edit');
            if (preview) {
                preview.src = "{{ asset('images/uploadimg.svg') }}";
            }

            // Hide remove button
            const removeBtn = document.querySelector('#editImage .remove-img');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }

        // fetch data
        function fetchData(page = 1) {
            const search = searchInput.value;
            currentPage = page;

            fetch(`/admin/events/gallery?page=${page}&search=${encodeURIComponent(search)}`, {
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
                    deleteEventGalleryImageUrl = deleteBtn.getAttribute("data-url");
                    if (!deleteEventGalleryImageUrl) return console.error("Delete URL not found!");

                    const modal = new bootstrap.Modal(deleteEventGalleryImageModalElement);
                    modal.show();
                } else if (editBtn) {
                    const url = editBtn.getAttribute('data-url');
                    const image = editBtn.getAttribute('data-image');
                    const alt_text = editBtn.getAttribute('data-text');
                    const mediaDeleteUrl = editBtn.getAttribute('data-media-delete-url');
                    const editRemoveBtn = document.querySelector('#editGalleryImage .js-media-remove');

                    document.querySelector('.preview.thumb-img.edit').setAttribute('src', image);
                    document.querySelector('.edit-alt-text').value = alt_text;
                    document.getElementById('editImageForm').setAttribute('action', url);

                    if (editRemoveBtn) {
                        editRemoveBtn.dataset.hasMedia = '1';
                        editRemoveBtn.dataset.deleteUrl = mediaDeleteUrl;
                        editRemoveBtn.style.display = 'inline-block';
                    }

                    const editModal = new bootstrap.Modal(document.getElementById('editGalleryImage'));
                    editModal.show();
                }
            });
        });

        // edit slide
        const editImageForm = document.getElementById('editImageForm');
        editImageForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const editFormData = new FormData(editImageForm);
            const url = editImageForm.getAttribute('action');

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
                    createNotification("success", result.message || "Image updated successfully!", "");
                    resetEditModal();
                    fetchData();

                    const modal = editImageForm.closest('.modal');
                    const bootstrapModal = bootstrap.Modal.getInstance(modal);
                    if (bootstrapModal) bootstrapModal.hide();
                } else {
                    createNotification("error", result.message || "Error updating image", "");
                }
            } catch (error) {
                console.error("Update failed:", error);
                createNotification("error", "Error updating image", "");
            }
        });

        // Modal event listeners for clean state
        const createModal = document.getElementById('galleryji');
        const editModal = document.getElementById('editGalleryImage');

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

        confirmDeleteEventGalleryImageBtn.addEventListener('click', function() {
            if (!deleteEventGalleryImageUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteEventGalleryImageUrl, {
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
                    const modalInstance = bootstrap.Modal.getInstance(deleteEventGalleryImageModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {
                        createNotification("success", data.message || "Image deleted successfully!", "");
                    } else {
                        createNotification("error", data.message || "Error deleting image", "");
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
                    deleteEventGalleryImageUrl = null;
                });
        });
    </script>
@endsection
