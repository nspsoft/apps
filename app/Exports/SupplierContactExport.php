<?php

namespace App\Exports;

use App\Models\SupplierContact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierContactExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return SupplierContact::with('supplier')->get();
    }

    public function headings(): array
    {
        return [
            'Supplier Code',
            'Supplier Name',
            'PIC Name',
            'Position',
            'Phone',
            'Email',
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->supplier ? $contact->supplier->code : '',
            $contact->supplier ? $contact->supplier->name : '',
            $contact->name,
            $contact->position,
            $contact->phone,
            $contact->email,
        ];
    }
}
