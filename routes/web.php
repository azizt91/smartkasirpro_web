<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/products/search', [PosController::class, 'searchProducts'])->name('pos.products.search');
    Route::get('/pos/categories', [PosController::class, 'getCategories'])->name('pos.categories');
    Route::post('/pos/search', [PosController::class, 'searchProducts'])->name('pos.search');
    Route::post('/pos/transaction', [PosController::class, 'store'])->name('pos.transaction');



    // Protected Routes (Admin or Permission based)
    Route::resource('products', ProductController::class)->middleware('permission:view_products');
    Route::get('/products/barcodes/print', [App\Http\Controllers\ProductController::class, 'printBarcodes'])
        ->name('products.print_barcodes')
        ->middleware('permission:view_products');
        
    Route::resource('categories', CategoryController::class)->middleware('permission:view_categories');
    
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class)->middleware('permission:view_purchases');
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->middleware('permission:view_expenses');
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->middleware('permission:view_suppliers');
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->middleware('permission:view_customers');
    Route::resource('transactions', \App\Http\Controllers\TransactionController::class)->only(['index', 'show', 'destroy'])->middleware('permission:view_transactions');
    
    // Reports
    Route::middleware('permission:view_reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/sales/export-pdf', [ReportController::class, 'exportSalesPdf'])->name('sales.pdf');
        Route::get('/sales/export-excel', [ReportController::class, 'exportSalesExcel'])->name('sales.excel');
        Route::get('/products/export-pdf', [ReportController::class, 'exportProductsPdf'])->name('products.pdf');
        Route::get('/products/export-excel', [ReportController::class, 'exportProductsExcel'])->name('products.excel');
        Route::get('/stock/export-pdf', [ReportController::class, 'exportStockPdf'])->name('stock.pdf');
        Route::get('/stock/export-excel', [ReportController::class, 'exportStockExcel'])->name('stock.excel');
        Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
        Route::post('/receivables/{transaction}/paid', [ReportController::class, 'markAsPaid'])->name('receivables.paid');
    });

    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
