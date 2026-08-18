@extends('layouts.admin')

@section('head')
    <title>Ticket Types</title>
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

            <h4 class="hd-lg">Ticket Types</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span>{{ $tickets->count() }}</span></h6>
                <!-- Data table Head -->
                <div class="dataTable-HD"><div>
                        {{-- Check if the event object exists and its type is 2 --}}
                        @if(isset($event) && $event->type == 2)
                            <a href="{{ route('admin.ticket.types.createSeats') }}" class="btn-sm btn-sec"> 
                                <i class="fa-solid fa-plus i-mr"></i> Create New
                            </a>
                        @else
                            <a href="{{ route('admin.ticket.types.create') }}" class="btn-sm btn-sec"> 
                                <i class="fa-solid fa-plus i-mr"></i> Create New
                            </a>
                        @endif
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" id="search" class="form-control" placeholder="Search">
                        <span class="search-base">Search By: Ticket Type</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="ticketTypesTable">
                    @include('admin.ticket_types._partials.table', ['tickets' => $tickets, 'event' => $event])
                </div>
            </div>

        </main>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTicketTypeModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">⚠️ Delete Ticket Type</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete <strong id="ticketTypeName"></strong>?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteTicketBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("ticketTypesTable");

        let currentPage = 1;

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

            fetch(`{{ route('admin.ticket.types.index') }}?page=${page}&search=${encodeURIComponent(search)}`, {
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
        });

        let deleteUrl = null;

        tableContainer.addEventListener("click", function(e) {
            const deleteBtn = e.target.closest(".action-btn.delete");
            if (!deleteBtn) return;

            deleteUrl = deleteBtn.getAttribute("data-url");
            const ticketTitle = deleteBtn.getAttribute("data-title") || "this ticket type";
            if (!deleteUrl) return console.error("Delete URL not found!");

            // Update modal content
            document.getElementById('ticketTypeName').textContent = ticketTitle;
            
            // Show modal
            // const modal = new bootstrap.Modal(document.getElementById('deleteTicketTypeModal'));
            // modal.show();
        });

        document.getElementById('confirmDeleteTicketBtn').addEventListener('click', function() {
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
                bootstrap.Modal.getInstance(document.getElementById('deleteTicketTypeModal')).hide();
                createNotification("success", "Ticket type deleted successfully", "");
                fetchData(currentPage);
                // Reset button
                confirmBtn.innerHTML = 'Delete';
                confirmBtn.disabled = false;
            })
            .catch(err => {
                console.error(err);
                createNotification("error", "Failed to delete ticket type", "");
                confirmBtn.innerHTML = 'Delete';
                confirmBtn.disabled = false;
            });
        });
    </script>
@endsection
