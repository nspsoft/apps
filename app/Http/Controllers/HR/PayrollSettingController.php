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
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('hr_payroll.payroll.edit'))) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola pengaturan Payroll.');
        }

        return Inertia::render('HR/Payroll/Settings', [
            'settings' => PayrollSetting::all()->groupBy('category'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('hr_payroll.payroll.edit'))) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola pengaturan Payroll.');
        }

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
