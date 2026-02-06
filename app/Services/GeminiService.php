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

    /**
     * Extract Delivery Note (Surat Jalan) data from an image or PDF.
     *
     * @param string $filePath Absolute path to the file
     * @param string $mimeType Mime type of the file
     * @return array|null Extracted data as an associative array
     */
    public function extractDeliveryNoteData(string $filePath, string $mimeType): ?array
    {
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured.');
            return null;
        }

        $fileData = base64_encode(file_get_contents($filePath));

        $prompt = "You are an expert document reader. Analyze this Delivery Note (Surat Jalan/DO) document carefully.
        
        The document is likely in Indonesian or English. Look for:
        - The SUPPLIER/SENDER name (company with the letterhead/logo at the top)
        - The PO Number reference (labeled as 'Ref PO', 'Your Order', 'No. PO', etc.)
        - The Delivery Note Number (No. Surat Jalan)
        - Date of the document
        - All line items with quantities and units (Description, Qty, Unit)
        
        CRITICAL - IDENTIFYING THE SUPPLIER:
        - The SUPPLIER is the company sending the goods (Letterhead/Logo at top).
        - The 'To:' field is likely 'PT. SPINDO' (the receiver), do NOT extract that as supplier.
        
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"supplier_name\": \"name of the SUPPLIER (from letterhead)\",
            \"dn_number\": \"Delivery Note / Surat Jalan Number\",
            \"po_number\": \"The referenced PO Number if visible, or null\",
            \"date\": \"YYYY-MM-DD format or null\",
            \"items\": [
                {
                    \"description\": \"product name/description\",
                    \"qty\": 100,
                    \"unit\": \"Pcs\",
                    \"remarks\": \"any notes if available\"
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
                
                Log::info('Gemini API Raw Response (DN): ' . substr($text ?? 'NULL', 0, 500));
                
                if ($text) {
                    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    $decoded = json_decode($text, true);
                    return $decoded;
                }
            } else {
                Log::error('Gemini API Error (DN): ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (DN): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Analyze customer intent from WhatsApp message
     */
    public function analyzeCustomerIntent(string $message, ?array $customerContext = null): array
    {
        if (!$this->apiKey) {
            return ['intent' => 'unknown', 'parameters' => []];
        }

        $contextInfo = $customerContext ? "Customer Name: {$customerContext['name']}" : "Unknown customer";

        $prompt = "You are a customer service intent classifier for PT SPINDO, a steel pipe manufacturing company.

Analyze this customer message and classify the intent.

Customer Context: {$contextInfo}
Message: \"{$message}\"

Possible intents:
- greeting: Customer is saying hello or greeting
- order_status: Customer wants to check order/shipment status
- invoice_check: Customer wants to check invoice/payment/tagihan
- product_catalog: Customer asking about products/prices
- faq: General questions about company, business hours, address, etc
- unknown: Cannot determine intent

Extract any relevant parameters like order numbers (SO-xxx, PO-xxx format).

Return ONLY a valid JSON object:
{
    \"intent\": \"one of the intents above\",
    \"parameters\": {
        \"order_number\": \"extracted order number or null\",
        \"product_name\": \"extracted product name or null\"
    },
    \"confidence\": 0.0 to 1.0
}";

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    return json_decode($text, true) ?? ['intent' => 'unknown', 'parameters' => []];
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini Intent Analysis Error: ' . $e->getMessage());
        }

        return ['intent' => 'unknown', 'parameters' => []];
    }

    /**
     * Generate FAQ response using AI
     */
    public function generateFAQResponse(string $question): string
    {
        if (!$this->apiKey) {
            return "Maaf, saya tidak bisa memproses pertanyaan Anda saat ini. Silakan hubungi CS kami di 021-xxx-xxxx.";
        }

        $prompt = "You are a helpful customer service assistant for PT SPINDO, a leading steel pipe manufacturer in Indonesia.

Company Info:
- Name: PT SPINDO Tbk
- Industry: Steel pipe manufacturing
- Products: Steel pipes, galvanized pipes, ERW pipes
- Business Hours: Monday-Friday 08:00-17:00 WIB
- Address: Jl. Kalibutuh 189-191, Surabaya
- Phone: 031-5321234

Answer this customer question in Indonesian, friendly and concise (max 200 chars):
Question: \"{$question}\"

If you don't know the answer, politely suggest contacting sales team.";

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    return trim($text);
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini FAQ Response Error: ' . $e->getMessage());
        }

        return "Terima kasih atas pertanyaan Anda. Untuk informasi lebih lanjut, silakan hubungi tim sales kami di 021-xxx-xxxx atau email ke sales@spindo.co.id";
    }
}
