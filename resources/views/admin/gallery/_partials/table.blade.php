<!-- Data Table -->
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
            @forelse($galleries as $index => $gallery)
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ ($galleries->currentPage() - 1) * $galleries->perPage() + $index + 1 }}</div>
                    </td>
                    <td>
                        <div class="data-label"><i class="fa-regular fa-image"></i></div>
                        <div>
                            <img src="{{ asset('storage/' . $gallery->image_path) }}" class="table-f-img" alt="{{ $gallery->alt_text }}">
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Alt Text</div>
                        <div>
                            {{ $gallery->alt_text }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <button class="action-btn edit" data-bs-toggle="modal"
                                    data-bs-target="#galleryji" data-url="{{ route('admin.gallery.update', $gallery->id) }}"
                                    data-text="{{ $gallery->alt_text }}"
                                    data-image="{{ asset('storage/' . $gallery->image_path) }}"
                                    data-media-delete-url="{{ route('admin.media.destroy', ['target' => 'gallery-record', 'id' => $gallery->id]) }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <button class="action-btn delete"
                                    data-url="{{ route('admin.gallery.destroy', $gallery->id) }}"
                                    data-alt="{{ $gallery->alt_text ?: 'this image' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteGalleryModal">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">
                        <p>No gallery images found.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if ($galleries->hasPages())
    <div class="pagination">
        {{ $galleries->links() }}
    </div>
@endif
