<?php

use Illuminate\Support\Facades\Schedule;
// Dynamic automated database backup scheduler (evaluates user dynamic schedule from UI)
Schedule::command('database:backup-automated')->everyMinute()->withoutOverlapping(60)->runInBackground();
Schedule::command('inventory:check-low-stock')->dailyAt('08:00');
Schedule::command('app:send-weekly-executive-report')->mondays()->at('08:00');
// Schedule::command('purchase-order:fix-creator')->hourly();
