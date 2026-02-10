#!/bin/bash
PROJECT_PATH="/www/wwwroot/jicos.jidoka.co.id"
PHP_BIN="/www/server/php/84/bin/php"

cd $PROJECT_PATH || { echo "Directory not found! ($PROJECT_PATH)"; exit 1; }

# === MODE RESET & BUILD ===
echo "--- 🔄 MULAI SINKRONISASI (Updated via Git) ---"

# 1. Reset Kode
git fetch --all
git reset --hard origin/main

# 2. Update Database (Hanya Struktur Tabel Baru)
$PHP_BIN artisan migrate --force

# 3. Build Ulang Frontend (Opsional - karena aset sudah di-push, tapi aman dijalankan)
# npm install
# npm run build

# 4. Bersihkan Cache
$PHP_BIN artisan view:clear
$PHP_BIN artisan config:clear
$PHP_BIN artisan route:clear

# 5. Fix Permission
chown -R www:www $PROJECT_PATH
chmod -R 777 $PROJECT_PATH/storage

echo "--- ✅ DEPLOY SELESAI & AMAN ---"
