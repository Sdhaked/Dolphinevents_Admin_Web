@extends('layouts.admin')

@section('head')
    <title>Ticket Failed</title>
    <meta name="description" content="Ticket failed and pending verification records.">

    @include('admin._partials.head.g-links')
    @include('admin._partials.head.g-css-files')
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @include('admin._partials.preloader')
    @include('admin._partials.sidebar')
    @include('admin._partials.header')

    <section class="wrapper">
        <main class="dash-content">
            @include('admin._partials.breadcrumb')

            <h4 class="hd-lg">Ticket Failed</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span id="total-count">{{ $tickets->total() }}</span></h6>
                <div class="dataTable-HD">
                    <div style="flex-grow: 1; max-width: 480px;">
                        <span class="search-base">Search By: Booking ID</span>
                        <input type="search" id="search" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div id="ticketFailedTable">
                    @include('admin.ticket_failed._partials.table', ['tickets' => $tickets])
                </div>
            </div>
        </main>
    </section>

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
        const tableContainer = document.getElementById("ticketFailedTable");
        const totalCountSpan = document.getElementById("total-count");
        const deleteModalElement = document.getElementById("ticketDeleteModal");
        const deleteConfirmBtn = document.getElementById("ticketDeleteConfirmBtn");
        const deleteModal = deleteModalElement && window.bootstrap?.Modal ? new bootstrap.Modal(deleteModalElement) : null;

        let currentPage = 1;
        let pendingDeleteUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        function buildParams(page = 1) {
            const params = new URLSearchParams();
            const search = searchInput.value.trim();
            params.append('page', page);
            if (search) params.append('search', search);
            return params;
        }

        function fetchData(page = 1) {
            currentPage = page;

            fetch(`{{ route('admin.ticket.failed.index') }}?${buildParams(page)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                }
            })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    updateTotalCount();
                })
                .catch(error => console.error("Error fetching data:", error));
        }

        function updateTotalCount() {
            const params = buildParams(currentPage);
            params.set('count_only', '1');

            fetch(`{{ route('admin.ticket.failed.index') }}?${params}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (totalCountSpan && data.total !== undefined) totalCountSpan.textContent = data.total;
                })
                .catch(error => console.error("Error fetching count:", error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            searchInput?.addEventListener('input', debounce(() => fetchData(1), 600));

            document.addEventListener("click", function(e) {
                const link = e.target.closest(".page-link-ajax");
                if (!link) return;

                e.preventDefault();
                const url = new URL(link.href);
                fetchData(url.searchParams.get("page") || 1);
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
            if (url) openDeleteModal(url);
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
