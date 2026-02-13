<?php

namespace App\Exports;

use App\Models\SalesInvoice;
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

class SalesInvoiceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return SalesInvoice::with(['salesOrder.customer', 'customer', 'items.product', 'items.unit'])
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->flatMap(function ($inv) {
                if ($inv->items->isEmpty()) {
                    return [['invoice' => $inv, 'item' => null]];
                }
                return $inv->items->map(function ($item) use ($inv) {
                    return ['invoice' => $inv, 'item' => $item];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'SO Number',
            'Customer Code',
            'Customer Name',
            'Invoice Date',
            'Due Date',
            'Product Code',
            'Product Name',
            'Qty',
            'Unit',
            'Unit Price',
            'Discount %',
            'Subtotal',
            'Status',
            'Invoice Total',
            'Paid Amount',
            'Balance',
        ];
    }

    public function map($row): array
    {
        $inv = $row['invoice'];
        $item = $row['item'];
        $customer = $inv->customer ?? $inv->salesOrder?->customer;

        return [
            $inv->invoice_number,
            $inv->salesOrder?->so_number,
            $customer?->code,
            $customer?->name,
            $inv->invoice_date?->format('Y-m-d'),
            $inv->due_date?->format('Y-m-d'),
            $item?->product?->sku,
            $item?->product?->name,
            $item?->qty,
            $item?->unit?->name,
            $item?->unit_price,
            $item?->discount_percent,
            $item?->subtotal,
            ucfirst($inv->status),
            $inv->total,
            $inv->paid_amount,
            $inv->balance,
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
                $sheet->mergeCells('A1:Q1');
                $sheet->setCellValue('A1', 'SALES INVOICE DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Export Date
                $sheet->mergeCells('A2:Q2');
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

                // Number format for currency columns (K=Unit Price, M=Subtotal, O=Invoice Total, P=Paid, Q=Balance)
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("K5:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M5:M{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("O5:O{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("P5:P{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("Q5:Q{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
