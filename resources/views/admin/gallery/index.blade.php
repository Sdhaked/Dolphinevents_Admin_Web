@extends('layouts.admin')

@section('head')
    <title>Gallery</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')
    <link rel="stylesheet" href="{{ asset('style/gallery-system.css') }}">

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
    <script src="{{ asset('javascript/gallery.js') }}" defer></script>
@endsection

@section('body')
    <!-- PRELOADER -->
    @include('admin._partials.preloader')
    @include('admin._partials.preloader002')

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

                <div class="style-box mb-5">
                    <h5 class="hd-sm">Add gallery images</h5>

                    @if (session('success'))
                        <script>
                            window.addEventListener('load', function() {
                                createNotification("success", "{{ session('success') }}", "");
                            });
                        </script>
                    @endif

                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <script>
                                window.addEventListener('load', function() {
                                    createNotification("error", "{{ $error }}", "");
                                });
                            </script>
                        @endforeach
                    @endif

                    <form class="gallery-form" showat="#img_box" onsubmit="uploadImages(event)"
                        enctype="multipart/form-data">
                        <input type="file" multiple="" class="d-none" accept="image/jpeg, image/png, image/jpg, image/webp">
                        <div>
                            <button type="button" class="btn-sm btn-sec">
                                <i class="fa-regular fa-file-image"></i> Upload Images
                            </button>
                        </div>


                        <div class="place-imgs" id="img_box">
                            <div>
                                <h6 class="empty-hd"><i class="fa-regular fa-images"></i> <br>
                                    No Product Images are uploaded</h6>
                            </div>
                        </div>
                        <div class="gallery-submit-wrap" style="display: none;">
                            <button type="submit" class="btn-md btn-prim">Submit</button>
                        </div>
                    </form>
                </div>


                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search" />
                        <span class="search-base">Search By: Alt Text</span>
                    </div>
                    <button type="button" class="btn-sm danger-fill-btn" id="deleteAllGalleryBtn">
                        <i class="fa-solid fa-trash"></i> Delete All
                    </button>
                </div>

                <!-- Data Table -->
                <div id="galleryTable">
                    @include('admin.gallery._partials.table', ['galleries' => $galleries])
                </div>
            </div>
        </main>

        <!-- Modal -->
        <div class="modal fade" id="galleryji" tabindex="-1" aria-labelledby="galleryjiLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Edit Gallery Image</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="editGalleryForm" novalidate enctype="multipart/form-data">
                            @csrf

                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img edit x2"
                                        id="editPreviewImage">
                                    <x-admin.media-remove label="gallery image" />
                                </div>
                                <div class="mt-2">
                                    <input type="file" accept="image/*" class="d-none" id="editImageInput"
                                        name="image">
                                    <label for="editImageInput">
                                        <button type="button" class="btn-xs btn-sec-outline">
                                            <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                            Change Image
                                        </button>
                                    </label>
                                </div>
                            </div>

                            <!-- Alt Text -->
                            <div class="form-floating">
                                <input type="text" class="form-control edit-alt-text" id="editAltText" name="alt_text" />
                                <label for="editAltText">Alt Text (optional)</label>
                            </div>

                            <button type="submit" class="btn-md btn-sec" id="editGallerySubmitBtn">
                                <span class="btn-text">Update</span>
                                <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteGalleryModal" tabindex="-1" aria-labelledby="deleteGalleryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Delete Gallery Image</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete <strong id="galleryImageName"></strong>?</p>
                        <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteGalleryBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteAllGalleryModal" tabindex="-1" aria-labelledby="deleteAllGalleryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Delete All Gallery Images</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete all gallery images?</p>
                        <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteAllGalleryBtn">Delete All</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const galleryUploadBatchSize = 1;

        function getImageSources() {
            const imgBox = document.getElementById('img_box');
            const images = imgBox.querySelectorAll('img');
            const sources = [];

            images.forEach(img => {
                sources.push(img.src);
            });

            return sources;
        }

        async function getImageFiles() {
            const selectedImages = typeof imagesMap !== 'undefined' ? (imagesMap.get('#img_box') || []) : [];

            if (selectedImages.length > 0) {
                return selectedImages
                    .map(image => image.file)
                    .filter(Boolean);
            }

            const sources = getImageSources();
            const files = [];

            for (let i = 0; i < sources.length; i++) {
                const res = await fetch(sources[i]);
                const blob = await res.blob();
                const timestamp = new Date().getTime();
                const file = new File([blob], `image_${timestamp + i}.png`, {
                    type: blob.type
                });
                files.push(file);
            }

            return files;
        }

        async function submitGalleryImageBatch(files) {
            const formData = new FormData();

            files.forEach(file => {
                formData.append('images[]', file);
            });

            const response = await fetch("{{ route('admin.gallery.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(firstError || data.message || 'Failed to upload gallery images');
            }

            return data;
        }

        async function uploadImages(e) {
            e.preventDefault();
            const galleryForm = e.target;
            const submitButton = galleryForm.querySelector('button[type="submit"]');
            const originalSubmitText = submitButton ? submitButton.innerHTML : '';

            if (actionLoader) {
                actionLoader.style.display = 'flex';
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const files = await getImageFiles();

                if (!files.length) {
                    throw new Error('Please select at least one gallery image.');
                }

                let uploadedCount = 0;

                for (let i = 0; i < files.length; i += galleryUploadBatchSize) {
                    const batch = files.slice(i, i + galleryUploadBatchSize);

                    if (submitButton) {
                        submitButton.innerHTML = `Uploading ${Math.min(i + batch.length, files.length)} of ${files.length}...`;
                    }

                    await submitGalleryImageBatch(batch);
                    uploadedCount += batch.length;
                }

                if (uploadedCount > 0) {
                    const imageBox = document.getElementById('img_box');
                    const imageInput = galleryForm.querySelector('input[type="file"]');

                    galleryForm.reset();
                    if (imageInput) {
                        imageInput.value = '';
                    }
                    if (typeof imagesMap !== 'undefined') {
                        imagesMap.set('#img_box', []);
                    }
                    if (imageBox && typeof showImgFun === 'function') {
                        imageBox.innerHTML = showImgFun([], '#img_box');
                    }
                    const submitWrap = galleryForm.querySelector('.gallery-submit-wrap');
                    if (submitWrap) {
                        submitWrap.style.display = 'none';
                    }
                    createNotification("success", `${uploadedCount} image(s) uploaded successfully!`, "");
                    fetchData(1);
                }
            } catch (error) {
                console.error('Upload failed:', error);
                createNotification("error", error.message || "Failed to upload gallery images", "");
            } finally {
                if (submitButton) {
                    submitButton.innerHTML = originalSubmitText;
                    submitButton.disabled = false;
                }
                if (actionLoader) {
                    actionLoader.style.display = 'none';
                }
            }
        }

        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("galleryTable");
        const deleteGalleryModalElement = document.getElementById('deleteGalleryModal');
        const deleteAllGalleryModalElement = document.getElementById('deleteAllGalleryModal');
        const confirmDeleteGalleryBtn = document.getElementById('confirmDeleteGalleryBtn');
        const deleteAllGalleryBtn = document.getElementById('deleteAllGalleryBtn');
        const confirmDeleteAllGalleryBtn = document.getElementById('confirmDeleteAllGalleryBtn');
        const actionLoader = document.getElementById('actionLoader');

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

            fetch(`{{ route('admin.gallery.index') }}?page=${page}&search=${encodeURIComponent(search)}`, {
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
            fetchData();
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

            // delete or edit
            tableContainer.addEventListener("click", function (e) {
                const deleteBtn = e.target.closest(".action-btn.delete");
                const editBtn = e.target.closest(".action-btn.edit");

                if (!deleteBtn && !editBtn) return;

                if (deleteBtn) {
                    deleteUrl = deleteBtn.getAttribute("data-url");
                    const imageName = deleteBtn.getAttribute("data-alt") || "this image";
                    if (!deleteUrl) return console.error("Delete URL not found!");

                    document.getElementById('galleryImageName').textContent = imageName;
                } else if (editBtn) {
                    const url = editBtn.getAttribute('data-url');
                    const image = editBtn.getAttribute('data-image');
                    const alt_text = editBtn.getAttribute('data-text');
                    const mediaDeleteUrl = editBtn.getAttribute('data-media-delete-url');
                    const editRemoveBtn = document.querySelector('#galleryji .js-media-remove');

                    document.querySelector('.preview.thumb-img.edit').setAttribute('src', image);
                    document.querySelector('.edit-alt-text').value = alt_text;
                    document.getElementById('editGalleryForm').setAttribute('action', url);

                    if (editRemoveBtn) {
                        editRemoveBtn.dataset.hasMedia = '1';
                        editRemoveBtn.dataset.deleteUrl = mediaDeleteUrl;
                        editRemoveBtn.style.display = 'inline-block';
                    }
                }
            });
        });

        if (deleteAllGalleryBtn) {
            deleteAllGalleryBtn.addEventListener('click', function() {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(deleteAllGalleryModalElement);
                modalInstance.show();
            });
        }

        confirmDeleteGalleryBtn.addEventListener('click', function() {
            if (!deleteUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            fetch(deleteUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
            })
                .then(res => res.json())
                .then(data => {
                    const modalInstance = bootstrap.Modal.getInstance(deleteGalleryModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    createNotification("success", data.message || "Gallery image deleted successfully", "");
                    fetchData(currentPage);
                })
                .catch(err => {
                    console.error(err);
                    createNotification("error", "Failed to delete gallery image", "");
                })
                .finally(() => {
                    confirmBtn.innerHTML = 'Delete';
                    confirmBtn.disabled = false;
                    deleteUrl = null;
                });
        });

        if (confirmDeleteAllGalleryBtn) {
            confirmDeleteAllGalleryBtn.addEventListener('click', function() {
                const confirmBtn = this;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                confirmBtn.disabled = true;

                fetch("{{ route('admin.gallery.destroy_all') }}", {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                    },
                })
                    .then(res => res.json().then(data => ({
                        ok: res.ok,
                        data
                    })))
                    .then(({ ok, data }) => {
                        if (!ok || !data.success) {
                            throw new Error(data.message || "Failed to delete gallery images");
                        }

                        const modalInstance = bootstrap.Modal.getInstance(deleteAllGalleryModalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        createNotification("success", data.message || "Gallery images deleted successfully", "");
                        fetchData(1);
                    })
                    .catch(err => {
                        console.error(err);
                        createNotification("error", err.message || "Failed to delete gallery images", "");
                    })
                    .finally(() => {
                        confirmBtn.innerHTML = 'Delete All';
                        confirmBtn.disabled = false;
                    });
            });
        }

        // edit image
        const editGalleryForm = document.getElementById('editGalleryForm');
        const editGallerySubmitBtn = document.getElementById('editGallerySubmitBtn');
        editGalleryForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const editFormData = new FormData(editGalleryForm);
            const url = editGalleryForm.getAttribute('action');
            const btnText = editGallerySubmitBtn.querySelector('.btn-text');
            const spinner = editGallerySubmitBtn.querySelector('.spinner-border');

            editGallerySubmitBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'inline-block';

            try {
                const response = await fetch(`${url}`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: editFormData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    editGalleryForm.reset();
                    fetchData();
                    if (typeof createNotification === 'function') {
                        createNotification('success', result.message || 'Gallery image updated successfully', '');
                    }

                    const modal = editGalleryForm.closest('.modal');
                    const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
                    if (dismissBtn) dismissBtn.click();
                } else if (typeof createNotification === 'function') {
                    createNotification('error', result.message || 'Failed to update gallery image', '');
                }
            } catch (error) {
                console.error("Update failed:", error);
                if (typeof createNotification === 'function') {
                    createNotification('error', 'Something went wrong while updating the gallery image', '');
                }
            } finally {
                editGallerySubmitBtn.disabled = false;
                btnText.style.display = 'inline';
                spinner.style.display = 'none';
            }
        });
    </script>
@endsection
