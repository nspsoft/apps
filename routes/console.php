<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\ScheduledBackupJob;

Schedule::job(new ScheduledBackupJob)->dailyAt('00:00');
