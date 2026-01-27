<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SupplierContactTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'SUP-001',          // Supplier Code
                'Anton Wijaya',     // PIC Name
                'Sales Manager',    // Position
                '081234567890',     // Phone
                'anton@email.com',  // Email
            ],
            [
                'SUP-001',          // Supplier Code
                'Budi Santoso',     // PIC Name
                'Finance Staff',    // Position
                '089876543210',     // Phone
                'budi@email.com',   // Email
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Supplier Code',
            'PIC Name',
            'Position',
            'Phone',
            'Email',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments
                $sheet->getComment('A1')->getText()->createTextRun("Must match an existing Supplier Code in the system.");

                // 2. Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: Supplier Code (A1), PIC Name (B1)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:E1')->getFont()->setBold(true);
            },
        ];
    }
}
