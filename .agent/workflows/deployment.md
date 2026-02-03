---
description: Prosedur Umum Deployment (Setiap Perubahan)
---

// turbo-all
# Prosedur Umum Update & Deployment

Sesuai permintaan USER, setiap kali melakukan perubahan pada codebase (baik Backend PHP maupun Frontend JS/Vue), ikuti langkah-langkah berikut:

1. **Lakukan Perubahan**: Selesaikan modifikasi file.
2. **Build (Jika Frontend)**: Jalankan `npm run build` jika ada perubahan pada folder `resources/js/`.
3. **Commit & Push Otomatis**:
   ```bash
   git add .
   git commit -m "Deskripsi perubahan yang jelas"
   git push origin main
   ```
4. **Verifikasi**: Pastikan tidak ada error pada proses build maupun push.
5. **Laporkan ke USER**: Beritahu USER bahwa perubahan sudah tersedia di GitHub dan siap di-update via aaPanel Cron Job.

**PENTING**: USER mengandalkan push otomatis ini agar mereka tinggal mengklik tombol Execute pada Cron Job di aaPanel. Jangan pernah melewatkan langkah `git push`.
