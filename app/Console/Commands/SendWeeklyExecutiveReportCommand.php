<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExecutiveReportService;

class SendWeeklyExecutiveReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-weekly-executive-report {--dry-run : Generate report PDF but do not send via WhatsApp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the weekly executive report (Sales & Purchasing) and send the PDF to registered owners via WhatsApp.';

    /**
     * Execute the console command.
     */
    public function handle(ExecutiveReportService $reportService): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Starting Executive Report generation in DRY RUN mode...');
        } else {
            $this->info('Starting Weekly Executive Report generation and delivery...');
        }

        $result = $reportService->generateAndSendWeeklyReport($dryRun);

        if ($result['success']) {
            $this->info($result['message']);
            if ($result['pdf_url']) {
                $this->line("PDF URL: " . $result['pdf_url']);
            }
            return Command::SUCCESS;
        } else {
            $this->error('Failed: ' . $result['message']);
            return Command::FAILURE;
        }
    }
}
