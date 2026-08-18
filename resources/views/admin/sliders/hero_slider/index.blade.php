@extends('layouts.admin')

@section('head')
    <title>Main Hero Image Slider</title>
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
                <h4 class="hd-lg">Home Image Slider</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <!--  data-bs-toggle="modal" data-bs-target="#createSlide" -->
                        <button onclick="openAddSlideModal()" type="button"
                                class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add New
                        </button>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search"/>
                        <span class="search-base">Search By: Attached Link</span>
                    </div>

                </div>

                <!-- Data Table -->
                <div id="slidesTable">
                    @include('admin.sliders.hero_slider._partials.table', ['slides' => $slides])
                </div>
            </div>
        </main>
            <!-- Unified Add/Edit Hero Slide Modal -->
    <div class="modal fade" id="heroSlideModal" tabindex="-1" aria-labelledby="heroSlideModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h6 class="hd-sm m-0 modal-title-text">Add Hero Slide</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="heroSlideForm" enctype="multipart/form-data">
                        @csrf

                        <!-- Upload IMg -->
                        <div class="upload-box">
                            <div class="previewBox">
                                <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img slide-preview x2">
                                <x-admin.media-remove class="removeImage d-none" label="hero slide" />
                            </div>
                            <div class="mt-2">
                                <input type="file" name="image" id="slideImage" accept="image/*" class="d-none">
                                <label for="slideImage">
                                    <button type="button" class="btn-xs btn-sec-outline">
                                        <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                        Hero Image*
                                    </button>
                                </label>
                            </div>
                            <div class="image-error text-danger small"></div>
                        </div>

                        <!-- Alt Text -->
                        <div class="form-floating">
                            <input type="text" name="alt_text" class="form-control" id="slideAlt">
                            <label for="slideAlt">Alt Text*</label>
                            <div class="alt-error text-danger small"></div>
                        </div>

                        <!-- Attach Link -->
                        <div class="form-floating">
                            <input type="url" name="url" class="form-control" id="slideUrl">
                            <label for="slideUrl">Attach Link</label>
                            <div class="url-error text-danger small"></div>
                        </div>

                        <button type="submit" class="btn-md btn-sec submit-btn">Create</button>

                    </form>
                </div>

            </div>
        </div>
    </div>

        <!-- Modal -->
        <!-- <div class="modal fade" id="createSlide" tabindex="-1" aria-labelledby="createSlideLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Add Hero Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="createSliderForm" novalidate enctype="multipart/form-data">
                            
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x2">
                                    <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                                </div>
                                <div class="mt-2">
                                    <input type="file" name="image" id="image" accept="image/*" class="d-none"
                                           required>
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

                            <div class="form-floating">
                                <input type="text" name="alt_text" class="form-control" id="dg8"/>
                                <label for="dg8">Alt Text*</label>
                            </div>

                            <div class="form-floating">
                                <input type="url" name="url" class="form-control" id="c-dfg53"/>
                                <label for="c-dfg53">Attach Link</label>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editSlide" tabindex="-1" aria-labelledby="editSlideLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Edit Hero Slide</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" id="editSliderForm" method="POST" action="" novalidate enctype="multipart/form-data">
                            @csrf
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img edit x2">
                                    <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                                </div>
                                <div class="mt-2">
                                    <input type="file" name="image" id="image" accept="image/*" class="d-none">
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

                            <div class="form-floating">
                                <input type="text" name="alt_text" class="form-control edit-alt-text" id="dg8"/>
                                <label for="dg8">Alt Text*</label>
                            </div>

                            <div class="form-floating">
                                <input type="url" name="url" class="form-control edit-attach-link" id="c-dfg53"/>
                                <label for="c-dfg53">Attach Link</label>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="modal fade" id="deleteHeroSlideModal" tabindex="-1" aria-labelledby="deleteHeroSlideModalLabel" aria-hidden="true">
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
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteHeroSlideBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const deleteHeroSlideModalElement = document.getElementById('deleteHeroSlideModal');
        const confirmDeleteHeroSlideBtn = document.getElementById('confirmDeleteHeroSlideBtn');
        let deleteHeroSlideUrl = null;

        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("slidesTable");

       // let currentPage = 1;
        let currentPage = parseInt(
                    document.querySelector("li.active span")?.innerText || 1
                );

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

            fetch(`{{ route('admin.sliders.hero.index') }}?page=${page}&search=${encodeURIComponent(search)}`, {
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

            window.openAddSlideModal = openAddSlideModal;
            window.openEditSlideModal = openEditSlideModal;

            let isEdit = false;
            let editId = null;

            // ✅ Open for ADD
            function openAddSlideModal() {
                isEdit = false;
                editId = null;

                $("#heroSlideForm")[0].reset();
                $(".modal-title-text").text("Add Hero Slide");
                $(".submit-btn").text("Create");

                $(".slide-preview").attr("src", "{{ asset('images/uploadimg.svg') }}");
                $(".removeImage")
                    .addClass("d-none")
                    .attr("data-has-media", "0")
                    .attr("data-delete-url", "");

                clearErrors();
                $("#heroSlideModal").modal("show");
            }

            // ✅ Open for EDIT
            function openEditSlideModal(slide, mediaDeleteUrl) {
                isEdit = true;
                editId = slide.id;

                $("#heroSlideForm")[0].reset();

                $(".modal-title-text").text("Edit Hero Slide");
                $(".submit-btn").text("Update");

                $("#slideAlt").val(slide.alt_text);
                $("#slideUrl").val(slide.url);

                $(".slide-preview").attr("src", "/storage/" + slide.image);
                $(".removeImage")
                    .removeClass("d-none")
                    .css("display", "inline-block")
                    .attr("data-has-media", "1")
                    .attr("data-delete-url", mediaDeleteUrl);

                clearErrors();
                $("#heroSlideModal").modal("show");
            }

            // ✅ Preview Image
            $("#slideImage").on("change", function (e) {
                let file = e.target.files[0];
                if (file) {
                    $(".slide-preview").attr("src", URL.createObjectURL(file));
                    $(".removeImage").removeClass("d-none");
                }
            });

            // ✅ Remove image preview
            // ✅ Clear Error Messages
            function clearErrors() {
                $(".image-error, .alt-error, .url-error").html("");
            }

            // ✅ Submit Form (AJAX)
            $("#heroSlideForm").on("submit", function (e) {
                e.preventDefault();
                clearErrors();

                let formData = new FormData(this);

                let url = isEdit
                    ? "/admin/sliders/hero/update/" + editId         // Update route
                    : "/admin/sliders/hero/store";                  // Create route
                const currentPage = parseInt(
                    document.querySelector("li.active span")?.innerText || 1
                );
                console.log('currentPage '+currentPage);
                formData.append('page', currentPage); 

                formData.append("_method", isEdit ? "POST" : "POST");

                $.ajax({
                    url: url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function (res) {
                        const currentPage = parseInt(
                            document.querySelector("li.active span")?.innerText || 1
                        );
                        //console.log(res);
                        $("#heroSlideModal").modal("hide");
                        createNotification(
                            "success",
                            res.title ?? "Hero Slider",
                            res.message ?? "Action completed successfully."
                        );
                        const pageToLoad = res.page ?? currentPage;
                        fetchData(pageToLoad);
                        //location.reload(); // or reload table only

                    },

                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            if (errors.image) $(".image-error").html(errors.image[0]);
                            if (errors.alt_text) $(".alt-error").html(errors.alt_text[0]);
                            if (errors.url) $(".url-error").html(errors.url[0]);
                        }
                    }
                });
            });


            // delete
            tableContainer.addEventListener("click", function (e) {

            const deleteBtn = e.target.closest(".action-btn.delete");
            if (!deleteBtn) return;

            deleteHeroSlideUrl = deleteBtn.getAttribute("data-url");
            if (!deleteHeroSlideUrl) return console.error("Delete URL not found!");

            const modal = new bootstrap.Modal(deleteHeroSlideModalElement);
            modal.show();
        });

        confirmDeleteHeroSlideBtn.addEventListener('click', function() {
            if (!deleteHeroSlideUrl) return;

            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;

            const currentPage = parseInt(
                document.querySelector("li.active span")?.innerText || 1
            );

            fetch(deleteHeroSlideUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ page: currentPage })
            })
                .then(res => res.json())
                .then(data => {
                    const modalInstance = bootstrap.Modal.getInstance(deleteHeroSlideModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {

                        // ✅ Server returns correct page → use it
                        const pageToLoad = data.page ?? currentPage;

                        fetchData(pageToLoad);

                        createNotification(
                            "success",
                            "Hero Slider",
                            "Slide deleted successfully."
                        );

                    } else {
                        createNotification(
                            "error",
                            "Hero Slider",
                            data.message ?? "Failed to delete slide."
                        );
                    }
                })
                .catch(err => {
                    console.error(err);
                    createNotification(
                        "error",
                        "Hero Slider",
                        "Failed to delete, something went wrong."
                    );
                })
                .finally(() => {
                    confirmBtn.innerHTML = 'Delete';
                    confirmBtn.disabled = false;
                    deleteHeroSlideUrl = null;
                });
        });

        });

        // create slider
        // const createSliderForm = document.getElementById('createSliderForm');
        // createSliderForm.addEventListener('submit', async function (e) {
        //     e.preventDefault();

        //     const formData = new FormData(createSliderForm);

        //     try {
        //         const response = await fetch("{{ route('admin.sliders.hero.store') }}", {
        //             method: "POST",
        //             headers: {
        //                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        //             },
        //             body: formData
        //         });

        //         const result = await response.json();

        //         if (response.ok && result.success) {
        //             createSliderForm.reset();
        //             fetchData();
        //             createNotification("success", "Hero Slider", " added successfully.");
        //             const modal = createSliderForm.closest('.modal');
        //             const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
        //             if (dismissBtn) dismissBtn.click();
        //         }
        //     } catch (error) {
        //         createNotification("error", "Hero Slider", " failed to add, something went wrong.");
        //         console.error("Upload failed:", error);
        //     }
        // });

        // //edit slider
        // const editSliderForm = document.getElementById('editSliderForm');
        // editSliderForm.addEventListener('submit', async function (e) {
        //     e.preventDefault();

        //     const editFormData = new FormData(editSliderForm);
        //     const url = editSliderForm.getAttribute('action');

        //     try {
        //         const response = await fetch(`${url}`, {
        //             method: "POST",
        //             headers: {
        //                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        //             },
        //             body: editFormData
        //         });

        //         const result = await response.json();

        //         if (response.ok && result.success) {
        //             editSliderForm.reset();
        //             fetchData();
                    
        //             createNotification("success", "Hero Slider", " updated successfully.");

        //             const modal = editSliderForm.closest('.modal');
        //             const dismissBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
        //             if (dismissBtn) dismissBtn.click();
        //         }
        //     } catch (error) {
        //         createNotification("error", "Hero Slider", " failed to update, something went wrong.");
        //         console.error("Update failed:", error);
        //     }
        // });
    </script>
@endsection
