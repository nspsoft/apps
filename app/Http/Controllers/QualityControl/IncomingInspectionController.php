<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IncomingInspectionController extends Controller
{
    public function index()
    {
        $receipts = \App\Models\GoodsReceipt::with(['supplier', 'purchaseOrder'])
            ->where('status', \App\Models\GoodsReceipt::STATUS_RECEIVED)
            ->latest()
            ->paginate(10);
            
        return inertia('QualityControl/Incoming/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function show($id)
    {
        // Eager load items, products, and their master points
        $receipt = \App\Models\GoodsReceipt::with(['items.product.qcMasterPoints', 'items.unit', 'supplier'])
            ->findOrFail($id);
            
        return inertia('QualityControl/Incoming/Show', [
            'receipt' => $receipt,
        ]);
    }

    public function store(Request $request, $id)
    {
        $receipt = \App\Models\GoodsReceipt::findOrFail($id);
        
        $validated = $request->validate([
            'inspector_id' => 'required|exists:users,id',
            'inspection_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:goods_receipt_items,id',
            'items.*.qty_rejected' => 'required|numeric|min:0',
            // 'items.*.qty_accepted' => 'required|numeric|min:0', // Optional if calculated
            'items.*.inspection_data' => 'nullable|array', // QC Points results
        ]);

        $receiptOverallStatus = 'pass';
        $firstFailedInspectionId = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $receipt, &$receiptOverallStatus, &$firstFailedInspectionId) {
            foreach ($validated['items'] as $itemData) {
                $grItem = \App\Models\GoodsReceiptItem::findOrFail($itemData['id']);
                
                // 1. Update GR Item Quantities
                $qtyReceived = $grItem->qty_received;
                $qtyRejected = $itemData['qty_rejected'];
                
                // Ensure rejected doesn't exceed received
                if ($qtyRejected > $qtyReceived) {
                    throw new \Exception("Rejected quantity cannot exceed received quantity for item {$grItem->product->name}");
                }

                $grItem->update([
                    'qty_rejected' => $qtyRejected,
                ]);
                
                // 2. Create QC Inspection Record (for this Item)
                if (!empty($itemData['inspection_data'])) {
                    // Calculate status based on points
                    $itemOverallStatus = 'pass';
                    $inspectionItems = [];
                    
                    foreach ($itemData['inspection_data'] as $pointId => $val) {
                        $masterPoint = \App\Models\QcMasterPoint::find($pointId);
                        if ($masterPoint) {
                            $isPass = ($val['actual_value'] >= $masterPoint->standard_min && 
                                       $val['actual_value'] <= $masterPoint->standard_max);
                            
                            if (!$isPass) $itemOverallStatus = 'fail';

                            $inspectionItems[] = [
                                'qc_master_point_id' => $pointId,
                                'actual_value' => $val['actual_value'],
                                'is_pass' => $isPass,
                                'remark' => $val['remark'] ?? null,
                            ];
                        }
                    }

                    $inspection = \App\Models\QcInspection::create([
                        'reference_type' => \App\Models\GoodsReceiptItem::class,
                        'reference_id' => $grItem->id,
                        'inspector_id' => $validated['inspector_id'],
                        'inspection_date' => $validated['inspection_date'],
                        'status' => $itemOverallStatus, 
                        'sample_size' => 1, // Defaulting to 1 for now
                        'notes' => 'Incoming Inspection',
                    ]);
                    
                    $inspection->items()->createMany($inspectionItems);

                    // Update receipt overall status if any item fails
                    if ($itemOverallStatus === 'fail') {
                        $receiptOverallStatus = 'fail';
                        if (!$firstFailedInspectionId) {
                            $firstFailedInspectionId = $inspection->id;
                        }
                    }
                }
            }

            // 3. Update GR Header Status
            $receipt->update(['status' => \App\Models\GoodsReceipt::STATUS_INSPECTED]);

            // 4. Create NCR if the overall receipt inspection failed
            if ($receiptOverallStatus === 'fail' && $firstFailedInspectionId !== null) {
                \App\Models\NonConformanceReport::create([
                    'qc_inspection_id' => $firstFailedInspectionId, // Link to the first inspection that failed
                    'defect_description' => 'Incoming Inspection Failed for Goods Receipt #' . $receipt->id . '. See QC details for specific items.',
                    'status' => 'open',
                ]);
            }
        });

        return redirect()->route('qc.incoming.index')->with('success', 'Inspection recorded.' . ($receiptOverallStatus === 'fail' ? ' NCR Created.' : ''));
    }
}
