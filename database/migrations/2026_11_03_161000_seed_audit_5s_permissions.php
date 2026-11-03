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

        // 1. Define all 5S Audit Permissions (Module level + 7 Submenus)
        $permissions = [
            // General Module Access
            'audit_5s.view',
            'audit_5s.create',
            'audit_5s.edit',
            'audit_5s.delete',

            // 1. Interactive Floor Map
            'audit_5s.floor_map.view',
            'audit_5s.floor_map.create',
            'audit_5s.floor_map.edit',
            'audit_5s.floor_map.delete',

            // 2. Audit Schedules
            'audit_5s.schedules.view',
            'audit_5s.schedules.create',
            'audit_5s.schedules.edit',
            'audit_5s.schedules.delete',

            // 3. Audit Execution & Scoring
            'audit_5s.audits.view',
            'audit_5s.audits.create',
            'audit_5s.audits.edit',
            'audit_5s.audits.delete',

            // 4. Corrective Actions (CAPA Before-After)
            'audit_5s.corrective_actions.view',
            'audit_5s.corrective_actions.create',
            'audit_5s.corrective_actions.edit',
            'audit_5s.corrective_actions.delete',

            // 5. Leaderboard & Rankings
            'audit_5s.leaderboard.view',
            'audit_5s.leaderboard.create',
            'audit_5s.leaderboard.edit',
            'audit_5s.leaderboard.delete',

            // 6. Analytics & Reports
            'audit_5s.analytics.view',
            'audit_5s.analytics.create',
            'audit_5s.analytics.edit',
            'audit_5s.analytics.delete',

            // 7. Criteria & Settings
            'audit_5s.criteria.view',
            'audit_5s.criteria.create',
            'audit_5s.criteria.edit',
            'audit_5s.criteria.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Assign to Super Admin (Gets all)
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // 3. Assign to IT Administrator (Gets all)
        $itAdmin = Role::where('name', 'IT Administrator')->first();
        if ($itAdmin) {
            $itAdmin->givePermissionTo($permissions);
        }

        // 4. Assign to Administrator (if exists)
        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // 5. Assign to Quality Control / QMS / GA Managers
        $auditManagers = Role::whereIn('name', ['Quality Control Manager', 'General Affair', 'GA Manager', 'Production Manager'])->get();
        foreach ($auditManagers as $mgr) {
            $mgr->givePermissionTo($permissions);
        }

        // 6. Assign to Director (View-only across all submenus)
        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $directorViewPerms = array_filter($permissions, fn($p) => str_ends_with($p, '.view'));
            $director->givePermissionTo($directorViewPerms);
        }

        // 7. Area PIC / Operator role (View map, submit corrective action photo)
        $picRole = Role::firstOrCreate(['name' => '5S Area PIC', 'guard_name' => 'web']);
        $picRole->givePermissionTo([
            'audit_5s.view',
            'audit_5s.floor_map.view',
            'audit_5s.corrective_actions.view',
            'audit_5s.corrective_actions.edit',
            'audit_5s.leaderboard.view',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'like', 'audit_5s%')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
