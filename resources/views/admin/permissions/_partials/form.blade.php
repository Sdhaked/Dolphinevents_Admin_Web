<form action="{{ $action }}" method="POST" novalidate="" class="grid-1 gap-card needs-validation">
    @csrf

    <div class="grid-2 grid-sm-1 gap-card">
        <div class="form-floating">
            <input type="text" name="module" class="form-control @error('module') is-invalid @enderror"
                   id="permission_module" value="{{ old('module', $permission->module ?? '') }}">
            <label for="permission_module">Module</label>
            @error('module')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   id="permission_name" value="{{ old('name', $permission->name ?? '') }}" required>
            <label for="permission_name">Name*</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-floating">
        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
               id="permission_slug" value="{{ old('slug', $permission->slug ?? '') }}">
        <label for="permission_slug">Slug</label>
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-floating">
        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                  style="height: 100px" id="permission_description">{{ old('description', $permission->description ?? '') }}</textarea>
        <label for="permission_description">Description</label>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <button type="submit" class="btn-md btn-sec">Submit</button>
        <a href="{{ route('admin.permissions.index') }}" class="btn-md btn-sec-outline">Cancel</a>
    </div>
</form>
