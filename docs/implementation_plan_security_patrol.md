# 🛠️ Technical Implementation Plan: Digital Security Patrol & Guard Tour System

Modul ini menyediakan sistem patroli keamanan satpam terintegrasi berbasis **QR Code**, **GPS Geofencing Map**, **Resume Patrol (Rekapitulasi)**, dan **Grafik Analitik Kepatuhan**.

---

## 1. 🗄️ Database Architecture & Migrations

### A. Tabel Baru:
1. **`security_checkpoints`** (Master Titik Pos Patroli)
   - `id` (bigIncrements)
   - `code` (string, unique, index) — contoh: `CP-01`
   - `name` (string) — contoh: `Pos Gerbang Utama`, `Gudang B3 Bahan Kimia`
   - `description` (text, nullable)
   - `area_zone` (string, nullable) — contoh: `Ring 1 - Produksi`, `Ring 2 - Gudang`, `Pagar Keliling`
   - `latitude` (decimal 10, 8) — Titik kordinat master
   - `longitude` (decimal 11, 8) — Titik kordinat master
   - `geofence_radius` (integer, default 50) — Toleransi radius (meter)
   - `qr_token` (string, unique, index) — Hash unik untuk validasi QR Code (mencegah pemalsuan manual)
   - `sop_items` (json, nullable) — Item checklist SOP pos (misal: `["Gembok terkunci", "APAR baik", "Lampu nyala"]`)
   - `is_active` (boolean, default true)
   - `created_by`, `updated_by` (foreign keys)
   - `deleted_at`, `created_at`, `updated_at`

2. **`security_patrol_schedules`** (Master Template Jadwal Shift)
   - `id` (bigIncrements)
   - `shift_id` (foreign key ke `shifts`, nullable)
   - `name` (string) — contoh: `Patroli Rutin Shift Malam (Putaran 1)`
   - `start_time` (time) — Jam mulai patroli
   - `end_time` (time) — Batas akhir patroli
   - `checkpoints_count` (integer, default 0)
   - `is_active` (boolean, default true)
   - `deleted_at`, `created_at`, `updated_at`

3. **`security_patrol_schedule_checkpoints`** (Pivot Schedule & Checkpoints)
   - `id`
   - `schedule_id` (foreign key)
   - `checkpoint_id` (foreign key)
   - `sort_order` (integer, default 0)

4. **`security_patrol_sessions`** (Transaksi Sesi Patroli Per Shift)
   - `id` (bigIncrements)
   - `session_code` (string, unique, index) — contoh: `PTR-20261031-0001`
   - `schedule_id` (foreign key, nullable)
   - `user_id` (foreign key ke `users` — satpam yang bertugas)
   - `shift_name` (string)
   - `patrol_date` (date, index)
   - `start_time` (dateTime)
   - `end_time` (dateTime, nullable)
   - `duration_seconds` (integer, nullable)
   - `total_checkpoints` (integer, default 0)
   - `completed_checkpoints` (integer, default 0)
   - `missed_checkpoints` (integer, default 0)
   - `incidents_count` (integer, default 0)
   - `status` (enum: `'scheduled'`, `'in_progress'`, `'completed'`, `'incomplete'`, default `'scheduled'`)
   - `notes` (text, nullable)
   - `deleted_at`, `created_at`, `updated_at`

5. **`security_patrol_logs`** (Rincian Scan Per Titik Checkpoint)
   - `id` (bigIncrements)
   - `patrol_session_id` (foreign key ke `security_patrol_sessions`, index)
   - `checkpoint_id` (foreign key ke `security_checkpoints`, index)
   - `user_id` (foreign key ke `users`)
   - `scanned_at` (dateTime, index)
   - `lat_actual` (decimal 10, 8, nullable)
   - `lng_actual` (decimal 11, 8, nullable)
   - `distance_meters` (decimal 8, 2, nullable)
   - `is_deviation` (boolean, default false) — True jika di luar radius > 50m
   - `condition_status` (enum: `'normal'`, `'incident'`, default `'normal'`)
   - `checklist_answers` (json, nullable)
   - `photo_path` (string, nullable)
   - `notes` (text, nullable)
   - `ga_ticket_id` (foreign key ke `ga_tickets`, nullable) — Terhubung langsung ke modul tiket GA
   - `created_at`, `updated_at`

---

## 2. 🏛️ Backend Architecture (Laravel 12 / PHP 8.2+)

### A. Eloquent Models:
* `app/Models/SecurityCheckpoint.php`
* `app/Models/SecurityPatrolSchedule.php`
* `app/Models/SecurityPatrolSession.php`
* `app/Models/SecurityPatrolLog.php`

### B. Controllers & Services:
* `app/Http/Controllers/SecurityPatrol/SecurityPatrolMapController.php`
  - `index()`: Mengirim data live session, status titik pos hari ini, dan koordinat ke Leaflet Map.
* `app/Http/Controllers/SecurityPatrol/SecurityPatrolExecutionController.php`
  - `active()`: Tampilan antarmuka mobile satpam untuk menjalankan sesi patroli aktif.
  - `startSession(Request $request)`: Inisiasi sesi patroli shift.
  - `scanCheckpoint(Request $request)`: Validasi QR token + perhitungan jarak Haversine GPS + upload foto + status Aman/Insiden + otomatis `GaTicket` jika ada insiden.
  - `finishSession(Request $request)`: Finalisasi otomatis (hitung total selesai, missed, durasi).
* `app/Http/Controllers/SecurityPatrol/SecurityPatrolResumeController.php`
  - `index()`: Rekapitulasi log patroli per shift/tanggal dengan filter komprehensif.
  - `exportExcel()` & `exportPdf()`: Unduh rekap resmi untuk Danru / Manajemen.
* `app/Http/Controllers/SecurityPatrol/SecurityPatrolAnalyticsController.php`
  - `index()`: Data agregat grafik: Tren kepatuhan mingguan, perbandingan shift, titik paling sering terlewat, rincian insiden.
* `app/Http/Controllers/SecurityPatrol/SecurityCheckpointController.php`
  - `index()`, `store()`, `update()`, `destroy()`: Kelola master titik pos.
  - `printLabels(Request $request)`: Format cetak stiker/kartu label QR Code pos.
* `app/Http/Controllers/SecurityPatrol/SecurityPatrolScheduleController.php`
  - `index()`, `store()`, `update()`, `destroy()`: Kelola jadwal shift patroli.

---

## 3. 🎨 Frontend Architecture (Inertia.js + Vue 3 + Tailwind CSS v4)

### A. Sidebar Menu Baru di `AppLayout.vue`:
Menambahkan grup menu mandiri **Security Patrol** dengan ikon `ShieldCheckIcon`:
1. **Live Map & Monitor** (`/security-patrol/map`)
2. **Sesi & Jadwal** (`/security-patrol/schedules`)
3. **Resume Patrol** (`/security-patrol/resume`)
4. **Grafik & Analitik** (`/security-patrol/analytics`)
5. **Master Titik & Cetak QR** (`/security-patrol/checkpoints`)

### B. Komponen Halaman Vue:
1. `resources/js/Pages/SecurityPatrol/Map/Index.vue`:
   - Peta Leaflet dengan custom pin markers (Hijau = Aman, Kuning = Belum Dikunjungi, Oranye = Temuan Insiden).
   - Garis rute (*Polyline*) pergerakan satpam.
   - Panel ringkasan status patroli hari ini secara real-time.
2. `resources/js/Pages/SecurityPatrol/Active/RunPatrol.vue` (Mobile-Optimized):
   - Header status shift, timer durasi, progress bar (% titik terlewati).
   - Scanner kamera HTML5-QRCode langsung di browser HP dengan switch kamera depan/belakang dan senter flash.
   - Validasi Geolocation GPS akurat tinggi.
   - Form cepat: Pilihan "Kondisi Aman" / "Ada Temuan Kerusakan", checklist SOP ringkas, ambil foto langsung dari kamera HP.
3. `resources/js/Pages/SecurityPatrol/Resume/Index.vue`:
   - Data table responsif: Tanggal, Shift, Petugas, Total Titik, Selesai, Terlewat, Durasi, Insiden.
   - Slide-over Drawer: Rincian setiap pos yang dikunjungi, stempel jam scan, foto bukti, dan deviasi GPS.
   - Filter pencarian (Tanggal, Shift, Status, Satpam) + tombol ekspor Excel/PDF.
4. `resources/js/Pages/SecurityPatrol/Analytics/Index.vue`:
   - KPI Cards: % Kepatuhan (*Compliance Rate*), Rata-rata Durasi, Total Insiden Bulan Ini, Pos Sering Terlewat.
   - Grafik Line Chart: Tren Kepatuhan Patroli Harian (7 - 30 Hari).
   - Grafik Bar Chart: Pos Titik Terlewat Terbanyak (*Top Missed Checkpoints*).
   - Grafik Doughnut: Rasio Selesai Tepat Waktu vs Terlambat vs Terlewat.
5. `resources/js/Pages/SecurityPatrol/Checkpoints/Index.vue`:
   - Daftar master pos, pencarian, dan integrasi `MapPicker.vue` untuk memplot titik koordinat GPS.
   - Modal cetak lembar stiker QR Code siap cetak di kertas A4 / stiker pos.

---

## 4. 🧪 Tahapan Eksekusi & Rencana Verifikasi

### Langkah 1: Database Migrations, Models, & Seeder Realistis
* Buat file migration untuk 5 tabel.
* Buat model Eloquent lengkap dengan relasi dan casting.
* Buat Seeder `SecurityPatrolSeeder` dengan 10 titik pos pabrik nyata (Gerbang Utama, Gudang Pipa B3, Panel Genset, dsb.) + jadwal shift + riwayat patroli contoh agar grafik & resume langsung teruji.
* Verifikasi: `php artisan migrate`, `php artisan db:seed --class=SecurityPatrolSeeder`.

### Langkah 2: Backend Logic & Routes
* Daftarkan route di `routes/web.php`.
* Buat seluruh Controller dengan penanganan transaksi database, validasi haversine formula untuk jarak GPS, dan upload foto watermarking.
* Verifikasi: Pastikan route terdaftar via `php artisan route:list | grep security-patrol`.

### Langkah 3: Frontend Views & Integrasi Komponen
* Buat 5 halaman Vue di `resources/js/Pages/SecurityPatrol/`.
* Tambahkan menu **Security Patrol** di `resources/js/Layouts/AppLayout.vue`.
* Verifikasi tampilan non-destruktif: `npx tsc --noEmit` / build check tanpa merusak dev server.

### Langkah 4: Autonomous QA & Browser E2E Testing
* Membuka halaman di browser via Chrome DevTools MCP.
* Menguji navigasi menu: Map, Sesi & Jadwal, Resume Patrol, Grafik Analitik, Master Titik.
* Memverifikasi tidak ada console errors atau layout overflow.
* Mengambil tangkapan layar (screenshot) sebagai bukti visual verifikasi.

### Langkah 5: Dokumentasi SOP Pengguna (`USER_GUIDE.md`)
* Menyiapkan panduan operasional langkah demi langkah untuk Satpam (cara buka HP & scan) dan Danru/GA (cara monitoring, analisa grafik, dan cetak QR).
