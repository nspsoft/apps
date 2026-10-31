<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WhatsappReportSchedule;
use App\Services\InventoryReportGeneratorService;
use App\Services\LogisticsReportGeneratorService;
use App\Services\ManufacturingReportGeneratorService;
use App\Services\PurchasingReportGeneratorService;
use App\Services\SalesReportGeneratorService;
use App\Services\UsicsWhatsappEngineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappReportScheduleController extends Controller
{
    protected InventoryReportGeneratorService $reportGenerator;
    protected SalesReportGeneratorService $salesReportGenerator;
    protected PurchasingReportGeneratorService $purchasingReportGenerator;
    protected ManufacturingReportGeneratorService $manufacturingReportGenerator;
    protected LogisticsReportGeneratorService $logisticsReportGenerator;
    protected UsicsWhatsappEngineService $engine;

    public function __construct(
        InventoryReportGeneratorService $reportGenerator,
        SalesReportGeneratorService $salesReportGenerator,
        PurchasingReportGeneratorService $purchasingReportGenerator,
        ManufacturingReportGeneratorService $manufacturingReportGenerator,
        LogisticsReportGeneratorService $logisticsReportGenerator,
        UsicsWhatsappEngineService $engine
    ) {
        $this->reportGenerator = $reportGenerator;
        $this->salesReportGenerator = $salesReportGenerator;
        $this->purchasingReportGenerator = $purchasingReportGenerator;
        $this->manufacturingReportGenerator = $manufacturingReportGenerator;
        $this->logisticsReportGenerator = $logisticsReportGenerator;
        $this->engine = $engine;
    }

    /**
     * Display the Multi-Module WhatsApp Report Scheduler & Dispatcher Hub
     */
    public function index()
    {
        $this->ensureInitialSeed();

        $schedules = WhatsappReportSchedule::orderBy('module')
            ->orderBy('send_time')
            ->get();

        // Fetch Internal Employees with phone numbers for quick recipient selection
        $employees = [];
        try {
            $employees = Employee::with(['department', 'position'])
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->orderBy('full_name')
                ->limit(100)
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->full_name,
                    'phone' => $e->phone,
                    'role' => $e->position?->name ?? ($e->department?->name ?? 'Staff Internal'),
                ]);
        } catch (\Throwable $e) {
            $employees = [];
        }

        // Fetch connected WhatsApp Groups from Engine
        $groupsRes = $this->engine->setSession('inventory')->getGroups('inventory');
        $groups = $groupsRes['groups'] ?? [];

        // Preview live metrics for Inventory, Sales, Purchasing, Manufacturing & Logistics
        $liveMetrics = $this->reportGenerator->collectInventoryMetrics('weekly');
        $salesMetrics = $this->salesReportGenerator->collectSalesMetrics('weekly');
        $purchasingMetrics = $this->purchasingReportGenerator->collectPurchasingMetrics('weekly');
        $manufacturingMetrics = $this->manufacturingReportGenerator->collectManufacturingMetrics('weekly');
        $logisticsMetrics = $this->logisticsReportGenerator->collectLogisticsMetrics('weekly');

        // Ensure cover preview images exist
        $reportsDir = storage_path('app/public/reports');
        $coverPreviewPath = "{$reportsDir}/cover_bg_warehouse.png";
        $logoPreviewPath = "{$reportsDir}/company_logo_cover.png";
        if (!file_exists($coverPreviewPath)) {
            $this->reportGenerator->generateCoverBackgroundPng($coverPreviewPath);
        }
        if (!file_exists($logoPreviewPath)) {
            $this->reportGenerator->prepareCompanyLogoPng($logoPreviewPath);
        }

        $customBgPath = (string) \App\Models\AppSetting::get('wa_report_cover_bg_custom_path', '');
        $customLogoPath = (string) \App\Models\AppSetting::get('wa_report_cover_logo_path', '');

        return Inertia::render('Settings/WhatsappReportSettings', [
            'schedules' => $schedules,
            'employees' => $employees,
            'whatsappGroups' => $groups,
            'liveMetrics' => [
                'total_stock_value_formatted' => $liveMetrics['total_stock_value_formatted'],
                'total_active_skus' => $liveMetrics['total_active_skus'],
                'low_stock_count' => $liveMetrics['low_stock_count'],
                'opname_accuracy' => $liveMetrics['opname_accuracy'],
                'total_inbound_tx' => $liveMetrics['total_inbound_tx'],
                'total_outbound_tx' => $liveMetrics['total_outbound_tx'],
            ],
            'salesMetrics' => [
                'total_revenue_formatted' => $salesMetrics['total_revenue_formatted'],
                'total_so_count' => $salesMetrics['total_so_count'],
                'win_rate' => $salesMetrics['win_rate'],
                'on_time_delivery_rate' => $salesMetrics['on_time_delivery_rate'],
                'quotation_pipeline_formatted' => $salesMetrics['quotation_pipeline_formatted'],
                'paid_invoices' => $salesMetrics['paid_invoices'],
                'total_invoices' => $salesMetrics['total_invoices'],
            ],
            'purchasingMetrics' => [
                'total_spend_formatted' => $purchasingMetrics['total_spend_formatted'],
                'total_po_count' => $purchasingMetrics['total_po_count'],
                'open_po_count' => $purchasingMetrics['open_po_count'],
                'pr_conversion_rate' => $purchasingMetrics['pr_conversion_rate'],
                'on_time_grn_rate' => $purchasingMetrics['on_time_grn_rate'],
                'paid_invoices' => $purchasingMetrics['paid_invoices'],
                'total_invoices' => $purchasingMetrics['total_invoices'],
                'active_suppliers_count' => $purchasingMetrics['active_suppliers_count'],
            ],
            'manufacturingMetrics' => [
                'total_output_formatted' => $manufacturingMetrics['total_output_formatted'],
                'total_wo_count' => $manufacturingMetrics['total_wo_count'],
                'in_progress_wo_count' => $manufacturingMetrics['in_progress_wo_count'],
                'completed_wo_count' => $manufacturingMetrics['completed_wo_count'],
                'oee_plant_rate' => $manufacturingMetrics['oee_plant_rate'],
                'machine_uptime_rate' => $manufacturingMetrics['machine_uptime_rate'],
                'scrap_rate' => $manufacturingMetrics['scrap_rate'],
                'yield_rate' => $manufacturingMetrics['yield_rate'],
                'on_time_wo_rate' => $manufacturingMetrics['on_time_wo_rate'],
            ],
            'logisticsMetrics' => [
                'total_tonnage_formatted' => $logisticsMetrics['total_tonnage_formatted'],
                'total_do_count' => $logisticsMetrics['total_do_count'],
                'otd_rate' => $logisticsMetrics['otd_rate'],
                'fleet_utilization' => $logisticsMetrics['fleet_utilization'],
                'total_vehicles' => $logisticsMetrics['total_vehicles'],
                'active_vehicles' => $logisticsMetrics['active_vehicles'],
                'total_operational_cost_formatted' => $logisticsMetrics['total_operational_cost_formatted'],
            ],
            'coverSettings' => [
                'bg_mode' => (string) \App\Models\AppSetting::get('wa_report_cover_bg_mode', 'preset'),
                'bg_preset' => (string) \App\Models\AppSetting::get('wa_report_cover_bg_preset', 'slitting_machine'),
                'bg_custom_url' => $customBgPath ? asset('storage/' . ltrim(str_replace('/storage/', '', $customBgPath), '/')) : null,
                'opacity' => (int) \App\Models\AppSetting::get('wa_report_cover_opacity', 30),
                'logo_custom_url' => $customLogoPath ? asset('storage/' . ltrim(str_replace('/storage/', '', $customLogoPath), '/')) : null,
                'preview_bg_url' => asset('storage/reports/cover_bg_warehouse.png') . '?t=' . (@filemtime($coverPreviewPath) ?: time()),
                'preview_logo_url' => asset('storage/reports/company_logo_cover.png') . '?t=' . (@filemtime($logoPreviewPath) ?: time()),
            ],
        ]);
    }

    /**
     * Store a new WhatsApp Report Schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'module' => 'required|string|in:inventory,sales,purchasing,manufacturing,logistics,finance',
            'report_type' => 'required|string|max:80',
            'frequency' => 'required|string|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'send_time' => 'required|string|max:10',
            'timezone' => 'nullable|string|max:40',
            'formats' => 'required|array|min:1',
            'formats.*' => 'string|in:pptx,docx',
            'recipients' => 'required|array|min:1',
            'recipients.*.type' => 'required|string|in:phone,group',
            'recipients.*.target' => 'required|string|max:120',
            'recipients.*.name' => 'required|string|max:120',
            'whatsapp_session' => 'nullable|string|in:sales,purchasing,inventory',
            'include_ai_summary' => 'boolean',
            'include_charts' => 'boolean',
            'include_low_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['timezone'] = $validated['timezone'] ?? 'Asia/Jakarta';
        $validated['whatsapp_session'] = $validated['whatsapp_session'] ?? ($validated['module'] === 'inventory' ? 'inventory' : 'sales');

        WhatsappReportSchedule::create($validated);

        return redirect()->back()->with('success', 'Jadwal pengiriman laporan WhatsApp berhasil ditambahkan.');
    }

    /**
     * Update an existing WhatsApp Report Schedule
     */
    public function update(Request $request, WhatsappReportSchedule $schedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'module' => 'required|string|in:inventory,sales,purchasing,manufacturing,logistics,finance',
            'report_type' => 'required|string|max:80',
            'frequency' => 'required|string|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'send_time' => 'required|string|max:10',
            'timezone' => 'nullable|string|max:40',
            'formats' => 'required|array|min:1',
            'formats.*' => 'string|in:pptx,docx',
            'recipients' => 'required|array|min:1',
            'recipients.*.type' => 'required|string|in:phone,group',
            'recipients.*.target' => 'required|string|max:120',
            'recipients.*.name' => 'required|string|max:120',
            'whatsapp_session' => 'nullable|string|in:sales,purchasing,inventory',
            'include_ai_summary' => 'boolean',
            'include_charts' => 'boolean',
            'include_low_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $schedule->update($validated);

        return redirect()->back()->with('success', 'Jadwal pengiriman laporan WhatsApp berhasil diperbarui.');
    }

    /**
     * Toggle active state of a schedule
     */
    public function toggle(WhatsappReportSchedule $schedule)
    {
        $schedule->update([
            'is_active' => !$schedule->is_active,
        ]);

        return redirect()->back()->with('success', 'Status jadwal berhasil diubah.');
    }

    /**
     * Delete a schedule
     */
    public function destroy(WhatsappReportSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal pengiriman laporan berhasil dihapus.');
    }

    /**
     * Trigger immediate report generation & WhatsApp dispatch ("Kirim Sekarang")
     */
    public function sendNow(WhatsappReportSchedule $schedule)
    {
        if ($schedule->module === 'sales') {
            $result = $this->salesReportGenerator->dispatchScheduledReport($schedule);
        } elseif ($schedule->module === 'purchasing') {
            $result = $this->purchasingReportGenerator->dispatchScheduledReport($schedule);
        } elseif ($schedule->module === 'manufacturing') {
            $result = $this->manufacturingReportGenerator->dispatchScheduledReport($schedule);
        } elseif ($schedule->module === 'logistics') {
            $result = $this->logisticsReportGenerator->dispatchScheduledReport($schedule);
        } else {
            $result = $this->reportGenerator->dispatchScheduledReport($schedule);
        }

        if ($result['sent_count'] > 0) {
            return redirect()->back()->with('success', "Laporan {$schedule->name} berhasil dibuat dan dikirim ke {$result['sent_count']} tujuan WhatsApp!");
        }

        $errDetail = !empty($result['errors']) ? implode(', ', $result['errors']) : 'Pastikan sesi WhatsApp terhubung.';
        return redirect()->back()->with('error', "File laporan berhasil di-generate, namun pengiriman WA gagal: {$errDetail}");
    }

    /**
     * Fetch WhatsApp Groups dynamically via AJAX
     */
    public function fetchGroups(Request $request)
    {
        $session = $request->query('session', 'inventory');
        $res = $this->engine->setSession($session)->getGroups($session);

        return response()->json($res);
    }

    /**
     * Update Cover Background Image, Opacity & Logo Settings for PPTX Report
     */
    public function updateCoverSettings(Request $request)
    {
        $request->validate([
            'bg_mode' => 'required|string|in:preset,custom',
            'bg_preset' => 'nullable|string|in:slitting_machine,shearing_machine,blanking_press,laser_welder',
            'opacity' => 'required|integer|min:10|max:75',
            'custom_bg_file' => 'nullable|image|max:5120',
            'custom_logo_file' => 'nullable|image|max:2048',
            'reset_logo' => 'nullable|boolean',
        ]);

        \App\Models\AppSetting::set('wa_report_cover_bg_mode', $request->input('bg_mode', 'preset'), 'whatsapp_report', 'Cover Background Mode');
        if ($request->filled('bg_preset')) {
            \App\Models\AppSetting::set('wa_report_cover_bg_preset', $request->input('bg_preset'), 'whatsapp_report', 'Cover Background Preset');
        }
        \App\Models\AppSetting::set('wa_report_cover_opacity', (string) $request->input('opacity', 30), 'whatsapp_report', 'Cover Background Opacity');

        if ($request->hasFile('custom_bg_file')) {
            $path = $request->file('custom_bg_file')->store('reports/custom_bg', 'public');
            \App\Models\AppSetting::set('wa_report_cover_bg_custom_path', $path, 'whatsapp_report', 'Custom Cover Background Image');
            \App\Models\AppSetting::set('wa_report_cover_bg_mode', 'custom', 'whatsapp_report', 'Cover Background Mode');
        }

        if ($request->boolean('reset_logo')) {
            \App\Models\AppSetting::set('wa_report_cover_logo_path', '', 'whatsapp_report', 'Custom Cover Logo Path');
        } elseif ($request->hasFile('custom_logo_file')) {
            $logoPath = $request->file('custom_logo_file')->store('reports/custom_logo', 'public');
            \App\Models\AppSetting::set('wa_report_cover_logo_path', $logoPath, 'whatsapp_report', 'Custom Cover Logo Path');
        }

        // Regenerate cover background and logo PNGs immediately
        $reportsDir = storage_path('app/public/reports');
        $this->reportGenerator->generateCoverBackgroundPng("{$reportsDir}/cover_bg_warehouse.png");
        $this->reportGenerator->prepareCompanyLogoPng("{$reportsDir}/company_logo_cover.png");

        return redirect()->back()->with('success', 'Desain Cover, Background Pabrik, & Transparansi berhasil diperbarui!');
    }

    /**
     * Download freshly generated PPTX or DOCX report directly in browser
     */
    public function downloadSample(Request $request, string $format)
    {
        $frequency = $request->query('frequency', 'weekly');
        $module = $request->query('module', 'inventory');

        if ($module === 'sales') {
            $metrics = $this->salesReportGenerator->collectSalesMetrics($frequency);
            if ($format === 'pptx') {
                $file = $this->salesReportGenerator->generatePptxReport($metrics, $frequency);
                return response()->download($file['path'], $file['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]);
            }
            $file = $this->salesReportGenerator->generateDocxReport($metrics, $frequency);
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        if ($module === 'purchasing') {
            $metrics = $this->purchasingReportGenerator->collectPurchasingMetrics($frequency);
            if ($format === 'pptx') {
                $file = $this->purchasingReportGenerator->generatePptxReport($metrics, $frequency);
                return response()->download($file['path'], $file['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]);
            }
            $file = $this->purchasingReportGenerator->generateDocxReport($metrics, $frequency);
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        if ($module === 'manufacturing') {
            $metrics = $this->manufacturingReportGenerator->collectManufacturingMetrics($frequency);
            if ($format === 'pptx') {
                $file = $this->manufacturingReportGenerator->generatePptxReport($metrics, $frequency);
                return response()->download($file['path'], $file['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]);
            }
            $file = $this->manufacturingReportGenerator->generateDocxReport($metrics, $frequency);
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        if ($module === 'logistics') {
            $metrics = $this->logisticsReportGenerator->collectLogisticsMetrics($frequency);
            if ($format === 'pptx') {
                $file = $this->logisticsReportGenerator->generatePptxReport($metrics, $frequency);
                return response()->download($file['path'], $file['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]);
            }
            $file = $this->logisticsReportGenerator->generateDocxReport($metrics, $frequency);
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        $metrics = $this->reportGenerator->collectInventoryMetrics($frequency);

        if ($format === 'pptx') {
            $file = $this->reportGenerator->generatePptxReport($metrics, $frequency);
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]);
        }

        $file = $this->reportGenerator->generateDocxReport($metrics, $frequency);
        return response()->download($file['path'], $file['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Seed realistic default Inventory & Sales schedules if missing
     */
    protected function ensureInitialSeed(): void
    {
        if (WhatsappReportSchedule::where('module', 'inventory')->count() === 0) {
            WhatsappReportSchedule::create([
                'name' => 'Weekly Executive Briefing Inventory & Gudang',
                'module' => 'inventory',
                'report_type' => 'executive_summary',
                'frequency' => 'weekly',
                'day_of_week' => 1, // Senin
                'day_of_month' => null,
                'send_time' => '08:00',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['pptx', 'docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Kepala Gudang & PPIC (Trial)',
                    ],
                ],
                'whatsapp_session' => 'inventory',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => true,
                'is_active' => true,
            ]);

            WhatsappReportSchedule::create([
                'name' => 'Laporan Harian Stok Kritis & Mutasi Sore',
                'module' => 'inventory',
                'report_type' => 'low_stock_mutation',
                'frequency' => 'daily',
                'day_of_week' => null,
                'day_of_month' => null,
                'send_time' => '17:00',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Supervisor Warehouse',
                    ],
                ],
                'whatsapp_session' => 'inventory',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => true,
                'is_active' => true,
            ]);
        }

        if (WhatsappReportSchedule::where('module', 'sales')->count() === 0) {
            WhatsappReportSchedule::create([
                'name' => 'Weekly Executive Briefing Sales, Order & CRM',
                'module' => 'sales',
                'report_type' => 'executive_summary',
                'frequency' => 'weekly',
                'day_of_week' => 1, // Senin
                'day_of_month' => null,
                'send_time' => '08:30',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['pptx', 'docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Direktur & Sales Manager (Trial)',
                    ],
                ],
                'whatsapp_session' => 'sales',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => true,
                'is_active' => true,
            ]);
        }

        if (WhatsappReportSchedule::where('module', 'purchasing')->count() === 0) {
            WhatsappReportSchedule::create([
                'name' => 'Weekly Executive Briefing Purchasing & Procurement',
                'module' => 'purchasing',
                'report_type' => 'executive_summary',
                'frequency' => 'weekly',
                'day_of_week' => 1, // Senin
                'day_of_month' => null,
                'send_time' => '09:00',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['pptx', 'docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Purchasing Manager & Direksi (Trial)',
                    ],
                ],
                'whatsapp_session' => 'purchasing',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => true,
                'is_active' => true,
            ]);
        }

        if (WhatsappReportSchedule::where('module', 'manufacturing')->count() === 0) {
            WhatsappReportSchedule::create([
                'name' => 'Weekly Executive Briefing Manufacturing & Produksi',
                'module' => 'manufacturing',
                'report_type' => 'executive_summary',
                'frequency' => 'weekly',
                'day_of_week' => 1, // Senin
                'day_of_month' => null,
                'send_time' => '07:30',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['pptx', 'docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Plant Manager & Kepala Produksi (Trial)',
                    ],
                ],
                'whatsapp_session' => 'inventory',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => false,
                'is_active' => true,
            ]);
        }

        if (WhatsappReportSchedule::where('module', 'logistics')->count() === 0) {
            WhatsappReportSchedule::create([
                'name' => 'Weekly Executive Briefing Logistik & Pengiriman',
                'module' => 'logistics',
                'report_type' => 'executive_summary',
                'frequency' => 'weekly',
                'day_of_week' => 1, // Senin
                'day_of_month' => null,
                'send_time' => '08:00',
                'timezone' => 'Asia/Jakarta',
                'formats' => ['pptx', 'docx'],
                'recipients' => [
                    [
                        'type' => 'phone',
                        'target' => '6281119144287',
                        'name' => 'Logistics & Fleet Manager (Trial)',
                    ],
                ],
                'whatsapp_session' => 'inventory',
                'include_ai_summary' => true,
                'include_charts' => true,
                'include_low_stock' => false,
                'is_active' => true,
            ]);
        }
    }
}
