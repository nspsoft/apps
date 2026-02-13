<?php

namespace App\Exports;

use App\Models\PurchaseRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;

class PurchaseRequestsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return PurchaseRequest::with(['items.product', 'items.product.unit', 'createdBy'])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'PR Number',
            'Date',
            'Department',
            'Requester',
            'Status',
            'Notes',
            'Created By',
            'Product Code',
            'Product Name',
            'Quantity',
            'Unit',
            'Item Description',
        ];
    }

    public function map($request): array
    {
        $rows = [];
        
        if ($request->items->count() > 0) {
            foreach ($request->items as $item) {
                $rows[] = [
                    $request->pr_number,
                    $request->request_date->format('Y-m-d'),
                    $request->department,
                    $request->requester,
                    $request->status,
                    $request->notes,
                    $request->createdBy->name ?? '',
                    $item->product->code ?? '',
                    $item->product->name ?? '',
                    $item->qty,
                    $item->product->unit->name ?? '',
                    $item->description,
                ];
            }
        } else {
            // Handle PR with no items (edge case)
            $rows[] = [
                $request->pr_number,
                $request->request_date->format('Y-m-d'),
                $request->department,
                $request->requester,
                $request->status,
                $request->notes,
                $request->createdBy->name ?? '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $rows;
    }
}
