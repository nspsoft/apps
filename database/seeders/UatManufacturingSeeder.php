<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatManufacturingSeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'Manufacturing',
                'feature' => 'Intelligence Hub',
                'code' => 'MFG-DASH-001',
                'title' => 'Dashboard Produksi & OEE',
                'description' => 'Verifikasi bahwa dashboard menampilkan OEE (Overall Equipment Effectiveness) dan progress produksi harian.',
                'acceptance_criteria' => 'Grafik OEE valid dan status mesin real-time.',
                'custom_order' => 30,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Bill of Materials',
                'code' => 'MFG-BOM-001',
                'title' => 'Buat Bill of Materials (BOM)',
                'description' => 'Tentukan resep produksi untuk satu produk jadi (Finish Good) beserta bahan bakunya.',
                'acceptance_criteria' => 'BOM tersimpan dan total estimasi biaya bahan baku muncul.',
                'custom_order' => 31,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Production Routing',
                'code' => 'MFG-RUT-001',
                'title' => 'Definisikan Alur Produksi (Routing)',
                'description' => 'Buat urutan proses produksi (misal: Mixing -> Molding -> Packing).',
                'acceptance_criteria' => 'Routing berhasil disimpan dan bisa dipilih di Work Order.',
                'custom_order' => 32,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Work Orders',
                'code' => 'MFG-WO-001',
                'title' => 'Terbitkan SPK (Work Order)',
                'description' => 'Buat perintah kerja baru berdasarkan Sales Order atau untuk stok.',
                'acceptance_criteria' => 'Work Order berstatus "Planned" dan material ter-booking.',
                'custom_order' => 33,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Input Output',
                'code' => 'MFG-OUT-001',
                'title' => 'Input Hasil Produksi',
                'description' => 'Operator memasukkan jumlah barang jadi yang dihasilkan dan barang reject.',
                'acceptance_criteria' => 'Stok produk jadi bertambah, bahan baku berkurang (backflush).',
                'custom_order' => 34,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Machine Management',
                'code' => 'MFG-MCH-001',
                'title' => 'Update Status Mesin',
                'description' => 'Ubah status mesin dari "Running" ke "Down" atau "Maintenance".',
                'acceptance_criteria' => 'Status di dashboard berubah dan downtime tercatat.',
                'custom_order' => 35,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Shift Management',
                'code' => 'MFG-SFT-001',
                'title' => 'Kelola Shift Operator',
                'description' => 'Atur jadwal shift pagi/siang/malam untuk tim produksi.',
                'acceptance_criteria' => 'Jadwal shift tersimpan dan tampil di laporan absensi produksi.',
                'custom_order' => 36,
            ],
            [
                'module' => 'Manufacturing',
                'feature' => 'Subcontract Orders',
                'code' => 'MFG-SUB-001',
                'title' => 'Order Maklon (Subkon)',
                'description' => 'Buat pesanan jasa produksi ke vendor luar.',
                'acceptance_criteria' => 'PO Subkon terbentuk dan surat jalan material keluar tercetak.',
                'custom_order' => 37,
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
