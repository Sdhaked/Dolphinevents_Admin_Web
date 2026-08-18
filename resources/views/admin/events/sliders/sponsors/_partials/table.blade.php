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
            @forelse($sponsors as $index => $sponsor)
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ $sponsors->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label"><i class="fa-regular fa-image"></i></div>
                        <div>
                            <img src="{{ asset('storage/' . $sponsor->image) }}" class="table-f-img">
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Alt Text</div>
                        <div>
                            {{ $sponsor->alt_text }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div>
                            <div class="action-row">
                                <button class="action-btn edit"
                                        data-url="{{ route('admin.sponsors.update', $sponsor->id) }}"
                                        data-image="{{ asset('storage/' . $sponsor->image) }}"
                                        data-text="{{ $sponsor->alt_text }}"
                                        data-link="{{ $sponsor->url }}"
                                        data-media-delete-url="{{ route('admin.media.destroy', ['target' => 'event-sponsor-record', 'id' => $sponsor->id]) }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button class="action-btn delete"
                                        data-url="{{ route('admin.sponsors.destroy', $sponsor->id) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @if($sponsor->url)
                                    <a href="{{ $sponsor->url }}" target="_blank" rel="noopener noreferrer"
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

@if ($sponsors->hasPages())
    <div class="pagination">
        {{ $sponsors->links() }}
    </div>
@endif
