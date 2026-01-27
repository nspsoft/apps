<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        // Return a sample row
        return collect([
            [
                'nik' => 'EMP001',
                'date' => '2026-01-27',
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'note' => 'Regular Shift',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Date',
            'Clock In',
            'Clock Out',
            'Note',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
