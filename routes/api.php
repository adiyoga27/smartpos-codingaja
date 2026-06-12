<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountingReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashAccountController;
use App\Http\Controllers\Api\CashTransactionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompanySettingController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryOrderController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\PayableController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\ReceivableController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\StockMutationController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SmartPOS API v1
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Master Data
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('taxes', TaxController::class);
    Route::apiResource('payment-methods', PaymentMethodController::class);

    // Sales / POS
    Route::apiResource('sales', SaleController::class);
    Route::delete('sales/{sale}/force', [SaleController::class, 'destroy']);

    // Purchases
    Route::apiResource('purchases', PurchaseController::class)->except(['update']);
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::post('purchases/{purchase}/pay', [PurchaseController::class, 'pay']);

    // Sales Orders
    Route::apiResource('sales-orders', SalesOrderController::class)->except(['update']);
    Route::post('sales-orders/{salesOrder}/deliver', [SalesOrderController::class, 'deliver']);

    // Delivery Orders
    Route::apiResource('delivery-orders', DeliveryOrderController::class)->only(['index', 'show']);

    // Sale Returns
    Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store', 'show']);

    // Purchase Returns
    Route::apiResource('purchase-returns', PurchaseReturnController::class)->only(['index', 'store', 'show']);

    // Receivables
    Route::get('receivables', [ReceivableController::class, 'index']);
    Route::get('receivables/{receivable}', [ReceivableController::class, 'show']);
    Route::post('receivables/{receivable}/payment', [ReceivableController::class, 'storePayment']);

    // Payables
    Route::get('payables', [PayableController::class, 'index']);
    Route::get('payables/{payable}', [PayableController::class, 'show']);
    Route::post('payables/{payable}/payment', [PayableController::class, 'storePayment']);

    // Cash Accounts
    Route::apiResource('cash-accounts', CashAccountController::class);
    Route::post('cash-accounts/{cashAccount}/topup', [CashAccountController::class, 'topup']);
    Route::post('cash-accounts/{cashAccount}/withdraw', [CashAccountController::class, 'withdraw']);
    Route::post('cash-accounts/{cashAccount}/adjust', [CashAccountController::class, 'adjust']);

    // Cash Transactions
    Route::apiResource('cash-transactions', CashTransactionController::class)->only(['index', 'store', 'show']);

    // Journals
    Route::apiResource('journals', JournalController::class)->only(['index', 'store', 'show']);

    // Ledger
    Route::get('/ledger', [LedgerController::class, 'index']);

    // Accounting Reports
    Route::get('/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet']);
    Route::get('/reports/income-statement', [AccountingReportController::class, 'incomeStatement']);

    // Business Reports
    Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::get('/reports/purchases', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/receivables', [ReportController::class, 'receivableReport']);
    Route::get('/reports/payables', [ReportController::class, 'payableReport']);
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow']);
    Route::get('/reports/stock', [ReportController::class, 'stockReport']);

    // Stock Mutations & Opname
    Route::get('/stock-mutations', [StockMutationController::class, 'index']);
    Route::get('/stock-mutations/{product}/history', [StockMutationController::class, 'history']);
    Route::get('/stock-opname', [StockMutationController::class, 'opnameIndex']);
    Route::get('/stock-opname/{opnameNumber}', [StockMutationController::class, 'opnameDetail']);
    Route::post('/stock-opname', [StockMutationController::class, 'opnameStore']);

    // Company Settings
    Route::get('/company-settings', [CompanySettingController::class, 'show']);
    Route::put('/company-settings', [CompanySettingController::class, 'update']);

    // Users Management
    Route::apiResource('users', UserController::class);

    // Roles Management
    Route::apiResource('roles', RoleController::class);
    Route::get('/permissions', [RoleController::class, 'permissions']);
});
