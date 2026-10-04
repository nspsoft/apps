<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CctvPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'cctv.view',
            'cctv.manage',
            'cctv.snapshot',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin gets all
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // IT Administrator gets all
        $itAdmin = Role::where('name', 'IT Administrator')->first();
        if ($itAdmin) {
            $itAdmin->givePermissionTo($permissions);
        }

        // Owner & Director get view and snapshot
        $viewPermissions = ['cctv.view', 'cctv.snapshot'];

        $director = Role::where('name', 'Director')->first();
        if ($director) {
            $director->givePermissionTo($viewPermissions);
        }

        $owner = Role::where('name', 'Owner')->first();
        if ($owner) {
            $owner->givePermissionTo($viewPermissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
