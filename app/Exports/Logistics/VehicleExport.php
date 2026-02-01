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
            'STNK Number',
            'STNK Expiry',
            'KIR Number',
            'KIR Expiry',
            'Purchase Date',
            'Purchase Price',
            'Notes',
        ];
    }

    public function map($vehicle): array
    {
        return [
            $vehicle->license_plate,
            $vehicle->vehicle_type,
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
            $vehicle->stnk_number,
            $vehicle->stnk_expiry,
            $vehicle->kir_number,
            $vehicle->kir_expiry,
            $vehicle->purchase_date,
            $vehicle->purchase_price,
            $vehicle->notes,
        ];
    }
}
