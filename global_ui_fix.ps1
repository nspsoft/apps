$files = Get-ChildItem -Path "resources/js/Pages" -Filter "*.vue" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        $changed = $false
        
        # 1. Fix dark:bg-slate-50 (too bright for dark mode)
        if ($content.Contains('dark:bg-slate-50')) {
            $content = $content -replace 'dark:bg-slate-50(?!/)', 'dark:bg-slate-900'
            $changed = $true
        }

        # 2. Fix duplicate/broken background classes
        if ($content.Contains('dark:bg-slate-50 dark:bg-slate-800')) {
            $content = $content -replace 'dark:bg-slate-50 dark:bg-slate-800', 'dark:bg-slate-800'
            $changed = $true
        }

        # 3. Fix misplaced dark backgrounds (missing dark: prefix)
        $regex2 = '(?<!dark:)(bg-slate-(900|950))'
        if ([regex]::IsMatch($content, $regex2)) {
            $content = [regex]::Replace($content, $regex2, 'bg-white dark:$1')
            $changed = $true
        }

        # 4. Fix contrast on blue buttons
        $blueButtonPatterns = @(
            'bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-slate-900',
            'bg-blue-600 py-2.5 text-sm font-semibold text-slate-900'
        )
        
        foreach ($p in $blueButtonPatterns) {
            if ($content.Contains($p)) {
                $content = $content.Replace($p, $p.Replace('text-slate-900', 'text-white'))
                $changed = $true
            }
        }

        if ($changed) {
            [System.IO.File]::WriteAllText($file.FullName, $content)
            Write-Host "Fixed: $($file.FullName)"
        }
    }
    catch {
        Write-Warning "Failed: $($file.FullName): $($_.Exception.Message)"
    }
}
