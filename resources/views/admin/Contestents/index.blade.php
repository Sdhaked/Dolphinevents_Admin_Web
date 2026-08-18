@extends('layouts.admin')

@section('head')
    <title>Contestents - {{ $event->title }}</title>
    <meta name="description" content="Manage contestents for {{ $event->title }}.">

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
            @include('admin._partials.breadcrumb', ['breadcrumb_title' => 'Contestents'])

            <div>
                <!-- Voting Cards -->
                <div class="statsRow">
                    <div class="statCard info">
                        <h5><i class="fa-solid fa-crown yellow"></i> Winning Candidate</h5>
                        <div>
                            <div class="div-L">
                                <h4>{{ $winningContestent ? number_format($winningContestent->votes) : 0 }}</h4>
                                <p class="position-relative z-2">
                                    @if ($winningContestent)
                                        <a href="{{ route('admin.contestents.show', $winningContestent->id) }}" class="pe-auto text-break">
                                            <b>{{ $winningContestent->name }}</b>
                                        </a>
                                    @else
                                        <b>N/A</b>
                                    @endif
                                </p>
                                <p>{{ $winningContestent?->email ?? 'No votes yet' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="statCard info">
                        <h5>Total Candidates</h5>
                        <div>
                            <div class="div-L">
                                <h4>{{ number_format($totalContestents) }} <i class="fa-brands fa-angellist"></i></h4>
                                <p>Total number of candidates</p>
                            </div>
                        </div>
                    </div>

                    <div class="statCard success">
                        <img src="{{ asset('images/grapshbg/graph1.png') }}" alt="success">
                        <h5>Voting</h5>
                        <div>
                            <div class="div-L">
                                <h4>{{ number_format($totalVotes) }}</h4>
                                <p>Total voting done</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="hd-lg mt-5">Contestents</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <a href="{{ route('admin.contestents.create') }}" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add New
                        </a>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" id="search" placeholder="Search" />
                        <span class="search-base">Search By: Name, Email, Phone</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div id="contestentsTable">
                    @include('admin.Contestents._partials.table', ['contestents' => $contestents])
                </div>
            </div>
        </main>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteContestentModal" tabindex="-1" aria-labelledby="deleteContestentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0" id="deleteContestentModalLabel">Delete Contestent</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete <strong id="contestentName"></strong>?</p>
                        <p class="text-danger mb-0"><strong>This action cannot be undone.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteContestentBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const searchInput = document.getElementById("search");
        const tableContainer = document.getElementById("contestentsTable");
        const indexUrl = @json(route('admin.contestents.index'));
        const deleteModalElement = document.getElementById('deleteContestentModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteContestentBtn');

        let currentPage = 1;
        let deleteUrl = null;

        function debounce(callback, delay = 600) {
            let timeoutId;

            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback.apply(this, args), delay);
            };
        }

        function notify(type, message) {
            if (typeof createNotification === 'function') {
                createNotification(type, message, "");
            }
        }

        function fetchData(page = 1) {
            const search = searchInput.value;
            currentPage = page;

            fetch(`${indexUrl}?page=${page}&search=${encodeURIComponent(search)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                }
            })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => console.error("Error fetching data:", error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            const debouncedSearch = debounce(() => fetchData(1), 600);

            if (searchInput) {
                searchInput.addEventListener('input', debouncedSearch);
            }

            document.addEventListener("click", function(e) {
                const link = e.target.closest(".page-link-ajax");
                if (!link) return;

                e.preventDefault();
                const url = new URL(link.href);
                const page = url.searchParams.get("page") || 1;
                fetchData(page);
            });

            tableContainer.addEventListener("click", function(e) {
                const deleteBtn = e.target.closest(".action-btn.delete");
                if (!deleteBtn) return;

                deleteUrl = deleteBtn.getAttribute("data-url");
                const name = deleteBtn.getAttribute("data-name") || "this contestent";
                document.getElementById('contestentName').textContent = name;

                const modal = new bootstrap.Modal(deleteModalElement);
                modal.show();
            });
        });

        confirmDeleteBtn.addEventListener('click', function() {
            if (!deleteUrl) return;

            const button = this;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            button.disabled = true;

            fetch(deleteUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then(response => response.json())
                .then(data => {
                    const modalInstance = bootstrap.Modal.getInstance(deleteModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (data.success) {
                        notify("success", data.message || "Contestent deleted successfully.");
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        notify("error", data.message || "Failed to delete contestent.");
                    }
                })
                .catch(error => {
                    console.error(error);
                    notify("error", "Something went wrong while deleting.");
                })
                .finally(() => {
                    button.innerHTML = 'Delete';
                    button.disabled = false;
                    deleteUrl = null;
                });
        });
    </script>
@endsection
