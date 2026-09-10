<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return collect([
            [
                'EMP-001',                      // NIK
                'Budi Santoso',                 // Full Name
                'budi.santoso@example.com',     // Email
                '081234567890',                 // Phone
                'Jl. Raya Rungkut Industri No. 10, Surabaya', // Address
                'PPIC',                         // Department
                'Staff PPIC',                   // Position
                '2026-01-15',                   // Joining Date (YYYY-MM-DD)
                'permanent',                    // Employment Status (permanent, contract, probation, internship)
                5000000,                        // Basic Salary
                'Active',                       // Status (Active / Inactive)
            ],
            [
                'EMP-002',                      // NIK
                'Siti Aminah',                  // Full Name
                'siti.aminah@example.com',      // Email
                '081987654321',                 // Phone
                'Jl. Ahmad Yani No. 45, Sidoarjo', // Address
                'Production',                   // Department
                'Operator Produksi',            // Position
                '2026-02-01',                   // Joining Date
                'contract',                     // Employment Status
                4500000,                        // Basic Salary
                'Active',                       // Status
            ]
        ]);
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo-600
                ],
            ],
        ];
    }
}
