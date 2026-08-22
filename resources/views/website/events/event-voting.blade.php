@extends('layouts.website')

@section('head')
    @include('website._partials.head.meta-data', ['metaData' => $event?->meta_data, 'fallbackTitle' => 'Event Voting'])
    <!-- #=======> Head Files -->
    @include('website._partials.head.head-files')

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="{{ asset('website/style/aos.css') }}" />

    <!-- #=======> Call Style -->
    @include('website._partials.head.g-css-files')

    <!-- conditional css -->
    <link rel="stylesheet" href="{{ asset('website/style/page-styling/booking.css') }}" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="{{ asset('website/js/aos.js') }}" defer></script>
    <script src="{{ asset('website/js/custom.aos.js') }}" defer></script>

    <!-- Main JS Files -->
    @include('website._partials.head.g-js-files')
@endsection

@section('body')
    @php
        $completionUrl = $completionUrl ?? route('website.events.show', $event->slug);
    @endphp

    <!-- Preloader -->
    @include('website._partials.preloader')

    <!--########## HEADER ##########-->
    @include('website._partials.nav')

    <!-- MAIN BODY -->
    <main>
         <!--==================================================
                    Event Voting SECTION
        ======================================================-->
        <section class="container-fluid spc-y-half main-sec">
            <div class="container">
                <div class="back-box">
                    <button class="btm-md btn-link" onclick="history.back()">
                        <i class="fa-solid fa-arrow-left-long i-mr"></i>Back
                    </button>
                </div>

                <div class="voting-container max-full">
                    <div>
                        <div class="tag-box flex justify-center" style="margin-bottom:0.8rem;">
                            <span class="tag">Event: {{ $event?->title }}</span>
                            <span class="tag">Booking Id: {{ $booking?->booking_id }}</span>
                        </div>

                        <div class="all-text-center" data-aos="fade-up">
                            <h3 class="hd-prim">{{ $event?->voting_title ?: 'Give Vote' }}</h3>
                            {{-- @if ($event?->voting_des)
                                <p>{{ $event->voting_des }}</p>
                            @endif --}}
                        </div>

                        <div id="votingMessageBox">
                            @if (session('success'))
                                <p class="voting-alert success">{{ session('success') }}</p>
                            @endif

                            @if (session('warning'))
                                <p class="voting-alert warning">{{ session('warning') }}</p>
                            @endif

                            @if (session('error'))
                                <p class="voting-alert error">{{ session('error') }}</p>
                            @endif

                            @if ($errors->any())
                                <p class="voting-alert error">{{ $errors->first() }}</p>
                            @endif

                        </div>

                        @if ($contestents->isNotEmpty())
                            <form action="{{ route('website.events.voting.submit', $event->slug) }}" method="POST" class="voting-js-form">
                                @csrf

                                <div class="choose-row ticket-row">
                                    @foreach ($contestents as $contestent)
                                        @php
                                            $imageUrl = $contestent->image ? asset('storage/' . $contestent->image) : asset('images/no-img.png');
                                            $socialLinks = $contestent->social_links ?? [];
                                            $isSelected = old('contestent_id', $existingVote?->event_contestent_id) == $contestent->id;
                                        @endphp

                                        <div class="ticket-card aos-init aos-animate" data-aos="fade-up">
                                            <input type="radio" name="contestent_id" id="contestent-{{ $contestent->id }}" value="{{ $contestent->id }}" {{ $isSelected ? 'checked' : '' }} required>
                                            <label for="contestent-{{ $contestent->id }}" class="over-hidden">
                                                <div class="img-box" style="aspect-ratio: 1/1">
                                                    <img src="{{ $imageUrl }}" alt="{{ $contestent->name }}" loading="lazy" decoding="async">
                                                </div>

                                                <div class="info-box">
                                                    <h3 class="name">{{ $contestent->name }}</h3>

                                                    <ul class="contact-list">
                                                        @if ($contestent->email)
                                                            <li>
                                                                <i class="fa-solid fa-envelope"></i>
                                                                <span>{{ $contestent->email }}</span>
                                                            </li>
                                                        @endif

                                                        @if ($contestent->full_phone)
                                                            <li>
                                                                <i class="fa-solid fa-phone"></i>
                                                                <span>{{ $contestent->full_phone }}</span>
                                                            </li>
                                                        @endif
                                                    </ul>

                                                    @if (!empty($socialLinks))
                                                        <div class="social-list">
                                                            <hr style="margin: 0.8rem 0; border-color:var(--color-border-100);">
                                                            <ul>
                                                                @foreach ($socialLinks as $link)
                                                                    @php
                                                                        $social = config('entities.social_options.' . ($link['platform'] ?? ''));
                                                                    @endphp
                                                                    @if ($social && !empty($link['url']))
                                                                        <li>
                                                                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $social['label'] }}">
                                                                                <i class="{{ $social['icon'] }}"></i>
                                                                            </a>
                                                                        </li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex justify-center gap-card">
                                    <a href="{{ $completionUrl }}" class="btn-md hover-sec">I will choose later</a>
                                    <button type="submit" class="btn-md btn-prim hover-prim-outline no-transform" data-loading-text="Submitting...">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="no-data-box">
                                <p>No contestents found!</p>
                            </div>

                            <div class="flex justify-center gap-card">
                                <a href="{{ $completionUrl }}" class="btn-md hover-sec">Back to Event</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('website._partials.Footer')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery) return;

            const $messageBox = $('#votingMessageBox');
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const csrfRefreshUrl = @json(route('website.csrf_token'));

            function showVotingMessage(type, message) {
                $messageBox.html(`<p class="voting-alert ${type}">${message}</p>`);
            }

            function updateCsrfToken(token) {
                csrfToken = token;
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
                $('input[name="_token"]').val(token);
            }

            function refreshCsrfToken() {
                return $.ajax({
                    url: csrfRefreshUrl,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function (response) {
                    if (!response.token) {
                        return $.Deferred().reject().promise();
                    }

                    updateCsrfToken(response.token);
                    return response.token;
                });
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

            $('.voting-js-form').on('submit', function (event) {
                event.preventDefault();

                const $form = $(this);
                const $submitButton = $form.find('[type="submit"]').first();

                if ($form.data('submitting')) {
                    event.preventDefault();
                    showVotingMessage('warning', 'Vote submission is already processing. Please wait.');
                    return;
                }

                if (!$form.find('input[name="contestent_id"]:checked').length) {
                    event.preventDefault();
                    showVotingMessage('warning', 'Please choose one contestent before submitting your vote.');
                    return;
                }

                $form.data('submitting', true);
                $messageBox.empty();
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
                        const redirectUrl = response?.redirect || @json($completionUrl);
                        const eventDetailUrl = @json(route('website.events.show', $event->slug));

                        try {
                            if (new URL(redirectUrl, window.location.origin).href === new URL(eventDetailUrl, window.location.origin).href) {
                                localStorage.setItem(
                                    @json('event_voting_success_' . $event->slug),
                                    'Thankyou for voting we have receved your vote successfully'
                                );
                            }
                        } catch (error) {
                            console.warn('Voting success message could not be saved.', error);
                        }

                        window.location.href = redirectUrl;
                    },
                    error: function (xhr) {
                        if (xhr.status === 419) {
                            if ($form.data('csrf-retried')) {
                                $form.data('submitting', false);
                                $form.data('csrf-retried', false);
                                setButtonLoading($submitButton, false);
                                showVotingMessage('error', 'Your session expired. Please try again.');
                                return;
                            }

                            $form.data('csrf-retried', true);

                            refreshCsrfToken()
                                .done(function () {
                                    $form.data('submitting', false);
                                    $form.trigger('submit');
                                })
                                .fail(function () {
                                    $form.data('submitting', false);
                                    $form.data('csrf-retried', false);
                                    setButtonLoading($submitButton, false);
                                    showVotingMessage('error', 'Your session expired. Please refresh and try again.');
                                });
                            return;
                        }

                        const response = xhr.responseJSON || {};
                        let message = response.message || 'Unable to submit your vote. Please try again.';
                        $form.data('csrf-retried', false);

                        if (response.errors) {
                            const firstError = Object.values(response.errors)[0];
                            if (Array.isArray(firstError) && firstError.length) {
                                message = firstError[0];
                            }
                        }

                        showVotingMessage(xhr.status === 409 ? 'warning' : 'error', message);

                        if (response.redirect) {
                            alert(message);
                            window.location.href = response.redirect;
                            return;
                        }

                        $form.data('submitting', false);
                        setButtonLoading($submitButton, false);
                    }
                });
            });
        });
    </script>
@endsection
