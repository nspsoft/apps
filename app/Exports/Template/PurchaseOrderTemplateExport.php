<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PurchaseOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                '2026-02-15',       // Order Date
                '2026-02-20',       // Expected Date
                'SUP-001',          // Supplier Code
                'Main Warehouse',   // Warehouse Name
                'PROD-001',         // Product Code
                100,                // Quantity
                50000,              // Unit Price
                0,                  // Discount %
                'Urgent Order',     // Notes
            ],
            [
                '2026-02-15',
                '2026-02-20',
                'SUP-001',
                'Main Warehouse',
                'PROD-002',
                50,
                75000,
                5,
                '',
            ],
            [
                '2026-02-16',
                '',
                'SUP-002',
                'Site A',
                'MAT-005',
                200,
                10000,
                0,
                'Regular Restock',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Order Date',
            'Expected Date',
            'Supplier Code',
            'Warehouse Name',
            'Product Code',
            'Quantity',
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
                $sheet->getDelegate()->getStyle('A1:I1')->getFont()->setBold(true);
                
                // Add comments
                $sheet->getComment('A1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Date + Supplier + Warehouse will be grouped into one PO.");
                $sheet->getComment('B1')->getText()->createTextRun("Optional. Format: YYYY-MM-DD");
                $sheet->getComment('C1')->getText()->createTextRun("Required. Must match an existing Supplier Code.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Must match an existing Warehouse Name.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Must match an existing Product Code.");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Numeric.");
                $sheet->getComment('G1')->getText()->createTextRun("Required. Numeric.");
                $sheet->getComment('H1')->getText()->createTextRun("Optional. Numeric (0-100).");

                // Color headers
                $redColor = new Color(Color::COLOR_RED);
                $sheet->getDelegate()->getStyle('A1:G1')->getFont()->setColor($redColor);
            },
        ];
    }
}
