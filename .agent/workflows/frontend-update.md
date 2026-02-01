---
description: Prosedur Wajib Update Frontend (Vue/JS)
---

// turbo-all
# Prosedur Update Frontend

Setiap kali melakukan perubahan pada file di dalam folder `resources/js/`, ikuti langkah-langkah berikut tanpa pengecualian:

1. Modifikasi file `.vue` atau `.js` sesuai kebutuhan.
2. **WAJIB:** Jalankan perintah build untuk sinkronisasi aset ke server:
   ```bash
   npm run build
   ```
3. Verifikasi bahwa build berhasil tanpa error.
4. Baru laporkan hasil ke USER.

PENTING: Jangan pernah melaporkan "Selesai" jika `npm run build` belum dilakukan, karena USER tidak akan melihat perubahannya di browser.
