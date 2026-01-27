<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $structure = [
            'Sales & CRM' => [
                'Customers', 'Quotations', 'Sales Orders', 'Delivery Orders', 'Invoices', 'Sales Returns',
                'Leads Management', 'Opportunity Tracking', 'Marketing Campaigns'
            ],
            'Purchasing' => [
                'Suppliers', 'Purchase Requests', 'Purchase Orders', 'Goods Receipts', 'Purchase Invoices', 'Purchase Returns'
            ],
            'Inventory' => [
                'Categories', 'Products', 'Current Stock', 'Warehouses', 'Stock Movements', 'Stock Opname'
            ],
            'Manufacturing' => [
                'Bill of Materials', 'Work Orders', 'Production', 'Input Output', 'Shift Management', 'Machine Management', 'Subcontract Orders'
            ],
            'QC' => [
                'Incoming Inspection', 'In-Process QC', 'Quality Checklists'
            ],
            'Logistics' => [
                'Delivery Planning', 'Vehicle Fleet'
            ],
            'Finance' => [
                'General Ledger', 'Profit & Loss', 'AP & AR Monitoring', 'Production Costing', 'Overhead Allocation', 'Profitability Analytic'
            ],
            'HR & Payroll' => [
                'Employee Directory', 'Attendance', 'Payroll'
            ],
            'Settings' => [
                'User Management', 'Roles & Permissions', 'Company Profile', 'Document Numbering', 'Regional & Tax', 'System Preferences', 'Workflow Approval', 'Import & Export', 'Activity Logs'
            ]
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($structure as $module => $menus) {
            $moduleKey = $this->slugify($module);
            
            // Add a general module access permission
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$moduleKey}.{$action}"]);
            }

            // Add granular menu access permissions
            foreach ($menus as $menu) {
                $menuKey = $this->slugify($menu);
                foreach ($actions as $action) {
                    Permission::firstOrCreate(['name' => "{$moduleKey}.{$menuKey}.{$action}"]);
                }
            }
        }

        $roles = [
            'Super Admin',
            'Sales Manager',
            'Purchasing Manager',
            'Production Manager',
            'Quality Control',
            'Inventory Staff',
            'Finance',
            'HR & Payroll'
        ];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            if ($roleName === 'Super Admin') {
                $role->syncPermissions(Permission::all());
            }
        }

        // Assign Super Admin to potential admins
        $users = User::whereIn('email', ['test@example.com', 'admin@jicos.com'])->get();
        foreach ($users as $user) {
            if (!$user->hasRole('Super Admin')) {
                $user->assignRole('Super Admin');
            }
        }
    }

    private function slugify($text)
    {
        return strtolower(str_replace([' & ', ' '], ['_', '_'], $text));
    }
}
