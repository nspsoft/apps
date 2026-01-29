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

    /**
     * Store a new tax rate
     */
    public function storeTaxRate(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tax_rates,code',
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            TaxRate::where('is_default', true)->update(['is_default' => false]);
        }

        TaxRate::create($validated);

        return back()->with('success', 'Tax rate created successfully.');
    }

    /**
     * Update a tax rate
     */
    public function updateTaxRate(Request $request, TaxRate $taxRate)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tax_rates,code,' . $taxRate->id,
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            TaxRate::where('is_default', true)->where('id', '!=', $taxRate->id)->update(['is_default' => false]);
        }

        $taxRate->update($validated);

        return back()->with('success', 'Tax rate updated successfully.');
    }

    /**
     * Delete a tax rate
     */
    public function deleteTaxRate(TaxRate $taxRate)
    {
        $taxRate->delete();

        return back()->with('success', 'Tax rate deleted successfully.');
    }

    /**
     * Update regional/currency settings
     */
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
