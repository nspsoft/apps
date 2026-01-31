<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MaintenancePermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Define Permissions
        $permissions = [
            'maintenance.view',
            'maintenance.schedule.view',
            'maintenance.breakdown.view',
            'maintenance.spareparts.view',
        ];

        // 2. Create Permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Assign to Admin Role
        $role = Role::where('name', 'Administrator')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
        
        // Also assign to Super Admin just in case
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
