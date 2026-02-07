<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatLogisticsSeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'Logistics',
                'feature' => 'Vehicle Fleet',
                'code' => 'LOG-FLEET-001',
                'title' => 'Registrasi Armada Baru',
                'description' => 'Tambah data truk/mobil baru (Plat No, Kapasitas, Driver).',
                'acceptance_criteria' => 'Armada tersimpan dan statusnya "Available".',
                'custom_order' => 44,
            ],
            [
                'module' => 'Logistics',
                'feature' => 'Delivery Planning',
                'code' => 'LOG-PLAN-001',
                'title' => 'Buat Rencana Pengiriman (Route)',
                'description' => 'Gabungkan beberapa Delivery Order (DO) ke dalam satu pengiriman truk.',
                'acceptance_criteria' => 'Kapalitas truk terisi sesuai volume DO dan rute terbentuk.',
                'custom_order' => 45,
            ],
            [
                'module' => 'Logistics',
                'feature' => 'Delivery Planning',
                'code' => 'LOG-PLAN-002',
                'title' => 'Cetak Surat Jalan Gabungan',
                'description' => 'Cetak manifest pengiriman untuk supir.',
                'acceptance_criteria' => 'Dokumen mencantumkan semua alamat tujuan dalam satu rute.',
                'custom_order' => 46,
            ],
            [
                'module' => 'Logistics',
                'feature' => 'Logistics Hub',
                'code' => 'LOG-DASH-001',
                'title' => 'Monitoring Armada',
                'description' => 'Cek posisi atau status armada yang sedang mengirim.',
                'acceptance_criteria' => 'Status armada berubah menjadi "In Delivery".',
                'custom_order' => 47,
            ],
            [
                'module' => 'Logistics',
                'feature' => 'Vehicle Fleet',
                'code' => 'LOG-MAINT-001',
                'title' => 'Jadwal Service Kendaraan',
                'description' => 'Input jadwal ganti oli atau perbaikan truk.',
                'acceptance_criteria' => 'Status armada berubah menjadi "Maintenance" dan tidak bisa dipilih untuk pengiriman.',
                'custom_order' => 48,
            ],
        ];

        foreach ($scenarios as $scenario) {
            UatScenario::updateOrCreate(
                ['code' => $scenario['code']],
                $scenario
            );
        }
    }
}
