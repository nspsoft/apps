<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class SystemPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $preferences = [
            // UI/UX
            'default_theme' => ['value' => 'dark', 'label' => 'Default Theme', 'group' => 'system', 'type' => 'select'],
            'sidebar_collapsed' => ['value' => false, 'label' => 'Sidebar Collapsed Default', 'group' => 'system', 'type' => 'boolean'],
            'items_per_page' => ['value' => 25, 'label' => 'Items Per Page', 'group' => 'system', 'type' => 'select'],
            
            // Inventory
            'auto_update_stock' => ['value' => true, 'label' => 'Auto Update Stock on DO', 'group' => 'system', 'type' => 'boolean'],
            'allow_negative_stock' => ['value' => false, 'label' => 'Allow Negative Stock', 'group' => 'system', 'type' => 'boolean'],
            
            // Sales
            'require_po_number' => ['value' => false, 'label' => 'Require Customer PO Number', 'group' => 'system', 'type' => 'boolean'],
            'default_payment_terms' => ['value' => 'NET 30', 'label' => 'Default Payment Terms', 'group' => 'system', 'type' => 'select'],
            'auto_so_from_quotation' => ['value' => false, 'label' => 'Auto Create SO from Quotation', 'group' => 'system', 'type' => 'boolean'],
            
            // Notifications
            'email_on_new_order' => ['value' => true, 'label' => 'Email on New Order', 'group' => 'system', 'type' => 'boolean'],
            'notify_low_stock' => ['value' => true, 'label' => 'Notify on Low Stock', 'group' => 'system', 'type' => 'boolean'],
            
            // Security
            'session_timeout' => ['value' => 120, 'label' => 'Session Timeout (minutes)', 'group' => 'system', 'type' => 'number'],
        ];

        foreach ($preferences as $key => $data) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => ['value' => $data['value']],
                    'group' => $data['group'],
                    'label' => $data['label'],
                    'type' => $data['type'],
                ]
            );
        }
    }
}
