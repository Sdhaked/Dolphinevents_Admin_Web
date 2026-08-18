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

            <h5 class="hd-lg">Create Checker's Account</h5>
            <form method="POST" novalidate="">
                @csrf
                <div class="grid-2 grid-sm-1 gap-card">
                    <!-- Name -->
                    <div class="form-floating">
                        <input type="text" name="name" class="form-control" id="xname" value="{{ old('name') }}" required>
                        <label for="xname">Name</label>
                    </div>

                    <!-- Email -->
                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="Emailx" value="{{ old('email') }}" required>
                        <label for="Emailx">Email</label>
                        
                    </div>
                    

                    <!-- Create Password-->
                    <div>
                        <div class="passBox">
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control" id="cpass" value="{{ old('password') }}" required/>
                                <label for="cpass">Create Password</label>
                            </div>
                            <button type="button" class="input-group-text pass-eye">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                       
                    </div>
                    
                </div>
                   
                <div>
                    <button type="button" class="btn-md btn-sec" id="submitBtn">
                        Submit <i class="fa-solid fa-spinner" id="btnSpinner" style="display: none; animation: spin 1s linear infinite;"></i>
                    </button>
                </div>
            </form>

        </main>
    </section>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            const submitBtn = document.getElementById('submitBtn');
            const submitUrl = '{{ route("admin.checkers.store") }}';
            const redirectUrl = '{{ route("admin.checkers.index") }}';
            let isSubmitting = false;
            
            console.log('Form found:', form);
            console.log('Submit button found:', submitBtn);
            
            submitBtn.addEventListener('click', function(e) {
                console.log('Button click event triggered');
                console.log('Form element:', form);
                e.preventDefault();
                
                if (!form) {
                    console.error('Form not found!');
                    return false;
                }
                
                if (isSubmitting) {
                    console.log('Already submitting, returning');
                    return false;
                }
                
                console.log('Starting form validation');
                // Use direct ID selectors instead of form.querySelector
                const nameField = document.getElementById('xname');
                const emailField = document.getElementById('Emailx');
                const passwordField = document.getElementById('cpass');
                
                console.log('Found fields by ID:', {
                    name: nameField ? 'found' : 'not found',
                    email: emailField ? 'found' : 'not found', 
                    password: passwordField ? 'found' : 'not found'
                });
                
                let isValid = true;
                
                // Validate name field
                if (nameField && !nameField.value.trim()) {
                    isValid = false;
                    nameField.classList.add('is-invalid');
                    console.log('Name field is empty');
                } else if (nameField) {
                    nameField.classList.remove('is-invalid');
                }
                
                // Validate email field
                if (emailField && !emailField.value.trim()) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                    console.log('Email field is empty');
                } else if (emailField) {
                    emailField.classList.remove('is-invalid');
                }
                
                // Validate password field
                if (passwordField && !passwordField.value.trim()) {
                    isValid = false;
                    passwordField.classList.add('is-invalid');
                    console.log('Password field is empty');
                } else if (passwordField) {
                    passwordField.classList.remove('is-invalid');
                }
                
                // Check if all fields were found
                if (!nameField || !emailField || !passwordField) {
                    console.error('Some form fields were not found!');
                    return false;
                }
                
                if (!isValid) {
                    console.log('Form validation failed');
                    return false;
                }
                
                console.log('Form is valid, starting AJAX submission');
                // Show spinner and submit via AJAX
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Submitting... <i class="fa-solid fa-circle-notch fa-spin"></i>';
                
                // Create FormData
                let formData = new FormData(form);
                
                // Debug: Log FormData contents
                console.log('FormData contents:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                
                // If FormData is empty, create manually
                if (!formData.has('name')) {
                    console.log('FormData is empty, creating manually');
                    const manualFormData = new FormData();
                    manualFormData.append('_token', document.querySelector('input[name="_token"]').value);
                    manualFormData.append('name', nameField.value);
                    manualFormData.append('email', emailField.value);
                    manualFormData.append('password', passwordField.value);
                    
                    console.log('Manual FormData contents:');
                    for (let [key, value] of manualFormData.entries()) {
                        console.log(key + ': ' + value);
                    }
                    
                    // Use manual FormData
                    formData = manualFormData;
                }
                
                console.log('FormData created, making fetch request');
                
                fetch(submitUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        // Success - redirect to list page
                        if (typeof createNotification === 'function') {
                            createNotification("success", data.message || "Checker created successfully", "");
                        }
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        // Handle validation errors (422 status)
                        if (data.errors) {
                            // Show field-specific errors
                            /*if (data.errors.name) {
                                nameField.classList.add('is-invalid');
                                let errorDiv = nameField.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    nameField.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.name[0];
                            }*/
                            
                            /*if (data.errors.email) {
                                emailField.classList.add('is-invalid');
                                let errorDiv = emailField.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    emailField.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.email[0];
                            }*/
                            
                            /*if (data.errors.password) {
                                passwordField.classList.add('is-invalid');
                                let errorDiv = passwordField.parentNode.parentNode.querySelector('.invalid-feedback');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'invalid-feedback d-block';
                                    passwordField.parentNode.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent = data.errors.password[0];
                            }*/
                        }
                        
                        // Show general error notification
                        if (typeof createNotification === 'function') {
                            createNotification("error", data.message || "Please fix the errors below", "");
                        }
                        
                        // Reset button
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Submit';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof createNotification === 'function') {
                        createNotification("error", "Something went wrong. Please try again.", "");
                    }
                    
                    // Reset button
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit';
                });
            });
        });
    </script>
@endsection
