<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Users</th>
                <th>Permissions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roles as $index => $role)
                <tr>
                    <td>
                        <div class="data-label">S No.</div>
                        <div>{{ $roles->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label">Name</div>
                        <div class="text-capitalize">{{ $role->name }}</div>
                    </td>
                    <td>
                        <div class="data-label">Slug</div>
                        <div>{{ $role->slug }}</div>
                    </td>
                    <td>
                        <div class="data-label">Users</div>
                        <div>{{ $role->users_count }}</div>
                    </td>
                    <td>
                        <div class="data-label">Permissions</div>
                        <div>{{ $role->permissions_count }}</div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div class="action-row">
                            <a href="{{ route('admin.roles.edit', $role->id) }}" role="button" class="action-btn edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            @if($role->users_count === 0 && $role->permissions_count === 0)
                                <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="action-btn delete" type="submit">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="text-center">No roles found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($roles->hasPages())
    <div class="pagination">
        <ul>
            {{ $roles->withQueryString()->links() }}
        </ul>
    </div>
@endif
