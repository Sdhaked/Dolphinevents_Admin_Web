<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_services') && !Schema::hasColumn('event_services', 'is_reserved')) {
            Schema::table('event_services', function (Blueprint $table) {
                $table->boolean('is_reserved')->default(false)->after('is_mandatory');
            });
        }

        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissions = [
            'event-services-manage-reserved' => 'Manage Reserved',
            'event-services-delete-reserved' => 'Delete Reserved',
        ];

        foreach ($permissions as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'module' => 'Event Services',
                    'name' => $name,
                    'description' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $developerRoleId = DB::table('roles')->where('slug', 'developer-admin')->value('id');

        if (!$developerRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys($permissions))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $developerRoleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions') && Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', [
                    'event-services-manage-reserved',
                    'event-services-delete-reserved',
                ])
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            }
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->whereIn('slug', [
                    'event-services-manage-reserved',
                    'event-services-delete-reserved',
                ])
                ->delete();
        }

        if (Schema::hasTable('event_services') && Schema::hasColumn('event_services', 'is_reserved')) {
            Schema::table('event_services', function (Blueprint $table) {
                $table->dropColumn('is_reserved');
            });
        }
    }
};
