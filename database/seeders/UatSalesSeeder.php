<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatSalesSeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'Sales',
                'feature' => 'WhatsApp Center',
                'code' => 'SAL-WA-001',
                'title' => 'Hubungkan Perangkat WhatsApp',
                'description' => 'Verifikasi bahwa pengguna dapat memindai kode QR dan menghubungkan perangkat WhatsApp mereka.',
                'acceptance_criteria' => 'Status berubah menjadi "Terhubung" dan foto profil muncul.',
                'custom_order' => 1,
            ],
            [
                'module' => 'Sales',
                'feature' => 'WhatsApp Center',
                'code' => 'SAL-WA-002',
                'title' => 'Kirim Pesan Teks',
                'description' => 'Kirim pesan teks uji coba ke sebuah nomor.',
                'acceptance_criteria' => 'Pesan diterima oleh penerima.',
                'custom_order' => 2,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Sales Hub',
                'code' => 'SAL-DASH-001',
                'title' => 'Akurasi Data Dashboard',
                'description' => 'Verifikasi bahwa widget Omzet, Produk Terlaris, dan Pesanan Terbaru menampilkan data yang benar.',
                'acceptance_criteria' => 'Data sesuai dengan catatan transaksi aktual.',
                'custom_order' => 3,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Customers',
                'code' => 'SAL-CUST-001',
                'title' => 'Buat Pelanggan Baru',
                'description' => 'Tambahkan pelanggan baru dengan semua field (Nama, Alamat, Telepon, PIC).',
                'acceptance_criteria' => 'Pelanggan tersimpan dan muncul dalam daftar.',
                'custom_order' => 4,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Quotations',
                'code' => 'SAL-QT-001',
                'title' => 'Buat Penawaran Harga (Quotation)',
                'description' => 'Buat penawaran baru untuk pelanggan dengan beberapa item.',
                'acceptance_criteria' => 'Penawaran dibuat dengan status "Draft". Perhitungan total benar.',
                'custom_order' => 5,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Quotations',
                'code' => 'SAL-QT-002',
                'title' => 'Cetak PDF Penawaran',
                'description' => 'Hasilkan PDF untuk sebuah penawaran.',
                'acceptance_criteria' => 'PDF terunduh dan tata letaknya benar.',
                'custom_order' => 6,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Quotations',
                'code' => 'SAL-QT-003',
                'title' => 'Konversi ke Sales Order',
                'description' => 'Konversi penawaran yang berstatus "Diterima" menjadi Sales Order.',
                'acceptance_criteria' => 'Sales Order baru dibuat dengan data yang sama. Status penawaran diperbarui.',
                'custom_order' => 7,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Sales Orders',
                'code' => 'SAL-SO-001',
                'title' => 'Buat Sales Order Langsung',
                'description' => 'Buat Sales Order langsung tanpa penawaran.',
                'acceptance_criteria' => 'SO dibuat. Stok dicadangkan (jika berlaku).',
                'custom_order' => 8,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Delivery Orders',
                'code' => 'SAL-DO-001',
                'title' => 'Buat Delivery Order (Surat Jalan)',
                'description' => 'Buat Delivery Order dari Sales Order.',
                'acceptance_criteria' => 'DO dibuat. Stok berkurang.',
                'custom_order' => 9,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Sales Invoices',
                'code' => 'SAL-INV-001',
                'title' => 'Buat Faktur dari DO',
                'description' => 'Hasilkan faktur berdasarkan Delivery Order.',
                'acceptance_criteria' => 'Faktur dibuat dengan jumlah yang benar. Piutang (AR) bertambah.',
                'custom_order' => 10,
            ],
            [
                'module' => 'Sales',
                'feature' => 'Sales Returns',
                'code' => 'SAL-RET-001',
                'title' => 'Proses Retur Penjualan',
                'description' => 'Buat retur untuk item yang sudah dikirim.',
                'acceptance_criteria' => 'Retur tercatat. Stok disesuaikan kembali (jika kondisi baik).',
                'custom_order' => 11,
            ],
            [
                'module' => 'Sales',
                'feature' => 'PO Tracking',
                'code' => 'SAL-TRK-001',
                'title' => 'Lacak Status Pesanan',
                'description' => 'Cari nomor PO dan periksa statusnya.',
                'acceptance_criteria' => 'Timeline menunjukkan tahapan yang benar (Dipesan, Diproduksi, Dikirim).',
                'custom_order' => 12,
            ],
            [
                'module' => 'Sales',
                'feature' => 'AI PO Extractor',
                'code' => 'SAL-AI-001',
                'title' => 'Ekstrak PO dari PDF',
                'description' => 'Unggah contoh PDF PO dan verifikasi ekstraksi.',
                'acceptance_criteria' => 'Sistem mengidentifikasi Pelanggan, Item, dan Kuantitas dengan benar.',
                'custom_order' => 13,
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
