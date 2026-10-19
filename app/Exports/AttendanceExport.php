<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected array $filters;
    private int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $query = Attendance::with(['employee.department', 'employee.position'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('date', [$this->filters['start_date'], $this->filters['end_date']]);
        } elseif (!empty($this->filters['date'])) {
            $query->where('date', $this->filters['date']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->filters['department_id']);
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Employee Name',
            'Department',
            'Position',
            'Date',
            'Clock In',
            'Clock Out',
            'Late (Minutes)',
            'Early Leave (Minutes)',
            'Overtime (Minutes)',
            'Status',
            'Note',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $clockIn = $row->clock_in ? Carbon::parse($row->clock_in)->format('H:i:s') : '-';
        $clockOut = $row->clock_out ? Carbon::parse($row->clock_out)->format('H:i:s') : '-';
        $date = $row->date ? Carbon::parse($row->date)->format('Y-m-d') : '-';

        return [
            $this->rowNumber,
            $row->employee?->nik ?? '-',
            $row->employee?->full_name ?? '-',
            $row->employee?->department?->name ?? '-',
            $row->employee?->position?->name ?? '-',
            $date,
            $clockIn,
            $clockOut,
            $row->late_minutes ?? 0,
            $row->early_leave_minutes ?? 0,
            $row->overtime_minutes ?? 0,
            ucfirst($row->status ?? '-'),
            $row->note ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0F172A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0'], // Slate-200
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title Block
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'ATTENDANCE LOGS REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Metadata Subtitle
                $filterParts = [];
                $filterParts[] = 'Export Date: ' . now()->format('d F Y H:i');
                if (!empty($this->filters['date'])) {
                    $filterParts[] = 'Date: ' . $this->filters['date'];
                } elseif (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                    $filterParts[] = 'Period: ' . $this->filters['start_date'] . ' to ' . $this->filters['end_date'];
                }
                if (!empty($this->filters['department_id'])) {
                    $dept = \App\Models\Department::find($this->filters['department_id']);
                    if ($dept) {
                        $filterParts[] = 'Dept: ' . $dept->name;
                    }
                }
                if (!empty($this->filters['status'])) {
                    $filterParts[] = 'Status: ' . ucfirst($this->filters['status']);
                }
                if (!empty($this->filters['search'])) {
                    $filterParts[] = 'Search: "' . $this->filters['search'] . '"';
                }

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', implode(' | ', $filterParts));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

                $highestRow = $sheet->getHighestRow();

                // Set row 4 height for table headers
                $sheet->getRowDimension(4)->setRowHeight(26);

                // Thin borders for the whole table
                $cellRange = 'A4:M' . $highestRow;
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFCBD5E1'], // Slate-300
                        ],
                    ],
                ]);

                // Alignments for data cells
                if ($highestRow >= 5) {
                    $sheet->getStyle('A5:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('B5:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F5:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I5:K' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('L5:L' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
