<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $company = Company::first();
        return Inertia::render('Settings/CompanyProfile', [
            'company' => $company
        ]);
    }

    public function update(Request $request)
    {
        $company = Company::first() ?? new Company();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:255',
            'logo_file' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo_file')) {
            if ($company->logo && str_starts_with($company->logo, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $company->logo);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo_file')->store('logos', 'public');
            $validated['logo'] = '/storage/' . $path;
        }

        $company->fill($validated);
        
        if (!$company->code) {
            $company->code = 'COMP-' . strtoupper(substr(uniqid(), -4));
        }

        $company->save();

        return redirect()->back()->with('success', 'Company profile updated successfully.');
    }
}
