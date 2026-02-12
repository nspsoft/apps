<?php

namespace App\Exports;

use App\Models\Quotation;
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

class QuotationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return Quotation::with(['customer', 'items.product'])
            ->orderBy('quotation_date', 'desc')
            ->get()
            ->flatMap(function ($q) {
                if ($q->items->isEmpty()) {
                    return [['quotation' => $q, 'item' => null]];
                }
                return $q->items->map(function ($item) use ($q) {
                    return ['quotation' => $q, 'item' => $item];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Quotation Number',
            'Customer Name',
            'Quotation Date',
            'Valid Until',
            'Product Code',
            'Product Name',
            'Qty',
            'Unit Price',
            'Total Price',
            'Status',
            'Quotation Total',
            'Notes',
        ];
    }

    public function map($row): array
    {
        $q = $row['quotation'];
        $item = $row['item'];

        return [
            $q->number,
            $q->customer?->name,
            $q->quotation_date?->format('Y-m-d'),
            $q->valid_until?->format('Y-m-d'),
            $item?->product?->sku,
            $item?->product?->name,
            $item?->qty,
            $item?->unit_price,
            $item?->total_price,
            ucfirst($q->status),
            $q->total,
            $q->notes,
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

                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'QUOTATION DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("H5:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("I5:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("K5:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
