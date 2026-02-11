<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class QcInspectionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference_type' => 'required|string', // e.g., 'App\Models\Purchasing\GoodsReceipt'
            'reference_id' => 'required|integer',
            'inspector_id' => 'required|exists:users,id',
            'inspection_date' => 'required|date',
            'sample_size' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.qc_master_point_id' => 'required|exists:qc_master_points,id',
            'items.*.actual_value' => 'required|numeric',
            'items.*.remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // 1. Determine Overall Status
            $overallStatus = 'pass';
            $inspectionItems = [];

            foreach ($validated['items'] as $itemData) {
                $masterPoint = \App\Models\QcMasterPoint::findOrFail($itemData['qc_master_point_id']);
                
                // Auto-Judgment Logic
                $isPass = ($itemData['actual_value'] >= $masterPoint->standard_min && 
                           $itemData['actual_value'] <= $masterPoint->standard_max);

                if (!$isPass) {
                    $overallStatus = 'fail';
                }

                $inspectionItems[] = [
                    'qc_master_point_id' => $itemData['qc_master_point_id'],
                    'actual_value' => $itemData['actual_value'],
                    'is_pass' => $isPass, // Boolean result stored here
                    'remark' => $itemData['remark'] ?? null,
                ];
            }

            // 2. Create Header
            $inspection = \App\Models\QcInspection::create([
                'reference_type' => $validated['reference_type'],
                'reference_id' => $validated['reference_id'],
                'inspector_id' => $validated['inspector_id'],
                'inspection_date' => $validated['inspection_date'],
                'status' => $overallStatus, // auto calculated
                'sample_size' => $validated['sample_size'],
                'notes' => $validated['notes'],
            ]);

            // 3. Create Details
            $inspection->items()->createMany($inspectionItems);

            // 4. Create NCR if Failed (Placeholder for now)
            if ($overallStatus === 'fail') {
                // Future: Create draft NCR or redirect to NCR creation
            }

            DB::commit();

            return back()->with('success', 'Inspection recorded. Result: ' . strtoupper($overallStatus));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to record inspection: ' . $e->getMessage());
        }
    }
}
