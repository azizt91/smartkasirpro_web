<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/clear', [\App\Http\Controllers\Api\NotificationController::class, 'clearAll']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    // Master Data & Sync
    Route::get('/products/sync', [\App\Http\Controllers\Api\ProductController::class, 'sync']);
    Route::post('/products/{id}/adjust', [\App\Http\Controllers\Api\ProductController::class, 'adjustStock']);

    // POS Transaction (Sync Upstream & Resto Mode)
    Route::post('/pos/transaction', [\App\Http\Controllers\Api\PosController::class, 'store']);
    Route::get('/pos/api/orders/pending', [\App\Http\Controllers\Api\PosController::class, 'getPendingOrders']);
    Route::get('/pos/api/orders/kitchen', [\App\Http\Controllers\Api\PosController::class, 'getKitchenOrders']);
    Route::post('/pos/api/orders/{code}/status', [\App\Http\Controllers\Api\PosController::class, 'updateOrderStatus']);
    Route::get('/pos/api/orders/tables', [\App\Http\Controllers\Api\PosController::class, 'getTables']);
    Route::get('/pos/payment-channels', [\App\Http\Controllers\Api\PosController::class, 'paymentChannels']);

    // Transaction History
    Route::get('/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'index']);
    Route::get('/receivables', [\App\Http\Controllers\Api\TransactionController::class, 'receivables']); // New
    Route::get('/transactions/{id}', [\App\Http\Controllers\Api\TransactionController::class, 'show']);
    Route::patch('/transactions/{id}/mark-as-paid', [\App\Http\Controllers\Api\TransactionController::class, 'markAsPaid']); // New
    Route::delete('/transactions/{id}', [\App\Http\Controllers\Api\TransactionController::class, 'destroy']);

    // Expenses
    Route::get('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'index']);
    Route::post('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'store']);
    Route::put('/expenses/{id}', [\App\Http\Controllers\Api\ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}', [\App\Http\Controllers\Api\ExpenseController::class, 'destroy']);

    // Tables
    Route::get('/tables', [\App\Http\Controllers\Api\TableController::class, 'index']);
    Route::post('/tables', [\App\Http\Controllers\Api\TableController::class, 'store']);
    Route::put('/tables/{id}', [\App\Http\Controllers\Api\TableController::class, 'update']);
    Route::delete('/tables/{id}', [\App\Http\Controllers\Api\TableController::class, 'destroy']);
    Route::post('/tables/{id}/clear', [\App\Http\Controllers\Api\TableController::class, 'clear']);

    // Customers
    Route::get('/customers', [\App\Http\Controllers\Api\CustomerController::class, 'index']);
    Route::post('/customers', [\App\Http\Controllers\Api\CustomerController::class, 'store']);
    Route::put('/customers/{id}', [\App\Http\Controllers\Api\CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [\App\Http\Controllers\Api\CustomerController::class, 'destroy']);

    // Employees
    Route::get('/employees', [\App\Http\Controllers\Api\EmployeeController::class, 'index']);

    // Cashier Shifts
    Route::get('/shifts/check', [\App\Http\Controllers\Api\ShiftController::class, 'check']);
    Route::post('/shifts/open', [\App\Http\Controllers\Api\ShiftController::class, 'open']);
    Route::post('/shifts/close', [\App\Http\Controllers\Api\ShiftController::class, 'close']);
});
