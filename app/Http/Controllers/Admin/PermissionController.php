<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    public function index(Request $request)
    {
        $permissions = Permission::withCount('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%");
                });
            })
            ->orderBy('module')
            ->orderBy('name')
            ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.permissions._partials.table', compact('permissions'))->render();
        }

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'module' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug'],
            'description' => ['nullable', 'string'],
        ]);

        Permission::create($validated);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully!');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'module' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('permissions', 'slug')->ignore($permission->id)],
            'description' => ['nullable', 'string'],
        ]);

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully!');
    }

    public function destroy(Request $request, Permission $permission)
    {
        $permission->loadCount('roles');

        if ($permission->roles_count > 0) {
            $message = 'This permission is in use and cannot be deleted.';

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('admin.permissions.index')->with('error', $message);
        }

        $permission->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully!',
            ]);
        }

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully!');
    }
}
