@extends('layouts.admin')

@section('head')
    <title>Event Support Detail</title>
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
            <h4 class="hd-lg">Event Support Details</h4>
            <div class="style-box">
                <form class="needs-validation grid-1 gap-card" id="eventSupportForm" novalidate
                      action="{{ route('admin.event.support.store') }}" method="POST">
                    @csrf
                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="phone_prefix" id="dv8">
                                @include('admin._partials.options.prefix-options', ['selected' => $support->phone_prefix ?? ''])
                            </select>
                            <label for="dv8">Prefix?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="phone" name="phone_number" class="form-control" id="myphone"
                                   value="{{ $support->phone_number ?? '' }}" required>
                            <label for="myphone">Phone No *</label>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="form-floating flex-shrink-0 me-2">
                            <select class="form-select" name="secondary_phone_prefix" id="sf8">
                                @include('admin._partials.options.prefix-options', ['selected' => $support->secondary_phone_prefix ?? ''])
                            </select>
                            <label for="sf8">Prefix?</label>
                        </div>
                        <div class="form-floating flex-grow-1">
                            <input type="phone" name="secondary_phone_number" class="form-control" id="myphone"
                                   value="{{ $support->secondary_phone_number ?? '' }}">
                            <label for="myphone">Phone No *</label>
                        </div>
                    </div>


                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="myemail"
                               value="{{ $support->email ?? '' }}" required>
                        <label for="myemail">Email</label>
                    </div>

                    <div class="form-floating">
                        <textarea class="form-control" name="address" style="height: 100px"
                                  required>{{ $support->address ?? '' }}</textarea>
                        <label>Address</label>
                    </div>

                    <!-- Social Box -->
                    <div class="mt-4">
                        <h5 class="hd-sm">Social Links</h5>
                        <ul class="social-list">
                            <li>
                                <select class="form-select" name="platform[1]">
                                    @include('admin._partials.options.social-options', ['selected' => $social[0]->platform ?? ''])
                                </select>

                                <input type="link" name="url[1]" class="form-control" placeholder="Social Link" value="{{ $social[0]->url ?? '' }}">
                            </li>
                            <li>
                                <select class="form-select" name="platform[2]">
                                    @include('admin._partials.options.social-options', ['selected' => $social[1]?->platform ?? ''])
                                </select>
                                <input type="link" name="url[2]" class="form-control" placeholder="Social Link" value="{{ $social[1]->url ?? '' }}">
                            </li>
                            <li>
                                <select class="form-select" name="platform[3]">
                                    @include('admin._partials.options.social-options', ['selected' => $social[2]->platform ?? ''])
                                </select>

                                <input type="link" name="url[3]" class="form-control" placeholder="Social Link" value="{{ $social[2]->url ?? '' }}">
                            </li>
                            <li>
                                <select class="form-select" name="platform[4]">
                                    @include('admin._partials.options.social-options', ['selected' => $social[3]->platform ?? ''])
                                </select>

                                <input type="link" name="url[4]" class="form-control" placeholder="Social Link" value="{{ $social[3]->url ?? '' }}">
                            </li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn-md btn-sec" id="saveBtn">Save Changes</button>
                        <button class="btn-md btn-sec d-none" type="button" id="saveBtnLoader" disabled>
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span role="status">Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </section>

    <script>
        @if($errors->has('not-found'))
        window.addEventListener('load', function() {
            if (typeof createNotification === 'function') {
                createNotification('error', @json($errors->first('not-found')), '');
            }
        });
        @endif

        @if(session('success_message'))
        window.addEventListener('load', function() {
            if (typeof createNotification === 'function') {
                createNotification('success', @json(session('success_message')), '');
            }
        });
        @endif

        /**
         * Form Submit
         */
        const form = document.getElementById('eventSupportForm');
        const saveBtn = document.getElementById('saveBtn');
        const saveBtnLoader = document.getElementById('saveBtnLoader');

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            saveBtn.classList.add('d-none');
            saveBtnLoader.classList.remove('d-none');
        });

        /**
         * Social Pair Validation
         */
        const selects = document.querySelectorAll('select[name^="platform"]');
        const inputs = document.querySelectorAll('input[name^="url"]');

        selects.forEach((select, index) => {
            const input = inputs[index];

            select.addEventListener('change', () => {
                validatePair(select, input);
                updateSelectOptions();
            });
            input.addEventListener('input', () => validatePair(select, input));
        });

        function validatePair(select, input) {
            // remove previous error classes
            select.classList.remove('border-danger');
            input.classList.remove('border-danger');

            // remove required
            input.setAttribute('required', false);
            select.setAttribute('required', false);

            // case 1: select has value but input is empty
            if (select.value && !input.value.trim()) {
                input.classList.add('border-danger');
                input.setAttribute('required', true);
            }

            // case 2: input has value but select is empty
            if (input.value.trim() && !select.value) {
                select.classList.add('border-danger');
                select.setAttribute('required', true);
            }
        }

        function updateSelectOptions() {
            // collect all selected values
            const selectedValues = Array.from(selects)
                .map(s => s.value)
                .filter(v => v !== "");

            // for each select, disable options that are already selected elsewhere
            selects.forEach(select => {
                const currentValue = select.value;

                Array.from(select.options).forEach(option => {
                    if (option.value === "") return; // skip placeholder

                    if (selectedValues.includes(option.value) && option.value !== currentValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }
    </script>
@endsection
