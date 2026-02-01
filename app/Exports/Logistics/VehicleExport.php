<?php

namespace App\Exports\Logistics;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class VehicleExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Vehicle::all();
    }

    public function headings(): array
    {
        return [
            'Plate Number',
            'Type',
            'Brand',
            'Model',
            'Year',
            'Chassis Number',
            'Engine Number',
            'Fuel Type',
            'Status',
            'Ownership',
            'Driver Name',
            'Capacity Weight',
            'Capacity Volume',
            'Purchase Date',
            'Purchase Price',
            'Notes',
        ];
    }

    public function map($vehicle): array
    {
        return [
            $vehicle->plate_number,
            $vehicle->type,
            $vehicle->brand,
            $vehicle->model,
            $vehicle->year,
            $vehicle->chassis_number,
            $vehicle->engine_number,
            $vehicle->fuel_type,
            $vehicle->status,
            $vehicle->ownership,
            $vehicle->driver_name,
            $vehicle->capacity_weight,
            $vehicle->capacity_volume,
            $vehicle->purchase_date,
            $vehicle->purchase_price,
            $vehicle->notes,
        ];
    }
}
