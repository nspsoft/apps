<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class QuotationTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'CUST-0005',        // Customer Code
                '2026-02-13',       // Quotation Date
                '2026-03-13',       // Valid Until
                'PROD-001',         // Product Code
                100,                // Qty
                25000,              // Unit Price
                'Sample note',      // Notes
            ],
            [
                'CUST-0005',        // Same customer + date = same quotation
                '2026-02-13',
                '2026-03-13',
                'PROD-002',
                50,
                15000,
                '',
            ],
            [
                'CUST-0003',        // Different customer = new quotation
                '2026-02-14',
                '2026-03-14',
                'PROD-001',
                200,
                24000,
                'Another quotation',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Quotation Date',
            'Valid Until',
            'Product Code',
            'Qty',
            'Unit Price',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getComment('A1')->getText()->createTextRun("Required. Must match an existing Customer Code.");
                $sheet->getComment('B1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Customer Code + Quotation Date will be grouped into one Quotation.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. Format: YYYY-MM-DD. Must be after Quotation Date.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Must match an existing Product SKU.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Minimum: 0.0001");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Unit price in base currency.");

                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('G1')->getFont()->setBold(true);
            },
        ];
    }
}
