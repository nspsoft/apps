<?php

namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
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

class ActivityLogExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $query = Activity::with('causer')->orderBy('created_at', 'desc');

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'User',
            'Subject',
            'Event',
            'Description',
            'Details',
        ];
    }

    public function map($log): array
    {
        $details = '';
        if ($log->properties) {
            $props = $log->properties;
            if (isset($props['attributes']) && isset($props['old'])) {
                foreach ($props['attributes'] as $key => $value) {
                    if ($key !== 'updated_at' && json_encode($props['old'][$key] ?? null) !== json_encode($value)) {
                        $oldVal = is_array($props['old'][$key] ?? null) ? json_encode($props['old'][$key]) : ($props['old'][$key] ?? '-');
                        $newVal = is_array($value) ? json_encode($value) : $value;
                        $details .= "($key: $oldVal -> $newVal) ";
                    }
                }
            } else {
                $details = json_encode($props);
            }
        }

        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->causer->name ?? 'System',
            class_basename($log->subject_type) . ' #' . $log->subject_id,
            $log->event,
            $log->description,
            $details,
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
                
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'ACTIVITY LOGS REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $period = ($this->startDate || $this->endDate) 
                    ? "Period: " . ($this->startDate ?? '...') . " to " . ($this->endDate ?? '...')
                    : "All Time";

                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', $period . ' | Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                
                $cellRange = 'A4:F' . $sheet->getHighestRow();
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
