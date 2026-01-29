<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionalTaxController extends Controller
{
    /**
     * Display the regional & tax settings page
     */
    public function index()
    {
        return Inertia::render('Settings/RegionalTax', [
            'taxRates' => TaxRate::orderBy('name')->get(),
            'settings' => [
                'currency' => AppSetting::get('currency', 'IDR'),
                'currency_symbol' => AppSetting::get('currency_symbol', 'Rp'),
                'decimal_separator' => AppSetting::get('decimal_separator', ','),
                'thousand_separator' => AppSetting::get('thousand_separator', '.'),
                'date_format' => AppSetting::get('date_format', 'd/m/Y'),
                'timezone' => AppSetting::get('timezone', 'Asia/Jakarta'),
            ],
        ]);
    }

    // ... (lines 31-103)

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'decimal_separator' => 'required|string|max:5',
            'thousand_separator' => 'required|string|max:5',
            'date_format' => 'required|string|max:20',
            'timezone' => 'required|string|max:50',
        ]);

        foreach ($validated as $key => $value) {
            AppSetting::set($key, $value, 'regional', ucwords(str_replace('_', ' ', $key)));
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
