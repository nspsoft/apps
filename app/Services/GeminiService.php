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
        $aiSettings = $company->settings['ai'] ?? [];

        $this->apiKey = $aiSettings['gemini_api_key'] ?? config('services.gemini.key');
        $this->model = $aiSettings['gemini_model'] ?? 'gemini-1.5-flash';
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

        $prompt = "Extract the following information from this Purchase Order document and return ONLY a valid JSON object:
        - po_number (The Purchase Order number from the customer)
        - po_date (The date of the PO, format YYYY-MM-DD or null if not found)
        - customer_name (The name of the customer/company sending the PO)
        - customer_address (The address of the customer if visible)
        - items (An array of objects containing:)
            - description (Product name or description)
            - qty (Quantity as a number)
            - unit (UOM if specified, e.g. pcs, kg, m)
            - unit_price (Price per unit as a number, without currency symbol)
            - total_price (Total price for this line as a number)
        
        Return pure JSON without markdown backticks.";

        try {
            $response = Http::post("{$this->baseUrl}?key={$this->apiKey}", [
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
                
                if ($text) {
                    return json_decode($text, true);
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
