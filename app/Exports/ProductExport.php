<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return Product::with(['category', 'unit', 'stocks'])->get();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Category',
            'Type',
            'Unit',
            'On Hand',
            'Min Stock',
            'Reorder Point',
            'Reorder Qty',
            'Max Stock',
            'Avg Cost',
            'Lead Time',
            'Weight',
            'Length',
            'Width',
            'Height',
            'Manufactured',
            'Purchased',
            'Sold',
            'Track Serial',
            'Track Batch',
            'Track Expiry',
        ];
    }

    public function map($product): array
    {
        return [
            $product->sku,
            $product->name,
            $product->category->name ?? '-',
            $product->product_type,
            $product->unit->symbol ?? 'pcs',
            $product->stocks->sum('qty_on_hand'),
            $product->min_stock,
            $product->reorder_point,
            $product->reorder_qty,
            $product->max_stock,
            $product->avg_cost,
            $product->lead_time_days,
            $product->weight,
            $product->length,
            $product->width,
            $product->height,
            $product->is_manufactured ? 'Yes' : 'No',
            $product->is_purchased ? 'Yes' : 'No',
            $product->is_sold ? 'Yes' : 'No',
            $product->track_serial ? 'Yes' : 'No',
            $product->track_batch ? 'Yes' : 'No',
            $product->track_expiry ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row (now row 4)
            4    => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEFEFEF'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Insert Title and Date
                $sheet->mergeCells('A1:V1'); // Merge title across columns
                $sheet->setCellValue('A1', 'PRODUCT MASTER DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:V2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // 2. Page Setup for Printing
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0); // Automatic height
                
                // 3. Add Borders to all data cells (starting from row 4)
                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }
}
