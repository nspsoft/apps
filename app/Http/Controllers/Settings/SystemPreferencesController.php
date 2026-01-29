<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemPreferencesController extends Controller
{
    /**
     * Display the system preferences page
     */
    public function index()
    {
        return Inertia::render('Settings/SystemPreferences', [
            'preferences' => [
                // UI/UX
                'default_theme' => AppSetting::get('default_theme', 'dark'),
                'sidebar_collapsed' => AppSetting::get('sidebar_collapsed', false),
                'items_per_page' => AppSetting::get('items_per_page', 25),
                
                // Inventory
                'auto_update_stock' => AppSetting::get('auto_update_stock', true),
                'allow_negative_stock' => AppSetting::get('allow_negative_stock', false),
                
                // Sales
                'require_po_number' => AppSetting::get('require_po_number', false),
                'default_payment_terms' => AppSetting::get('default_payment_terms', 'NET 30'),
                'auto_so_from_quotation' => AppSetting::get('auto_so_from_quotation', false),
                
                // Notifications
                'email_on_new_order' => AppSetting::get('email_on_new_order', true),
                'notify_low_stock' => AppSetting::get('notify_low_stock', true),
                
                // Security
                'session_timeout' => AppSetting::get('session_timeout', 120),
            ],
        ]);
    }

    /**
     * Update preferences
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        foreach ($validated['preferences'] as $key => $value) {
            AppSetting::set($key, $value, 'system', ucwords(str_replace('_', ' ', $key)));
        }

        return back()->with('success', 'Preferences saved successfully.');
    }
}
