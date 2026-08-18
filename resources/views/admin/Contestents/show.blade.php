@extends('layouts.admin')

@section('head')
    <title>Contestent Details</title>
    <meta name="description" content="Contestent account details.">

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
            @include('admin._partials.breadcrumb', ['breadcrumb_title' => 'Contestent Details'])

            @php
                $imageUrl = $contestent->image ? asset('storage/' . $contestent->image) : asset('images/no-img.png');
                $socialLinks = $contestent->social_links ?? [];
            @endphp

            <div class="HDandP">
                <h4 class="hd-lg"><span>{{ $contestent->name }}</span> Account Details</h4>
                <p><i class="fa-solid fa-arrow-right-long i-mr"></i> Contestent Account</p>
            </div>

            <div class="mb-3">
                <img src="{{ $imageUrl }}" class="thumb-img x2" alt="{{ $contestent->name }}">
            </div>

            <div class="table-responsive">
                <table class="table view-table">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <td>#{{ $contestent->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $contestent->name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td class="text-break">{{ $contestent->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $contestent->full_phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Social Links</th>
                            <td>
                                @if (!empty($socialLinks))
                                    <div class="d-flex gap-3 align-items-center flex-wrap">
                                        @foreach ($socialLinks as $link)
                                            @php
                                                $social = config('entities.social_options.' . ($link['platform'] ?? ''));
                                            @endphp
                                            @if ($social && !empty($link['url']))
                                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $social['label'] }}">
                                                    <i class="{{ $social['icon'] }}"></i>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $contestent->created_at?->format('d M, Y \A\t h:i A') ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Votes Gained</th>
                            <td>{{ number_format($contestent->votes) }}</td>
                        </tr>
                        <tr>
                            <th>Operations</th>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.contestents.edit', $contestent->id) }}" class="action-btn edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.contestents.destroy', $contestent->id) }}" method="POST" id="deleteContestentForm">
                                        @csrf
                                        @method('delete')
                                        <button class="action-btn delete" type="button" id="openDeleteContestentModal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="style-box">
                <h4 class="hd-lg">Voters List</h4>

                <div class="table-responsive mt-4">
                    <table class="table mob-view">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Voted On</th>
                                <th>Booking Id</th>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($voters as $index => $voter)
                                <tr>
                                    <td>
                                        <div class="data-label">S.No</div>
                                        <div>{{ $index + 1 }}</div>
                                    </td>
                                    <td>
                                        <div class="data-label">Voted On</div>
                                        <div>{{ $voter->created_at?->format('d M, Y \A\t h:i A') ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="data-label">Booking Id</div>
                                        <div class="text-break">{{ $voter->booking_id ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="data-label">Name</div>
                                        <div>{{ $voter->name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="data-label">Email</div>
                                        <div class="text-break">{{ $voter->email ?? 'N/A' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="text-center">No voters found!</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end my-5">
                <button class="btn-sm btn-sec" onclick="window.history.back()">Back <i class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>
        </main>
    </section>

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
                    <p class="mb-2">Are you sure you want to delete this contestent?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteContestentBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const deleteContestentForm = document.getElementById('deleteContestentForm');
        const openDeleteContestentModal = document.getElementById('openDeleteContestentModal');
        const deleteContestentModalElement = document.getElementById('deleteContestentModal');
        const confirmDeleteContestentBtn = document.getElementById('confirmDeleteContestentBtn');

        openDeleteContestentModal?.addEventListener('click', function() {
            const modal = new bootstrap.Modal(deleteContestentModalElement);
            modal.show();
        });

        confirmDeleteContestentBtn?.addEventListener('click', function() {
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            this.disabled = true;
            deleteContestentForm.submit();
        });
    </script>
@endsection
