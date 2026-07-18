<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrderItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\Product;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ExecutiveReportService
{
    protected $gateway;
    protected string $provider;

    public function __construct()
    {
        $this->provider = 'wablas';
        $this->gateway = app(WablasService::class);
    }

    /**
     * Generate and send the executive report to all registered owners
     */
    public function generateAndSendWeeklyReport(bool $dryRun = false): array
    {
        // 1. Define date range (Last 7 Days)
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        // 2. Fetch Report Data
        $data = $this->gatherReportData($startDate, $endDate);

        // 3. Generate PDF
        $pdfUrl = $this->generatePdfReport($data);

        if (empty($pdfUrl)) {
            return [
                'success' => false,
                'message' => 'Failed to generate PDF Report',
            ];
        }

        if ($dryRun) {
            return [
                'success' => true,
                'message' => '[Dry Run] PDF generated successfully. WhatsApp sending skipped.',
                'pdf_url' => $pdfUrl,
                'success_count' => 0,
            ];
        }

        // 4. Send to Owner(s)
        $ownerNumbers = $this->getOwnerNumbers();
        if (empty($ownerNumbers)) {
            Log::warning('No owner WhatsApp numbers configured for Executive Report.');
            return [
                'success' => false,
                'message' => 'No owner WhatsApp numbers configured. Please set owner_whatsapp_numbers in AppSetting.',
                'pdf_url' => $pdfUrl,
            ];
        }

        $caption = "📊 *LAPORAN EKSEKUTIF MINGGUAN (SALES & PURCHASING)*\n" .
                   "Periode: " . $startDate->format('d M Y') . " - " . $endDate->format('d M Y') . "\n\n" .
                   "Halo Bapak/Ibu Management,\n\n" .
                   "Terlampir Resume & Grafik Tren Kinerja Bisnis JICOS ERP untuk periode 7 hari terakhir.\n\n" .
                   "• *Total Sales:* Rp " . number_format($data['sales_total'], 0, ',', '.') . " (" . $data['sales_count'] . " SO)\n" .
                   "• *Total Purchasing:* Rp " . number_format($data['purchase_total'], 0, ',', '.') . " (" . $data['purchase_count'] . " PO)\n" .
                   "• *Outstanding Invoice Sales:* Rp " . number_format($data['sales_outstanding'], 0, ',', '.') . "\n" .
                   "• *Outstanding Invoice Supplier:* Rp " . number_format($data['purchase_outstanding'], 0, ',', '.') . "\n\n" .
                   "_Laporan dicetak otomatis oleh sistem._";

        $successCount = 0;
        foreach ($ownerNumbers as $phone) {
            try {
                $result = $this->gateway->sendFile($phone, $pdfUrl, $caption);
                if ($result['success'] ?? false) {
                    $successCount++;
                } else {
                    Log::error("Failed sending Executive PDF to {$phone}: " . ($result['error'] ?? 'Unknown gateway error'));
                }
            } catch (\Exception $e) {
                Log::error("Exception sending Executive PDF to {$phone}: " . $e->getMessage());
            }
        }

        return [
            'success' => $successCount > 0,
            'message' => "Report sent successfully to {$successCount} of " . count($ownerNumbers) . " owners.",
            'pdf_url' => $pdfUrl,
            'success_count' => $successCount,
        ];
    }

    /**
     * Gather sales and purchasing metrics for the specified date range
     */
    public function gatherReportData(Carbon $startDate, Carbon $endDate): array
    {
        // Previous period for comparison (last-last week)
        $daysDiff = $startDate->diffInDays($endDate) + 1;
        $prevEndDate = $startDate->copy()->subSecond();
        $prevStartDate = $startDate->copy()->subDays($daysDiff);

        // --- Sales Orders ---
        $salesTotal = SalesOrder::where('status', '!=', SalesOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');
        
        $salesCount = SalesOrder::where('status', '!=', SalesOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        $prevSalesTotal = SalesOrder::where('status', '!=', SalesOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->sum('total');

        $salesChangePercent = $prevSalesTotal > 0 
            ? (($salesTotal - $prevSalesTotal) / $prevSalesTotal) * 100 
            : 0;

        // --- Purchase Orders ---
        $purchaseTotal = PurchaseOrder::where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        $purchaseCount = PurchaseOrder::where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        $prevPurchaseTotal = PurchaseOrder::where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->sum('total');

        $purchaseChangePercent = $prevPurchaseTotal > 0 
            ? (($purchaseTotal - $prevPurchaseTotal) / $prevPurchaseTotal) * 100 
            : 0;

        // --- Outstanding Invoices ---
        // Sales Invoices balance (total unpaid customer invoices)
        $salesOutstanding = SalesInvoice::whereIn('status', [SalesInvoice::STATUS_SENT, SalesInvoice::STATUS_PARTIAL])
            ->sum('balance');

        $salesOverdue = SalesInvoice::whereIn('status', [SalesInvoice::STATUS_SENT, SalesInvoice::STATUS_PARTIAL])
            ->where('due_date', '<', Carbon::now())
            ->sum('balance');

        // Supplier Invoices amount_due (total unpaid vendor invoices)
        $purchaseOutstanding = PurchaseInvoice::whereIn('status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIAL])
            ->get()
            ->sum('amount_due');

        $purchaseOverdue = PurchaseInvoice::whereIn('status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIAL])
            ->where('due_date', '<', Carbon::now())
            ->get()
            ->sum('amount_due');

        // --- Top 5 Products by Sales Volume ---
        $topProducts = SalesOrderItem::select('product_id', \DB::raw('SUM(qty) as total_qty'), \DB::raw('SUM(subtotal) as total_amount'))
            ->whereHas('salesOrder', function($q) use ($startDate, $endDate) {
                $q->where('status', '!=', SalesOrder::STATUS_CANCELLED)
                  ->whereBetween('order_date', [$startDate, $endDate]);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_amount')
            ->with('product')
            ->limit(5)
            ->get();

        // --- Top 5 Suppliers by Purchase Amount ---
        $topSuppliers = PurchaseOrder::select('supplier_id', \DB::raw('SUM(total) as total_amount'), \DB::raw('COUNT(id) as total_orders'))
            ->where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->with('supplier')
            ->limit(5)
            ->get();

        // --- Daily Trend Data & SVG Coordinate Calculations ---
        $dailyTrends = [];
        $tempDate = $startDate->copy();
        $daysList = [];
        $salesTrend = [];
        $purchaseTrend = [];

        while ($tempDate->lte($endDate)) {
            $dateStr = $tempDate->toDateString();
            $daysList[] = $tempDate->format('D (d/m)');

            $dailySales = SalesOrder::where('status', '!=', SalesOrder::STATUS_CANCELLED)
                ->whereDate('order_date', $dateStr)
                ->sum('total');

            $dailyPurchase = PurchaseOrder::where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
                ->whereDate('order_date', $dateStr)
                ->sum('total');

            $salesTrend[] = (float) $dailySales;
            $purchaseTrend[] = (float) $dailyPurchase;

            $dailyTrends[] = [
                'date' => $tempDate->format('d M Y'),
                'sales' => $dailySales,
                'purchase' => $dailyPurchase,
            ];

            $tempDate->addDay();
        }

        // SVG Chart coordinates
        // Canvas dimensions: width=500, height=150
        // Plotting margins: Left=60, Right=20, Top=15, Bottom=30
        $width = 500;
        $height = 150;
        $plotWidth = 420;
        $plotHeight = 105;
        $leftMargin = 60;
        $topMargin = 15;

        $maxVal = max(array_merge($salesTrend, $purchaseTrend));
        if ($maxVal <= 0) $maxVal = 1000000; // default minimum scaling

        $salesPoints = "";
        $purchasePoints = "";
        $chartLabels = [];
        $gridLines = [];

        $pointsCount = count($daysList);
        $xInterval = $plotWidth / max(1, $pointsCount - 1);

        for ($i = 0; $i < $pointsCount; $i++) {
            $x = $leftMargin + ($i * $xInterval);
            
            // Sales point
            $ySales = ($height - 30) - (($salesTrend[$i] / $maxVal) * $plotHeight);
            $salesPoints .= "{$x},{$ySales} ";

            // Purchase point
            $yPurchase = ($height - 30) - (($purchaseTrend[$i] / $maxVal) * $plotHeight);
            $purchasePoints .= "{$x},{$yPurchase} ";

            // Label x-axis
            $chartLabels[] = [
                'x' => $x,
                'y' => $height - 10,
                'text' => $daysList[$i]
            ];

            // Vertical grid line
            $gridLines[] = [
                'x1' => $x,
                'y1' => $topMargin,
                'x2' => $x,
                'y2' => $height - 30
            ];
        }

        // Horizontal Y grid lines and labels (4 lines)
        $yLabels = [];
        for ($j = 0; $j <= 3; $j++) {
            $val = ($maxVal / 3) * $j;
            $y = ($height - 30) - (($val / $maxVal) * $plotHeight);

            // Format to shorter text: e.g. 1.2M, 500K
            $labelStr = $this->formatShortCurrency($val);

            $yLabels[] = [
                'x' => $leftMargin - 10,
                'y' => $y + 4,
                'text' => $labelStr
            ];

            // Horizontal line
            $gridLines[] = [
                'x1' => $leftMargin,
                'y1' => $y,
                'x2' => $width - 20,
                'y2' => $y
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sales_total' => $salesTotal,
            'sales_count' => $salesCount,
            'sales_change_percent' => $salesChangePercent,
            'purchase_total' => $purchaseTotal,
            'purchase_count' => $purchaseCount,
            'purchase_change_percent' => $purchaseChangePercent,
            'sales_outstanding' => $salesOutstanding,
            'sales_overdue' => $salesOverdue,
            'purchase_outstanding' => $purchaseOutstanding,
            'purchase_overdue' => $purchaseOverdue,
            'top_products' => $topProducts,
            'top_suppliers' => $topSuppliers,
            'daily_trends' => $dailyTrends,
            // SVG plotting data
            'svg' => [
                'width' => $width,
                'height' => $height,
                'sales_points' => trim($salesPoints),
                'purchase_points' => trim($purchasePoints),
                'labels' => $chartLabels,
                'y_labels' => $yLabels,
                'grid_lines' => $gridLines,
                'max_val' => $maxVal
            ]
        ];
    }

    /**
     * Generate the PDF report using laravel-dompdf
     */
    protected function generatePdfReport(array $data): string
    {
        try {
            $pdf = Pdf::loadView('pdf.executive_report', $data)->setPaper('a4', 'portrait');

            $filename = 'executive_report_' . time() . '.pdf';
            $path = 'reports/' . $filename;

            // Ensure directory exists in public storage
            if (!Storage::disk('public')->exists('reports')) {
                Storage::disk('public')->makeDirectory('reports');
            }

            Storage::disk('public')->put($path, $pdf->output());

            return config('app.url') . '/storage/' . $path;
        } catch (\Exception $e) {
            Log::error('Failed to generate Executive PDF: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Get owner numbers from settings or environment
     */
    public function getOwnerNumbers(): array
    {
        $numbersStr = AppSetting::get('owner_whatsapp_numbers');
        
        // Fallback to environment variable or general settings
        if (empty($numbersStr)) {
            $numbersStr = config('services.whatsapp.owner_numbers', '');
        }

        if (empty($numbersStr)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $numbersStr)));
    }

    /**
     * Helper to format large currencies into short labels (e.g., 2.5jt, 1.2M)
     */
    protected function formatShortCurrency($val): string
    {
        if ($val >= 1000000000) {
            return round($val / 1000000000, 1) . ' M';
        } elseif ($val >= 1000000) {
            return round($val / 1000000, 1) . ' jt';
        } elseif ($val >= 1000) {
            return round($val / 1000, 1) . ' K';
        }
        return (string) $val;
    }
}
