<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('HR/Payroll/Settings', [
            'settings' => PayrollSetting::all()->groupBy('category'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:hr_payroll_settings,id',
            'settings.*.value' => 'required',
        ]);

        foreach ($request->settings as $settingData) {
            PayrollSetting::where('id', $settingData['id'])->update([
                'value' => $settingData['value']
            ]);
        }

        return redirect()->back()->with('success', 'Payroll configurations updated successfully.');
    }
}
