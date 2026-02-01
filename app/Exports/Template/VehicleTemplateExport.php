<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class VehicleTemplateExport implements WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'Plate Number',
            'Type', // Truck, Van, Motorcycle, Car
            'Brand',
            'Model',
            'Year',
            'Chassis Number',
            'Engine Number',
            'Fuel Type', // Diesel, Petrol, Electric
            'Status', // Available, Maintenance, In Use, Sold
            'Ownership', // Owned, Leased
            'Driver Name',
            'Capacity Weight',
            'Capacity Volume',
            'Purchase Date (YYYY-MM-DD)',
            'Purchase Price',
            'Notes',
        ];
    }
}
