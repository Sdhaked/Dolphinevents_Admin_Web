<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S.No</th>
                <th><i class="fa-regular fa-image"></i></th>
                <th>Alt Text</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($images as $index => $image)
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ $images->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label"><i class="fa-regular fa-image"></i></div>
                        <div>
                            <img src="{{ asset('storage/' . $image->image) }}" class="table-f-img">
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Alt Text</div>
                        <div>
                            {{ $image->alt_text }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <button class="action-btn edit"
                                        data-url="{{ route('admin.event.gallery.update', $image->id) }}"
                                        data-text="{{ $image->alt_text }}"
                                        data-image="{{ asset('storage/' . $image->image) }}"
                                        data-media-delete-url="{{ route('admin.media.destroy', ['target' => 'event-gallery-record', 'id' => $image->id]) }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button class="action-btn delete"
                                        data-url="{{ route('admin.event.gallery.destroy', $image->id) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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

@if ($images->hasPages())
    <div class="pagination">
        {{ $images->links() }}
    </div>
@endif
