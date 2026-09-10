<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji & Kehadiran - {{ $payroll->employee->full_name }} - {{ \Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F') }} {{ $payroll->period_year }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.6cm 0.6cm 0.6cm 0.6cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            width: 100%;
        }
        .page-col {
            width: 50%;
            border: 1.5pt solid #000;
            padding: 10px 12px;
            background-color: #fff;
            position: relative;
        }

        /* Typography & Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }

        /* Left Side: Data Kehadiran */
        .attendance-title {
            font-size: 14pt;
            font-weight: 900;
            text-align: center;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-bottom: 2pt solid #000;
            padding-bottom: 4px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 8pt;
            font-weight: bold;
        }
        .meta-table td {
            padding: 1.5px 0;
        }

        .att-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            table-layout: fixed;
        }
        .att-table th {
            border: 1pt solid #000;
            padding: 3px 2px;
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .att-table td {
            border: 0.5pt solid #555;
            padding: 2.5px 2px;
            vertical-align: middle;
        }
        .att-table tr.total-row td {
            border-top: 1.5pt solid #000;
            border-bottom: 1.5pt solid #000;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        /* Right Side: Slip Gaji */
        .header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1.5pt solid #000;
            padding-bottom: 6px;
        }
        .company-logo {
            height: 38px;
            width: auto;
            max-width: 85px;
            object-fit: contain;
        }
        .company-info {
            text-align: right;
            line-height: 1.15;
        }
        .company-name {
            font-size: 11pt;
            font-weight: 900;
            color: #000;
            margin: 0;
        }
        .company-address {
            font-size: 5.5pt;
            color: #222;
        }

        .payslip-title {
            text-align: center;
            font-size: 11pt;
            font-weight: 900;
            text-decoration: underline;
            letter-spacing: 1px;
            margin: 6px 0 8px 0;
        }

        .info-grid-box {
            border: 1pt solid #000;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-size: 7.5pt;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 1.5px 2px;
            vertical-align: top;
        }

        .salary-breakdown {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .salary-col {
            border: 1pt solid #000;
            padding: 6px 8px;
            font-size: 7.5pt;
        }
        .salary-col-left {
            width: 58%;
        }
        .salary-col-right {
            width: 42%;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .salary-table td {
            padding: 2px 0;
            vertical-align: middle;
        }
        .col-title {
            font-size: 8pt;
            font-weight: 900;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .row-divider {
            border-top: 1pt solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }

        .netto-box {
            border: 1pt solid #000;
            padding: 6px 12px;
            margin-bottom: 12px;
        }
        .netto-table {
            width: 100%;
            border-collapse: collapse;
        }
        .netto-table td {
            padding: 2px 0;
        }

        .signature-box {
            margin-top: 15px;
            font-size: 7.5pt;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: transparent; }
            .container { gap: 8px; }
            .page-col { padding: 8px 10px; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; padding: 12px; background: #f4f6f8; border-bottom: 1px solid #ddd; margin-bottom: 12px;">
        <span style="font-weight: bold; margin-right: 15px; font-size: 9pt;">Format Cetak Slip Gaji & Rekap Kehadiran (PT. Jidoka Result Indonesia)</span>
        <button onclick="window.print()" style="padding: 7px 20px; background: #4F46E5; color: white; border: none; cursor: pointer; border-radius: 6px; font-weight: bold; font-size: 8pt; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            CETAK / PRINT SLIP (A4 Landscape)
        </button>
    </div>

    @php
        $pricePerHour = $payroll->hourly_rate > 0 ? $payroll->hourly_rate : ($payroll->employee->hourly_rate > 0 ? $payroll->employee->hourly_rate : round($payroll->basic_salary / 173, 2));
        
        // Extract components from payroll items
        $tMakan = $payroll->items->filter(fn($i) => str_contains(strtolower($i->name), 'makan') && !str_contains(strtolower($i->name), 'lembur'))->first()?->amount ?? ($payroll->total_working_days * 12500);
        $tLembur = $payroll->items->filter(fn($i) => str_contains(strtolower($i->name), 't.lembur') || str_contains(strtolower($i->name), 'overtime'))->first()?->amount ?? ($payroll->total_overtime_hours * $pricePerHour);
        $tMakanLembur = $payroll->items->filter(fn($i) => str_contains(strtolower($i->name), 'makan lembur'))->first()?->amount ?? ($payroll->total_overtime_days * 12500);
        
        $tBpjstk = $payroll->items->where('type', 'allowance')->filter(fn($i) => str_contains(strtoupper($i->name), 'BPJSTK'))->first()?->amount ?? 0;
        $tBpjskes = $payroll->items->where('type', 'allowance')->filter(fn($i) => str_contains(strtoupper($i->name), 'BPJSKES'))->first()?->amount ?? 0;

        $potBpjstk = $payroll->items->where('type', 'deduction')->filter(fn($i) => str_contains(strtoupper($i->name), 'BPJSTK'))->first()?->amount ?? 0;
        $potBpjskes = $payroll->items->where('type', 'deduction')->filter(fn($i) => str_contains(strtoupper($i->name), 'BPJSKES'))->first()?->amount ?? 0;

        $gajiBruto = $payroll->basic_salary + $payroll->total_allowances;
        $totalPotongan = $payroll->total_deductions;
        $gajiNetto = $payroll->net_salary;
        $dibayar = $payroll->rounded_net_salary ?: ceil($gajiNetto / 100) * 100;
    @endphp

    <div class="container">
        <!-- ==================== LEMBAR KIRI: DATA KEHADIRAN KARYAWAN ==================== -->
        <div class="page-col">
            <div class="attendance-title">DATA KEHADIRAN KARYAWAN</div>

            <table class="meta-table">
                <tr>
                    <td width="70">NAMA</td>
                    <td width="10">:</td>
                    <td>{{ strtoupper($payroll->employee->full_name) }}</td>
                </tr>
                <tr>
                    <td>PERIODE</td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F') }} {{ $payroll->period_year }}</td>
                </tr>
                <tr>
                    <td>BAGIAN</td>
                    <td>:</td>
                    <td>{{ strtoupper($payroll->employee->section ?? ($payroll->employee->department->name ?? 'GENERAL')) }}</td>
                </tr>
            </table>

            <table class="att-table">
                <thead>
                    <tr>
                        <th width="16">No</th>
                        <th width="32">Date</th>
                        <th width="28">Masuk</th>
                        <th width="28">Keluar</th>
                        <th width="42">Price/Hour</th>
                        <th width="24">Jam Kerja</th>
                        <th width="24">Jam Lembur</th>
                        <th width="48">Amount Gaji Pokok</th>
                        <th width="44">Amount T. Lembur</th>
                        <th width="38">T. Makan</th>
                        <th width="38">T. Makan Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rowNo = 1;
                        $sumJamKerja = 0;
                        $sumJamLembur = 0;
                        $sumAmountGP = 0;
                        $sumAmountLembur = 0;
                        $sumTMakan = 0;
                        $sumTMakanLembur = 0;
                    @endphp

                    @foreach($periodDates as $date)
                        @php
                            $dateStr = $date->toDateString();
                            $att = $attendances->get($dateStr);
                            $ot = $overtimeRequests->get($dateStr);

                            $clockInStr = $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-';
                            $clockOutStr = $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-';

                            // Determine working hours
                            $isWorking = false;
                            $dailyJamKerja = 0.0;
                            if ($att && (in_array($att->status, ['present', 'late']) || !empty($att->clock_in))) {
                                $isWorking = true;
                                $dailyJamKerja = 8.0;
                            }

                            // Determine overtime hours
                            $dailyJamLembur = 0.0;
                            if ($ot && $ot->approved_minutes > 0) {
                                $dailyJamLembur = round($ot->approved_minutes / 60, 1);
                            } elseif ($att && $att->overtime_minutes > 0) {
                                $dailyJamLembur = round($att->overtime_minutes / 60, 1);
                            }

                            $amountGP = $dailyJamKerja * $pricePerHour;
                            $amountLembur = $dailyJamLembur * $pricePerHour;
                            $dailyTMakan = $isWorking ? 12500 : 0;
                            $dailyTMakanLembur = $dailyJamLembur > 0 ? 12500 : 0;

                            // Accumulate
                            $sumJamKerja += $dailyJamKerja;
                            $sumJamLembur += $dailyJamLembur;
                            $sumAmountGP += $amountGP;
                            $sumAmountLembur += $amountLembur;
                            $sumTMakan += $dailyTMakan;
                            $sumTMakanLembur += $dailyTMakanLembur;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $rowNo++ }}</td>
                            <td class="text-center font-bold">{{ $date->format('d-M') }}</td>
                            <td class="text-center">{{ $clockInStr }}</td>
                            <td class="text-center">{{ $clockOutStr }}</td>
                            <td class="text-right">Rp {{ number_format($pricePerHour, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $dailyJamKerja > 0 ? number_format($dailyJamKerja, 1, ',', '.') : '-' }}</td>
                            <td class="text-center">{{ $dailyJamLembur > 0 ? number_format($dailyJamLembur, 1, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $amountGP > 0 ? 'Rp ' . number_format($amountGP, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $amountLembur > 0 ? 'Rp ' . number_format($amountLembur, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $dailyTMakan > 0 ? 'Rp ' . number_format($dailyTMakan, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $dailyTMakanLembur > 0 ? 'Rp ' . number_format($dailyTMakanLembur, 0, ',', '.') : '0' }}</td>
                        </tr>
                    @endforeach

                    <!-- Total Row -->
                    <tr class="total-row">
                        <td colspan="5" class="text-center font-bold">Total</td>
                        <td class="text-center font-bold">{{ number_format($sumJamKerja, 1, ',', '.') }}</td>
                        <td class="text-center font-bold">{{ number_format($sumJamLembur, 1, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($sumAmountGP, 0, ',', '.') }},0</td>
                        <td class="text-right font-bold">{{ number_format($sumAmountLembur, 0, ',', '.') }},0</td>
                        <td class="text-right font-bold">{{ number_format($sumTMakan, 0, ',', '.') }},0</td>
                        <td class="text-right font-bold">{{ number_format($sumTMakanLembur, 0, ',', '.') }},0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ==================== LEMBAR KANAN: SLIP GAJI KARYAWAN ==================== -->
        <div class="page-col">
            <!-- Kop Perusahaan -->
            <div class="header-box">
                <div>
                    <img src="{{ asset('images/jri-official-logo.png') }}" alt="Jidoka Logo" class="company-logo" onerror="this.src='/images/jicos_logo.png'">
                </div>
                <div class="company-info">
                    <div class="company-name">PT. JIDOKA RESULT INDONESIA</div>
                    <div class="company-address">
                        Kawasan Industri JABABEKA I<br>
                        Jl. JABABEKA II Blok C No. 19L<br>
                        Pasir Gombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                        Telp. 021 89383915, Fax. : 021 -<br>
                        E_mail : jidoka.pt@yahoo.com
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div class="payslip-title">SLIP GAJI KARYAWAN</div>

            <!-- Employee Info Table -->
            <div class="info-grid-box">
                <table class="info-table">
                    <tr>
                        <td width="60">Nama</td>
                        <td width="10">:</td>
                        <td width="160" class="font-bold">{{ strtoupper($payroll->employee->full_name) }}</td>
                        <td width="60">Bulan</td>
                        <td width="10">:</td>
                        <td>{{ \Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F') }} {{ $payroll->period_year }}</td>
                    </tr>
                    <tr>
                        <td>NIK</td>
                        <td>:</td>
                        <td>{{ $payroll->employee->nik }}</td>
                        <td>Golongan</td>
                        <td>:</td>
                        <td>{{ $payroll->employee->golongan ?? 'I' }}</td>
                    </tr>
                    <tr>
                        <td>Dept.</td>
                        <td>:</td>
                        <td>{{ $payroll->employee->department->name ?? '-' }}</td>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td>{{ $payroll->employee->position->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Secondary Info Box (Shift & Jam Kerja) -->
            <div class="info-grid-box" style="margin-top: -4px;">
                <table class="info-table">
                    <tr>
                        <td width="60">Status</td>
                        <td width="10">:</td>
                        <td width="160">{{ $payroll->employee->tax_status ?? '1' }}</td>
                        <td width="70">Jam Lembur</td>
                        <td width="10">:</td>
                        <td class="font-bold">{{ number_format($payroll->total_overtime_hours, 1, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Shift II</td>
                        <td>:</td>
                        <td>-</td>
                        <td>Hari Kerja</td>
                        <td>:</td>
                        <td class="font-bold">{{ $payroll->total_working_days }}</td>
                    </tr>
                    <tr>
                        <td>Shift III</td>
                        <td>:</td>
                        <td>-</td>
                        <td colspan="3"></td>
                    </tr>
                </table>
            </div>

            <!-- Two Column Salary Table (Pendapatan vs Potongan) -->
            <div class="salary-breakdown">
                <!-- Pendapatan -->
                <div class="salary-col salary-col-left">
                    <div class="col-title">PENDAPATAN</div>
                    <table class="salary-table">
                        <tr>
                            <td>Gaji Pokok</td>
                            <td width="10">:</td>
                            <td class="text-right">{{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>T.Makan @12.5K/Hari</td>
                            <td>:</td>
                            <td class="text-right">{{ number_format($tMakan, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>T.Lembur</td>
                            <td>:</td>
                            <td class="text-right">{{ number_format($tLembur, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>T. Makan Lembur</td>
                            <td>:</td>
                            <td class="text-right">{{ number_format($tMakanLembur, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>T. Shift Malam 10K/hari</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>THR</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>Bonus</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>T. BPJSTK</td>
                            <td>:</td>
                            <td class="text-right">{{ $tBpjstk > 0 ? number_format($tBpjstk, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <td>T. BPJSKes</td>
                            <td>:</td>
                            <td class="text-right">{{ $tBpjskes > 0 ? number_format($tBpjskes, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <td>Tech. Allowance</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>Skill Allowance</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr class="row-divider">
                            <td class="font-bold">Gaji Bruto</td>
                            <td class="font-bold">:</td>
                            <td class="text-right font-bold">{{ number_format($gajiBruto, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Potongan -->
                <div class="salary-col salary-col-right">
                    <div class="col-title">POTONGAN</div>
                    <table class="salary-table">
                        <tr>
                            <td>PPh21</td>
                            <td width="10">:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>BPJSTK</td>
                            <td>:</td>
                            <td class="text-right">{{ $potBpjstk > 0 ? '-' . number_format($potBpjstk, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <td>BPJSKes</td>
                            <td>:</td>
                            <td class="text-right">{{ $potBpjskes > 0 ? '-' . number_format($potBpjskes, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <td>Lain - lain</td>
                            <td>:</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="height: 110px;"></td>
                        </tr>
                        <tr class="row-divider">
                            <td class="font-bold">Total</td>
                            <td class="font-bold">:</td>
                            <td class="text-right font-bold">{{ $totalPotongan > 0 ? '-' . number_format($totalPotongan, 0, ',', '.') : '0' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Netto & Dibayar Box -->
            <div class="netto-box">
                <table class="netto-table">
                    <tr>
                        <td width="60%"></td>
                        <td width="70" class="font-bold">Gaji Netto</td>
                        <td width="10">:</td>
                        <td class="text-right font-bold">{{ number_format($gajiNetto, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="font-bold">Dibayar</td>
                        <td>:</td>
                        <td class="text-right font-black" style="font-size: 13pt; letter-spacing: 0.5px;">
                            {{ number_format($dibayar, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Signatures Section -->
            <div class="signature-box">
                <table width="100%">
                    <tr>
                        <td width="60%" style="vertical-align: top;">
                            <div style="margin-bottom: 45px;">Diterima</div>
                            <div class="font-bold">{{ strtoupper($payroll->employee->full_name) }}</div>
                        </td>
                        <td width="40%" style="text-align: right; vertical-align: top;">
                            <div style="font-size: 6pt; color: #888; font-style: italic; margin-top: 50px;">
                                Cetak otomatis Sistem ERP Jidoka<br>
                                {{ date('d/m/Y H:i') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
