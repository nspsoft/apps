<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseRequestsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Group rows by unique PR identifier (Date + Department + Requester)
        $grouped = $rows->groupBy(function ($row) {
            $date = $this->parseDate($row['date']);
            return $date . '|' . strtolower(trim($row['department'])) . '|' . strtolower(trim($row['requester']));
        });

        foreach ($grouped as $key => $items) {
            $firstRow = $items->first();
            
            try {
                DB::transaction(function () use ($firstRow, $items) {
                    $requestDate = $this->parseDate($firstRow['date']);
                    
                    $pr = PurchaseRequest::create([
                        'pr_number' => PurchaseRequest::generatePrNumber(),
                        'request_date' => $requestDate,
                        'department' => $firstRow['department'],
                        'requester' => $firstRow['requester'],
                        'status' => 'draft',
                        'notes' => $firstRow['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    foreach ($items as $item) {
                        if (empty($item['product_code']) || empty($item['quantity'])) continue;

                        $product = Product::where('code', $item['product_code'])->first();
                        
                        if ($product) {
                            $pr->items()->create([
                                'product_id' => $product->id,
                                'qty' => $item['quantity'],
                                'description' => $item['item_description'] ?? null,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                // Log error or continue
                continue;
            }
        }
    }

    private function parseDate($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $ex) {
                return now()->format('Y-m-d');
            }
        }
    }
}
