<?php

namespace Database\Seeders;

use App\Models\DocumentNumbering;
use Illuminate\Database\Seeder;

class DocumentNumberingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // Returns
            [
                'module' => 'sales',
                'code' => 'sales_return',
                'name' => 'Sales Return',
                'prefix' => 'RET',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            [
                'module' => 'purchasing',
                'code' => 'purchase_return',
                'name' => 'Purchase Return',
                'prefix' => 'PRT',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            // Sales
            [
                'module' => 'sales',
                'code' => 'sales_order',
                'name' => 'Sales Order',
                'prefix' => 'SO',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            [
                'module' => 'sales',
                'code' => 'sales_invoice',
                'name' => 'Sales Invoice',
                'prefix' => 'INV',
                'format' => '{PREFIX}/{Y}{m}-{NUMBER}', // INV/202301-0001
            ],
            [
                'module' => 'sales',
                'code' => 'delivery_order',
                'name' => 'Delivery Order',
                'prefix' => 'DO',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
             [
                'module' => 'sales',
                'code' => 'quotation',
                'name' => 'Quotation',
                'prefix' => 'QT',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            // Purchasing
            [
                'module' => 'purchasing',
                'code' => 'purchase_request',
                'name' => 'Purchase Request',
                'prefix' => 'PR',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            [
                'module' => 'purchasing',
                'code' => 'purchase_order',
                'name' => 'Purchase Order',
                'prefix' => 'PO',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            [
                'module' => 'purchasing',
                'code' => 'goods_receipt',
                'name' => 'Goods Receipt',
                'prefix' => 'GR',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            // Manufacturing
            [
                'module' => 'manufacturing',
                'code' => 'work_order',
                'name' => 'Work Order',
                'prefix' => 'WO',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
            [
                'module' => 'manufacturing',
                'code' => 'production_entry',
                'name' => 'Production Entry',
                'prefix' => 'PROD',
                'format' => '{PREFIX}/{Y}/{m}/{NUMBER}',
            ],
        ];

        foreach ($defaults as $config) {
            DocumentNumbering::updateOrCreate(
                ['code' => $config['code']],
                $config
            );
        }
    }
}
