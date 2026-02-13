<?php

namespace App\Exports\Purchasing;

use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return GoodsReceipt::with(['purchaseOrder', 'supplier', 'warehouse', 'items.product.unit'])
            ->get()
            ->flatMap(function ($receipt) {
                return $receipt->items->map(function ($item) use ($receipt) {
                    return [
                        'receipt' => $receipt,
                        'item' => $item,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'PO Number',
            'Receipt Date',
            'Supplier',
            'Warehouse',
            'Delivery Note',
            'Status',
            'Product Code',
            'Product Name',
            'Qty Ordered',
            'Qty Received',
            'Unit',
            'Notes',
        ];
    }

    public function map($row): array
    {
        $receipt = $row['receipt'];
        $item = $row['item'];

        return [
            $receipt->grn_number,
            $receipt->purchaseOrder?->po_number ?? '-',
            $receipt->receipt_date ? $receipt->receipt_date->format('Y-m-d') : '',
            $receipt->supplier?->name,
            $receipt->warehouse?->name,
            $receipt->delivery_note_number,
            strtoupper($receipt->status),
            $item->product?->code,
            $item->product?->name,
            $item->qty_ordered,
            $item->qty_received,
            $item->product?->unit?->code,
            $receipt->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
