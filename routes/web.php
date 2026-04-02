<?php


use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderRequestsController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\GoodReceiveNoteController;
use App\Http\Controllers\PurchaseExpenseController;
use App\Http\Controllers\ProductTransferRequestsController;
use App\Http\Controllers\StockTransferReturnController;
use App\Http\Controllers\PurchaseRequestNoteController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\SmtpSettingController;
use App\Http\Controllers\GoodReceiveNoteReturnController;
use App\Http\Controllers\ProductReleaseReportController;
use App\Http\Controllers\StockTransferReturnReportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TillManagementController;

/*
|--------------------------------------------------------------------------
| Installation Routes
|--------------------------------------------------------------------------
|
| These routes handle the initial system installation and setup process.
| They guide users through:
| - System requirements check
| - Composer and NPM dependencies installation
| - Environment configuration (.env file setup)
| - Database creation and connection testing
| - Running migrations and seeders
| - Generating application key
| - Creating storage symbolic link
|
| Access: Public (no authentication required during installation)
|
*/


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Routes accessible without authentication
|
*/

// Welcome/Landing Page - redirect to login on first load if login exists
Route::get('/', function () {
    if (Route::has('login')) {
        return redirect()->route('login');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Role-based dashboard routes for authenticated users
| Middleware: auth, verified
|
*/

// Main Dashboard - Accessible to all authenticated users
Route::get('/dashboard', fn() => Inertia::render('Dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin Dashboard - For administrators with full system access
// Route::get('/admin-dashboard', fn() => Inertia::render('AdminDashboard'))
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

// User Dashboard - For regular users with limited access
// Route::get('/user-dashboard', fn() => Inertia::render('UserDashboard'))
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
|
| All routes below require authentication (auth middleware)
| Organized by functional area
|
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile Management Routes - All authenticated users
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes (user_type: 0)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1'])->group(function () {
    // Settings - Admin Only
    Route::get('/settings/app', [AppSettingController::class, 'index'])->name('settings.app');
    Route::post('/settings/app', [AppSettingController::class, 'store'])->name('settings.app.store');
    Route::get('/settings/smtp', [SmtpSettingController::class, 'index'])->name('settings.smtp');
    Route::post('/settings/smtp', [SmtpSettingController::class, 'store'])->name('settings.smtp.store');

    // Sync Setting - Admin Only
    Route::get('/settings/sync', [App\Http\Controllers\SyncSettingController::class, 'index'])->name('settings.sync');
    Route::post('/settings/sync', [App\Http\Controllers\SyncSettingController::class, 'store'])->name('settings.sync.store');
    Route::post('/settings/sync/update-second-db', [App\Http\Controllers\SyncSettingController::class, 'updateSecondDb'])->name('settings.sync.update-second-db');
    Route::post('/settings/sync/test-connection', [App\Http\Controllers\SyncSettingController::class, 'testConnection'])->name('settings.sync.test-connection');
    Route::post('/settings/sync/migrate-second-db', [App\Http\Controllers\SyncSettingController::class, 'migrateSecondDb'])->name('settings.sync.migrate-second-db');
    Route::get('/settings/sync/list', [App\Http\Controllers\SyncSettingController::class, 'getSyncList'])->name('settings.sync.list');
    Route::post('/settings/sync/module', [App\Http\Controllers\SyncSettingController::class, 'syncModule'])->name('settings.sync.module');

    // Bill Setting - Admin Only
    Route::get('/settings/bill', [App\Http\Controllers\BillSettingController::class, 'index'])->name('settings.bill');
    Route::post('/settings/bill', [App\Http\Controllers\BillSettingController::class, 'store'])->name('settings.bill.store');

    // User Management - Admin Only
    Route::resource('users', UserController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
});

/*
|--------------------------------------------------------------------------
| Admin & Manager Routes (user_type: 0,1)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1,3'])->group(function () {
    // Purchasing & Stock Management
    // Route::resource('purchase-expenses', PurchaseExpenseController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    // Route::get('/purchase-expenses/supplier-data', [PurchaseExpenseController::class, 'getSupplierData'])->name('purchase-expenses.supplier-data');

    // Brands - Admin & Manager Only
    Route::resource('brands', BrandController::class, ['only' => ['index', 'store', 'update', 'destroy']]);

    // Customer, Discount, Tax Management
    Route::resource('customers', CustomerController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::resource('discounts', DiscountController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::resource('taxes', TaxController::class, ['only' => ['index', 'store', 'update', 'destroy']]);

    // Quotations Management
    Route::resource('quotations', QuotationController::class, ['only' => ['index', 'store', 'edit', 'update', 'destroy']]);
    Route::get('quotation/view', [QuotationController::class, 'editQuotation'])->name('quotation.edit');

    // Returns
    Route::resource('return', ReturnController::class);

    //     Route::get('/settings/company', [CompanyInformationController::class, 'index'])->name('settings.company');
    // Route::post('/settings/company', [CompanyInformationController::class, 'store'])->name('settings.company.store');

});

/*
|--------------------------------------------------------------------------
| Admin, Manager & Stock Keeper Routes (user_type: 0,1,3)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1,3'])->group(function () {
    // Inventory Management - Custom product routes BEFORE resource routes to avoid conflicts
    Route::get('/products/fifo-price/{productId}', [ProductController::class, 'getFifoPricingInfo'])->name('products.fifo-price');
    Route::post('/products/pricing-by-batch', [ProductController::class, 'getPricingInfoByBatch'])->name('products.pricing-by-batch');
    Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::post('products/log-activity', [ProductController::class, 'logActivity'])->name('products.log-activity');
    
    // Product resource routes
    Route::resource('products', ProductController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::resource('categories', CategoryController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::resource('types', TypeController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::resource('measurement-units', MeasurementUnitController::class, ['only' => ['index', 'store', 'update', 'destroy']]);


});

/*
|-------------------------------------------------------------------------
| Admin & Stock Keeper Routes (user_type: 0,3)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,3,4'])->group(function () {
    // Stock Transfer Requests
    Route::resource('suppliers', SupplierController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
        Route::resource('purchase-expenses', PurchaseExpenseController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
    Route::get('/purchase-expenses/supplier-data', [PurchaseExpenseController::class, 'getSupplierData'])->name('purchase-expenses.supplier-data');

        Route::get('/product-release-notes', [PurchaseRequestNoteController::class, 'index'])->name('product-release-notes.index');                   // List all PRNs
    Route::post('/product-release-notes', [PurchaseRequestNoteController::class, 'store'])->name('product-release-notes.store');                  // Create new PRN
    Route::put('/product-release-notes/{productReleaseNote}', [PurchaseRequestNoteController::class, 'update'])->name('product-release-notes.update');           // Update PRN
    Route::delete('/product-release-notes/{productReleaseNote}', [PurchaseRequestNoteController::class, 'destroy'])->name('product-release-notes.destroy');      // Delete PRN
});

Route::middleware(['auth', 'role:0,1,3,4'])->group(function () {
    Route::resource('suppliers', SupplierController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
});
/*
|--------------------------------------------------------------------------
| Sales Management Routes (All Except Backoffice role: 1)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,2,3,4'])->group(function () {
    // Sales Management
    Route::get('/sales/unpaid-list', [SaleController::class, 'unpaidList'])->name('sales.unpaid-list');
    Route::get('/sales/{sale}/unpaid-details', [SaleController::class, 'unpaidDetails'])->name('sales.unpaid-details');
    Route::patch('/sales/{sale}/mark-paid', [SaleController::class, 'markAsPaid'])->name('sales.mark-paid');
    Route::patch('/sales/{sale}/complete-unpaid', [SaleController::class, 'completeUnpaid'])->name('sales.complete-unpaid');
    Route::resource('sales', SaleController::class, ['only' => ['index', 'store', 'update', 'destroy']]);
});

/*
|--------------------------------------------------------------------------
| Admin, Manager & Cashier Routes (user_type: 0,1,2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1,2'])->group(function () {
    // Shift Management
    Route::get('/shift-management', [ShiftController::class, 'index'])->name('shift-management.index');
    Route::get('/shift-management/end-page', [ShiftController::class, 'endPage'])->name('shift-management.end-page');
    Route::get('/shift-management/{shift}', [ShiftController::class, 'show'])->name('shift-management.show');
    Route::delete('/shift-management/{shift}', [ShiftController::class, 'destroy'])->name('shift-management.destroy');
    Route::post('/shift-management/start', [ShiftController::class, 'start'])->name('shift-management.start');
    Route::post('/shift-management/end', [ShiftController::class, 'end'])->name('shift-management.end');

    // Till Management
    Route::get('/till-management', [TillManagementController::class, 'index'])->name('till-management.index');
    Route::post('/till-management', [TillManagementController::class, 'store'])->name('till-management.store');
});

/*
|--------------------------------------------------------------------------
| Admin & Manager Only Routes (Purchasing) (user_type: 0,1,4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1,2,3'])->group(function () {
    Route::prefix('purchase-order-requests')->name('purchase-order-requests.')->group(function () {
    // Purchase Order Request Routes
        Route::get('/', [PurchaseOrderRequestsController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderRequestsController::class, 'create'])->name('create');
        Route::post('/', [PurchaseOrderRequestsController::class, 'store'])->name('store');
        Route::get('/{purchaseOrderRequest}', [PurchaseOrderRequestsController::class, 'show'])->name('show');
        Route::patch('/{purchaseOrderRequest}', [PurchaseOrderRequestsController::class, 'update'])->name('update');
        Route::patch('/{purchaseOrderRequest}/status', [PurchaseOrderRequestsController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{purchaseOrderRequest}', [PurchaseOrderRequestsController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseOrderRequest}/restore', [PurchaseOrderRequestsController::class, 'restore'])->name('restore');
    });

    // Get Purchase Order Details (AJAX endpoint)
    Route::get('/po/{id}/details', [PurchaseOrderRequestsController::class, 'purchaseOrderDetails']);

    // Purchase Orders (Formal PO → GRN workflow)
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrdersController::class, 'index'])->name('index');
        Route::post('/', [PurchaseOrdersController::class, 'store'])->name('store');
        Route::patch('/{purchaseOrder}/status', [PurchaseOrdersController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{purchaseOrder}', [PurchaseOrdersController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [PurchaseOrdersController::class, 'restore'])->name('restore');
        Route::get('/{id}/details', [PurchaseOrdersController::class, 'details'])->name('details');
        Route::get('/{id}/pdf', [PurchaseOrdersController::class, 'exportPdf'])->name('pdf');
    });


    // Goods Received Note Routes
    Route::prefix('goods-received-notes')->name('good-receive-notes.')->group(function () {
        Route::get('/', [GoodReceiveNoteController::class, 'index'])->name('index');
        Route::post('/', [GoodReceiveNoteController::class, 'store'])->name('store');
        Route::patch('/{goodsReceivedNote}', [GoodReceiveNoteController::class, 'update'])->name('update');
        Route::patch('/{goodsReceivedNote}/status', [GoodReceiveNoteController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{goodsReceivedNote}/approve', [GoodReceiveNoteController::class, 'approve'])->name('approve');
        Route::delete('/{goodsReceivedNote}', [GoodReceiveNoteController::class, 'destroy'])->name('destroy');
    });

    // Goods Received Note Return Routes
    Route::prefix('good-receive-note-returns')->name('good-receive-note-returns.')->group(function () {
        Route::get('/', [GoodReceiveNoteReturnController::class, 'index'])->name('index');
        Route::get('/{id}/details', [GoodReceiveNoteReturnController::class, 'goodsReceivedNoteReturnDetails']);
        Route::get('/create', [GoodReceiveNoteReturnController::class, 'create'])->name('create');
        Route::post('/', [GoodReceiveNoteReturnController::class, 'store'])->name('store');
        Route::delete('/{goodReceiveNoteReturn}', [GoodReceiveNoteReturnController::class, 'destroy'])->name('destroy');
        Route::patch('/{goodReceiveNoteReturn}', [GoodReceiveNoteReturnController::class, 'update'])->name('update');
    });

    // Product Transfer Request Routes
    Route::get('/product-transfer-requests/{id}/details', [ProductTransferRequestsController::class, 'productTransferRequestDetails']);
    Route::resource('product-transfer-requests', ProductTransferRequestsController::class);
    Route::patch('product-transfer-requests/{productTransferRequest}/status', [ProductTransferRequestsController::class, 'updateStatus'])->name('product-transfer-requests.updateStatus');

    // Stock Transfer Return Routes (Shop → Store - Damaged/Returns)
    Route::resource('stock-transfer-returns', StockTransferReturnController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('stock-transfer-returns/{stockTransferReturn}/status', [StockTransferReturnController::class, 'updateStatus'])->name('stock-transfer-returns.update-status');
    Route::post('stock-transfer-returns/available-quantity', [StockTransferReturnController::class, 'getAvailableQuantity'])->name('stock-transfer-returns.available-quantity');

    // Store Inventory Routes
    Route::prefix('store-inventory')->name('store-inventory.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StoreInventoryController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\StoreInventoryController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\StoreInventoryController::class, 'show'])->name('show');
        Route::patch('/{id}', [\App\Http\Controllers\StoreInventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\StoreInventoryController::class, 'destroy'])->name('destroy');
        Route::get('/current/quantities', [\App\Http\Controllers\StoreInventoryController::class, 'getCurrentQuantities'])->name('current-quantities');
    });

    /*
    |--------------------------------------------------------------------------
    | Product Release Note Routes
    |--------------------------------------------------------------------------
    |
    | Track product releases/dispatches from inventory
    |
    */

        Route::get('/sales-history', [SaleController::class, 'salesHistory'])->name('sales.all');
    // Return Routes
    Route::prefix('return')->name('return.')->group(function () {
        Route::get('/', [ReturnController::class, 'index'])->name('index');
        Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
        // Export Sales Return Bill (PDF)
        Route::get('/{return}/bill/pdf', [ReturnController::class, 'exportBillPdf'])->name('export.bill.pdf');
        Route::post('/', [ReturnController::class, 'store'])->name('store');
        Route::post('/from-sales', [ReturnController::class, 'createFromSales'])->name('create-from-sales');
        Route::put('/{return}', [ReturnController::class, 'update'])->name('update');
        Route::patch('/{return}/status', [ReturnController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{return}', [ReturnController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Report Routes
    |--------------------------------------------------------------------------
    |
    | Individual report pages with filtering and export capabilities
    |
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        // Sales Report - Sales by type with income
        Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');

        // Product Sales Report - Product-wise sales and returns
        Route::get('/product-sales', [ReportController::class, 'productSalesReport'])->name('product-sales');

        // Stock Report - Current inventory levels
        Route::get('/stock', [ReportController::class, 'stockReport'])->name('stock');

        // Income Report - Income by payment type
        Route::get('/income', [ReportController::class, 'incomeReport'])->name('income');

        // Sync Report - Sync activity logs
        Route::get('/sync', [\App\Http\Controllers\SyncReportController::class, 'index'])->name('sync');
        Route::get('/export/sync/pdf', [\App\Http\Controllers\SyncReportController::class, 'exportPdf'])->name('export.sync.pdf');
        Route::get('/export/sync/excel', [\App\Http\Controllers\SyncReportController::class, 'exportExcel'])->name('export.sync.excel');

        //GRN Report - Goods Received Notes
        Route::get('/grn', [ReportController::class, 'grnReport'])->name('grn');

        // GRN Return Report
        Route::get('/grn-returns', [ReportController::class, 'grnReturnReport'])->name('grn-returns');

        // Product Movements Report - Track all inventory movements
        Route::get('/product-movements', [ReportController::class, 'productMovementReport'])->name('product-movements');

            // Product Release Notes Report
        Route::get('/product-release', [ProductReleaseReportController::class, 'index'])->name('product-release');

        // Stock Transfer Returns Report
        Route::get('/stock-transfer-return', [StockTransferReturnReportController::class, 'index'])->name('stock-transfer-return');

        // Export Routes
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/product-stock/pdf', [ReportController::class, 'exportProductStockPdf'])->name('export.product-stock.pdf');
        Route::get('/export/product-stock/excel', [ReportController::class, 'exportProductStockExcel'])->name('export.product-stock.excel');
        Route::get('/export/income/pdf', [ReportController::class, 'exportIncomePdf'])->name('export.income.pdf');
        Route::get('/export/income/excel', [ReportController::class, 'exportIncomeExcel'])->name('export.income.excel');
        // Product sales exports (used by Sales/ProductSales reports)
        Route::get('/export/product-sales/pdf', [ReportController::class, 'exportProductSalesPdf'])->name('export.product-sales.pdf');
        Route::get('/export/product-sales/excel', [ReportController::class, 'exportProductSalesExcel'])->name('export.product-sales.excel');
        Route::get('/export/grn/pdf', [ReportController::class, 'exportGoodReceiveNotePdf'])->name('export.grn.pdf');
        Route::get('/export/grn/excel', [ReportController::class, 'exportGoodReceiveNoteExcel'])->name('export.grn.excel');
        Route::get('/export/grn-returns/pdf', [ReportController::class, 'exportGoodReceiveNoteReturnPdf'])->name('export.grn-returns.pdf');
        Route::get('/export/grn-returns/excel', [ReportController::class, 'exportGoodReceiveNoteReturnExcel'])->name('export.grn-returns.excel');
        Route::get('/export/product-movements/pdf', [ReportController::class, 'exportProductMovementPdf'])->name('export.product-movements.pdf');
        Route::get('/export/product-movements/excel', [ReportController::class, 'exportProductMovementExcel'])->name('export.product-movements.excel');
        Route::get('/export/product-release/pdf', [ProductReleaseReportController::class, 'exportPdf'])->name('export.product-release.pdf');
        Route::get('/export/product-release/excel', [ProductReleaseReportController::class, 'exportExcel'])->name('export.product-release.excel');
        Route::get('/export/stock-transfer-return/pdf', [StockTransferReturnReportController::class, 'exportPdf'])->name('export.stock-transfer-return.pdf');
        Route::get('/export/stock-transfer-return/excel', [StockTransferReturnReportController::class, 'exportExcel'])->name('export.stock-transfer-return.excel');

        // Activity Log Report
        Route::get('/activity-log', [\App\Http\Controllers\ActivityLogReportController::class, 'index'])->name('activity-log');
        Route::get('/export/activity-log/pdf', [\App\Http\Controllers\ActivityLogReportController::class, 'exportPdf'])->name('export.activity-log.pdf');
        Route::get('/export/activity-log/excel', [\App\Http\Controllers\ActivityLogReportController::class, 'exportExcel'])->name('export.activity-log.excel');

        // Products Low Stock - Combined Report
        Route::get('/low-stock', [ReportController::class, 'lowStockReport'])->name('low-stock');

        // Products Low Stock - Shop
        Route::get('/low-stock-shop', [ReportController::class, 'lowStockShopReport'])->name('low-stock-shop');
        Route::get('/export/low-stock-shop/pdf', [ReportController::class, 'exportLowStockShopPdf'])->name('export.low-stock-shop.pdf');
        Route::get('/export/low-stock-shop/csv', [ReportController::class, 'exportLowStockShopCsv'])->name('export.low-stock-shop.csv');

        // Products Low Stock - Store
        Route::get('/low-stock-store', [ReportController::class, 'lowStockStoreReport'])->name('low-stock-store');
        Route::get('/export/low-stock-store/pdf', [ReportController::class, 'exportLowStockStorePdf'])->name('export.low-stock-store.pdf');
        Route::get('/export/low-stock-store/csv', [ReportController::class, 'exportLowStockStoreCsv'])->name('export.low-stock-store.csv');
        // Product Movement Based Sales Optimization Report
        Route::get('/product-movement-sales-optimization', [ReportController::class, 'productMovementSalesOptimizationReport'])->name('product-movement-sales-optimization')->middleware('role:0,2,3');
        Route::get('/export/product-movement-sales-optimization/pdf', [ReportController::class, 'exportProductMovementSalesOptimizationPdf'])->name('export.product-movement-sales-optimization.pdf');
        Route::get('/export/product-movement-sales-optimization/csv', [ReportController::class, 'exportProductMovementSalesOptimizationCsv'])->name('export.product-movement-sales-optimization.csv');

        // Unpaid Sales Report
        Route::get('/unpaid-sales', [SaleController::class, 'unpaidReport'])->name('unpaid-sales')->middleware('role:0,2,3');
    });
});

/*
|--------------------------------------------------------------------------
| Sales Income Report Routes (Order History) - Admin, Manager & Cashier (user_type: 0,1,2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // Order History Report - Sales income and returns transactions
        Route::get('/sales-income', [ReportController::class, 'salesIncomeReport'])->name('sales-income');
        Route::get('/sales-income/totals', [ReportController::class, 'salesIncomeTotals'])->name('sales-income.totals');
        
        // Export Routes
        Route::get('/export/sales-income/pdf', [ReportController::class, 'exportSalesIncomePdf'])->name('export.sales-income.pdf');
        Route::get('/export/sales-income/excel', [ReportController::class, 'exportSalesIncomeExcel'])->name('export.sales-income.excel');
    });
});

/*
|--------------------------------------------------------------------------
| Expenses Report Routes - Admin & Backoffice (user_type: 0,1)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // Expenses Report - Expense details and summary
        Route::get('/expenses', [ReportController::class, 'expensesReport'])->name('expenses');
        Route::get('/export/expenses/pdf', [ReportController::class, 'exportExpensesPdf'])->name('export.expenses.pdf');
        Route::get('/export/expenses/excel', [ReportController::class, 'exportExpensesExcel'])->name('export.expenses.excel');
    });
});

/*
|--------------------------------------------------------------------------
| Sales Report Routes - Admin ONLY (user_type: 0)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // Sales Report - Sales by type with income
        Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    });
});

/*
|--------------------------------------------------------------------------
| Stock Reports Routes - Admin, Manager & Stock Keeper (user_type: 0,1,4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1, 2,3'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // Stock Report - Current stock levels
        Route::get('/stock', [ReportController::class, 'stockReport'])->name('stock');

        // Products Low Stock - Shop
        Route::get('/low-stock-shop', [ReportController::class, 'lowStockShopReport'])->name('low-stock-shop');
        Route::get('/export/low-stock-shop/pdf', [ReportController::class, 'exportLowStockShopPdf'])->name('export.low-stock-shop.pdf');
        Route::get('/export/low-stock-shop/csv', [ReportController::class, 'exportLowStockShopCsv'])->name('export.low-stock-shop.csv');

        // Products Low Stock - Store
        Route::get('/low-stock-store', [ReportController::class, 'lowStockStoreReport'])->name('low-stock-store');
        Route::get('/export/low-stock-store/pdf', [ReportController::class, 'exportLowStockStorePdf'])->name('export.low-stock-store.pdf');
        Route::get('/export/low-stock-store/csv', [ReportController::class, 'exportLowStockStoreCsv'])->name('export.low-stock-store.csv');

        // Product Movements Report - Track all inventory movements
        Route::get('/product-movements', [ReportController::class, 'productMovementReport'])->name('product-movements');

        // Product Release Notes Report
        Route::get('/product-release', [ProductReleaseReportController::class, 'index'])->name('product-release');
        Route::get('/export/product-release/pdf', [ProductReleaseReportController::class, 'exportPdf'])->name('export.product-release.pdf');
        Route::get('/export/product-release/excel', [ProductReleaseReportController::class, 'exportExcel'])->name('export.product-release.excel');

        // Stock Transfer Returns Report
        Route::get('/stock-transfer-return', [StockTransferReturnReportController::class, 'index'])->name('stock-transfer-return');
        Route::get('/export/stock-transfer-return/pdf', [StockTransferReturnReportController::class, 'exportPdf'])->name('export.stock-transfer-return.pdf');
        Route::get('/export/stock-transfer-return/excel', [StockTransferReturnReportController::class, 'exportExcel'])->name('export.stock-transfer-return.excel');

        //GRN Report - Goods Received Notes
        Route::get('/grn', [ReportController::class, 'grnReport'])->name('grn');

        // GRN Return Report
        Route::get('/grn-returns', [ReportController::class, 'grnReturnReport'])->name('grn-returns');

        Route::get('/export/product-stock/pdf', [ReportController::class, 'exportProductStockPdf'])->name('export.product-stock.pdf');
        Route::get('/export/product-stock/excel', [ReportController::class, 'exportProductStockExcel'])->name('export.product-stock.excel');
    });
});

/*
|--------------------------------------------------------------------------
| Admin & Manager Reports (user_type: 0,1,2,4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:0,1,2,3'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // Income Report - Income by payment type
        Route::get('/income', [ReportController::class, 'incomeReport'])->name('income');

        // Activity Log Report
        Route::get('/activity-log', [\App\Http\Controllers\ActivityLogReportController::class, 'index'])->name('activity-log');
    });

    // Product Release Note Routes (Admin & Manager Only)
    Route::get('/product-release-notes', [PurchaseRequestNoteController::class, 'index'])->name('product-release-notes.index');
    Route::post('/product-release-notes', [PurchaseRequestNoteController::class, 'store'])->name('product-release-notes.store');
    Route::put('/product-release-notes/{productReleaseNote}', [PurchaseRequestNoteController::class, 'update'])->name('product-release-notes.update');
    Route::delete('/product-release-notes/{productReleaseNote}', [PurchaseRequestNoteController::class, 'destroy'])->name('product-release-notes.destroy');

    // Return Routes (Admin & Manager Only)
    Route::prefix('return')->name('return.')->group(function () {
        Route::get('/', [ReturnController::class, 'index'])->name('index');
        Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
        Route::post('/', [ReturnController::class, 'store'])->name('store');
        Route::post('/from-sales', [ReturnController::class, 'createFromSales'])->name('create-from-sales');
        Route::put('/{return}', [ReturnController::class, 'update'])->name('update');
        Route::patch('/{return}/status', [ReturnController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{return}', [ReturnController::class, 'destroy'])->name('destroy');
    });

    Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export');

});

/*
|--------------------------------------------------------------------------
| Quick Add Routes (Modal Creation)
|--------------------------------------------------------------------------
|
| These routes allow quick creation of supporting data from modal windows
| Used when creating products or orders and need to add a new brand/category/etc.
| on the fly without leaving the current page.
|
| Note: These routes are duplicated outside the auth group for AJAX accessibility
|
*/

// Quick Add: Brand - Create new brand from modal
Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');

// Quick Add: Category - Create new category from modal
// Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

// Quick Add: Type - Create new type from modal
Route::post('/types', [TypeController::class, 'store'])->name('types.store');

/*
|--------------------------------------------------------------------------
| Database Backup Routes
|--------------------------------------------------------------------------
|
| Routes for database backup functionality
|
*/
Route::middleware(['auth', 'role:0'])->group(function () {
    Route::get('/settings/backup', function () {
        return \Inertia\Inertia::render('Settings/BackupSetting');
    })->name('backup.settings');
    Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
    Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');
    Route::get('/backup/list', [BackupController::class, 'listBackups'])->name('backup.list');
    Route::get('/backup/download/{filename}', [BackupController::class, 'downloadBackup'])->name('backup.download');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Loaded from auth.php - includes login, logout, register, password reset, etc.
|
*/
require __DIR__.'/auth.php';


Route::get('/excel/export/{module}', [ExcelController::class, 'export'])->name('excel.export');
Route::get('/excel/export-data/{module}', [ExcelController::class, 'exportData'])->name('excel.export-data');
Route::get('/excel/export-headers/{module}', [ExcelController::class, 'exportHeaders'])->name('excel.export-headers');
Route::post('/excel/upload/{module}', [ExcelController::class, 'upload'])->name('excel.upload');
