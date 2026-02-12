<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return SalesOrder::with(['customer', 'warehouse', 'items.product', 'items.unit'])
            ->orderBy('order_date', 'desc')
            ->get()
            ->flatMap(function ($so) {
                if ($so->items->isEmpty()) {
                    return [['order' => $so, 'item' => null]];
                }
                return $so->items->map(function ($item) use ($so) {
                    return ['order' => $so, 'item' => $item];
                });
            });
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Customer PO',
            'Customer Code',
            'Customer Name',
            'Warehouse',
            'Order Date',
            'Product Code',
            'Product Name',
            'Qty',
            'Unit',
            'Unit Price',
            'Discount %',
            'Subtotal',
            'Status',
            'SO Total',
        ];
    }

    public function map($row): array
    {
        $so = $row['order'];
        $item = $row['item'];

        return [
            $so->so_number,
            $so->customer_po_number,
            $so->customer?->code,
            $so->customer?->name,
            $so->warehouse?->name,
            $so->order_date?->format('Y-m-d'),
            $item?->product?->code,
            $item?->product?->name,
            $item?->qty,
            $item?->unit?->name,
            $item?->unit_price,
            $item?->discount_percent,
            $item?->subtotal,
            ucfirst($so->status),
            $so->total,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
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

                // Title
                $sheet->mergeCells('A1:O1');
                $sheet->setCellValue('A1', 'SALES ORDER DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Export Date
                $sheet->mergeCells('A2:O2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Page Setup
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Borders
                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Number format for currency columns (K=Unit Price, M=Subtotal, O=SO Total)
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("K5:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M5:M{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("O5:O{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
