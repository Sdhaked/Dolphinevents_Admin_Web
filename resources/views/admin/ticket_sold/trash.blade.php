@extends('layouts.admin')

@php
    $permissionTablesReady = \Illuminate\Support\Facades\Schema::hasTable('permissions')
        && \Illuminate\Support\Facades\Schema::hasTable('role_permissions');
    $ticketSoldPermissionSlugs = collect();
    $authUser = auth()->user();

    if ($permissionTablesReady && $authUser?->role) {
        $ticketSoldPermissionSlugs = \App\Models\Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $authUser->role)
            ->pluck('permissions.slug');
    }

    $hasTicketSoldPermission = function (array $permissions) use ($ticketSoldPermissionSlugs) {
        return $ticketSoldPermissionSlugs->intersect($permissions)->isNotEmpty();
    };

    $canEmptyTicketSoldTrash = $hasTicketSoldPermission([
        'ticket-sold-manage-ticket-sold-trash',
        'ticket-sold-empty-ticket-sold-trash',
    ]);
@endphp

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

            <h4 class="hd-lg">Ticket Sold Trash</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span id="total-count">{{ $tickets->total() }}</span></h6>
                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div style="flex-grow: 1; max-width: 480px;">
                        <span class="search-base">Search By: Booking ID, Email, Customer, Mobile, Ticket Type, Associate</span>
                        <input type="search" id="search" class="form-control" placeholder="Search">
                    </div>

                </div>

                @if($canEmptyTicketSoldTrash)
                    <button type="button" class="btn-xs bulk-delete-btn "><i class="fa-regular fa-trash-can me-1"></i> Empty
                        Trash</button>
                @endif

                <!-- Data Table -->
                <div id="ticketSoldTable">
                    @include('admin.ticket_sold._partials.table', ['tickets' => $tickets])
                </div>
            </div>

        </main>
    </section>

    <!-- Action Confirmation Modal -->
    <div class="modal fade" id="ticketActionModal" tabindex="-1" aria-labelledby="ticketActionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0" id="ticketActionModalLabel">Confirm Action</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="ticketActionModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="ticketActionConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("ticketSoldTable");
        const totalCountSpan = document.getElementById("total-count");
        const actionModalElement = document.getElementById("ticketActionModal");
        const actionModalLabel = document.getElementById("ticketActionModalLabel");
        const actionModalMessage = document.getElementById("ticketActionModalMessage");
        const actionConfirmBtn = document.getElementById("ticketActionConfirmBtn");
        const bootstrap5ModalClass = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal : null;
        const actionModal = (actionModalElement && bootstrap5ModalClass) ? new bootstrap5ModalClass(actionModalElement) : null;

        let currentPage = 1;
        let pendingActionConfig = null;

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
            currentPage = page;

            const params = new URLSearchParams();
            params.append('page', page);

            if (search) {
                params.append('search', search);
            }

            fetch(`{{ route('admin.ticket.sold.trash') }}?${params}`, {
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
            const params = new URLSearchParams();

            if (search) {
                params.append('search', search);
            }

            fetch(`{{ route('admin.ticket.sold.trash') }}?${params}&count_only=1`, {
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

        function openActionModal(config) {
            pendingActionConfig = config;
            actionModalLabel.textContent = config.title || 'Confirm Action';
            actionModalMessage.textContent = config.message || 'Are you sure?';
            actionConfirmBtn.className = `btn-sm ${config.confirmBtnClass || 'danger-fill-btn'}`;
            actionConfirmBtn.textContent = config.confirmBtnText || 'Confirm';

            if (actionModal) {
                actionModal.show();
                return;
            }

            if (window.$ && typeof window.$.fn.modal === 'function') {
                window.$(actionModalElement).modal('show');
            }
        }

        function executePendingAction() {
            if (!pendingActionConfig) return;

            const config = pendingActionConfig;
            const originalText = actionConfirmBtn.innerHTML;
            actionConfirmBtn.disabled = true;
            actionConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Processing...';

            fetch(config.url, {
                method: config.method || 'DELETE',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                   
                    if (actionModal) {
                        actionModal.hide();
                    } else if (window.$ && typeof window.$.fn.modal === 'function') {
                        window.$(actionModalElement).modal('hide');
                    }
                    pendingActionConfig = null;

                    if (typeof config.onSuccess === 'function') {
                        config.onSuccess();
                    } else {
                        fetchData(currentPage);
                    }
                    createNotification("success", data.message || config.successMsg || "Action completed", "");
                } else {
                    createNotification("error", data.message || "Action failed", "");
                }
            })
            .catch(err => {
                console.error(err);
                createNotification("error", "Network error or server failure", "");
            })
            .finally(() => {
                actionConfirmBtn.disabled = false;
                actionConfirmBtn.innerHTML = originalText;
            });
        }

        actionConfirmBtn?.addEventListener('click', executePendingAction);

        // Handle trash/restore/permanent delete
        tableContainer.addEventListener("click", function(e) {
            // 1. Identify which action button was clicked
            const btn = e.target.closest(".action-btn");
            if (!btn) return;

            const url = btn.getAttribute("data-url");
            if (!url) return;

            let config = {
                title: "",
                message: "",
                method: "DELETE",
                successMsg: "",
                confirmBtnText: "Confirm",
                confirmBtnClass: "danger-fill-btn"
            };

            // 2. Configure request based on button class
            if (btn.classList.contains("soft-delete-btn")) {
                config.title = "Move To Trash";
                config.message = "Are you sure you want to move this ticket to trash?";
                config.successMsg = "Ticket moved to trash successfully";
            } 
            else if (btn.classList.contains("permanent-delete-btn")) {
                config.title = "Permanent Delete";
                config.message = "WARNING: This will permanently delete the ticket. This cannot be undone!";
                config.successMsg = "Ticket permanently deleted";
                config.confirmBtnText = "Delete Permanently";
            } 
            else if (btn.classList.contains("restore-btn")) {
                config.title = "Restore Ticket";
                config.message = "Do you want to restore this ticket to the active list?";
                config.method = "POST"; // Restore usually uses POST
                config.successMsg = "Ticket restored successfully";
                config.confirmBtnClass = "btn-prim";
                config.confirmBtnText = "Restore";
            } 
            else {
                return; // Ignore other buttons like 'View'
            }

            config.url = url;
            openActionModal(config);
        });

        document.querySelector(".bulk-delete-btn")?.addEventListener("click", function() {
            const url = "{{ route('admin.ticket.sold.empty_trash') }}";
            openActionModal({
                title: "Empty Trash",
                message: "Are you sure you want to permanently delete all tickets in the trash? This cannot be undone.",
                method: "DELETE",
                url: url,
                successMsg: "Trash cleared successfully.",
                confirmBtnText: "Empty Trash",
                confirmBtnClass: "danger-fill-btn",
                onSuccess: function() {
                    location.reload();
                }
            });
        });
    </script>
@endsection
