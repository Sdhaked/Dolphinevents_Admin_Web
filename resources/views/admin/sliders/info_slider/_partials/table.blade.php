<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S.No</th>
                <th><i class="fa-regular fa-image"></i></th>
                <th>Alt Text</th>
                <th>Acion</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($slides as $index => $slide)
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ $slides->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label"><i class="fa-regular fa-image"></i></div>
                        <div>
                            <img src="{{ asset('storage/' . $slide->image) }}" class="table-f-img">
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Alt Text</div>
                        <div>
                            {{ $slide->alt_text }}
                        </div>
                    </td>

                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <button class="action-btn edit" data-url="{{ route('admin.sliders.info.update', $slide->id) }}" data-image="{{ asset('storage/' . $slide->image) }}" data-text="{{ $slide->alt_text }}" data-link="{{ $slide->url }}" data-media-delete-url="{{ route('admin.media.destroy', ['target' => 'info-slider-record', 'id' => $slide->id]) }}" data-bs-toggle="modal" data-bs-target="#editSlide">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button class="action-btn delete" data-url="{{ route('admin.sliders.info.destroy', $slide->id) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>

                                @if ($slide->url)
                                    <a href="{{ $slide->url }}" target="_blank" rel="noopener noreferrer"
                                        class="action-btn universal">
                                        <i class="fa-solid fa-link"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="text-center">No data found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($slides->hasPages())
    <div class="pagination">
        {{ $slides->links() }}
    </div>
@endif
