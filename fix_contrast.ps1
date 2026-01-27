$files = Get-ChildItem -Path "resources/js/Pages" -Filter "*.vue" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        $changed = $false
        
        # 1. Fix contrast on blue backgrounds (Generic)
        # Find any tag with bg-blue-600 that has text-slate-X
        $regexBlue = '(<[^>]*class="[^"]*bg-blue-600[^"]*)(text-slate-[69]00)([^"]*")'
        if ([regex]::IsMatch($content, $regexBlue)) {
            $content = [regex]::Replace($content, $regexBlue, '${1}text-white${3}')
            $changed = $true
        }

        # 2. Fix contrast on blue gradients (Generic)
        $regexGradient = '(<[^>]*class="[^"]*from-blue-600[^"]*to-blue-500[^"]*)(text-slate-[69]00)([^"]*")'
        if ([regex]::IsMatch($content, $regexGradient)) {
            $content = [regex]::Replace($content, $regexGradient, '${1}text-white${3}')
            $changed = $true
        }

        if ($changed) {
            [System.IO.File]::WriteAllText($file.FullName, $content)
            Write-Host "Fixed Contrast: $($file.FullName)"
        }
    }
    catch {
        Write-Warning "Failed: $($file.FullName): $($_.Exception.Message)"
    }
}
