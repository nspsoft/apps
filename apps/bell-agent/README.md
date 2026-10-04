# 🔔 JICOS Bell Agent (Factory PA / TOA Controller)

Aplikasi desktop lokal pendamping JICOS ERP yang bertugas mengeksekusi suara bel pabrik secara presisi dan langsung mengunci output kartu suara ke **Speaker TOA** (Port Jack 3.5mm Analog / Realtek Audio) tanpa bentrok dengan suara **HDMI Smart TV** (Attendance Kiosk).

---

## ✨ Fitur Utama
1. **Hardware Audio Locking**:
   - Memilih kartu suara output secara langsung (misal: `Speakers (Realtek Audio)` untuk TOA).
   - Suara bel dijamin **100% tidak akan pernah keluar ke HDMI TV**.
2. **Sinkronisasi Jadwal Otomatis dari ERP**:
   - Mengambil jadwal bel harian dari server ERP secara berkala (`/api/v1/bell-agent`).
   - Jadwal dan file suara (MP3/WAV/Chime) di-cache secara lokal di folder `cache/`.
3. **Offline Resilient (Tahan Putus Jaringan)**:
   - Jika jaringan LAN/WiFi pabrik sempat terputus, bel tetap berbunyi tepat waktu menggunakan jadwal offline lokal.
4. **System Tray Integration**:
   - Berjalan senyap di latar belakang Windows (ikon lonceng di taskbar kanan bawah dekat jam).
   - Menu klik kanan: Buka Dashboard, Tes Bunyi Bel TOA, Force Sync, Keluar.
5. **Dukungan Berbagai Tipe Suara**:
   - **Industrial Chime**: Nada lonceng Westminster 4-nada bawaan.
   - **Custom Audio**: File audio MP3/WAV resmi perusahaan.
   - **Text-to-Speech (TTS)**: Pengumuman suara otomatis.

---

## 🚀 Cara Menjalankan di PC Client

### Opsi A: Menggunakan Python (Langsung)
1. Buka folder `apps/bell-agent/`.
2. Klik ganda file **`Start-Bell-Agent.bat`**.
3. Aplikasi akan memeriksa dependensi, menginstalnya jika belum ada, dan membuka dashboard.

### Opsi B: Membuat Paket Standalone (.EXE) untuk Didistribusikan
Jika PC Client tidak memiliki Python:
1. Di komputer ini, jalankan file **`build-exe.bat`**.
2. Setelah selesai, salin folder **`dist/JICOS-Bell-Agent/`** ke flashdisk dan pindahkan ke PC Client mana pun.
3. Di PC Client, cukup klik ganda **`JICOS-Bell-Agent.exe`**.

---

## ⚙️ Pengaturan Pertama Kali di PC Client
1. **URL Server ERP**: Masukkan IP Server ERP pabrik (misal: `http://192.168.1.50` atau `http://erp.test`).
2. **Pilih Output Audio**: Pilih kartu suara yang terhubung ke kabel TOA (contoh: `[7] Speakers (Realtek(R) Audio)`).
3. **Klik "▶️ Tes Bunyi TOA"**: Pastikan suara lonceng berbunyi di speaker TOA.
4. **Klik "🔄 Sync Jadwal"**: Jadwal bel hari ini akan langsung muncul di tabel.
5. Klik tanda silang **[X]** atau tombol **"Sembunyikan ke Tray"** agar aplikasi berjalan tenang di background.
