<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Mobile</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $index => $user)
                <tr>
                    <td>
                        <div class="data-label">S No.</div>
                        <div>{{ $users->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label">Name</div>
                        <div>{{ $user->name }}</div>
                    </td>
                    <td>
                        <div class="data-label">Username</div>
                        <div>{{ $user->username }}</div>
                    </td>
                    <td>
                        <div class="data-label">Email</div>
                        <div class="text-break">{{ $user->email }}</div>
                    </td>
                    <td>
                        <div class="data-label">Role</div>
                        <div class="text-capitalize">{{ $user->roleModel?->name ?? 'unknown' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Mobile</div>
                        <div>{{ trim(($user->mobile_number_prefix ?? '') . ' ' . ($user->mobile_number ?? '')) ?: 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Status</div>
                        <div class="{{ $user->trashed() ? 'text-danger' : 'green' }}">
                            {{ $user->trashed() ? 'Deactivated' : 'Active' }}
                        </div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div class="action-row">
                            @php($isDeveloperAdmin = $user->roleModel?->slug === 'developer-admin')
                            @if($user->trashed())
                                <form action="{{ route('admin.users.activate', $user->id) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to activate this user?');">
                                    @csrf
                                    <button class="action-btn edit" type="submit" title="Activate user">
                                        <i class="fa-solid fa-user-check"></i>
                                    </button>
                                </form>
                            @elseif(auth()->id() !== $user->id)
                                <a href="{{ route('admin.users.edit', $user->id) }}" role="button" class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                @unless($isDeveloperAdmin)
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="action-btn delete" type="submit" title="Deactivate user">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="text-center">No users found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($users->hasPages())
    <div class="pagination">
        <ul>
            {{ $users->withQueryString()->links() }}
        </ul>
    </div>
@endif
