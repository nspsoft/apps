<?php

namespace App\Exports;

use App\Models\SalesForecast;
use App\Models\SalesOrderItem;
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
use Carbon\Carbon;

class SalesForecastExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $query = SalesForecast::with(['customer', 'product.unit']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        if (!empty($this->filters['month'])) {
            $query->whereDate('period', $this->filters['month'] . '-01');
        }

        $forecasts = $query->get();

        // Calculate actual Qty for each forecast row
        foreach ($forecasts as $forecast) {
            $startOfMonth = Carbon::parse($forecast->period)->startOfMonth();
            $endOfMonth = Carbon::parse($forecast->period)->endOfMonth();

            $actualQty = SalesOrderItem::whereHas('salesOrder', function($q) use ($startOfMonth, $endOfMonth, $forecast) {
                    $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                      ->where('customer_id', $forecast->customer_id)
                      ->whereNotIn('status', ['cancelled']);
                })
                ->where('product_id', $forecast->product_id)
                ->sum('qty');

            $forecast->qty_actual = (float) $actualQty;
        }

        return $forecasts;
    }

    public function headings(): array
    {
        return [
            'Period',
            'Customer Code',
            'Customer Name',
            'Product SKU',
            'Product Name',
            'Qty Forecast',
            'Qty Actual',
            'Accuracy (%)',
            'Sales Name',
            'Input User',
            'Input Date',
            'Notes',
        ];
    }

    public function map($forecast): array
    {
        return [
            Carbon::parse($forecast->period)->format('F Y'),
            $forecast->customer?->code,
            $forecast->customer?->name,
            $forecast->product?->sku,
            $forecast->product?->name,
            (float) $forecast->qty_forecast,
            (float) $forecast->qty_actual,
            (float) $forecast->accuracy,
            $forecast->sales_name,
            $forecast->createdBy?->name,
            $forecast->created_at?->format('d/m/Y H:i'),
            $forecast->notes,
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
                
                // 1. Insert Title and Date
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'SALES FORECAST REPORT');
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

                // 2. Page Setup for Printing
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                
                // 3. Add Borders
                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // 4. Correct alignment for numeric columns (F, G, H)
                $sheet->getStyle('F5:H' . $sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                
                // Auto-size all columns
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
