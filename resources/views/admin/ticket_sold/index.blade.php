@extends('layouts.admin')

@section('head')
    <title>Ticket Sold</title>
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

            <h4 class="hd-lg">Ticket Sold</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span id="total-count">{{ $tickets->total() }}</span></h6>
                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div style="flex-grow: 1; max-width: 480px;">
                        <span class="search-base">Search By: Booking ID, Email, Customer, Mobile, Ticket Type, Associate</span>
                        <input type="search" id="search" class="form-control" placeholder="Search">
                    </div>

                    <div class="label-spc">
                        <label>Ticket Type</label>
                        <select class="form-select choose-option" id="ticket-type-filter">
                            <option value="all" selected="">All</option>
                            @foreach($ticketTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="label-spc">
                        <label>Associate Name</label>
                        <select class="form-select choose-option" id="associate-filter">
                            <option value="all" selected="">All</option>
                            @foreach($associates as $associate)
                                <option value="{{ $associate }}">{{ $associate }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex-grow: 1; max-width: 250px;">
                        <label>Date Filter</label>
                        <div class="i-holder">
                            <i class="fa-solid fa-calendar-days i-icon"></i>
                            <input type="date" class="form-control my-datepicker" id="date-filter">
                        </div>
                    </div>
                </div>

                <div class="">
                    <a href="{{ route('admin.ticket.sold.export') }}" id="download-btn" style="font-weight: 500; color:var(--color-status-success)" class="btn-sm p-0">
                        Download Ticket Sale Record<i class="fa-solid fa-download i-ml"></i>
                    </a>
                </div>

                <!-- Data Table -->
                <div id="ticketSoldTable">
                    @include('admin.ticket_sold._partials.table', ['tickets' => $tickets])
                </div>
            </div>

        </main>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="ticketDeleteModal" tabindex="-1" aria-labelledby="ticketDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0" id="ticketDeleteModalLabel">Delete Ticket</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="ticketDeleteModalMessage">Are you sure you want to move this ticket to trash?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="ticketDeleteConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search");
        const ticketTypeFilter = document.getElementById("ticket-type-filter");
        const associateFilter = document.getElementById("associate-filter");
        const dateFilter = document.getElementById("date-filter");
        const tableContainer = document.getElementById("ticketSoldTable");
        const totalCountSpan = document.getElementById("total-count");
        const deleteModalElement = document.getElementById("ticketDeleteModal");
        const deleteConfirmBtn = document.getElementById("ticketDeleteConfirmBtn");
        const bootstrap5ModalClass = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal : null;
        const deleteModal = (deleteModalElement && bootstrap5ModalClass) ? new bootstrap5ModalClass(deleteModalElement) : null;

        let currentPage = 1;
        let pendingDeleteUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        // fetch data
        function fetchData(page = 1) {
            const search = searchInput.value.trim();
            const ticketType = ticketTypeFilter.value;
            const associate = associateFilter.value;
            const date = dateFilter.value;
            currentPage = page;

            const params = new URLSearchParams();
            params.append('page', page);

            if (search) {
                params.append('search', search);
            }

            if (ticketType && ticketType !== 'all') {
                params.append('ticket_type', ticketType);
            }

            if (associate && associate !== 'all') {
                params.append('associate', associate);
            }

            if (date) {
                params.append('date', date);
            }

            fetch(`{{ route('admin.ticket.sold.index') }}?${params}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    updateTotalCount();
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                });
        }

        function updateTotalCount() {
            const search = searchInput.value.trim();
            const ticketType = ticketTypeFilter.value;
            const associate = associateFilter.value;
            const date = dateFilter.value;

            const params = new URLSearchParams();

            if (search) {
                params.append('search', search);
            }

            if (ticketType && ticketType !== 'all') {
                params.append('ticket_type', ticketType);
            }

            if (associate && associate !== 'all') {
                params.append('associate', associate);
            }

            if (date) {
                params.append('date', date);
            }

            fetch(`{{ route('admin.ticket.sold.index') }}?${params}&count_only=1`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (totalCountSpan && data.total !== undefined) {
                        totalCountSpan.textContent = data.total;
                    }
                })
                .catch(error => {
                    console.error("Error fetching count:", error);
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const debouncedSearch = debounce(() => fetchData(1), 600);

            if (searchInput) {
                searchInput.addEventListener('input', debouncedSearch);
            }

            // ticket type filter
            if (ticketTypeFilter) {
                ticketTypeFilter.addEventListener('change', function(e) {
                    fetchData(1);
                });
            }

            // associate filter
            if (associateFilter) {
                associateFilter.addEventListener('change', function(e) {
                    fetchData(1);
                });
            }

            // date filter
            if (dateFilter) {
                dateFilter.addEventListener('change', function(e) {
                    fetchData(1);
                });
            }

            // download button
            const downloadBtn = document.getElementById('download-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const search = searchInput.value.trim();
                    const ticketType = ticketTypeFilter.value;
                    const associate = associateFilter.value;
                    const date = dateFilter.value;

                    const params = new URLSearchParams();

                    if (search) {
                        params.append('search', search);
                    }

                    if (ticketType && ticketType !== 'all') {
                        params.append('ticket_type', ticketType);
                    }

                    if (associate && associate !== 'all') {
                        params.append('associate', associate);
                    }

                    if (date) {
                        params.append('date', date);
                    }

                    window.location.href = `{{ route('admin.ticket.sold.export') }}?${params}`;
                });
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
        });

        function openDeleteModal(url) {
            pendingDeleteUrl = url;
            if (deleteModal) {
                deleteModal.show();
                return;
            }
            if (window.$ && typeof window.$.fn.modal === 'function') {
                window.$(deleteModalElement).modal('show');
            }
        }

        function closeDeleteModal() {
            if (deleteModal) {
                deleteModal.hide();
                return;
            }
            if (window.$ && typeof window.$.fn.modal === 'function') {
                window.$(deleteModalElement).modal('hide');
            }
        }

        tableContainer.addEventListener("click", function(e) {
            const deleteBtn = e.target.closest(".action-btn.delete");
            if (!deleteBtn) return;

            const url = deleteBtn.getAttribute("data-url");
            if (!url) return console.error("Delete URL not found!");

            openDeleteModal(url);
        });

        deleteConfirmBtn?.addEventListener("click", function() {
            if (!pendingDeleteUrl) return;

            const originalText = deleteConfirmBtn.innerHTML;
            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Deleting...';

            fetch(pendingDeleteUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fetchData(currentPage);
                    closeDeleteModal();
                    createNotification("success", data.message || "Ticket deleted successfully", "");
                    pendingDeleteUrl = null;
                } else {
                    createNotification("error", data.message || "Failed to delete ticket", "");
                }
            })
            .catch(err => {
                console.error(err);
                createNotification("error", "Something went wrong while deleting!", "");
            })
            .finally(() => {
                deleteConfirmBtn.disabled = false;
                deleteConfirmBtn.innerHTML = originalText;
            });
        });
    </script>
@endsection
