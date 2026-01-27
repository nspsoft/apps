<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CustomerContactTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'CUST-001',         // Customer Code
                'Budi Santoso',     // PIC Name
                'Purchasing Mgr',   // Position
                '081234567890',     // Phone
                'budi@example.com', // Email
            ],
            [
                'CUST-001',         // Customer Code
                'Siti Aminah',      // PIC Name
                'Finance Staff',    // Position
                '089876543210',     // Phone
                'siti@example.com', // Email
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
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
                $sheet->getComment('A1')->getText()->createTextRun("Must match an existing Customer Code in the system.");

                // 2. Visual Cues (Mandatory Fields = Red & Bold)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:E1')->getFont()->setBold(true);
            },
        ];
    }
}
