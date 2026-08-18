@extends('layouts.admin')

@section('head')
    <title>Checkers</title>
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

    <!-- MAIN CONTENT -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <h4 class="hd-lg">All Checker's Accounts</h4>

            <div>
                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <a href="{{ route('admin.checkers.create') }}" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i> Create Checker
                        </a>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" id="search" name="search" class="form-control" placeholder="Search" />
                        <span class="search-base">Search By: Name, Email</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="checkerTable">
                    @include('admin.ticket_checker._partials.table', ['checkers' => $checkers])
                </div>
            </div>
        </main>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0" id="deleteModalLabel">Delete Checker</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete <strong id="checkerName"></strong>?</p>
                    <p class="text-danger mb-0"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("checkerTable");
        const deleteModalElement = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

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

            fetch(`{{ route('admin.checkers.index') }}?page=${page}&search=${encodeURIComponent(search)}`, {
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

            // delete
            tableContainer.addEventListener("click", function(e) {
                const deleteBtn = e.target.closest(".action-btn.delete");
                if (!deleteBtn) return;

                deleteUrl = deleteBtn.getAttribute("data-url");
                const checkerName = deleteBtn.getAttribute("data-name") || "this checker account";
                if (!deleteUrl) return console.error("Delete URL not found!");

                document.getElementById('checkerName').textContent = checkerName;
            });

            // Handle modal delete confirmation
            confirmDeleteBtn.addEventListener('click', function() {
                if (!deleteUrl) return;

                const deleteBtn = this;
                deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                deleteBtn.disabled = true;

                fetch(deleteUrl, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        const modalInstance = bootstrap.Modal.getInstance(deleteModalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        if (data.success) {
                            if (typeof createNotification === 'function') {
                                createNotification("success", data.message || "Checker deleted successfully", "");
                            }
                        } else {
                            if (typeof createNotification === 'function') {
                                createNotification("error", data.message || "Failed to delete checker", "");
                            }
                        }

                        fetchData(currentPage);
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof createNotification === 'function') {
                            createNotification("error", "Something went wrong while deleting!", "");
                        }
                    })
                    .finally(() => {
                        deleteBtn.innerHTML = 'Delete';
                        deleteBtn.disabled = false;
                        deleteUrl = null;
                    });
            });
        });
    </script>
@endsection
