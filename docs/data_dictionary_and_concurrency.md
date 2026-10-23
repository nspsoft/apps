# 🗄️ Kamus Data & Arsitektur Konkurensi Database (Enterprise Standard)
> **Sistem**: ERP Terpadu (Manufaktur, Distribusi & Operasional)  
> **Versi**: 1.0 (Enterprise Specification)  
> **Standar Kepatuhan**: ACID Compliant, Anti-Race Condition, Soft Deletes, High-Performance Indexing  

---

## 1. Prinsip Desain Basis Data (Database Design Principles)

Untuk memastikan sistem mampu menangani ribuan transaksi harian di lantai pabrik dan kantor pusat tanpa selisih atau penurunan performa:

1. **Integritas Transaksi (ACID - Atomicity, Consistency, Isolation, Durability)**:
   - Setiap operasi yang melibatkan lebih dari satu entitas (contoh: *Penerimaan Barang GRN + Mutasi Stok + Update Saldo Gudang*) **WAJIB** berada dalam blok `DB::transaction`.
   - Rollback otomatis terjadi jika salah satu sub-kueri gagal atau melempar pengecualian (*exception*).
2. **Presisi Finansial & Kuantitas**:
   - Kolom harga, mata uang, dan kuantitas menggunakan tipe data desimal presisi: `decimal(15, 4)` atau `decimal(18, 2)` (menghindari *floating point rounding errors*).
3. **Penyimpanan Waktu Standar UTC**:
   - Seluruh kolom `created_at`, `updated_at`, dan `deleted_at` disimpan dalam zona waktu **UTC (ISO 8601)** di basis data. Konversi ke waktu lokal (`Asia/Jakarta` / WIB) hanya dieksekusi pada lapisan presentasi pengguna.
4. **Proteksi Penghapusan Fisik (Soft Deletes)**:
   - Seluruh tabel master (produk, mitra, akun) dan tabel transaksi bisnis (SO, PO, WO, Faktur) **DILARANG DIHAPUS FISIK** (*hard delete*). Wajib menggunakan `deleted_at (timestamp nullable)`.

---

## 2. Kamus Data Entitas Utama (Core Data Dictionary)

### 2.1 Master Produk & Persediaan (`products` & `product_stocks`)

#### Tabel `products` (Katalog Master SKU)
| Kolom | Tipe Data | Nullable | Kunci | Keterangan / Validasi Bisnis |
|---|---|:---:|:---:|---|
| `id` | `bigint unsigned` | NO | PK | Auto-increment identifier unik |
| `sku` | `varchar(64)` | NO | UNIQUE | Kode identifikasi produk unik (Indexed) |
| `name` | `varchar(255)` | NO | INDEX | Nama resmi produk / komponen |
| `category_id` | `bigint unsigned` | NO | FK | Terhubung ke `categories.id` (`RESTRICT`) |
| `unit_id` | `bigint unsigned` | NO | FK | Terhubung ke `units.id` (`RESTRICT`) |
| `product_type` | `enum('raw_material', 'wip', 'finished_good', 'sparepart', 'service')` | NO | INDEX | Klasifikasi akuntansi pabrikasi |
| `min_stock` | `decimal(15, 4)` | NO | - | Ambang batas peringatan stok minimum |
| `max_stock` | `decimal(15, 4)` | YES | - | Kapasitas tampung maksimum gudang |
| `cost_price` | `decimal(18, 2)` | NO | - | HPP rata-rata berjalan (Moving Average Cost) |
| `selling_price`| `decimal(18, 2)` | NO | - | Harga jual standar |
| `is_active` | `boolean` | NO | INDEX | Default `true`. Kontrol ketersediaan |
| `deleted_at` | `timestamp` | YES | INDEX | Soft delete flag |
| `created_at` | `timestamp` | NO | INDEX | Waktu registrasi produk (UTC) |
| `updated_at` | `timestamp` | NO | - | Waktu update terakhir (UTC) |

#### Tabel `product_stocks` (Saldo Stok Fisik per Gudang)
| Kolom | Tipe Data | Nullable | Kunci | Keterangan / Validasi Bisnis |
|---|---|:---:|:---:|---|
| `id` | `bigint unsigned` | NO | PK | Auto-increment identifier unik |
| `product_id` | `bigint unsigned` | NO | FK | Terhubung ke `products.id` (`RESTRICT`) |
| `warehouse_id`| `bigint unsigned`| NO | FK | Terhubung ke `warehouses.id` (`RESTRICT`) |
| `physical_qty`| `decimal(15, 4)` | NO | - | Kuantitas fisik riil di gudang |
| `reserved_qty`| `decimal(15, 4)` | NO | - | Kuantitas terikat SO belum dikirim |
| `available_qty`| `decimal(15, 4)`| NO | - | Saldo bebas (`physical_qty - reserved_qty`) |
| `created_at` | `timestamp` | NO | - | Waktu alokasi saldo pertama |
| `updated_at` | `timestamp` | NO | - | Waktu mutasi saldo terakhir |
| *Composite* | `UNIQUE (product_id, warehouse_id)` | - | UNIQUE | Mencegah baris stok ganda di gudang sama |

---

### 2.2 Transaksi Mutasi Stok (`stock_movements`)
Tabel buku besar (*ledger*) mutasi stok. Sifat tabel ini adalah **Append-Only** (tidak pernah di-update atau di-delete).

| Kolom | Tipe Data | Nullable | Kunci | Keterangan / Validasi Bisnis |
|---|---|:---:|:---:|---|
| `id` | `bigint unsigned` | NO | PK | Auto-increment sequence |
| `movement_number` | `varchar(64)` | NO | UNIQUE | Nomor bukti mutasi (Auto-generated) |
| `product_id` | `bigint unsigned` | NO | FK | Terhubung ke `products.id` |
| `warehouse_id` | `bigint unsigned` | NO | FK | Terhubung ke `warehouses.id` |
| `reference_type` | `varchar(64)` | NO | INDEX | Polymorphic ref: `GoodsReceipt`, `DeliveryOrder`, `WorkOrder`, `Adjustment` |
| `reference_id` | `bigint unsigned` | NO | INDEX | ID dokumen sumber |
| `movement_type` | `varchar(32)` | NO | INDEX | `po_receive`, `so_delivery`, `production_in`, `production_out`, `transfer`, `adjustment`, `opname`, `return` |
| `quantity` | `decimal(15, 4)` | NO | - | Nilai mutasi (+ untuk masuk, - untuk keluar) |
| `balance_before` | `decimal(15, 4)` | NO | - | Saldo sebelum mutasi terjadi |
| `balance_after` | `decimal(15, 4)` | NO | - | Saldo setelah mutasi terjadi |
| `unit_cost` | `decimal(18, 2)` | NO | - | Nilai perolehan/HPP saat transaksi |
| `user_id` | `bigint unsigned` | NO | FK | Pelaku transaksi (`users.id`) |
| `notes` | `text` | YES | - | Catatan / alasan mutasi |
| `created_at` | `timestamp` | NO | INDEX | Waktu pencatatan mutasi (UTC) |

---

### 2.3 Dokumen Pesanan Penjualan (`sales_orders` & `sales_order_items`)

#### Tabel `sales_orders`
| Kolom | Tipe Data | Nullable | Kunci | Keterangan |
|---|---|:---:|:---:|---|
| `id` | `bigint unsigned` | NO | PK | Identifier unik pesanan |
| `order_number` | `varchar(64)` | NO | UNIQUE | Format: `SO-YYYYMM-XXXX` (Indexed) |
| `customer_id` | `bigint unsigned` | NO | FK | Terhubung ke `customers.id` (`RESTRICT`) |
| `order_date` | `date` | NO | INDEX | Tanggal pesanan disepakati |
| `status` | `enum('draft', 'confirmed', 'approved', 'in_progress', 'completed', 'cancelled')` | NO | INDEX | State machine status pesanan |
| `subtotal` | `decimal(18, 2)` | NO | - | Total sebelum pajak |
| `tax_amount` | `decimal(18, 2)` | NO | - | Nilai PPN |
| `grand_total` | `decimal(18, 2)` | NO | - | Total tagihan akhir |
| `approved_by` | `bigint unsigned` | YES | FK | PIC Approver (`users.id`) |
| `approved_at` | `timestamp` | YES | - | Waktu persetujuan |
| `deleted_at` | `timestamp` | YES | INDEX | Soft delete timestamp |
| `created_at` | `timestamp` | NO | INDEX | Waktu pembuatan dokumen |

---

### 2.4 Kamus Data Entitas Operasional Tambahan

| Entitas / Tabel | Kategori | Kolom Kunci / Atribut Penting | Relasi & Konstrain |
|---|---|---|---|
| ga_vehicle_bookings | General Affair | id, user_id, ehicle_id, ooking_date, status, start_km, end_km | FK to users, ga_vehicles (RESTRICT) |
| ga_tickets | General Affair | id, 	icket_number, user_id, category, priority, status, ssigned_to | FK to users |
| ga_assets | General Affair | id, sset_code, 
ame, location_id, condition, purchase_date | UNIQUE sset_code, FK to ga_locations |
| meetings | Meeting | id, 	itle, date, 
oom, qr_code_token, i_summary, status | UNIQUE qr_code_token |
| meeting_action_items | Meeting | id, meeting_id, 	ask_description, ssigned_to, due_date, status | FK to meetings, users |
| ell_schedules | Automated Bell | id, 
ame, 	ime, days_of_week, chime_sound, is_active, category | Indexed 	ime, is_active |
| cctv_devices | Facilities | id, device_name, ip_address, 
tsp_url, location, is_active | Indexed is_active |
| helpdesk_tickets | Helpdesk | id, 	icket_number, user_id, subject, category, priority, status | UNIQUE 	icket_number, FK to users |
| job_postings | HR ATS | id, 	itle, department_id, 	ype, status, deadline | Indexed status, FK to departments |
| pplicants | HR ATS | id, job_posting_id, ull_name, email, phone, 
esume_path, status | FK to job_postings, Indexed status |

---

## 3. Strategi Pengindeksan Wajib (Mandatory Indexing Strategy)

Untuk mencegah degradasi performa pada tabel berukuran jutaan baris:

```sql
-- Indeks Komposit untuk Kueri Pelaporan & Filter Cepat
CREATE INDEX idx_stock_movements_prod_wh_created 
ON stock_movements (product_id, warehouse_id, created_at DESC);

CREATE INDEX idx_sales_orders_status_date 
ON sales_orders (status, order_date DESC);

CREATE INDEX idx_purchase_orders_status_date 
ON purchase_orders (status, order_date DESC);

CREATE INDEX idx_work_orders_status_priority 
ON work_orders (status, priority, scheduled_start_date);
```

### Aturan Indeks Standar Enterprise:
1. Kolom foreign key wajib memiliki index tunggal atau komposit.
2. Kolom `status`, `created_at`, dan `deleted_at` wajib diindeks karena menjadi klausa `WHERE` pada 95% kueri aplikasi.
3. Gunakan indeks unik (*Unique Constraint*) pada kolom dokumen bisnis (`order_number`, `invoice_number`, `sku`) untuk mencegah duplikasi data akibat *double click* atau re-submit form.

---

## 4. Arsitektur Penanganan Konkurensi & Mutasi Stok (Concurrency & Locking)

### 4.1 Permasalahan Race Condition
Pada jam sibuk pabrik, 2 operator gudang dapat memvalidasi pengiriman atau pemakaian bahan baku secara simultan pada produk yang sama, mengakibatkan:
- Saldo fisik terbaca sama di awal.
- Pengurangan terjadi secara tumpang tindih (*Overwriting update*).
- Stok gudang menjadi minus tanpa sengaja.

### 4.2 Solusi Enterprise: Pessimistic Locking (`lockForUpdate`)
Dalam setiap proses reservasi atau pengurangan kuantitas stok fisik, baris data saldo gudang **WAJIB DIKUNCI** menggunakan `lockForUpdate()` di dalam transaksi database:

```php
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientStockException;

DB::transaction(function () use ($productId, $warehouseId, $quantityToDeduct, $reference) {
    // 1. Kunci baris stok produk di gudang spesifik
    $stock = DB::table('product_stocks')
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->lockForUpdate() // KUNCI BARIS (PESSIMISTIC LOCK)
        ->first();

    if (!$stock || $stock->available_qty < $quantityToDeduct) {
        throw new InsufficientStockException("Stok tidak mencukupi untuk SKU ID: {$productId}");
    }

    $balanceBefore = $stock->physical_qty;
    $balanceAfter  = $balanceBefore - $quantityToDeduct;

    // 2. Update saldo fisik & available
    DB::table('product_stocks')
        ->where('id', $stock->id)
        ->update([
            'physical_qty'  => $balanceAfter,
            'available_qty' => $stock->available_qty - $quantityToDeduct,
            'updated_at'    => now(),
        ]);

    // 3. Catat buku besar pergerakan stok (Append-Only)
    DB::table('stock_movements')->insert([
        'movement_number' => generate_movement_number(),
        'product_id'      => $productId,
        'warehouse_id'    => $warehouseId,
        'reference_type'  => get_class($reference),
        'reference_id'    => $reference->id,
        'movement_type'   => 'so_delivery',
        'quantity'        => -$quantityToDeduct,
        'balance_before'  => $balanceBefore,
        'balance_after'   => $balanceAfter,
        'user_id'         => auth()->id(),
        'created_at'      => now(),
    ]);
});
```

---

## 5. Kebijakan Retensi & Pengarsipan Data (Data Archiving)

| Kategori Data | Retensi Data Aktif | Kebijakan Pengarsipan (Cold Storage) |
|---|---|---|
| **Buku Besar Stok (`stock_movements`)** | 2 Tahun di tabel aktif | Partisi tahunan; data > 2 tahun dipindahkan ke tabel arsip `stock_movements_archive` untuk menjaga kecepatan kueri harian. |
| **Audit Logs (`activity_log`)** | 1 Tahun di tabel aktif | Dicadangkan bulanan ke format kompresi JSONL / Cold Storage; data aktif di-purge bertahap. |
| **Master Data Terhapus (`deleted_at`)** | Permanen disimpan (*Soft Delete*) | Tidak pernah dihapus fisik demi integritas laporan audit keuangan tahunan. |
