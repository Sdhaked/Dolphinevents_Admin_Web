@php
    $isEdit = isset($user);
    $storedPrefix = old('mobile_number_prefix', $user->mobile_number_prefix ?? '+353');
    $storedPrefix = trim((string) $storedPrefix);
    $storedPrefix = preg_replace('/^\((\+\d{1,4})\)$/', '$1', $storedPrefix);

    $mobileValue = old('mobile_number', $user->mobile_number ?? '');

    if (blank($mobileValue) && filled($storedPrefix) && !str_starts_with($storedPrefix, '+')) {
        $mobileValue = $storedPrefix;
        $storedPrefix = '+353';
    }

    $selectedPrefix = filled($storedPrefix) && str_starts_with($storedPrefix, '+') ? $storedPrefix : '+353';
@endphp

<form action="{{ $action }}" method="POST" novalidate="" class="grid-1 gap-card needs-validation">
    @csrf

    <div class="grid-2 grid-sm-1 gap-card">
        <div class="form-floating">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   id="user_name" value="{{ old('name', $user->name ?? '') }}" required>
            <label for="user_name">Name*</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating">
            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                   id="user_username" value="{{ old('username', $user->username ?? '') }}" required>
            <label for="user_username">Username*</label>
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   id="user_email" value="{{ old('email', $user->email ?? '') }}" required>
            <label for="user_email">Email*</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating">
            <select name="role" class="form-select text-capitalize @error('role') is-invalid @enderror" id="user_role" required>
                <option value="">Select Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}"
                        @selected((int) old('role', $user->role ?? 0) === $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            <label for="user_role">Role*</label>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <div class="passBox">
                <div class="form-floating">
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="user_password" @required(!$isEdit)>
                    <label for="user_password">{{ $isEdit ? 'New Password' : 'Password*' }}</label>
                </div>
                <button type="button" class="input-group-text pass-eye">
                    <i class="fa-solid fa-eye-slash"></i>
                </button>
            </div>
            @if($isEdit)
                <p style="margin-bottom:0; margin-top:0.2rem; font-size: 0.6rem">Leave blank to keep current password.</p>
            @endif
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex gap-2">
            <div class="form-floating flex-shrink-0">
                <select name="mobile_number_prefix"
                        class="form-select @error('mobile_number_prefix') is-invalid @enderror"
                        id="user_mobile_prefix" required>
                    @include('admin._partials.options.prefix-options', ['selected' => $selectedPrefix])
                </select>
                <label for="user_mobile_prefix">Prefix</label>
                @error('mobile_number_prefix')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating flex-grow-1">
                <input type="text" name="mobile_number"
                       class="form-control @error('mobile_number') is-invalid @enderror"
                       id="user_mobile" value="{{ $mobileValue }}"
                       inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" autocomplete="tel"
                       title="Mobile number must contain only digits and maximum 12 digits.">
                <label for="user_mobile">Mobile</label>
                @error('mobile_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div>
        <button type="submit" class="btn-md btn-sec">Submit</button>
        <a href="{{ route('admin.users.index') }}" class="btn-md btn-sec-outline">Cancel</a>
    </div>
</form>
