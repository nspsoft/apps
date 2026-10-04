<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\CctvDevice;
use App\Models\CctvChannel;
use App\Models\Company;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class CctvController extends Controller
{
    /**
     * Display the CCTV Video Wall Surveillance Kiosk
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.view'))) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat pengawasan CCTV.');
        }

        $device = CctvDevice::where('is_active', true)->first();
        $channels = CctvChannel::with('device')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $company = Company::first();
        $companyName = $company?->legal_name ?? $company?->name ?? AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA');
        $companyLogo = $company?->logo ?? '/images/jicos.png';

        return Inertia::render('Facilities/CctvSurveillance', [
            'channels' => $channels,
            'device' => $device,
            'companyProfile' => [
                'name' => $companyName,
                'logo' => $companyLogo,
            ],
            'canManage' => $user->hasRole('Super Admin') || $user->can('cctv.manage'),
            'canSnapshot' => $user->hasRole('Super Admin') || $user->can('cctv.snapshot'),
        ]);
    }

    /**
     * Display CCTV Configuration Settings
     */
    public function settings(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.manage'))) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengatur perangkat CCTV.');
        }

        $device = CctvDevice::firstOrCreate(
            ['name' => 'NVR Hikvision Utama'],
            [
                'brand' => 'Hikvision',
                'ip_address' => '192.168.1.100',
                'rtsp_port' => 554,
                'http_port' => 80,
                'username' => 'admin',
                'password' => '',
                'is_active' => true,
            ]
        );

        $channels = CctvChannel::where('device_id', $device->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return Inertia::render('Facilities/CctvSettings', [
            'device' => $device,
            'channels' => $channels,
        ]);
    }

    /**
     * Update NVR Device Settings
     */
    public function updateDevice(Request $request, CctvDevice $device)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.manage'))) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'ip_address' => 'required|string|max:255',
            'rtsp_port' => 'required|integer|between:1,65535',
            'http_port' => 'required|integer|between:1,65535',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $device->update($validated);

        return redirect()->back()->with('success', 'Konfigurasi NVR Hikvision berhasil diperbarui.');
    }

    /**
     * Update Channel Settings
     */
    public function updateChannel(Request $request, CctvChannel $channel)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.manage'))) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'zone' => 'required|string|in:security,production,warehouse,logistics,facility,office',
            'location_description' => 'nullable|string|max:255',
            'stream_url' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
            'sort_order' => 'required|integer|between:0,99',
        ]);

        $channel->update($validated);

        return redirect()->back()->with('success', "Channel {$channel->channel_number} berhasil diperbarui.");
    }

    /**
     * Trigger Live Snapshot from Camera
     */
    public function snapshot(Request $request, CctvChannel $channel)
    {
        $device = $channel->device;
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        // Hikvision ISAPI snapshot URL format
        $ip = $device->ip_address;
        $port = $device->http_port;
        $chNum = $channel->channel_number;
        $url = "http://{$ip}:{$port}/ISAPI/Streaming/channels/{$chNum}01/picture";

        try {
            $resp = Http::withDigestAuth($device->username, $device->password ?: '')
                ->timeout(3)
                ->get($url);

            if ($resp->successful()) {
                return response($resp->body(), 200)
                    ->header('Content-Type', 'image/jpeg');
            }
        } catch (\Throwable $e) {
            // In demo/test without physical NVR connection, return null or fallback
        }

        return response()->json([
            'status' => 'offline_preview',
            'message' => 'NVR fisik belum terhubung di IP ' . $device->ip_address,
            'channel' => $channel->name,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
