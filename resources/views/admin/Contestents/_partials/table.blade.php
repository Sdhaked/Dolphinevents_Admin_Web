<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S.No</th>
                <th><i class="fa-regular fa-image"></i></th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Votes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contestents as $index => $contestent)
                @php
                    $imageUrl = $contestent->image ? asset('storage/' . $contestent->image) : asset('images/no-img.png');
                @endphp
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ $contestents->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label"><i class="fa-regular fa-image"></i></div>
                        <div>
                            <img src="{{ $imageUrl }}" class="table-f-img" alt="{{ $contestent->name }}">
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Name</div>
                        <div>{{ $contestent->name }}</div>
                    </td>
                    <td>
                        <div class="data-label">Email</div>
                        <div class="text-break">{{ $contestent->email ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Phone</div>
                        <div>{{ $contestent->full_phone ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Votes</div>
                        <div>{{ number_format($contestent->votes) }}</div>
                    </td>
                    <td>
                        <div class="data-label">Actions</div>
                        <div>
                            <div class="action-row">
                                <a href="{{ route('admin.contestents.show', $contestent->id) }}" class="action-btn universal">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.contestents.edit', $contestent->id) }}" class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <button class="action-btn delete"
                                    data-url="{{ route('admin.contestents.destroy', $contestent->id) }}"
                                    data-name="{{ $contestent->name }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="text-center">No data found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($contestents->hasPages())
    <div class="pagination">
        {{ $contestents->links() }}
    </div>
@endif
