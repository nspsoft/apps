<?php

namespace App\Services\HR;

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\HR\OvertimeRequest;
use App\Models\Company;
use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Services\FonnteService;
use App\Services\WablasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PayslipDeliveryService
{
    /**
     * Generate or retrieve the PDF file path for a payroll record.
     */
    public function generatePdf(Payroll $payroll, bool $force = false): string
    {
        $payroll->loadMissing(['employee.department', 'employee.position', 'employee.workSchedule.details', 'items']);

        $filename = 'payslip_' . $payroll->id . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $payroll->employee->nik) . '_' . $payroll->period_year . '_' . sprintf('%02d', $payroll->period_month) . '.pdf';
        $relativeDir = 'payslips';
        $storageDir = storage_path('app/public/' . $relativeDir);

        if (!File::isDirectory($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        $fullPath = $storageDir . '/' . $filename;

        // If already generated and not forced, return existing
        if (!$force && File::exists($fullPath) && !empty($payroll->pdf_path)) {
            return $fullPath;
        }

        // Prepare data for the print.payslip Blade view
        $cutoffStart = $payroll->cutoff_start ? Carbon::parse($payroll->cutoff_start)->toDateString() : Carbon::create($payroll->period_year, $payroll->period_month, 1)->subMonth()->day(26)->toDateString();
        $cutoffEnd = $payroll->cutoff_end ? Carbon::parse($payroll->cutoff_end)->toDateString() : Carbon::create($payroll->period_year, $payroll->period_month, 25)->toDateString();
        $periodDates = CarbonPeriod::create($cutoffStart, $cutoffEnd);

        $attendances = Attendance::where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        $overtimeRequests = OvertimeRequest::where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->where('status', 'approved')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        $company = Company::first();

        $pdf = Pdf::loadView('print.payslip', [
            'payroll' => $payroll,
            'cutoffStart' => $cutoffStart,
            'cutoffEnd' => $cutoffEnd,
            'periodDates' => $periodDates,
            'attendances' => $attendances,
            'overtimeRequests' => $overtimeRequests,
            'company' => $company,
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption(['isRemoteEnabled' => true]);

        File::put($fullPath, $pdf->output());

        $payroll->update([
            'pdf_path' => $relativeDir . '/' . $filename
        ]);

        return $fullPath;
    }

    /**
     * Get the publicly accessible URL for the generated PDF.
     */
    public function getPdfUrl(Payroll $payroll): string
    {
        $this->generatePdf($payroll);
        return asset('storage/' . $payroll->pdf_path);
    }

    /**
     * Format a polite and professional WhatsApp notification message.
     */
    public function formatWhatsappMessage(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.department', 'employee.position']);
        $employee = $payroll->employee;

        $startDate = $payroll->cutoff_start ? Carbon::parse($payroll->cutoff_start)->format('d M Y') : '-';
        $endDate = $payroll->cutoff_end ? Carbon::parse($payroll->cutoff_end)->format('d M Y') : '-';
        $thp = number_format($payroll->rounded_net_salary ?: $payroll->net_salary, 0, ',', '.');
        $dept = $employee->department?->name ?? '-';

        return "*SLIP GAJI & REKAP KEHADIRAN*\n"
            . "*PT. JIDOKA RESULT INDONESIA*\n"
            . "----------------------------------------\n"
            . "Yth. Bpk/Ibu *{$employee->full_name}*\n"
            . "NIK: {$employee->nik}\n"
            . "Departemen: {$dept}\n\n"
            . "Berikut rincian ringkasan penggajian Anda untuk periode:\n"
            . "📅 *{$startDate} s/d {$endDate}*\n\n"
            . "💵 *Take Home Pay (THP):* Rp {$thp}\n"
            . "🏢 *Hari Kerja Masuk:* {$payroll->total_working_days} Hari\n"
            . "⏱️ *Jam Kerja:* {$payroll->total_working_hours} Jam\n"
            . "✨ *Lembur:* {$payroll->total_overtime_hours} Jam ({$payroll->total_overtime_days} Hari)\n\n"
            . "📄 Dokumen resmi Slip Gaji & Rekap Absensi lengkap terlampir dalam bentuk PDF.\n\n"
            . "_Jika terdapat pertanyaan terkait rincian ini, silakan hubungi tim HRD._\n\n"
            . "-- HRD PT. Jidoka Result Indonesia --";
    }

    /**
     * Send payslip via WhatsApp to the employee.
     */
    public function sendWhatsapp(Payroll $payroll): array
    {
        $payroll->loadMissing('employee');
        $employee = $payroll->employee;

        // Find best phone number
        $phone = $employee->phone;
        if (empty($phone) && $employee->user) {
            $phone = $employee->user->phone ?? null;
        }

        if (empty($phone)) {
            $payroll->update(['wa_status' => 'failed']);
            return [
                'success' => false,
                'message' => "Nomor telepon/WhatsApp untuk {$employee->full_name} belum terdaftar.",
            ];
        }

        try {
            $fullPath = $this->generatePdf($payroll);
            $pdfUrl = $this->getPdfUrl($payroll);
            $caption = $this->formatWhatsappMessage($payroll);

            $hrMode = AppSetting::get('hr_whatsapp_mode', 'same_as_sales');
            if ($hrMode === 'dedicated') {
                $provider = AppSetting::get('hr_whatsapp_provider', 'fonnte');
                if ($provider === 'wablas') {
                    $token = AppSetting::get('hr_wablas_api_token', '');
                    $url = AppSetting::get('hr_wablas_server_url', 'https://pati.wablas.com');
                    $gateway = app(WablasService::class)->setCredentials($token, $url);
                } else {
                    $token = AppSetting::get('hr_fonnte_api_token', '');
                    $gateway = app(FonnteService::class)->setCredentials($token);
                }
            } else {
                $provider = AppSetting::get('whatsapp_provider', 'fonnte');
                $gateway = match ($provider) {
                    'wablas' => app(WablasService::class),
                    default => app(FonnteService::class),
                };
            }

            // Send document via gateway
            $result = $gateway->sendFile($phone, $pdfUrl, $caption);

            // If sending file directly returns error (e.g. localhost URL not publicly reachable by external gateway),
            // fallback to sending text message with information
            if (!($result['success'] ?? false)) {
                Log::warning("Gateway sendFile failed, attempting text message fallback: " . ($result['error'] ?? 'unknown error'));
                $textResult = $gateway->sendMessage($phone, $caption);
                if ($textResult['success'] ?? false) {
                    $payroll->update([
                        'wa_sent_at' => now(),
                        'wa_status' => 'sent',
                    ]);

                    // Log to HR WhatsApp messages
                    WhatsappMessage::create([
                        'phone' => $phone,
                        'employee_id' => $employee->id,
                        'direction' => 'outgoing',
                        'message' => $caption,
                        'intent' => 'payslip',
                        'is_read' => true,
                        'module' => 'hr',
                        'metadata' => [
                            'type' => 'text_fallback',
                            'payroll_id' => $payroll->id,
                            'delivery_success' => true,
                        ],
                    ]);

                    return [
                        'success' => true,
                        'message' => "Pesan teks berhasil dikirim ke WhatsApp {$employee->full_name} ({$phone}).",
                        'data' => $textResult,
                    ];
                }
                
                $payroll->update(['wa_status' => 'failed']);
                return [
                    'success' => false,
                    'message' => "Gagal mengirim WhatsApp ke {$phone}: " . ($result['error'] ?? 'Kesalahan gateway'),
                ];
            }

            $payroll->update([
                'wa_sent_at' => now(),
                'wa_status' => 'sent',
            ]);

            // Log to HR WhatsApp messages
            WhatsappMessage::create([
                'phone' => $phone,
                'employee_id' => $employee->id,
                'direction' => 'outgoing',
                'message' => $caption,
                'intent' => 'payslip',
                'is_read' => true,
                'module' => 'hr',
                'metadata' => [
                    'type' => 'document',
                    'name' => basename($fullPath),
                    'url' => $pdfUrl,
                    'size' => file_exists($fullPath) ? filesize($fullPath) : null,
                    'delivery_success' => true,
                    'payroll_id' => $payroll->id,
                ],
            ]);

            return [
                'success' => true,
                'message' => "Slip gaji PDF berhasil dikirim ke WhatsApp {$employee->full_name} ({$phone}).",
                'data' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error("Payslip WhatsApp Send Error [Payroll ID: {$payroll->id}]: " . $e->getMessage());
            $payroll->update(['wa_status' => 'failed']);
            return [
                'success' => false,
                'message' => "Error saat mengirim WhatsApp: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Send payslip via Email to the employee.
     */
    public function sendEmail(Payroll $payroll): array
    {
        $payroll->loadMissing(['employee.department', 'employee.position', 'items']);
        $employee = $payroll->employee;

        // Find email address
        $email = $employee->email;
        if (empty($email) && $employee->user) {
            $email = $employee->user->email;
        }

        if (empty($email)) {
            $payroll->update(['email_status' => 'failed']);
            return [
                'success' => false,
                'message' => "Alamat email untuk {$employee->full_name} belum terdaftar.",
            ];
        }

        try {
            $pdfPath = $this->generatePdf($payroll);
            $company = Company::first();
            $periodStr = Carbon::create($payroll->period_year, $payroll->period_month, 1)->translatedFormat('F Y');

            Mail::send('emails.payslip', [
                'payroll' => $payroll,
                'employee' => $employee,
                'company' => $company,
            ], function ($message) use ($email, $employee, $periodStr, $pdfPath) {
                $message->to($email, $employee->full_name)
                    ->subject("Slip Gaji & Rekap Kehadiran {$periodStr} - {$employee->full_name}")
                    ->attach($pdfPath, [
                        'as' => 'Slip_Gaji_' . preg_replace('/[^A-Za-z0-9]/', '_', $employee->nik) . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });

            $payroll->update([
                'email_sent_at' => now(),
                'email_status' => 'sent',
            ]);

            return [
                'success' => true,
                'message' => "Slip gaji berhasil dikirim ke email {$employee->full_name} ({$email}).",
            ];
        } catch (\Throwable $e) {
            Log::error("Payslip Email Send Error [Payroll ID: {$payroll->id}]: " . $e->getMessage());
            $payroll->update(['email_status' => 'failed']);
            return [
                'success' => false,
                'message' => "Error saat mengirim email: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Send single payslip via selected channel ('wa', 'whatsapp', 'email', or 'both').
     */
    public function sendSingle(Payroll $payroll, string $channel = 'both'): array
    {
        $results = [];

        if ($channel === 'wa' || $channel === 'whatsapp' || $channel === 'both') {
            $results['whatsapp'] = $this->sendWhatsapp($payroll);
        }

        if ($channel === 'email' || $channel === 'both') {
            $results['email'] = $this->sendEmail($payroll);
        }

        $allSuccess = true;
        $messages = [];
        $errors = [];
        foreach ($results as $ch => $res) {
            if (!($res['success'] ?? false)) {
                $allSuccess = false;
                $errors[] = strtoupper($ch) . ': ' . ($res['message'] ?? 'Gagal');
            }
            $messages[] = strtoupper($ch) . ': ' . ($res['message'] ?? '');
        }

        return [
            'success' => $allSuccess,
            'status' => $allSuccess ? 'success' : 'error',
            'message' => implode(' | ', $messages),
            'errors' => $errors,
            'details' => $results,
        ];
    }

    /**
     * Send bulk payslips for an array of payroll IDs or an Eloquent Collection.
     */
    public function sendBatch($payrollsOrIds, string $channel = 'both'): array
    {
        if ($payrollsOrIds instanceof \Illuminate\Database\Eloquent\Collection) {
            $payrolls = $payrollsOrIds;
        } elseif (is_array($payrollsOrIds)) {
            $payrolls = Payroll::with(['employee.department', 'employee.position', 'employee.user'])->whereIn('id', $payrollsOrIds)->get();
        } else {
            $payrolls = collect();
        }

        $total = $payrolls->count();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $logs = [];

        foreach ($payrolls as $payroll) {
            $res = $this->sendSingle($payroll, $channel);
            if ($res['success']) {
                $successCount++;
            } else {
                $failedCount++;
                $errors[] = "{$payroll->employee->full_name}: " . implode(', ', $res['errors']);
            }

            $logs[] = [
                'payroll_id' => $payroll->id,
                'employee_name' => $payroll->employee->full_name,
                'success' => $res['success'],
                'message' => $res['message'],
            ];

            // Safe throttling between messages if sending via WA (protect gateway phone number)
            if ($channel === 'wa' || $channel === 'whatsapp' || $channel === 'both') {
                usleep(300000); // 0.3s delay between messages in batch
            }
        }

        return [
            'total' => $total,
            'success' => $successCount,
            'failed' => $failedCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
            'logs' => $logs,
        ];
    }
}
