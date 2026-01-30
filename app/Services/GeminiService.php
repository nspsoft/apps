<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $company = \App\Models\Company::first();
        $aiSettings = $company?->settings['ai'] ?? [];

        $this->apiKey = $aiSettings['gemini_api_key'] ?? config('services.gemini.key');
        $this->model = $aiSettings['gemini_model'] ?? 'gemini-2.0-flash';
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Extract PO data from an image or PDF file.
     *
     * @param string $filePath Absolute path to the file
     * @param string $mimeType Mime type of the file
     * @return array|null Extracted data as an associative array
     */
    public function extractPOData(string $filePath, string $mimeType): ?array
    {
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured.');
            return null;
        }

        $fileData = base64_encode(file_get_contents($filePath));

        $prompt = "You are an expert document reader. Analyze this Purchase Order (PO) document carefully.
        
        The document may be in Indonesian or English. Look for:
        - The PO/Order number (labeled as 'PO No', 'No. PO', 'Order No', 'Nomor PO', etc.)
        - The customer/buyer company name (the company SENDING the order, has the letterhead/logo)
        - Date of the PO
        - All line items with quantities and prices
        
        CRITICAL - IDENTIFYING THE CUSTOMER:
        - The CUSTOMER/BUYER is the company with the LETTERHEAD/LOGO at the top of the document
        - The CUSTOMER is sending the PO to buy products
        - 'To:', 'Kepada:', 'Attention:' fields show the SUPPLIER/SELLER, NOT the customer
        - The company name in the HEADER/TOP of document with logo = CUSTOMER (buyer)
        - The company in 'To:' field = SELLER/SUPPLIER (ignore this for customer_name)
        
        Example: If header shows 'PT. HONDA PROSPECT MOTOR' with logo, and 'To: JIDOKA RESULT', 
        then customer_name = 'PT. HONDA PROSPECT MOTOR' (the buyer with letterhead)
        
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"po_number\": \"the PO number or null\",
            \"po_date\": \"YYYY-MM-DD format or null\",
            \"delivery_date\": \"YYYY-MM-DD format or null\",
            \"customer_name\": \"name of the BUYER company (from letterhead/logo)\",
            \"customer_address\": \"address if visible or null\",
            \"items\": [
                {
                    \"description\": \"product name/description\",
                    \"qty\": 100,
                    \"unit\": \"Pcs\",
                    \"unit_price\": 15000,
                    \"total_price\": 1500000
                }
            ]
        }
        
        Return pure JSON without any markdown formatting or backticks.";

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                // Log the raw response for debugging
                Log::info('Gemini API Raw Response: ' . substr($text ?? 'NULL', 0, 500));
                
                if ($text) {
                    // Clean markdown code blocks if present (handle both ```json and just ```)
                    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    $decoded = json_decode($text, true);
                    
                    // Log decoded result
                    Log::info('Gemini Decoded Result: ' . json_encode($decoded));
                    
                    return $decoded;
                }
            } else {
                Log::error('Gemini API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService: ' . $e->getMessage());
        }

        return null;
    }
}
