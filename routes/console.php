<?php

use Illuminate\Support\Facades\Schedule;

// Dynamic automated database backup scheduler (evaluates user dynamic schedule from UI)
Schedule::command('database:backup-automated')->everyMinute()->withoutOverlapping(60)->runInBackground();
Schedule::command('inventory:check-low-stock')->dailyAt('08:00');
Schedule::command('app:send-weekly-executive-report')->mondays()->at('08:00');
Schedule::command('whatsapp:run-scheduled-reports')->everyMinute();
// Automated Data Lifecycle Archiving: runs weekly on Sunday at 02:00 AM (older than 180 days)
Schedule::command('erp:archive-old-logs --days=180')->weeklyOn(0, '02:00')->runInBackground();
// Schedule::command('purchase-order:fix-creator')->hourly();
