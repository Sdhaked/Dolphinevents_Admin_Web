<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $permissions = [
        'event-services-manage-event-services' => 'Manage Event Services',
        'event-services-view-event-services' => 'View Event Services',
        'event-services-create-event-services' => 'Create Event Services',
        'event-services-edit-event-services' => 'Edit Event Services',
        'event-services-delete-event-services' => 'Delete Event Services',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'module' => 'Event Services',
                    'name' => $name,
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys($this->permissions))
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $roleIds = $this->rolesThatAlreadyCouldManageServices();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys($this->permissions))
            ->pluck('id');

        if (Schema::hasTable('role_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')
            ->whereIn('slug', array_keys($this->permissions))
            ->delete();
    }

    private function rolesThatAlreadyCouldManageServices(): Collection
    {
        $roleIds = collect();

        $ticketTypesManagePermissionId = DB::table('permissions')
            ->where('slug', 'ticket-types-manage-ticket-types')
            ->value('id');

        if ($ticketTypesManagePermissionId) {
            $roleIds = $roleIds->merge(
                DB::table('role_permissions')
                    ->where('permission_id', $ticketTypesManagePermissionId)
                    ->pluck('role_id')
            );
        }

        $developerRoleId = DB::table('roles')
            ->where('slug', 'developer-admin')
            ->value('id');

        if ($developerRoleId) {
            $roleIds->push($developerRoleId);
        }

        return $roleIds->unique()->values();
    }
};
