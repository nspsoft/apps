<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SalesInformationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Chart Data (Status Distribution)
        // Grouping logic:
        // - Processing: confirmed, processing, shipped
        // - Completed: completed, paid
        // - Cancelled: cancelled
        // - Draft: draft
        
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
            $so = SalesOrder::with(['deliveryOrders', 'invoices.payments'])
                ->where('client_po_number', 'like', "%{$request->search}%")
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
                // Note: strict updated_at might not be exact confirmation time but best proxy
                $status2 = ($so->status === 'draft') ? 'Pending' : 'Completed';
                $timeline[] = [
                    'step' => 'Sales Order Processed',
                    'date' => $confirmedAt ? $confirmedAt->format('Y-m-d H:i') : '-',
                    'status' => $status2,
                    'lead_time' => $confirmedAt ? $createdAt->diffForHumans($confirmedAt, true) : '-',
                ];

                // STEP 3: Delivery (First DO)
                $firstDO = $so->deliveryOrders->sortBy('created_at')->first();
                $doAt = $firstDO ? Carbon::parse($firstDO->created_at) : null;
                $status3 = $firstDO ? 'Completed' : 'Pending';
                
                $timeline[] = [
                    'step' => 'Delivery (Surat Jalan)',
                    'date' => $doAt ? $doAt->format('Y-m-d H:i') : '-',
                    'status' => $status3,
                    'lead_time' => ($doAt && $confirmedAt) ? $confirmedAt->diffForHumans($doAt, true) : '-',
                ];

                // STEP 4: Invoice (First Inv)
                $firstInv = $so->invoices->sortBy('created_at')->first();
                $invAt = $firstInv ? Carbon::parse($firstInv->created_at) : null;
                $status4 = $firstInv ? 'Completed' : 'Pending';

                $timeline[] = [
                    'step' => 'Invoice Issued',
                    'date' => $invAt ? $invAt->format('Y-m-d H:i') : '-',
                    'status' => $status4,
                    'lead_time' => ($invAt && $doAt) ? $doAt->diffForHumans($invAt, true) : '-',
                ];

                // STEP 5: Payment
                $firstPay = null;
                if ($firstInv) {
                     // Assuming payments relation exists or status check
                     if ($firstInv->status === 'paid' || $firstInv->paid_amount >= $firstInv->total) {
                         $status5 = 'Paid';
                         // Since we don't have exact payment date easily without payment model relation loaded deep,
                         // we use updated_at of invoice if status is paid
                         $payAt = Carbon::parse($firstInv->updated_at);
                     } else {
                         $status5 = 'Pending';
                         $payAt = null;
                     }
                } else {
                    $status5 = 'Pending';
                    $payAt = null;
                }

                $timeline[] = [
                    'step' => 'Payment Received',
                    'date' => $payAt ? $payAt->format('Y-m-d H:i') : '-',
                    'status' => $status5,
                    'lead_time' => ($payAt && $invAt) ? $invAt->diffForHumans($payAt, true) : '-',
                ];

                $searchResult = [
                    'so_number' => $so->so_number,
                    'po_number' => $so->client_po_number ?? 'N/A',
                    'customer' => $so->customer->name ?? 'Unknown',
                    'timeline' => $timeline,
                    'overall_lead_time' => $payAt ? $createdAt->diffForHumans($payAt, true) : 'Ongoing',
                ];
            }
        }

        return Inertia::render('Sales/Information', [
            'chartData' => $chartData,
            'chartPercentages' => $chartPercentages,
            'searchResult' => $searchResult,
            'queryParams' => $request->only(['search']),
        ]);
    }
}
