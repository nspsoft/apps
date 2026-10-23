# Panduan Modul, Fitur, dan PIC Aplikasi ERP

Aplikasi ini merupakan sistem ERP (Enterprise Resource Planning) yang sangat komprehensif, mencakup operasional terpadu mulai dari Penjualan, Pembelian, Produksi, Kualitas, Keuangan, hingga SDM. 

Berikut adalah penjabaran seluruh modul/menu, fungsinya, serta spesifikasi Person In Charge (PIC) atau role user yang berwenang melakukan input data.

---

## 1. Modul Sales (Penjualan)
Digunakan untuk mengelola siklus penjualan, mulai dari prospek hingga pengiriman.
**PIC / Role Utama:** _Sales Admin_, _Sales Representative_, _Sales Manager_.

*   **Sales Hub, Planning, Forecast, Schedule:** Dasbor dan perencanaan target/jadwal pengiriman penjualan (PIC: Sales Manager / Supervisor).
*   **Customers:** Database pelanggan / pembeli (PIC: Sales Admin).
*   **Quotations:** Pembuatan Surat Penawaran Harga kepada pelanggan (PIC: Sales Admin / Sales Rep).
*   **Sales Orders (SO):** Input pesanan resmi dari pelanggan (PIC: Sales Admin).
*   **Delivery Orders (DO):** Pembuatan Surat Jalan untuk pengiriman barang ke customer (PIC: Logistics Admin / Sales Admin).
*   **Sales Invoices:** Pembuatan faktur/tagihan penjualan (PIC: Finance AR / Sales Admin).
*   **Sales Returns:** Pencatatan retur barang dari pelanggan (PIC: Sales Admin).
*   **Reports & Tracking (Items, PO Tracking, AI PO Extractor):** Pelacakan status dokumen dan ekstraksi PO dari file via AI.

## 2. Modul CRM (Manajemen Hubungan Pelanggan)
Digunakan untuk interaksi prospek, kampanye, dan komunikasi.
**PIC / Role Utama:** _Marketing Admin_, _Sales Representative_, _CRM Staff_.

*   **WhatsApp Center & AI Email Inbox:** Sentralisasi komunikasi chat dan email dengan pelanggan (PIC: Customer Service / Sales).
*   **Leads Management & Opportunity Tracking:** Rekap calon pelanggan berpotensi (Leads) untuk dipantau status pendekatannya (PIC: Sales / Marketing).
*   **Marketing Campaigns:** Penyiapan dan jadwal peluncuran promosi massal (PIC: Marketing Admin).

## 3. Modul Purchasing (Pembelian)
Mengelola pengadaan barang mentah, aset, hingga jasa dari luar.
**PIC / Role Utama:** _Purchasing Admin_, _Procurement Manager_, _Buyer_.

*   **Procurement Ops & Delivery Schedule:** Dasbor pengadaan serta pantauan jadwal barang datang (PIC: Purchasing Manager).
*   **Suppliers:** Database pemasok / vendor (PIC: Purchasing Admin).
*   **Purchase Requests (PR):** Input permintaan pembelian barang dari berbagai departemen (PIC: _Semua Dept Head / User_ yang butuh barang).
*   **Purchase Orders (PO):** Pembuatan dokumen pemesanan resmi ke Supplier (PIC: Purchasing / Buyer).
*   **Goods Receipts (GR) / Bukti Terima Barang:** Bukti bahwa barang dari supplier telah tiba (PIC: Warehouse Admin / Receiving Staff).
*   **AI Gen. Receipt / DN Extractor:** Pindai dokumen jalan supplier jadi tanda terima otomatis.
*   **Purchase Invoices & Returns:** Tagihan supplier dan pengembalian barang cacat (PIC: Finance AP & Purchasing Admin).

## 4. Modul Inventory (Gudang & Stok)
Sistem tracking barang mentah, setengah jadi, hingga barang jadi.
**PIC / Role Utama:** _Warehouse Admin_, _Inventory Controller_, _Master Data Admin_.

*   **Command Center & Current Stock:** Pemantauan indikator *Live Stock* seluruh produk.
*   **Categories, Products, Units:** Registrasi jenis kategori, satuan ukur, dan master produk baru (PIC: Master Data / Inventory Admin).
*   **Warehouses:** Pengaturan fisik lokasi gudang dan rak (PIC: Warehouse Manager).
*   **Stock Movements & Transfers:** Pemindahan barang antar lokasi/gudang (PIC: Warehouse Admin).
*   **Stock Opname:** Input *adjustment* hasil hitung fisik gudang vs sistem (PIC: Inventory Controller / QC Auditor).

## 5. Modul Manufacturing (Produksi)
Mengendalikan proses pabrikasi dan pelacakan material ke mesin.
**PIC / Role Utama:** _Production PPIC_, _Production Operator / Admin_, _Engineering_.

*   **Bill of Materials (BOM):** Pembuatan resep komponen atau struktur formula pembentuk produk jadi (PIC: Engineering / R&D / PPIC).
*   **Production Routing:** Jalur urutan kerja/mesin untuk pembuatan barang (PIC: PPIC).
*   **Work Orders (WO):** Perintah kerja fabrikasi berdasarkan permintaan atau target stok (PIC: PPIC).
*   **Input Output (Production Entry):** Laporan pemakaian bahan (Issue) dan hasil jadi produksi (PIC: Production Admin / Operator).
*   **Machine & Shift Management:** Pendataan pengaturan waktu shift dan jadwal pakai mesin.
*   **Subcontract Orders:** Pencatatan titip proses produksi di pihak ketiga / vendor (Maklon) (PIC: PPIC / Purchasing).

## 6. Modul Maintenance (Pemeliharaan Aset)
Pengorganisasian rawat-inap/perawatan pencegahan untuk mesin/fasilitas.
**PIC / Role Utama:** _Maintenance Admin_, _Teknisi_, _Engineering Support_.

*   **Preventive Schedule:** Penjadwalan rutinitas perawatan mesin (PIC: Maintenance Planner/Admin).
*   **Breakdown Logs:** Catatan laporan kerusakan mendadak di lapangan (PIC: Operator/Maintenance Admin).
*   **Spareparts Inventory:** Gudang khusus mendata keluar-masuk suku cadang mesin (PIC: Maintenance / Warehouse Admin).

## 7. Modul Quality Control (QC)
Verifikasi kualitas bahan dan produk yang meliputi pengetesan dan penyitaan (karantina).
**PIC / Role Utama:** _QC Inspector_, _QA Manager_.

*   **Incoming Inspection:** Pemeriksaan bahan baku datang (Goods Receipt) sebelum dipakai (PIC: QC Inspector).
*   **In-Process QC:** Pengecekan sampel fisik di tengah proses lantai produksi (PIC: QC Line Inspector).
*   **Defect Management (NCR):** Pencatatan produk *Non-Conforming* / cacat serta tindak lanjutnya (PIC: QA/QC Admin).
*   **COA Generator:** Penerbitan *Certificate of Analysis* untuk konsumen (PIC: QA Admin).
*   **Master Points:** Setting parameter standar ukur yang lulus limit uji (PIC: QC Manager).

## 8. Modul Logistics (Logistik & Armada)
Koordinasi kesiapan armada transport dan distribusi.
**PIC / Role Utama:** _Logistics Planner_, _Dispatcher_, _Driver_.

*   **Loading Queue:** Penjadwalan kapan armada bersandar *docking* untuk muat barang (PIC: Warehouse Logistik).
*   **Delivery Planning & Dispatch:** Alokasi supir, kendaraan, dan rute perjalanan berdasarkan Delivery Order (PIC: Logistics Dispatcher).
*   **Vehicle Fleet:** Pendataan izin jalan dan aset kendaraan perusahaan (PIC: Fleet Admin).

## 9. Modul Finance (Keuangan & Akuntansi)
Pembukuan mutasi uang, piutang, hutang, dan laba rugi.
**PIC / Role Utama:** _Finance & Accounting Staff_, _Controller_, _CFO_.

*   **General Ledger (Buku Besar):** Penjurnalan entri manual tiap transaksi bisnis (PIC: Accounting).
*   **Profit & Loss / Reports:** Neraca keuangan (Otomatis / View Only).
*   **AP & AR Monitoring:** Penjadwalan bayar hutang (AP) ke supplier dan tagih piutang (AR) ke pelanggan (PIC: Finance Staff).
*   **Production Costing & Overhead:** (Modul *Costing*) Penentuan Harga Pokok Produksi (HPP/COGS) berdasarkan alokasi biaya listrik/buruh, dll (PIC: Cost Accountant).

## 10. Modul Human Resources (HR & Payroll)
Sistem absensi, kepegawaian, penggajian, dan tata kelola SDM internal perusahaan.
**PIC / Role Utama:** _HR Manager_, _HR Admin_, _Payroll Officer_.

*   **Employee Directory:** Master biodata karyawan, NIK, penempatan departemen, dan status kontrak (PIC: HR Admin).
*   **Pendaftaran Face ID (Biometric):** Registrasi wajah karyawan untuk absensi biometrik nir-sentuh (PIC: HR Admin).
*   **Attendance & Dashboard:** Monitoring kehadiran harian, keterlambatan, pulang awal, dan lembur (PIC: HR Admin).
*   **Attendance Kiosk:** Layar tap absensi mandiri kamera berbasis tablet/web di pos sekuriti/lobby (PIC: HR / Sekuriti).
*   **Jadwal & Shift:** Pengaturan shift kerja dinamis, toleransi jam telat, dan aturan penalti denda (PIC: HR Admin).
*   **Leave Management:** Persetujuan cuti tahunan, sakit, dan izin khusus (PIC: HR Admin & Dept Head).
*   **Overtime Requests:** Verifikasi dan persetujuan Surat Perintah Kerja Lembur (SPKL) (PIC: Dept Head & HR).
*   **HR Reimbursements:** Verifikasi klaim biaya medis/transport dan pembayaran ke karyawan (PIC: HR & Finance).
*   **Payroll & Settings:** Penggajian terotomatisasi (Gaji Pokok, Tunjangan, BPJS, PPh 21, Potongan Telat, dan slip gaji PDF/WA) (PIC: Payroll Officer).
*   **Performance Monitoring:** Evaluasi KPI dan penilaian berkala kinerja karyawan (PIC: HR & Management).
*   **HR WhatsApp Center:** Saluran komunikasi chat resmi HR ke seluruh karyawan (PIC: HR Admin).

## 11. Modul General Affair (GA)
Pengelolaan aset umum, operasional gedung, kendaraan dinas, dan pengadaan kantor.
**PIC / Role Utama:** _GA Manager_, _GA Officer_, _Building Maintenance_.

*   **GA Dashboard:** Ringkasan status tiket servis, jadwal pemeliharaan gedung, dan okupansi armada.
*   **Tickets:** Pengaduan kerusakan fasilitas kerja (lampu, AC, meja, toilet, plumbing) dari staf internal (PIC: GA Officer).
*   **Assets:** Master inventaris aset non-produksi (furnitur, laptop kantor, printer, AC, genset) beserta nomor barcode (PIC: GA Asset Admin).
*   **Preventive Maintenance (PM):** Kalender servis rutin fasilitas gedung dan pencatatan riwayat teknisi (PIC: GA Officer).
*   **Armada Operasional (Fleet):** Pendataan mobil/motor dinas kantor, masa berlaku STNK/KIR, dan riwayat servis (PIC: GA Fleet Staff).
*   **Peminjaman Kendaraan (Vehicle Booking):** Formulir permohonan pinjam mobil dinas kantor, verifikasi approval, catatan kilometer awal/akhir, dan pengembalian (PIC: All Staff & GA Approver).
*   **Locations:** Pemetaan denah ruangan, lantai gedung, dan pos operasional (PIC: GA Admin).
*   **Requests (PR):** Pengajuan pembelian belanja kebutuhan rumah tangga kantor / GA ke Purchasing (PIC: GA Officer).

## 12. Modul Rekrutmen & Pelamar (ATS & Portal Karir)
Sistem penerimaan pegawai baru dari pembukaan lowongan hingga seleksi.
**PIC / Role Utama:** _HR Recruiter_, _Hiring Manager_.

*   **Job Postings (ATS):** Pembuatan dan penayangan lowongan kerja baru ke publik (PIC: HR Recruiter).
*   **Applicant Tracking:** Pelacakan berkas lamaran, riwayat CV, tahapan interview, psikotes, hingga penawaran kerja (Offering) (PIC: HR Recruiter).
*   **Public Careers Portal (`/careers`):** Halaman depan eksternal responsif bagi publik untuk melamar kerja tanpa perlu login ke sistem ERP.

## 13. Modul Employee Self-Service (ESS Portal)
Portal mandiri bagi setiap karyawan untuk mengelola urusan kepegawaian pribadi dari smartphone atau browser.
**PIC / Role Utama:** _Seluruh Karyawan Perusahaan (All Employees)_.

*   **Smart Attendance:** Tap absensi langsung dari HP/Laptop dengan verifikasi kamera (PIC: Karyawan).
*   **My Attendance:** Rekap histori absensi pribadi per bulan (PIC: Karyawan).
*   **My Time-Off (`/my-timeoff`):** Pengajuan cuti, izin, dan surat sakit mandiri (PIC: Karyawan).
*   **My Overtime:** Pengajuan permohonan lembur mandiri ke atasan (PIC: Karyawan).
*   **My Reimbursements:** Pengajuan klaim nota belanja operasional/kesehatan dengan bukti foto (PIC: Karyawan).
*   **My Performance:** Melihat penilaian capaian target dan feedback dari atasan (PIC: Karyawan).

## 14. Modul Meeting Command & AI MoM
Sentralisasi manajemen rapat kerja, absensi digital, dan notulensi cerdas berbasis AI.
**PIC / Role Utama:** _Sekretaris Direksi_, _Project Leader_, _Department Head_.

*   **Dashboard / List Rapat:** Kalender dan daftar agenda rapat yang sedang/akan berlangsung (PIC: Meeting Organizer).
*   **New Meeting:** Pembuatan undangan rapat, penetapan ruang/link virtual, dan daftar peserta wajib/opsional (PIC: Organizer).
*   **Public Check-in:** Presensi kehadiran rapat digital via pemindaian QR code atau tautan mandiri oleh peserta.
*   **AI Transcription & Audio Processing:** Fitur cerdas Google Gemini untuk mengekstrak rekaman suara/catatan rapat menjadi ringkasan resmi dan notulensi (Minutes of Meeting / MoM) secara otomatis.
*   **Action Items Kanban Board:** Papan penugasan tugas lanjutan (Follow-up Tasks) pasca-rapat dengan status To-Do, In-Progress, Done.
*   **Approval & WhatsApp Dispatch:** Pengesahan resmi risalah rapat dan pengiriman notifikasi rangkuman hasil rapat via WhatsApp ke nomor peserta.

## 15. Modul Automated Bell System (JICOS Bell Agent)
Sistem otomatisasi jadwal bel dan sirene pabrik yang terintegrasi antara cloud ERP dengan speaker fisik di area workshop.
**PIC / Role Utama:** _HR Officer_, _Facility Admin_, _PPIC_.

*   **Schedule Settings:** Pengaturan jadwal alarm dan bel harian/mingguan (Jam Masuk Kerja, Bel Istirahat Shift 1/2/3, Bel Masuk Kembali, Bel Pulang, Alarm Darurat) (PIC: HR / GA).
*   **Bell Terminal (Kiosk):** Pemutar audio bel berbasis antarmuka web mandiri di ruang audio/sekuriti.
*   **JICOS Bell Agent (`apps/bell-agent`):** Aplikasi background client (Python) yang berjalan di komputer pabrik terhubung ke sound system/amplifier. Sinkron otomatis via API ke server ERP untuk memutar file chime MP3/WAV secara tepat waktu tanpa koneksi internet statis.

## 16. Modul Fasilitas & Video Wall CCTV (Live Streaming)
Pemantauan keamanan visual area pabrik, gudang, dan gerbang secara real-time.
**PIC / Role Utama:** _Security Head_, _Safety Officer_, _Operations Director_.

*   **Video Wall (Kiosk):** Tata letak layar multi-channel (Grid 2x2, 3x3) untuk TV monitor pemantau operasional di command center atau pos sekuriti.
*   **CCTV Settings:** Pendaftaran stream RTSP/HLS kamera CCTV, pengaturan resolusi, dan mapping titik kamera per area gudang/pabrik (PIC: IT / Security).
*   **Snapshot Generator:** Pengambilan cuplikan visual otomatis untuk lampiran bukti insiden keamanan.

## 17. Modul Helpdesk & Dukungan Internal
Ticketing keluhan dan permohonan bantuan teknis IT dan operasional.
**PIC / Role Utama:** _IT Support_, _Helpdesk Admin_, _Seluruh Pengguna_.

*   **Create Ticket:** Pembuatan tiket komplain/permohonan bantuan (komputer error, printer macet, reset password) lengkap dengan tingkat prioritas (Low, Medium, High, Urgent).
*   **Ticket Queue & Discussion:** Alur interaksi chat tanya-jawab teknisi dengan pemohon hingga masalah terselesaikan (*Closed*).

## 18. Modul Driver Mobile Portal
Antarmuka khusus supir pengiriman logistik yang dioptimalkan untuk layar ponsel.
**PIC / Role Utama:** _Supir Pengiriman (Delivery Drivers)_.

*   **Driver Dashboard:** Daftar Surat Jalan (DO) yang ditugaskan ke supir hari ini.
*   **DO Barcode Scanner:** Pemindaian barcode lembar surat jalan menggunakan kamera smartphone supir saat memuat barang ke truk.
*   **Arrival & Delivery Confirmation:** Supir mengonfirmasi saat tiba di lokasi pembeli, mengambil foto bukti terima barang, dan mencatat nama penerima.

## 19. Modul Portal Mitra Eksternal (Supplier & Partner Portal)
Portal web aman dengan hak akses terbatas yang disediakan khusus bagi mitra pemasok (vendor).
**PIC / Role Utama:** _Vendor / Rekanan Pemasok Perusahaan_.

*   **Portal Dashboard:** Ringkasan pesanan aktif dan status tagihan vendor.
*   **Purchase Orders:** Vendor melihat PO resmi dari perusahaan, serta melakukan konfirmasi kesanggupan (*Acknowledge*) atau penolakan (*Reject*).
*   **Surat Jalan Vendor (Deliveries):** Vendor membuat dan mencetak Surat Jalan pengiriman dari sistem sebelum mengirim truk ke pabrik kita.
*   **Unggah Faktur (Invoices):** Vendor mengunggah berkas tagihan/invoice dan kwitansi secara online untuk mempercepat proses verifikasi AP.
*   **Schedule & RFQ:** Melihat jadwal penerimaan pabrik dan menanggapi permintaan penawaran harga (*Request For Quotation*).
*   **Document Repository:** Tempat vendor mengunduh PO resmi dan mengunggah dokumen legalitas (NPWP, NIB, Sertifikasi).

## 20. Modul Project Matrix (Manajemen Proyek)
Pengelolaan proyek jangka panjang, instalasi mesin pabrik, maupun proyek internal IT.
**PIC / Role Utama:** _Project Manager_, _Project Engineer_, _Team Members_.

*   **Project Dashboard:** Pantauan linimasa progres proyek, persentase penyelesaian, dan kurva anggaran.
*   **Task Management & Kanban:** Pembagian tugas (WBS), penetapan tenggat waktu, PIC penanggung jawab, dan lampiran berkas teknis.
*   **Project Members:** Alokasi anggota tim dan pembagian peran proyek.

## 21. Modul Pengaturan (Settings / IT & System Admin)
Konfigurasi akar sistem, hak akses, alur persetujuan, dan utilitas pemeliharaan aplikasi.
**PIC / Role Utama:** _Super Admin_, _Head of IT_, _System Administrator_.

*   **User Management:** Pembuatan, penonaktifan, dan reset akses akun login pengguna (PIC: IT).
*   **Roles & Permissions:** Manajemen kelompok jabatan (*roles*) serta penentuan matriks hak akses (*permissions*) granular (PIC: IT Manager / Super Admin).
*   **Mobile Navbar:** Penyesuaian navigasi menu bawah (*bottom bar*) untuk pengalaman aplikasi mobile (PIC: IT).
*   **Company Profile:** Konfigurasi identitas pendaftaran PT, NPWP, Logo Kop Surat Invoice, dan kontak resmi (PIC: IT / Direksi).
*   **Departments:** Penataan struktur departemen dan divisi perusahaan (PIC: HR Admin / IT).
*   **AI Configuration:** Setelan integrasi Google Gemini (API key, threshold temperatur, dan prompt pemrosesan dokumen) (PIC: IT).
*   **Document Numbering:** Pengaturan formula kode penomoran dokumen otomatis (Prefix, Romawi Bulan, Sequence, Suffix) (PIC: Finance Controller / IT).
*   **Regional & Tax:** Penentuan jenis tarif PPN, mata uang asing, dan zona perpajakan regional (PIC: Finance / Tax Officer).
*   **System Preferences:** Penyetelan preferensi dasar ERP (timeout login, notifikasi email, sesi pengguna) (PIC: IT).
*   **Workflow Approval:** Penyusunan matriks alur persetujuan bertingkat per jenis dokumen dan batas nominal (PIC: IT / Direksi).
*   **Database Management:** Utilitas backup, restore basis data, dan optimalisasi tabel (PIC: IT / DBA).
*   **Activity Logs (Audit Trail):** Penelusuran jejak rekam detail seluruh aksi di sistem (PIC: IT / Internal Auditor).
*   **WhatsApp Bot:** Konfigurasi koneksi Gateway API Wablas/Fonnte dan template pesan otomatis (PIC: IT / CRM Admin).
*   **Traccar Tracking:** Konfigurasi koneksi server GPS satelit Traccar untuk live monitoring kendaraan armada (PIC: IT / Logistics Head).

---
_Catatan: Seluruh hak akses fitur dapat disesuaikan sewaktu-waktu di menu **Settings -> Roles & Permissions** sesuai SK penugasan resmi organisasi._
