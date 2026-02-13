<?php

use App\Models\Company;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- DEBUGGING COMPANY MISMATCH ---\n";

// 1. List Companies
$companies = Company::all();
echo "Companies Found: " . $companies->count() . "\n";
foreach ($companies as $c) {
    echo "ID: {$c->id}, Name: {$c->name}\n";
}

// 2. Check Sales Orders
$ordersCount = SalesOrder::count();
echo "\nTotal Sales Orders: $ordersCount\n";
if ($ordersCount > 0) {
    $firstOrder = SalesOrder::with('company')->first();
    echo "First Order ID: {$firstOrder->id}, Company ID: {$firstOrder->company_id}, Company Name: " . ($firstOrder->company->name ?? 'N/A') . "\n";
    
    // Group by Company ID
    $ordersByCompany = SalesOrder::select('company_id', DB::raw('count(*) as total'))
        ->groupBy('company_id')
        ->get();
        
    echo "Orders Distribution by Company:\n";
    foreach ($ordersByCompany as $row) {
        $compName = Company::find($row->company_id)?->name ?? 'Unknown';
        echo "- Company ID {$row->company_id} ($compName): {$row->total} orders\n";
    }
}

// 3. Check Users
$users = User::whereIn('email', ['admin@jicos.com', 'admin@jidoka.co.id', 'test@example.com', 'admin@manufacturing.id'])->get(); // Included email from DemoDataSeeder
echo "\nUsers Checked:\n";
foreach ($users as $u) {
    echo "User: {$u->email}, Company ID: {$u->company_id}, Name: {$u->name}\n";
}

// 4. Check Admin User (usually ID 1)
$admin = User::find(1);
if ($admin) {
    echo "User ID 1: {$admin->email}, Company ID: {$admin->company_id}\n";
}

echo "--- END DEBUG ---\n";
