$files = Get-ChildItem -Path "app/Http/Controllers" -Filter "*.php" -Recurse
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
        if ($content -match "ilike") {
            $newContent = $content -replace "ilike", "like"
            [System.IO.File]::WriteAllText($file.FullName, $newContent)
            Write-Host "Fixed Query: $($file.FullName)"
        }
    }
    catch {
        Write-Error "Failed to process $($file.FullName): $($_.Exception.Message)"
    }
}
