$files = Get-ChildItem -Path "resources/js/Pages" -Filter "*.vue" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        $changed = $false
        
        # 1. Hapus dark:bg-white (ini adalah kesalahan yang memaksa latar putih di mode malam)
        if ($content -match "dark:bg-white") {
            $content = $content -replace "dark:bg-white", ""
            $changed = $true
        }
        
        # 2. Perbaiki hover baris tabel yang terlalu terang di mode malam
        # Cari baris yang punya hover:bg-slate-50 tapi belum punya dark:hover
        if ($content -match "hover:bg-slate-50" -and $content -notmatch "dark:hover:bg-slate-800") {
            $content = $content -replace "hover:bg-slate-50", "hover:bg-slate-50 dark:hover:bg-slate-800/50"
            $changed = $true
        }
        if ($content -match "hover:bg-gray-50" -and $content -notmatch "dark:hover:bg-slate-800") {
            $content = $content -replace "hover:bg-gray-50", "hover:bg-gray-50 dark:hover:bg-slate-800/50"
            $changed = $true
        }

        # 3. Pastikan input/select tidak tumpang tindih warna di mode malam
        # Jika ada bg-slate-50 dan dark:bg-slate-800 (sering terjadi), kita biarkan, tapi pastikan kontras teks.
        
        if ($changed) {
            [System.IO.File]::WriteAllText($file.FullName, $content)
            Write-Host "Fixed UI: $($file.FullName)"
        }
    }
    catch {
        Write-Error "Failed to process $($file.FullName): $($_.Exception.Message)"
    }
}
