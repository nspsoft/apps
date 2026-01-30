<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class POImportController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Handle the PO upload and AI processing.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB limit
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $mimeType = $file->getMimeType();

        // 1. Extract data using AI
        $extractedData = $this->gemini->extractPOData($path, $mimeType);

        if (!$extractedData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract data from PO using AI. Please check your API configuration.'
            ], 422);
        }

        // 2. Intelligent Auto-Matching
        $processedData = $this->autoMatchData($extractedData);

        return response()->json([
            'success' => true,
            'data' => $processedData
        ]);
    }

    /**
     * Match extracted text with database records.
     */
    private function autoMatchData(array $data): array
    {
        // Match Customer
        $customerName = $data['customer_name'] ?? '';
        $customer = Customer::where('name', 'like', "%{$customerName}%")
            ->orWhere('code', 'like', "%{$customerName}%")
            ->first();

        $data['matched_customer_id'] = $customer?->id;
        $data['matched_customer_name'] = $customer?->name;

        // Match Products
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                $description = $item['description'] ?? '';
                
                // Try to find matching product by SKU or Name
                $product = Product::active()
                    ->where(function($q) use ($description) {
                        $q->where('sku', 'like', "%{$description}%")
                          ->orWhere('name', 'like', "%{$description}%");
                    })
                    ->first();

                $item['matched_product_id'] = $product?->id;
                $item['matched_product_name'] = $product?->name;
                $item['matched_sku'] = $product?->sku;
                $item['current_stock'] = $product?->current_stock ?? 0; // Available stock from product master
                
                // Store both prices for comparison
                $aiPrice = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
                $dbPrice = $product?->selling_price ?? $product?->price ?? 0;
                
                $item['po_price'] = $aiPrice; // Price from PO document (AI extracted)
                $item['db_price'] = floatval($dbPrice); // Price from database (Selling Price)
                // Keep the AI price as unit_price for editing - don't overwrite with db_price
                // This way, user sees the original PO price and can adjust as needed
                $item['unit_price'] = $aiPrice; 
                $item['price_mismatch'] = $aiPrice > 0 && $dbPrice > 0 && abs($aiPrice - $dbPrice) > 0.01;
            }
        }

        return $data;
    }
}
