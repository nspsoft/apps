<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SalesOrder;
use App\Http\Controllers\Sales\SalesOrderController;

$order = SalesOrder::find(473);
if (!$order) {
    echo "Order not found!" . PHP_EOL;
    exit;
}

echo "Order found: " . $order->so_number . " with status: " . $order->status . PHP_EOL;

try {
    // Run the cancel logic
    $controller = app(SalesOrderController::class);
    $response = $controller->cancel($order);
    
    // Refresh and check status
    $order->refresh();
    echo "Cancellation run complete!" . PHP_EOL;
    echo "New status: " . $order->status . PHP_EOL;
} catch (\Throwable $e) {
    echo "CRASH DETECTED: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
