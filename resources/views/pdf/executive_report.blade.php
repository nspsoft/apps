<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Executive Weekly Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #2D3748;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .container {
            padding: 20px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #d32f2f; /* SPINDO Red */
            float: left;
        }
        .title {
            float: right;
            text-align: right;
        }
        .title h2 {
            margin: 0 0 4px 0;
            color: #1A202C;
            font-size: 16px;
        }
        .title p {
            margin: 0;
            color: #718096;
            font-size: 10px;
        }
        .clear {
            clear: both;
        }
        
        /* Grid Cards */
        .card-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .card-col {
            width: 48%;
            float: left;
        }
        .card-col-right {
            width: 48%;
            float: right;
        }
        .card {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 12px;
            background-color: #F8FAFC;
        }
        .card-sales {
            border-left: 4px solid #48BB78; /* Green for Sales */
        }
        .card-purchase {
            border-left: 4px solid #d32f2f; /* Red for Purchase */
        }
        .card-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .card-value {
            font-size: 18px;
            font-weight: bold;
            color: #1A202C;
            margin-bottom: 4px;
        }
        .card-subtext {
            font-size: 9px;
        }
        .text-green {
            color: #38A169;
            font-weight: bold;
        }
        .text-red {
            color: #E53E3E;
            font-weight: bold;
        }
        .text-muted {
            color: #718096;
        }

        /* Chart Section */
        .chart-box {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #FFFFFF;
        }
        .chart-header {
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: bold;
            color: #2D3748;
        }
        .chart-legend {
            float: right;
            font-size: 9px;
        }
        .legend-item {
            display: inline-block;
            margin-left: 12px;
        }
        .legend-color {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-right: 4px;
            vertical-align: middle;
            border-radius: 2px;
        }
        
        /* Table Section */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1A202C;
            margin-bottom: 8px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
        }
        th {
            background-color: #EDF2F7;
            font-weight: bold;
            color: #4A5568;
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        
        .footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            font-size: 8px;
            color: #A0AEC0;
            text-align: center;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">PT SPINDO</div>
            <div class="title">
                <h2>LAPORAN KINERJA EKSEKUTIF</h2>
                <p>Periode: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
            </div>
            <div class="clear"></div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="card-grid">
            <div class="card-col">
                <div class="card card-sales">
                    <div class="card-title">Kinerja Penjualan (Sales)</div>
                    <div class="card-value">Rp {{ number_format($sales_total, 0, ',', '.') }}</div>
                    <div class="card-subtext">
                        <span class="{{ $sales_change_percent >= 0 ? 'text-green' : 'text-red' }}">
                            {{ $sales_change_percent >= 0 ? '+' : '' }}{{ number_format($sales_change_percent, 1) }}%
                        </span>
                        <span class="text-muted">vs periode sebelumnya ({{ $sales_count }} SO)</span>
                    </div>
                </div>
            </div>
            <div class="card-col-right">
                <div class="card card-purchase">
                    <div class="card-title">Kinerja Pembelian (Purchasing)</div>
                    <div class="card-value">Rp {{ number_format($purchase_total, 0, ',', '.') }}</div>
                    <div class="card-subtext">
                        <span class="{{ $purchase_change_percent <= 0 ? 'text-green' : 'text-red' }}">
                            {{ $purchase_change_percent >= 0 ? '+' : '' }}{{ number_format($purchase_change_percent, 1) }}%
                        </span>
                        <span class="text-muted">vs periode sebelumnya ({{ $purchase_count }} PO)</span>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Chart Container -->
        <div class="chart-box">
            <div class="chart-header">
                <span class="chart-legend">
                    <span class="legend-item"><span class="legend-color" style="background-color: #48BB78;"></span>Sales</span>
                    <span class="legend-item"><span class="legend-color" style="background-color: #E53E3E;"></span>Purchasing</span>
                </span>
                Tren Transaksi Harian (7 Hari Terakhir)
            </div>
            <div class="clear"></div>
            
            <!-- Native SVG Chart inside PDF -->
            <svg width="{{ $svg['width'] }}" height="{{ $svg['height'] }}" style="width: 100%; display: block; overflow: visible;">
                <!-- Background Gridlines -->
                @foreach ($svg['grid_lines'] as $line)
                    <line x1="{{ $line['x1'] }}" y1="{{ $line['y1'] }}" x2="{{ $line['x2'] }}" y2="{{ $line['y2'] }}" stroke="#E2E8F0" stroke-width="0.8" stroke-dasharray="2,2" />
                @endforeach

                <!-- Y Axis Labels -->
                @foreach ($svg['y_labels'] as $label)
                    <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" font-size="8" fill="#718096" text-anchor="end">{{ $label['text'] }}</text>
                @endforeach

                <!-- X Axis Labels -->
                @foreach ($svg['labels'] as $label)
                    <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" font-size="8" fill="#718096" text-anchor="middle">{{ $label['text'] }}</text>
                @endforeach

                <!-- Sales Polyline (Green) -->
                @if(!empty($svg['sales_points']))
                    <polyline fill="none" stroke="#48BB78" stroke-width="2.5" points="{{ $svg['sales_points'] }}" />
                    @foreach(explode(' ', $svg['sales_points']) as $pt)
                        @php $coords = explode(',', $pt); @endphp
                        @if(count($coords) == 2)
                            <circle cx="{{ $coords[0] }}" cy="{{ $coords[1] }}" r="3" fill="#48BB78" stroke="#FFFFFF" stroke-width="1" />
                        @endif
                    @endforeach
                @endif

                <!-- Purchasing Polyline (Red) -->
                @if(!empty($svg['purchase_points']))
                    <polyline fill="none" stroke="#E53E3E" stroke-width="2.5" points="{{ $svg['purchase_points'] }}" />
                    @foreach(explode(' ', $svg['purchase_points']) as $pt)
                        @php $coords = explode(',', $pt); @endphp
                        @if(count($coords) == 2)
                            <circle cx="{{ $coords[0] }}" cy="{{ $coords[1] }}" r="3" fill="#E53E3E" stroke="#FFFFFF" stroke-width="1" />
                        @endif
                    @endforeach
                @endif
            </svg>
        </div>

        <!-- Cash Flow & Outstanding Invoices Section -->
        <div class="card-grid">
            <div class="card-col">
                <div class="section-title">Piutang Customer (Sales Invoices)</div>
                <table style="margin-bottom: 0px;">
                    <tr>
                        <td class="text-muted">Total Outstanding Piutang</td>
                        <td class="text-right" style="font-weight: bold;">Rp {{ number_format($sales_outstanding, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="color: #E53E3E;">Piutang Jatuh Tempo (Overdue)</td>
                        <td class="text-right text-red" style="font-weight: bold;">Rp {{ number_format($sales_overdue, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-col-right">
                <div class="section-title">Hutang Supplier (Purchase Invoices)</div>
                <table style="margin-bottom: 0px;">
                    <tr>
                        <td class="text-muted">Total Outstanding Hutang</td>
                        <td class="text-right" style="font-weight: bold;">Rp {{ number_format($purchase_outstanding, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="color: #E53E3E;">Hutang Jatuh Tempo (Overdue)</td>
                        <td class="text-right text-red" style="font-weight: bold;">Rp {{ number_format($purchase_overdue, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Detail Rankings (Two columns) -->
        <div class="card-grid" style="margin-top: 15px;">
            <div class="card-col">
                <div class="section-title">Top 5 Produk Terlaris (by Volume)</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25px;">No</th>
                            <th>Nama Produk</th>
                            <th class="text-right" style="width: 50px;">Qty</th>
                            <th class="text-right" style="width: 80px;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($top_products as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $item->product->name ?? 'Unknown Product' }}</strong><br><small>{{ $item->product->sku ?? '-' }}</small></td>
                            <td class="text-right">{{ number_format($item->total_qty) }}</td>
                            <td class="text-right">Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-muted" style="text-align: center;">Tidak ada data penjualan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-col-right">
                <div class="section-title">Top 5 Belanja Supplier (by Nominal)</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25px;">No</th>
                            <th>Nama Supplier</th>
                            <th class="text-right" style="width: 50px;">PO</th>
                            <th class="text-right" style="width: 80px;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($top_suppliers as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $item->supplier->name ?? 'Unknown Supplier' }}</strong></td>
                            <td class="text-right">{{ number_format($item->total_orders) }}</td>
                            <td class="text-right">Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-muted" style="text-align: center;">Tidak ada data pembelian</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            PT Steel Pipe Industry of Indonesia, Tbk (PT SPINDO) | Laporan Kinerja Mingguan Otomatis JICOS ERP | Waktu Cetak: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
