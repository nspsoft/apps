<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryScheduleTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return collect([
            [
                'CUST-0001',
                'PT. Contoh Customer',
                'PO-2024-001',
                'PROD-001',
                'Pipa Galvanis 2 inch',
                '100',
                'REF-001',
                'Arrived at Port',
            ],
            [
                'CUST-0002',
                'PT. Customer Lain',
                'PO-2024-002',
                'PROD-002',
                'Pipa Hitam 3 inch',
                '50',
                'REF-002',
                'Pending production',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer Name (Reference only)',
            'PO Number',
            'Product SKU',
            'Product Name (Reference only)',
            'Qty',
            'Reference Number',
            'Notes',
            'Delivery Date (YYYY-MM-DD)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
