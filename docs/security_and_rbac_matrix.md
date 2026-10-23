# 🛡️ Matriks Keamanan & Kontrol Akses Granular (RBAC Matrix)
> **Sistem**: ERP Terpadu (Manufaktur, Distribusi & Operasional)  
> **Versi**: 1.0 (Enterprise Specification)  
> **Standar Kepatuhan**: Zero-Trust, Granular RBAC, Audit Trail, Enkripsi Data Sensitif (UU PDP No. 27 Tahun 2022)  

---

## 1. Arsitektur Otorisasi Dua Lapis (Double-Layer Authorization)

Dalam standar enterprise, keamanan hak akses tidak boleh hanya mengandalkan penyembunyian tombol di antarmuka web (Frontend):

```mermaid
flowchart TD
    REQ["Permintaan Pengguna (Browser)"] --> UI_GUARD["Lapisan 1: Frontend Guard (Vue / Inertia)\n(Menyembunyikan menu & tombol aksi)"]
    UI_GUARD --> ROUTE["Lapisan 2: Backend Middleware (Laravel)\n(RoleMiddleware / PermissionCheck)"]
    ROUTE --> POLICY["FormRequest & Policy Authorization\n(Gate::authorize / Model Policy)"]
    POLICY --> EXEC["Eksekusi Logika Bisnis & Database"]
    
    ROUTE -.->|Jika Tidak Berhak (403)| DENY["Tolak Akses (HTTP 403 Forbidden)\nCatat Security Log"]
```

1. **Frontend Layer**: Directive `$can('sales.create')` mengatur visibilitas tombol dan menu.
2. **Backend Gateway Layer**: Seluruh rute dilindungi middleware `auth` dan `role_or_permission`.
3. **Controller/Service Layer**: Verifikasi kepemilikan data (*tenancy/branch level isolation*) dan otorisasi tahap approval dokumen via Laravel Policies.

---

## 2. Matriks Hak Akses Pengguna (Granular RBAC Matrix)

ERP mendefinisikan 6 tingkatan peran utama (*Role Archetypes*):
- **SA**: Super Admin (IT Administrator & Sistem)
- **MGR**: Department Manager / Direktur (Approver Tertinggi)
- **SPV**: Supervisor / Timbalan Operasional (Reviewer)
- **OPR**: Staff Operasional / Operator Lantai Pabrik (Data Entry)
- **FIN**: Tim Keuangan & Akuntansi (Auditor & Kasir)
- **AUD**: Internal Auditor / Viewer Tamu (Read-Only & Verifikator)

### Keterangan Hak Aksi:
- **C** = Create (Membuat baru)
- **R** = Read / View (Melihat data & laporan)
- **U** = Update (Mengubah data draf)
- **D** = Delete (Soft delete data draf)
- **A** = Approve / Reject (Menyetujui atau menolak dokumen transaksi)
- **X** = Export (Unduh data ke Excel / Cetak PDF Resmi)

| Modul & Sub-Menu | Super Admin | Manager / Approver | Supervisor | Operator / Staff | Finance / Accounting | Internal Auditor |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **1. Sales: Quotation & SO** | C, R, U, D, A, X | R, U, A, X | C, R, U, X | C, R, U (Draft) | R, X | R, X |
| **2. Sales: Delivery Order (DO)** | C, R, U, D, A, X | R, A, X | C, R, U, A, X | C, R, U | R | R, X |
| **3. Sales: Invoicing & Retur** | C, R, U, D, A, X | R, A, X | R, X | R | C, R, U, A, X | R, X |
| **4. Purchasing: PR & PO** | C, R, U, D, A, X | R, A, X | C, R, U, X | C, R, U (Draft) | R, X | R, X |
| **5. Purchasing: Goods Receipt (GRN)**| C, R, U, D, A, X | R, A, X | C, R, U, A, X | C, R, U | R | R, X |
| **6. Inventory: Master SKU & Stock** | C, R, U, D, A, X | R, A, X | C, R, U, X | R | R | R, X |
| **7. Inventory: Mutasi & Opname** | C, R, U, D, A, X | R, A, X | C, R, U, X | C, R, U (Hitung Fisik)| R | R, X |
| **8. Manufacturing: BOM Formula** | C, R, U, D, A, X | C, R, U, A, X | C, R, U, X | R | R | R, X |
| **9. Manufacturing: Work Order & SPK**| C, R, U, D, A, X | R, A, X | C, R, U, A, X | C, R, U (Lapor Hasil)| R | R, X |
| **10. QC: Inspeksi & Sertifikat COA** | C, R, U, D, A, X | R, A, X | C, R, U, A, X | C, R, U | R | R, X |
| **11. Finance: Jurnal & Buku Besar** | C, R, U, D, A, X | R, A, X | R | - | C, R, U, A, X | R, X |
| **12. HR: Master Karyawan & Slip Gaji**| C, R, U, D, A, X | R, A (Dept)| R (Dept) | R (Profil Sendiri)| C, R, U, A, X | R, X |
| **13. Employee Self-Service (ESS)** | C, R, U (Mandiri)| - | - | C, R, U (Cuti, Reimburse)| - | R |
| **14. General Affair: Aset & Fleet**| C, R, U, D, A, X | R, A, X | C, R, U, X | C, R, U (Booking) | R | R, X |
| **15. Meeting Command & AI MoM**| C, R, U, D, A, X | C, R, A, X | C, R, U | R (Peserta / QR Check-in)| R | R |
| **16. Facilities & CCTV Surveillance**| C, R, U, D, A, X | R, X | R | R (Video Wall) | - | R |
| **17. Automated Bell System** | C, R, U, D, A, X | R, A | R | R (Kiosk Terminal) | - | - |
| **18. Helpdesk & Support** | C, R, U, D, A, X | R, A | C, R, U (Support) | C, R, U (Tiket Sendiri)| R | R |
| **19. Rekrutmen & ATS** | C, R, U, D, A, X | R, A | C, R, U | - | - | R |
| **20. Driver Mobile Module** | C, R, U, D, A, X | R, A | R, U | C, R, U (Konfirmasi Kirim)| - | - |
| **21. Supplier & Partner Portal** | C, R, U, D, A, X | R, A | - | - | R | R |
| **22. Settings: Role, User & Audit** | C, R, U, D, A, X | - | - | - | - | R (Audit Log) |

---

## 3. Perlindungan & Enkripsi Data Sensitif (Data Protection & Privacy)

Sesuai regulasi kepatuhan privasi (UU PDP No. 27/2022) dan standar keamanan korporat:

### 3.1 Data Finansial & Penggajian (Payroll Data)
1. **Nominal Gaji Pokok & Tunjangan**:
   - Kolom `base_salary`, `bank_account_number`, dan `allowances` disimpan dalam bentuk terenkripsi di basis data menggunakan enkripsi AES-256-CBC bawaan Laravel (`Illuminate\Support\Facades\Crypt`).
   - Kunci enkripsi dikelola secara terisolasi melalui variabel `APP_KEY` di file `.env`.
2. **Data Biometrik & Wajah (Face ID Enrollment)**:
   - File gambar foto biometrik mentah **DILARANG** disimpan secara terbuka di folder publik web.
   - Gambar disimpan di penyimpanan privat (`storage/app/private/biometrics/`) dan hanya dapat diakses melalui URL bertanda tangan (*Signed Temporary URL*).
   - Vektor embedding wajah disimpan dalam format representasi array numerik non-reversibel.

### 3.2 Siklus Keamanan Sesi Pengguna (Session Security)
- **Idle Session Timeout**: Pengguna yang tidak aktif selama **15 menit** otomatis dialihkan ke layar kunci (*Lock Screen / Re-authentication*).
- **Throttling Proteksi Brute Force**: Percobaan login gagal maksimal **5 kali berturut-turut** akan mengunci akun selama **15 menit** dan mengirimkan peringatan keamanan ke Super Admin.
- **Single Active Session**: Mencegah akun staf digunakan bersamaan di beberapa komputer secara liar; sesi lama otomatis di-revoke saat login baru terdeteksi.

---

## 4. Arsitektur Audit Trail & Activity Log (Jejak Rekam Dokumen)

Setiap aksi kritis yang mengubah status keuangan, stok, atau dokumen bisnis **WAJIB** meninggalkan jejak audit yang tidak dapat diubah (*immutable*).

### Skema Tabel `activity_logs`
```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL, -- 'create', 'update', 'status_change', 'approve', 'reject', 'export'
    entity_type VARCHAR(128) NOT NULL, -- 'App\Models\SalesOrder', 'App\Models\StockMovement'
    entity_id BIGINT UNSIGNED NOT NULL,
    document_number VARCHAR(64) NULL, -- Misal: 'SO-202610-0012'
    old_values JSON NULL, -- Snapshot data sebelum diubah
    new_values JSON NULL, -- Snapshot data sesudah diubah
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_user (user_id, created_at),
    INDEX idx_audit_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Logika Otomatisasi Jejak Rekam:
1. Terpicu otomatis via **Eloquent Model Observers** pada event `created`, `updated`, dan `deleted`.
2. Khusus perubahan status approval (ACC/Reject), sistem wajib merekam:
   - Nama pengesah (`approved_by`).
   - Timestamp pengesahan (`approved_at`).
   - Catatan / Alasan penolakan (`rejection_reason`) jika status ditolak.
3. Tabel `activity_logs` tidak memiliki izin `UPDATE` atau `DELETE` bagi peran apa pun di aplikasi, termasuk Super Admin (hanya bisa dibaca / Read-Only).
