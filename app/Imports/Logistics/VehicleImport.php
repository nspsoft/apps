<?php

namespace App\Imports\Logistics;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class VehicleImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Vehicle([
            'plate_number'    => $row['plate_number'],
            'type'            => $row['type'],
            'brand'           => $row['brand'],
            'model'           => $row['model'],
            'year'            => $row['year'],
            'chassis_number'  => $row['chassis_number'],
            'engine_number'   => $row['engine_number'],
            'fuel_type'       => $row['fuel_type'],
            'status'          => $row['status'] ?? 'Available',
            'ownership'       => $row['ownership'] ?? 'Owned',
            'driver_name'     => $row['driver_name'],
            'capacity_weight' => $row['capacity_weight'],
            'capacity_volume' => $row['capacity_volume'],
            'purchase_date'   => $this->transformDate($row['purchase_date_yyyy_mm_dd']),
            'purchase_price'  => $row['purchase_price'],
            'notes'           => $row['notes'],
        ]);
    }

    public function rules(): array
    {
        return [
            'plate_number' => 'required|unique:vehicles,plate_number',
            'type'         => 'required',
            'brand'        => 'required',
            'model'        => 'required',
            'year'         => 'required|numeric',
        ];
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format($format);
            }
            return Carbon::createFromFormat($format, $value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
}
