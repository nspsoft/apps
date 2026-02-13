<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return a single example row
        return collect([
            [
                '2023-10-25', // Receipt Date
                'SUP-001',    // Supplier Code
                'WH-MAIN',    // Warehouse Name
                'DN-12345',   // Delivery Note
                'PO-2023-001',// PO Number (Optional)
                'PROD-001',   // Product Code
                '100',        // Qty Received
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Receipt Date (YYYY-MM-DD)',
            'Supplier Code',
            'Warehouse Name',
            'Delivery Note Number',
            'PO Number (Optional)',
            'Product Code',
            'Qty Received',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
