<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesInvoiceTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            ['SO-202602-0001', '2026-02-13', '2026-03-15', 'SKU001', 5, 100000, 0, ''],
            ['SO-202602-0001', '2026-02-13', '2026-03-15', 'SKU002', 10, 50000, 5, ''],
            ['SO-202602-0002', '2026-02-13', '2026-03-15', 'SKU003', 3, 250000, 0, 'Priority shipment'],
        ]);
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Invoice Date',
            'Due Date',
            'Product Code',
            'Qty',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getComment('A1')->getText()->createTextRun(
                    "Required. The SO Number to link this invoice to. Must be a valid confirmed/processing SO."
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Required. Invoice date in YYYY-MM-DD format."
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    "Required. Due date in YYYY-MM-DD format."
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    "Required. Product SKU code. Must match an existing product."
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    "Required. Quantity to invoice. Must be greater than 0."
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    "Required. Unit price for the product."
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    "Optional. Discount percentage (0-100)."
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    "Optional. Notes for the invoice."
                );
            },
        ];
    }
}
