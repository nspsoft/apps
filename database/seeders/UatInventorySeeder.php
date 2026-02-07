<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatInventorySeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'Inventory',
                'feature' => 'Command Center',
                'code' => 'INV-DASH-001',
                'title' => 'Monitoring Command Center',
                'description' => 'Verifikasi widget status stok, peringatan stok menipis, dan valuasi aset berjalan real-time.',
                'acceptance_criteria' => 'Angka total aset dan jumlah stok low valid.',
                'custom_order' => 22,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Products',
                'code' => 'INV-PROD-001',
                'title' => 'Tambah Produk Baru',
                'description' => 'Input data master barang baru (Nama, SKU, Kategori, Satuan, Foto).',
                'acceptance_criteria' => 'Produk tersimpan dan muncul di daftar pencarian.',
                'custom_order' => 23,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Categories',
                'code' => 'INV-CAT-001',
                'title' => 'Kelola Kategori Barang',
                'description' => 'Buat kategori baru (misal: Bahan Baku, Sparepart) dan tetapkan ke produk.',
                'acceptance_criteria' => 'Kategori bisa dipilih saat input produk.',
                'custom_order' => 24,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Warehouses',
                'code' => 'INV-WH-001',
                'title' => 'Tambah Gudang/Lokasi',
                'description' => 'Daftarkan lokasi gudang fisik baru.',
                'acceptance_criteria' => 'Gudang baru muncul di pilihan saat penerimaan barang.',
                'custom_order' => 25,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Current Stock',
                'code' => 'INV-STK-001',
                'title' => 'Cek Stok Real-time',
                'description' => 'Cari stok item tertentu dan lihat detail per gudang.',
                'acceptance_criteria' => 'Jumlah stok fisik sama dengan yang ditampilkan di sistem.',
                'custom_order' => 26,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Stock Movements',
                'code' => 'INV-MOV-001',
                'title' => 'Transfer Antar Gudang',
                'description' => 'Pindahkan stok dari Gudang A ke Gudang B.',
                'acceptance_criteria' => 'Stok Gudang A berkurang, Stok Gudang B bertambah. History tercatat.',
                'custom_order' => 27,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Stock Opname',
                'code' => 'INV-SO-001',
                'title' => 'Lakukan Stock Opname',
                'description' => 'Buat sesi SO, input hasil hitung fisik, dan finalisasi (adjust).',
                'acceptance_criteria' => 'Selisih stok (jika ada) otomatis ter-adjust oleh sistem.',
                'custom_order' => 28,
            ],
            [
                'module' => 'Inventory',
                'feature' => 'Unit Management',
                'code' => 'INV-UNIT-001',
                'title' => 'Konversi Satuan',
                'description' => 'Set satuan terkecil (Pcs) dan satuan besar (Box). Coba transaksi dengan satuan besar.',
                'acceptance_criteria' => 'Sistem mengonversi Box ke Pcs dengan benar di kartu stok.',
                'custom_order' => 29,
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
