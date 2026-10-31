<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrolLog;
use App\Models\SecurityPatrolSchedule;
use App\Models\SecurityPatrolSession;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SecurityPatrolSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        // Base coordinates dynamically retrieved from Smart Attendance preferences
        $office = AppSetting::getOfficeCoordinates();
        $baseLat = $office['latitude'];
        $baseLng = $office['longitude'];

        $checkpointsData = [
            [
                'code' => 'CP-01',
                'name' => 'Pos Jaga Gerbang Utama (Gate 1)',
                'description' => 'Akses keluar masuk tamu, truk kontainer, dan pos jaga utama.',
                'area_zone' => 'Ring Luar',
                'lat_offset' => 0.00000,
                'lng_offset' => 0.00000,
                'sop_items' => ['Palang pintu gerbang berfungsi', 'Buku log tamu terisi', 'CCTV pos menyala'],
            ],
            [
                'code' => 'CP-02',
                'name' => 'Pos Timbangan Truk & Loading Dock',
                'description' => 'Area timbangan kendaraan material pipa dan dermaga bongkar muat.',
                'area_zone' => 'Logistik',
                'lat_offset' => -0.00030,
                'lng_offset' => 0.00020,
                'sop_items' => ['Lampu penerangan dock aktif', 'Ganjal ban terpasang', 'Pintu dock terkunci jika kosong'],
            ],
            [
                'code' => 'CP-03',
                'name' => 'Gudang Bahan Kimia & B3',
                'description' => 'Penyimpanan bahan kimia berbahaya, oli hidrolik, dan pelumas pabrik.',
                'area_zone' => 'Area Kritis & Berbahaya',
                'lat_offset' => -0.00060,
                'lng_offset' => 0.00050,
                'sop_items' => ['Pintu gembok B3 terkunci rapat', 'Eye wash shower berfungsi', 'Tidak ada bau menyengat / tumpahan', 'APAR kondisi baik'],
            ],
            [
                'code' => 'CP-04',
                'name' => 'Gardu Induk Trafo Listrik & Genset',
                'description' => 'Panel distribusi daya utama tegangan menengah dan ruang generator darurat.',
                'area_zone' => 'Fasilitas Vital',
                'lat_offset' => -0.00070,
                'lng_offset' => -0.00020,
                'sop_items' => ['Pintu gardu terkunci', 'Panel instrumen suhu normal', 'Level solar genset cukup', 'APAR CO2 siap pakai'],
            ],
            [
                'code' => 'CP-05',
                'name' => 'Gudang Finish Goods Pipa Baja',
                'description' => 'Area penyimpanan pipa siap kirim dan rak stacking pipa galvanis.',
                'area_zone' => 'Gudang Utama',
                'lat_offset' => -0.00020,
                'lng_offset' => 0.00070,
                'sop_items' => ['Jalur forklift steril dari halangan', 'Pintu geser gudang terkunci malam hari', 'Stapel pipa aman'],
            ],
            [
                'code' => 'CP-06',
                'name' => 'Area Produksi Slitting & Forming Line',
                'description' => 'Area mesin forming pipa baja, mesin potong, dan crane gantry.',
                'area_zone' => 'Produksi',
                'lat_offset' => 0.00010,
                'lng_offset' => 0.00060,
                'sop_items' => ['Mesin dalam kondisi aman (E-stop saat shift jeda)', 'Tidak ada kabel terkelupas', 'Hydrant bebas blokade'],
            ],
            [
                'code' => 'CP-07',
                'name' => 'Pintu Darurat & Tangga Evakuasi Barat',
                'description' => 'Jalur evakuasi darurat sisi barat pabrik menuju muster point.',
                'area_zone' => 'K3 / Jalur Evakuasi',
                'lat_offset' => 0.00030,
                'lng_offset' => 0.00010,
                'sop_items' => ['Panic exit bar berfungsi baik', 'Jalur evakuasi bebas barang', 'Lampu EXIT menyala'],
            ],
            [
                'code' => 'CP-08',
                'name' => 'Instalasi Pengolahan Air Limbah (IPAL)',
                'description' => 'Kolam pengolahan limbah cair industri dan pompa sedimentasi.',
                'area_zone' => 'Fasilitas Vital',
                'lat_offset' => -0.00090,
                'lng_offset' => 0.00030,
                'sop_items' => ['Level kolam normal tidak meluap', 'Pagar pengaman kolam tertutup', 'Aerator pompa bekerja'],
            ],
            [
                'code' => 'CP-09',
                'name' => 'Area Parkir Karyawan & Pos 2',
                'description' => 'Area parkir kendaraan roda dua dan pos penjagaan barat.',
                'area_zone' => 'Ring Luar',
                'lat_offset' => 0.00040,
                'lng_offset' => -0.00020,
                'sop_items' => ['Penerangan parkiran terang', 'Kunci ganda kendaraan terpasang', 'Pintu gerbang pos 2 terkunci'],
            ],
            [
                'code' => 'CP-10',
                'name' => 'Pagar Batas Perimeter Selatan',
                'description' => 'Tembok batas pabrik dengan saluran pembuangan kawasan industri.',
                'area_zone' => 'Perimeter Pabrik',
                'lat_offset' => -0.00110,
                'lng_offset' => 0.00000,
                'sop_items' => ['Kawat berduri tidak rusak / jebol', 'Tidak ada celah orang masuk', 'Lampu perimeter menyala'],
            ],
        ];

        $createdCheckpoints = [];
        foreach ($checkpointsData as $cp) {
            $checkpoint = SecurityCheckpoint::updateOrCreate(
                ['code' => $cp['code']],
                [
                    'name' => $cp['name'],
                    'description' => $cp['description'],
                    'area_zone' => $cp['area_zone'],
                    'latitude' => $baseLat + $cp['lat_offset'],
                    'longitude' => $baseLng + $cp['lng_offset'],
                    'geofence_radius' => 50,
                    'qr_token' => 'CP-TOKEN-' . Str::upper(Str::random(12)) . '-' . $cp['code'],
                    'sop_items' => $cp['sop_items'],
                    'is_active' => true,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
            $createdCheckpoints[] = $checkpoint;
        }

        // Shifts and Schedules
        $shifts = Shift::all();
        $shift1 = $shifts->firstWhere('name', 'Shift 1') ?? $shifts->first();
        $shift2 = $shifts->firstWhere('name', 'Shift 2') ?? $shifts->skip(1)->first() ?? $shift1;

        $schedulesData = [
            [
                'name' => 'Patroli Shift Pagi (Putaran 1)',
                'shift_id' => $shift1?->id,
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
            ],
            [
                'name' => 'Patroli Shift Siang (Putaran 2)',
                'shift_id' => $shift2?->id,
                'start_time' => '14:00:00',
                'end_time' => '16:00:00',
            ],
            [
                'name' => 'Patroli Shift Malam (Perimeter & Vital)',
                'shift_id' => $shift2?->id,
                'start_time' => '23:00:00',
                'end_time' => '01:00:00',
            ],
        ];

        $createdSchedules = [];
        foreach ($schedulesData as $s) {
            $schedule = SecurityPatrolSchedule::updateOrCreate(
                ['name' => $s['name']],
                [
                    'shift_id' => $s['shift_id'],
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                    'checkpoints_count' => count($createdCheckpoints),
                    'is_active' => true,
                ]
            );

            // Sync checkpoints pivot
            $syncData = [];
            foreach ($createdCheckpoints as $idx => $cp) {
                $syncData[$cp->id] = ['sort_order' => $idx + 1];
            }
            $schedule->checkpoints()->sync($syncData);
            $createdSchedules[] = $schedule;
        }

        // Seed 7 days of historical sessions for rich Resume Patrol & Analytics
        $today = Carbon::today();
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);

            foreach ($createdSchedules as $schIdx => $sch) {
                $sessionCode = 'PTR-' . $date->format('Ymd') . '-00' . ($schIdx + 1);
                $isToday = $i === 0;

                $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $sch->start_time);
                $endTime = $startTime->copy()->addMinutes(rand(45, 75));
                $duration = $endTime->diffInSeconds($startTime);

                $totalPoints = count($createdCheckpoints);
                // On some days, 1 point might be missed to simulate realistic industrial performance
                $missed = ($i === 2 || $i === 4) && $schIdx === 2 ? 1 : 0;
                $completed = $totalPoints - $missed;
                $hasIncident = ($i === 3 && $schIdx === 1) || ($i === 1 && $schIdx === 2);
                $incidentCount = $hasIncident ? 1 : 0;

                $status = ($isToday && $schIdx === 2) ? 'in_progress' : ($missed > 0 ? 'incomplete' : 'completed');

                $session = SecurityPatrolSession::updateOrCreate(
                    ['session_code' => $sessionCode],
                    [
                        'schedule_id' => $sch->id,
                        'user_id' => $adminId,
                        'shift_name' => $sch->name,
                        'patrol_date' => $date->format('Y-m-d'),
                        'start_time' => $startTime,
                        'end_time' => ($status === 'in_progress') ? null : $endTime,
                        'duration_seconds' => ($status === 'in_progress') ? null : $duration,
                        'total_checkpoints' => $totalPoints,
                        'completed_checkpoints' => ($status === 'in_progress') ? 6 : $completed,
                        'missed_checkpoints' => $missed,
                        'incidents_count' => $incidentCount,
                        'status' => $status,
                        'notes' => $hasIncident ? 'Ditemukan lampu penerangan mati dekat gardu trafo listrik.' : 'Situasi lingkungan aman dan terkendali.',
                    ]
                );

                // Add checkpoint logs
                $pointsToLog = ($status === 'in_progress') ? array_slice($createdCheckpoints, 0, 6) : array_slice($createdCheckpoints, 0, $completed);
                $scanCursor = $startTime->copy();

                foreach ($pointsToLog as $pIdx => $cp) {
                    $scanCursor->addMinutes(rand(3, 7));
                    $isCpIncident = ($hasIncident && $cp->code === 'CP-04');
                    $isDeviation = ($pIdx === 2); // Sample indoor slight GPS drift

                    SecurityPatrolLog::updateOrCreate(
                        [
                            'patrol_session_id' => $session->id,
                            'checkpoint_id' => $cp->id,
                        ],
                        [
                            'user_id' => $adminId,
                            'scanned_at' => $scanCursor,
                            'lat_actual' => $cp->latitude + ($isDeviation ? 0.0004 : 0.00005),
                            'lng_actual' => $cp->longitude + ($isDeviation ? 0.0004 : 0.00005),
                            'distance_meters' => $isDeviation ? 62.5 : rand(5, 25),
                            'is_deviation' => $isDeviation,
                            'condition_status' => $isCpIncident ? 'incident' : 'normal',
                            'checklist_answers' => [
                                'kondisi' => $isCpIncident ? 'Lampu mati' : 'Semua checklist SOP terpenuhi',
                                'kondisi_pintu' => 'Terkunci',
                            ],
                            'notes' => $isCpIncident ? 'Lampu floodlight padam, perlu penggantian lampu LED 50W.' : 'Aman terkendali.',
                        ]
                    );
                }
            }
        }
    }
}
