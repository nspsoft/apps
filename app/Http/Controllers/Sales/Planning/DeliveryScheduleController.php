<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DeliverySchedule;
use App\Imports\DeliveryScheduleImport;
use App\Exports\DeliveryScheduleExport;
use App\Exports\DeliveryScheduleTemplateExport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;

class DeliveryScheduleController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'delivery_date');
        $direction = $request->input('direction', 'asc');

        $query = DeliverySchedule::with(['customer', 'product.unit', 'created_by_user'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                      ->orWhere('po_number', 'like', "%{$search}%");
            })
            ->when($request->date, function ($query, $date) {
                $query->whereDate('delivery_date', $date);
            });

        if ($sort === 'customer_name') {
            $query->join('customers', 'delivery_schedules.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('delivery_schedules.*');
        } elseif ($sort === 'product_name') {
            $query->join('products', 'delivery_schedules.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction)
                  ->select('delivery_schedules.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $schedules = $query->paginate(10)->withQueryString();

        return Inertia::render('Sales/Planning/Schedule/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search', 'date', 'sort', 'direction']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'sales_name' => 'nullable|string',
        ]);

        try {
            Excel::import(new DeliveryScheduleImport($request->sales_name), $request->file('file'));
            return back()->with('success', 'Delivery Schedule imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'date']);
        return Excel::download(new DeliveryScheduleExport($filters), 'delivery_schedule_' . now()->format('YmdHis') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new DeliveryScheduleTemplateExport, 'delivery_schedule_template.xlsx');
    }

    public function comparison(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->search;
        $mode = $request->mode ?? 'daily'; // 'daily' or 'weekly'

        // Generate list of dates for headers
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Generate week ranges for weekly mode
        $weeks = [];
        if ($mode === 'weekly') {
            $wStart = $startDate->copy();
            $weekNum = 1;
            while ($wStart <= $endDate) {
                $wEnd = $wStart->copy()->endOfWeek(Carbon::SUNDAY); // Mon-Sun
                if ($wEnd > $endDate) $wEnd = $endDate->copy();
                $weeks[] = [
                    'key' => 'W' . $weekNum,
                    'label' => $wStart->format('d') . '-' . $wEnd->format('d M'),
                    'start' => $wStart->format('Y-m-d'),
                    'end' => $wEnd->format('Y-m-d'),
                ];
                $weekNum++;
                $wStart = $wEnd->copy()->addDay();
            }
        }

        // Get Schedules
        $schedules = DeliverySchedule::with(['customer', 'product.unit'])
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->when($search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                      ->orWhere('po_number', 'like', "%{$search}%");
            })
            ->get();

        // Get Actuals
        $actuals = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('delivery_date', [$startDate, $endDate])
                  ->whereIn('status', [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_COMPLETED]);
            })
            ->select(
                'delivery_orders.delivery_date',
                'delivery_orders.customer_id',
                'delivery_order_items.product_id',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered')
            )
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->groupBy('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id')
            ->get();

        // Map Actuals
        $actualsMap = [];
        foreach ($actuals as $act) {
            $d = Carbon::parse($act->delivery_date)->format('Y-m-d');
            $actualsMap[$act->customer_id][$act->product_id][$d] = (float) $act->total_delivered;
        }

        // Combine into Matrix
        $matrix = [];
        foreach ($schedules as $sch) {
            $custId = $sch->customer_id;
            $prodId = $sch->product_id;
            $d = Carbon::parse($sch->delivery_date)->format('Y-m-d');
            
            if (!isset($matrix[$custId])) {
                $matrix[$custId] = [
                    'customer_name' => $sch->customer->name,
                    'customer_code' => $sch->customer->code,
                    'products' => []
                ];
            }

            if (!isset($matrix[$custId]['products'][$prodId])) {
                $matrix[$custId]['products'][$prodId] = [
                    'product_name' => $sch->product->name,
                    'sku' => $sch->product->sku,
                    'unit' => $sch->product->unit->code ?? 'PCS',
                    'po_number' => $sch->po_number,
                    'daily' => [],
                    'totals' => ['sch' => 0, 'act' => 0, 'bal' => 0]
                ];
            }

            $actQty = $actualsMap[$custId][$prodId][$d] ?? 0;
            $schQty = (float) $sch->qty_scheduled;
            $bal = $actQty - $schQty;

            $matrix[$custId]['products'][$prodId]['daily'][$d] = [
                'sch' => $schQty,
                'act' => $actQty,
                'bal' => $bal
            ];

            $matrix[$custId]['products'][$prodId]['totals']['sch'] += $schQty;
            $matrix[$custId]['products'][$prodId]['totals']['act'] += $actQty;
            $matrix[$custId]['products'][$prodId]['totals']['bal'] += $bal;
        }

        // Reformat for Frontend
        $formattedMatrix = [];
        foreach ($matrix as $custId => $cData) {
            $products = [];
            foreach ($cData['products'] as $prodId => $pData) {
                // Fill missing dates
                foreach ($dates as $date) {
                    if (!isset($pData['daily'][$date])) {
                        $actQty = $actualsMap[$custId][$prodId][$date] ?? 0;
                        $pData['daily'][$date] = ['sch' => 0, 'act' => $actQty, 'bal' => $actQty];
                        if ($actQty > 0) {
                            $pData['totals']['act'] += $actQty;
                            $pData['totals']['bal'] += $actQty;
                        }
                    }
                }

                // Aggregate into weeks if weekly mode
                if ($mode === 'weekly') {
                    $weeklyData = [];
                    foreach ($weeks as $week) {
                        $wSch = 0; $wAct = 0;
                        foreach ($dates as $date) {
                            if ($date >= $week['start'] && $date <= $week['end']) {
                                $wSch += $pData['daily'][$date]['sch'] ?? 0;
                                $wAct += $pData['daily'][$date]['act'] ?? 0;
                            }
                        }
                        $weeklyData[$week['key']] = ['sch' => $wSch, 'act' => $wAct, 'bal' => $wAct - $wSch];
                    }
                    $pData['daily'] = $weeklyData;
                }

                $products[] = $pData;
            }
            $cData['products'] = $products;
            $formattedMatrix[] = $cData;
        }

        // Use week keys as column headers for weekly mode
        $columnHeaders = $mode === 'weekly' 
            ? array_map(fn($w) => $w['key'], $weeks) 
            : $dates;

        return Inertia::render('Sales/Planning/Schedule/Comparison', [
            'dates' => $columnHeaders,
            'matrix' => $formattedMatrix,
            'weeks' => $mode === 'weekly' ? $weeks : [],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => $search,
                'mode' => $mode,
            ]
        ]);
    }

    public function printSchedule(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->search;
        $mode = $request->mode ?? 'daily';

        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Generate week ranges for weekly mode
        $weeks = [];
        if ($mode === 'weekly') {
            $wStart = $startDate->copy();
            $weekNum = 1;
            while ($wStart <= $endDate) {
                $wEnd = $wStart->copy()->endOfWeek(Carbon::SUNDAY);
                if ($wEnd > $endDate) $wEnd = $endDate->copy();
                $weeks[] = [
                    'key' => 'W' . $weekNum,
                    'label' => 'Week ' . $weekNum . "\n" . $wStart->format('d') . '-' . $wEnd->format('d M'),
                    'start' => $wStart->format('Y-m-d'),
                    'end' => $wEnd->format('Y-m-d'),
                ];
                $weekNum++;
                $wStart = $wEnd->copy()->addDay();
            }
        }

        $schedules = DeliverySchedule::with(['customer', 'product.unit'])
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->when($search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->get();

        $actuals = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('delivery_date', [$startDate, $endDate])
                  ->whereIn('status', [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_COMPLETED]);
            })
            ->select('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered'))
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->groupBy('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id')
            ->get();

        $actualsMap = [];
        foreach ($actuals as $act) {
            $d = Carbon::parse($act->delivery_date)->format('Y-m-d');
            $actualsMap[$act->customer_id][$act->product_id][$d] = (float) $act->total_delivered;
        }

        $matrix = [];
        foreach ($schedules as $sch) {
            $custId = $sch->customer_id;
            $prodId = $sch->product_id;
            $d = Carbon::parse($sch->delivery_date)->format('Y-m-d');

            if (!isset($matrix[$custId])) {
                $matrix[$custId] = [
                    'customer_name' => $sch->customer->name,
                    'customer_code' => $sch->customer->code,
                    'products' => []
                ];
            }
            if (!isset($matrix[$custId]['products'][$prodId])) {
                $matrix[$custId]['products'][$prodId] = [
                    'product_name' => $sch->product->name,
                    'sku' => $sch->product->sku,
                    'unit' => $sch->product->unit->code ?? 'PCS',
                    'po_number' => $sch->po_number,
                    'daily' => [],
                    'totals' => ['sch' => 0, 'act' => 0, 'bal' => 0]
                ];
            }

            $actQty = $actualsMap[$custId][$prodId][$d] ?? 0;
            $schQty = (float) $sch->qty_scheduled;
            $bal = $actQty - $schQty;

            $matrix[$custId]['products'][$prodId]['daily'][$d] = ['sch' => $schQty, 'act' => $actQty, 'bal' => $bal];
            $matrix[$custId]['products'][$prodId]['totals']['sch'] += $schQty;
            $matrix[$custId]['products'][$prodId]['totals']['act'] += $actQty;
            $matrix[$custId]['products'][$prodId]['totals']['bal'] += $bal;
        }

        // Fill missing dates
        foreach ($matrix as $custId => &$cData) {
            $products = [];
            foreach ($cData['products'] as $prodId => $pData) {
                foreach ($dates as $date) {
                    if (!isset($pData['daily'][$date])) {
                        $actQty = $actualsMap[$custId][$prodId][$date] ?? 0;
                        $pData['daily'][$date] = ['sch' => 0, 'act' => $actQty, 'bal' => $actQty];
                        if ($actQty > 0) {
                            $pData['totals']['act'] += $actQty;
                            $pData['totals']['bal'] += $actQty;
                        }
                    }
                }

                // Aggregate into weeks if weekly mode
                if ($mode === 'weekly') {
                    $weeklyData = [];
                    foreach ($weeks as $week) {
                        $wSch = 0; $wAct = 0;
                        foreach ($dates as $date) {
                            if ($date >= $week['start'] && $date <= $week['end']) {
                                $wSch += $pData['daily'][$date]['sch'] ?? 0;
                                $wAct += $pData['daily'][$date]['act'] ?? 0;
                            }
                        }
                        $weeklyData[$week['key']] = ['sch' => $wSch, 'act' => $wAct, 'bal' => $wAct - $wSch];
                    }
                    $pData['daily'] = $weeklyData;
                }
                $products[] = $pData;
            }
            $cData['products'] = $products;
        }

        $columnHeaders = $mode === 'weekly' 
            ? $weeks 
            : $dates;

        return view('print.delivery-schedule-matrix', [
            'headers' => $columnHeaders,
            'matrix' => $matrix,
            'period' => $mode === 'weekly' ? 'Weekly View: ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') : $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'printDate' => Carbon::now()->format('d/m/Y H:i'),
            'today' => Carbon::now()->format('Y-m-d'),
            'mode' => $mode,
        ]);
    }
}
