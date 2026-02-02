<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PoTrackingController extends Controller
{
    public function index(Request $request)
    {
        // 1. Chart Data (Status Distribution) - Needed for the Global Donut View
        $stats = SalesOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        
        $chartData = [
            'draft' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($stats as $stat) {
            $s = $stat->status;
            $count = $stat->total;
            
            if ($s === 'draft') {
                $chartData['draft'] += $count;
            } elseif (in_array($s, ['confirmed', 'processing', 'shipped'])) {
                 $chartData['processing'] += $count;
            } elseif (in_array($s, ['completed', 'paid'])) {
                 $chartData['completed'] += $count;
            } elseif ($s === 'cancelled') {
                 $chartData['cancelled'] += $count;
            }
        }

        $totalOrders = array_sum($chartData);
        $chartPercentages = [];
        foreach ($chartData as $key => $val) {
            $chartPercentages[$key] = $totalOrders > 0 ? round(($val / $totalOrders) * 100, 1) : 0;
        }

        // 2. Search Logic
        $searchResult = null;
        if ($request->search) {
            $so = SalesOrder::with(['deliveryOrders', 'invoices', 'items'])
                ->where('customer_po_number', 'like', "%{$request->search}%")
                ->orWhere('so_number', 'like', "%{$request->search}%")
                ->latest()
                ->first();

            if ($so) {
                // Determine Timeline Steps
                $timeline = [];
                
                // STEP 1: PO Received (SO Created)
                $createdAt = Carbon::parse($so->created_at);
                $timeline[] = [
                    'step' => 'PO Customer Received',
                    'date' => $createdAt->format('Y-m-d H:i'),
                    'status' => 'Completed',
                    'lead_time' => 'Start',
                ];

                // STEP 2: Sales Order Confirmed
                $confirmedAt = ($so->status !== 'draft') ? Carbon::parse($so->updated_at) : null; 
                $status2 = ($so->status === 'draft') ? 'Pending' : 'Completed';
                $timeline[] = [
                    'step' => 'Sales Order Processed',
                    'date' => $confirmedAt ? $confirmedAt->format('Y-m-d H:i') : '-',
                    'status' => $status2,
                    'lead_time' => $confirmedAt ? $createdAt->diffForHumans($confirmedAt, true) : '-',
                ];

                // STEP 3: Delivery (Surat Jalan)
                $firstDO = $so->deliveryOrders->sortBy('created_at')->first();
                $doAt = $firstDO ? Carbon::parse($firstDO->created_at) : null;
                
                // Calculate Delivery Progress
                $totalQty = $so->items->sum('qty');
                $deliveredQty = $so->items->sum('qty_delivered');
                $deliveryProgress = $totalQty > 0 ? round(($deliveredQty / $totalQty) * 100, 1) : 0;

                if ($deliveryProgress == 100) {
                    $status3 = 'Completed';
                } elseif ($deliveryProgress > 0) {
                    $status3 = 'Partial ' . $deliveryProgress . '%';
                } else {
                    $status3 = 'Pending';
                }
                
                $timeline[] = [
                    'step' => 'Delivery (Surat Jalan)',
                    'date' => $doAt ? $doAt->format('Y-m-d H:i') : '-',
                    'status' => $status3, 
                    'lead_time' => ($doAt && $confirmedAt) ? $confirmedAt->diffForHumans($doAt, true) : '-',
                ];

                // Re-add Invoice Step logic
                $firstInv = $so->invoices->sortBy('created_at')->first();
                $invAt = $firstInv ? Carbon::parse($firstInv->created_at) : null;
                $status4 = $firstInv ? 'Completed' : 'Pending';

                $timeline[] = [
                    'step' => 'Invoice Issued',
                    'date' => $invAt ? $invAt->format('Y-m-d H:i') : '-',
                    'status' => $status4,
                    'lead_time' => ($invAt && $doAt) ? $doAt->diffForHumans($invAt, true) : '-',
                ];

                // Payment
                 $firstPay = null;
                 $isPaid = false;
                 foreach($so->invoices as $inv) {
                     if ($inv->status === 'paid' || ($inv->total > 0 && $inv->paid_amount >= $inv->total)) {
                         $isPaid = true;
                         $firstPay = $inv; 
                         break;
                     }
                 }
                 
                 $payAt = $isPaid && $firstPay ? Carbon::parse($firstPay->updated_at) : null;
                 $status5 = $isPaid ? 'Paid' : 'Pending';
 
                 $timeline[] = [
                     'step' => 'Payment Received',
                     'date' => $payAt ? $payAt->format('Y-m-d H:i') : '-',
                     'status' => $status5,
                     'lead_time' => ($payAt && $invAt) ? $invAt->diffForHumans($payAt, true) : '-',
                 ];

                $searchResult = [
                    'so_number' => $so->so_number,
                    'po_number' => $so->customer_po_number ?? '-',
                    'customer' => $so->customer->name ?? 'Unknown',
                    'timeline' => $timeline,
                    'overall_lead_time' => $payAt ? $createdAt->diffForHumans($payAt, true) : 'Ongoing',
                    'delivery_progress' => $deliveryProgress,
                ];
            }
        }

        return Inertia::render('Sales/PoTracking', [
            'chartData' => $chartData,
            'chartPercentages' => $chartPercentages,
            'searchResult' => $searchResult,
            'queryParams' => $request->only(['search']),
        ]);
    }
}
