; =====================================================================
; SPINDO ERP Production Installer Script (Inno Setup)
; =====================================================================
; Script ini digunakan untuk mengompilasi seluruh source code aplikasi
; SPINDO ERP menjadi satu file installer otomatis (.EXE) untuk server IIS.
; Standar sesuai arsitektur deployment SMART-CERT & SMART-7 SPINDO.
; =====================================================================

[Setup]
AppName=SPINDO ERP System
AppVersion=1.0.0
AppPublisher=PT. SPINDO, Tbk.
AppPublisherURL=https://www.spindo.com
AppSupportURL=https://www.spindo.com
DefaultDirName=C:\inetpub\wwwroot\ERP
DisableDirPage=no
AppendDefaultDirName=no
OutputDir=C:\OutputInstaller
OutputBaseFilename=SPINDO_ERP_Installer_v1.0.0
Compression=lzma2/max
SolidCompression=yes
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64compatible
VersionInfoVersion=1.0.0.0
VersionInfoCompany=PT. SPINDO, Tbk.
VersionInfoDescription=SPINDO Integrated ERP Application Installer
VersionInfoTextVersion=1.0.0
VersionInfoCopyright=Copyright (C) 2026 PT. Steel Pipe Industry of Indonesia, Tbk.
VersionInfoProductName=SPINDO ERP
VersionInfoProductVersion=1.0.0

[Files]
; Salin source code produksi (vendor disertakan agar server tidak perlu koneksi internet / composer)
Source: "*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs overwritereadonly; Excludes: "\.git\*,\node_modules\*,\scratch\*,\.env,\storage\logs\*,bootstrap\cache\*.php,\storage\framework\cache\data\*,\storage\framework\sessions\*,\storage\framework\views\*.php,\.phpunit.result.cache,\*.log,\check_*.php,\debug_*.php,\test-*.php,\seed_*.php,\cloudflared.exe,\start-tunnel.bat,\build-output.log,\storage\app\private\backups\*,\storage\app\private\temp-imports\*"

; Salin .env.example menjadi .env jika belum ada di server target
Source: ".env.example"; DestDir: "{app}"; DestName: ".env"; Flags: onlyifdoesntexist

[Dirs]
Name: "{app}\storage\app\public"
Name: "{app}\storage\framework\cache\data"
Name: "{app}\storage\framework\sessions"
Name: "{app}\storage\framework\views"
Name: "{app}\storage\logs"
Name: "{app}\bootstrap\cache"

[Run]
; 1. Berikan izin akses tulis ke akun IIS (IIS_IUSRS, IUSR) untuk storage & cache
Filename: "icacls.exe"; Parameters: """{app}\storage"" /grant ""IIS_IUSRS"":(OI)(CI)M /grant ""IUSR"":(OI)(CI)M /grant ""Everyone"":(OI)(CI)M /T /Q"; Flags: runhidden; StatusMsg: "Menyetel izin direktori storage..."
Filename: "icacls.exe"; Parameters: """{app}\bootstrap\cache"" /grant ""IIS_IUSRS"":(OI)(CI)M /grant ""IUSR"":(OI)(CI)M /grant ""Everyone"":(OI)(CI)M /T /Q"; Flags: runhidden; StatusMsg: "Menyetel izin direktori cache..."

; 2. Jalankan konfigurasi awal Laravel via Artisan
Filename: "{code:GetPhpExe}"; Parameters: """{app}\artisan"" key:generate --force"; Flags: runhidden; StatusMsg: "Memeriksa Application Key..."
Filename: "{code:GetPhpExe}"; Parameters: """{app}\artisan"" storage:link"; Flags: runhidden; StatusMsg: "Membuat symbolic link storage..."
Filename: "{code:GetPhpExe}"; Parameters: """{app}\artisan"" migrate --force"; Flags: runhidden; StatusMsg: "Menjalankan migrasi database..."
Filename: "{code:GetPhpExe}"; Parameters: """{app}\artisan"" optimize:clear"; Flags: runhidden; StatusMsg: "Membersihkan cache sistem..."
Filename: "{code:GetPhpExe}"; Parameters: """{app}\artisan"" optimize"; Flags: runhidden; StatusMsg: "Membangun optimasi cache produksi..."

[Code]
// Deteksi lokasi php.exe di berbagai kemungkinan direktori server
function GetPhpExe(Param: String): String;
begin
  if FileExists('C:\php\php.exe') then
    Result := 'C:\php\php.exe'
  else if FileExists('C:\php85\php.exe') then
    Result := 'C:\php85\php.exe'
  else if FileExists('C:\php82\php.exe') then
    Result := 'C:\php82\php.exe'
  else if FileExists('C:\laragon\bin\php\php-8.5.0-nts-Win32-vs17-x64\php.exe') then
    Result := 'C:\laragon\bin\php\php-8.5.0-nts-Win32-vs17-x64\php.exe'
  else
    Result := 'php.exe';
end;

function PrepareToInstall(var NeedsRestart: Boolean): String;
var
  ResultCode: Integer;
begin
  // Hentikan sementara IIS Application Pool ERP_Prod agar file DLL/PHP tidak locked saat ditimpa
  Exec('powershell.exe', '-Command "if (Get-Command Stop-WebAppPool -ErrorAction SilentlyContinue) { try { Stop-WebAppPool -Name ''ERP_Prod'' } catch {} }"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Result := '';
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
begin
  // Setelah instalasi selesai, nyalakan kembali IIS Application Pool ERP_Prod
  if CurStep = ssPostInstall then
  begin
    Exec('powershell.exe', '-Command "if (Get-Command Start-WebAppPool -ErrorAction SilentlyContinue) { try { Start-WebAppPool -Name ''ERP_Prod'' } catch {} }"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  end;
end;
