@php
    $isEdit = isset($role);
    $selectedPermissions = collect(old('permission_ids', $isEdit ? $role->permissions->pluck('id')->all() : []))
        ->map(fn ($permissionId) => (int) $permissionId)
        ->all();
@endphp

<form action="{{ $action }}" method="POST" novalidate="" class="grid-1 gap-card needs-validation">
    @csrf

    <div class="grid-2 grid-sm-1 gap-card">
        <div class="form-floating">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   id="role_name" value="{{ old('name', $role->name ?? '') }}" required>
            <label for="role_name">Name*</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating">
            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                   id="role_slug" value="{{ old('slug', $role->slug ?? '') }}">
            <label for="role_slug">Slug</label>
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-floating">
        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                  style="height: 100px" id="role_description">{{ old('description', $role->description ?? '') }}</textarea>
        <label for="role_description">Description</label>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="style-box">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-card mb-3">
            <h6 class="hd-sm mb-0">Permissions</h6>
            <button type="button" class="btn-xs btn-sec-outline" id="togglePermissions">Select All</button>
        </div>

        @error('permission_ids')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        @forelse($permissions as $module => $modulePermissions)
            <div class="mb-3">
                <h6 class="hd-xs text-capitalize">{{ $module ?: 'General' }}</h6>
                <div class="row">
                    @foreach($modulePermissions as $permission)
                        <div class="col-md-4 col-sm-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox" type="checkbox"
                                       name="permission_ids[]" value="{{ $permission->id }}"
                                       id="permission_{{ $permission->id }}"
                                       @checked(in_array($permission->id, $selectedPermissions, true))>
                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No permissions found.</p>
        @endforelse
    </div>

    <div>
        <button type="submit" class="btn-md btn-sec">Submit</button>
        <a href="{{ route('admin.roles.index') }}" class="btn-md btn-sec-outline">Cancel</a>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleButton = document.getElementById('togglePermissions');
        const permissionBoxes = document.querySelectorAll('.permission-checkbox');

        if (!toggleButton) return;

        toggleButton.addEventListener('click', function () {
            const shouldCheck = Array.from(permissionBoxes).some((box) => !box.checked);

            permissionBoxes.forEach((box) => {
                box.checked = shouldCheck;
            });

            toggleButton.textContent = shouldCheck ? 'Unselect All' : 'Select All';
        });
    });
</script>
