<?php

namespace App\Exports;

use App\Models\Employee;
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

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected $department_id;
    protected $status;

    public function __construct($department_id = null, $status = null)
    {
        $this->department_id = $department_id;
        $this->status = $status;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $query = Employee::with(['department', 'position']);

        if ($this->department_id) {
            $query->where('department_id', $this->department_id);
        }

        if ($this->status) {
            $query->where('employment_status', $this->status);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Full Name',
            'Email',
            'Phone',
            'Address',
            'Department',
            'Position',
            'Joining Date',
            'Employment Status',
            'Basic Salary',
            'Status',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->nik,
            $employee->full_name,
            $employee->email,
            $employee->phone,
            $employee->address,
            $employee->department?->name,
            $employee->position?->name,
            $employee->joining_date,
            $employee->employment_status,
            $employee->basic_salary,
            $employee->is_active ? 'Active' : 'Inactive',
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
                
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'EMPLOYEE MASTER DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:K2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                
                $cellRange = 'A4:K' . $sheet->getHighestRow();
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
