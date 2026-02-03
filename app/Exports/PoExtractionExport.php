<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PoExtractionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data['items'] ?? []);
        $this->header = [
            'PO Number' => $data['po_number'] ?? '',
            'Customer' => $data['customer_name'] ?? '',
            'Date' => $data['po_date'] ?? '',
        ];
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['PO Extraction Result'],
            ['PO Number:', $this->header['PO Number']],
            ['Customer:', $this->header['Customer']],
            ['Date:', $this->header['Date']],
            [''], // Empty row
            [
                'No',
                'Product Description',
                'Qty',
                'Unit',
                'Unit Price (PO)',
                'Total Amount',
                'Matched Product',
                'Matched SKU'
            ]
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;
        
        return [
            $no,
            $item['description'] ?? '',
            $item['qty'] ?? 0,
            $item['unit'] ?? '',
            $item['unit_price'] ?? 0,
            ($item['qty'] ?? 0) * ($item['unit_price'] ?? 0),
            $item['matched_product_name'] ?? '',
            $item['matched_sku'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            6 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]], // Header row
        ];
    }
}
