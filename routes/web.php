<?php

use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\PayrollSettingController;

// Public Payroll Validation (No login required)
Route::get('/v/p/{uuid}', [PayrollController::class, 'publicValidate'])->name('payroll.public-validate');
Route::get('/v/pr/{uuid}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'publicValidate'])->name('purchasing.requests.public-validate');
Route::get('/v/po/{uuid}', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'publicValidate'])->name('purchasing.orders.public-validate');
Route::get('/v/grn/{uuid}', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'publicValidate'])->name('purchasing.receipts.public-validate');
Route::get('/v/wo/{uuid}', [App\Http\Controllers\Manufacturing\WorkOrderController::class, 'publicValidate'])->name('manufacturing.work-orders.public-validate');
Route::get('/v/sco/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidate'])->name('manufacturing.subcontract-orders.public-validate');
Route::get('/v/scsj/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidateSJ'])->name('manufacturing.subcontract-orders.public-validate-sj');
Route::get('/v/scgr/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidateGRN'])->name('manufacturing.subcontract-orders.public-validate-grn');
Route::get('/v/q/{uuid}', [App\Http\Controllers\Sales\QuotationController::class, 'publicValidate'])->name('sales.quotations.public-validate');
Route::get('/v/inv/{uuid}', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'publicValidate'])->name('sales.invoices.public-validate');
Route::get('/v/do/{uuid}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'publicValidate'])->name('sales.deliveries.public-validate');
Route::get('/v/ret/{uuid}', [App\Http\Controllers\Sales\SalesReturnController::class, 'publicValidate'])->name('sales.returns.public-validate');
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Manufacturing\BomController;
use App\Http\Controllers\Manufacturing\MachineController;
use App\Http\Controllers\Manufacturing\RoutingController;
use App\Http\Controllers\Manufacturing\ShiftController;
use App\Http\Controllers\Manufacturing\SubcontractOrderController;
use App\Http\Controllers\Manufacturing\WorkOrderController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchaseReturnController;
use App\Http\Controllers\Purchasing\SupplierController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\SalesReturnController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register'])->name('register');

// Dashboard
Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

// Inventory Module
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    Route::resource('categories', App\Http\Controllers\Inventory\CategoryController::class);
    Route::get('/stocks', [App\Http\Controllers\Inventory\CurrentStockController::class, 'index'])->name('stocks.index');
    Route::resource('products', ProductController::class);
    Route::get('/products-export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products-import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products-template', [ProductController::class, 'template'])->name('products.template');
    Route::resource('warehouses', WarehouseController::class);

    Route::get('/movements', [App\Http\Controllers\Inventory\StockMovementController::class, 'index'])->name('movements.index');

    // Stock Adjustments
    Route::resource('adjustments', App\Http\Controllers\Inventory\StockAdjustmentController::class);
    Route::post('/adjustments/{adjustment}/complete', [App\Http\Controllers\Inventory\StockAdjustmentController::class, 'complete'])->name('adjustments.complete');
    Route::get('/stock-check', [App\Http\Controllers\Inventory\StockAdjustmentController::class, 'getStock'])->name('stock.check');

    // Stock Opname
    Route::resource('opname', App\Http\Controllers\Inventory\StockOpnameController::class);
    Route::post('/opname/{opname}/populate', [App\Http\Controllers\Inventory\StockOpnameController::class, 'populate'])->name('opname.populate');
    Route::put('/opname/{opname}/items', [App\Http\Controllers\Inventory\StockOpnameController::class, 'updateItems'])->name('opname.update-items');
    Route::post('/opname/{opname}/complete', [App\Http\Controllers\Inventory\StockOpnameController::class, 'complete'])->name('opname.complete');
    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/inventory-balance', [App\Http\Controllers\ReportController::class, 'inventoryBalance'])->name('reports.inventory-balance');
    Route::get('/reports/stock-card', [App\Http\Controllers\ReportController::class, 'stockCard'])->name('reports.stock-card');
    Route::get('/reports/sales-summary', [App\Http\Controllers\ReportController::class, 'salesSummary'])->name('reports.sales-summary');
    Route::get('/reports/purchase-summary', [App\Http\Controllers\ReportController::class, 'purchaseSummary'])->name('reports.purchase-summary');
    Route::get('/reports/production-summary', [App\Http\Controllers\ReportController::class, 'productionSummary'])->name('reports.production-summary');

    Route::get('/reports/export/sales', [App\Http\Controllers\ReportController::class, 'exportSales'])->name('reports.export.sales');
    Route::get('/reports/export/purchase', [App\Http\Controllers\ReportController::class, 'exportPurchase'])->name('reports.export.purchase');
    Route::get('/reports/export/production', [App\Http\Controllers\ReportController::class, 'exportProduction'])->name('reports.export.production');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Purchasing Module
Route::prefix('purchasing')->name('purchasing.')->middleware(['auth'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers-export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::post('/suppliers-import', [SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('/suppliers-contacts-export', [SupplierController::class, 'exportContacts'])->name('suppliers.contacts.export');
    Route::post('/suppliers-contacts-import', [SupplierController::class, 'importContacts'])->name('suppliers.contacts.import');
    Route::get('/suppliers-template', [SupplierController::class, 'template'])->name('suppliers.template');
    Route::get('/suppliers-contacts-template', [SupplierController::class, 'templateContacts'])->name('suppliers.contacts.template');
    Route::resource('orders', PurchaseOrderController::class);
    Route::post('/orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('orders.submit');
    Route::post('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])->name('orders.mark-ordered');
    Route::post('/orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
    Route::put('/orders/items/{item}/qty', [PurchaseOrderController::class, 'updateItemQty'])->name('orders.update-item-qty');
    Route::get('/orders/{order}/print', [PurchaseOrderController::class, 'print'])->name('orders.print');

    Route::get('/requests/create', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{request}/edit', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'edit'])->name('requests.edit');
    Route::put('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'update'])->name('requests.update');
    Route::delete('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'destroy'])->name('requests.destroy');
    Route::get('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{request}/approve', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{request}/reject', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'reject'])->name('requests.reject');
    Route::get('/requests/{request}/print', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'print'])->name('requests.print');
    Route::get('/requests', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'index'])->name('requests.index');
    Route::get('/receipts/po-items/{order}', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'getPoItems'])->name('receipts.po-items');
    Route::resource('receipts', App\Http\Controllers\Purchasing\GoodsReceiptController::class);
    Route::post('/receipts/{receipt}/complete', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'complete'])->name('receipts.complete');
    Route::get('/receipts/{receipt}/print', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'print'])->name('receipts.print');
    Route::get('/returns/po-items/{order}', [PurchaseReturnController::class, 'getReturnableItems'])->name('purchase-returns.po-items');
    Route::resource('returns', PurchaseReturnController::class)->names([
        'index' => 'purchase-returns.index',
        'create' => 'purchase-returns.create',
        'store' => 'purchase-returns.store',
        'show' => 'purchase-returns.show',
        'update' => 'purchase-returns.update',
        'destroy' => 'purchase-returns.destroy',
        'edit' => 'purchase-returns.edit',
    ]);
    Route::post('/returns/{purchase_return}/confirm', [PurchaseReturnController::class, 'confirm'])->name('purchase-returns.confirm');

    // Purchase Invoices
    Route::resource('invoices', App\Http\Controllers\Purchasing\PurchaseInvoiceController::class);
    Route::post('/invoices/{invoice}/payment', [App\Http\Controllers\Purchasing\PurchaseInvoiceController::class, 'recordPayment'])->name('invoices.payment');
    Route::delete('/invoices/{invoice}/payment/{payment}', [App\Http\Controllers\Purchasing\PurchaseInvoiceController::class, 'deletePayment'])->name('invoices.payment.delete');
});

// Sales Module
Route::prefix('sales')->name('sales.')->middleware(['auth'])->group(function () {
    Route::resource('customers', CustomerController::class);
    Route::get('/customers-export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('/customers-import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/customers-contacts-export', [CustomerController::class, 'exportContacts'])->name('customers.contacts.export');
    Route::post('/customers-contacts-import', [CustomerController::class, 'importContacts'])->name('customers.contacts.import');
    Route::get('/customers-template', [CustomerController::class, 'template'])->name('customers.template');
    Route::get('/customers-contacts-template', [CustomerController::class, 'templateContacts'])->name('customers.contacts.template');
    Route::resource('orders', SalesOrderController::class);
    Route::post('/orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');
    Route::put('/orders/items/{item}/qty', [SalesOrderController::class, 'updateItemQty'])->name('orders.update-item-qty');
    Route::get('/orders/{order}/print', [SalesOrderController::class, 'print'])->name('orders.print');
    Route::post('/orders/{order}/delivery', [SalesOrderController::class, 'createDelivery'])->name('orders.create-delivery');
    Route::post('/orders/{order}/invoice', [SalesOrderController::class, 'createInvoice'])->name('orders.create-invoice');

    Route::resource('quotations', App\Http\Controllers\Sales\QuotationController::class);
    Route::post('/quotations/{quotation}/send', [App\Http\Controllers\Sales\QuotationController::class, 'send'])->name('quotations.send');
    Route::post('/quotations/{quotation}/accept', [App\Http\Controllers\Sales\QuotationController::class, 'accept'])->name('quotations.accept');
    Route::post('/quotations/{quotation}/reject', [App\Http\Controllers\Sales\QuotationController::class, 'reject'])->name('quotations.reject');
    Route::get('/quotations/{quotation}/print', [App\Http\Controllers\Sales\QuotationController::class, 'print'])->name('quotations.print');
    Route::post('/quotations/{quotation}/convert', [App\Http\Controllers\Sales\QuotationController::class, 'convertToSO'])->name('quotations.convert');
    Route::get('/deliveries', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/{delivery_order}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'show'])->name('deliveries.show');
    Route::put('/deliveries/{delivery_order}/items', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'updateItems'])->name('deliveries.update-items');
    Route::delete('/deliveries/items/{item}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'destroyItem'])->name('deliveries.destroy-item');
    Route::delete('/deliveries/{delivery_order}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'destroy'])->name('deliveries.destroy');
    Route::post('/deliveries/{delivery_order}/complete', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'complete'])->name('deliveries.complete');
    Route::get('/deliveries/{delivery_order}/print', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'print'])->name('deliveries.print');
    Route::post('/deliveries/{delivery_order}/invoice', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'createInvoice'])->name('deliveries.create-invoice');
    Route::get('/invoices', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{sales_invoice}', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{sales_invoice}/confirm', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'confirm'])->name('invoices.confirm');
    Route::post('/invoices/{sales_invoice}/pay', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'recordPayment'])->name('invoices.pay');
    Route::get('/invoices/{sales_invoice}/print', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{sales_invoice}/print-v2', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'printV2'])->name('invoices.print-v2');
    Route::get('/returns/so-items/{sales_order}', [App\Http\Controllers\Sales\SalesReturnController::class, 'getReturnableItems'])->name('returns.so-items');
    Route::get('/returns/{sales_return}/print', [App\Http\Controllers\Sales\SalesReturnController::class, 'print'])->name('returns.print');
    Route::resource('returns', SalesReturnController::class);
    Route::post('/returns/{sales_return}/confirm', [SalesReturnController::class, 'confirm'])->name('returns.confirm');
});

// Manufacturing Module
Route::prefix('manufacturing')->name('manufacturing.')->middleware(['auth'])->group(function () {
    Route::resource('boms', BomController::class);
    Route::post('/boms/{bom}/activate', [BomController::class, 'activate'])->name('boms.activate');
    Route::post('/boms/{bom}/archive', [BomController::class, 'archive'])->name('boms.archive');

    Route::resource('work-orders', WorkOrderController::class);
    Route::post('/work-orders/{workOrder}/confirm', [WorkOrderController::class, 'confirm'])->name('work-orders.confirm');
    Route::post('/work-orders/{workOrder}/revert-to-draft', [WorkOrderController::class, 'revertToDraft'])->name('work-orders.revert-to-draft');
    Route::post('/work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
    Route::post('/work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
    Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
    Route::get('/work-orders/{workOrder}/record-production', [WorkOrderController::class, 'recordProductionForm'])->name('work-orders.record-production-form');
    Route::post('/work-orders/{workOrder}/record-production', [WorkOrderController::class, 'recordProduction'])->name('work-orders.record-production');
    
    Route::get('/production-entry', [WorkOrderController::class, 'productionEntryIndex'])->name('production-entry.index');
    Route::resource('shifts', ShiftController::class);
    Route::resource('machines', MachineController::class);
    Route::resource('subcontract-orders', SubcontractOrderController::class);
    Route::post('/subcontract-orders/{subcontractOrder}/dispatch', [SubcontractOrderController::class, 'dispatchMaterials'])->name('subcontract-orders.dispatch');
    Route::post('/subcontract-orders/{subcontractOrder}/return-materials', [SubcontractOrderController::class, 'returnMaterials'])->name('subcontract-orders.return-materials');
    Route::post('/subcontract-orders/{subcontractOrder}/receive', [SubcontractOrderController::class, 'receiveGoods'])->name('subcontract-orders.receive');
    Route::get('/subcontract-orders/{subcontractOrder}/print', [SubcontractOrderController::class, 'print'])->name('subcontract-orders.print');
    Route::get('/subcontract-orders/{subcontractOrder}/print-delivery-note', [SubcontractOrderController::class, 'printDeliveryNote'])->name('subcontract-orders.print-delivery-note');
    Route::get('/subcontract-orders/{movement}/print-grn', [SubcontractOrderController::class, 'printGrn'])->name('subcontract-orders.print-grn');

    Route::get('/production', [\App\Http\Controllers\Manufacturing\ProductionController::class, 'index'])->name('production.index');
    Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');
});

// Quality Control
Route::middleware(['auth'])->prefix('qc')->name('qc.')->group(function () {
    Route::get('/incoming', fn () => Inertia::render('Blueprints/QC', ['title' => 'Incoming Inspection']))->name('incoming');
    Route::get('/in-process', fn () => Inertia::render('Blueprints/QC', ['title' => 'In-Process QC']))->name('in-process');
    Route::get('/checklists', fn () => Inertia::render('Blueprints/QC', ['title' => 'Quality Checklists']))->name('checklists');
});

// Maintenance
Route::middleware(['auth'])->prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/schedule', fn () => Inertia::render('Blueprints/Maintenance', ['title' => 'Preventive Schedule']))->name('schedule');
    Route::get('/breakdown', fn () => Inertia::render('Blueprints/Maintenance', ['title' => 'Breakdown Logs']))->name('breakdown');
    Route::get('/spareparts', fn () => Inertia::render('Blueprints/Maintenance', ['title' => 'Spareparts Tracking']))->name('spareparts');
});

// Finance
Route::middleware(['auth'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/ledger', fn () => Inertia::render('Blueprints/Finance', ['title' => 'General Ledger']))->name('ledger');
    Route::get('/reports', fn () => Inertia::render('Blueprints/Finance', ['title' => 'Profit & Loss']))->name('reports');
    Route::get('/payment-monitoring', fn () => Inertia::render('Blueprints/Finance', ['title' => 'AP & AR Monitoring']))->name('payment-monitoring');
});

// HR
Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/employees-export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employees-template', [EmployeeController::class, 'template'])->name('employees.template');
    Route::post('/employees-import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::resource('employees', EmployeeController::class);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance-template', [AttendanceController::class, 'template'])->name('attendance.template');
    Route::post('/attendance-import', [AttendanceController::class, 'import'])->name('attendance.import');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out/{attendance}', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/settings', [PayrollSettingController::class, 'index'])->name('payroll.settings');
    Route::post('/payroll/settings', [PayrollSettingController::class, 'update'])->name('payroll.settings.update');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('/payroll/{payroll}/print', [PayrollController::class, 'print'])->name('payroll.print');
    Route::put('/payroll/{payroll}/status', [PayrollController::class, 'updateStatus'])->name('payroll.update-status');
});

// Logistics
Route::middleware(['auth'])->prefix('logistics')->name('logistics.')->group(function () {
    Route::get('/planning', [App\Http\Controllers\Logistics\LogisticsController::class, 'index'])->name('planning');
    Route::post('/planning/assign', [App\Http\Controllers\Logistics\LogisticsController::class, 'assignVehicle'])->name('planning.assign');
    Route::resource('fleet', App\Http\Controllers\Logistics\VehicleController::class);
});

// CRM
Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::get('/leads', fn () => Inertia::render('Blueprints/CRM', ['title' => 'Leads Management']))->name('leads');
    Route::get('/opportunities', fn () => Inertia::render('Blueprints/CRM', ['title' => 'Opportunity Tracking']))->name('opportunities');
    Route::get('/campaigns', fn () => Inertia::render('Blueprints/CRM', ['title' => 'Marketing Campaigns']))->name('campaigns');
});

// Costing
Route::middleware(['auth'])->prefix('costing')->name('costing.')->group(function () {
    Route::get('/production', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Production Costing']))->name('production');
    Route::get('/overhead', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Overhead Allocation']))->name('overhead');
    Route::get('/profitability', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Profitability Analytic']))->name('profitability');
});

// Portal
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/supplier', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Supplier Portal']))->name('supplier');
    Route::get('/customer', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Customer Portal']))->name('customer');
    Route::get('/settings', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Portal Settings']))->name('settings');
});

// Settings
Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');
    Route::get('/users', [App\Http\Controllers\Settings\UserController::class, 'index'])->name('users');
    Route::post('/users', [App\Http\Controllers\Settings\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [App\Http\Controllers\Settings\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [App\Http\Controllers\Settings\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/roles', [App\Http\Controllers\Settings\RoleController::class, 'index'])->name('roles');
    Route::post('/roles', [App\Http\Controllers\Settings\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/company', [App\Http\Controllers\Settings\CompanyController::class, 'index'])->name('company');
    Route::post('/company', [App\Http\Controllers\Settings\CompanyController::class, 'update'])->name('company.update');
    Route::get('/numbering', fn () => Inertia::render('Settings/DocumentNumbering'))->name('numbering');
    Route::get('/regional', fn () => Inertia::render('Settings/RegionalTax'))->name('regional');
    Route::get('/preferences', fn () => Inertia::render('Settings/SystemPreferences'))->name('preferences');
    Route::get('/workflow', fn () => Inertia::render('Settings/WorkflowApproval'))->name('workflow');
    Route::get('/io', fn () => Inertia::render('Settings/ImportExport'))->name('io');
});

// Admin
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/activity-logs/export', [App\Http\Controllers\Admin\ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::post('/activity-logs/reset', [App\Http\Controllers\Admin\ActivityLogController::class, 'reset'])->name('activity-logs.reset');
    Route::resource('activity-logs', App\Http\Controllers\Admin\ActivityLogController::class)->only(['index', 'show']);
});

// Profile
Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/logout', [App\Http\Controllers\ProfileController::class, 'logout'])->name('logout');
