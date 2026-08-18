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
                        <input type="file" multiple="" class="d-none" accept="image/jpeg, image/png, image/jpg">
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
    </section>

    <script>
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

        async function uploadImages(e) {
            e.preventDefault();
            if (actionLoader) {
                actionLoader.style.display = 'flex';
            }
            try {
                const files = await getImageFiles();
                const formData = new FormData();

                files.forEach((file, index) => {
                    formData.append('images[]', file);
                });

                // Send request to your controller route
                const response = await fetch("{{ route('admin.gallery.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const galleryForm = e.target;
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
                    fetchData();
                } else {
                    console.error('Upload failed:', data.message);
                }
            } finally {
                if (actionLoader) {
                    actionLoader.style.display = 'none';
                }
            }
        }

        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("galleryTable");
        const deleteGalleryModalElement = document.getElementById('deleteGalleryModal');
        const confirmDeleteGalleryBtn = document.getElementById('confirmDeleteGalleryBtn');
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
