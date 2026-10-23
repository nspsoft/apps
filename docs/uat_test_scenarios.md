# 🧪 Matriks & Skenario Uji Keberterimaan Pengguna (UAT Test Scenario Catalog)
> **Sistem**: ERP Terpadu (Manufaktur, Distribusi & Operasional)  
> **Versi**: 1.0 (Enterprise Specification)  
> **Target**: Tim QA, Konsultan Implementator, Key Users & Project Manager  

---

## 1. Panduan & Kriteria Keberterimaan UAT (Acceptance Criteria)

Pengujian UAT (*User Acceptance Test*) wajib diselesaikan dan disahkan sebelum sistem diizinkan naik ke lingkungan produksi (*Go-Live*).
- **Tingkat Kelolosan Wajib**: 100% untuk kriteria berstatus *Critical* / *High*, dan minimal 95% untuk kriteria berstatus *Medium*.
- **Data Uji**: Menggunakan basis data *staging* yang telah diisi data industri realistis (*realistic seeder*).

---

## 2. Matriks Skenario Pengujian per Modul

### 2.1 Modul Penjualan (Sales) & Pengiriman
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-SLS-01** | Pembuatan Quotation & Konversi ke SO | 1. Input penawaran untuk Customer A.<br>2. Submit dan approve Quotation.<br>3. Klik tombol "Convert to Sales Order". | Dokumen SO terbuat otomatis dengan nomor baru, item, kuantitas, dan harga yang identik dengan Quotation asal. | Critical | PASS |
| **TC-SLS-02** | Validasi Batas Kredit Pelanggan | 1. Pilih pelanggan dengan batas kredit Rp 50jt.<br>2. Buat SO baru bernilai Rp 65jt.<br>3. Submit SO. | Sistem memunculkan dialog peringatan limit kredit terlampaui dan mengunci status dokumen ke `pending_credit_approval`. | High | PASS |
| **TC-SLS-03** | Penerbitan Surat Jalan (DO) Parsial | 1. Buat DO dari SO sejumlah 50 dari total 100 unit.<br>2. Simpan dan validasi pengiriman. | Stok fisik berkurang 50 unit, reserved stok berkurang 50 unit, status SO menjadi `in_progress` (sisa 50 unit). | Critical | PASS |
| **TC-SLS-04** | Ekstraksi PO Otomatis (AI PO Extractor) | 1. Unggah berkas pesanan PDF pelanggan.<br>2. Jalankan tombol ekstraksi AI. | Form SO otomatis terisi nama item, kuantitas, harga, dan nomor PO pelanggan tanpa eror. | Medium | PASS |

---

### 2.2 Modul Pengadaan (Purchasing) & Penerimaan
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-PUR-01** | Alur Persetujuan Bertingkat PO | 1. Buat PO bernilai > Rp 50.000.000.<br>2. Cek status persetujuan. | PO membutuhkan ACC beruntun dari Dept Head -> Purchasing Manager -> Direktur sebelum bisa dicetak PDF. | Critical | PASS |
| **TC-PUR-02** | Penerimaan Barang GRN & Mutasi Stok | 1. Buka PO aktif.<br>2. Buat Goods Receipt sejumlah barang yang tiba.<br>3. Verifikasi saldo gudang. | Saldo fisik produk di gudang penerima otomatis bertambah; kartu stok (*stock movement*) mencatat tipe `po_receive`. | Critical | PASS |
| **TC-PUR-03** | 3-Way Matching Faktur Supplier | 1. Input Purchase Invoice dari vendor.<br>2. Cocokkan kuantitas tagihan terhadap GRN dan harga terhadap PO asal. | Sistem mendeteksi jika nominal faktur lebih besar daripada kesepakatan PO dan memunculkan peringatan selisih. | High | PASS |

---

### 2.3 Modul Gudang (Inventory) & Konkurensi Mutasi
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-INV-01** | Pengujian Mutasi Konkuren (Anti-Race Condition) | 1. Dua pengguna melakukan checkout/pengiriman item yang sama secara bersamaan di mana sisa stok hanya 10 unit.<br>2. Masing-masing meminta 8 unit. | Permintaan pertama sukses (sisa 2 unit), permintaan kedua ditolak dengan pesan validasi "Stok tidak mencukupi" (tidak terjadi stok minus). | Critical | PASS |
| **TC-INV-02** | Rekonsiliasi Stock Opname | 1. Buat sesi jadwal Stock Opname.<br>2. Masukkan kuantitas hitung fisik yang berbeda dari data sistem.<br>3. Klik Complete Opname. | Sistem otomatis menghasilkan mutasi penyesuaian (*Adjustment*) surplus/defisit dan menyamakan saldo buku ke saldo fisik. | High | PASS |
| **TC-INV-03** | Ekspor/Impor Template Excel | 1. Unduh template Excel Master Produk.<br>2. Isi 50 baris data produk baru.<br>3. Upload kembali ke sistem. | Semua 50 produk terimpor sempurna tanpa merusak format data atau duplikasi SKU. | High | PASS |

---

### 2.4 Modul Pabrikasi (Manufacturing) & Quality Control
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-MFG-01** | Siklus Work Order & Konsumsi Bahan | 1. Terbitkan SPK (WO) berdasarkan BOM.<br>2. Catat pemakaian bahan (*Material Consumption*).<br>3. Laporkan hasil jadi (*Production Entry*). | Stok bahan baku berkurang otomatis (`production_in`), stok produk jadi bertambah (`production_out`), dan HPP terhitung otomatis. | Critical | PASS |
| **TC-QC-01** | Karantina & Penerbitan NCR | 1. Lakukan inspeksi QC pada GRN yang tiba.<br>2. Tandai 10 unit sebagai Cacat Dimensi (*Rejected*).<br>3. Simpan hasil inspeksi. | Terbit dokumen Non-Conformance Report (NCR) otomatis, 10 unit masuk ke Gudang Karantina dan terpisah dari stok siap pakai. | High | PASS |

---

### 2.5 Modul Keamanan, Sesi & Audit Trail
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-SEC-01** | Proteksi Akses Berdasarkan Peran (RBAC) | 1. Login menggunakan akun staf Operator Gudang.<br>2. Coba akses URL `/finance/general-ledger`. | Sistem menolak dengan halaman HTTP 403 Forbidden dan mencatat log upaya akses ilegal. | Critical | PASS |
| **TC-SEC-02** | Verifikasi Jejak Rekam (Audit Trail) | 1. Edit nominal harga atau diskon pada Sales Order.<br>2. Buka menu Admin > Activity Logs. | Tercatat detail waktu, user pelaku, nilai lama (*old values*), nilai baru (*new values*), dan alamat IP. | Critical | PASS |
| **TC-SEC-03** | Idle Session Timeout | 1. Biarkan sesi login tanpa aktivitas mouse/keyboard selama 15 menit. | Pengguna otomatis di-redirect ke halaman kunci/login untuk menjaga kerahasiaan data di area pabrik. | Medium | PASS |

---

### 2.6 Modul General Affair (GA) & Operasional Kantor
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-GA-01** | Pengajuan & Persetujuan Pinjam Mobil Dinas | 1. Ajukan booking mobil dinas Toyota Avanza.<br>2. Verifikasi approval GA Manager.<br>3. Input KM Awal dan KM Akhir saat pengembalian. | Data terverifikasi, status berpindah dari submitted -> pproved -> in_trip -> completed, dan riwayat pemakaian tercatat di histori armada. | High | PASS |
| **TC-GA-02** | Tiket Pengaduan Servis Fasilitas | 1. Buat tiket keluhan perbaikan AC ruangan.<br>2. GA menugaskan teknisi internal.<br>3. Teknisi menyelesaikan perbaikan. | Status tiket berubah dari open -> in_progress -> 
esolved, dan pelapor menerima notifikasi pembaruan. | Medium | PASS |

---

### 2.7 Modul Meeting Command & AI Notulensi (MoM)
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-MTG-01** | Presensi Digital QR-Code Peserta | 1. Tampilkan layar QR-Code rapat.<br>2. Peserta memindai QR menggunakan kamera ponsel.<br>3. Submit nama & departemen. | Kehadiran peserta tercatat secara real-time pada daftar presensi rapat tanpa perlu login akun. | High | PASS |
| **TC-MTG-02** | Ekstraksi Audio Rapat ke Notulensi AI | 1. Unggah rekaman suara rapat singkat.<br>2. Klik tombol proses AI Gemini. | AI menghasilkan Ringkasan Eksekutif, Poin Keputusan, dan Action Items yang langsung mengisi form notulensi resmi. | High | PASS |

---

### 2.8 Modul Automated Bell System (Bel Pabrik JICOS)
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-BEL-01** | Sinkronisasi Jadwal Bel ke Bell Agent | 1. Tambah jadwal bel baru jam istirahat di web ERP.<br>2. Cek respon API /api/bell-agent/schedules.<br>3. Verifikasi log aplikasi desktop Python. | Bell Agent menerima payload jadwal baru dan menyimpan cache lokal untuk persiapan pemutaran audio chime. | High | PASS |

---

### 2.9 Modul Driver Mobile & Supplier Portal
| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan (Expected Result) | Tingkat | Status |
|---|---|---|---|:---:|:---:|
| **TC-DRV-01** | Scan Barcode DO & Bukti Kirim (POD) | 1. Supir membuka antarmuka /driver di ponsel.<br>2. Pindai barcode DO.<br>3. Konfirmasi tiba, unggah foto serah-terima dan tanda tangan digital. | Status DO otomatis berpindah ke delivered, bukti foto & tanda tangan tersimpan di sistem, dan stok terkonfirmasi sampai. | Critical | PASS |
| **TC-POR-01** | Konfirmasi PO & Unggah Faktur oleh Vendor | 1. Vendor login ke Supplier Portal.<br>2. Klik *Acknowledge* pada PO yang masuk.<br>3. Unggah file PDF tagihan invoice. | Notifikasi terkirim ke bagian Purchasing pabrik, PO terkonfirmasi, dan invoice vendor siap di-review tim Finance. | Critical | PASS |

---

## 3. Lembar Pengesahan Pelaksanaan UAT

| Peran Pemangku Kepentingan | Nama PIC | Tanggal Uji | Tanda Tangan |
|---|---|:---:|:---:|
| **Lead Implementator ERP** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
| **Key User - Sales & CRM** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
| **Key User - Purchasing** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
| **Key User - Warehouse & PPIC** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
| **Key User - Finance & HR** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
| **Project Manager Klien** | ____________________ | ___ / ___ / 2026 | [ _______________ ] |
