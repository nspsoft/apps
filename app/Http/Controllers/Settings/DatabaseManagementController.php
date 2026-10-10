<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class DatabaseManagementController extends Controller
{
    protected $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display database management page
     */
    public function index()
    {
        return Inertia::render('Settings/DatabaseManagement', [
            'modules' => $this->backupService->getModules(),
            'backups' => $this->backupService->getBackupList(),
            'auto_backup' => $this->getAutoBackupConfig(),
        ]);
    }

    /**
     * Create a backup
     */
    public function backup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:full,partial',
            'modules' => 'required_if:type,partial|array',
        ]);

        if ($request->type === 'full') {
            $result = $this->backupService->createFullBackup();
        } else {
            $result = $this->backupService->createPartialBackup($request->modules);
        }

        if ($result['success']) {
            return back()->with('success', 'Backup created successfully: ' . $result['filename']);
        }

        return back()->with('error', 'Backup failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Download a backup file
     */
    public function download(string $filename)
    {
        $filepath = 'backups/' . $filename;
        
        if (!Storage::disk('local')->exists($filepath)) {
            return back()->with('error', 'Backup file not found');
        }

        return Storage::disk('local')->download($filepath, $filename);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename)
    {
        if ($this->backupService->deleteBackup($filename)) {
            return back()->with('success', 'Backup deleted successfully');
        }

        return back()->with('error', 'Failed to delete backup');
    }

    /**
     * Restore from an existing backup
     */
    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $filepath = 'backups/' . $request->filename;
        $result = $this->backupService->restore($filepath);

        if ($result['success']) {
            return back()->with('success', 'Database restored successfully. Pre-restore backup: ' . ($result['pre_restore_backup'] ?? 'N/A'));
        }

        return back()->with('error', 'Restore failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Upload and restore from uploaded file
     */
    public function uploadRestore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // Max 100MB
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        // Store uploaded file
        $file = $request->file('file');
        $filename = 'uploaded_' . date('Y-m-d_H-i-s') . '_' . $file->getClientOriginalName();
        $filepath = $file->storeAs('backups', $filename, 'local');

        $result = $this->backupService->restore($filepath);

        if ($result['success']) {
            return back()->with('success', 'Database restored from uploaded file successfully');
        }

        return back()->with('error', 'Restore failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Soft reset - Clear transaction data
     */
    public function softReset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:SOFT RESET',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $result = $this->backupService->softReset();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', 'Soft reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Hard reset - Reset entire database
     */
    public function hardReset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:HARD RESET',
        ]);

        // Only Super Admin can do hard reset
        if (!auth()->user()->hasRole('Super Admin')) {
            return back()->with('error', 'Only Super Admin can perform hard reset');
        }

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $result = $this->backupService->hardReset();

        if ($result['success']) {
            return redirect()->route('login')->with('success', $result['message']);
        }

        return back()->with('error', 'Hard reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Module reset - Reset specific module
     */
    public function moduleReset(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'password' => 'required|string',
            'mode' => 'nullable|in:soft,hard',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $mode = (string) ($request->input('mode') ?? 'hard');
        $result = $this->backupService->moduleReset($request->module, $mode);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', 'Module reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Get backup info
     */
    public function backupInfo(string $filename)
    {
        $info = $this->backupService->getBackupInfo($filename);
        
        if (!$info) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        return response()->json($info);
    }

    /**
     * Sync Roles & Permissions from seeder
     */
    public function syncPermissions()
    {
        try {
            \Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
            $output = \Artisan::output();
            
            return back()->with('success', 'Roles & Permissions synced successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync Document Numbering defaults
     */
    public function syncDocumentNumbering()
    {
        try {
            \Artisan::call('db:seed', ['--class' => 'DocumentNumberingSeeder', '--force' => true]);
            
            return back()->with('success', 'Document Numbering formats synced successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Run pending migrations
     */
    public function runMigrations()
    {
        try {
            \Artisan::call('migrate', ['--force' => true]);
            $output = \Artisan::output();
            
            return back()->with('success', 'Migrations executed successfully! Output: ' . $output);
        } catch (\Exception $e) {
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches
     */
    public function clearCache()
    {
        try {
            \Artisan::call('optimize:clear');
            
            return back()->with('success', 'All caches cleared successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Get automated backup configuration object
     */
    protected function getAutoBackupConfig(): array
    {
        $enabled = (bool) AppSetting::get('backup_auto_enabled', false);
        $frequency = AppSetting::get('backup_auto_frequency', 'daily');
        $time = AppSetting::get('backup_auto_time', '02:00');
        $intervalHours = (int) AppSetting::get('backup_auto_interval_hours', 6);
        $dayOfWeek = AppSetting::get('backup_auto_day_of_week', 'sunday');
        $retentionDays = (int) AppSetting::get('backup_auto_retention_days', 30);
        $lastRunAt = AppSetting::get('backup_last_run_at', null);
        $lastStatus = AppSetting::get('backup_last_status', null);
        $lastMessage = AppSetting::get('backup_last_message', null);

        return [
            'enabled' => $enabled,
            'frequency' => $frequency,
            'time' => $time,
            'interval_hours' => $intervalHours > 0 ? $intervalHours : 6,
            'day_of_week' => $dayOfWeek ?: 'sunday',
            'retention_days' => $retentionDays > 0 ? $retentionDays : 30,
            'last_run_at' => $lastRunAt,
            'last_status' => $lastStatus,
            'last_message' => $lastMessage,
            'next_run_human' => $this->calculateNextRunHuman($enabled, $frequency, $time, $intervalHours, $dayOfWeek, $lastRunAt),
        ];
    }

    /**
     * Calculate human-readable next run schedule
     */
    protected function calculateNextRunHuman(bool $enabled, string $frequency, string $time, int $intervalHours, string $dayOfWeek, ?string $lastRunAt): string
    {
        if (!$enabled) {
            return 'Jadwal Non-Aktif (Disabled)';
        }

        $tz = 'Asia/Jakarta';
        $now = Carbon::now($tz);

        try {
            switch ($frequency) {
                case 'interval_hours':
                    if ($intervalHours < 1) $intervalHours = 6;
                    if ($lastRunAt) {
                        $last = Carbon::parse($lastRunAt)->setTimezone($tz);
                        $next = $last->copy()->addHours($intervalHours);
                        if ($next->isPast()) {
                            return 'Segera / Dalam Antrean (Due Now)';
                        }
                        return $next->translatedFormat('d M Y, H:i') . ' WIB (' . $next->diffForHumans($now) . ')';
                    }
                    return 'Segera / Dalam Antrean (Due Now)';

                case 'weekly':
                    $parts = explode(':', $time);
                    $hour = isset($parts[0]) ? (int) $parts[0] : 2;
                    $minute = isset($parts[1]) ? (int) $parts[1] : 0;
                    $targetDayNum = [
                        'sunday' => Carbon::SUNDAY,
                        'monday' => Carbon::MONDAY,
                        'tuesday' => Carbon::TUESDAY,
                        'wednesday' => Carbon::WEDNESDAY,
                        'thursday' => Carbon::THURSDAY,
                        'friday' => Carbon::FRIDAY,
                        'saturday' => Carbon::SATURDAY,
                    ][strtolower($dayOfWeek)] ?? Carbon::SUNDAY;

                    $next = $now->copy()->setTime($hour, $minute, 0);
                    if ($now->dayOfWeek === $targetDayNum && $next->isFuture()) {
                        // Today is target day and time is in the future
                    } else {
                        $next->next($targetDayNum);
                    }

                    return $next->translatedFormat('l, d M Y, H:i') . ' WIB (' . $next->diffForHumans($now) . ')';

                case 'daily':
                default:
                    $parts = explode(':', $time);
                    $hour = isset($parts[0]) ? (int) $parts[0] : 2;
                    $minute = isset($parts[1]) ? (int) $parts[1] : 0;
                    $next = $now->copy()->setTime($hour, $minute, 0);
                    if ($next->isPast()) {
                        $next->addDay();
                    }
                    return $next->translatedFormat('d M Y, H:i') . ' WIB (' . $next->diffForHumans($now) . ')';
            }
        } catch (\Throwable $e) {
            return 'Jadwal Aktif';
        }
    }

    /**
     * Save automated backup settings
     */
    public function saveAutoBackupSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:daily,interval_hours,weekly',
            'time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'interval_hours' => 'required|integer|min:1|max:24',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        AppSetting::set('backup_auto_enabled', (bool) $request->enabled, 'backup', 'Enable Automated Database Backup');
        AppSetting::set('backup_auto_frequency', $request->frequency, 'backup', 'Automated Backup Frequency');
        AppSetting::set('backup_auto_time', $request->time, 'backup', 'Automated Backup Execution Time (WIB)');
        AppSetting::set('backup_auto_interval_hours', (int) $request->interval_hours, 'backup', 'Automated Backup Interval in Hours');
        AppSetting::set('backup_auto_day_of_week', $request->day_of_week, 'backup', 'Automated Backup Day of Week for Weekly Schedule');
        AppSetting::set('backup_auto_retention_days', (int) $request->retention_days, 'backup', 'Automated Backup Retention Period in Days');

        return back()->with('success', 'Automated backup settings saved successfully.');
    }

    /**
     * Trigger immediate automated backup run
     */
    public function runAutoBackupNow()
    {
        try {
            $exitCode = Artisan::call('database:backup-automated', ['--force' => true]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return back()->with('success', 'Automated backup executed successfully! ' . trim($output));
            }

            return back()->with('error', 'Backup failed: ' . trim($output));
        } catch (\Exception $e) {
            return back()->with('error', 'Execution error: ' . $e->getMessage());
        }
    }
}
