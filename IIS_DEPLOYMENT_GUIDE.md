# 🚀 Panduan Deployment & Update SPINDO ERP di Server IIS Windows

Dokumen ini menjelaskan alur instalasi awal (*first-time setup*), konfigurasi server, dan pembaruan (*update workflow*) aplikasi **SPINDO ERP** (Laravel 12 + Vue 3 Inertia) pada server **Windows IIS (Internet Information Services)**.

Alur ini mengadopsi standar resmi yang digunakan di lingkungan **PT. SPINDO, Tbk.** (sama seperti di **SMART-CERT** dan **SMART-7**) menggunakan installer biner otomatis berbasis **Inno Setup (`.iss` ➔ `.exe`)**.

---

## 1. 🏗️ Arsitektur Server IIS Windows

```mermaid
flowchart TD
    UserLAN["Staf / Komputer Pabrik (Jaringan LAN / Wi-Fi)"] -- "HTTP Port 80 / 8080" --> IIS["IIS Web Server (Windows)"]
    UserRemote["Akses Luar Kantor / Cabang (Opsional)"] -- "HTTPS erp.spindo.com" --> Cloudflare["Cloudflare Tunnel (cloudflared)"]
    Cloudflare -- "Forward Local Port" --> IIS

    subgraph Windows Server (PC Server Lokal)
        subgraph IIS Web Server
            IIS --> AppPool["Application Pool: ERP_Prod"]
        end

        AppPool --> FastCGI["PHP FastCGI (C:\\php\\php-cgi.exe)"]
        AppPool --> WebRoot["Physical Path: C:\\inetpub\\wwwroot\\ERP\\public"]
        
        FastCGI --> MySQL[("MySQL Database Server")]
        
        subgraph Background Services
            NSSM["NSSM Windows Service"] --> QueueWorker["php artisan queue:work"]
            TaskScheduler["Windows Task Scheduler (1 Min)"] --> CronScheduler["php artisan schedule:run"]
        end
    end
```

### Keunggulan Metode Ini:
1. **Pemasangan 1-Klik Mandiri (Inno Setup)**: Cukup *double-click* installer `.exe`, proses stop pool, overwrite file produksi, setel izin folder (`icacls`), migrasi database, dan pembersihan cache berjalan otomatis.
2. **Offline-Ready**: Folder dependensi `vendor` dan asset `public/build` sudah terkompresi di dalam file `.exe`, sehingga server **tidak membutuhkan koneksi internet atau Composer**.
3. **Perlindungan Data**: File konfigurasi `.env` produksi **TIDAK akan tertimpa** saat proses update/instalasi ulang dijalankan.

---

## 2. 📋 Prasyarat Server (Harus Disiapkan Sekali di Server)

Sebelum menjalankan file installer di komputer server baru, pastikan komponen berikut sudah terpasang:

| No | Komponen | Keterangan & Lokasi |
| :---: | :--- | :--- |
| 1 | **IIS Web Server** | Fitur Windows aktif dengan sub-komponen **CGI** tercentang. |
| 2 | **IIS URL Rewrite Module 2.1** | Wajib terpasang dari installer resmi Microsoft (`rewrite_amd64_en-US.msi`). |
| 3 | **Visual C++ Redistributable (VS16)** | Paket library C++ x64 agar `php.exe` dapat dieksekusi. |
| 4 | **PHP 8.2 / 8.3 Non-Thread Safe (NTS)** | Diekstrak di `C:\php\` dengan ekstensi aktif: `pdo_mysql`, `gd`, `zip`, `mbstring`, `fileinfo`, `openssl`, `curl`, `intl`, `bcmath`. |
| 5 | **MySQL Server 8.0+** | Berjalan aktif sebagai Windows Service (port 3306). |
| 6 | **NSSM (Non-Sucking Service Manager)** | Tool utilitas untuk menjalankan Laravel Queue Worker di latar belakang. |

---

## 3. ⚙️ Langkah Pendaftaran Website di IIS (First-Time Setup)

Langkah ini hanya dilakukan **sekali saja** saat pertama kali menyiapkan server:

### A. Buat Application Pool
1. Buka **IIS Manager** (`inetmgr`).
2. Klik kanan **Application Pools** ➔ **Add Application Pool...**:
   * **Name**: `ERP_Prod`
   * **.NET CLR Version**: `No Managed Code`
   * **Managed pipeline mode**: `Integrated`
3. Klik **OK**.

### B. Daftarkan Website
1. Klik kanan pada menu **Sites** ➔ **Add Website...**:
   * **Site name**: `SPINDO-ERP`
   * **Application pool**: Pilih `ERP_Prod`
   * **Physical path**: **`C:\inetpub\wwwroot\ERP\public`** *(Wajib mengarah ke folder `public`)*
   * **Binding Type**: `http`
   * **IP address**: `All Unassigned`
   * **Port**: `80` (atau `8080` jika port 80 telah digunakan website lain)
2. Klik **OK**.

### C. Daftarkan PHP di Handler Mappings (Jika Belum)
1. Di IIS Manager, klik nama Server (akar paling atas).
2. Buka fitur **Handler Mappings** ➔ klik **Add Module Mapping...**:
   * **Request path**: `*.php`
   * **Module**: `FastCgiModule`
   * **Executable**: `C:\php\php-cgi.exe`
   * **Name**: `PHP_via_FastCGI`
3. Klik **OK** lalu konfirmasi **Yes**.

---

## 4. 📦 Alur Pembuatan & Deployment Installer (Inno Setup)

### Langkah di PC Developer:
1. Pastikan seluruh perubahan kode sudah dites dan siap rilis.
2. Klik ganda file:
   ```cmd
   build-installer.bat
   ```
3. Script akan otomatis:
   * Memeriksa dan mem-build asset frontend (`npm run build`).
   * Mengompilasi seluruh file proyek via Inno Setup Compiler (`ISCC.exe`).
   * Menghasilkan file installer di folder:
     ```text
     C:\OutputInstaller\SPINDO_ERP_Installer_v1.0.0.exe
     ```

### Langkah di Komputer Server:
1. Salin file `SPINDO_ERP_Installer_v1.0.0.exe` ke komputer server (via Remote Desktop atau flashdisk).
2. Klik kanan file installer ➔ pilih **Run as administrator**.
3. Ikuti wizard instalasi ke folder tujuan (default: `C:\inetpub\wwwroot\ERP`).
4. **Installer secara otomatis mengeksekusi tahapan berikut:**
   - [x] Menghentikan sementara IIS AppPool `ERP_Prod` (mencegah file lock).
   - [x] Menyalin seluruh source code terbaru beserta folder `vendor` dan asset `public/build`.
   - [x] Menjaga file `.env` produksi agar tidak terhapus / tidak tertimpa.
   - [x] Menyetel izin akses Windows (`icacls`) folder `storage` & `bootstrap/cache` untuk akun `IIS_IUSRS` dan `IUSR`.
   - [x] Menjalankan `php artisan migrate --force` untuk memperbarui tabel database.
   - [x] Menjalankan `php artisan storage:link` untuk symlink penyimpanan dokumen/foto.
   - [x] Membersihkan dan menyusun ulang cache performa (`artisan optimize`).
   - [x] Menyalakan kembali IIS AppPool `ERP_Prod`.

---

## 5. 🔄 Konfigurasi Background Services & Otomasi

Agar fitur notifikasi, export dokumen, dan proses antrean data berjalan otomatis di Windows:

### A. Background Queue Worker (Menggunakan NSSM)
1. Unduh binary `nssm.exe` (x64) dan letakkan di `C:\Windows\System32\` atau `C:\php\`.
2. Buka Command Prompt (Run as Administrator), jalankan:
   ```cmd
   nssm install "ERP-Queue-Worker" "C:\php\php.exe" "C:\inetpub\wwwroot\ERP\artisan queue:work --tries=3 --timeout=120"
   nssm set "ERP-Queue-Worker" AppDirectory "C:\inetpub\wwwroot\ERP"
   nssm set "ERP-Queue-Worker" Start SERVICE_AUTO_START
   nssm start "ERP-Queue-Worker"
   ```
3. Service `ERP-Queue-Worker` kini akan otomatis berjalan di latar belakang setiap kali server menyala.

### B. Task Scheduler (Laravel Cron Scheduler)
1. Buka **Task Scheduler** di Windows Server.
2. Klik **Create Task...**:
   * **General**: Beri nama `ERP-Laravel-Schedule`, pilih opsi *"Run whether user is logged on or not"*, dan centang *"Run with highest privileges"*.
   * **Triggers**: Klik *New* ➔ Atur *Daily* ➔ Di bagian *Advanced settings*, centang *"Repeat task every: 1 minute"* dengan durasi *"Indefinitely"*.
   * **Actions**: Klik *New* ➔ *Start a program*:
     * Program/script: `C:\php\php.exe`
     * Add arguments: `artisan schedule:run`
     * Start in: `C:\inetpub\wwwroot\ERP`
3. Klik **OK** dan masukkan password administrator Windows.

---

## 6. 🌐 Remote Access via Cloudflare Tunnel (Opsional)

Jika ERP perlu diakses dari luar kantor tanpa IP Public statis:
1. Pastikan file `cloudflared.exe` tersedia di server.
2. Daftarkan service Cloudflare Tunnel dengan token akun perusahaan:
   ```cmd
   cloudflared.exe service install <TOKEN_TUNNEL_ANDA>
   ```
3. Arahkan hostname di Cloudflare Zero Trust Dashboard ke `http://localhost:80` (port IIS ERP).

---

## 7. 🛠️ Panduan Troubleshooting

| Masalah / Error | Penyebab Utama | Solusi |
| :--- | :--- | :--- |
| **HTTP Error 500.19 - Internal Server Error** | Modul IIS URL Rewrite belum terpasang di server. | Install `rewrite_amd64_en-US.msi`, lalu jalankan `iisreset`. |
| **HTTP Error 500.0 - Module FastCgiModule Error** | Fitur Windows CGI belum diaktifkan atau path PHP salah. | Buka *Turn Windows features on/off* ➔ centang *CGI*. Periksa path `php-cgi.exe` di Handler Mappings. |
| **The stream or file ".../logs/laravel.log" could not be opened in append mode: Permission denied** | Akun IIS belum memiliki izin tulis di folder storage. | Jalankan `deploy\iis-setup.bat` (Run as Administrator). |
| **404 Not Found saat klik menu / route aplikasi** | File `public/web.config` hilang atau URL Rewrite mati. | Pastikan file `web.config` ada di folder `C:\inetpub\wwwroot\ERP\public\`. |
| **Database Connection Refused (SQLSTATE[HY000])** | Service MySQL belum menyala atau setting `.env` keliru. | Buka `services.msc`, pastikan service MySQL berstatus *Running*. Cek kredensial di file `.env`. |

---
*Dokumentasi ini disiapkan untuk standar operasional deployment PT. Steel Pipe Industry of Indonesia, Tbk. (SPINDO).*
