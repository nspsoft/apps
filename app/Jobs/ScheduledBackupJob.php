<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Log;

class ScheduledBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DatabaseBackupService $backupService): void
    {
        try {
            Log::info('[ScheduledBackup] Starting daily scheduled backup...');

            // 1. Create Full Backup
            $result = $backupService->createFullBackup();
            
            if ($result['success']) {
                Log::info('[ScheduledBackup] Full backup created successfully: ' . $result['filename']);
            } else {
                Log::error('[ScheduledBackup] Backup failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            // 2. Clean Old Backups (Retention: 30 days)
            $deletedCount = $backupService->cleanOldBackups(30);
            
            if ($deletedCount > 0) {
                Log::info("[ScheduledBackup] Cleaned up {$deletedCount} old backup files.");
            }

        } catch (\Exception $e) {
            Log::error('[ScheduledBackup] Critical error: ' . $e->getMessage());
        }
    }
}
