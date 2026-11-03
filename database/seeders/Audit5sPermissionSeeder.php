<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Audit5sPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'audit_5s.view',
            'audit_5s.create',
            'audit_5s.edit',
            'audit_5s.delete',

            'audit_5s.floor_map.view',
            'audit_5s.floor_map.create',
            'audit_5s.floor_map.edit',
            'audit_5s.floor_map.delete',

            'audit_5s.schedules.view',
            'audit_5s.schedules.create',
            'audit_5s.schedules.edit',
            'audit_5s.schedules.delete',

            'audit_5s.audits.view',
            'audit_5s.audits.create',
            'audit_5s.audits.edit',
            'audit_5s.audits.delete',

            'audit_5s.corrective_actions.view',
            'audit_5s.corrective_actions.create',
            'audit_5s.corrective_actions.edit',
            'audit_5s.corrective_actions.delete',

            'audit_5s.leaderboard.view',
            'audit_5s.leaderboard.create',
            'audit_5s.leaderboard.edit',
            'audit_5s.leaderboard.delete',

            'audit_5s.analytics.view',
            'audit_5s.analytics.create',
            'audit_5s.analytics.edit',
            'audit_5s.analytics.delete',

            'audit_5s.criteria.view',
            'audit_5s.criteria.create',
            'audit_5s.criteria.edit',
            'audit_5s.criteria.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $itAdmin = Role::where('name', 'IT Administrator')->first();
        if ($itAdmin) {
            $itAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $auditManagers = Role::whereIn('name', ['Quality Control Manager', 'General Affair', 'GA Manager', 'Production Manager'])->get();
        foreach ($auditManagers as $mgr) {
            $mgr->givePermissionTo($permissions);
        }

        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $directorViewPerms = array_filter($permissions, fn($p) => str_ends_with($p, '.view'));
            $director->givePermissionTo($directorViewPerms);
        }

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
}
