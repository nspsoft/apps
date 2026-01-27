<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $headings;
    protected $mapCallback;

    public function __construct($data, array $headings, callable $mapCallback)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->mapCallback = $mapCallback;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return ($this->mapCallback)($row);
    }
}
