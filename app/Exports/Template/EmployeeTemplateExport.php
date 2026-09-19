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
                'Cikarang, Bekasi',             // Address
                'Produksi',                     // Department
                'Line A',                       // Section / Bagian
                'Staff Operator',               // Position / Jabatan
                'I',                            // Golongan
                '1',                            // Tax Status / Status PTKP
                '2024-01-15',                   // Joining Date (YYYY-MM-DD)
                'permanent',                    // Employment Status
                'hourly',                       // Salary Type (hourly / monthly)
                22310,                          // Price/Hour (Tarif per jam)
                4885890,                        // Basic Salary (GP Tetap bulanan)
                'Ya',                           // BPJS TK (Ya / Tidak)
                '00012345678',                  // No BPJS TK (opsional)
                'Ya',                           // BPJS Kes (Ya / Tidak)
                '00087654321',                  // No BPJS Kes (opsional)
                'Active',                       // Status
            ],
            [
                'EMP-002',                      // NIK
                'Siti Aminah',                  // Full Name
                'siti.aminah@example.com',      // Email
                '081987654321',                 // Phone
                'Karawang, Jawa Barat',         // Address
                'Production',                   // Department
                'Line 1',                       // Section / Bagian
                'Operator Produksi',            // Position / Jabatan
                'II',                           // Golongan
                'TK/0',                         // Tax Status
                '2025-02-01',                   // Joining Date
                'contract',                     // Employment Status
                'monthly',                      // Salary Type
                0,                              // Price/Hour (0 jika monthly)
                4500000,                        // Basic Salary
                'Tidak',                        // BPJS TK (Ya / Tidak)
                '',                             // No BPJS TK (opsional)
                'Tidak',                        // BPJS Kes (Ya / Tidak)
                '',                             // No BPJS Kes (opsional)
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
            'Section',
            'Position',
            'Golongan',
            'Tax Status',
            'Joining Date',
            'Employment Status',
            'Salary Type',
            'Price/Hour',
            'Basic Salary',
            'BPJS TK',
            'No BPJS TK',
            'BPJS Kes',
            'No BPJS Kes',
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
