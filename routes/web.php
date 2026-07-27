<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxInvoiceController;
use App\Http\Controllers\UnitController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// ลิงก์สั้นสำหรับสแกน QR ดูใบเสร็จ (ไม่ต้องล็อกอิน)
Route::get('/r/{sale}', [TaxInvoiceController::class, 'receipt'])->name('receipts.public');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/barcode', [PosController::class, 'findByBarcode'])->name('pos.barcode');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/toggle-cost', [ProductController::class, 'toggleCost'])->name('products.toggle-cost');
    Route::get('/products/barcode-preview', [ProductController::class, 'barcodePreview'])->name('products.barcode');
    Route::get('/products/cipher-preview', [ProductController::class, 'cipherPreview'])->name('products.cipher');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/product-groups', [ProductGroupController::class, 'index'])->name('product-groups.index');
    Route::post('/product-groups', [ProductGroupController::class, 'store'])->name('product-groups.store');
    Route::put('/product-groups/{productGroup}', [ProductGroupController::class, 'update'])->name('product-groups.update');
    Route::delete('/product-groups/{productGroup}', [ProductGroupController::class, 'destroy'])->name('product-groups.destroy');

    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{sale}/items/{item}/remove', [SaleController::class, 'removeItem'])->name('sales.items.remove');

    Route::get('/invoices/{sale}/tax', [TaxInvoiceController::class, 'show'])->name('invoices.tax');
    Route::get('/invoices/{sale}/receipt', [TaxInvoiceController::class, 'receipt'])->name('invoices.receipt');
    Route::post('/invoices/{sale}/customer', [TaxInvoiceController::class, 'updateCustomer'])->name('invoices.customer');

    Route::middleware(EnsureRole::class.':owner')->group(function () {
        Route::get('/payment-accounts', [PaymentAccountController::class, 'index'])->name('payment-accounts.index');
        Route::post('/payment-accounts', [PaymentAccountController::class, 'store'])->name('payment-accounts.store');
        Route::put('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('payment-accounts.update');
        Route::delete('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('payment-accounts.destroy');
        Route::post('/payment-accounts/{paymentAccount}/toggle', [PaymentAccountController::class, 'toggle'])->name('payment-accounts.toggle');
        Route::post('/payment-accounts/{paymentAccount}/default', [PaymentAccountController::class, 'setDefault'])->name('payment-accounts.default');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/shop', [SettingController::class, 'updateShop'])->name('settings.shop');
        Route::post('/settings/cipher', [SettingController::class, 'updateCipher'])->name('settings.cipher');
        Route::post('/settings/line', [SettingController::class, 'updateLine'])->name('settings.line');
        Route::post('/settings/line-test', [SettingController::class, 'testLine'])->name('settings.line-test');
        Route::get('/backup/json', [SettingController::class, 'exportJson'])->name('backup.json');
        Route::get('/backup/csv', [SettingController::class, 'exportCsv'])->name('backup.csv');
    });
});
