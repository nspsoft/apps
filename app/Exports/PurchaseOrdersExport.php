<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'items.unit'])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Expected Date',
            'Supplier Code',
            'Supplier Name',
            'Warehouse',
            'Status',
            'Notes',
            'Product Code',
            'Product Name',
            'Quantity',
            'Unit',
            'Unit Price',
            'Discount %',
            'Total Price',
        ];
    }

    public function map($order): array
    {
        $rows = [];
        
        if ($order->items->count() > 0) {
            foreach ($order->items as $item) {
                $rows[] = [
                    $order->po_number,
                    $order->order_date->format('Y-m-d'),
                    $order->expected_date ? $order->expected_date->format('Y-m-d') : '',
                    $order->supplier->code ?? '',
                    $order->supplier->name ?? '',
                    $order->warehouse->name ?? '',
                    $order->status,
                    $order->notes,
                    $item->product->code ?? '',
                    $item->product->name ?? '',
                    $item->qty,
                    $item->unit->name ?? '',
                    $item->unit_price,
                    $item->discount_percent,
                    $item->qty * $item->unit_price * (1 - ($item->discount_percent / 100)),
                ];
            }
        } else {
            // Handle PO with no items
            $rows[] = [
                $order->po_number,
                $order->order_date->format('Y-m-d'),
                $order->expected_date ? $order->expected_date->format('Y-m-d') : '',
                $order->supplier->code ?? '',
                $order->supplier->name ?? '',
                $order->warehouse->name ?? '',
                $order->status,
                $order->notes,
                '',
                '',
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
