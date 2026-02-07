<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatPurchasingSeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'Purchasing',
                'feature' => 'Procurement Ops',
                'code' => 'PUR-DASH-001',
                'title' => 'Monitoring Dashboard Pembelian',
                'description' => 'Verifikasi widget ringkasan pembelian, hutang jatuh tempo, dan performa supplier.',
                'acceptance_criteria' => 'Data dashboard sinkron dengan transaksi aktual.',
                'custom_order' => 14,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Suppliers',
                'code' => 'PUR-SUP-001',
                'title' => 'Tambah Supplier Baru',
                'description' => 'Input data supplier baru lengkap dengan info pembayaran dan kontak.',
                'acceptance_criteria' => 'Supplier berhasil disimpan dan bisa dipilih saat buat PO.',
                'custom_order' => 15,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Purchase Requests',
                'code' => 'PUR-REQ-001',
                'title' => 'Buat Pengajuan Pembelian (PR)',
                'description' => 'Staf gudang mengajukan permintaan restock barang.',
                'acceptance_criteria' => 'PR terbentuk dan menunggu approval (jika ada workflow).',
                'custom_order' => 16,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Purchase Orders',
                'code' => 'PUR-PO-001',
                'title' => 'Buat Purchase Order (PO)',
                'description' => 'Buat PO resmi ke supplier berdasarkan PR atau langsung.',
                'acceptance_criteria' => 'PO terbentuk, status "Ordered", dan PDF bisa dicetak/dikirim.',
                'custom_order' => 17,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Goods Receipts',
                'code' => 'PUR-GR-001',
                'title' => 'Penerimaan Barang (GR)',
                'description' => 'Terima barang dari supplier berdasarkan nomor PO.',
                'acceptance_criteria' => 'Stok gudang bertambah otomatis sesuai qty yang diterima.',
                'custom_order' => 18,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'AI Gen. Receipt',
                'code' => 'PUR-AI-001',
                'title' => 'Scan Surat Jalan (AI OCR)',
                'description' => 'Upload foto/PDF Surat Jalan supplier untuk auto-input penerimaan.',
                'acceptance_criteria' => 'Sistem mendeteksi item dan qty dengan tingkat akurasi > 90%.',
                'custom_order' => 19,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Purchase Invoices',
                'code' => 'PUR-INV-001',
                'title' => 'Input Tagihan Supplier',
                'description' => 'Catat tagihan (Faktur Pembelian) dari supplier.',
                'acceptance_criteria' => 'Hutang usaha (AP) bertambah di modul Finance.',
                'custom_order' => 20,
            ],
            [
                'module' => 'Purchasing',
                'feature' => 'Purchase Returns',
                'code' => 'PUR-RET-001',
                'title' => 'Retur Pembelian',
                'description' => 'Kembalikan barang rusak ke supplier.',
                'acceptance_criteria' => 'Stok berkurang dan potong hutang/minta refund tercatat.',
                'custom_order' => 21,
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
