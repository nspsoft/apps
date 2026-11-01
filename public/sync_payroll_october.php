<?php

// Direct Standalone Runner for October 2026 Payroll Sync
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Reset Spatie permission cache
if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Payroll Oktober 2026 - JICOS ERP</title>
    <style>
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: #0b0f19; color: #f1f5f9; padding: 2rem; line-height: 1.5; }
        .card { max-width: 900px; margin: 0 auto; background: #131b2e; border: 1px solid #1e293b; border-radius: 1rem; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        h1 { font-size: 1.25rem; font-weight: 800; color: #38bdf8; margin-top: 0; display: flex; align-items: center; gap: 0.5rem; }
        pre { background: #050811; padding: 1.25rem; border-radius: 0.75rem; border: 1px solid #1e293b; overflow-x: auto; font-size: 0.85rem; color: #e2e8f0; white-space: pre-wrap; }
        .btn { display: inline-block; background: #4f46e5; color: white; padding: 0.65rem 1.25rem; border-radius: 0.5rem; text-decoration: none; font-weight: bold; font-size: 0.875rem; margin-top: 1rem; transition: background 0.2s; }
        .btn:hover { background: #4338ca; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1>⚡ JICOS ERP - Sinkronisasi Penggajian Oktober 2026</h1>
            <span class="badge-success">Official Script</span>
        </div>
        <p style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 1rem;">
            Menjalankan penyelarasan 54 Master Karyawan, 1.251 Baris Kehadiran (Cutoff 26 Sept - 25 Okt 2026), 627 Lembur Disetujui, dan Generate Otomatis 54 Slip Gaji.
        </p>
        <pre><?php
        try {
            \Illuminate\Support\Facades\Artisan::call('hr:sync-october-payroll', ['--force' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();
            echo htmlspecialchars($output);
            echo "\n================================================================================\n";
            echo "   ✅ SINKRONISASI SELESAI DENGAN SUKSES!\n";
            echo "================================================================================\n";
        } catch (\Throwable $e) {
            echo "❌ ERROR: " . htmlspecialchars($e->getMessage()) . "\n\n";
            echo htmlspecialchars($e->getTraceAsString());
        }
        ?></pre>
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
            <a href="/hr/payroll" class="btn">➔ Buka Halaman Payroll</a>
            <a href="/hr/employees" class="btn" style="background: #334155;">➔ Buka Employee Directory</a>
        </div>
    </div>
</body>
</html>
