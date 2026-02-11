<?php

namespace App\Exports;

use App\Models\DeliverySchedule;
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

class DeliveryScheduleExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
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
        $query = DeliverySchedule::with(['customer', 'product.unit', 'created_by_user']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                  ->orWhere('po_number', 'like', "%{$search}%");
        }

        if (!empty($this->filters['date'])) {
            $query->whereDate('delivery_date', $this->filters['date']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Delivery Date',
            'Customer Code',
            'Customer Name',
            'PO Number',
            'Product SKU',
            'Product Name',
            'Qty Scheduled',
            'Unit',
            'Reference Number',
            'Sales Name',
            'Input User',
            'Input Date',
            'Notes',
        ];
    }

    public function map($schedule): array
    {
        return [
            Carbon::parse($schedule->delivery_date)->format('d F Y'),
            $schedule->customer?->code,
            $schedule->customer?->name,
            $schedule->po_number,
            $schedule->product?->sku,
            $schedule->product?->name,
            (float) $schedule->qty_scheduled,
            $schedule->product?->unit?->code,
            $schedule->reference_number,
            $schedule->sales_name,
            $schedule->created_by_user?->name,
            $schedule->created_at?->format('d/m/Y H:i'),
            $schedule->notes,
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
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'DELIVERY SCHEDULE REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:M2');
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

                // 4. Correct alignment for numeric columns (G)
                $sheet->getStyle('G5:G' . $sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                
                // Auto-size all columns
                foreach (range('A', 'M') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
