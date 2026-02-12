<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'CUST-0005',                // Customer Code
                '044/PO-SENFU/1/2026',      // Customer PO Number
                'WH-MAIN',                  // Warehouse Code
                '2026-02-13',               // Order Date
                'PROD-001',                 // Product Code
                100,                        // Qty
                'PCS',                      // Unit Code
                25000,                      // Unit Price
                0,                          // Discount %
                'Sample order note',        // Notes
            ],
            [
                'CUST-0005',                // Customer Code (same = same SO)
                '044/PO-SENFU/1/2026',      // Customer PO Number
                'WH-MAIN',                  // Warehouse Code
                '2026-02-13',               // Order Date (same date = grouped)
                'PROD-002',                 // Product Code
                50,                         // Qty
                'KG',                       // Unit Code
                15000,                      // Unit Price
                5,                          // Discount %
                '',                         // Notes
            ],
            [
                'CUST-0003',                // Customer Code (different = new SO)
                '',                         // Customer PO Number
                'WH-MAIN',                  // Warehouse Code
                '2026-02-14',               // Order Date
                'PROD-001',                 // Product Code
                200,                        // Qty
                'PCS',                      // Unit Code
                24000,                      // Unit Price
                0,                          // Discount %
                'Another order',            // Notes
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer PO',
            'Warehouse Code',
            'Order Date',
            'Product Code',
            'Qty',
            'Unit Code',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Instructions via comments
                $sheet->getComment('A1')->getText()->createTextRun("Required. Must match an existing Customer Code.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. Must match an existing Warehouse Code.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Customer Code + Order Date will be grouped into one SO.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Must match an existing Product Code.");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Minimum: 0.0001");
                $sheet->getComment('G1')->getText()->createTextRun("Optional. Must match an existing Unit Code. Leave blank to use product default unit.");
                $sheet->getComment('H1')->getText()->createTextRun("Required. Unit price in base currency.");

                // Mandatory fields in red bold
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('C1:F1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('H1')->getFont()->setBold(true)->setColor($redColor);

                // Optional fields in standard bold
                $sheet->getStyle('B1')->getFont()->setBold(true);
                $sheet->getStyle('G1')->getFont()->setBold(true);
                $sheet->getStyle('I1:J1')->getFont()->setBold(true);
            },
        ];
    }
}
