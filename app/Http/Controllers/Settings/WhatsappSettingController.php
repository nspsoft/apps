<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappSettingController extends Controller
{
    public function index()
    {
        $company = \App\Models\Company::first();
        $companyName = $company?->legal_name ?? $company?->name ?? AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA');

        $settings = [
            'whatsapp_provider' => AppSetting::get('whatsapp_provider', 'fonnte'),
            'fonnte_api_token' => AppSetting::get('fonnte_api_token', ''),
            'fonnte_device' => AppSetting::get('fonnte_device', ''),
            'wablas_api_token' => AppSetting::get('wablas_api_token', ''),
            'wablas_device' => AppSetting::get('wablas_device', ''),
            'wablas_server_url' => AppSetting::get('wablas_server_url', 'https://pati.wablas.com'),
            'whatsapp_bot_instruction' => (string) AppSetting::get('whatsapp_bot_instruction', "Anda adalah Customer Service {$companyName} AI yang ramah. Tugas Anda adalah melayani Customer dan Staff {$companyName} dengan sopan, ceria, dan membantu."),
            
            // Purchasing settings
            'purchasing_whatsapp_provider' => AppSetting::get('purchasing_whatsapp_provider', 'fonnte'),
            'purchasing_fonnte_api_token' => AppSetting::get('purchasing_fonnte_api_token', ''),
            'purchasing_fonnte_device' => AppSetting::get('purchasing_fonnte_device', ''),
            'purchasing_wablas_api_token' => AppSetting::get('purchasing_wablas_api_token', ''),
            'purchasing_wablas_device' => AppSetting::get('purchasing_wablas_device', ''),
            'purchasing_wablas_server_url' => AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com'),
            'purchasing_whatsapp_bot_instruction' => (string) AppSetting::get('purchasing_whatsapp_bot_instruction', "Anda adalah Purchasing Assistant {$companyName} yang ramah dan profesional. Tugas Anda adalah melayani supplier dan vendor kami dengan memberikan informasi PO, penerimaan barang, RFQ, dan invoice tagihan."),

            // HR & Payroll settings
            'hr_whatsapp_mode' => AppSetting::get('hr_whatsapp_mode', 'same_as_sales'),
            'hr_whatsapp_provider' => AppSetting::get('hr_whatsapp_provider', 'fonnte'),
            'hr_fonnte_api_token' => AppSetting::get('hr_fonnte_api_token', ''),
            'hr_fonnte_device' => AppSetting::get('hr_fonnte_device', ''),
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
            'whatsapp_provider' => 'required|in:fonnte,wablas',
            'fonnte_api_token' => 'nullable|string|max:500',
            'fonnte_device' => 'nullable|string|max:20',
            'wablas_api_token' => 'nullable|string|max:500',
            'wablas_device' => 'nullable|string|max:20',
            'wablas_server_url' => 'nullable|url|max:255',
            'whatsapp_bot_instruction' => 'nullable|string',

            // Purchasing rules
            'purchasing_whatsapp_provider' => 'required|in:fonnte,wablas',
            'purchasing_fonnte_api_token' => 'nullable|string|max:500',
            'purchasing_fonnte_device' => 'nullable|string|max:20',
            'purchasing_wablas_api_token' => 'nullable|string|max:500',
            'purchasing_wablas_device' => 'nullable|string|max:20',
            'purchasing_wablas_server_url' => 'nullable|url|max:255',
            'purchasing_whatsapp_bot_instruction' => 'nullable|string',

            // HR rules
            'hr_whatsapp_mode' => 'required|in:same_as_sales,dedicated',
            'hr_whatsapp_provider' => 'nullable|in:fonnte,wablas',
            'hr_fonnte_api_token' => 'nullable|string|max:500',
            'hr_fonnte_device' => 'nullable|string|max:20',
            'hr_wablas_api_token' => 'nullable|string|max:500',
            'hr_wablas_device' => 'nullable|string|max:20',
            'hr_wablas_server_url' => 'nullable|url|max:255',
            'hr_whatsapp_bot_instruction' => 'nullable|string',
        ]);

        // Save Sales provider selection
        AppSetting::set('whatsapp_provider', $request->whatsapp_provider, 'whatsapp', 'WhatsApp Provider');

        // Save Sales Fonnte settings
        AppSetting::set('fonnte_api_token', $request->fonnte_api_token ?: '', 'whatsapp', 'Fonnte API Token');
        AppSetting::set('fonnte_device', $request->fonnte_device ?: '', 'whatsapp', 'Fonnte Device ID');

        // Save Sales Wablas settings
        AppSetting::set('wablas_api_token', $request->wablas_api_token ?: '', 'whatsapp', 'Wablas API Token');
        AppSetting::set('wablas_device', $request->wablas_device ?: '', 'whatsapp', 'Wablas Device ID');
        AppSetting::set('wablas_server_url', $request->wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'Wablas Server URL');

        // Save Sales AI Bot Instructions
        AppSetting::set('whatsapp_bot_instruction', $request->whatsapp_bot_instruction ?: '', 'whatsapp', 'WhatsApp Bot Personality');

        // Save Purchasing provider selection
        AppSetting::set('purchasing_whatsapp_provider', $request->purchasing_whatsapp_provider, 'whatsapp', 'Purchasing WhatsApp Provider');

        // Save Purchasing Fonnte settings
        AppSetting::set('purchasing_fonnte_api_token', $request->purchasing_fonnte_api_token ?: '', 'whatsapp', 'Purchasing Fonnte API Token');
        AppSetting::set('purchasing_fonnte_device', $request->purchasing_fonnte_device ?: '', 'whatsapp', 'Purchasing Fonnte Device ID');

        // Save Purchasing Wablas settings
        AppSetting::set('purchasing_wablas_api_token', $request->purchasing_wablas_api_token ?: '', 'whatsapp', 'Purchasing Wablas API Token');
        AppSetting::set('purchasing_wablas_device', $request->purchasing_wablas_device ?: '', 'whatsapp', 'Purchasing Wablas Device ID');
        AppSetting::set('purchasing_wablas_server_url', $request->purchasing_wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'Purchasing Wablas Server URL');

        // Save Purchasing AI Bot Instructions
        AppSetting::set('purchasing_whatsapp_bot_instruction', $request->purchasing_whatsapp_bot_instruction ?: '', 'whatsapp', 'Purchasing WhatsApp Bot Personality');

        // Save HR settings
        AppSetting::set('hr_whatsapp_mode', $request->hr_whatsapp_mode, 'whatsapp', 'HR WhatsApp Mode');
        AppSetting::set('hr_whatsapp_provider', $request->hr_whatsapp_provider ?: 'fonnte', 'whatsapp', 'HR WhatsApp Provider');
        AppSetting::set('hr_fonnte_api_token', $request->hr_fonnte_api_token ?: '', 'whatsapp', 'HR Fonnte API Token');
        AppSetting::set('hr_fonnte_device', $request->hr_fonnte_device ?: '', 'whatsapp', 'HR Fonnte Device ID');
        AppSetting::set('hr_wablas_api_token', $request->hr_wablas_api_token ?: '', 'whatsapp', 'HR Wablas API Token');
        AppSetting::set('hr_wablas_device', $request->hr_wablas_device ?: '', 'whatsapp', 'HR Wablas Device ID');
        AppSetting::set('hr_wablas_server_url', $request->hr_wablas_server_url ?: 'https://pati.wablas.com', 'whatsapp', 'HR Wablas Server URL');
        AppSetting::set('hr_whatsapp_bot_instruction', $request->hr_whatsapp_bot_instruction ?: '', 'whatsapp', 'HR WhatsApp Bot Personality');

        return redirect()->back()->with('success', 'WhatsApp configuration saved successfully!');
    }

    /**
     * Test connection by sending a test message
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'module' => 'nullable|string|in:sales,purchasing,hr',
        ]);

        $module = $request->input('module', 'sales');
        $phone = $request->phone;

        if ($module === 'purchasing') {
            $provider = AppSetting::get('purchasing_whatsapp_provider', 'fonnte');
            $moduleLabel = 'Purchasing Bot';
        } elseif ($module === 'hr') {
            $hrMode = AppSetting::get('hr_whatsapp_mode', 'same_as_sales');
            $provider = $hrMode === 'dedicated' 
                ? AppSetting::get('hr_whatsapp_provider', 'fonnte')
                : AppSetting::get('whatsapp_provider', 'fonnte');
            $moduleLabel = 'HR & Payroll Notification' . ($hrMode === 'same_as_sales' ? ' (Shared)' : ' (Dedicated)');
        } else {
            $provider = AppSetting::get('whatsapp_provider', 'fonnte');
            $moduleLabel = 'Sales Bot';
        }

        $testMessage = "🔔 *JICOS ERP - Test Connection ({$moduleLabel})*\n\n";
        $testMessage .= "✅ Koneksi WhatsApp Bot berhasil!\n\n";
        $testMessage .= "📱 Provider: " . strtoupper($provider) . "\n";
        $testMessage .= "⏰ Waktu: " . now()->format('d M Y H:i:s') . "\n\n";
        $testMessage .= "_Pesan ini dikirim otomatis untuk testing._";

        try {
            if ($module === 'purchasing') {
                if ($provider === 'wablas') {
                    $token = AppSetting::get('purchasing_wablas_api_token', '');
                    $url = AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com');
                    $service = app(WablasService::class)->setCredentials($token, $url);
                } else {
                    $token = AppSetting::get('purchasing_fonnte_api_token', '');
                    $service = app(FonnteService::class)->setCredentials($token);
                }
            } elseif ($module === 'hr') {
                $hrMode = AppSetting::get('hr_whatsapp_mode', 'same_as_sales');
                if ($hrMode === 'dedicated') {
                    if ($provider === 'wablas') {
                        $token = AppSetting::get('hr_wablas_api_token', '');
                        $url = AppSetting::get('hr_wablas_server_url', 'https://pati.wablas.com');
                        $service = app(WablasService::class)->setCredentials($token, $url);
                    } else {
                        $token = AppSetting::get('hr_fonnte_api_token', '');
                        $service = app(FonnteService::class)->setCredentials($token);
                    }
                } else {
                    $salesProvider = AppSetting::get('whatsapp_provider', 'fonnte');
                    if ($salesProvider === 'wablas') {
                        $service = app(WablasService::class);
                    } else {
                        $service = app(FonnteService::class);
                    }
                }
            } else {
                if ($provider === 'wablas') {
                    $service = app(WablasService::class);
                } else {
                    $service = app(FonnteService::class);
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

    /**
     * Clear WhatsApp chat history in bulk
     */
    public function clearHistory(Request $request)
    {
        $request->validate([
            'module' => 'required|in:sales,purchasing,all',
            'password' => 'required|string',
        ]);

        // Verify password
        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Password salah! Gagal menghapus riwayat chat.');
        }

        try {
            $query = \App\Models\WhatsappMessage::query();
            
            if ($request->module !== 'all') {
                $query->where('module', $request->module);
            }
            
            $count = $query->count();
            $query->delete();

            // Clear unread counts cache
            \Illuminate\Support\Facades\Cache::forget('purchasing_whatsapp_unread_count');
            \Illuminate\Support\Facades\Cache::forget('sales_whatsapp_unread_count');

            return back()->with('success', "Berhasil menghapus {$count} pesan riwayat chat WhatsApp module " . strtoupper($request->module) . ".");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus riwayat chat: ' . $e->getMessage());
        }
    }
}

