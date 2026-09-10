<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LinkUserEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Link matching users to employees
        $userMap = [
            'jahrudin@jidoka.co.id' => 120, // JAHRUDIN
            'nanang@jidoka.co.id' => 72,    // NANANG MULYANA
            'agus@jidoka.co.id' => 69,      // AGUS SUPRIYANTO
            'santi@jidoka.co.id' => 70,     // ELY SUSANTI
            'andi@jidoka.co.id' => 85,      // ARIEF ROSANDI
            'amur@jidoka.co.id' => 101,     // AMUR
            'rustam@jidoka.co.id' => 106,   // RUSTAM NAWAWI
            'zainul@jidoka.co.id' => 112,   // ZAINUL KHAQ
            'noviardi@jidoka.co.id' => 97,  // NOVIARDI
            'accounting@jidoka.co.id' => 68,// AHMAD HASANUDIN
            'mouriceningrum23@gmail.com' => 71, // MOURICE NINGRUM
        ];

        foreach ($userMap as $email => $empId) {
            $user = User::where('email', $email)->first();
            $employee = Employee::find($empId);
            if ($user && $employee) {
                $employee->update(['user_id' => $user->id]);
            }
        }

        // 2. Ensure permissions exist for hr_payroll.work_schedules
        $actions = ['view', 'create', 'edit', 'delete'];
        foreach ($actions as $act) {
            Permission::firstOrCreate(['name' => "hr_payroll.work_schedules.{$act}"]);
        }

        // Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 3. Grant full permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        // 4. Grant HR & Payroll role full hr_payroll permissions
        $hrRole = Role::where('name', 'HR & Payroll')->first();
        if ($hrRole) {
            $hrPerms = Permission::where('name', 'like', 'hr_payroll.%')
                ->orWhere('name', 'like', 'general_affair.%')
                ->get();
            $hrRole->syncPermissions($hrPerms);
        }

        // 5. Grant Owner role full HR access
        $ownerRole = Role::where('name', 'Owner')->first();
        if ($ownerRole) {
            $ownerHrPerms = Permission::where('name', 'like', 'hr_payroll.%')->get();
            $ownerRole->givePermissionTo($ownerHrPerms);
        }

        // 6. Ensure Mourice (HRGA Admin) has HR & Payroll role
        $mourice = User::where('email', 'mouriceningrum23@gmail.com')->first();
        if ($mourice && $hrRole && !$mourice->hasRole('HR & Payroll')) {
            $mourice->assignRole('HR & Payroll');
        }
    }
}
