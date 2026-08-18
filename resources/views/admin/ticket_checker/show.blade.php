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

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <div class="HDandP">
                <h4 class="hd-lg"><span>{{ $checker->name }}</span> Account Details</h4>
                <p><i class="fa-solid fa-arrow-right-long i-mr"></i> Checker Account</p>
            </div>

            <div class="table-responsive">
                <table class="table view-table">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <td>#{{ $checker->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $checker->name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $checker->email }}</td>
                        </tr>
                        <tr>
                            <th>Password</th>
                            <td>
                                <div>
                                    <div class="passBox">
                                        <div>
                                            <input type="password" class="form-control"
                                                value="{{ $checker->plain_password }}" readonly>
                                        </div>
                                        <button type="button" class="input-group-text pass-eye">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td>
                                @if($checker->creator)
                                    <div style="font-weight: 500;">{{ $checker->creator->name }}</div>
                                    <div style="font-size: 0.85em; margin-top: 2px;" class="text-break">{{ $checker->creator->email }}</div>
                                @else
                                    <div>N/A</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Operations</th>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.checkers.edit', $checker->id) }}" class="action-btn edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.checkers.destroy', $checker->id) }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <button class="action-btn delete" type="button" id="openDeleteCheckerModal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Previous Pg Btn -->
            <div class="d-flex justify-content-end my-5">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>

        </main>
    </section>

    <div class="modal fade" id="deleteCheckerModal" tabindex="-1" aria-labelledby="deleteCheckerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Delete Checker</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this checker?</p>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs danger-fill-btn" id="confirmDeleteCheckerBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const deleteCheckerForm = document.querySelector('form[action="{{ route('admin.checkers.destroy', $checker->id) }}"]');
        const openDeleteCheckerModal = document.getElementById('openDeleteCheckerModal');
        const deleteCheckerModalElement = document.getElementById('deleteCheckerModal');
        const confirmDeleteCheckerBtn = document.getElementById('confirmDeleteCheckerBtn');

        openDeleteCheckerModal?.addEventListener('click', function() {
            const modal = new bootstrap.Modal(deleteCheckerModalElement);
            modal.show();
        });

        confirmDeleteCheckerBtn?.addEventListener('click', function() {
            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            confirmBtn.disabled = true;
            deleteCheckerForm.submit();
        });
    </script>
@endsection
