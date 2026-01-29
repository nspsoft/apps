<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportExportController extends Controller
{
    /**
     * Display Import/Export page
     */
    public function index()
    {
        return Inertia::render('Settings/ImportExport', [
            'dataTypes' => $this->getDataTypes(),
            'stats' => [
                'products' => Product::count(),
                'customers' => Customer::count(),
                'suppliers' => Supplier::count(),

            ],
        ]);
    }

    /**
     * Get available data types for import/export
     */
    private function getDataTypes(): array
    {
        return [
            'products' => [
                'name' => 'Products',
                'icon' => 'cube',
                'description' => 'Master data produk',
                'columns' => ['code', 'name', 'category', 'unit', 'price', 'cost', 'description'],
            ],
            'customers' => [
                'name' => 'Customers',
                'icon' => 'users',
                'description' => 'Data pelanggan',
                'columns' => ['code', 'name', 'email', 'phone', 'address', 'city', 'tax_id'],
            ],
            'suppliers' => [
                'name' => 'Suppliers',
                'icon' => 'truck',
                'description' => 'Data supplier',
                'columns' => ['code', 'name', 'email', 'phone', 'address', 'city', 'tax_id'],
            ],

        ];
    }

    /**
     * Download template for a data type
     */
    public function downloadTemplate(string $type)
    {
        $dataTypes = $this->getDataTypes();
        
        if (!isset($dataTypes[$type])) {
            return back()->with('error', 'Invalid data type');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $columns = $dataTypes[$type]['columns'];
        foreach ($columns as $index => $column) {
            $cell = chr(65 + $index) . '1';
            $sheet->setCellValue($cell, strtoupper($column));
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getColumnDimension(chr(65 + $index))->setAutoSize(true);
        }

        // Add sample data row
        $sampleData = $this->getSampleData($type);
        foreach ($sampleData as $index => $value) {
            $sheet->setCellValue(chr(65 + $index) . '2', $value);
        }

        $filename = "template_{$type}.xlsx";
        $tempPath = storage_path("app/temp/{$filename}");
        
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get sample data for template
     */
    private function getSampleData(string $type): array
    {
        return match($type) {
            'products' => ['PRD-001', 'Sample Product', 'General', 'PCS', '100000', '80000', 'Product description'],
            'customers' => ['CUST-001', 'PT Sample Customer', 'customer@email.com', '021-12345678', 'Jl. Sample No. 1', 'Jakarta', '01.234.567.8-901.000'],
            'suppliers' => ['SUP-001', 'PT Sample Supplier', 'supplier@email.com', '021-87654321', 'Jl. Supplier No. 1', 'Surabaya', '09.876.543.2-109.000'],
            default => [],
        };
    }

    /**
     * Export data to Excel
     */
    public function export(string $type)
    {
        $dataTypes = $this->getDataTypes();
        
        if (!isset($dataTypes[$type])) {
            return back()->with('error', 'Invalid data type');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $columns = $dataTypes[$type]['columns'];
        foreach ($columns as $index => $column) {
            $cell = chr(65 + $index) . '1';
            $sheet->setCellValue($cell, strtoupper($column));
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getColumnDimension(chr(65 + $index))->setAutoSize(true);
        }

        // Get data
        $data = $this->getExportData($type);
        $row = 2;
        
        foreach ($data as $item) {
            foreach ($columns as $index => $column) {
                $value = $item[$column] ?? '';
                $sheet->setCellValue(chr(65 + $index) . $row, $value);
            }
            $row++;
        }

        $filename = "export_{$type}_" . date('Ymd_His') . ".xlsx";
        $tempPath = storage_path("app/temp/{$filename}");
        
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get data for export
     */
    private function getExportData(string $type): array
    {
        return match($type) {
            'products' => Product::with('category')->get()->map(fn($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category?->name ?? '',
                'unit' => $p->unit,
                'price' => $p->selling_price,
                'cost' => $p->cost_price,
                'description' => $p->description,
            ])->toArray(),
            
            'customers' => Customer::all()->map(fn($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'address' => $c->address,
                'city' => $c->city,
                'tax_id' => $c->tax_id,
            ])->toArray(),
            
            'suppliers' => Supplier::all()->map(fn($s) => [
                'code' => $s->code,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'address' => $s->address,
                'city' => $s->city,
                'tax_id' => $s->tax_id,
            ])->toArray(),
                        
            default => [],
        };
    }

    /**
     * Import data from Excel
     */
    public function import(Request $request, string $type)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $dataTypes = $this->getDataTypes();
        
        if (!isset($dataTypes[$type])) {
            return back()->with('error', 'Invalid data type');
        }

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Get headers from first row
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            unset($rows[0]); // Remove header row
            
            $imported = 0;
            $errors = [];
            
            DB::beginTransaction();
            
            foreach ($rows as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $data = [];
                foreach ($headers as $index => $header) {
                    $data[$header] = $row[$index] ?? null;
                }
                
                try {
                    $this->importRow($type, $data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "Successfully imported {$imported} records.";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " rows had errors.";
            }
            
            return back()->with('success', $message)->with('importErrors', $errors);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Import failed: " . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import a single row
     */
    private function importRow(string $type, array $data): void
    {
        switch ($type) {
            case 'products':
                $category = null;
                if (!empty($data['category'])) {
                    $category = ProductCategory::firstOrCreate(['name' => $data['category']]);
                }
                
                Product::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'category_id' => $category?->id,
                        'unit' => $data['unit'] ?? 'PCS',
                        'selling_price' => $data['price'] ?? 0,
                        'cost_price' => $data['cost'] ?? 0,
                        'description' => $data['description'] ?? null,
                    ]
                );
                break;
                
            case 'customers':
                Customer::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'address' => $data['address'] ?? null,
                        'city' => $data['city'] ?? null,
                        'tax_id' => $data['tax_id'] ?? null,
                    ]
                );
                break;
                
            case 'suppliers':
                Supplier::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'address' => $data['address'] ?? null,
                        'city' => $data['city'] ?? null,
                        'tax_id' => $data['tax_id'] ?? null,
                    ]
                );
                break;
                
        }
    }

    /**
     * Preview import data
     */
    public function preview(Request $request, string $type)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            unset($rows[0]);
            
            $data = [];
            $count = 0;
            
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                if ($count >= 10) break; // Limit preview to 10 rows
                
                $item = [];
                foreach ($headers as $index => $header) {
                    $item[$header] = $row[$index] ?? null;
                }
                $data[] = $item;
                $count++;
            }
            
            return response()->json([
                'headers' => $headers,
                'data' => $data,
                'totalRows' => count(array_filter($rows, fn($r) => !empty(array_filter($r)))),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
