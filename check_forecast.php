<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. List all customers in forecast table
$fcCustomers = App\Models\SalesForecast::with('customer')
    ->get()
    ->pluck('customer.name')
    ->unique()
    ->sort()
    ->values();

echo "=== Customers WITH forecast data ===\n";
foreach ($fcCustomers as $i => $name) {
    echo ($i+1) . ". {$name}\n";
}

// 2. Check if Mitsubishi exists as customer
echo "\n=== Mitsubishi in customers table ===\n";
$mitsubishi = App\Models\Customer::where('name', 'like', '%Mitsubishi%')->get();
foreach ($mitsubishi as $c) {
    echo "ID: {$c->id}, Name: {$c->name}\n";
}

// 3. Check if there are SalesOrders for Mitsubishi
if ($mitsubishi->count() > 0) {
    $mid = $mitsubishi->first()->id;
    $soCount = App\Models\SalesOrder::where('customer_id', $mid)->count();
    echo "\nSalesOrders for Mitsubishi: {$soCount}\n";
    
    $fcCount = App\Models\SalesForecast::where('customer_id', $mid)->count();
    echo "Forecasts for Mitsubishi: {$fcCount}\n";
}
