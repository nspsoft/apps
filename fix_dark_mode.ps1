$files = Get-ChildItem -Path "resources/js/Pages" -Filter "*.vue" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        $changed = $false
        
        # 1. Hapus duplikasi dark:bg-white yang salah
        if ($content.Contains('dark:bg-white')) {
            $content = $content -replace 'dark:bg-white\s*', ''
            # Pastikan jika ada dua dark:bg berturut-turut, kita ambil yang benar
            $content = $content -replace 'dark:bg-slate-950\s+dark:bg-slate-950', 'dark:bg-slate-950'
            $changed = $true
        }

        # 2. Perbaiki hover pada tabel yang terlalu terang di mode malam
        # Cari pola hover:bg-slate-50/50 atau hover:bg-slate-50 tanpa dark:hover
        if ($content -match 'hover:bg-slate-50(?!.*dark:hover:bg)') {
            $content = $content -replace 'hover:bg-slate-50/50', 'hover:bg-slate-50/50 dark:hover:bg-slate-800/50'
            $content = $content -replace 'hover:bg-slate-50(?!\/)', 'hover:bg-slate-50 dark:hover:bg-slate-800/50'
            $changed = $true
        }

        # 3. Ganti latar belakang kartu manual dengan glass-card jika belum
        $manualCard = 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800'
        if ($content.Contains($manualCard)) {
            $content = $content.Replace($manualCard, 'glass-card')
            $changed = $true
        }

        if ($changed) {
            [System.IO.File]::WriteAllText($file.FullName, $content)
            Write-Host "Fixed Dark Mode: $($file.FullName)"
        }
    }
    catch {
        Write-Warning "Failed: $($file.FullName): $($_.Exception.Message)"
    }
}
