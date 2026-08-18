<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S No.</th>
                <th>Module</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Roles</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($permissions as $index => $permission)
                <tr>
                    <td>
                        <div class="data-label">S No.</div>
                        <div>{{ $permissions->firstItem() + $index }}</div>
                    </td>
                    <td>
                        <div class="data-label">Module</div>
                        <div>{{ $permission->module ?: 'General' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Name</div>
                        <div>{{ $permission->name }}</div>
                    </td>
                    <td>
                        <div class="data-label">Slug</div>
                        <div>{{ $permission->slug }}</div>
                    </td>
                    <td>
                        <div class="data-label">Roles</div>
                        <div>{{ $permission->roles_count }}</div>
                    </td>
                    <td>
                        <div class="data-label">Action</div>
                        <div class="action-row">
                            <a href="{{ route('admin.permissions.edit', $permission->id) }}" role="button"
                               class="action-btn edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            @if($permission->roles_count === 0)
                                <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this permission?');">
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
                        <div class="text-center">No permissions found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($permissions->hasPages())
    <div class="pagination">
        <ul>
            {{ $permissions->withQueryString()->links() }}
        </ul>
    </div>
@endif
