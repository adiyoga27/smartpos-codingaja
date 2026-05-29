<?php

use App\Http\Controllers\Akuntansi\JournalController;
use App\Http\Controllers\Akuntansi\LedgerController;
use App\Http\Controllers\Akuntansi\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Keuangan\CashAccountController;
use App\Http\Controllers\Keuangan\CashTransactionController;
use App\Http\Controllers\Keuangan\PayableController;
use App\Http\Controllers\Keuangan\ReceivableController;
use App\Http\Controllers\Master\AccountController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\TaxController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Setting\CompanySettingController;
use App\Http\Controllers\Stok\StockMutationController;
use App\Http\Controllers\Transaksi\PurchaseController;
use App\Http\Controllers\Transaksi\PurchaseReturnController;
use App\Http\Controllers\Transaksi\SaleController;
use App\Http\Controllers\Transaksi\SaleReturnController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('customers', CustomerController::class);
        Route::resource('accounts', AccountController::class);
        Route::resource('taxes', TaxController::class);
    });

    // Transaksi
    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::resource('purchases', PurchaseController::class);
        Route::patch('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::resource('purchase_returns', PurchaseReturnController::class);
        Route::resource('sale_returns', SaleReturnController::class);
    });

    Route::get('pos/kasir', [SaleController::class, 'kasir'])->name('pos.kasir');
    Route::post('pos/kasir', [SaleController::class, 'store'])->name('transaksi.sales.store');
    Route::get('pos/riwayat', [SaleController::class, 'riwayat'])->name('pos.riwayat');
    Route::get('pos/print-a4/{sale}', [SaleController::class, 'printA4'])->name('pos.print-a4');
    Route::get('pos/print-thermal/{sale}', [SaleController::class, 'printThermal'])->name('pos.print-thermal');

    // Hutang & Piutang
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
        Route::get('payables/{payable}/pay', [PayableController::class, 'payForm'])->name('payables.pay');
        Route::post('payables/{payable}/pay', [PayableController::class, 'payStore'])->name('payables.pay.store');
        Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('receivables/{receivable}/receive', [ReceivableController::class, 'receiveForm'])->name('receivables.receive');
        Route::post('receivables/{receivable}/receive', [ReceivableController::class, 'receiveStore'])->name('receivables.receive.store');
        Route::resource('cash_accounts', CashAccountController::class);
        Route::resource('cash_transactions', CashTransactionController::class);
    });

    // Akuntansi
    Route::prefix('akuntansi')->name('akuntansi.')->group(function () {
        Route::resource('journals', JournalController::class);
        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger');
        Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance_sheet');
        Route::get('income-statement', [ReportController::class, 'incomeStatement'])->name('income_statement');
    });

    // Stok
    Route::prefix('stok')->name('stok.')->group(function () {
        Route::get('mutations', [StockMutationController::class, 'index'])->name('mutations.index');
        Route::get('mutations/{product}', [StockMutationController::class, 'show'])->name('mutations.show');
        Route::get('opname', [StockMutationController::class, 'opnameForm'])->name('opname');
        Route::post('opname', [StockMutationController::class, 'opnameStore'])->name('opname.store');
    });

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [App\Http\Controllers\Laporan\ReportController::class, 'index'])->name('index');
        Route::get('pembelian', [App\Http\Controllers\Laporan\ReportController::class, 'pembelian'])->name('pembelian');
        Route::get('penjualan', [App\Http\Controllers\Laporan\ReportController::class, 'penjualan'])->name('penjualan');
        Route::get('hutang', [App\Http\Controllers\Laporan\ReportController::class, 'hutang'])->name('hutang');
        Route::get('piutang', [App\Http\Controllers\Laporan\ReportController::class, 'piutang'])->name('piutang');
        Route::get('arus-kas', [App\Http\Controllers\Laporan\ReportController::class, 'arusKas'])->name('arus_kas');
        Route::get('stok', [App\Http\Controllers\Laporan\ReportController::class, 'stok'])->name('stok');
    });

    // Settings
    Route::get('settings/company', [CompanySettingController::class, 'edit'])->name('settings.company');
    Route::put('settings/company', [CompanySettingController::class, 'update'])->name('settings.company.update');

    // Users & Roles
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
