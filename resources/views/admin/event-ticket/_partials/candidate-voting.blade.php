@php
    $votingContestents = ($contestents ?? collect())->values();
    $showVotingSelector = ($event->enable_voting ?? false) && $votingContestents->isNotEmpty();
    $defaultContestentImage = asset('images/defult_user.png');
@endphp

@if ($showVotingSelector)
    <input type="hidden" id="selectedContestentId" name="contestent_id" value="">

    <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap my-4">
        <div class="flex-grow-1">
            <h1 class="hd-sm mb-0">{{ $event->voting_title ?: 'Voting' }}</h1>
        </div>
        <div class="flex-shrink-0">
            <button type="button" class="btn-sm btn-prim" data-bs-toggle="modal" data-bs-target="#voteModal">
                {{ $event->voting_btn_title ?: 'Vote Now' }}
            </button>
        </div>
    </div>

    <div class="my-3 d-flex gap-3" id="selectedContestentBox" style="display: none !important;">
        <div class="flex-shrink-0">
            <img src="{{ $defaultContestentImage }}" id="selectedContestentImage" class="img-fluid img-thumbnail rounded-circle object-fit-cover" style="width: 50px; height: 50px;" alt="Contestent">
        </div>
        <div class="flex-grow-1">
            <p class="m-0">Selected Option</p>
            <h6 class="m-0" id="selectedContestentName"></h6>
            <small class="text-muted" id="selectedContestentMeta"></small>
        </div>
        <div class="flex-shrink-0">
            <button type="button" class="btn-xs btn-sec-outline" data-clear-contestent>Clear</button>
        </div>
    </div>

    <div class="modal fade" id="voteModal" tabindex="-1" aria-labelledby="voteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0" id="voteModalLabel">{{ $event->voting_title ?: 'Choose Contestent' }}</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-lg-5 g-4 main-row-modal44">
                        @foreach ($votingContestents as $contestent)
                            @php
                                $contestentImage = $contestent->image_url ?: $defaultContestentImage;
                            @endphp
                            <div>
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="contestent_picker"
                                    id="contestentPicker{{ $contestent->id }}"
                                    value="{{ $contestent->id }}"
                                    data-name="{{ $contestent->name }}"
                                    data-email="{{ $contestent->email ?: 'N/A' }}"
                                    data-phone="{{ $contestent->full_phone ?: 'N/A' }}"
                                    data-image="{{ $contestentImage }}"
                                >

                                <label class="vote-card w-100" for="contestentPicker{{ $contestent->id }}">
                                    <span class="vote-radio"></span>

                                    <div class="vote-img">
                                        <img src="{{ $contestentImage }}" alt="{{ $contestent->name }}">
                                    </div>

                                    <div class="vote-content">
                                        <h4 class="mb-2">{{ $contestent->name }}</h4>
                                        <p class="mb-1">{{ $contestent->email ?: 'Email N/A' }}</p>
                                        <p class="mb-1">{{ $contestent->full_phone ?: 'Phone N/A' }}</p>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid-auto">
                        <button class="btn-md btn-sec-outline" type="button" data-clear-contestent data-bs-dismiss="modal">I will choose later</button>
                        <button class="btn-md btn-sec" type="button" id="confirmContestentSelection">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectedContestentInput = document.getElementById('selectedContestentId');
            const selectedContestentBox = document.getElementById('selectedContestentBox');
            const selectedContestentImage = document.getElementById('selectedContestentImage');
            const selectedContestentName = document.getElementById('selectedContestentName');
            const selectedContestentMeta = document.getElementById('selectedContestentMeta');
            const confirmContestentSelection = document.getElementById('confirmContestentSelection');
            const voteModal = document.getElementById('voteModal');

            if (!selectedContestentInput || !confirmContestentSelection) return;

            const closeVoteModal = () => {
                if (window.bootstrap && voteModal) {
                    bootstrap.Modal.getOrCreateInstance(voteModal).hide();
                }
            };

            const clearContestentSelection = () => {
                selectedContestentInput.value = '';
                document.querySelectorAll('input[name="contestent_picker"]').forEach((radio) => {
                    radio.checked = false;
                });
                if (selectedContestentBox) {
                    selectedContestentBox.style.setProperty('display', 'none', 'important');
                }
            };

            window.resetAdminContestentSelection = clearContestentSelection;

            document.querySelectorAll('[data-clear-contestent]').forEach((button) => {
                button.addEventListener('click', clearContestentSelection);
            });

            confirmContestentSelection.addEventListener('click', () => {
                const selected = document.querySelector('input[name="contestent_picker"]:checked');

                if (!selected) {
                    if (typeof createNotification === 'function') {
                        createNotification('warning', 'Please choose a contestent or click I will choose later.', '');
                    }
                    return;
                }

                selectedContestentInput.value = selected.value;
                selectedContestentName.textContent = selected.dataset.name || '';
                selectedContestentMeta.textContent = [selected.dataset.email, selected.dataset.phone]
                    .filter((value) => value && value !== 'N/A')
                    .join(' | ');
                selectedContestentImage.src = selected.dataset.image || '{{ $defaultContestentImage }}';
                selectedContestentBox.style.removeProperty('display');
                closeVoteModal();
            });
        });
    </script>
@endif
