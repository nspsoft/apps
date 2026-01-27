<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SupplierTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'SUP-001',              // Code
                'PT. Supplier Baja',    // Name
                'Anton Wijaya',         // Contact Person
                'Jl. Industri Raya 5',  // Address
                'Jakarta',              // City
                '021-5555555',          // Phone
                '021-5555556',          // Fax
                'baja@supplier.com',    // Email
                '01.234.567.8-001.000', // Tax ID
                '01.234.567.8-001.xxx', // NPWP
                'Net 60',               // Payment Terms
                60,                     // Payment Days
            ],
            [
                'SUP-002',              // Code
                'CV. Sentosa Jaya',     // Name
                'Budi Kurniawan',       // Contact Person
                'Jl. Gudang Peluru 8',  // Address
                'Surabaya',             // City
                '031-7777777',          // Phone
                '-',                    // Fax
                'sales@sentosa.com',    // Email
                '99.888.777.6-002.000', // Tax ID
                '99.888.777.6-002.xxx', // NPWP
                'Cash',                 // Payment Terms
                0,                      // Payment Days
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Contact Person',
            'Address',
            'City',
            'Phone',
            'Fax',
            'Email',
            'Tax ID',
            'NPWP',
            'Payment Terms',
            'Payment Days',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments
                $sheet->getComment('K1')->getText()->createTextRun("e.g., Net 30, Cash, COD");

                // 2. Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: Code (A1), Name (B1), Payment Terms (K1), Payment Days (L1)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle('K1:L1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:J1')->getFont()->setBold(true);
            },
        ];
    }
}
