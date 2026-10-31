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
                // Inject customer product aliases if available
                if ($cust) {
                    foreach ($inv->items as $item) {
                        if ($item->product && !isset($item->product_alias_name)) {
                            $alias = $item->product->getAliasFor($cust);
                            $item->product_alias_name = $alias?->alias_name;
                            $item->product_alias_sku = $alias?->alias_sku;
                        }
                    }
                }

                // Group items by Faktur line (consolidating partial deliveries / DO details into single invoice items)
                $groupedItems = $inv->items->groupBy(function ($item) {
                    if ($item->sales_order_item_id) {
                        return 'so-' . $item->sales_order_item_id;
                    }
                    if ($item->product_id) {
                        return 'prod-' . $item->product_id . '-' . (string)$item->unit_price;
                    }
                    return 'item-' . $item->id;
                });

                foreach ($groupedItems as $groupId => $items) {
                    $firstItem = $items->first();
                    $pName = $firstItem->product_alias_name ?? $firstItem->product?->name ?? $firstItem->description ?? 'Item';
                    $unitCode = self::mapUnitCode($firstItem->unit?->code, $firstItem->unit?->name);

                    $qty = (float) $items->sum('qty');
                    $price = (float) ($firstItem->unit_price ?: 0);
                    $disc = (float) $items->sum('discount_amount');

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
     * Generate CoreTax XML (TaxInvoiceBulk) from an invoice collection or query.
     *
     * @param iterable $invoices
     * @param array $options
     * @return string Path to temporary generated .xml file
     */
    public function generateXml(iterable $invoices, array $options = []): string
    {
        $sellerNpwp = preg_replace('/[^0-9]/', '', $options['seller_npwp'] ?? '0809672348413000');
        $sellerNitku = preg_replace('/[^0-9]/', '', $options['seller_nitku'] ?? '0809672348413000000000');
        $barangJasaType = $options['barang_jasa'] ?? 'A';
        $selectedKodeTransaksi = $options['kode_transaksi'] ?? '04';
        $tarifPpnbm = (float) ($options['tarif_ppnbm'] ?? 0);
        $keterangan07 = $options['keterangan_tambahan_07'] ?? 'TD.00502';
        $capFasilitas07 = $options['cap_fasilitas_07'] ?? 'TD.01103';

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('TaxInvoiceBulk');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', 'TaxInvoice.xsd');
        $dom->appendChild($root);

        $tin = $dom->createElement('TIN', $sellerNpwp);
        $root->appendChild($tin);

        $listOfTaxInvoice = $dom->createElement('ListOfTaxInvoice');
        $root->appendChild($listOfTaxInvoice);

        foreach ($invoices as $inv) {
            $cust = $inv->customer ?? $inv->salesOrder?->customer;
            $taxAmount = (float) $inv->tax_amount;

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

            $ketTambahan = ($kodeTransaksi === '07' || $kodeTransaksi === '08') ? $keterangan07 : '';
            $capFasilitas = ($kodeTransaksi === '07' || $kodeTransaksi === '08') ? $capFasilitas07 : '';

            $invDate = $inv->invoice_date instanceof Carbon 
                ? $inv->invoice_date->format('Y-m-d') 
                : ($inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('Y-m-d') : date('Y-m-d'));

            $taxInvoice = $dom->createElement('TaxInvoice');
            $listOfTaxInvoice->appendChild($taxInvoice);

            $taxInvoice->appendChild($dom->createElement('TaxInvoiceDate', $invDate));
            $taxInvoice->appendChild($dom->createElement('TaxInvoiceOpt', 'Normal'));
            $taxInvoice->appendChild($dom->createElement('TrxCode', $kodeTransaksi));
            $taxInvoice->appendChild($dom->createElement('AddInfo', $ketTambahan));
            $taxInvoice->appendChild($dom->createElement('CustomDoc', ''));
            $taxInvoice->appendChild($dom->createElement('CustomDocMonthYear', ''));
            $taxInvoice->appendChild($dom->createElement('RefDesc', $inv->invoice_number));
            $taxInvoice->appendChild($dom->createElement('FacilityStamp', $capFasilitas));
            $taxInvoice->appendChild($dom->createElement('SellerIDTKU', $sellerNitku));

            $taxInvoice->appendChild($dom->createElement('BuyerTin', $buyerId));
            $taxInvoice->appendChild($dom->createElement('BuyerDocument', $buyerType));
            $taxInvoice->appendChild($dom->createElement('BuyerCountry', 'IDN'));
            $taxInvoice->appendChild($dom->createElement('BuyerDocumentNumber', $buyerDocNo));
            $taxInvoice->appendChild($dom->createElement('BuyerName', $cust?->name ?? 'Customer'));
            $taxInvoice->appendChild($dom->createElement('BuyerAdress', $cust?->full_address ?? $cust?->address ?? '-'));
            $taxInvoice->appendChild($dom->createElement('BuyerEmail', $cust?->email ?? ''));
            $taxInvoice->appendChild($dom->createElement('BuyerIDTKU', $buyerNitku));

            $listOfGoodService = $dom->createElement('ListOfGoodService');
            $taxInvoice->appendChild($listOfGoodService);

            // Group items by Faktur line (consolidating partial deliveries / DO details into single invoice items)
            if ($inv->items->isEmpty()) {
                $subtotal = (float) $inv->subtotal;
                $disc = (float) $inv->discount_amount;
                $taxBase = round($subtotal - $disc, 2);
                $otherTaxBase = ($kodeTransaksi === '04') ? round((11 / 12) * $taxBase, 2) : $taxBase;
                $vatRate = ($kodeTransaksi === '04') ? 12 : 0;
                $vat = ($kodeTransaksi === '04') ? round($otherTaxBase * $vatRate / 100, 2) : 0;
                $stlg = ($tarifPpnbm > 0) ? round($otherTaxBase * $tarifPpnbm / 100, 2) : 0;

                $itemEl = $dom->createElement('GoodService');
                $itemEl->appendChild($dom->createElement('Opt', $barangJasaType));
                $itemEl->appendChild($dom->createElement('Code', '481900'));
                $itemEl->appendChild($dom->createElement('Name', 'Penjualan Barang / Jasa ' . $inv->invoice_number));
                $itemEl->appendChild($dom->createElement('Unit', 'UM.0021'));
                $itemEl->appendChild($dom->createElement('Price', number_format($subtotal, 2, '.', '')));
                $itemEl->appendChild($dom->createElement('Qty', '1'));
                $itemEl->appendChild($dom->createElement('TotalDiscount', number_format($disc, 2, '.', '')));
                $itemEl->appendChild($dom->createElement('TaxBase', number_format($taxBase, 2, '.', '')));
                $itemEl->appendChild($dom->createElement('OtherTaxBase', number_format($otherTaxBase, 2, '.', '')));
                $itemEl->appendChild($dom->createElement('VATRate', (string) $vatRate));
                $itemEl->appendChild($dom->createElement('VAT', number_format($vat, 2, '.', '')));
                $itemEl->appendChild($dom->createElement('STLGRate', (string) $tarifPpnbm));
                $itemEl->appendChild($dom->createElement('STLG', number_format($stlg, 2, '.', '')));
                $listOfGoodService->appendChild($itemEl);
            } else {
                // Inject customer product aliases if available
                if ($cust) {
                    foreach ($inv->items as $item) {
                        if ($item->product && !isset($item->product_alias_name)) {
                            $alias = $item->product->getAliasFor($cust);
                            $item->product_alias_name = $alias?->alias_name;
                            $item->product_alias_sku = $alias?->alias_sku;
                        }
                    }
                }

                $groupedItems = $inv->items->groupBy(function ($item) {
                    if ($item->sales_order_item_id) {
                        return 'so-' . $item->sales_order_item_id;
                    }
                    if ($item->product_id) {
                        return 'prod-' . $item->product_id . '-' . (string)$item->unit_price;
                    }
                    return 'item-' . $item->id;
                });

                foreach ($groupedItems as $groupId => $items) {
                    $firstItem = $items->first();
                    $pName = $firstItem->product_alias_name ?? $firstItem->product?->name ?? $firstItem->description ?? 'Item';
                    $unitCode = self::mapUnitCode($firstItem->unit?->code, $firstItem->unit?->name);

                    $qty = (float) $items->sum('qty');
                    $price = (float) ($firstItem->unit_price ?: 0);
                    $disc = (float) $items->sum('discount_amount');

                    $taxBase = round($qty * $price - $disc, 2);
                    $otherTaxBase = ($kodeTransaksi === '04') ? round((11 / 12) * $taxBase, 2) : $taxBase;
                    $vatRate = ($kodeTransaksi === '04') ? 12 : 0;
                    $vat = ($kodeTransaksi === '04') ? round($otherTaxBase * $vatRate / 100, 2) : 0;
                    $stlg = ($tarifPpnbm > 0) ? round($otherTaxBase * $tarifPpnbm / 100, 2) : 0;

                    $itemEl = $dom->createElement('GoodService');
                    $itemEl->appendChild($dom->createElement('Opt', $barangJasaType));
                    $itemEl->appendChild($dom->createElement('Code', '481900'));
                    $itemEl->appendChild($dom->createElement('Name', $pName));
                    $itemEl->appendChild($dom->createElement('Unit', $unitCode));
                    $itemEl->appendChild($dom->createElement('Price', number_format($price, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('Qty', (float)$qty == (int)$qty ? (string)(int)$qty : number_format($qty, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('TotalDiscount', number_format($disc, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('TaxBase', number_format($taxBase, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('OtherTaxBase', number_format($otherTaxBase, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('VATRate', (string) $vatRate));
                    $itemEl->appendChild($dom->createElement('VAT', number_format($vat, 2, '.', '')));
                    $itemEl->appendChild($dom->createElement('STLGRate', (string) $tarifPpnbm));
                    $itemEl->appendChild($dom->createElement('STLG', number_format($stlg, 2, '.', '')));
                    $listOfGoodService->appendChild($itemEl);
                }
            }
        }

        // Save output to temporary file
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir . '/CoreTax_Faktur_' . date('Ymd_His') . '_' . uniqid() . '.xml';
        $dom->save($outputPath);

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
