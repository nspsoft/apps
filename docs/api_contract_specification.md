# 🌐 Kontrak API & Standarisasi Komunikasi Data (API Contract Specification)
> **Sistem**: ERP Terpadu (Manufaktur, Distribusi & Operasional)  
> **Versi**: 1.0 (Enterprise Specification)  
> **Standar**: RESTful JSON API, Standardized Envelopes, Granular Business Error Codes  

---

## 1. Format Respon Standar (API Response Envelopes)

Seluruh endpoint API publik, integrasi pihak ketiga, dan pertukaran data internal wajib menggunakan struktur payload yang konsisten.

### 1.1 Respon Sukses Tunggal (Single Resource Success)
**HTTP Status**: `200 OK` atau `201 Created`
```json
{
  "success": true,
  "message": "Sales Order berhasil dibuat",
  "data": {
    "id": 1045,
    "order_number": "SO-202610-0105",
    "customer_name": "PT Sumber Baja Perkasa",
    "grand_total": 45000000.00,
    "status": "confirmed",
    "created_at": "2026-10-21T06:15:30Z"
  }
}
```

### 1.2 Respon Sukses Berpaginasi (Paginated Collection Success)
**HTTP Status**: `200 OK`
```json
{
  "success": true,
  "message": "Daftar produk berhasil dimuat",
  "data": [
    {
      "id": 12,
      "sku": "RM-ST-001",
      "name": "Steel Coil 2.0mm",
      "category": "Raw Material",
      "available_qty": 450.5000,
      "unit": "KG"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total_records": 1420,
    "total_pages": 71
  },
  "links": {
    "first": "/api/v1/products?page=1",
    "last": "/api/v1/products?page=71",
    "prev": null,
    "next": "/api/v1/products?page=2"
  }
}
```

### 1.3 Respon Validasi Gagal (Client Validation Error)
**HTTP Status**: `422 Unprocessable Content`
```json
{
  "success": false,
  "error_code": "ERR_VALIDATION_FAILED",
  "message": "Validasi formulir tidak lolos",
  "errors": {
    "customer_id": [
      "Pelanggan yang dipilih tidak valid atau sudah nonaktif"
    ],
    "items.0.quantity": [
      "Kuantitas barang minimal 1 unit"
    ]
  }
}
```

### 1.4 Respon Kesalahan Bisnis Terstandar (Business Exception Error)
**HTTP Status**: `400 Bad Request` atau `409 Conflict`
```json
{
  "success": false,
  "error_code": "ERR_INV_INSUFFICIENT_STOCK",
  "message": "Transaksi ditolak: Saldo fisik produk tidak mencukupi di gudang yang dipilih",
  "details": {
    "sku": "RM-ST-001",
    "warehouse": "Gudang Utama Raw Material",
    "requested_qty": 500.0000,
    "available_qty": 450.5000
  }
}
```

---

## 2. Katalog Kode Kesalahan Bisnis (Business Error Code Catalog)

Untuk mempermudah integrasi sistem frontend dan aplikasi pihak ketiga, setiap kondisi penolakan bisnis memiliki kode khusus:

| Kode Kesalahan | HTTP Status | Kondisi Pemicu |
|---|:---:|---|
| `ERR_AUTH_UNAUTHENTICATED` | 401 | Token sesi tidak valid, kadaluarsa, atau tidak disertakan |
| `ERR_AUTH_FORBIDDEN` | 403 | Peran pengguna tidak memiliki hak akses (*RBAC check failed*) |
| `ERR_DOC_CREDIT_LIMIT` | 409 | Nilai pesanan melampaui batas plafon kredit pelanggan |
| `ERR_INV_INSUFFICIENT_STOCK` | 409 | Stok gudang tidak mencukupi untuk reservasi atau pengiriman |
| `ERR_INV_LOCKED_FOR_OPNAME` | 423 | Gudang sedang dalam status beku untuk proses *Stock Opname* |
| `ERR_WORKFLOW_INVALID_TRANSITION` | 400 | Transisi status tidak sah (contoh: dari *Draft* langsung loncat ke *Completed*) |
| `ERR_MFG_BOM_INACTIVE` | 400 | Resep produksi (BOM) belum berstatus aktif (*Active*) |
| `ERR_3WAY_MATCH_MISMATCH` | 422 | Kuantitas atau harga faktur supplier tidak sesuai dengan PO dan GRN |

---

## 3. Konvensi Parameter URL & Kueri (Query Parameters Convention)

Setiap endpoint bertipe daftar data (*list/index*) mendukung parameter standar berikut:

| Parameter | Tipe Data | Contoh | Fungsi |
|---|---|---|---|
| `page` | Integer | `?page=2` | Menentukan nomor halaman paginasi (default: 1) |
| `per_page` | Integer | `?per_page=50` | Jumlah data per halaman (pilihan: 10, 20, 50, 100) |
| `search` | String | `?search=Coil+Baja` | Pencarian teks bebas pada kolom-kolom ber-indeks |
| `sort_by` | String | `?sort_by=created_at` | Nama kolom acuan pengurutan |
| `sort_order`| String | `?sort_order=desc` | Arah urutan (`asc` atau `desc`, default: `desc`) |
| `date_from` | ISO Date | `?date_from=2026-10-01` | Filter rentang tanggal awal dokumen |
| `date_to` | ISO Date | `?date_to=2026-10-31` | Filter rentang tanggal akhir dokumen |
| `status` | String | `?status=approved` | Filter berdasarkan status dokumen |

---

## 4. Kontrak Integrasi Eksternal (External Webhooks & Services)

### 4.1 Webhook Penerimaan Dokumen WhatsApp (Wablas / Fonnte)
- **Metode**: `POST /api/v1/webhooks/whatsapp/incoming`
- **Keamanan**: Dilindungi verifikasi *HMAC SHA256 Signature Header* (`X-Signature`).
- **Tindak Lanjut**: Pesan masuk didorong ke `WhatsappMessageReceivedJob` untuk diproses oleh agen AI pembuat draft dokumen (*AI PO Extractor*).

### 4.2 Endpoint Health Check
- **Metode**: `GET /api/health`
- **Akses**: Terbuka untuk monitoring server (Uptime Monitoring / Container Orchestrator).
