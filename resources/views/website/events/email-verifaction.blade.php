@extends('layouts.website')

@section('head')
    @include('website._partials.head.meta-data', [
        'metaData' => (($verificationFlow ?? 'voting') === 'voting') ? $event?->meta_data : null,
        'fallbackTitle' => $pageTitle ?? 'Email Verification',
    ])

    @include('website._partials.head.head-files')
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />
    @include('website._partials.head.g-css-files')
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}" />

    <style>
        .otp-operations {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            justify-content: space-between;
            margin-top: 0.8rem;
        }

        .otp-operations button {
            color: var(--my-primary);
            font-size: 0.85rem;
            font-weight: 700;
            transition: var(--transition-ease);
        }

        .otp-operations button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>
    @include('website._partials.head.g-js-files')
@endsection

@section('body')
    @php
        $flow = $verificationFlow ?? 'voting';
        $allowEmailChange = ($allowEmailChange ?? $flow === 'checkout') && !empty($changeEmailUrl);
        $formClass = 'otp-js-form';
        $resendFormId = 'resendOtpForm';
        $alertModalId = 'otpAlertModal';
        $alertTitleId = 'otpAlertTitle';
        $alertMessageId = 'otpAlertMessage';
        $initialAlertTitle = null;
        $initialAlertMessage = null;

        if (session('warning')) {
            $initialAlertTitle = 'Notice';
            $initialAlertMessage = session('warning');
        } elseif (session('error')) {
            $initialAlertTitle = 'Process Failed!';
            $initialAlertMessage = session('error');
        } elseif ($errors->any()) {
            $initialAlertTitle = 'Validation Error';
            $initialAlertMessage = $errors->first();
        }
    @endphp

    @include('website._partials.preloader')
    @include('website._partials.nav')

    <main>
        <section class="container-fluid spc-y-half main-sec">
            <div class="container">
                <div class="back-box">
                    @if (!empty($backUrl))
                        <a class="btm-md btn-link" id="verificationBackLink" href="{{ $backUrl }}">
                            <i class="fa-solid fa-arrow-left-long i-mr"></i>Back
                        </a>
                    @else
                        <button class="btm-md btn-link" id="verificationBackLink" onclick="history.back()">
                            <i class="fa-solid fa-arrow-left-long i-mr"></i>Back
                        </button>
                    @endif
                </div>

                <div class="voting-container" data-aos="fade-up">
                    <div id="otpStepBox">
                        @if (!empty($paymentIssueMessage))
                            <div class="otp-section">
                                <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                                    <span class="tag">Booking Id: {{ $bookingId }}</span>
                                </div>

                                <div class="all-text-center">
                                    <h3 class="hd-prim">Payment Issue</h3>
                                    @if (!empty($eventTitle))
                                        <p style="font-size:0.82rem;">{{ $eventTitle }}</p>
                                    @endif
                                </div>

                                <div class="otp-sent-text"
                                    style="margin-bottom:1rem; color: var(--color-status-red); font-weight:700; text-align:center;">
                                    {{ $paymentIssueMessage }}
                                </div>

                                @if (!empty($paymentRetryUrl))
                                    <a href="{{ $paymentRetryUrl }}"
                                        class="btn-md btn-prim hover-prim-outline btn-w-full no-transform">
                                        Retry Payment <i class="fa-solid fa-arrow-right-long i-ml"></i>
                                    </a>
                                @endif
                            </div>
                        @elseif (!$showOtpForm)
                            <div class="all-text-center">
                                <h3 class="hd-prim">Enter: Booking ID</h3>
                                <p style="font-size: 0.82rem;">{{ $eventTitle ?? $event?->title }}</p>
                            </div>

                            <form action="{{ $sendOtpUrl }}" method="POST"
                                class="needs-validation {{ $formClass }}" novalidate
                                data-warning-message="Please enter your booking id.">
                                @csrf

                                <div style="margin-bottom:0.8rem;">
                                    <input type="text" name="booking_id" class="form-control"
                                        value="{{ old('booking_id') }}" placeholder="Enter your Booking id" required>
                                    <div class="invalid-feedback" data-feedback-for="booking_id"></div>
                                </div>

                                <button type="submit"
                                    class="btn-md btn-prim hover-prim-outline btn-w-full no-transform"
                                    data-loading-text="Sending OTP...">
                                    Next <i class="fa-solid fa-arrow-right-long i-ml"></i>
                                </button>
                            </form>
                        @else
                            <div class="otp-section">
                                <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                                    <span class="tag">Booking Id: {{ $bookingId }}</span>
                                </div>

                                <div class="all-text-center">
                                    <h3 class="hd-prim">Enter: OTP</h3>
                                    @if (!empty($eventTitle))
                                        <p style="font-size:0.82rem;">{{ $eventTitle }}</p>
                                    @endif
                                </div>

                                <form action="{{ $verifyOtpUrl }}" method="POST"
                                    class="needs-validation {{ $formClass }}" novalidate
                                    data-warning-message="Please enter the 6 digit OTP.">
                                    @csrf
                                    @if ($flow === 'voting')
                                        <input type="hidden" name="booking_id" value="{{ $bookingId }}">
                                    @endif

                                    <div style="margin-bottom:0.8rem;">
                                        <input type="text" name="otp" class="form-control" placeholder="Enter OTP"
                                            inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                                        <div class="invalid-feedback" data-feedback-for="otp"></div>
                                        <p class="otp-sent-text"
                                            style="font-size: 0.7rem; margin-top:0.4rem; color: var(--color-status-green); font-weight:700;">
                                            OTP is sent to {{ $maskedEmail }}.
                                        </p>

                                        <div class="otp-operations">
                                            @if ($allowEmailChange)
                                                <button type="button" class="change-email-toggle">Change Email Id</button>
                                            @endif
                                            <button type="submit" form="{{ $resendFormId }}"
                                                data-loading-text="Sending..." data-resend-button
                                                data-resend-seconds="{{ $resendWaitSeconds }}">Resend OTP</button>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="btn-md btn-prim hover-prim-outline btn-w-full no-transform"
                                        data-loading-text="Verifying...">
                                        Verify <i class="fa-solid fa-arrow-right-long i-ml"></i>
                                    </button>
                                </form>

                                <form action="{{ $resendOtpUrl }}" method="POST" id="{{ $resendFormId }}"
                                    class="{{ $formClass }}"
                                    data-warning-message="Please wait while we resend your OTP.">
                                    @csrf
                                </form>
                            </div>

                            @if ($allowEmailChange)
                                <form action="{{ $changeEmailUrl }}" method="POST"
                                    class="needs-validation {{ $formClass }} change-email-form" novalidate
                                    data-warning-message="Please enter a valid email id." style="display:none;">
                                    @csrf

                                    <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                                        <span class="tag">Booking Id: {{ $bookingId }}</span>
                                    </div>

                                    <div class="all-text-center">
                                        <h3 class="hd-prim">Change Email Id</h3>
                                    </div>

                                    <div style="margin-bottom:0.8rem;">
                                        <input type="email" name="email" class="form-control"
                                            placeholder="Enter new email id" required>
                                        <div class="invalid-feedback" data-feedback-for="email"></div>
                                        <p style="font-size: 0.7rem; margin-top:0.4rem;">
                                            This email will be saved with your booking id and a new OTP will be sent.
                                        </p>
                                    </div>

                                    <button type="submit"
                                        class="btn-md btn-prim hover-prim-outline btn-w-full no-transform"
                                        data-loading-text="Updating...">
                                        Update Email & Send OTP
                                    </button>

                                    <button type="button"
                                        class="btn-md btn-lite-outline hover-lite btn-w-full no-transform back-to-otp"
                                        style="margin-top:0.8rem;">
                                        Back to OTP
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="popup-container pop-boxJS" id="{{ $alertModalId }}">
        <div class="popup w-s popJS">
            <div class="close-box">
                <div class="title-box">
                    <h5 class="hd-sub" id="{{ $alertTitleId }}">Notice</h5>
                </div>
                <button class="btn-lg btn-close">
                    <i class="fa-regular fa-circle-xmark"></i>
                </button>
            </div>

            <div class="module-body">
                <p class="mb-0 text-center" id="{{ $alertMessageId }}">Something went wrong!! Please try again.</p>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn-sm btn-prim hover-prim-outline btn-close">Ok</button>
                </div>
            </div>
        </div>
    </div>

    @include('website._partials.Footer')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery) return;

            const flow = @json($flow);
            const allowEmailChange = @json((bool) $allowEmailChange);
            const formClass = @json($formClass);
            const resendFormId = @json($resendFormId);
            const $stepBox = $('#otpStepBox');
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const csrfRefreshUrl = @json(route('website.csrf_token'));
            const resetUrl = @json($resetUrl);
            const showOtpForm = @json((bool) $showOtpForm);
            let resendTimer = null;
            const initialAlert = {
                title: @json($initialAlertTitle),
                message: @json($initialAlertMessage),
            };

            function showOtpAlert(message, title = 'Notice') {
                $('#{{ $alertTitleId }}').text(title);
                $('#{{ $alertMessageId }}').text(message);
                showElement('#{{ $alertModalId }}');
            }

            function setButtonLoading($button, isLoading) {
                if (!$button.length) return;

                if (isLoading) {
                    $button.data('original-html', $button.html());
                    const loadingText = $button.data('loading-text') || 'Please wait...';
                    $button.html(`<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${loadingText}`);
                    $button.prop('disabled', true).attr('aria-busy', 'true');
                    return;
                }

                $button.html($button.data('original-html'));
                $button.prop('disabled', false).removeAttr('aria-busy');
            }

            function startResendTimer(seconds) {
                const $button = $('[data-resend-button]');
                if (!$button.length) return;

                const originalText = 'Resend OTP';
                let remaining = Number(seconds || 0);

                if (resendTimer) {
                    clearInterval(resendTimer);
                    resendTimer = null;
                }

                if (remaining <= 0) {
                    $button.text(originalText).prop('disabled', false);
                    return;
                }

                $button.prop('disabled', true).text(`${originalText} (${remaining}s)`);

                resendTimer = setInterval(() => {
                    remaining -= 1;

                    if (remaining <= 0) {
                        clearInterval(resendTimer);
                        resendTimer = null;
                        $button.text(originalText).prop('disabled', false);
                        return;
                    }

                    $button.text(`${originalText} (${remaining}s)`);
                }, 1000);
            }

            function getErrorMessage(xhr) {
                const response = xhr.responseJSON || {};
                let message = response.message || 'Something went wrong. Please try again.';

                if (response.errors) {
                    const firstError = Object.values(response.errors)[0];
                    if (Array.isArray(firstError) && firstError.length) {
                        message = firstError[0];
                    }
                }

                return message;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function clearFormErrors($form) {
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
            }

            function applyFieldErrors($form, errors = {}) {
                Object.entries(errors).forEach(([field, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    const $field = $form.find(`[name="${field}"]`).first();
                    const $feedback = $form.find(`[data-feedback-for="${field}"]`).first();

                    $field.addClass('is-invalid');
                    $feedback.text(message);
                });
            }

            function updateCsrfToken(token) {
                csrfToken = token;
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
                $(`input[name="_token"]`).val(token);
            }

            function refreshCsrfToken() {
                return $.ajax({
                    url: csrfRefreshUrl,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then((response) => {
                    if (!response.token) {
                        return $.Deferred().reject().promise();
                    }

                    updateCsrfToken(response.token);
                    return response.token;
                });
            }

            function handleCsrfMismatch($form, $button) {
                if ($form.data('csrf-retried')) {
                    $form.data('submitting', false);
                    setButtonLoading($button, false);
                    showOtpAlert('Your session expired. Please request OTP again.', 'Session Expired');
                    return;
                }

                $form.data('csrf-retried', true);

                refreshCsrfToken()
                    .done(() => {
                        $form.data('submitting', false);
                        $form.trigger('submit');
                    })
                    .fail(() => {
                        $form.data('submitting', false);
                        setButtonLoading($button, false);
                        showOtpAlert('Your session expired. Please try again.', 'Session Expired');
                    });
            }

            function otpStepHtml(response) {
                const bookingId = escapeHtml(response.booking_id);
                const maskedEmail = escapeHtml(response.masked_email);
                const canChangeEmail = Boolean(response.allow_email_change ?? allowEmailChange);
                const changeEmailButton = canChangeEmail
                    ? '<button type="button" class="change-email-toggle">Change Email Id</button>'
                    : '';
                const changeEmailForm = canChangeEmail
                    ? `
                    <form action="${response.change_email_url}" method="POST" class="needs-validation ${formClass} change-email-form" novalidate data-warning-message="Please enter a valid email id." style="display:none; margin-top:1rem;">
                        <input type="hidden" name="_token" value="${csrfToken}">

                        <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                            <span class="tag">Booking Id: ${bookingId}</span>
                        </div>

                        <div class="all-text-center">
                            <h3 class="hd-prim">Change Email Id</h3>
                        </div>

                        <div style="margin-bottom:0.8rem;">
                            <input type="email" name="email" class="form-control" placeholder="Enter new email id" required>
                            <div class="invalid-feedback" data-feedback-for="email"></div>
                            <p style="font-size: 0.7rem; margin-top:0.4rem;">
                                This email will be saved with your booking id and a new OTP will be sent.
                            </p>
                        </div>

                        <button type="submit" class="btn-md btn-prim hover-prim-outline btn-w-full no-transform" data-loading-text="Updating...">
                            Update Email & Send OTP
                        </button>

                        <button type="button" class="btn-md btn-lite-outline hover-lite btn-w-full no-transform back-to-otp" style="margin-top:0.8rem;">
                            Back to OTP
                        </button>
                    </form>
                `
                    : '';

                return `
                    <div class="otp-section">
                        <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                            <span class="tag">Booking Id: ${bookingId}</span>
                        </div>

                        <div class="all-text-center">
                            <h3 class="hd-prim">Enter: OTP</h3>
                        </div>

                        <form action="${response.verify_url}" method="POST" class="needs-validation ${formClass}" novalidate data-warning-message="Please enter the 6 digit OTP.">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="booking_id" value="${bookingId}">

                            <div style="margin-bottom:0.8rem;">
                                <input type="text" name="otp" class="form-control" placeholder="Enter OTP" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                                <div class="invalid-feedback" data-feedback-for="otp"></div>
                                <p class="otp-sent-text" style="font-size: 0.7rem; margin-top:0.4rem; color: var(--color-status-green); font-weight:700;">
                                    OTP is sent to ${maskedEmail}.
                                </p>

                                <div class="otp-operations">
                                    ${changeEmailButton}
                                    <button type="submit" form="${resendFormId}" data-loading-text="Sending..." data-resend-button data-resend-seconds="${response.resend_after_seconds || 60}">Resend OTP</button>
                                </div>
                            </div>

                            <button type="submit" class="btn-md btn-prim hover-prim-outline btn-w-full no-transform" data-loading-text="Verifying...">
                                Verify <i class="fa-solid fa-arrow-right-long i-ml"></i>
                            </button>
                        </form>

                        <form action="${response.resend_url}" method="POST" id="${resendFormId}" class="${formClass}" data-warning-message="Please wait while we resend your OTP.">
                            <input type="hidden" name="_token" value="${csrfToken}">
                        </form>
                    </div>
                    ${changeEmailForm}
                `;
            }

            if (initialAlert.message) {
                showOtpAlert(initialAlert.message, initialAlert.title || 'Notice');
            }

            $(document).on('click', '.change-email-toggle', function () {
                $('.otp-section').hide();
                $('.change-email-form').fadeIn(160);
            });

            $(document).on('click', '.back-to-otp', function () {
                $('.change-email-form').hide();
                $('.otp-section').fadeIn(160);
            });

            $(document).on('click', `.${formClass} [type="submit"], button[form="${resendFormId}"]`, function () {
                const formId = $(this).attr('form');
                const $form = formId ? $('#' + formId) : $(this).closest('form');
                $form.data('clicked-submit', $(this));
            });

            $(document).on('submit', `.${formClass}`, function (event) {
                event.preventDefault();

                const form = this;
                const $form = $(form);
                let $submitButton = $form.data('clicked-submit');

                if (!$submitButton?.length) {
                    $submitButton = $form.find('[type="submit"]').first();
                }

                if (!$submitButton.length && form.id) {
                    $submitButton = $(`[type="submit"][form="${form.id}"]`).first();
                }

                if ($form.data('submitting')) {
                    showOtpAlert('Request is already processing. Please wait.', 'Notice');
                    return;
                }

                clearFormErrors($form);

                if (form.checkValidity && !form.checkValidity()) {
                    $form.addClass('was-validated');
                    showOtpAlert($form.data('warning-message') || 'Please fill required details correctly.', 'Validation Error');
                    return;
                }

                $form.data('submitting', true);
                setButtonLoading($submitButton, true);

                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method') || 'POST',
                    data: $form.serialize(),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function (response) {
                        $form.data('csrf-retried', false);

                        if (response.otp_step) {
                            $stepBox.html(otpStepHtml(response));
                            if (flow === 'voting' && response.reset_url) {
                                $('#verificationBackLink').attr('href', response.reset_url).removeAttr('onclick');
                            }
                            startResendTimer(response.resend_after_seconds || 60);
                            return;
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }

                        if (response.masked_email) {
                            $('.otp-sent-text').text(`OTP is sent to ${response.masked_email}.`);
                        }

                        if ($form.hasClass('change-email-form')) {
                            form.reset();
                            $('.change-email-form').hide();
                            $('.otp-section').fadeIn(160);
                        }

                        $form.data('submitting', false);
                        setButtonLoading($submitButton, false);

                        if (response.resend_after_seconds !== undefined) {
                            startResendTimer(response.resend_after_seconds);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 419) {
                            handleCsrfMismatch($form, $submitButton);
                            return;
                        }

                        const response = xhr.responseJSON || {};
                        const title = response.type === 'warning' || xhr.status === 409 ? 'Notice' : 'Process Failed!';
                        $form.data('csrf-retried', false);

                        if (response.errors) {
                            applyFieldErrors($form, response.errors);
                            $form.addClass('was-validated');
                        }

                        showOtpAlert(getErrorMessage(xhr), title);

                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }

                        if (response.reset && resetUrl) {
                            window.location.href = resetUrl;
                            return;
                        }

                        $form.data('submitting', false);
                        setButtonLoading($submitButton, false);

                        if (response.resend_after_seconds !== undefined) {
                            startResendTimer(response.resend_after_seconds);
                        }
                    }
                });
            });

            startResendTimer(Number($('[data-resend-button]').data('resend-seconds') || 0));

            window.addEventListener('pageshow', function (event) {
                const cameFromBackForward = event.persisted
                    || performance.getEntriesByType('navigation')?.[0]?.type === 'back_forward';

                if (flow === 'voting' && cameFromBackForward && showOtpForm && resetUrl) {
                    window.location.replace(resetUrl);
                }
            });
        });
    </script>
@endsection
