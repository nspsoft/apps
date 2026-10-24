<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveBalance;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class EmployeeSelfServicePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Granular Permissions for Employee Self Service
        $modules = [
            'attendance',
            'timeoff',
            'overtime',
            'reimbursements',
            'performance'
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        Permission::firstOrCreate(['name' => 'employee_self_service.view']);

        foreach ($modules as $mod) {
            foreach ($actions as $act) {
                Permission::firstOrCreate(['name' => "employee_self_service.{$mod}.{$act}"]);
            }
        }

        // Reset Spatie cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Grant all permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        // 3. Grant Employee Self Service permissions to all roles
        $allRoles = Role::all();
        $essPermissions = Permission::where('name', 'like', 'employee_self_service.%')->get();
        foreach ($allRoles as $role) {
            $role->givePermissionTo($essPermissions);
        }

        // 4. Ensure Administrator user (ID 1) has an Employee profile
        $adminUser = User::where('email', 'admin@jidoka.co.id')->first() ?? User::find(1);
        if ($adminUser) {
            $dirDept = Department::where('name', 'DIRECTOR')->first() ?? Department::first();
            $mgrPos = Position::where('name', 'Manager')->first() ?? Position::first();

            $adminEmployee = Employee::where('user_id', $adminUser->id)->first();
            if (!$adminEmployee) {
                $adminEmployee = Employee::create([
                    'user_id' => $adminUser->id,
                    'nik' => 'JRI-ADMIN-001',
                    'full_name' => 'Administrator',
                    'email' => $adminUser->email,
                    'phone' => '081234567890',
                    'address' => 'Kantor PT Jidoka Result Indonesia',
                    'department_id' => $dirDept ? $dirDept->id : null,
                    'position_id' => $mgrPos ? $mgrPos->id : null,
                    'joining_date' => '2020-01-01',
                    'employment_status' => 'permanent',
                    'is_active' => true,
                ]);
            }

            // Grant Annual Leave Balance for 2026
            $annualLeave = LeaveType::where('name', 'Annual Leave')->first();
            if ($annualLeave && $adminEmployee) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $adminEmployee->id,
                        'leave_type_id' => $annualLeave->id,
                        'year' => Carbon::now()->year,
                    ],
                    [
                        'total_days' => 12,
                        'used_days' => 0,
                    ]
                );
            }
        }

        // 5. Link named users to their actual employees
        $userEmpMap = [
            'jahrudin@jidoka.co.id' => 118, // JAHRUDIN
            'nanang@jidoka.co.id' => 70,    // NANANG MULYANA
            'agus@jidoka.co.id' => 67,      // AGUS SUPRIYANTO
            'santi@jidoka.co.id' => 68,     // ELY SUSANTI
            'andi@jidoka.co.id' => 96,      // ANDI SUHANDI
            'amur@jidoka.co.id' => 99,      // AMUR
            'rustam@jidoka.co.id' => 104,   // RUSTAM NAWAWI
            'zainul@jidoka.co.id' => 110,   // ZAINUL KHAQ
            'noviardi@jidoka.co.id' => 95,  // NOVIARDI FADILATUL RAHMAN
            'accounting@jidoka.co.id' => 66,// AHMAD HASANUDIN
            'mouriceningrum23@gmail.com' => 69, // MOURICE NINGRUM
        ];

        foreach ($userEmpMap as $email => $empId) {
            $user = User::where('email', $email)->first();
            $emp = Employee::find($empId);
            if ($user && $emp) {
                $emp->update(['user_id' => $user->id]);
            }
        }

        // Reset permission cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
