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

// Payment Gateway Webhook Callbacks (public, no auth)
Route::post('/payment/callback/tripay', [\App\Http\Controllers\PaymentCallbackController::class, 'tripay'])->name('payment.callback.tripay');
Route::post('/payment/callback/duitku', [\App\Http\Controllers\PaymentCallbackController::class, 'duitku'])->name('payment.callback.duitku');
Route::post('/payment/callback/midtrans', [\App\Http\Controllers\PaymentCallbackController::class, 'midtrans'])->name('payment.callback.midtrans');

// Public Digital Receipt (no auth required — shared via WhatsApp)
Route::get('/receipt/{code}', [\App\Http\Controllers\ReceiptController::class, 'show'])->name('receipt.public');

// Public Self Ordering (QR Menu)
Route::get('/order/{hash}', [\App\Http\Controllers\PublicOrderController::class, 'showMenu'])->name('public.order');
Route::get('/api/order/products', [\App\Http\Controllers\PublicOrderController::class, 'getProducts'])->name('public.order.products');
Route::post('/order/{hash}/submit', [\App\Http\Controllers\PublicOrderController::class, 'submitOrder'])->name('public.order.submit');
Route::get('/order/success/{code}', [\App\Http\Controllers\PublicOrderController::class, 'success'])->name('public.order.success');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // POS Shift Management
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/shift/create', [\App\Http\Controllers\ShiftController::class, 'create'])->name('shift.create');
        Route::post('/shift/store', [\App\Http\Controllers\ShiftController::class, 'store'])->name('shift.store');
        Route::get('/shift/close', [\App\Http\Controllers\ShiftController::class, 'edit'])->name('shift.close');
        Route::post('/shift/close', [\App\Http\Controllers\ShiftController::class, 'update'])->name('shift.update');
    });

    // Main POS interface (requires open shift)
    Route::middleware('shift.open')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/kitchen', [PosController::class, 'kitchen'])->name('pos.kitchen');
        Route::get('/pos/products/search', [PosController::class, 'searchProducts'])->name('pos.products.search');
        Route::get('/pos/categories', [PosController::class, 'getCategories'])->name('pos.categories');
        Route::post('/pos/search', [PosController::class, 'searchProducts'])->name('pos.search');
        Route::post('/pos/transaction', [PosController::class, 'store'])->name('pos.transaction');
        Route::get('/pos/transaction/{code}/status', [PosController::class, 'checkStatus'])->name('pos.transaction.status');
        Route::get('/pos/payment-channels', [PosController::class, 'paymentChannels'])->name('pos.payment-channels');
        
        // Resto API Routes
        Route::get('/pos/api/orders/pending', [PosController::class, 'getPendingOrders'])->name('pos.api.orders.pending');
        Route::get('/pos/api/orders/kitchen', [PosController::class, 'getKitchenOrders'])->name('pos.api.orders.kitchen');
        Route::post('/pos/api/orders/{code}/status', [PosController::class, 'updateOrderStatus'])->name('pos.api.orders.update-status');
    });



    // Protected Routes (Admin or Permission based)
    Route::get('/products/export-template', [\App\Http\Controllers\ProductController::class, 'exportTemplate'])
        ->name('products.export-template')
        ->middleware('permission:view_products');
    Route::post('/products/import', [\App\Http\Controllers\ProductController::class, 'import'])
        ->name('products.import')
        ->middleware('permission:view_products');
    
    Route::resource('products', ProductController::class)->middleware('permission:view_products');
    Route::get('/products/barcodes/print', [App\Http\Controllers\ProductController::class, 'printBarcodes'])
        ->name('products.print_barcodes')
        ->middleware('permission:view_products');
        
    Route::resource('categories', CategoryController::class)->middleware('permission:view_categories');
    
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class)->middleware('permission:view_purchases');
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->middleware('permission:view_expenses');
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->middleware('permission:view_suppliers');
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->middleware('permission:view_customers');
    Route::get('/transactions/{transaction}/print', [\App\Http\Controllers\TransactionController::class, 'print'])->name('transactions.print')->middleware('permission:view_transactions');
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
        Route::get('/commissions', [ReportController::class, 'commissions'])->name('commissions');
        Route::post('/commissions/settle', [ReportController::class, 'settleCommission'])->name('commissions.settle');
        
        // Cashier Shifts Report
        Route::get('/shifts', [\App\Http\Controllers\ShiftController::class, 'index'])->name('shifts');
        
        // Audit Logs Report
        Route::get('/audits', [\App\Http\Controllers\AuditController::class, 'index'])->name('audits');
        
        // Accounting Reports
        Route::get('/ledger', [ReportController::class, 'ledger'])->name('ledger');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit_loss');
    });

    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('/accounts', [\App\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/{account}/initial-balance', [\App\Http\Controllers\AccountController::class, 'setInitialBalance'])->name('accounts.initialBalance');

        Route::get('tables/{table}/qrcode', [\App\Http\Controllers\TableController::class, 'qrCode'])->name('tables.qrcode');
        Route::post('tables/{table}/clear', [\App\Http\Controllers\TableController::class, 'clear'])->name('tables.clear');
        Route::resource('tables', \App\Http\Controllers\TableController::class);

        Route::resource('users', UserController::class);
        Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-whatsapp', [SettingController::class, 'testWhatsapp'])->name('settings.test-whatsapp');
    });
});

// ── FCM Debug Test Link (accessible from browser) ──
Route::get('/test-fcm', function () {
    $results = ['step' => []];

    // 1. Check config
    $projectId = config('services.firebase.project_id');
    $credPath  = config('services.firebase.credentials', 'service-account-file.json');
    $credFull  = base_path($credPath);
    $credExists = file_exists($credFull);
    $results['step'][] = '1️⃣ Config check';
    $results['config'] = [
        'project_id'         => $projectId ?: '❌ MISSING',
        'credentials_path'   => $credPath,
        'credentials_exists' => $credExists ? '✅ Yes' : '❌ No (' . $credFull . ')',
    ];
    if (!$projectId || !$credExists) {
        return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 2. Get access token
    $results['step'][] = '2️⃣ Getting OAuth2 access token';
    try {
        $sa = json_decode(file_get_contents($credFull), true);
        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = base64_encode(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));
        $sig = '';
        openssl_sign("$header.$claim", $sig, $sa['private_key'], 'SHA256');
        $jwt = "$header.$claim." . base64_encode($sig);

        $tokenResp = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);
        $accessToken = $tokenResp->json('access_token');
        $results['access_token'] = $accessToken ? '✅ Obtained (' . strlen($accessToken) . ' chars)' : '❌ Failed: ' . $tokenResp->body();
    } catch (\Exception $e) {
        $results['access_token'] = '❌ Error: ' . $e->getMessage();
        return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 3. Find users with tokens
    $results['step'][] = '3️⃣ Finding users with FCM tokens';
    $users = \App\Models\User::whereNotNull('fcm_token')
        ->where('fcm_token', '!=', '')
        ->get(['id', 'name', 'fcm_token']);
    $results['users'] = $users->map(fn($u) => [
        'id'   => $u->id,
        'name' => $u->name,
        'token' => substr($u->fcm_token, 0, 30) . '...',
        'token_length' => strlen($u->fcm_token),
    ])->toArray();

    if ($users->isEmpty()) {
        $results['error'] = '❌ No users with FCM tokens!';
        return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 4. Send FCM to each user and capture FULL Google response
    $results['step'][] = '4️⃣ Sending test notifications (showing raw Google response)';
    $results['send_results'] = [];
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    foreach ($users as $user) {
        $payload = [
            'message' => [
                'token' => $user->fcm_token,
                'notification' => [
                    'title' => '🔔 Test FCM - ' . now()->format('H:i:s'),
                    'body'  => 'Halo ' . $user->name . '! Jika muncul, FCM berfungsi!',
                ],
                'data' => [
                    'notification_type' => 'order',
                    'type' => 'test',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'notif_order_alert',
                    ],
                ],
            ],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post($url, $payload);

            $results['send_results'][] = [
                'user'              => $user->name,
                'http_status'       => $response->status(),
                'google_response'   => $response->json(),
                'verdict'           => $response->successful() ? '✅ Google accepted' : '❌ Google rejected',
            ];
        } catch (\Exception $e) {
            $results['send_results'][] = [
                'user'    => $user->name,
                'verdict' => '❌ HTTP Error: ' . $e->getMessage(),
            ];
        }
    }

    $results['timestamp'] = now()->toDateTimeString();
    $results['important_notes'] = [
        'Jika Google accepted tapi HP tidak muncul notif:',
        '1. Pastikan sudah install APK baru (setelah namespace fix)',
        '2. WAJIB uninstall dulu app lama, lalu install ulang',
        '3. Login ulang agar token FCM baru terdaftar',
        '4. Cek: Settings > Apps > Smart Kasir Pro > Notifications > pastikan ON',
        '5. Jika google_response berisi error UNREGISTERED = token expired, perlu login ulang',
    ];

    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ->header('Content-Type', 'application/json');
});

require __DIR__.'/auth.php';
