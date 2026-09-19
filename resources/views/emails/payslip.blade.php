<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - PT. Jidoka Result Indonesia</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            padding: 24px;
            color: #ffffff;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.05em;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .content {
            padding: 24px;
        }
        .summary-box {
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            border-left: 4px solid #4f46e5;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            font-weight: bold;
            font-size: 15px;
            color: #0f172a;
        }
        .thp-value {
            color: #059669;
            font-weight: 800;
        }
        .footer {
            padding: 20px 24px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>PT. JIDOKA RESULT INDONESIA</h1>
            <p>Slip Gaji & Rekapitulasi Kehadiran Resmi</p>
        </div>
        <div class="content">
            <p style="font-size: 14px; margin-top: 0;">
                Yth. Bpk/Ibu <strong>{{ $employee->full_name }}</strong>,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.5;">
                Bersama ini kami lampirkan dokumen resmi Slip Gaji dan Rekapitulasi Kehadiran Anda untuk periode 
                <strong>{{ \Carbon\Carbon::parse($payroll->cutoff_start)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($payroll->cutoff_end)->format('d M Y') }}</strong>.
            </p>

            <div class="summary-box">
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;">NIK:</td>
                        <td style="padding: 4px 0; text-align: right; font-weight: 600;">{{ $employee->nik }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;">Departemen:</td>
                        <td style="padding: 4px 0; text-align: right; font-weight: 600;">{{ $employee->department->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;">Hari Kerja Masuk:</td>
                        <td style="padding: 4px 0; text-align: right; font-weight: 600;">{{ $payroll->total_working_days }} Hari</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;">Lembur:</td>
                        <td style="padding: 4px 0; text-align: right; font-weight: 600;">{{ $payroll->total_overtime_hours }} Jam ({{ $payroll->total_overtime_days }} Hari)</td>
                    </tr>
                    <tr style="border-top: 1px dashed #cbd5e1;">
                        <td style="padding: 8px 0 0 0; font-weight: bold; color: #0f172a; font-size: 14px;">Gaji Bersih (THP):</td>
                        <td style="padding: 8px 0 0 0; text-align: right; font-weight: 800; color: #059669; font-size: 15px;">
                            Rp {{ number_format($payroll->rounded_net_salary ?: $payroll->net_salary, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 12px; color: #64748b; line-height: 1.5;">
                Silakan periksa lampiran file PDF pada email ini untuk melihat rincian pendapatan, tunjangan, potongan, serta absensi harian Anda.
            </p>
            <p style="font-size: 12px; color: #64748b;">
                Jika Anda memiliki pertanyaan mengenai slip gaji ini, silakan menghubungi departemen HRD.
            </p>
        </div>
        <div class="footer">
            <p style="margin: 0;">Email ini dikirimkan secara otomatis oleh Sistem JICOS ERP PT. Jidoka Result Indonesia.</p>
            <p style="margin: 4px 0 0 0;">Kawasan Industri Jababeka I, Cikarang Utara, Bekasi, Jawa Barat</p>
        </div>
    </div>
</body>
</html>
