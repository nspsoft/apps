<?php

namespace App\Imports\Logistics;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class VehicleImport implements OnEachRow, WithHeadingRow, WithValidation
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();
        
        // Handle potential key variations (robustness)
        $plateNumber = isset($row['plate_number']) ? trim($row['plate_number']) : (isset($row['plate_no']) ? trim($row['plate_no']) : null);

        if (!$plateNumber) {
            // Should be caught by validation, but just in case
            return;
        }

        // Normalize status
        $rawStatus = isset($row['status']) ? strtolower(trim($row['status'])) : 'available';
        $validStatuses = ['available', 'maintenance', 'busy'];
        $status = in_array($rawStatus, $validStatuses) ? $rawStatus : 'available';

        \App\Models\Vehicle::updateOrCreate(
            ['license_plate' => $plateNumber],
            [
                'vehicle_type'    => $row['type'],
                'brand'           => $row['brand'],
                'model'           => $row['model'],
                'year'            => $row['year'],
                'chassis_number'  => $row['chassis_number'],
                'engine_number'   => $row['engine_number'],
                'fuel_type'       => $row['fuel_type'],
                'status'          => $status,
                'ownership'       => $row['ownership'] ?? 'Owned',
                'driver_name'     => $row['driver_name'],
                'capacity_weight' => $row['capacity_weight'],
                'capacity_volume' => $row['capacity_volume'],
                'stnk_number'     => $row['stnk_number'],
                'stnk_expiry'     => $this->transformDate($row['stnk_expiry_yyyy_mm_dd']),
                'kir_number'      => $row['kir_number'],
                'kir_expiry'      => $this->transformDate($row['kir_expiry_yyyy_mm_dd']),
                'purchase_date'   => $this->transformDate($row['purchase_date_yyyy_mm_dd']),
                'purchase_price'  => $row['purchase_price'],
                'notes'           => $row['notes'],
            ]
        );
    }

    public function rules(): array
    {
        return [
            'plate_number' => 'required',
            'type'         => 'required',
            'brand'        => 'required',
        ];
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            if (empty($value)) return null;
            
            // Handle numeric Excel date
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format($format);
            }
            
            // Handle d/m/Y format (DD/MM/YYYY) commonly used in ID/Excel
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format($format);
            }

            // Fallback to Y-m-d
            return Carbon::createFromFormat($format, $value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
}
