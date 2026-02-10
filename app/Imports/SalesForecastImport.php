<?php

namespace App\Imports;

use App\Models\SalesForecast;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SalesForecastImport implements ToModel, WithHeadingRow, WithValidation
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
            if (is_numeric($row['period'])) {
                $period = Date::excelToDateTimeObject($row['period'])->format('Y-m-01');
            } else {
                $period = Carbon::parse($row['period'])->format('Y-m-01');
            }
        } catch (\Exception $e) {
            return null;
        }

        return SalesForecast::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'period' => $period,
            ],
            [
                'qty_forecast' => $row['qty'],
                'notes' => $row['notes'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'customer_code' => 'required|exists:customers,code',
            'product_sku' => 'required|exists:products,sku',
            'period' => 'required',
            'qty' => 'required|numeric|min:0',
        ];
    }
}
