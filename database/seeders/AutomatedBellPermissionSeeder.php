<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AutomatedBellPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // General Module Access
            'automated_bell.view',
            'automated_bell.create',
            'automated_bell.edit',
            'automated_bell.delete',

            // Bell Terminal (Kiosk)
            'automated_bell.bell_terminal.view',
            'automated_bell.bell_terminal.create',
            'automated_bell.bell_terminal.edit',
            'automated_bell.bell_terminal.delete',

            // Schedule Settings
            'automated_bell.schedule_settings.view',
            'automated_bell.schedule_settings.create',
            'automated_bell.schedule_settings.edit',
            'automated_bell.schedule_settings.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. Super Admin gets all permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // 2. IT Administrator gets Automated Bell permissions
        $itAdmin = Role::where('name', 'IT Administrator')->first();
        if ($itAdmin) {
            $itAdmin->givePermissionTo($permissions);
        }

        // 3. HR & Payroll gets Automated Bell permissions
        $hrPayroll = Role::where('name', 'HR & Payroll')->first();
        if ($hrPayroll) {
            $hrPayroll->givePermissionTo($permissions);
        }

        // 4. Owner / Director can view
        $viewPermissions = [
            'automated_bell.view',
            'automated_bell.bell_terminal.view',
            'automated_bell.schedule_settings.view',
        ];

        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $director->givePermissionTo($viewPermissions);
        }

        $owner = Role::where('name', 'Owner')->first();
        if ($owner) {
            $owner->givePermissionTo($viewPermissions);
        }

        // Clear cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
