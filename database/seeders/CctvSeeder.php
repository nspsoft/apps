<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CctvDevice;
use App\Models\CctvChannel;

class CctvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $device = CctvDevice::firstOrCreate(
            ['name' => 'NVR Hikvision Utama'],
            [
                'brand' => 'Hikvision',
                'ip_address' => '192.168.1.100',
                'rtsp_port' => 554,
                'http_port' => 80,
                'username' => 'admin',
                'password' => 'Admin12345',
                'is_active' => true,
            ]
        );

        $channels = [
            [
                'channel_number' => 1,
                'name' => 'Main Gate & Pos Satpam',
                'zone' => 'security',
                'location_description' => 'Pintu Gerbang Utama & Pos Keamanan Satpam',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 1,
            ],
            [
                'channel_number' => 2,
                'name' => 'Workshop & Production Floor',
                'zone' => 'production',
                'location_description' => 'Lantai Kerja Produksi & Area Mesin Pabrik',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 2,
            ],
            [
                'channel_number' => 3,
                'name' => 'Warehouse Material Storage',
                'zone' => 'warehouse',
                'location_description' => 'Gudang Penyimpanan Material & Rak Logistik',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 3,
            ],
            [
                'channel_number' => 4,
                'name' => 'Loading Dock & Logistics',
                'zone' => 'logistics',
                'location_description' => 'Area Bongkar Muat Armada & Timbangan Truk',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 4,
            ],
            [
                'channel_number' => 5,
                'name' => 'Control Room & Electrical Panel',
                'zone' => 'facility',
                'location_description' => 'Ruang Kontrol Mesin & Panel Distribusi Listrik',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 5,
            ],
            [
                'channel_number' => 6,
                'name' => 'Office Entrance Lobby',
                'zone' => 'office',
                'location_description' => 'Lobi Utama Kantor & Akses Karyawan/Tamu',
                'resolution' => '1080p',
                'fps' => 25,
                'sort_order' => 6,
            ],
        ];

        foreach ($channels as $chanData) {
            CctvChannel::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'channel_number' => $chanData['channel_number']
                ],
                $chanData
            );
        }
    }
}
