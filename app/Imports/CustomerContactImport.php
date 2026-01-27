<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerContact;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerContactImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Require customer_code (col A) and pic_name (col B)
        if (!isset($row['customer_code']) || !isset($row['pic_name'])) {
            return null;
        }

        $customer = Customer::where('code', $row['customer_code'])->first();

        if (!$customer) {
            return null; // Skip if customer not found
        }

        return new CustomerContact([
            'customer_id' => $customer->id,
            'name'        => $row['pic_name'],
            'position'    => $row['position'] ?? null,
            'phone'       => $row['phone'] ?? null,
            'email'       => $row['email'] ?? null,
        ]);
    }
}
