<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityPatrolPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all Security Patrol Permissions (Module level + 6 Granular Submenus)
        $permissions = [
            // General Module Access
            'security_patrol.view',
            'security_patrol.create',
            'security_patrol.edit',
            'security_patrol.delete',

            // 1. Live Map & Monitor
            'security_patrol.live_map.view',
            'security_patrol.live_map.create',
            'security_patrol.live_map.edit',
            'security_patrol.live_map.delete',

            // 2. Sesi & Jadwal
            'security_patrol.schedules.view',
            'security_patrol.schedules.create',
            'security_patrol.schedules.edit',
            'security_patrol.schedules.delete',

            // 3. Patroli Aktif (Mobile)
            'security_patrol.active_patrol.view',
            'security_patrol.active_patrol.create',
            'security_patrol.active_patrol.edit',
            'security_patrol.active_patrol.delete',

            // 4. Resume Patrol (Rekapitulasi & Hapus Sesi)
            'security_patrol.resume.view',
            'security_patrol.resume.create',
            'security_patrol.resume.edit',
            'security_patrol.resume.delete',

            // 5. Grafik & Analitik
            'security_patrol.analytics.view',
            'security_patrol.analytics.create',
            'security_patrol.analytics.edit',
            'security_patrol.analytics.delete',

            // 6. Master Titik & Cetak QR
            'security_patrol.checkpoints.view',
            'security_patrol.checkpoints.create',
            'security_patrol.checkpoints.edit',
            'security_patrol.checkpoints.delete',
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

        // 5. Assign to Director (View-only across all submenus)
        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $directorViewPerms = array_filter($permissions, fn($p) => str_ends_with($p, '.view'));
            $director->givePermissionTo($directorViewPerms);
        }

        // 6. Assign to General Affair / HR (Management oversight)
        $gaRoles = Role::whereIn('name', ['General Affair', 'GA Manager', 'HR & Payroll'])->get();
        foreach ($gaRoles as $gaRole) {
            $gaRole->givePermissionTo($permissions);
        }

        // 7. Security Guard / Satpam Role
        $securityRole = Role::firstOrCreate(['name' => 'Security Guard', 'guard_name' => 'web']);
        $securityRole->givePermissionTo([
            'security_patrol.view',
            'security_patrol.live_map.view',
            'security_patrol.active_patrol.view',
            'security_patrol.active_patrol.create',
            'security_patrol.active_patrol.edit',
        ]);

        // If 'Security' or 'Satpam' role exists, give same permissions
        $otherSecRoles = Role::whereIn('name', ['Security', 'Satpam', 'Petugas Keamanan'])->get();
        foreach ($otherSecRoles as $sec) {
            $sec->givePermissionTo([
                'security_patrol.view',
                'security_patrol.live_map.view',
                'security_patrol.active_patrol.view',
                'security_patrol.active_patrol.create',
                'security_patrol.active_patrol.edit',
            ]);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
