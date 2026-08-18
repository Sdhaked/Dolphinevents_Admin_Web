@extends('layouts.admin')

@section('head')
    <title>Create Checker's Account</title>
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

            <h5 class="hd-lg">Update Checker's Account</h5>
            <form class="needs-validation" action="{{ route('admin.checkers.update', $checker->id) }}" method="POST" novalidate="">
                @csrf
                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Name -->
                    <div class="form-floating">
                        <input type="text" name="name" class="form-control" id="xname" value="{{ $checker->name }}" required>
                        <label for="xname">Name</label>
                    </div>

                    <!-- Email -->
                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="Emailx" value="{{ old('email', $checker->email) }}" required>
                        <label for="Emailx">Email</label>
                        @error('email')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Create Password-->
                    <div>
                        <div class="passBox">
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control" id="cpass" value="{{     $checker->plain_password }}" required/>
                                <label for="cpass">Create Password</label>
                            </div>
                            <button type="button" class="input-group-text pass-eye">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>
                    <button type="button" class="btn-md btn-sec" id="submitBtn">
                        Submit
                    </button>
                </div>
            </form>

        </main>
    </section>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            const submitBtn = document.getElementById('submitBtn');
            const submitUrl = '{{ route("admin.checkers.update", $checker->id) }}';
            const redirectUrl = '{{ route("admin.checkers.index") }}';
            let isSubmitting = false;
            
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (isSubmitting) {
                    return false;
                }
                
                // Validate fields
                const nameField = document.getElementById('xname');
                const emailField = document.getElementById('Emailx');
                const passwordField = document.getElementById('cpass');
                
                let isValid = true;
                
                if (!nameField.value.trim()) {
                    isValid = false;
                    nameField.classList.add('is-invalid');
                } else {
                    nameField.classList.remove('is-invalid');
                }
                
                if (!emailField.value.trim()) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                } else {
                    emailField.classList.remove('is-invalid');
                }
                
                if (!passwordField.value.trim()) {
                    isValid = false;
                    passwordField.classList.add('is-invalid');
                } else {
                    passwordField.classList.remove('is-invalid');
                }
                
                if (!isValid) {
                    return false;
                }
                
                // Show spinner and submit via AJAX
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Updating... <i class="fa-solid fa-circle-notch fa-spin"></i>';
                
                // Create FormData
                let formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                formData.append('name', nameField.value);
                formData.append('email', emailField.value);
                formData.append('password', passwordField.value);
                
                fetch(submitUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof createNotification === 'function') {
                            createNotification("success", data.message || "Checker updated successfully", "");
                        }
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        if (data.errors) {
                            if (data.errors.name) {
                                nameField.classList.add('is-invalid');
                                let errorDiv = nameField.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    nameField.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.name[0];
                            }
                            
                            if (data.errors.email) {
                                emailField.classList.add('is-invalid');
                                let errorDiv = emailField.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    emailField.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.email[0];
                            }
                            
                            if (data.errors.password) {
                                passwordField.classList.add('is-invalid');
                                let errorDiv = passwordField.parentNode.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    passwordField.parentNode.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.password[0];
                            }
                        }
                        
                        if (typeof createNotification === 'function') {
                            createNotification("error", data.message || "Please fix the errors and try again", "");
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof createNotification === 'function') {
                        createNotification("error", "An error occurred. Please try again.", "");
                    }
                })
                .finally(() => {
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit';
                });
            });
        });
    </script>
@endsection