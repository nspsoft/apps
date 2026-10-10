<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseBackupService;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DatabaseBackupAutomatedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup-automated {--force : Force execute backup regardless of schedule/enabled status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute dynamic automated database backup and retention cleanup based on system settings';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $isForce = $this->option('force');
        $tz = 'Asia/Jakarta';
        $now = Carbon::now($tz);

        // 1. Check if enabled when not forced
        if (!$isForce) {
            $isEnabled = (bool) AppSetting::get('backup_auto_enabled', false);
            if (!$isEnabled) {
                $this->info('Automated database backup is currently DISABLED in settings.');
                return Command::SUCCESS;
            }

            // Check if it's due to run
            if (!$this->isDueToRun($now)) {
                $this->info('Backup is enabled but not due to run at this time.');
                return Command::SUCCESS;
            }
        }

        $this->info('[' . $now->toDateTimeString() . '] Starting automated database backup process...');
        Log::info('[AutomatedBackup] Starting backup process. Forced: ' . ($isForce ? 'YES' : 'NO'));

        try {
            // 2. Perform Full Backup
            $result = $backupService->createFullBackup();

            if (!$result['success']) {
                $errorMessage = $result['error'] ?? 'Unknown backup error';
                $this->error('Backup failed: ' . $errorMessage);
                Log::error('[AutomatedBackup] Backup failed: ' . $errorMessage);

                AppSetting::set('backup_last_run_at', $now->toIso8601String(), 'backup', 'Last Automated Backup Run Timestamp');
                AppSetting::set('backup_last_status', 'failed', 'backup', 'Last Automated Backup Status');
                AppSetting::set('backup_last_message', $errorMessage, 'backup', 'Last Automated Backup Message');

                return Command::FAILURE;
            }

            $filename = $result['filename'];
            $fileSize = isset($result['size']) ? $this->formatBytes($result['size']) : 'N/A';
            $this->info("Backup created successfully: {$filename} ({$fileSize})");

            // 3. Perform Retention Cleanup
            $retentionDays = (int) AppSetting::get('backup_auto_retention_days', 30);
            if ($retentionDays <= 0) {
                $retentionDays = 30;
            }

            $cleanedCount = $backupService->cleanOldBackups($retentionDays);
            $cleanupMsg = $cleanedCount > 0 ? " Cleaned up {$cleanedCount} backup(s) older than {$retentionDays} days." : "";

            $summaryMessage = "Backup created: {$filename} ({$fileSize})." . $cleanupMsg;
            $this->info($summaryMessage);
            Log::info('[AutomatedBackup] ' . $summaryMessage);

            // 4. Update Status in AppSetting
            AppSetting::set('backup_last_run_at', $now->toIso8601String(), 'backup', 'Last Automated Backup Run Timestamp');
            AppSetting::set('backup_last_status', 'success', 'backup', 'Last Automated Backup Status');
            AppSetting::set('backup_last_message', $summaryMessage, 'backup', 'Last Automated Backup Message');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Exception occurred during automated backup: ' . $e->getMessage());
            Log::error('[AutomatedBackup] Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            AppSetting::set('backup_last_run_at', $now->toIso8601String(), 'backup', 'Last Automated Backup Run Timestamp');
            AppSetting::set('backup_last_status', 'failed', 'backup', 'Last Automated Backup Status');
            AppSetting::set('backup_last_message', $e->getMessage(), 'backup', 'Last Automated Backup Message');

            return Command::FAILURE;
        }
    }

    /**
     * Check if backup is due to run based on dynamic settings
     */
    protected function isDueToRun(Carbon $now): bool
    {
        $frequency = AppSetting::get('backup_auto_frequency', 'daily');
        $targetTime = AppSetting::get('backup_auto_time', '02:00'); // 'HH:mm'
        $lastRunAtStr = AppSetting::get('backup_last_run_at', null);
        $lastRunAt = $lastRunAtStr ? Carbon::parse($lastRunAtStr)->setTimezone('Asia/Jakarta') : null;

        $currentTimeStr = $now->format('H:i');

        switch ($frequency) {
            case 'interval_hours':
                $intervalHours = (int) AppSetting::get('backup_auto_interval_hours', 6);
                if ($intervalHours < 1) $intervalHours = 6;

                if (!$lastRunAt) {
                    return true;
                }

                return $now->diffInHours($lastRunAt) >= $intervalHours;

            case 'weekly':
                $targetDay = strtolower(AppSetting::get('backup_auto_day_of_week', 'sunday'));
                $currentDay = strtolower($now->format('l'));

                // Must be target day
                if ($currentDay !== $targetDay) {
                    return false;
                }

                // Must match target time (within current minute)
                if ($currentTimeStr !== $targetTime) {
                    return false;
                }

                // Ensure it hasn't already run in the last 20 hours to prevent duplicate runs
                if ($lastRunAt && $now->diffInHours($lastRunAt) < 20) {
                    return false;
                }

                return true;

            case 'daily':
            default:
                // Must match target time (within current minute)
                if ($currentTimeStr !== $targetTime) {
                    return false;
                }

                // Ensure it hasn't already run today
                if ($lastRunAt && $lastRunAt->isSameDay($now)) {
                    return false;
                }

                return true;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
