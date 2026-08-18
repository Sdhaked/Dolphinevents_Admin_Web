<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    public function index(Request $request)
    {
        $users = User::withTrashed()
            ->with('roleModel')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.users._partials.table', compact('users'))->render();
        }

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->preparePhoneInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'integer', 'exists:roles,id'],
            'mobile_number_prefix' => ['nullable', 'required_with:mobile_number', 'regex:/^\+\d{1,4}$/'],
            'mobile_number' => ['nullable', 'digits_between:1,12'],
        ]);

        $phoneData = $this->phoneData($validated);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'plain_password' => $validated['password'],
            'role' => $validated['role'],
            'mobile_number_prefix' => $phoneData['mobile_number_prefix'],
            'mobile_number' => $phoneData['mobile_number'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot update your own account from Master Control.');
        }

        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot update your own account from Master Control.');
        }

        $this->preparePhoneInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'integer', 'exists:roles,id'],
            'mobile_number_prefix' => ['nullable', 'required_with:mobile_number', 'regex:/^\+\d{1,4}$/'],
            'mobile_number' => ['nullable', 'digits_between:1,12'],
        ]);

        $phoneData = $this->phoneData($validated);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'mobile_number_prefix' => $phoneData['mobile_number_prefix'],
            'mobile_number' => $phoneData['mobile_number'],
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot deactivate your own account.');
        }

        if ($this->isDeveloperAdmin($user)) {
            return redirect()->route('admin.users.index')->with('error', 'Developer admin account cannot be deactivated.');
        }

        $user->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deactivated successfully!',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deactivated successfully!');
    }

    public function activate(Request $request, int $id)
    {
        $user = User::withTrashed()->with('roleModel')->findOrFail($id);

        if (!$user->trashed()) {
            return redirect()->route('admin.users.index')->with('success', 'User is already active.');
        }

        $user->restore();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User activated successfully!',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User activated successfully!');
    }

    private function preparePhoneInput(Request $request): void
    {
        $prefix = trim((string) $request->input('mobile_number_prefix'));
        $prefix = preg_replace('/^\((\+\d{1,4})\)$/', '$1', $prefix);
        $mobileNumber = preg_replace('/\s+/', '', (string) $request->input('mobile_number'));

        $request->merge([
            'mobile_number_prefix' => $prefix ?: null,
            'mobile_number' => $mobileNumber ?: null,
        ]);
    }

    private function phoneData(array $validated): array
    {
        $mobileNumber = $validated['mobile_number'] ?? null;

        if ($mobileNumber === null || $mobileNumber === '') {
            return [
                'mobile_number_prefix' => null,
                'mobile_number' => null,
            ];
        }

        return [
            'mobile_number_prefix' => $validated['mobile_number_prefix'] ?? null,
            'mobile_number' => $mobileNumber,
        ];
    }

    private function isDeveloperAdmin(User $user): bool
    {
        $role = $user->relationLoaded('roleModel') ? $user->roleModel : $user->roleModel()->first();

        return $role?->slug === 'developer-admin';
    }
}
