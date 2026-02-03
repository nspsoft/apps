---
description: Prosedur Wajib Update Frontend (Vue/JS)
---

// turbo-all
# Prosedur Update Frontend

Setiap kali melakukan perubahan pada file di dalam folder `resources/js/`, ikuti langkah-langkah berikut tanpa pengecualian:

1. Modifikasi file `.vue` atau `.js` sesuai kebutuhan.
2. **WAJIB:** Jalankan perintah build untuk sinkronisasi aset:
   ```bash
   npm run build
   ```
3. **WAJIB:** Lakukan Commit & Push ke GitHub secara otomatis:
   ```bash
   git add .
   git commit -m "Deskripsi perubahan"
   git push origin main
   ```
4. Verifikasi bahwa build dan push berhasil tanpa error.
5. Baru laporkan hasil ke USER agar USER tinggal mengeksekusi Cron Job di aaPanel.

PENTING: Jangan pernah melaporkan "Selesai" jika `git push` belum dilakukan, karena server aaPanel menarik data dari GitHub.
