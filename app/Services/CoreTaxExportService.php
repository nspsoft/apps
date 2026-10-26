<?php

namespace App\Services;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CoreTaxExportService
{
    /**
     * Generate CoreTax Excel from an invoice collection or query.
     *
     * @param iterable $invoices
     * @param array $options
     * @return string Path to temporary generated .xlsx file
     */
    public function generate(iterable $invoices, array $options = []): string
    {
        $templatePath = resource_path('templates/Format_Testing_Faktur.xlsx');
        if (!file_exists($templatePath)) {
            // Fallback to project root if not in resources
            $fallback = base_path('Format Testing Faktur.xlsx');
            if (file_exists($fallback)) {
                $templatePath = $fallback;
            } else {
                throw new \RuntimeException("CoreTax template file not found.");
            }
        }

        $spreadsheet = IOFactory::load($templatePath);

        $sheetFaktur = $spreadsheet->getSheetByName('Faktur');
        $sheetDetail = $spreadsheet->getSheetByName('DetailFaktur');

        if (!$sheetFaktur || !$sheetDetail) {
            throw new \RuntimeException("Format template tidak valid: Sheet 'Faktur' atau 'DetailFaktur' tidak ditemukan.");
        }

        // Seller data defaults
        $sellerNpwp = $options['seller_npwp'] ?? '0809672348413000';
        $sellerNitku = $options['seller_nitku'] ?? '0809672348413000000000';
        $sheetFaktur->setCellValueExplicit('C1', $sellerNpwp, DataType::TYPE_STRING);

        // Clear existing sample rows from Faktur
        $highestFakturRow = $sheetFaktur->getHighestRow();
        for ($r = 4; $r <= max(10, $highestFakturRow); $r++) {
            for ($c = 'A'; $c <= 'R'; $c++) {
                $sheetFaktur->setCellValue($c . $r, null);
            }
        }

        // Clear existing sample rows from DetailFaktur
        $highestDetailRow = $sheetDetail->getHighestRow();
        for ($r = 2; $r <= max(10, $highestDetailRow); $r++) {
            for ($c = 'A'; $c <= 'N'; $c++) {
                $sheetDetail->setCellValue($c . $r, null);
            }
        }

        $fakturRow = 4;
        $detailRow = 2;
        $fakturIndex = 1;

        $keterangan07 = $options['keterangan_tambahan_07'] ?? 'TD.00502';
        $capFasilitas07 = $options['cap_fasilitas_07'] ?? 'TD.01103';
        $barangJasaType = strtoupper($options['barang_jasa'] ?? 'A');
        if (!in_array($barangJasaType, ['A', 'B'])) {
            $barangJasaType = 'A';
        }
        $selectedKodeTransaksi = $options['kode_transaksi'] ?? '04';
        $tarifPpnbm = (float) ($options['tarif_ppnbm'] ?? 0);

        foreach ($invoices as $inv) {
            $cust = $inv->customer ?? $inv->salesOrder?->customer;
            $taxAmount = (float) ($inv->tax_amount_manual ?? $inv->tax_amount ?? 0);
            
            // Logic: if user selected 04 and tax is 0, switch to 07
            if ($selectedKodeTransaksi === '04') {
                $kodeTransaksi = ($taxAmount > 0) ? '04' : '07';
            } else {
                $kodeTransaksi = $selectedKodeTransaksi;
            }

            // Clean Customer Tax ID / NIK
            $rawTax = preg_replace('/[^0-9]/', '', $cust?->tax_id ?? '');
            if (!empty($rawTax)) {
                if (strlen($rawTax) === 15) {
                    $rawTax = '0' . $rawTax;
                }
                if (strlen($rawTax) === 16) {
                    $buyerId = $rawTax;
                    $buyerType = 'TIN';
                    $buyerDocNo = '-';
                    $buyerNitku = $rawTax . '000000'; // 22 digits
                } else {
                    $buyerId = str_pad($rawTax, 16, '0', STR_PAD_LEFT);
                    $buyerType = 'National ID';
                    $buyerDocNo = $rawTax;
                    $buyerNitku = '0000000000000000000000';
                }
            } else {
                $buyerId = '0000000000000000';
                $buyerType = 'TIN';
                $buyerDocNo = '-';
                $buyerNitku = '0000000000000000000000';
            }

            $ketTambahan = ($kodeTransaksi === '07') ? $keterangan07 : '';
            $capFasilitas = ($kodeTransaksi === '07') ? $capFasilitas07 : '';

            $invDate = $inv->invoice_date instanceof Carbon 
                ? $inv->invoice_date->format('d/m/Y') 
                : ($inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('d/m/Y') : date('d/m/Y'));

            // Write Sheet Faktur
            $sheetFaktur->setCellValue('A' . $fakturRow, $fakturIndex);
            $sheetFaktur->setCellValue('B' . $fakturRow, $invDate);
            $sheetFaktur->setCellValue('C' . $fakturRow, 'Normal');
            $sheetFaktur->setCellValueExplicit('D' . $fakturRow, $kodeTransaksi, DataType::TYPE_STRING);
            $sheetFaktur->setCellValue('E' . $fakturRow, $ketTambahan);
            $sheetFaktur->setCellValue('F' . $fakturRow, '');
            $sheetFaktur->setCellValue('G' . $fakturRow, '');
            $sheetFaktur->setCellValue('H' . $fakturRow, $inv->invoice_number);
            $sheetFaktur->setCellValue('I' . $fakturRow, $capFasilitas);
            $sheetFaktur->setCellValueExplicit('J' . $fakturRow, $sellerNitku, DataType::TYPE_STRING);
            $sheetFaktur->setCellValueExplicit('K' . $fakturRow, $buyerId, DataType::TYPE_STRING);
            $sheetFaktur->setCellValue('L' . $fakturRow, $buyerType);
            $sheetFaktur->setCellValue('M' . $fakturRow, 'IDN');
            $sheetFaktur->setCellValueExplicit('N' . $fakturRow, $buyerDocNo, DataType::TYPE_STRING);
            $sheetFaktur->setCellValue('O' . $fakturRow, $cust?->name ?? 'Customer');
            $sheetFaktur->setCellValue('P' . $fakturRow, $cust?->full_address ?? $cust?->address ?? '-');
            $sheetFaktur->setCellValue('Q' . $fakturRow, $cust?->email ?? '');
            $sheetFaktur->setCellValueExplicit('R' . $fakturRow, $buyerNitku, DataType::TYPE_STRING);

            // Write Sheet DetailFaktur
            if ($inv->items->isEmpty()) {
                // Single line fallback if no detail items
                $sheetDetail->setCellValue('A' . $detailRow, $fakturIndex);
                $sheetDetail->setCellValue('B' . $detailRow, $barangJasaType);
                $sheetDetail->setCellValueExplicit('C' . $detailRow, '481900', DataType::TYPE_STRING);
                $sheetDetail->setCellValue('D' . $detailRow, 'Penjualan Barang / Jasa ' . $inv->invoice_number);
                $sheetDetail->setCellValue('E' . $detailRow, 'UM.0021');
                $sheetDetail->setCellValue('F' . $detailRow, (float)$inv->subtotal);
                $sheetDetail->setCellValue('G' . $detailRow, 1);
                $sheetDetail->setCellValue('H' . $detailRow, (float)$inv->discount_amount);
                $sheetDetail->setCellValue('I' . $detailRow, "=G{$detailRow}*F{$detailRow}-H{$detailRow}");

                if ($kodeTransaksi === '04') {
                    $sheetDetail->setCellValue('J' . $detailRow, "=11/12*I{$detailRow}");
                    $sheetDetail->setCellValue('K' . $detailRow, 12);
                    $sheetDetail->setCellValue('L' . $detailRow, "=J{$detailRow}*K{$detailRow}/100");
                } else {
                    $sheetDetail->setCellValue('J' . $detailRow, "=I{$detailRow}");
                    $sheetDetail->setCellValue('K' . $detailRow, 0);
                    $sheetDetail->setCellValue('L' . $detailRow, 0);
                }
                if ($tarifPpnbm > 0) {
                    $sheetDetail->setCellValue('M' . $detailRow, $tarifPpnbm);
                    $sheetDetail->setCellValue('N' . $detailRow, "=M{$detailRow}*J{$detailRow}/100");
                } else {
                    $sheetDetail->setCellValue('M' . $detailRow, 0);
                    $sheetDetail->setCellValue('N' . $detailRow, 0);
                }
                $detailRow++;
            } else {
                foreach ($inv->items as $item) {
                    $pName = $item->product?->name ?? $item->description ?? 'Item';
                    $unitCode = self::mapUnitCode($item->unit?->code, $item->unit?->name);

                    $qty = (float) ($item->qty ?: 1);
                    $price = (float) ($item->unit_price ?: 0);
                    $disc = (float) ($item->discount_amount ?: 0);

                    $sheetDetail->setCellValue('A' . $detailRow, $fakturIndex);
                    $sheetDetail->setCellValue('B' . $detailRow, $barangJasaType);
                    $sheetDetail->setCellValueExplicit('C' . $detailRow, '481900', DataType::TYPE_STRING);
                    $sheetDetail->setCellValue('D' . $detailRow, $pName);
                    $sheetDetail->setCellValue('E' . $detailRow, $unitCode);
                    $sheetDetail->setCellValue('F' . $detailRow, $price);
                    $sheetDetail->setCellValue('G' . $detailRow, $qty);
                    $sheetDetail->setCellValue('H' . $detailRow, $disc);
                    $sheetDetail->setCellValue('I' . $detailRow, "=G{$detailRow}*F{$detailRow}-H{$detailRow}");

                    if ($kodeTransaksi === '04') {
                        $sheetDetail->setCellValue('J' . $detailRow, "=11/12*I{$detailRow}");
                        $sheetDetail->setCellValue('K' . $detailRow, 12);
                        $sheetDetail->setCellValue('L' . $detailRow, "=J{$detailRow}*K{$detailRow}/100");
                    } else {
                        $sheetDetail->setCellValue('J' . $detailRow, "=I{$detailRow}");
                        $sheetDetail->setCellValue('K' . $detailRow, 0);
                        $sheetDetail->setCellValue('L' . $detailRow, 0);
                    }
                    if ($tarifPpnbm > 0) {
                        $sheetDetail->setCellValue('M' . $detailRow, $tarifPpnbm);
                        $sheetDetail->setCellValue('N' . $detailRow, "=M{$detailRow}*J{$detailRow}/100");
                    } else {
                        $sheetDetail->setCellValue('M' . $detailRow, 0);
                        $sheetDetail->setCellValue('N' . $detailRow, 0);
                    }

                    $detailRow++;
                }
            }

            $fakturIndex++;
            $fakturRow++;
        }

        // Close both sheets with END in column A
        $sheetFaktur->setCellValue('A' . $fakturRow, 'END');
        $sheetDetail->setCellValue('A' . $detailRow, 'END');

        // Save output to temporary file
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir . '/CoreTax_Faktur_' . date('Ymd_His') . '_' . uniqid() . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Map ERP Unit to DJP CoreTax Unit Code.
     */
    public static function mapUnitCode(?string $unitCode, ?string $unitName): string
    {
        $u = strtoupper(trim($unitCode ?? $unitName ?? ''));
        return match ($u) {
            'KG', 'KILOGRAM' => 'UM.0003',
            'GR', 'GRAM' => 'UM.0004',
            'LTR', 'LITER', 'L' => 'UM.0007',
            'MTR', 'METER', 'M' => 'UM.0013',
            'LUSIN', 'DZ', 'DOZEN' => 'UM.0017',
            'UNIT', 'UNT' => 'UM.0018',
            'SET' => 'UM.0019',
            'SHT', 'LEMBAR', 'SHEET' => 'UM.0020',
            'PCS', 'PC', 'PCE', 'PIECE', 'PIECES', 'BUAH' => 'UM.0021',
            'BOX', 'BOKS', 'DUS' => 'UM.0022',
            'DRUM' => 'UM.0036',
            'KARTON', 'CTN' => 'UM.0037',
            'ROLL', 'ROL' => 'UM.0039',
            default => 'UM.0021',
        };
    }
}
