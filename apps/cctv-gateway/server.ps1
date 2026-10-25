param([int]$port = 8080)

$PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
$listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Any, $port)

try {
    $listener.Start()
    Write-Host "[JICOS CCTV] Web Server aktif di port $port"
    Write-Host "[JICOS CCTV] Akses Smart TV: http://jidoka.local:$port (atau http://localhost:$port)"
} catch {
    Write-Host "[ERROR] Gagal membuka port ${port}: $_"
    exit 1
}

$htmlFile = Join-Path $PSScriptRoot "Layar-Pantau.html"

while ($true) {
    try {
        $client = $listener.AcceptTcpClient()
        $stream = $client.GetStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $req = $reader.ReadLine()
        
        if ($req -and (Test-Path $htmlFile)) {
            $htmlBytes = [System.IO.File]::ReadAllBytes($htmlFile)
            $header = "HTTP/1.1 200 OK`r`nContent-Type: text/html; charset=utf-8`r`nContent-Length: $($htmlBytes.Length)`r`nAccess-Control-Allow-Origin: *`r`nConnection: close`r`n`r`n"
            $headerBytes = [System.Text.Encoding]::UTF8.GetBytes($header)
            $stream.Write($headerBytes, 0, $headerBytes.Length)
            $stream.Write($htmlBytes, 0, $htmlBytes.Length)
            $stream.Flush()
        }
        $client.Close()
    } catch {
        # ignore client disconnects
    }
}
