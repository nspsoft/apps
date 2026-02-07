<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UatScenario;

class UatCrmSeeder extends Seeder
{
    public function run()
    {
        $scenarios = [
            [
                'module' => 'CRM',
                'feature' => 'Leads Management',
                'code' => 'CRM-LEAD-001',
                'title' => 'Input Lead Baru',
                'description' => 'Masukkan data calon pelanggan potensial (Nama, Perusahaan, Kontak).',
                'acceptance_criteria' => 'Lead tersimpan dengan status "New".',
                'custom_order' => 38,
            ],
            [
                'module' => 'CRM',
                'feature' => 'Leads Management',
                'code' => 'CRM-LEAD-002',
                'title' => 'Konversi Lead ke Customer',
                'description' => 'Ubah status Lead menjadi Customer setelah deal.',
                'acceptance_criteria' => 'Data pindah ke Master Customer dan status Lead menjadi "Converted".',
                'custom_order' => 39,
            ],
            [
                'module' => 'CRM',
                'feature' => 'Opportunity Tracking',
                'code' => 'CRM-OPP-001',
                'title' => 'Buat Peluang (Opportunity)',
                'description' => 'Catat peluang proyek baru dengan estimasi nilai dan tahapannya (Qualification, Proposal, Negotiation).',
                'acceptance_criteria' => 'Opportunity muncul di pipeline penjualan.',
                'custom_order' => 40,
            ],
            [
                'module' => 'CRM',
                'feature' => 'Opportunity Tracking',
                'code' => 'CRM-OPP-002',
                'title' => 'Update Tahapan Opportunity',
                'description' => 'Pindahkan opportunity dari tahap "Proposal" ke "Negotiation".',
                'acceptance_criteria' => 'Probabilitas closing terupdate otomatis sesuai tahapan.',
                'custom_order' => 41,
            ],
            [
                'module' => 'CRM',
                'feature' => 'Marketing Campaigns',
                'code' => 'CRM-CMP-001',
                'title' => 'Buat Kampanye Email',
                'description' => 'Buat jadwal pengiriman email blast ke daftar leads.',
                'acceptance_criteria' => 'Kampanye terjadwal dan status berubah menjadi "Active".',
                'custom_order' => 42,
            ],
            [
                'module' => 'CRM',
                'feature' => 'CRM Intelligence',
                'code' => 'CRM-DASH-001',
                'title' => 'Analisa Sales Funnel',
                'description' => 'Cek laporan konversi dari Lead ke Opportunity hingga Won.',
                'acceptance_criteria' => 'Grafik funnel menampilkan rasio konversi yang benar.',
                'custom_order' => 43,
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
