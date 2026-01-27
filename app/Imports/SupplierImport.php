<?php

namespace App\Imports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['name'])) {
            return null;
        }

        return new Supplier([
            'name'    => $row['name'],
            'code'    => $row['code'] ?? 'SUP-' . strtoupper(bin2hex(random_bytes(3))),
            'email'   => $row['email'],
            'phone'   => $row['phone'],
            'fax'     => $row['fax'] ?? null,
            'address' => $row['address'],
            'tax_id'  => $row['tax_id'],
            'npwp'    => $row['npwp'] ?? null,
        ]);
    }
}
