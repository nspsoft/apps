<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DeliveryOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            ['SO-202602-0001', 'SKU001', 5, '', ''],
            ['SO-202602-0001', 'SKU002', 10, '', ''],
            ['SO-202602-0002', 'SKU003', 3, 'BATCH-001', 'Handle with care'],
        ]);
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Product Code',
            'Qty Delivered',
            'Batch Number',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getComment('A1')->getText()->createTextRun(
                    "Required. The SO Number to link this delivery to. Must be a valid confirmed/processing SO."
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Required. Product SKU code. Must match an existing product in the system."
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    "Required. Quantity to deliver. Must be greater than 0."
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    "Optional. Batch or lot number for traceability."
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    "Optional. Additional notes for this line item."
                );
            },
        ];
    }
}
