<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\JicosWhatsappEngineService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappSettingController extends Controller
{
    public function index()
    {
        $company = \App\Models\Company::first();
        $companyName = $company?->legal_name ?? $company?->name ?? AppSetting::get('company_full_name', 'PT. SPINDO / JICOS');

        $normalize = fn($val) => ($val === 'wablas') ? 'wablas' : 'jicos_engine';

        $settings = [
            'whatsapp_provider' => $normalize(AppSetting::get('whatsapp_provider', 'jicos_engine')),
            'jicos_engine_server_url' => AppSetting::get('jicos_engine_server_url') ?: AppSetting::get('usics_engine_server_url', 'http://127.0.0.1:5055'),
            'jicos_engine_api_key' => AppSetting::get('jicos_engine_api_key') ?: AppSetting::get('usics_engine_api_key', 'jicos-wa-secret-2026'),
            'usics_engine_server_url' => AppSetting::get('jicos_engine_server_url') ?: AppSetting::get('usics_engine_server_url', 'http://127.0.0.1:5055'),
            'usics_engine_api_key' => AppSetting::get('jicos_engine_api_key') ?: AppSetting::get('usics_engine_api_key', 'jicos-wa-secret-2026'),
            'wablas_api_token' => AppSetting::get('wablas_api_token', ''),
            'wablas_device' => AppSetting::get('wablas_device', ''),
            'wablas_server_url' => AppSetting::get('wablas_server_url', 'https://pati.wablas.com'),
            'whatsapp_bot_instruction' => (string) AppSetting::get('whatsapp_bot_instruction', "Anda adalah Customer Service Assistant {$companyName} yang ramah. Tugas Anda adalah melayani Customer dan Staff {$companyName} dengan sopan, ceria, dan membantu."),
            'default_phone' => '6285888883258',
            'whatsapp_mfa_module' => (string) AppSetting::get('whatsapp_mfa_module', 'hr'),
            'whatsapp_sync_phone_outgoing' => (bool) AppSetting::get('whatsapp_sync_phone_outgoing', false),
            'purchasing_whatsapp_sync_phone_outgoing' => (bool) AppSetting::get('purchasing_whatsapp_sync_phone_outgoing', false),
            'inventory_whatsapp_sync_phone_outgoing' => (bool) AppSetting::get('inventory_whatsapp_sync_phone_outgoing', false),
            'hr_whatsapp_sync_phone_outgoing' => (bool) AppSetting::get('hr_whatsapp_sync_phone_outgoing', false),
            
            // Purchasing settings
            'purchasing_whatsapp_provider' => $normalize(AppSetting::get('purchasing_whatsapp_provider', 'jicos_engine')),
            'purchasing_jicos_engine_server_url' => AppSetting::get('purchasing_jicos_engine_server_url') ?: AppSetting::get('purchasing_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'purchasing_jicos_engine_api_key' => AppSetting::get('purchasing_jicos_engine_api_key') ?: AppSetting::get('purchasing_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'purchasing_usics_engine_server_url' => AppSetting::get('purchasing_jicos_engine_server_url') ?: AppSetting::get('purchasing_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'purchasing_usics_engine_api_key' => AppSetting::get('purchasing_jicos_engine_api_key') ?: AppSetting::get('purchasing_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'purchasing_wablas_api_token' => AppSetting::get('purchasing_wablas_api_token', ''),
            'purchasing_wablas_device' => AppSetting::get('purchasing_wablas_device', ''),
            'purchasing_wablas_server_url' => AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com'),
            'purchasing_whatsapp_bot_instruction' => (string) AppSetting::get('purchasing_whatsapp_bot_instruction', "Anda adalah Purchasing Assistant {$companyName} yang ramah dan profesional. Tugas Anda adalah melayani supplier dan vendor kami dengan memberikan informasi PO, penerimaan barang, RFQ, dan invoice tagihan."),

            // Inventory settings
            'inventory_whatsapp_provider' => $normalize(AppSetting::get('inventory_whatsapp_provider', 'jicos_engine')),
            'inventory_jicos_engine_server_url' => AppSetting::get('inventory_jicos_engine_server_url') ?: AppSetting::get('inventory_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'inventory_jicos_engine_api_key' => AppSetting::get('inventory_jicos_engine_api_key') ?: AppSetting::get('inventory_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'inventory_usics_engine_server_url' => AppSetting::get('inventory_jicos_engine_server_url') ?: AppSetting::get('inventory_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'inventory_usics_engine_api_key' => AppSetting::get('inventory_jicos_engine_api_key') ?: AppSetting::get('inventory_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'inventory_wablas_api_token' => AppSetting::get('inventory_wablas_api_token', ''),
            'inventory_wablas_device' => AppSetting::get('inventory_wablas_device', ''),
            'inventory_wablas_server_url' => AppSetting::get('inventory_wablas_server_url', 'https://pati.wablas.com'),
            'inventory_whatsapp_bot_instruction' => (string) AppSetting::get('inventory_whatsapp_bot_instruction', "Anda adalah AI Warehouse & Inventory Assistant {$companyName} yang sigap, teliti, dan profesional. Tugas Anda adalah membantu tim Gudang, PPIC, dan Operasional dalam mengecek stok produk, lokasi rak penyimpanan (SLoc), daftar stok kritis (low stock), status transfer gudang, serta status stock opname."),

            // HR settings
            'hr_whatsapp_mode' => AppSetting::get('hr_whatsapp_mode', 'same_as_sales'),
            'hr_whatsapp_provider' => $normalize(AppSetting::get('hr_whatsapp_provider', 'jicos_engine')),
            'hr_jicos_engine_server_url' => AppSetting::get('hr_jicos_engine_server_url') ?: AppSetting::get('hr_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'hr_jicos_engine_api_key' => AppSetting::get('hr_jicos_engine_api_key') ?: AppSetting::get('hr_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'hr_usics_engine_server_url' => AppSetting::get('hr_jicos_engine_server_url') ?: AppSetting::get('hr_usics_engine_server_url', 'http://127.0.0.1:5055'),
            'hr_usics_engine_api_key' => AppSetting::get('hr_jicos_engine_api_key') ?: AppSetting::get('hr_usics_engine_api_key', 'jicos-wa-secret-2026'),
            'hr_wablas_api_token' => AppSetting::get('hr_wablas_api_token', ''),
            'hr_wablas_device' => AppSetting::get('hr_wablas_device', ''),
            'hr_wablas_server_url' => AppSetting::get('hr_wablas_server_url', 'https://pati.wablas.com'),
            'hr_whatsapp_bot_instruction' => (string) AppSetting::get('hr_whatsapp_bot_instruction', "Anda adalah Asisten HRD {$companyName} yang ramah dan profesional. Tugas Anda adalah membantu karyawan terkait informasi slip gaji, kehadiran, dan pengumuman internal."),
        ];

        return Inertia::render('Settings/WhatsappSettings', [
            'settings' => $settings,
            'appUrl' => config('app.url'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            // Sales rules
            'whatsapp_provider' => 'required|in:jicos_engine,usics_engine,wablas',
            'jicos_engine_server_url' => 'nullable|url|max:255',
            'jicos_engine_api_key' => 'nullable|string|max:500',
            'usics_engine_server_url' => 'nullable|url|max:255',
            'usics_engine_api_key' => 'nullable|string|max:500',
            'wablas_api_token' => 'nullable|string|max:500',
            'wablas_device' => 'nullable|string|max:20',
            'wablas_server_url' => 'nullable|url|max:255',
            'whatsapp_bot_instruction' => 'nullable|string',
            'whatsapp_sync_phone_outgoing' => 'nullable|boolean',
            'whatsapp_mfa_module' => 'nullable|string|in:hr,sales,purchasing,inventory',
            'purchasing_whatsapp_sync_phone_outgoing' => 'nullable|boolean',
            'inventory_whatsapp_sync_phone_outgoing' => 'nullable|boolean',
            'hr_whatsapp_sync_phone_outgoing' => 'nullable|boolean',

            // Purchasing rules
            'purchasing_whatsapp_provider' => 'required|in:jicos_engine,usics_engine,wablas',
            'purchasing_jicos_engine_server_url' => 'nullable|url|max:255',
            'purchasing_jicos_engine_api_key' => 'nullable|string|max:500',
            'purchasing_usics_engine_server_url' => 'nullable|url|max:255',
            'purchasing_usics_engine_api_key' => 'nullable|string|max:500',
            'purchasing_wablas_api_token' => 'nullable|string|max:500',
            'purchasing_wablas_device' => 'nullable|string|max:20',
            'purchasing_wablas_server_url' => 'nullable|url|max:255',
            'purchasing_whatsapp_bot_instruction' => 'nullable|string',

            // Inventory rules
            'inventory_whatsapp_provider' => 'nullable|in:jicos_engine,usics_engine,wablas',
            'inventory_jicos_engine_server_url' => 'nullable|url|max:255',
            'inventory_jicos_engine_api_key' => 'nullable|string|max:500',
            'inventory_usics_engine_server_url' => 'nullable|url|max:255',
            'inventory_usics_engine_api_key' => 'nullable|string|max:500',
            'inventory_wablas_api_token' => 'nullable|string|max:500',
            'inventory_wablas_device' => 'nullable|string|max:20',
            'inventory_wablas_server_url' => 'nullable|url|max:255',
            'inventory_whatsapp_bot_instruction' => 'nullable|string',

            // HR rules
            'hr_whatsapp_mode' => 'nullable|in:same_as_sales,dedicated',
            'hr_whatsapp_provider' => 'nullable|in:jicos_engine,usics_engine,wablas',
            'hr_jicos_engine_server_url' => 'nullable|url|max:255',
            'hr_jicos_engine_api_key' => 'nullable|string|max:500',
            'hr_usics_engine_server_url' => 'nullable|url|max:255',
            'hr_usics_engine_api_key' => 'nullable|string|max:500',
            'hr_wablas_api_token' => 'nullable|string|max:500',
            'hr_wablas_device' => 'nullable|string|max:20',
            'hr_wablas_server_url' => 'nullable|url|max:255',
            'hr_whatsapp_bot_instruction' => 'nullable|string',
        ]);

        // Normalize provider selections to jicos_engine
        $saveSalesProvider = $request->whatsapp_provider === 'wablas' ? 'wablas' : 'jicos_engine';
        $savePurchasingProvider = $request->purchasing_whatsapp_provider === 'wablas' ? 'wablas' : 'jicos_engine';
        $saveInventoryProvider = $request->inventory_whatsapp_provider === 'wablas' ? 'wablas' : 'jicos_engine';
        $saveHrProvider = $request->hr_whatsapp_provider === 'wablas' ? 'wablas' : 'jicos_engine';

        // Save Sales provider selection
        AppSetting::set('whatsapp_provider', $saveSalesProvider, 'whatsapp', 'WhatsApp Provider');

        // Save Sales JICOS WhatsApp Engine settings (dual-key for safety)
        $salesUrl = $request->jicos_engine_server_url ?: $request->usics_engine_server_url ?: 'http://127.0.0.1:5055';
        $salesKey = $request->jicos_engine_api_key ?: $request->usics_engine_api_key ?: 'jicos-wa-secret-2026';
        AppSetting::set('jicos_engine_server_url', $salesUrl, 'whatsapp', 'JICOS WA Engine Server URL');
        AppSetting::set('jicos_engine_api_key', $salesKey, 'whatsapp', 'JICOS WA Engine API Key');
        AppSetting::set('usics_engine_server_url', $salesUrl, 'whatsapp', 'JICOS WA Engine Server URL');
        AppSetting::set('usics_engine_api_key', $salesKey, 'whatsapp', 'JICOS WA Engine API Key');

        // Save Sales Wablas settings
        AppSetting::set('wablas_api_token', $request->wablas_api_token ?: '', 'whatsapp', 'Wablas API Token');
        AppSetting::set('wablas_device', $request->wablas_device ?: '', 'whatsapp', 'Wablas Device ID');
        AppSetting::set('wablas_server_url', $request->wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'Wablas Server URL');

        // Save Phone Outgoing Sync Toggles
        AppSetting::set('whatsapp_sync_phone_outgoing', $request->boolean('whatsapp_sync_phone_outgoing') ? '1' : '0', 'whatsapp', 'Sync Phone Outgoing (Sales)');
        AppSetting::set('purchasing_whatsapp_sync_phone_outgoing', $request->boolean('purchasing_whatsapp_sync_phone_outgoing') ? '1' : '0', 'whatsapp', 'Sync Phone Outgoing (Purchasing)');
        AppSetting::set('inventory_whatsapp_sync_phone_outgoing', $request->boolean('inventory_whatsapp_sync_phone_outgoing') ? '1' : '0', 'whatsapp', 'Sync Phone Outgoing (Inventory)');
        AppSetting::set('hr_whatsapp_sync_phone_outgoing', $request->boolean('hr_whatsapp_sync_phone_outgoing') ? '1' : '0', 'whatsapp', 'Sync Phone Outgoing (HR)');

        // Save Sales AI Bot Instructions
        AppSetting::set('whatsapp_bot_instruction', $request->whatsapp_bot_instruction ?: '', 'whatsapp', 'WhatsApp Bot Personality');

        // Save Purchasing provider selection
        AppSetting::set('purchasing_whatsapp_provider', $savePurchasingProvider, 'whatsapp', 'Purchasing WhatsApp Provider');

        // Save Purchasing JICOS WhatsApp Engine settings
        $purchasingUrl = $request->purchasing_jicos_engine_server_url ?: $request->purchasing_usics_engine_server_url ?: 'http://127.0.0.1:5055';
        $purchasingKey = $request->purchasing_jicos_engine_api_key ?: $request->purchasing_usics_engine_api_key ?: 'jicos-wa-secret-2026';
        AppSetting::set('purchasing_jicos_engine_server_url', $purchasingUrl, 'whatsapp', 'Purchasing WA Engine Server URL');
        AppSetting::set('purchasing_jicos_engine_api_key', $purchasingKey, 'whatsapp', 'Purchasing WA Engine API Key');
        AppSetting::set('purchasing_usics_engine_server_url', $purchasingUrl, 'whatsapp', 'Purchasing WA Engine Server URL');
        AppSetting::set('purchasing_usics_engine_api_key', $purchasingKey, 'whatsapp', 'Purchasing WA Engine API Key');

        // Save Purchasing Wablas settings
        AppSetting::set('purchasing_wablas_api_token', $request->purchasing_wablas_api_token ?: '', 'whatsapp', 'Purchasing Wablas API Token');
        AppSetting::set('purchasing_wablas_device', $request->purchasing_wablas_device ?: '', 'whatsapp', 'Purchasing Wablas Device ID');
        AppSetting::set('purchasing_wablas_server_url', $request->purchasing_wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'Purchasing Wablas Server URL');

        // Save Purchasing AI Bot Instructions
        AppSetting::set('purchasing_whatsapp_bot_instruction', $request->purchasing_whatsapp_bot_instruction ?: '', 'whatsapp', 'Purchasing WhatsApp Bot Personality');

        // Save Inventory provider selection
        AppSetting::set('inventory_whatsapp_provider', $saveInventoryProvider, 'whatsapp', 'Inventory WhatsApp Provider');

        // Save Inventory JICOS WhatsApp Engine settings
        $invUrl = $request->inventory_jicos_engine_server_url ?: $request->inventory_usics_engine_server_url ?: 'http://127.0.0.1:5055';
        $invKey = $request->inventory_jicos_engine_api_key ?: $request->inventory_usics_engine_api_key ?: 'jicos-wa-secret-2026';
        AppSetting::set('inventory_jicos_engine_server_url', $invUrl, 'whatsapp', 'Inventory WA Engine Server URL');
        AppSetting::set('inventory_jicos_engine_api_key', $invKey, 'whatsapp', 'Inventory WA Engine API Key');
        AppSetting::set('inventory_usics_engine_server_url', $invUrl, 'whatsapp', 'Inventory WA Engine Server URL');
        AppSetting::set('inventory_usics_engine_api_key', $invKey, 'whatsapp', 'Inventory WA Engine API Key');

        // Save Inventory Wablas settings
        AppSetting::set('inventory_wablas_api_token', $request->inventory_wablas_api_token ?: '', 'whatsapp', 'Inventory Wablas API Token');
        AppSetting::set('inventory_wablas_device', $request->inventory_wablas_device ?: '', 'whatsapp', 'Inventory Wablas Device ID');
        AppSetting::set('inventory_wablas_server_url', $request->inventory_wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'Inventory Wablas Server URL');

        // Save Inventory AI Bot Instructions
        AppSetting::set('inventory_whatsapp_bot_instruction', $request->inventory_whatsapp_bot_instruction ?: '', 'whatsapp', 'Inventory WhatsApp Bot Personality');

        // Save HR settings
        if ($request->has('hr_whatsapp_mode')) {
            $hrUrl = $request->hr_jicos_engine_server_url ?: $request->hr_usics_engine_server_url ?: 'http://127.0.0.1:5055';
            $hrKey = $request->hr_jicos_engine_api_key ?: $request->hr_usics_engine_api_key ?: 'jicos-wa-secret-2026';
            AppSetting::set('hr_whatsapp_mode', $request->hr_whatsapp_mode, 'whatsapp', 'HR WhatsApp Mode');
            AppSetting::set('hr_whatsapp_provider', $saveHrProvider, 'whatsapp', 'HR WhatsApp Provider');
            AppSetting::set('hr_jicos_engine_server_url', $hrUrl, 'whatsapp', 'HR WA Engine Server URL');
            AppSetting::set('hr_jicos_engine_api_key', $hrKey, 'whatsapp', 'HR WA Engine API Key');
            AppSetting::set('hr_usics_engine_server_url', $hrUrl, 'whatsapp', 'HR WA Engine Server URL');
            AppSetting::set('hr_usics_engine_api_key', $hrKey, 'whatsapp', 'HR WA Engine API Key');
            AppSetting::set('hr_wablas_api_token', $request->hr_wablas_api_token ?: '', 'whatsapp', 'HR Wablas API Token');
            AppSetting::set('hr_wablas_device', $request->hr_wablas_device ?: '', 'whatsapp', 'HR Wablas Device ID');
            AppSetting::set('hr_wablas_server_url', $request->hr_wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'HR Wablas Server URL');
            AppSetting::set('hr_whatsapp_bot_instruction', $request->hr_whatsapp_bot_instruction ?: '', 'whatsapp', 'HR WhatsApp Bot Personality');
        }

        if ($request->has('whatsapp_mfa_module')) {
            AppSetting::set('whatsapp_mfa_module', $request->whatsapp_mfa_module, 'whatsapp', 'Modul WhatsApp Pengirim OTP MFA');
        }

        return redirect()->back()->with('success', 'Konfigurasi WhatsApp berhasil diperbarui!');
    }

    /**
     * Check JICOS WhatsApp Engine connection status
     */
    public function checkEngineStatus(Request $request)
    {
        $serverUrl = $request->query('server_url') ?: AppSetting::get('jicos_engine_server_url') ?: AppSetting::get('usics_engine_server_url', 'http://127.0.0.1:5055');
        $apiKey = $request->query('api_key') ?: AppSetting::get('jicos_engine_api_key') ?: AppSetting::get('usics_engine_api_key', 'jicos-wa-secret-2026');
        $session = $request->query('session', 'sales');

        $engine = app(JicosWhatsappEngineService::class)->setCredentials($apiKey, $serverUrl, $session);
        $status = $engine->getStatus($session);

        return response()->json($status);
    }

    /**
     * Test connection by sending a test message
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'module' => 'nullable|string|in:sales,purchasing,inventory,hr',
        ]);

        $module = $request->input('module', 'sales');
        $phone = $request->phone ?: '6285888883258';

        $provider = match ($module) {
            'purchasing' => AppSetting::get('purchasing_whatsapp_provider', 'jicos_engine'),
            'inventory' => AppSetting::get('inventory_whatsapp_provider', 'jicos_engine'),
            'hr' => AppSetting::get('hr_whatsapp_provider', 'jicos_engine'),
            default => AppSetting::get('whatsapp_provider', 'jicos_engine'),
        };

        $providerName = match ($provider) {
            'wablas' => 'Wablas (Cloud)',
            default => 'JICOS WhatsApp Engine (Node.js)',
        };

        $botLabel = match ($module) {
            'purchasing' => 'Purchasing Bot',
            'inventory' => 'Inventory & Warehouse Bot',
            'hr' => 'HR & Payroll Bot',
            default => 'Sales CS Bot',
        };

        $company = \App\Models\Company::first();
        $companyName = $company?->name ?? 'JICOS ERP';

        $testMessage = "🔔 *{$companyName} - Test Connection ({$botLabel})*\n\n";
        $testMessage .= "✅ Koneksi WhatsApp Bot berhasil!\n\n";
        $testMessage .= "📱 Gateway: " . $providerName . " [Sesi: " . strtoupper($module) . "]\n";
        $testMessage .= "⏰ Waktu: " . now()->format('d M Y H:i:s') . "\n\n";
        $testMessage .= "_Pesan ini dikirim otomatis melalui {$companyName}._";

        try {
            if ($module === 'purchasing') {
                if ($provider === 'wablas') {
                    $token = AppSetting::get('purchasing_wablas_api_token', '');
                    $url = AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com');
                    $service = app(WablasService::class)->setCredentials($token, $url);
                } else {
                    $token = AppSetting::get('purchasing_jicos_engine_api_key') ?: AppSetting::get('purchasing_usics_engine_api_key', 'jicos-wa-secret-2026');
                    $url = AppSetting::get('purchasing_jicos_engine_server_url') ?: AppSetting::get('purchasing_usics_engine_server_url', 'http://127.0.0.1:5055');
                    $service = app(JicosWhatsappEngineService::class)->setCredentials($token, $url, 'purchasing');
                }
            } elseif ($module === 'inventory') {
                if ($provider === 'wablas') {
                    $token = AppSetting::get('inventory_wablas_api_token', '');
                    $url = AppSetting::get('inventory_wablas_server_url', 'https://pati.wablas.com');
                    $service = app(WablasService::class)->setCredentials($token, $url);
                } else {
                    $token = AppSetting::get('inventory_jicos_engine_api_key') ?: AppSetting::get('inventory_usics_engine_api_key', 'jicos-wa-secret-2026');
                    $url = AppSetting::get('inventory_jicos_engine_server_url') ?: AppSetting::get('inventory_usics_engine_server_url', 'http://127.0.0.1:5055');
                    $service = app(JicosWhatsappEngineService::class)->setCredentials($token, $url, 'inventory');
                }
            } elseif ($module === 'hr') {
                if ($provider === 'wablas') {
                    $token = AppSetting::get('hr_wablas_api_token', '');
                    $url = AppSetting::get('hr_wablas_server_url', 'https://pati.wablas.com');
                    $service = app(WablasService::class)->setCredentials($token, $url);
                } else {
                    $token = AppSetting::get('hr_jicos_engine_api_key') ?: AppSetting::get('hr_usics_engine_api_key', 'jicos-wa-secret-2026');
                    $url = AppSetting::get('hr_jicos_engine_server_url') ?: AppSetting::get('hr_usics_engine_server_url', 'http://127.0.0.1:5055');
                    $service = app(JicosWhatsappEngineService::class)->setCredentials($token, $url, 'hr');
                }
            } else {
                if ($provider === 'wablas') {
                    $service = app(WablasService::class);
                } else {
                    $token = AppSetting::get('jicos_engine_api_key') ?: AppSetting::get('usics_engine_api_key', 'jicos-wa-secret-2026');
                    $url = AppSetting::get('jicos_engine_server_url') ?: AppSetting::get('usics_engine_server_url', 'http://127.0.0.1:5055');
                    $service = app(JicosWhatsappEngineService::class)->setCredentials($token, $url, 'sales');
                }
            }

            $result = $service->sendMessage($phone, $testMessage);

            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan test berhasil dikirim ke ' . $phone,
                    'provider' => $provider,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim pesan: ' . ($result['error'] ?? 'Unknown error'),
                    'provider' => $provider,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'provider' => $provider,
            ], 500);
        }
    }
}
