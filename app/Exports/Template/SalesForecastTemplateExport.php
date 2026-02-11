<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesForecastTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            [
                'CUST-001',             // customer_code
                'PROD-SKU-001',        // product_sku
                now()->format('Y-m-01'), // period (First day of month)
                1000,                   // qty
                'Initial forecast for testing', // notes
            ],
            [
                'CUST-002',
                'PROD-SKU-002',
                now()->addMonth()->format('Y-m-01'),
                2500,
                'Seasonal peak expectation',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'customer_code',
            'product_sku',
            'period',
            'qty',
            'notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments (Instructions)
                $sheet->getComment('A1')->getText()->createTextRun("Required: Customer Code (e.g., SPINDO-001)");
                $sheet->getComment('B1')->getText()->createTextRun("Required: Product SKU (e.g., PIPE-GI-0.5)");
                $sheet->getComment('C1')->getText()->createTextRun("Required Format: YYYY-MM-DD\ne.g., 2026-01-01");
                $sheet->getComment('D1')->getText()->createTextRun("Required: Numeric value");

                // 2. Visual Cues (Mandatory Fields = Red & Bold)
                $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('E1')->getFont()->setBold(true);

                // Auto-size columns
                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
