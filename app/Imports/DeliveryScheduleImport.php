<?php

namespace App\Imports;

use App\Models\DeliverySchedule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DeliveryScheduleImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $customer = Customer::where('code', $row['customer_code'])->first();
        $product = Product::where('sku', $row['product_sku'])->first();

        if (!$customer || !$product) {
            return null; // Skip if not found
        }

        // Handle Date Format from Excel
        try {
            if (is_numeric($row['delivery_date'])) {
                $date = Date::excelToDateTimeObject($row['delivery_date'])->format('Y-m-d');
            } else {
                $date = Carbon::parse($row['delivery_date'])->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }

        return DeliverySchedule::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => $date,
                'po_number' => $row['po_number'] ?? null,
            ],
            [
                'qty_scheduled' => $row['qty'],
                'reference_number' => $row['reference_number'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'customer_code' => 'required|exists:customers,code',
            'product_sku' => 'required|exists:products,sku',
            'delivery_date' => 'required',
            'qty' => 'required|numeric|min:0',
        ];
    }
}
