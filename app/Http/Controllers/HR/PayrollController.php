<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PayrollSetting;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->month ?: Carbon::now()->month;
        $year = $request->year ?: Carbon::now()->year;

        $payrolls = Payroll::with(['employee.department', 'employee.position'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when($request->search, function($query, $search) {
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HR/Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => [
                'month' => (int)$month,
                'year' => (int)$year,
                'search' => $request->search
            ]
        ]);
    }


    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'cutoff_start' => 'nullable|date',
            'cutoff_end' => 'nullable|date',
        ]);

        $month = (int)$request->month;
        $year = (int)$request->year;

        // Cutoff dates: 26th of previous month to 25th of current month
        $cutoffStart = $request->cutoff_start ?: Carbon::create($year, $month, 1)->subMonth()->day(26)->toDateString();
        $cutoffEnd = $request->cutoff_end ?: Carbon::create($year, $month, 25)->toDateString();
        
        $employees = Employee::where('is_active', true)->get();
        $generatedCount = 0;

        // Fetch settings
        $settings = PayrollSetting::where('is_active', true)->get()->keyBy('key');
        $mealRate = (double)($settings['meal_allowance_daily']->value ?? 12500);
        $overtimeMealRate = (double)($settings['overtime_meal_allowance_daily']->value ?? 12500);
        $bpjstkRate = (double)($settings['bpjstk_deduction_rate']->value ?? 3);
        $bpjskesRate = (double)($settings['bpjskes_deduction_rate']->value ?? 1);

        DB::transaction(function () use ($employees, $month, $year, $cutoffStart, $cutoffEnd, $mealRate, $overtimeMealRate, $bpjstkRate, $bpjskesRate, &$generatedCount) {
            foreach ($employees as $employee) {
                if (Payroll::where('employee_id', $employee->id)->where('period_month', $month)->where('period_year', $year)->exists()) {
                    continue;
                }

                // Presences in Cutoff Period
                $presences = Attendance::where('employee_id', $employee->id)
                    ->whereBetween('date', [$cutoffStart, $cutoffEnd])
                    ->get();

                $overtimeRequests = \App\Models\HR\OvertimeRequest::where('employee_id', $employee->id)
                    ->whereBetween('date', [$cutoffStart, $cutoffEnd])
                    ->where('status', 'approved')
                    ->get();

                $presenceByDate = $presences->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());
                $overtimeByDate = $overtimeRequests->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());

                $periodDates = \Carbon\CarbonPeriod::create($cutoffStart, $cutoffEnd);
                $totalWorkingHours = 0;
                $totalOvertimeHours = 0;
                $workingDaysCount = 0;
                $overtimeDaysCount = 0;

                foreach ($periodDates as $dateObj) {
                    $dateStr = $dateObj->toDateString();
                    $att = $presenceByDate->get($dateStr);
                    $ot = $overtimeByDate->get($dateStr);

                    $dailyWorkingHours = 0;
                    $dailyOvertimeHours = 0;

                    if ($att && (in_array($att->status, ['present', 'late']) || !empty($att->clock_in))) {
                        $dailyWorkingHours = 8.0;
                        $workingDaysCount++;
                    }

                    if ($ot && $ot->approved_minutes > 0) {
                        $dailyOvertimeHours = round($ot->approved_minutes / 60, 2);
                    } elseif ($att && $att->overtime_minutes > 0) {
                        $dailyOvertimeHours = round($att->overtime_minutes / 60, 2);
                    }

                    $totalWorkingHours += $dailyWorkingHours;
                    $totalOvertimeHours += $dailyOvertimeHours;

                    if ($dailyOvertimeHours > 0) {
                        $overtimeDaysCount++;
                    }
                }

                // Price/Hour & Basic Salary calculation
                $hourlyRate = 0;
                if ($employee->salary_type === 'hourly') {
                    $hourlyRate = $employee->hourly_rate > 0 ? (double)$employee->hourly_rate : round($employee->basic_salary / 173, 2);
                    $basicSalary = round($totalWorkingHours * $hourlyRate);
                } else {
                    $hourlyRate = $employee->hourly_rate > 0 ? (double)$employee->hourly_rate : round($employee->basic_salary / 173, 2);
                    $basicSalary = (double)$employee->basic_salary;
                }

                // Allowances
                $overtimeAmount = round($totalOvertimeHours * $hourlyRate);
                $mealAllowance = $workingDaysCount * $mealRate;
                $overtimeMealAllowance = $overtimeDaysCount * $overtimeMealRate;

                // Subtotal Gross before BPJS allowance
                $subtotalGross = $basicSalary + $overtimeAmount + $mealAllowance + $overtimeMealAllowance;

                // BPJS allowances (company contribution subsidy) & employee deductions
                $bpjstkAllowance = round($subtotalGross * ($bpjstkRate / 100));
                $bpjskesAllowance = round($subtotalGross * ($bpjskesRate / 100));

                $totalGross = $subtotalGross + $bpjstkAllowance + $bpjskesAllowance;

                $bpjstkDeduction = round($totalGross * ($bpjstkRate / 100));
                $bpjskesDeduction = round($totalGross * ($bpjskesRate / 100));

                $totalDeductions = $bpjstkDeduction + $bpjskesDeduction;
                $netSalary = $totalGross - $totalDeductions;
                // Round take home pay up to nearest Rp 100
                $roundedNetSalary = ceil($netSalary / 100) * 100;

                $totalAllowances = $overtimeAmount + $mealAllowance + $overtimeMealAllowance + $bpjstkAllowance + $bpjskesAllowance;

                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'cutoff_start' => $cutoffStart,
                    'cutoff_end' => $cutoffEnd,
                    'total_working_hours' => $totalWorkingHours,
                    'total_overtime_hours' => $totalOvertimeHours,
                    'total_working_days' => $workingDaysCount,
                    'total_overtime_days' => $overtimeDaysCount,
                    'basic_salary' => $basicSalary,
                    'hourly_rate' => $hourlyRate,
                    'total_allowances' => $totalAllowances,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'rounded_net_salary' => $roundedNetSalary,
                    'status' => 'draft',
                ]);

                // Record Detail Items
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "T.Makan @12.5K/Hari ({$workingDaysCount} hari)",
                    'amount' => $mealAllowance,
                    'type' => 'allowance'
                ]);

                if ($overtimeAmount > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "T.Lembur ({$totalOvertimeHours} jam)",
                        'amount' => $overtimeAmount,
                        'type' => 'allowance'
                    ]);
                }

                if ($overtimeMealAllowance > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "T. Makan Lembur ({$overtimeDaysCount} hari)",
                        'amount' => $overtimeMealAllowance,
                        'type' => 'allowance'
                    ]);
                }

                if ($bpjstkAllowance > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "T. BPJSTK",
                        'amount' => $bpjstkAllowance,
                        'type' => 'allowance'
                    ]);
                }

                if ($bpjskesAllowance > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "T. BPJSKes",
                        'amount' => $bpjskesAllowance,
                        'type' => 'allowance'
                    ]);
                }

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "BPJSTK (3%)",
                    'amount' => $bpjstkDeduction,
                    'type' => 'deduction'
                ]);

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "BPJSKes (1%)",
                    'amount' => $bpjskesDeduction,
                    'type' => 'deduction'
                ]);

                $generatedCount++;
            }
        });

        return redirect()->back()->with('success', "Payroll generated for {$generatedCount} employees (Cutoff: {$cutoffStart} s/d {$cutoffEnd}).");
    }

    public function show(Payroll $payroll): Response
    {
        return Inertia::render('HR/Payroll/Show', [
            'payroll' => $payroll->load(['employee.department', 'employee.position', 'items'])
        ]);
    }

    public function updateStatus(Request $request, Payroll $payroll)
    {
        $request->validate(['status' => 'required|in:confirmed,paid,cancelled']);
        
        $data = ['status' => $request->status];
        if ($request->status === 'paid') {
            $data['payment_date'] = Carbon::now();
        }

        $payroll->update($data);

        return redirect()->back()->with('success', "Payroll status updated to {$request->status}.");
    }

    public function print(Payroll $payroll)
    {
        $payroll->load(['employee.department', 'employee.position', 'items']);

        $cutoffStart = $payroll->cutoff_start ? Carbon::parse($payroll->cutoff_start)->toDateString() : Carbon::create($payroll->period_year, $payroll->period_month, 1)->subMonth()->day(26)->toDateString();
        $cutoffEnd = $payroll->cutoff_end ? Carbon::parse($payroll->cutoff_end)->toDateString() : Carbon::create($payroll->period_year, $payroll->period_month, 25)->toDateString();

        $periodDates = \Carbon\CarbonPeriod::create($cutoffStart, $cutoffEnd);

        $attendances = Attendance::where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        $overtimeRequests = \App\Models\HR\OvertimeRequest::where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->where('status', 'approved')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        return view('print.payslip', [
            'payroll' => $payroll,
            'cutoffStart' => $cutoffStart,
            'cutoffEnd' => $cutoffEnd,
            'periodDates' => $periodDates,
            'attendances' => $attendances,
            'overtimeRequests' => $overtimeRequests,
        ]);
    }

    public function publicValidate($id)
    {
        $payroll = Payroll::with(['employee.department', 'employee.position'])
            ->findOrFail($id);

        return view('print.public-payslip-validation', [
            'payroll' => $payroll
        ]);
    }
}
