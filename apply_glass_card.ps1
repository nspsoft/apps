$pattern = 'bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800'
$replacement = 'glass-card'

$files = Get-ChildItem -Path "resources/js/Pages" -Filter "*.vue" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        if ($content.Contains($pattern)) {
            $newContent = $content.Replace($pattern, $replacement)
            [System.IO.File]::WriteAllText($file.FullName, $newContent)
            Write-Host "Updated: $($file.FullName)"
        }
    }
    catch {
        Write-Warning "Failed to process $($file.FullName): $($_.Exception.Message)"
    }
}
