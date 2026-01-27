<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-logo-img {
            height: 55px;
            float: left;
            margin-right: 15px;
        }
        .company-header-text {
            color: #E21E26;
            font-weight: 900;
            font-style: italic;
            font-size: 24pt;
            letter-spacing: -1px;
            margin: 0;
            line-height: 1;
        }
        .company-header-sub {
            color: #003680;
            font-weight: 800;
            font-size: 11pt;
            margin: -2px 0 5px 0;
        }
        .company-address {
            font-size: 9pt;
            line-height: 1.3;
            clear: left;
            padding-top: 5px;
        }
        .doc-title {
            text-align: right;
            font-size: 24pt;
            font-weight: 900;
            font-style: italic;
            color: #000080;
            margin-top: 10px;
        }
        .meta-table {
            float: right;
            margin-top: 10px;
        }
        .meta-table td {
            padding: 2px 0;
            font-size: 10pt;
        }
        .meta-label {
            font-weight: bold;
            text-align: left;
            padding-right: 10px;
        }
        .meta-separator {
            width: 15px;
            text-align: center;
        }
        .meta-value {
            min-width: 150px;
        }

        .billing-section {
            margin-top: 25px;
            margin-bottom: 20px;
        }
        .bill-to-box {
            border: 1pt solid #003680;
            padding: 8px 12px;
            min-height: 80px;
        }
        .box-title {
            font-weight: bold;
            text-decoration: underline;
            color: #003680;
            margin-bottom: 5px;
            font-size: 10pt;
        }

        .items-table {
            margin-top: 15px;
            border: 1pt solid #000;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 10px 5px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border-left: 1pt solid #000;
            border-right: 1pt solid #000;
            border-top: none;
            border-bottom: none;
            padding: 8px 5px;
            vertical-align: top;
        }
        .items-table tr.last-row td {
            border-bottom: 1pt solid #000;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .footer-section {
            margin-top: 30px;
        }
        .bank-info {
            font-size: 10pt;
            border: 1pt solid #000;
            padding: 10px;
            width: 320px;
        }
        .qr-box {
            text-align: center;
            width: 120px;
            float: right;
        }
        .signature-box {
            text-align: center;
            width: 250px;
            float: right;
            margin-right: 20px;
        }

        .footer-wrapper {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0 20px 20px 20px;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .content-wrapper { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #E21E26; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">PRINT INVOICE</button>
    </div>

    <div class="content-wrapper">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td width="60%">
                    <img src="/images/jri-official-logo.png" alt="logo" class="company-logo-img">
                    <div>
                        <div class="company-header-text">jidoka</div>
                        <div class="company-header-sub">PT. JIDOKA RESULT INDONESIA</div>
                    </div>
                    <div class="company-address">
                        Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L<br>
                        Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                        Telp : +62 21 89383915
                    </div>
                </td>
                <td width="40%">
                    <div class="doc-title">INVOICE</div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">NO</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value">{{ $invoice->invoice_date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">PO No.</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold">{{ $invoice->salesOrder->customer_po_number ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Billing Section -->
        <div class="billing-section">
            <table width="100%">
                <tr>
                    <td width="55%">
                        <div class="bill-to-box">
                            <div class="box-title">BILL TO:</div>
                            <div class="font-bold" style="font-size: 11pt; margin-bottom: 2px;">{{ $invoice->customer->name }}</div>
                            <div style="font-size: 9pt; color: #333;">
                                {{ $invoice->customer->full_address }}<br>
                                Telp: {{ $invoice->customer->phone ?? '-' }}
                            </div>
                        </div>
                    </td>
                    <td width="45%" style="padding-left: 30px; vertical-align: bottom;">
                        <table cellpadding="2">
                            <tr>
                                <td class="font-bold" width="100">DUE DATE</td>
                                <td width="15">:</td>
                                <td class="font-bold" style="color: #E21E26;">{{ $invoice->due_date->format('d F Y') }}</td>
                            </tr>
                            <tr>
                                <td class="font-bold">CURRENCY</td>
                                <td>:</td>
                                <td>IDR</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-uppercase">DO Numbers</td>
                                <td>:</td>
                                <td style="font-size: 8pt;">
                                    @php
                                        $doNumbers = $invoice->salesOrder->deliveryOrders()->where('status', 'delivered')->pluck('do_number')->toArray();
                                        echo implode(', ', $doNumbers) ?: '-';
                                    @endphp
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Description</th>
                    <th width="60">Qty</th>
                    <th width="60">UOM</th>
                    <th width="120">Price</th>
                    <th width="120">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $item->product->name }}</div>
                        <div style="font-size: 8pt; color: #555;">{{ $item->product->description }}</div>
                    </td>
                    <td class="text-center">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="text-center uppercase">{{ $item->unit->code ?? 'PCS' }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                <!-- Spacing rows with vertical borders only -->
                @for ($i = count($invoice->items); $i < 6; $i++)
                <tr>
                    <td style="height: 25px; border-bottom: none; border-top: none;"></td>
                    <td style="border-bottom: none; border-top: none;"></td>
                    <td style="border-bottom: none; border-top: none;"></td>
                    <td style="border-bottom: none; border-top: none;"></td>
                    <td style="border-bottom: none; border-top: none;"></td>
                    <td style="border-bottom: none; border-top: none;"></td>
                </tr>
                @endfor
                <!-- Empty row to close the bottom border -->
                <tr class="last-row">
                    <td style="height: 0px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Bank Info & Totals Section -->
        <div style="margin-top: 15px; page-break-inside: avoid;">
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td width="55%" style="vertical-align: top;">
                        <div class="bank-info" style="width: auto; max-width: 320px;">
                            <div class="font-bold" style="text-decoration: underline; margin-bottom: 5px; font-size: 9pt;">KINDLY TRANSFER PAYMENT TO :</div>
                            <div class="font-bold" style="color: #003680;">Bank MANDIRI</div>
                            <div>KK Karawang Galuh Mas</div>
                            <div class="font-bold" style="font-size: 11pt; letter-spacing: 1px;">173-00-0777778-3</div>
                            <div class="font-bold text-uppercase">PT JIDOKA RESULT INDONESIA</div>
                        </div>
                    </td>
                    <td width="45%" style="vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td width="80" class="text-right font-bold" style="padding: 3px 0;">Subtotal</td>
                                <td width="20" class="text-center">:</td>
                                <td width="40" class="text-left font-bold">IDR</td>
                                <td class="text-right font-bold" style="padding: 3px 0;">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr style="border-bottom: 1pt solid #000;">
                                <td class="text-right font-bold" style="padding: 3px 0;">Discount</td>
                                <td class="text-center">:</td>
                                <td class="text-left font-bold">IDR</td>
                                <td class="text-right font-bold" style="padding: 3px 0;">{{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-right font-bold" style="padding: 3px 0;">Subtotal</td>
                                <td class="text-center">:</td>
                                <td class="text-left font-bold">IDR</td>
                                <td class="text-right font-bold" style="padding: 3px 0;">{{ number_format($invoice->subtotal - $invoice->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr style="border-bottom: 2pt solid #000;">
                                <td class="text-right font-bold" style="padding: 3px 0;">VAT (11%)</td>
                                <td class="text-center">:</td>
                                <td class="text-left font-bold">IDR</td>
                                <td class="text-right font-bold" style="padding: 3px 0;">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr style="border-bottom: 3pt double #000;">
                                <td class="text-right font-bold" style="padding: 8px 0; font-size: 11pt;">Grand Total</td>
                                <td class="text-center">:</td>
                                <td class="text-left font-bold" style="font-size: 11pt;">IDR</td>
                                <td class="text-right font-bold" style="padding: 8px 0; font-size: 11pt;">{{ number_format($invoice->total, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-wrapper">
        <table width="100%">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <div class="signature-box" style="float: left; text-align: center; margin-left: 0;">
                        <div>Cikarang, {{ $invoice->invoice_date->format('d F Y') }}</div>
                        <div style="height: 60px;"></div>
                        <div class="font-bold" style="text-decoration: underline; font-size: 11pt;">JAHRUDIN</div>
                        <div style="font-weight: bold; color: #666;">Direktur</div>
                    </div>
                    
                    <div style="clear: both; margin-top: 5px; font-size: 8pt; color: #555;">
                        <i>* This invoice is a valid document generated by JICOS ERP System.</i><br>
                        <i>* Payment is considered valid only after clearing in our bank account.</i>
                    </div>
                </td>
                <td width="45%" style="vertical-align: top;">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.invoices.public-validate', $invoice->id)) }}" style="width: 80px; height: 80px;">
                        <div style="font-size: 8pt; font-weight: bold; margin-top: 5px; color: #003680; line-height: 1;">SCAN FOR VALIDATION</div>
                        <div style="font-size: 7pt; color: #666; margin-top: 3px;">Official JIDOKA Form</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
</body>
</html>
