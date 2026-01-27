<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['name'])) {
            return null;
        }

        return new Customer([
            'name'          => $row['name'],
            'code'          => $row['code'] ?? 'CUST-' . strtoupper(bin2hex(random_bytes(3))),
            'email'         => $row['email'],
            'phone'         => $row['phone'],
            'address'       => $row['address'],
            'customer_type' => $row['type'] ?? 'regular',
            'payment_terms' => 'COD',
            'payment_days'  => 0,
            'is_active'     => true,
        ]);
    }
}
