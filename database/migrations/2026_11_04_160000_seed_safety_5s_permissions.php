<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all Safety & 5S Permissions
        $permissions = [
            // General Module Access
            'safety_5s.view',
            'safety_5s.create',
            'safety_5s.edit',
            'safety_5s.delete',

            // 1. Interactive Floor Map
            'safety_5s.floor_map.view',
            'safety_5s.floor_map.create',
            'safety_5s.floor_map.edit',
            'safety_5s.floor_map.delete',

            // 2. Safety Inspections
            'safety_5s.inspections.view',
            'safety_5s.inspections.create',
            'safety_5s.inspections.edit',
            'safety_5s.inspections.delete',

            // 3. 5S Audit Execution
            'safety_5s.audits.view',
            'safety_5s.audits.create',
            'safety_5s.audits.edit',
            'safety_5s.audits.delete',

            // 4. Hazard & Near-Miss Reports
            'safety_5s.hazard_reports.view',
            'safety_5s.hazard_reports.create',
            'safety_5s.hazard_reports.edit',
            'safety_5s.hazard_reports.delete',

            // 5. Corrective Actions (CAPA)
            'safety_5s.corrective_actions.view',
            'safety_5s.corrective_actions.create',
            'safety_5s.corrective_actions.edit',
            'safety_5s.corrective_actions.delete',

            // 6. Leaderboard & Safety Rank
            'safety_5s.leaderboard.view',
            'safety_5s.leaderboard.create',
            'safety_5s.leaderboard.edit',
            'safety_5s.leaderboard.delete',

            // 7. Analytics & Safety Metrics
            'safety_5s.analytics.view',
            'safety_5s.analytics.create',
            'safety_5s.analytics.edit',
            'safety_5s.analytics.delete',

            // 8. Standards & Settings
            'safety_5s.settings.view',
            'safety_5s.settings.create',
            'safety_5s.settings.edit',
            'safety_5s.settings.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Assign to Super Admin, IT Administrator, Administrator
        $rolesToAssignAll = ['Super Admin', 'IT Administrator', 'Administrator', 'Quality Control Manager', 'General Affair', 'GA Manager', 'Production Manager'];
        foreach ($rolesToAssignAll as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        // Director gets all .view permissions
        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $directorViewPerms = array_filter($permissions, fn($p) => str_ends_with($p, '.view'));
            $director->givePermissionTo($directorViewPerms);
        }

        // Also ensure any roles with old audit_5s permissions get safety_5s permissions
        $rolesWith5S = Role::whereHas('permissions', function($q) {
            $q->where('name', 'like', 'audit_5s.%');
        })->get();

        foreach ($rolesWith5S as $r) {
            $r->givePermissionTo($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'like', 'safety_5s.%')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
