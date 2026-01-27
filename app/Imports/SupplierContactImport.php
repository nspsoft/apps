<?php

namespace App\Imports;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierContactImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['supplier_code']) || !isset($row['pic_name'])) {
            return null;
        }

        $supplier = Supplier::where('code', $row['supplier_code'])->first();

        if (!$supplier) {
            return null; // Skip if supplier not found
        }

        return new SupplierContact([
            'supplier_id' => $supplier->id,
            'name'        => $row['pic_name'],
            'position'    => $row['position'] ?? null,
            'phone'       => $row['phone'] ?? null,
            'email'       => $row['email'] ?? null,
        ]);
    }
}
