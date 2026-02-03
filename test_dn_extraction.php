<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

$file = 'C:\Users\SPINDO_Digitalisasi\Downloads\Surat Jalan - DO_20260201_0048.pdf';

if (!file_exists($file)) {
    echo "Error: File not found at $file\n";
    exit(1);
}

echo "Testing AI Extraction for: $file\n";
echo "File Size: " . filesize($file) . " bytes\n";
echo "Mime Type: " . mime_content_type($file) . "\n\n";

$service = new GeminiService();
$result = $service->extractDeliveryNoteData($file, mime_content_type($file));

if ($result) {
    echo "SUCCESS! Extracted Data:\n";
    print_r($result);
} else {
    echo "FAILED to extract data.\n";
    echo "Check laravel.log for details.\n";
}
