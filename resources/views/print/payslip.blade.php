<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji & Kehadiran - {{ $payroll->employee->full_name }} - {{ \Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F') }} {{ $payroll->period_year }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.3cm 0.8cm 0.3cm 0.8cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            color: #1e293b;
            margin: 0;
            padding: 8px 30px;
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Container Layout */
        .container {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            width: 100%;
            align-items: stretch;
        }
        .page-col {
            flex: 1;
            width: 50%;
            border: 1.2pt solid #003680;
            border-radius: 4px;
            padding: 6px 14px 4px 14px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Typography & Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }

        /* DO Consistent Company Header */
        .col-header-bar {
            margin-bottom: 6px;
        }
        .company-logo-text {
            font-size: 18pt;
            font-weight: 900;
            font-style: italic;
            color: #E21E26;
            letter-spacing: -0.8px;
            margin: 0;
            line-height: 1;
        }
        .company-full-name {
            font-size: 7.5pt;
            font-weight: 800;
            color: #003680;
            margin: 1px 0 0 0;
            letter-spacing: 0.2px;
        }
        .company-address {
            font-size: 5.5pt;
            line-height: 1.25;
            color: #334155;
            margin-top: 3px;
        }
        .doc-title-right {
            font-size: 12pt;
            font-weight: 900;
            color: #003680;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .header-divider {
            border: 0;
            border-top: 2px double #003680;
            margin: 5px 0 6px 0;
        }

        /* Meta Info Tables */
        .meta-box, .employee-card {
            border: 1pt solid #cbd5e1;
            border-radius: 3px;
            padding: 4px 6px;
            margin-bottom: 6px;
            background-color: #f8fafc;
        }
        .meta-table, .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
        }
        .meta-table td, .info-table td {
            padding: 1.5px 1px;
            vertical-align: middle;
        }
        .info-lbl, .meta-lbl {
            color: #475569;
            font-weight: 600;
        }
        .info-sep, .meta-sep {
            color: #64748b;
            text-align: center;
        }
        .info-val, .meta-val {
            color: #0f172a;
        }

        /* Left Side: Attendance Table */
        .att-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.2pt;
            table-layout: fixed;
        }
        .att-table th {
            border: 0.6pt solid #003680;
            padding: 2.5px 1px;
            background-color: #003680;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
        }
        .att-table td {
            border: 0.5pt solid #cbd5e1;
            padding: 1.8px 1px;
            vertical-align: middle;
            color: #1e293b;
        }
        .att-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .att-table tr.bg-red-date td {
            background-color: #fee2e2 !important;
        }
        .text-late, .text-early {
            color: #dc2626 !important;
            font-weight: 800;
        }
        .att-table tr.total-row td {
            border-top: 1.5pt solid #003680;
            border-bottom: 1.5pt solid #003680;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #003680;
            padding: 2.5px 1px;
        }

        /* Right Side: Salary Breakdown */
        .salary-breakdown {
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
            align-items: stretch;
        }
        .salary-col {
            border: 1pt solid #cbd5e1;
            border-radius: 3px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #fff;
            overflow: hidden;
        }
        .salary-col-left {
            flex: 1.18;
        }
        .salary-col-right {
            flex: 0.82;
        }
        .col-header {
            background-color: #f1f5f9;
            padding: 3px 6px;
            font-weight: 800;
            font-size: 6.8pt;
            color: #003680;
            border-bottom: 1pt solid #cbd5e1;
            letter-spacing: 0.5px;
        }
        .salary-body {
            padding: 4px 6px;
            flex: 1;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
        }
        .salary-table td {
            padding: 1.8px 0;
            vertical-align: middle;
        }
        .salary-footer {
            border-top: 1.2pt solid #003680;
            background-color: #f8fafc;
            padding: 3px 6px;
        }

        /* Netto & Dibayar Card */
        .netto-card {
            border: 1.2pt solid #003680;
            border-radius: 3px;
            padding: 5px 8px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #f8fafc, #edf2f7);
        }

        /* Signature & Footer Section */
        .signature-box {
            margin-top: auto;
            padding-top: 4px;
            border-top: 1pt dashed #cbd5e1;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: transparent; padding: 0 !important; margin: 0 !important; }
            .page-col { padding: 5px 12px 3px 12px; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; padding: 10px 15px; background: #003680; color: white; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold; font-size: 8.5pt;">Slip Gaji & Rekap Kehadiran Resmi (PT. Jidoka Result Indonesia)</span>
        <button onclick="window.print()" style="padding: 6px 18px; background: #E21E26; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 8pt; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
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
            <!-- Header Kiri (Konsisten DO) -->
            <div class="col-header-bar">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 48%; vertical-align: middle;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" style="height: 32px;" onerror="this.src='/images/jicos_logo.png'">
                                <div>
                                    <div class="company-logo-text" style="font-size: 15pt;">{{ \App\Models\AppSetting::get('company_logo_text', 'jidoka') }}</div>
                                    <div class="company-full-name" style="font-size: 6.5pt;">{{ \App\Models\AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA') }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="width: 52%; vertical-align: middle; text-align: right;">
                            <div style="font-size: 10.5pt; font-weight: 900; color: #003680; letter-spacing: 0.3px; line-height: 1.1;">DATA KEHADIRAN KARYAWAN</div>
                            <div style="font-size: 6.2pt; color: #64748b; margin-top: 2px;">
                                CUTOFF: {{ \Carbon\Carbon::parse($cutoffStart)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($cutoffEnd)->format('d/m/Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
                <hr class="header-divider">
            </div>

            <!-- Meta Data Karyawan (Kiri) -->
            <div class="meta-box">
                <table class="meta-table">
                    <tr>
                        <td class="meta-lbl" width="55">NAMA</td>
                        <td class="meta-sep" width="8">:</td>
                        <td class="meta-val font-bold" width="150">{{ strtoupper($payroll->employee->full_name) }}</td>
                        <td class="meta-lbl" width="75">PRICE / HOUR</td>
                        <td class="meta-sep" width="8">:</td>
                        <td class="meta-val font-bold">
                            @if($payroll->employee->salary_type === 'hourly' || $payroll->hourly_rate > 0)
                                Rp {{ number_format($pricePerHour, 2, ',', '.') }}
                            @else
                                - (GP Tetap)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">NIK</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-val">{{ $payroll->employee->nik }}</td>
                        <td class="meta-lbl">STATUS PTKP</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-val">{{ $payroll->employee->tax_status ?? 'TK/0' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">BAGIAN / DEPT</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-val">{{ strtoupper($payroll->employee->section ?? ($payroll->employee->department->name ?? 'GENERAL')) }}</td>
                        <td class="meta-lbl">GOLONGAN</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-val">{{ $payroll->employee->golongan ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <table class="att-table">
                <thead>
                    <tr>
                        <th width="16">No</th>
                        <th width="34">Date</th>
                        <th width="30">Masuk</th>
                        <th width="30">Keluar</th>
                        <th width="26">Jam Kerja</th>
                        <th width="26">Jam Lembur</th>
                        <th width="54">Amount Gaji Pokok</th>
                        <th width="50">Amount T. Lembur</th>
                        <th width="42">T. Makan</th>
                        <th width="46">T. Makan Lembur</th>
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

                        // Daftar Hari Libur Nasional Resmi Indonesia (Fixed & 2026)
                        $publicHolidays = [
                            '01-01' => 'Tahun Baru Masehi',
                            '05-01' => 'Hari Buruh Internasional',
                            '06-01' => 'Hari Lahir Pancasila',
                            '08-17' => 'Hari Kemerdekaan RI',
                            '12-25' => 'Hari Raya Natal',
                            '2026-01-16' => 'Isra Mi\'raj',
                            '2026-02-17' => 'Tahun Baru Imlek 2577',
                            '2026-03-19' => 'Hari Suci Nyepi',
                            '2026-03-20' => 'Hari Raya Idul Fitri',
                            '2026-03-21' => 'Hari Raya Idul Fitri',
                            '2026-04-03' => 'Wafat Yesus Kristus',
                            '2026-05-01' => 'Hari Buruh',
                            '2026-05-14' => 'Kenaikan Yesus Kristus',
                            '2026-05-27' => 'Hari Raya Idul Adha',
                            '2026-05-31' => 'Hari Raya Waisak',
                            '2026-06-16' => 'Tahun Baru Islam',
                            '2026-08-17' => 'Hari Kemerdekaan RI',
                            '2026-08-25' => 'Maulid Nabi Muhammad SAW',
                        ];
                    @endphp

                    @foreach($periodDates as $date)
                        @php
                            $dateStr = $date->toDateString();
                            $att = $attendances->get($dateStr);
                            $ot = $overtimeRequests->get($dateStr);

                            // Libur: Hari Minggu atau Tanggal Merah
                            $isSunday = $date->isSunday();
                            $holidayTitle = $publicHolidays[$dateStr] ?? $publicHolidays[$date->format('m-d')] ?? null;
                            $isHoliday = !empty($holidayTitle) || ($att && $att->status === 'holiday');
                            $isRedDate = $isSunday || $isHoliday;

                            $clockInStr = $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-';
                            $clockOutStr = $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-';

                            // Telat (Masuk > 07:30 atau late_minutes > 0 atau status late)
                            $isLate = false;
                            if ($att && !empty($att->clock_in)) {
                                $cIn = \Carbon\Carbon::parse($att->clock_in);
                                if ($att->late_minutes > 0 || $att->status === 'late' || $cIn->format('H:i:s') > '07:30:00') {
                                    $isLate = true;
                                }
                            }

                            // Determine working hours
                            $isWorking = false;
                            $dailyJamKerja = 0.0;
                            if ($att && (in_array($att->status, ['present', 'late']) || !empty($att->clock_in))) {
                                $isWorking = true;
                                $dailyJamKerja = 8.0;
                            }

                            // Pulang Cepat (early_leave_minutes > 0, status early_leave, atau pulang sebelum 17:00 dan jam kerja < 8.0)
                            $isEarlyLeave = false;
                            if ($att && !empty($att->clock_out)) {
                                $cOut = \Carbon\Carbon::parse($att->clock_out);
                                if ($att->early_leave_minutes > 0 || $att->status === 'early_leave' || ($cOut->format('H:i:s') < '17:00:00' && $dailyJamKerja < 8.0)) {
                                    $isEarlyLeave = true;
                                }
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
                            
                            // T. Makan Lembur ada jika pulang jam 19.00 atau lebih (>= 19:00)
                            $hasOvertimeMeal = false;
                            if ($att && !empty($att->clock_out)) {
                                $clockOutTime = \Carbon\Carbon::parse($att->clock_out)->format('H:i:s');
                                if ($clockOutTime >= '19:00:00') {
                                    $hasOvertimeMeal = true;
                                }
                            } elseif ($dailyJamLembur >= 2.0) {
                                $hasOvertimeMeal = true;
                            }
                            $dailyTMakanLembur = $hasOvertimeMeal ? 12500 : 0;

                            // Accumulate
                            $sumJamKerja += $dailyJamKerja;
                            $sumJamLembur += $dailyJamLembur;
                            $sumAmountGP += $amountGP;
                            $sumAmountLembur += $amountLembur;
                            $sumTMakan += $dailyTMakan;
                            $sumTMakanLembur += $dailyTMakanLembur;
                        @endphp
                        <tr class="{{ $isRedDate ? 'bg-red-date' : '' }}">
                            <td class="text-center">{{ $rowNo++ }}</td>
                            <td class="text-center font-bold" style="{{ $isRedDate ? 'color: #b91c1c;' : '' }}" @if($holidayTitle) title="{{ $holidayTitle }}" @endif>{{ $date->format('d-M') }}</td>
                            <td class="text-center {{ $isLate ? 'text-late' : '' }}" @if($isLate && $att && $att->late_minutes > 0) title="Telat {{ $att->late_minutes }}m" @endif>{{ $clockInStr }}</td>
                            <td class="text-center {{ $isEarlyLeave ? 'text-early' : '' }}" @if($isEarlyLeave && $att && $att->early_leave_minutes > 0) title="Pulang cepat {{ $att->early_leave_minutes }}m" @endif>{{ $clockOutStr }}</td>
                            <td class="text-center">{{ $dailyJamKerja > 0 ? number_format($dailyJamKerja, 1, ',', '.') : '-' }}</td>
                            <td class="text-center">{{ $dailyJamLembur > 0 ? number_format($dailyJamLembur, 1, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $amountGP > 0 ? 'Rp ' . number_format($amountGP, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $amountLembur > 0 ? 'Rp ' . number_format($amountLembur, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $dailyTMakan > 0 ? 'Rp ' . number_format($dailyTMakan, 0, ',', '.') : 'Rp -' }}</td>
                            <td class="text-right">{{ $dailyTMakanLembur > 0 ? 'Rp ' . number_format($dailyTMakanLembur, 0, ',', '.') : '0' }}</td>
                        </tr>
                    @endforeach

                    <!-- Total Row (Clean Rupiah format) -->
                    <tr class="total-row">
                        <td colspan="4" class="text-center font-bold">TOTAL</td>
                        <td class="text-center font-bold">{{ number_format($sumJamKerja, 1, ',', '.') }}</td>
                        <td class="text-center font-bold">{{ number_format($sumJamLembur, 1, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($sumAmountGP, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($sumAmountLembur, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($sumTMakan, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($sumTMakanLembur, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Legend Info -->
            <div style="font-size: 5.5pt; color: #64748b; margin-top: 3px; display: flex; justify-content: space-between; align-items: center; padding: 0 2px;">
                <span><span style="display: inline-block; width: 7px; height: 7px; background-color: #fee2e2; border: 0.5pt solid #fca5a5; vertical-align: middle; margin-right: 3px; border-radius: 1px;"></span> Latar Merah Muda: Hari Minggu / Tanggal Merah</span>
                <span><span style="color: #dc2626; font-weight: bold; margin-right: 2px;">07:41</span> Teks Merah: Telat / Pulang Cepat (Perlu Cek Shift)</span>
            </div>
        </div>

        <!-- ==================== LEMBAR KANAN: SLIP GAJI KARYAWAN ==================== -->
        <div class="page-col">
            <!-- Header Kanan (DO Standar Jidoka) -->
            <div class="col-header-bar">
                <table class="header-section" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 58%; vertical-align: top;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" style="height: 38px;" onerror="this.src='/images/jicos_logo.png'">
                                <div>
                                    <div class="company-logo-text">{{ \App\Models\AppSetting::get('company_logo_text', 'jidoka') }}</div>
                                    <div class="company-full-name">{{ \App\Models\AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA') }}</div>
                                </div>
                            </div>
                            <div class="company-address">
                                {!! nl2br(e(\App\Models\AppSetting::get('company_address', "Kawasan Industri JABABEKA I\nJl. Jababeka II Blok C No. 19 L, Pasir gombong, Cikarang Utara\nBekasi 17530 Jawa Barat. Telp : 021 8938 3915\ne_mail : jidoka.pt@yahoo.com"))) !!}
                            </div>
                        </td>
                        <td style="width: 42%; vertical-align: top; text-align: right;">
                            <div class="doc-title-right">SLIP GAJI KARYAWAN</div>
                            <table style="margin-left: auto; border-collapse: collapse; margin-top: 2px;">
                                <tr>
                                    <td style="font-size: 6.5pt; color: #64748b; text-align: right; padding: 1px 3px;">Periode:</td>
                                    <td style="font-size: 7pt; font-weight: bold; color: #003680; text-align: right;">{{ strtoupper(\Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F')) }} {{ $payroll->period_year }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 6.5pt; color: #64748b; text-align: right; padding: 1px 3px;">Status:</td>
                                    <td style="font-size: 6.5pt; font-weight: bold; color: {{ $payroll->status === 'paid' ? '#059669' : '#003680' }}; text-align: right; text-transform: uppercase;">{{ $payroll->status }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="font-size: 5.5pt; color: #94a3b8; text-align: right; letter-spacing: 0.5px; padding-top: 2px;">CONFIDENTIAL / RAHASIA</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr class="header-divider">
            </div>

            <!-- Info Karyawan Terpadu (1 Box Simetris 5x2) -->
            <div class="employee-card">
                <table class="info-table">
                    <tr>
                        <td class="info-lbl" width="65">Nama</td>
                        <td class="info-sep" width="8">:</td>
                        <td class="info-val font-bold" width="145">{{ strtoupper($payroll->employee->full_name) }}</td>
                        <td class="info-lbl" width="70">Bulan / Thn</td>
                        <td class="info-sep" width="8">:</td>
                        <td class="info-val">{{ \Carbon\Carbon::create(null, $payroll->period_month)->translatedFormat('F') }} {{ $payroll->period_year }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">NIK</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->nik }}</td>
                        <td class="info-lbl">Jabatan</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->position->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Departemen</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->department->name ?? '-' }}</td>
                        <td class="info-lbl">Golongan</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->golongan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Bagian</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->section ?? ($payroll->employee->department->name ?? '-') }}</td>
                        <td class="info-lbl">Hari Kerja</td>
                        <td class="info-sep">:</td>
                        <td class="info-val font-bold">{{ $payroll->total_working_days }} Hari</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Status PTKP</td>
                        <td class="info-sep">:</td>
                        <td class="info-val">{{ $payroll->employee->tax_status ?? 'TK/0' }}</td>
                        <td class="info-lbl">Jam Lembur</td>
                        <td class="info-sep">:</td>
                        <td class="info-val font-bold">{{ number_format($payroll->total_overtime_hours, 1, ',', '.') }} Jam</td>
                    </tr>
                </table>
            </div>

            <!-- Breakdown Pendapatan vs Potongan (Baseline Terpadu Sejajar) -->
            <div class="salary-breakdown">
                <!-- Pendapatan -->
                <div class="salary-col salary-col-left">
                    <div class="col-header">PENDAPATAN</div>
                    <div class="salary-body">
                        <table class="salary-table">
                            <tr>
                                <td>Gaji Pokok</td>
                                <td width="8">:</td>
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
                        </table>
                    </div>
                    <div class="salary-footer">
                        <table class="salary-table">
                            <tr>
                                <td class="font-bold" style="color: #003680;">Gaji Bruto</td>
                                <td width="8" class="font-bold" style="color: #003680;">:</td>
                                <td class="text-right font-bold" style="color: #003680; font-size: 7.5pt;">{{ number_format($gajiBruto, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Potongan -->
                <div class="salary-col salary-col-right">
                    <div class="col-header">POTONGAN</div>
                    <div class="salary-body">
                        <table class="salary-table">
                            <tr>
                                <td>PPh21</td>
                                <td width="8">:</td>
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
                        </table>
                    </div>
                    <div class="salary-footer">
                        <table class="salary-table">
                            <tr>
                                <td class="font-bold" style="color: #003680;">Total Potongan</td>
                                <td width="8" class="font-bold" style="color: #003680;">:</td>
                                <td class="text-right font-bold" style="color: #003680; font-size: 7.5pt;">{{ $totalPotongan > 0 ? '-' . number_format($totalPotongan, 0, ',', '.') : '0' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Netto & Dibayar Card -->
            <div class="netto-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 38%; font-size: 5.8pt; color: #64748b; vertical-align: middle; line-height: 1.3;">
                            * Telah ditransfer / diserahkan sesuai rincian di atas.<br>
                            Harap disimpan sebagai bukti pembayaran yang sah.
                        </td>
                        <td style="width: 62%; text-align: right; vertical-align: middle;">
                            <table style="margin-left: auto; border-collapse: collapse;">
                                <tr>
                                    <td style="font-size: 7pt; font-weight: bold; color: #475569; padding-right: 8px; text-align: right; white-space: nowrap;">Gaji Netto :</td>
                                    <td style="font-size: 7.5pt; font-weight: bold; color: #1e293b; text-align: right; white-space: nowrap;">Rp {{ number_format($gajiNetto, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 8.5pt; font-weight: 900; color: #003680; padding-right: 8px; text-align: right; padding-top: 2px; white-space: nowrap;">DIBAYAR :</td>
                                    <td style="font-size: 11.5pt; font-weight: 900; color: #003680; text-align: right; padding-top: 2px; white-space: nowrap;">Rp {{ number_format($dibayar, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tanda Tangan & Dokumen Sah Digital (Anchored at Bottom) -->
            <div class="signature-box">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; vertical-align: bottom;">
                            <div style="font-size: 6.5pt; color: #475569; margin-bottom: 18px;">Diterima Oleh,</div>
                            <div style="font-weight: bold; font-size: 7.5pt; text-decoration: underline; color: #0f172a;">{{ strtoupper($payroll->employee->full_name) }}</div>
                            <div style="font-size: 6pt; color: #64748b;">NIK: {{ $payroll->employee->nik }}</div>
                        </td>
                        <td style="width: 50%; text-align: right; vertical-align: bottom;">
                            <table style="float: right; border-collapse: collapse;">
                                <tr>
                                    <td style="vertical-align: middle; padding-right: 8px; text-align: right;">
                                        <div style="font-size: 6.2pt; font-weight: 800; color: #003680; letter-spacing: 0.3px;">DOKUMEN SAH DIGITAL</div>
                                        <div style="font-size: 5pt; color: #64748b; line-height: 1.25; margin-top: 1px;">
                                            Sistem ERP Jidoka Result Indonesia<br>
                                            Cetak: {{ date('d/m/Y H:i') }} WIB<br>
                                            Status: Valid Electronic Document
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle; text-align: center; border: 1pt solid #cbd5e1; padding: 2px; background: #fff; border-radius: 3px;">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('payroll.public-validate', $payroll->id)) }}" 
                                             alt="QR Validasi" 
                                             style="width: 44px; height: 44px; display: block;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
