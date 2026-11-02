<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArchiveOldLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:archive-old-logs 
                            {--days=180 : Umur log dalam hari sebelum diarsipkan/dibersihkan (default: 180 hari)}
                            {--type=all : Target yang diproses (activity_log, whatsapp_messages, all)}
                            {--dry-run : Menghitung data tanpa menghapus (simulasi)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arsipkan dan bersihkan log aktivitas & metadata lama untuk menjaga performa jutaan data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $type = strtolower($this->option('type'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days)->toDateTimeString();

        $this->info("=== JICOS ERP DATA LIFECYCLE & ARCHIVING ENGINE ===");
        $this->info("Batas Tanggal (Cutoff Date): {$cutoffDate} ({$days} hari yang lalu)");
        if ($dryRun) {
            $this->warn("MODE SIMULASI (--dry-run): Tidak ada data yang dihapus dari database.");
        }
        $this->newLine();

        $totalCleaned = 0;

        // 1. Process activity_log
        if ($type === 'all' || $type === 'activity_log') {
            $totalCleaned += $this->cleanActivityLogs($cutoffDate, $dryRun);
        }

        // 2. Process whatsapp_messages
        if ($type === 'all' || $type === 'whatsapp_messages') {
            $totalCleaned += $this->cleanWhatsappMessages($cutoffDate, $dryRun);
        }

        $this->newLine();
        $this->info("=== SELESAI ===");
        $this->info("Total baris data yang " . ($dryRun ? "terdeteksi siap dibersihkan: " : "berhasil dibersihkan: ") . number_format($totalCleaned));

        return Command::SUCCESS;
    }

    /**
     * Clean old activity logs in safe batches
     */
    protected function cleanActivityLogs(string $cutoffDate, bool $dryRun): int
    {
        $this->line("<comment>Memeriksa tabel: activity_log...</comment>");

        if (!\Illuminate\Support\Facades\Schema::hasTable('activity_log')) {
            $this->warn("Tabel activity_log tidak ditemukan. Dilewati.");
            return 0;
        }

        $query = DB::table('activity_log')->where('created_at', '<', $cutoffDate);
        $count = $query->count();

        $this->line("  Ditemukan: <info>" . number_format($count) . "</info> baris log aktivitas lebih tua dari batas tanggal.");

        if ($dryRun || $count === 0) {
            return $count;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $deletedTotal = 0;
        $batchSize = 1000;

        // Delete in batches to avoid locking the table
        do {
            $ids = DB::table('activity_log')
                ->where('created_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $affected = DB::table('activity_log')->whereIn('id', $ids)->delete();
            $deletedTotal += $affected;
            $bar->advance($affected);

            // Small pause between batches to yield CPU & I/O
            usleep(10000); // 10ms
        } while (true);

        $bar->finish();
        $this->newLine();
        $this->info("  Berhasil membersihkan {$deletedTotal} log aktivitas lama.");

        Log::info("ERP Archiving: Cleaned {$deletedTotal} rows from activity_log (older than {$cutoffDate})");

        return $deletedTotal;
    }

    /**
     * Clean old read WhatsApp messages / metadata in safe batches
     */
    protected function cleanWhatsappMessages(string $cutoffDate, bool $dryRun): int
    {
        $this->line("<comment>Memeriksa tabel: whatsapp_messages...</comment>");

        if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
            $this->warn("Tabel whatsapp_messages tidak ditemukan. Dilewati.");
            return 0;
        }

        // Only prune successfully delivered outgoing messages or read incoming messages older than cutoff
        // Never delete unread messages or failed messages needing resend
        $query = DB::table('whatsapp_messages')
            ->where('created_at', '<', $cutoffDate)
            ->where(function ($q) {
                $q->where('direction', 'incoming')->where('is_read', true)
                  ->orWhere(function ($sub) {
                      $sub->where('direction', 'outgoing')
                          ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.delivery_success')) = 'true'");
                  });
            });

        $count = $query->count();

        $this->line("  Ditemukan: <info>" . number_format($count) . "</info> pesan WhatsApp terkirim/terbaca yang dapat diarsipkan.");

        if ($dryRun || $count === 0) {
            return $count;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $deletedTotal = 0;
        $batchSize = 500;

        do {
            $ids = DB::table('whatsapp_messages')
                ->where('created_at', '<', $cutoffDate)
                ->where(function ($q) {
                    $q->where('direction', 'incoming')->where('is_read', true)
                      ->orWhere(function ($sub) {
                          $sub->where('direction', 'outgoing')
                              ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.delivery_success')) = 'true'");
                      });
                })
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $affected = DB::table('whatsapp_messages')->whereIn('id', $ids)->delete();
            $deletedTotal += $affected;
            $bar->advance($affected);

            usleep(10000); // 10ms
        } while (true);

        $bar->finish();
        $this->newLine();
        $this->info("  Berhasil mengarsipkan {$deletedTotal} pesan WhatsApp lama.");

        Log::info("ERP Archiving: Cleaned {$deletedTotal} rows from whatsapp_messages (older than {$cutoffDate})");

        return $deletedTotal;
    }
}
