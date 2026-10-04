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
     * Ensure default NVR device and 6 default camera channels exist in database.
     */
    private function ensureDeviceAndChannels(bool $force = false): CctvDevice
    {
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

        $defaultChannels = [
            [
                'channel_number' => 1,
                'name' => 'Main Gate & Pos Satpam',
                'zone' => 'security',
                'location_description' => 'Pintu Gerbang Utama & Pos Keamanan Depan',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'channel_number' => 2,
                'name' => 'Workshop & Production Floor',
                'zone' => 'production',
                'location_description' => 'Lantai Produksi Mesin CNC & Workshop Pabrik',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'channel_number' => 3,
                'name' => 'Warehouse Material Storage',
                'zone' => 'warehouse',
                'location_description' => 'Gudang Bahan Baku & High Racks Logistik',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'channel_number' => 4,
                'name' => 'Loading Dock & Logistics',
                'zone' => 'logistics',
                'location_description' => 'Area Bongkar Muat Barang & Dermaga Logistik Truk',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'channel_number' => 5,
                'name' => 'Control Room & Electrical Panel',
                'zone' => 'facility',
                'location_description' => 'Ruang Kontrol & Panel Distribusi Listrik',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'channel_number' => 6,
                'name' => 'Office Entrance Lobby',
                'zone' => 'office',
                'location_description' => 'Lobby Utama & Resepsionis Gedung Kantor',
                'stream_url' => null,
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        $currentCount = CctvChannel::where('device_id', $device->id)->count();

        if ($force || $currentCount === 0) {
            foreach ($defaultChannels as $chData) {
                CctvChannel::updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'channel_number' => $chData['channel_number']
                    ],
                    $chData
                );
            }
        }

        return $device;
    }

    /**
     * Display the CCTV Video Wall Surveillance Kiosk
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.view'))) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat pengawasan CCTV.');
        }

        $device = $this->ensureDeviceAndChannels();
        $channels = CctvChannel::with('device')
            ->where('device_id', $device->id)
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

        $device = $this->ensureDeviceAndChannels();
        $channels = CctvChannel::where('device_id', $device->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return Inertia::render('Facilities/CctvSettings', [
            'device' => $device,
            'channels' => $channels,
        ]);
    }

    /**
     * Re-initialize / Reset Default 6 Camera Channels
     */
    public function resetDefaults(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('cctv.manage'))) {
            abort(403, 'Akses ditolak.');
        }

        $this->ensureDeviceAndChannels(true);

        return redirect()->back()->with('success', '6 Titik kamera default berhasil diinisialisasi ulang.');
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
