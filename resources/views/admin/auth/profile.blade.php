@extends('layouts.admin')

@section('head')
    <title>Profile</title>
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

            <h4 class="hd-lg">My Profile <a href="{{ route('profile.edit') }}" class="text-prim"><i
                        class="fa-regular fa-pen-to-square"></i></a>
            </h4>

            <div class="table-responsive mt-4">
                <table class="table view-table">
                    <tbody>
                        <tr>
                            <th>Profile Pic
                            </th>
                            <td><img src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('images/profile.jpg') }}"
                                    class="thumb-img x2"></td>
                        </tr>
                        <tr>
                            <th>Id</th>
                            <td>#{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <th>Role</th>
                            <td class="text-capitalize">
                                @php
                                    $roleName = \App\Models\Role::whereKey($user->role)->value('name')
                                        ?? data_get(config('entities.user_types', []), $user->role, 'unknown');
                                @endphp
                                {{ $roleName }}
                            </td>
                        </tr>
                        <tr>
                            <th>Name
                            </th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th>Email Id
                            </th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>Mobile No
                            </th>
                            <td>{{ trim(($user->mobile_number_prefix ?? '') . ' ' . ($user->mobile_number ?? '')) ?: 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button class="btn-sm btn-sec-outline" data-bs-toggle="modal" data-bs-target="#resetpass">
                <i class="fa-solid fa-key i-mr"></i> Change Password
            </button>

            <!-- Modal -->
            <div class="modal fade" id="resetpass" tabindex="-1" aria-labelledby="resetpassLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="hd-sm m-0">change password</h6>
                            <button type="button" id="closePasswordModalBtn" data-bs-dismiss="modal" aria-label="Close"><i
                                    class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="modal-body">
                            <form class="needs-validation" id="changePasswordForm" novalidate>
                                @csrf
                                <!-- Old Password  -->
                                <div class="passBox">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="oldpass" name="old_password"
                                            required>
                                        <label for="oldpass">Old Password</label>
                                    </div>
                                    <button type="button" class="input-group-text pass-eye">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="text-danger small mt-1" id="error-old_password"
                                    style=" margin-top: -15px !important; "></div>

                                <!-- New Password  -->
                                <div class="passBox">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="newpass" name="new_password"
                                            required>
                                        <label for="newpass">New Password</label>
                                    </div>
                                    <button type="button" class="input-group-text pass-eye">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="text-danger small mt-1" id="error-new_password"
                                    style=" margin-top: -15px !important; "></div>

                                <!-- Conform Password  -->
                                <div class="passBox">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="chkpass"
                                            name="new_password_confirmation" required>
                                        <label for="chkpass">Conform Password</label>
                                    </div>
                                    <button type="button" class="input-group-text pass-eye">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="text-danger small mt-1" id="error-new_password_confirmation"
                                    style=" margin-top: -15px !important; "></div>

                                <button class="btn-sm btn-sec" id="updateBtn">Update</button>
                                <button class="btn-md btn-prim d-none" id="updateBtnLoader" type="button" disabled>
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    <span role="status">Loading...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Btn Sec -->
            <div class="d-flex justify-content-end my-5">
                <button class=" btn-sm btn-sec" onclick="window.history.back()">Back <i
                        class="fa-solid fa-right-to-bracket i-ml"></i></button>
            </div>
        </main>
    </section>

    <script>
        const updateBtn = document.getElementById('updateBtn');
        const loaderBtn = document.getElementById('updateBtnLoader');

        document.querySelector('#changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            document.querySelectorAll('[id^="error-"]').forEach(el => el.textContent = '');

            const oldPass = document.querySelector('#oldpass').value.trim();
            const newPass = document.querySelector('#newpass').value.trim();
            const chkPass = document.querySelector('#chkpass').value.trim();

            // Basic validation
            if (!oldPass || !newPass || !chkPass) {
                return createNotification("error", "All fields are required");
            }

            if (newPass !== chkPass) {
                document.getElementById('error-new_password_confirmation').textContent =
                    "Passwords do not match.";
                return;
            }

            const formData = new FormData(this);

            try {
                updateBtn.classList.add('d-none');
                loaderBtn.classList.remove('d-none');

                const res = await fetch('{{ route('profile.change.password') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const result = await res.json();

                loaderBtn.classList.add('d-none');
                updateBtn.classList.remove('d-none');

                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const errorDiv = document.getElementById(`error-${field}`);
                        if (errorDiv) {
                            errorDiv.textContent = result.errors[field][0];
                        } else {
                            // fallback: show as notification
                            createNotification('error', result.errors[field][0]);
                        }
                    });
                    return; // don't continue on validation failure
                }

                if (result.success) {
                    createNotification("success", result.message);
                    this.reset();
                    document.getElementById('closePasswordModalBtn')?.click();
                } else {
                    createNotification("error", result.message);
                }

            } catch (err) {
                console.error(err);
                createNotification("error", "Something went wrong, please try again.");

                loaderBtn.classList.add('d-none');
                updateBtn.classList.remove('d-none');
            }
        });
    </script>
@endsection
