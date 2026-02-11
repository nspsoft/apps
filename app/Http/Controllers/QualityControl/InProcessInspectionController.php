<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InProcessInspectionController extends Controller
{
    public function index()
    {
        $workOrders = \App\Models\WorkOrder::with(['product', 'bom'])
            ->whereIn('status', [\App\Models\WorkOrder::STATUS_IN_PROGRESS, \App\Models\WorkOrder::STATUS_CONFIRMED])
            ->latest()
            ->paginate(10);
            
        return inertia('QualityControl/InProcess/Index', [
            'workOrders' => $workOrders,
        ]);
    }

    public function show($id)
    {
        // Eager load master points for the product being produced
        $workOrder = \App\Models\WorkOrder::with(['product.qcMasterPoints', 'product.unit', 'bom'])
            ->findOrFail($id);
            
        return inertia('QualityControl/InProcess/Show', [
            'workOrder' => $workOrder,
        ]);
    }

    public function store(Request $request, $id)
    {
        $workOrder = \App\Models\WorkOrder::findOrFail($id);
        
        $validated = $request->validate([
            'inspector_id' => 'required|exists:users,id',
            'inspection_date' => 'required|date',
            'sample_size' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'inspection_data' => 'nullable|array', // QC Points results
            'qty_rejected_in_sample' => 'nullable|integer|min:0', // Rejects found in this sample
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $workOrder) {
            
            // 1. Create QC Inspection Record
            $overallStatus = 'pass';
            $inspectionItems = [];
            
            if (!empty($validated['inspection_data'])) {
                foreach ($validated['inspection_data'] as $pointId => $val) {
                    $masterPoint = \App\Models\QcMasterPoint::find($pointId);
                    if ($masterPoint) {
                        $isPass = ($val['actual_value'] >= $masterPoint->standard_min && 
                                   $val['actual_value'] <= $masterPoint->standard_max);
                        
                        if (!$isPass) $overallStatus = 'fail';

                        $inspectionItems[] = [
                            'qc_master_point_id' => $pointId,
                            'actual_value' => $val['actual_value'],
                            'is_pass' => $isPass,
                            'remark' => $val['remark'] ?? null,
                        ];
                    }
                }
            }
            
            if (($validated['qty_rejected_in_sample'] ?? 0) > 0) {
                // $overallStatus = 'fail'; // Optional business logic
            }

            $inspection = \App\Models\QcInspection::create([
                'reference_type' => \App\Models\WorkOrder::class,
                'reference_id' => $workOrder->id,
                'inspector_id' => $validated['inspector_id'],
                'inspection_date' => $validated['inspection_date'],
                'status' => $overallStatus,
                'sample_size' => $validated['sample_size'],
                'notes' => $validated['notes'],
            ]);
            
            $inspection->items()->createMany($inspectionItems);

            // 2. Update Work Order Rejects (Cumulative)
            if (!empty($validated['qty_rejected_in_sample'])) {
                $workOrder->increment('qty_rejected', $validated['qty_rejected_in_sample']);
            }
            
            // 3. Create NCR if Failed
            if ($overallStatus === 'fail') {
                \App\Models\NonConformanceReport::create([
                    'qc_inspection_id' => $inspection->id,
                    'defect_description' => 'In-Process Inspection Failed.',
                    'status' => 'open',
                ]);
            }
        });

        return redirect()->route('qc.in-process.index')->with('success', 'In-Process Inspection recorded successfully.');
    }
}
