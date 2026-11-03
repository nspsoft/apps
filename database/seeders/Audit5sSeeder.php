<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use App\Models\Audit5sChecklist;
use App\Models\Audit5sSchedule;
use App\Models\Audit5sSession;
use App\Models\Audit5sRating;
use App\Models\Audit5sFinding;
use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;

class Audit5sSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::first();
        $adminId = $adminUser ? $adminUser->id : 1;

        // 1. Master Facilities / Buildings
        $building1 = Audit5sBuilding::updateOrCreate(
            ['code' => 'BLD-01'],
            [
                'name' => 'Plant 1 - Pipe Forming & Finishing Workshop',
                'floor_plan_image' => 'images/floor-plans/plant-1.svg',
                'description' => 'Fasilitas utama pembuatan pipa baja ERW, slitting coil, hidrotest, dan penumpukan pipa jadi.',
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        $building2 = Audit5sBuilding::updateOrCreate(
            ['code' => 'BLD-02'],
            [
                'name' => 'Plant 2 - Slitting Line & Raw Coil Yard',
                'floor_plan_image' => 'images/floor-plans/plant-1.svg',
                'description' => 'Fasilitas pembelahan coil baja dan penyimpanan master coil strip.',
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        $building3 = Audit5sBuilding::updateOrCreate(
            ['code' => 'BLD-03'],
            [
                'name' => 'Central Warehouse & Logistics Yard',
                'floor_plan_image' => 'images/floor-plans/plant-1.svg',
                'description' => 'Gudang distribusi produk pipa, staging loading truk, dan area transit peti kemas.',
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        // 2. Master Checklists (15 Industrial 5S Criteria - 3 per pillar)
        $criteria = [
            // Sort (Ringkas)
            [
                'pillar' => 'sort',
                'title' => 'Pemilahan Barang Kerja vs Barang Afkir/Sampah',
                'guideline_notes' => 'Tidak ada potongan plat sisa, besi tua, atau pallet rusak yang teronggok di sekitar mesin kerja.',
                'weight_points' => 7,
                'sort_order' => 1,
            ],
            [
                'pillar' => 'sort',
                'title' => 'Penerapan Label Red Tag (Kartu Merah)',
                'guideline_notes' => 'Barang yang tidak diketahui status kegunaannya harus segera ditempel Red Tag digital dengan batas waktu tindakan.',
                'weight_points' => 7,
                'sort_order' => 2,
            ],
            [
                'pillar' => 'sort',
                'title' => 'Bebas Hambatan pada Jalur Evakuasi & Panel Listrik',
                'guideline_notes' => 'Jalur hijau evakuasi, tangga darurat, APAR, dan pintu panel listrik bebas 100% dari tumpukan barang.',
                'weight_points' => 6,
                'sort_order' => 3,
            ],

            // Set in Order (Rapi)
            [
                'pillar' => 'set_in_order',
                'title' => 'Ketegasan Garis Demarkasi Lantai & Alur Lalu Lintas',
                'guideline_notes' => 'Garis kuning/putih batas area kerja, jalur forklift, dan pejalan kaki terlihat jelas dan tidak pudar.',
                'weight_points' => 7,
                'sort_order' => 4,
            ],
            [
                'pillar' => 'set_in_order',
                'title' => 'Penggunaan Shadow Board & Kotak Perkakas Berlabel',
                'guideline_notes' => 'Kunci pas, tang, mata gerinda, dan alat ukur memiliki cetakan bayangan (shadow board) dan selalu berada pada posisinya.',
                'weight_points' => 7,
                'sort_order' => 5,
            ],
            [
                'pillar' => 'set_in_order',
                'title' => 'Identitas Penataan Rak & Tumpukan Pipa (3T: Tempat, Tempatkan, Tanda)',
                'guideline_notes' => 'Setiap rak bahan, tumpukan coil, dan bundle pipa memiliki papan nama ukuran/spesifikasi yang jelas terbaca.',
                'weight_points' => 6,
                'sort_order' => 6,
            ],

            // Shine (Resik)
            [
                'pillar' => 'shine',
                'title' => 'Kebersihan Mesin dari Ceceran Oli, Coolant & Gram Besi',
                'guideline_notes' => 'Body mesin roll forming, uncoiler, dan gearbox bebas rembesan oli/oli bocor dan tidak ada tumpukan serbuk gerinda.',
                'weight_points' => 7,
                'sort_order' => 7,
            ],
            [
                'pillar' => 'shine',
                'title' => 'Kering dan Bebas Genangan Air/Pelumas pada Lantai',
                'guideline_notes' => 'Lantai kerja kering, bebas lumut, dan tidak licin demi pencegahan kecelakaan kerja (slip & trip hazard).',
                'weight_points' => 7,
                'sort_order' => 8,
            ],
            [
                'pillar' => 'shine',
                'title' => 'Ketersediaan dan Kerapian Alat Kebersihan (Sapu, Kain Majun)',
                'guideline_notes' => 'Tempat sampah terpilah (Organik, B3, Besi Scrap) dan kain majun bekas oli ditaruh di drum tertutup.',
                'weight_points' => 6,
                'sort_order' => 9,
            ],

            // Standardize (Rawat)
            [
                'pillar' => 'standardize',
                'title' => 'Visual Display SOP & Instruksi Kerja (IK) Terawat',
                'guideline_notes' => 'Lembar SOP mesin terlaminasi rapi, bersih, dan mudah dibaca oleh operator mesin di tempat kerja.',
                'weight_points' => 7,
                'sort_order' => 10,
            ],
            [
                'pillar' => 'standardize',
                'title' => 'Rambu K3 & Papan Informasi 5S Up-to-Date',
                'guideline_notes' => 'Rambu wajib APD, penunjuk bahaya tegangan tinggi, dan papan jadwal audit 5S terpasang sesuai standar.',
                'weight_points' => 7,
                'sort_order' => 11,
            ],
            [
                'pillar' => 'standardize',
                'title' => 'Kondisi Penerangan Lampu & Ventilasi Udara',
                'guideline_notes' => 'Lampu penerangan kerja menyala terang dan exhaust fan / ventilasi bebas dari sumbatan debu tebal.',
                'weight_points' => 6,
                'sort_order' => 12,
            ],

            // Sustain (Rajin)
            [
                'pillar' => 'sustain',
                'title' => 'Kepatuhan Pemakaian APD (Alat Pelindung Diri)',
                'guideline_notes' => 'Seluruh personel di zona memakai helm safety, kacamata pelindung, sarung tangan, dan safety shoes.',
                'weight_points' => 7,
                'sort_order' => 13,
            ],
            [
                'pillar' => 'sustain',
                'title' => 'Disiplin 5 Menit Bersih-Bersih Akhir Shift',
                'guideline_notes' => 'Operator rutin melakukan pembersihan dan serah terima kebersihan zona saat pergantian shift.',
                'weight_points' => 7,
                'sort_order' => 14,
            ],
            [
                'pillar' => 'sustain',
                'title' => 'Tindak Lanjut Cepat atas Temuan Audit Sebelumnya',
                'guideline_notes' => 'PIC Area proaktif menyelesaikan catatan temuan audit periode sebelumnya sebelum jatuh tempo.',
                'weight_points' => 6,
                'sort_order' => 15,
            ],
        ];

        foreach ($criteria as $item) {
            Audit5sChecklist::updateOrCreate(
                ['title' => $item['title']],
                $item
            );
        }

        // 3. Link PIC Employees
        $employees = Employee::where('is_active', true)->take(6)->get();
        $productionDept = Department::where('name', 'like', '%Production%')->orWhere('name', 'like', '%Produksi%')->first();
        $qcDept = Department::where('name', 'like', '%Quality%')->orWhere('name', 'like', '%QC%')->first();
        $maintDept = Department::where('name', 'like', '%Maintenance%')->first();
        $warehouseDept = Department::where('name', 'like', '%Warehouse%')->orWhere('name', 'like', '%Gudang%')->first();

        // 4. Plant 1 Zones (6 Key Industrial Bays mapped to coordinates)
        $zonesData = [
            [
                'name' => 'Bay 1: Raw Coil Storage Area',
                'code' => 'Z-COIL-01',
                'area_type' => 'warehouse',
                'department_id' => $warehouseDept ? $warehouseDept->id : null,
                'pic_employee_id' => $employees->count() > 0 ? $employees[0]->id : null,
                'map_x_percent' => 17.50,
                'map_y_percent' => 24.00,
                'latest_score' => 92.50,
                'latest_grade' => 'A',
                'open_findings_count' => 0,
                'red_tags_count' => 0,
            ],
            [
                'name' => 'Bay 2: Slitting Line & Shearing Machine',
                'code' => 'Z-SLIT-02',
                'area_type' => 'production',
                'department_id' => $productionDept ? $productionDept->id : null,
                'pic_employee_id' => $employees->count() > 1 ? $employees[1]->id : null,
                'map_x_percent' => 49.50,
                'map_y_percent' => 24.00,
                'latest_score' => 76.00,
                'latest_grade' => 'B',
                'open_findings_count' => 1,
                'red_tags_count' => 1,
            ],
            [
                'name' => 'Bay 3: Pipe Forming & H.F. Welding Mill',
                'code' => 'Z-MILL-03',
                'area_type' => 'production',
                'department_id' => $productionDept ? $productionDept->id : null,
                'pic_employee_id' => $employees->count() > 2 ? $employees[2]->id : null,
                'map_x_percent' => 82.50,
                'map_y_percent' => 24.00,
                'latest_score' => 88.00,
                'latest_grade' => 'A',
                'open_findings_count' => 0,
                'red_tags_count' => 0,
            ],
            [
                'name' => 'Bay 4: Hydrostatic Testing & NDT Lab',
                'code' => 'Z-QC-04',
                'area_type' => 'production',
                'department_id' => $qcDept ? $qcDept->id : null,
                'pic_employee_id' => $employees->count() > 3 ? $employees[3]->id : null,
                'map_x_percent' => 17.50,
                'map_y_percent' => 73.00,
                'latest_score' => 95.00,
                'latest_grade' => 'A',
                'open_findings_count' => 0,
                'red_tags_count' => 0,
            ],
            [
                'name' => 'Bay 5: Tooling & Mechanical Workshop',
                'code' => 'Z-MAINT-05',
                'area_type' => 'workshop',
                'department_id' => $maintDept ? $maintDept->id : null,
                'pic_employee_id' => $employees->count() > 4 ? $employees[4]->id : null,
                'map_x_percent' => 49.50,
                'map_y_percent' => 73.00,
                'latest_score' => 64.00,
                'latest_grade' => 'C',
                'open_findings_count' => 2,
                'red_tags_count' => 1,
            ],
            [
                'name' => 'Bay 6: Finished Goods Staging & Packing',
                'code' => 'Z-FG-06',
                'area_type' => 'warehouse',
                'department_id' => $warehouseDept ? $warehouseDept->id : null,
                'pic_employee_id' => $employees->count() > 5 ? $employees[5]->id : null,
                'map_x_percent' => 82.50,
                'map_y_percent' => 73.00,
                'latest_score' => 86.50,
                'latest_grade' => 'A',
                'open_findings_count' => 0,
                'red_tags_count' => 0,
            ],
        ];

        $createdZones = [];
        foreach ($zonesData as $zd) {
            $zd['building_id'] = $building1->id;
            $zd['latest_audit_date'] = Carbon::today()->subDays(rand(1, 10));
            $zone = Audit5sZone::updateOrCreate(
                ['building_id' => $building1->id, 'code' => $zd['code']],
                $zd
            );
            $createdZones[] = $zone;
        }

        // 5. Schedules
        $schedule = Audit5sSchedule::updateOrCreate(
            ['title' => 'Audit 5S Mingguan - Plant 1 (October Round)'],
            [
                'frequency' => 'weekly',
                'scheduled_date' => Carbon::today()->subDays(5),
                'auditor_id' => $adminId,
                'building_id' => $building1->id,
                'status' => 'completed',
                'notes' => 'Audit rutin menyeluruh mencakup 6 bay workshop Plant 1.',
            ]
        );

        // 6. Audit Sessions & Findings
        $allChecklists = Audit5sChecklist::all();

        foreach ($createdZones as $zIdx => $zone) {
            $sessionCode = '5S-' . Carbon::today()->format('Ym') . '-00' . ($zIdx + 1);
            $totalScore = $zone->latest_score ?: 85.0;
            $grade = $zone->latest_grade ?: 'A';

            $session = Audit5sSession::updateOrCreate(
                ['session_code' => $sessionCode],
                [
                    'schedule_id' => $schedule->id,
                    'zone_id' => $zone->id,
                    'auditor_id' => $adminId,
                    'audit_date' => $zone->latest_audit_date ?: Carbon::today(),
                    'score_sort' => round($totalScore * 0.20, 2),
                    'score_set_in_order' => round($totalScore * 0.20, 2),
                    'score_shine' => round($totalScore * 0.20, 2),
                    'score_standardize' => round($totalScore * 0.20, 2),
                    'score_sustain' => round($totalScore * 0.20, 2),
                    'total_score' => $totalScore,
                    'grade' => $grade,
                    'status' => 'completed',
                    'summary_notes' => "Hasil evaluasi audit 5S pada {$zone->name}. Standar kepatuhan: {$grade}.",
                ]
            );

            // Populate ratings
            foreach ($allChecklists as $chk) {
                Audit5sRating::updateOrCreate(
                    ['session_id' => $session->id, 'checklist_id' => $chk->id],
                    [
                        'pillar' => $chk->pillar,
                        'score' => $totalScore >= 85 ? rand(4, 5) : rand(2, 4),
                        'notes' => 'Kondisi sesuai standar inspeksi.',
                    ]
                );
            }

            // Create sample findings for Bay 2 (Slitting) and Bay 5 (Maintenance)
            if ($zone->code === 'Z-SLIT-02') {
                Audit5sFinding::updateOrCreate(
                    ['session_id' => $session->id, 'description' => 'Tumpukan sisa potongan plat strip menumpuk di dekat rotary shear tanpa penanda Red Tag.'],
                    [
                        'zone_id' => $zone->id,
                        'pillar' => 'sort',
                        'before_photo_path' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&auto=format&fit=crop',
                        'is_red_tag' => true,
                        'red_tag_action' => 'scrap',
                        'due_date' => Carbon::today()->addDays(3),
                        'status' => 'in_progress',
                        'after_photo_path' => null,
                        'remedy_notes' => null,
                    ]
                );
            }

            if ($zone->code === 'Z-MAINT-05') {
                // Finding 1: Open finding (oil leak)
                Audit5sFinding::updateOrCreate(
                    ['session_id' => $session->id, 'description' => 'Genangan oli mesin bubut belum dibersihkan dan serbuk gerinda berserakan.'],
                    [
                        'zone_id' => $zone->id,
                        'pillar' => 'shine',
                        'before_photo_path' => 'https://images.unsplash.com/photo-1581092335397-9583fe92d232?w=800&auto=format&fit=crop',
                        'is_red_tag' => false,
                        'red_tag_action' => 'none',
                        'due_date' => Carbon::today()->addDays(2),
                        'status' => 'open',
                        'after_photo_path' => null,
                        'remedy_notes' => null,
                    ]
                );

                // Finding 2: Closed finding with Before & After photo
                Audit5sFinding::updateOrCreate(
                    ['session_id' => $session->id, 'description' => 'Kunci pas dan tracker bearing tidak ditaruh pada shadow board.'],
                    [
                        'zone_id' => $zone->id,
                        'pillar' => 'set_in_order',
                        'before_photo_path' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&auto=format&fit=crop',
                        'is_red_tag' => false,
                        'red_tag_action' => 'none',
                        'due_date' => Carbon::today()->subDays(1),
                        'status' => 'verified',
                        'after_photo_path' => 'https://images.unsplash.com/photo-1581092162384-8987c1d64718?w=800&auto=format&fit=crop',
                        'remedy_notes' => 'Perkakas telah dirapikan kembali ke shadow board bertanda warna.',
                        'remediated_by' => $adminId,
                        'remediated_at' => Carbon::today()->subDays(1),
                        'verified_by' => $adminId,
                        'verified_at' => Carbon::today(),
                        'verification_notes' => 'Verifikasi selesai. Kondisi shadow board rapi 100%.',
                    ]
                );
            }
        }
    }
}
