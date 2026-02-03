<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DNExtractorController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index()
    {
        return Inertia::render('Purchasing/DNExtractor', [
            'suppliers' => Supplier::select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function extract(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('DN Extraction: Request received', ['file_present' => $request->hasFile('file')]);

        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $mimeType = $file->getMimeType();

        \Illuminate\Support\Facades\Log::info("DN Extraction: Processing file $path ($mimeType)");

        $data = $this->geminiService->extractDeliveryNoteData($path, $mimeType);

        if (!$data) {
            return response()->json(['message' => 'Failed to extract data from image.'], 422);
        }

        // Logic Matching PO
        $matchedPO = null;
        if (!empty($data['po_number'])) {
            // Try explicit search
            $matchedPO = PurchaseOrder::where('po_number', 'like', "%{$data['po_number']}%")
                ->with(['items.product', 'items.unit'])
                ->first();
        }

        if (!$matchedPO && !empty($data['supplier_name'])) {
            // Try finding latest open PO from this supplier?
            // Optional, maybe too aggressive. Let's stick to PO Number for now.
        }

        return response()->json([
            'extracted_data' => $data,
            'matched_po' => $matchedPO,
        ]);
    }

    public function storeGR(Request $request)
    {
        $data = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'dn_number' => 'required|string|max:255',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::findOrFail($data['purchase_order_id']);

            // Create GR Header
            $gr = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $po->warehouse_id ?? 1, // Default warehouse
                'grn_number' => GoodsReceipt::generateGrnNumber(),
                'delivery_note_number' => $data['dn_number'],
                'receipt_date' => $data['date'],
                'received_by' => auth()->id(),
                'status' => 'draft', // User needs to verify/approve later? Or received directly? Let's verify as draft.
                'notes' => 'Created via AI Extractor',
            ]);

            foreach ($data['items'] as $item) {
                if ($item['qty_received'] > 0) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    
                    GoodsReceiptItem::create([
                        'goods_receipt_id' => $gr->id,
                        'purchase_order_item_id' => $poItem->id,
                        'product_id' => $poItem->product_id,
                        'qty_ordered' => $poItem->qty,
                        'qty_received' => $item['qty_received'],
                        'unit_id' => $poItem->unit_id,
                        'unit_cost' => $poItem->unit_price,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    // Update PO Item Delivered Qty?
                    // Typically handled by GR approval logic.
                    // If we set status 'draft', we don't update stock yet.
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Goods Receipt created successfully!',
                'redirect_url' => route('purchasing.receipts.show', $gr->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create GR: ' . $e->getMessage()], 500);
        }
    }
}
