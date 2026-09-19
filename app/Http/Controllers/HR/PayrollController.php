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
use App\Models\Department;
use App\Services\HR\PayslipDeliveryService;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->month ?: Carbon::now()->month;
        $year = $request->year ?: Carbon::now()->year;

        $payrolls = Payroll::with(['employee.department', 'employee.position', 'employee.user'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when($request->search, function($query, $search) {
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->when($request->department_id, function($query, $deptId) {
                $query->whereHas('employee', function($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            })
            ->paginate(20)
            ->withQueryString();

        // Calculate delivery stats for this period
        $statsQuery = Payroll::where('period_month', $month)
            ->where('period_year', $year);
        $totalPeriodCount = (clone $statsQuery)->count();
        $waSentCount = (clone $statsQuery)->where('wa_status', 'sent')->count();
        $emailSentCount = (clone $statsQuery)->where('email_status', 'sent')->count();

        // Calculate Cutoff Period for this month & year
        $cutoffStart = Carbon::create($year, $month, 1)->subMonth()->day(26)->toDateString();
        $cutoffEnd = Carbon::create($year, $month, 25)->toDateString();

        // Count pending overtime requests in this cutoff period
        $pendingOvertimeCount = \App\Models\HR\OvertimeRequest::whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->where('status', 'pending')
            ->count();

        return Inertia::render('HR/Payroll/Index', [
            'payrolls' => $payrolls,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'deliveryStats' => [
                'total' => $totalPeriodCount,
                'wa_sent' => $waSentCount,
                'email_sent' => $emailSentCount,
            ],
            'pendingOvertimeCount' => $pendingOvertimeCount,
            'cutoffPeriod' => [
                'start' => $cutoffStart,
                'end' => $cutoffEnd,
            ],
            'filters' => [
                'month' => (int)$month,
                'year' => (int)$year,
                'search' => $request->search,
                'department_id' => $request->department_id ? (int)$request->department_id : null,
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
        $overtimeDivisor = (double)($settings['overtime_divisor']->value ?? 173);

        $lateRule = \App\Models\PenaltyRule::getActiveRule('late');
        $earlyRule = \App\Models\PenaltyRule::getActiveRule('early_leave');

        DB::transaction(function () use ($employees, $month, $year, $cutoffStart, $cutoffEnd, $mealRate, $overtimeMealRate, $bpjstkRate, $bpjskesRate, $overtimeDivisor, $lateRule, $earlyRule, &$generatedCount) {
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
                $totalPenaltyLateMinutes = 0;
                $totalPenaltyEarlyMinutes = 0;

                foreach ($periodDates as $dateObj) {
                    $dateStr = $dateObj->toDateString();
                    $att = $presenceByDate->get($dateStr);
                    $ot = $overtimeByDate->get($dateStr);

                    $dailyWorkingHours = 0;
                    $dailyOvertimeHours = 0;

                    if ($att && (in_array($att->status, ['present', 'late']) || !empty($att->clock_in))) {
                        $workingDaysCount++;

                        // Accumulate penalty minutes
                        $lateM = $att->late_minutes ?? 0;
                        $earlyM = $att->early_leave_minutes ?? 0;

                        $pLateM = ($att->penalty_late_minutes > 0)
                            ? $att->penalty_late_minutes
                            : ($lateRule ? $lateRule->calculatePenaltyMinutes($lateM) : $lateM);

                        $pEarlyM = ($att->penalty_early_leave_minutes > 0)
                            ? $att->penalty_early_leave_minutes
                            : ($earlyRule ? $earlyRule->calculatePenaltyMinutes($earlyM) : $earlyM);

                        $totalPenaltyLateMinutes += $pLateM;
                        $totalPenaltyEarlyMinutes += $pEarlyM;

                        $penaltyHours = round(($pLateM + $pEarlyM) / 60, 2);
                        $dailyWorkingHours = max(0.0, 8.0 - $penaltyHours);
                    }

                    // Overtime calculation: ONLY approved overtime requests are counted (no fallback to biometric)
                    if ($ot && $ot->approved_minutes > 0) {
                        $dailyOvertimeHours = round($ot->approved_minutes / 60, 2);
                    } else {
                        $dailyOvertimeHours = 0.0;
                    }

                    $totalWorkingHours += $dailyWorkingHours;
                    $totalOvertimeHours += $dailyOvertimeHours;

                    // T. Makan Lembur: diberikan jika lembur disetujui dan (jam lembur >= 2.5 jam ATAU jam pulang >= 19:00)
                    $hasOvertimeMeal = false;
                    if ($dailyOvertimeHours > 0) {
                        if ($dailyOvertimeHours >= 2.5) {
                            $hasOvertimeMeal = true;
                        } elseif ($att && !empty($att->clock_out)) {
                            $clockOutTime = Carbon::parse($att->clock_out)->format('H:i:s');
                            if ($clockOutTime >= '19:00:00') {
                                $hasOvertimeMeal = true;
                            }
                        } elseif ($ot && !empty($ot->end_time)) {
                            $otEnd = Carbon::parse($ot->end_time)->format('H:i:s');
                            if ($otEnd >= '19:00:00') {
                                $hasOvertimeMeal = true;
                            }
                        }
                    }

                    if ($hasOvertimeMeal) {
                        $overtimeDaysCount++;
                    }
                }

                // Price/Hour & Basic Salary calculation
                $hourlyRate = 0;
                if ($employee->salary_type === 'hourly') {
                    $hourlyRate = $employee->hourly_rate > 0 ? (double)$employee->hourly_rate : round($employee->basic_salary / $overtimeDivisor, 2);
                    $basicSalary = round($totalWorkingHours * $hourlyRate);
                } else {
                    $hourlyRate = $employee->hourly_rate > 0 ? (double)$employee->hourly_rate : round($employee->basic_salary / $overtimeDivisor, 2);
                    $basicSalary = (double)$employee->basic_salary;
                }

                // Allowances
                $overtimeAmount = round($totalOvertimeHours * $hourlyRate);
                $mealAllowance = $workingDaysCount * $mealRate;
                $overtimeMealAllowance = $overtimeDaysCount * $overtimeMealRate;

                // Subtotal Gross before BPJS allowance
                $subtotalGross = $basicSalary + $overtimeAmount + $mealAllowance + $overtimeMealAllowance;

                // BPJS allowances (company contribution subsidy) & employee deductions
                $hasBpjstk = (bool)($employee->has_bpjstk ?? false);
                $hasBpjskes = (bool)($employee->has_bpjskes ?? false);

                $bpjstkAllowance = $hasBpjstk ? round($subtotalGross * ($bpjstkRate / 100)) : 0;
                $bpjskesAllowance = $hasBpjskes ? round($subtotalGross * ($bpjskesRate / 100)) : 0;

                $totalGross = $subtotalGross + $bpjstkAllowance + $bpjskesAllowance;

                $bpjstkDeduction = $hasBpjstk ? round($totalGross * ($bpjstkRate / 100)) : 0;
                $bpjskesDeduction = $hasBpjskes ? round($totalGross * ($bpjskesRate / 100)) : 0;

                // Late and early leave penalties (for monthly employees; hourly employees already have hours reduced)
                $lateDeduction = 0;
                $earlyDeduction = 0;
                if ($employee->salary_type !== 'hourly') {
                    $lateDeduction = $lateRule 
                        ? $lateRule->calculateDeduction($totalPenaltyLateMinutes, $hourlyRate) 
                        : round(($totalPenaltyLateMinutes / 60) * $hourlyRate, 2);
                    $earlyDeduction = $earlyRule 
                        ? $earlyRule->calculateDeduction($totalPenaltyEarlyMinutes, $hourlyRate) 
                        : round(($totalPenaltyEarlyMinutes / 60) * $hourlyRate, 2);
                }

                $totalPenaltyDeductions = $lateDeduction + $earlyDeduction;

                $totalDeductions = $bpjstkDeduction + $bpjskesDeduction + $totalPenaltyDeductions;
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

                // Dynamic Meal Allowance Label
                $mealRateLabel = ($mealRate >= 1000 && ($mealRate % 1000 == 0)) 
                    ? ($mealRate / 1000) . 'K' 
                    : ($mealRate >= 1000 ? round($mealRate / 1000, 1) . 'K' : number_format($mealRate, 0, ',', '.'));

                // Record Detail Items
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "T.Makan @{$mealRateLabel}/Hari ({$workingDaysCount} hari)",
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

                // Deductions
                if ($bpjstkDeduction > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "BPJSTK ({$bpjstkRate}%)",
                        'amount' => $bpjstkDeduction,
                        'type' => 'deduction'
                    ]);
                }

                if ($bpjskesDeduction > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "BPJSKes ({$bpjskesRate}%)",
                        'amount' => $bpjskesDeduction,
                        'type' => 'deduction'
                    ]);
                }

                if ($lateDeduction > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "Pot. Telat ({$totalPenaltyLateMinutes} mnt)",
                        'amount' => $lateDeduction,
                        'type' => 'deduction'
                    ]);
                }

                if ($earlyDeduction > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "Pot. Pulang Cepat ({$totalPenaltyEarlyMinutes} mnt)",
                        'amount' => $earlyDeduction,
                        'type' => 'deduction'
                    ]);
                }

                $generatedCount++;
            }
        });

        // Count pending overtime requests in this cutoff period
        $pendingOvertimeCount = \App\Models\HR\OvertimeRequest::whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->where('status', 'pending')
            ->count();

        if ($pendingOvertimeCount > 0) {
            return redirect()->back()->with('warning', "Payroll berhasil digenerate untuk {$generatedCount} karyawan (Cutoff: {$cutoffStart} s/d {$cutoffEnd}). PERINGATAN: Masih ada {$pendingOvertimeCount} pengajuan lembur yang BELUM DISETUJUI (Pending) pada periode ini sehingga belum masuk ke hitungan lembur.");
        }

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
        $payroll->load(['employee.department', 'employee.position', 'employee.workSchedule.details', 'items']);

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

        $settings = PayrollSetting::all()->keyBy('key');

        return view('print.payslip', [
            'payroll' => $payroll,
            'cutoffStart' => $cutoffStart,
            'cutoffEnd' => $cutoffEnd,
            'periodDates' => $periodDates,
            'attendances' => $attendances,
            'overtimeRequests' => $overtimeRequests,
            'settings' => $settings,
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

    public function downloadPdf(Payroll $payroll, PayslipDeliveryService $deliveryService)
    {
        $filePath = $deliveryService->generatePdf($payroll);
        $filename = "Slip_Gaji_{$payroll->employee->nik}_{$payroll->period_year}_{$payroll->period_month}.pdf";

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function sendSingle(Request $request, Payroll $payroll, PayslipDeliveryService $deliveryService)
    {
        $request->validate([
            'channel' => 'required|in:whatsapp,email,both',
        ]);

        $result = $deliveryService->sendSingle($payroll, $request->channel);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', "Slip gaji berhasil dikirim ke {$payroll->employee->name} melalui {$request->channel}.");
        }

        $errorMsg = !empty($result['errors']) ? implode(', ', $result['errors']) : 'Terjadi kendala saat mengirim';
        return redirect()->back()->with('error', "Gagal mengirim slip gaji: {$errorMsg}");
    }

    public function sendBulk(Request $request, PayslipDeliveryService $deliveryService)
    {
        $request->validate([
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2000',
            'channel' => 'required|in:whatsapp,email,both',
            'department_id' => 'nullable',
            'payroll_ids' => 'nullable|array',
            'payroll_ids.*' => 'exists:hr_payrolls,id',
        ]);

        $query = Payroll::with(['employee.department', 'employee.user'])
            ->where('period_month', $request->period_month)
            ->where('period_year', $request->period_year);

        if (!empty($request->payroll_ids)) {
            $query->whereIn('id', $request->payroll_ids);
        } elseif ($request->department_id && $request->department_id !== 'all') {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $payrolls = $query->get();

        if ($payrolls->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'message' => 'Tidak ada data payroll yang ditemukan untuk periode dan filter ini.'
                ], 422);
            }
            return redirect()->back()->with('error', 'Tidak ada data payroll yang ditemukan untuk dikirim.');
        }

        $results = $deliveryService->sendBatch($payrolls, $request->channel);

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        return redirect()->back()->with('success', "Selesai memproses pengiriman massal: {$results['success']} berhasil, {$results['failed']} gagal.");
    }
}
