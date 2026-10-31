<?php

namespace App\Console\Commands;

use App\Models\WhatsappReportSchedule;
use App\Services\InventoryReportGeneratorService;
use App\Services\LogisticsReportGeneratorService;
use App\Services\ManufacturingReportGeneratorService;
use App\Services\PurchasingReportGeneratorService;
use App\Services\SalesReportGeneratorService;
use Illuminate\Console\Command;

class RunWhatsappScheduledReports extends Command
{
    protected $signature = 'whatsapp:run-scheduled-reports {--force-id= : Force run a specific schedule ID}';

    protected $description = 'Evaluate and dispatch scheduled WhatsApp executive reports (PPTX / DOCX) across ERP modules';

    public function handle(
        InventoryReportGeneratorService $inventoryGenerator,
        SalesReportGeneratorService $salesGenerator,
        PurchasingReportGeneratorService $purchasingGenerator,
        ManufacturingReportGeneratorService $manufacturingGenerator,
        LogisticsReportGeneratorService $logisticsGenerator
    ): int {
        $forceId = $this->option('force-id');
        if ($forceId) {
            $schedule = WhatsappReportSchedule::find($forceId);
            if (!$schedule) {
                $this->error("Schedule #{$forceId} tidak ditemukan.");
                return self::FAILURE;
            }
            $this->info("Menjalankan jadwal #{$schedule->id} [{$schedule->module}]: {$schedule->name}...");
            $res = match ($schedule->module) {
                'sales' => $salesGenerator->dispatchScheduledReport($schedule),
                'purchasing' => $purchasingGenerator->dispatchScheduledReport($schedule),
                'manufacturing' => $manufacturingGenerator->dispatchScheduledReport($schedule),
                'logistics' => $logisticsGenerator->dispatchScheduledReport($schedule),
                default => $inventoryGenerator->dispatchScheduledReport($schedule),
            };
            $this->info("Selesai: terkirim ke {$res['sent_count']} tujuan.");
            return self::SUCCESS;
        }

        $now = now()->timezone('Asia/Jakarta');
        $currentHm = $now->format('H:i');
        $currentDayOfWeek = (int) $now->format('N'); // 1 (Mon) .. 7 (Sun)
        $currentDayOfMonth = (int) $now->format('j'); // 1 .. 31

        $schedules = WhatsappReportSchedule::where('is_active', true)->get();
        $dispatched = 0;

        foreach ($schedules as $schedule) {
            $schedHm = substr((string) $schedule->send_time, 0, 5);
            if ($schedHm !== $currentHm) {
                continue;
            }

            if ($schedule->frequency === 'weekly' && (int) $schedule->day_of_week !== $currentDayOfWeek) {
                continue;
            }

            if ($schedule->frequency === 'monthly' && (int) $schedule->day_of_month !== $currentDayOfMonth) {
                continue;
            }

            // Avoid sending twice within the same minute/hour
            if ($schedule->last_sent_at && $schedule->last_sent_at->diffInMinutes($now) < 5) {
                continue;
            }

            $this->info("Dispatching [{$schedule->module}] {$schedule->name}...");
            match ($schedule->module) {
                'sales' => $salesGenerator->dispatchScheduledReport($schedule),
                'purchasing' => $purchasingGenerator->dispatchScheduledReport($schedule),
                'manufacturing' => $manufacturingGenerator->dispatchScheduledReport($schedule),
                'logistics' => $logisticsGenerator->dispatchScheduledReport($schedule),
                default => $inventoryGenerator->dispatchScheduledReport($schedule),
            };
            $dispatched++;
        }

        $this->info("Evaluasi selesai. {$dispatched} laporan dikirim.");
        return self::SUCCESS;
    }
}
