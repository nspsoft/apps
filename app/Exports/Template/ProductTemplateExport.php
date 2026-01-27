<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\WithEvents;

class ProductTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'RM-STEEL-001',             // SKU
                'Steel Plate 2mm',          // Name
                'High quality steel plate', // Description
                '8991234567001',            // Barcode
                'Raw Materials',            // Category
                'kg',                       // Unit
                'product',                  // Item Type
                'raw_material',             // Product Type
                15000,                      // Cost Price
                0,                          // Selling Price
                100,                        // Min Stock
                200,                        // Reorder Point
                500,                        // Reorder Qty
                1000,                       // Max Stock
                3,                          // Lead Time (Days)
                1,                          // Weight
                'kg',                       // Weight Unit
                100,                        // Length
                100,                        // Width
                2,                          // Height
                'cm',                       // Dimension Unit
                'No',                       // Is Manufactured
                'Yes',                      // Is Purchased
                'No',                       // Is Sold
                'No',                       // Track Serial
                'Yes',                      // Track Batch
                'No',                       // Track Expiry
            ],
            [
                'FG-TABLE-001',             // SKU
                'Executive Office Table',   // Name
                'Mahogany wood table',      // Description
                '8991234567002',            // Barcode
                'Finished Goods',           // Category
                'pcs',                      // Unit
                'product',                  // Item Type
                'finished_good',            // Product Type
                1500000,                    // Cost Price
                2500000,                    // Selling Price
                10,                         // Min Stock
                15,                         // Reorder Point
                20,                         // Reorder Qty
                50,                         // Max Stock
                7,                          // Lead Time (Days)
                25,                         // Weight
                'kg',                       // Weight Unit
                120,                        // Length
                60,                         // Width
                75,                         // Height
                'cm',                       // Dimension Unit
                'Yes',                      // Is Manufactured
                'No',                       // Is Purchased
                'Yes',                      // Is Sold
                'Yes',                      // Track Serial
                'No',                       // Track Batch
                'No',                       // Track Expiry
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Description',
            'Barcode',
            'Category',
            'Unit',
            'Item Type',
            'Product Type',
            'Cost Price',
            'Selling Price',
            'Min Stock',
            'Reorder Point',
            'Reorder Qty',
            'Max Stock',
            'Lead Time (Days)',
            'Weight',
            'Weight Unit',
            'Length',
            'Width',
            'Height',
            'Dimension Unit',
            'Is Manufactured',
            'Is Purchased',
            'Is Sold',
            'Track Serial',
            'Track Batch',
            'Track Expiry',
        ];

    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments (Instructions)
                $sheet->getComment('G1')->getText()->createTextRun("Options:\n- product\n- service\n- consumable");
                $sheet->getComment('H1')->getText()->createTextRun("Options:\n- raw_material\n- wip\n- finished_good\n- spare_part");
                $sheet->getComment('V1')->getText()->createTextRun("Fill with 'Yes' or 'No'");
                
                // 2. Data Validation (Dropdowns)
                // Item Type (Column G)
                $validation = $sheet->getCell('G2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"product,service,consumable"');
                // Apply to rows 2-1000
                $sheet->setDataValidation('G2:G1000', $validation);

                // Product Type (Column H)
                $validation2 = $sheet->getCell('H2')->getDataValidation();
                $validation2->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation2->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation2->setAllowBlank(false);
                $validation2->setShowInputMessage(true);
                $validation2->setShowErrorMessage(true);
                $validation2->setShowDropDown(true);
                $validation2->setFormula1('"raw_material,wip,finished_good,spare_part"');
                $sheet->setDataValidation('H2:H1000', $validation2);

                // Boolean Fields (Yes/No) - Columns V, W, X, Y, Z, AA
                $validation3 = $sheet->getCell('V2')->getDataValidation();
                $validation3->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation3->setAllowBlank(true);
                $validation3->setShowDropDown(true);
                $validation3->setFormula1('"Yes,No"');
                
                $sheet->setDataValidation('V2:V1000', $validation3); // Is Manufactured
                $sheet->setDataValidation('W2:W1000', $validation3); // Is Purchased
                $sheet->setDataValidation('X2:X1000', $validation3); // Is Sold
                $sheet->setDataValidation('Y2:Y1000', $validation3); // Track Serial
                $sheet->setDataValidation('Z2:Z1000', $validation3); // Track Batch
                $sheet->setDataValidation('AA2:AA1000', $validation3); // Track Expiry
                $sheet->setDataValidation('AA2:AA1000', $validation3); // Track Expiry

                // 3. Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: SKU (A1), Name (B1), Item Type (G1), Product Type (H1)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:F1')->getFont()->setBold(true);
                $sheet->getStyle('I1:AA1')->getFont()->setBold(true);
            },
        ];
    }
}
