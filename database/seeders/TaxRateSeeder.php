<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Indonesian Tax Rates
        $taxRates = [
            [
                'code' => 'ppn',
                'name' => 'PPN',
                'rate' => 11.00,
                'description' => 'Pajak Pertambahan Nilai (11%)',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'pph22',
                'name' => 'PPh 22',
                'rate' => 1.50,
                'description' => 'Pajak Penghasilan Pasal 22 (1.5%)',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'pph23',
                'name' => 'PPh 23',
                'rate' => 2.00,
                'description' => 'Pajak Penghasilan Pasal 23 (2%)',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'pph_final',
                'name' => 'PPh Final',
                'rate' => 0.50,
                'description' => 'Pajak Penghasilan Final UMKM (0.5%)',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'ppnbm',
                'name' => 'PPnBM',
                'rate' => 10.00,
                'description' => 'Pajak Penjualan Barang Mewah (10%)',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'no_tax',
                'name' => 'Tanpa Pajak',
                'rate' => 0.00,
                'description' => 'Tidak dikenakan pajak',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($taxRates as $taxRate) {
            TaxRate::updateOrCreate(
                ['code' => $taxRate['code']],
                $taxRate
            );
        }

        // Default App Settings
        $settings = [
            'currency' => ['value' => 'IDR', 'label' => 'Currency', 'group' => 'regional', 'type' => 'select'],
            'currency_symbol' => ['value' => 'Rp', 'label' => 'Currency Symbol', 'group' => 'regional', 'type' => 'text'],
            'decimal_separator' => ['value' => ',', 'label' => 'Decimal Separator', 'group' => 'regional', 'type' => 'select'],
            'thousand_separator' => ['value' => '.', 'label' => 'Thousand Separator', 'group' => 'regional', 'type' => 'select'],
            'date_format' => ['value' => 'd/m/Y', 'label' => 'Date Format', 'group' => 'regional', 'type' => 'select'],
            'timezone' => ['value' => 'Asia/Jakarta', 'label' => 'Timezone', 'group' => 'regional', 'type' => 'select'],
        ];

        foreach ($settings as $key => $data) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => ['value' => $data['value']],
                    'group' => $data['group'],
                    'label' => $data['label'],
                    'type' => $data['type'],
                ]
            );
        }
    }
}
