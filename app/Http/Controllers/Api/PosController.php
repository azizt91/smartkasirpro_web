<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentGatewayService;
use App\Jobs\SendWhatsappNotification;
use App\Services\WhatsappService;

class PosController extends Controller
{
    /**
     * Process transaction from mobile app.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.employee_id' => 'nullable|exists:employees,id',
            'payment_method' => 'required|in:cash,utang,card,ewallet,transfer,qris',
            'amount_paid' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string',
            'points_redeemed' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
            'created_at' => 'nullable|date', // Allow mobile to send offline timestamp
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $items = [];
            $syncErrors = [];

            // 1. Process items and validate stock (Server is Truth)
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    $syncErrors[] = "Product ID {$item['product_id']} not found.";
                    continue;
                }

                // Check stock availability (BYPASS FOR JASA)
                if ($product->type !== 'jasa' && $product->stock < $item['quantity']) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi. Server: {$product->stock}, Req: {$item['quantity']}");
                }

                $itemSubtotal = (float) $product->selling_price * $item['quantity'];
                $subtotal += $itemSubtotal;

                // Hitung Komisi jika Jasa
                $commissionAmount = 0;
                if ($product->type === 'jasa' && $product->commission_amount > 0) {
                    if ($product->commission_type === 'percentage') {
                        $commissionAmount = ($itemSubtotal * $product->commission_amount) / 100;
                    } else {
                        $commissionAmount = $product->commission_amount * $item['quantity'];
                    }
                }

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->selling_price,
                    'subtotal' => $itemSubtotal,
                    'employee_id' => $item['employee_id'] ?? null,
                    'commission_amount' => $commissionAmount,
                ];
            }

            // 2. Calculate Totals
            // For now, simple logic. Add Tax/Discount logic here if needed from settings.
            $storeSettings = \App\Models\Setting::first();
            $taxRate = $storeSettings ? $storeSettings->tax_rate : 0;
            
            // Assume tax is included or excluded? 
            // Existing PosController logic used $request->tax. 
            // For mobile, we might want to let server calculate based on settings.
            // Let's assume simple calculation for now: Total = Subtotal. 
            // If Tax is needed, we calculate it here.
            
            $discount = 0; // Or passed from request if allowed
            $tax = 0; // ($subtotal - $discount) * ($taxRate / 100); 

            // Points Logic
            $pointsRedeemed = $request->points_redeemed ?? 0;
            $pointsDiscountAmount = 0;
            $pointsEarned = 0;
            
            $pointExchangeRate = $storeSettings->point_exchange_rate ?? 100;
            $pointEarningRate = $storeSettings->point_earning_rate ?? 10000;
            
            $customer = null;
            if ($request->customer_name && $request->customer_name !== 'Umum') {
                $customer = \App\Models\Customer::where('name', $request->customer_name)->first();
            }
            
            if ($customer && $pointsRedeemed > 0) {
                if ($pointsRedeemed > $customer->points) {
                    $pointsRedeemed = $customer->points; // Cap to max points
                }
                $pointsDiscountAmount = $pointsRedeemed * $pointExchangeRate;
                $customer->decrement('points', $pointsRedeemed);
            } else {
                $pointsRedeemed = 0;
            }

            $totalAmount = round(max(0, $subtotal - $discount - $pointsDiscountAmount + $tax)); // Round to avoid float mismatch with mobile

            // === Fee Payment Gateway ===
            $pgService = new PaymentGatewayService();
            $pgFee = 0;
            $isDigitalPayment = $pgService->isActive() && $pgService->isDigitalPayment($request->payment_method);
            if ($isDigitalPayment) {
                $pgFee = $pgService->calculateFee($totalAmount, $request->payment_method);
                $totalAmount += $pgFee;
            }

            $amountPaid = $request->amount_paid;
            
            $changeAmount = 0;
            if ($request->payment_method !== 'utang') {
                  // Server-side Validation: Prevent Underpayment
                  if ($amountPaid < $totalAmount) {
                      throw new \Exception("Nominal pembayaran kurang! Total: {$totalAmount}, Bayar: {$amountPaid}");
                  }
                  $changeAmount = $amountPaid - $totalAmount;
            }

            // 3. Get Open Shift & Create Transaction
            $transactionDate = $request->created_at ? \Carbon\Carbon::parse($request->created_at) : now();
            
            $openShift = \App\Models\CashierShift::where('user_id', auth()->id())
                ->where('status', 'open')
                ->first();
                
            // Earn Points (SKIP for digital payment - will be earned on webhook)
            if (!$isDigitalPayment && $customer && $pointEarningRate > 0) {
                $pointsEarned = floor($totalAmount / $pointEarningRate);
                if ($pointsEarned > 0) {
                    $customer->increment('points', $pointsEarned);
                }
            }
            
            if ($request->filled('pending_order_code')) {
                // Gunakan Eloquent dengan lockForUpdate untuk mencegah race conditions
                $transaction = Transaction::where('transaction_code', $request->pending_order_code)->lockForUpdate()->firstOrFail();
                
                $transaction->update([
                    'shift_id' => $openShift ? $openShift->id : null,
                    'table_id' => $request->table_id ?? $transaction->table_id, // Gunakan dari request atau pertahankan yang lama
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'amount_paid' => $isDigitalPayment ? $totalAmount : $amountPaid,
                    'change_amount' => $isDigitalPayment ? 0 : $changeAmount,
                    'status' => $isDigitalPayment ? 'pending' : 'completed',
                    'order_status' => 'completed', // Memastikan pesanan hilang dari "Antrean Pesanan Masuk"
                    'payment_status' => $isDigitalPayment ? 'unpaid' : (($request->payment_method === 'utang') ? 'unpaid' : 'paid'),
                    'customer_name' => $customer ? $customer->name : ($request->customer_name ?? 'Umum'),
                    'note' => $request->note . ($request->created_at ? " (Offline Sync)" : ""),
                    'points_earned' => $isDigitalPayment ? 0 : $pointsEarned,
                    'points_redeemed' => $pointsRedeemed,
                    'points_discount_amount' => $pointsDiscountAmount,
                    'updated_at' => now(),
                ]);

                // Hapus item lama agar diganti dengan yang baru dari keranjang mobile
                $transaction->items()->delete();
            } else {            
                $transaction = Transaction::create([
                    'transaction_code' => Transaction::generateTransactionCode(),
                    'user_id' => auth()->id(), // Authenticated mobile user
                    'shift_id' => $openShift ? $openShift->id : null,
                    'table_id' => $request->table_id, // <-- Add table_id
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'amount_paid' => $isDigitalPayment ? $totalAmount : $amountPaid,
                    'change_amount' => $isDigitalPayment ? 0 : $changeAmount,
                    'status' => $isDigitalPayment ? 'pending' : 'completed',
                    'order_status' => $storeSettings->business_mode === 'resto' ? 'pending' : 'completed',
                    'payment_status' => $isDigitalPayment ? 'unpaid' : (($request->payment_method === 'utang') ? 'unpaid' : 'paid'),
                    'customer_name' => $customer ? $customer->name : ($request->customer_name ?? 'Umum'),
                    'note' => $request->note . ($request->created_at ? " (Offline Sync)" : ""),
                    'points_earned' => $isDigitalPayment ? 0 : $pointsEarned,
                    'points_redeemed' => $pointsRedeemed,
                    'points_discount_amount' => $pointsDiscountAmount,
                    'created_at' => $transactionDate,
                    'updated_at' => now(), // Record when it was synced to server
                ]);
            }

            // 4. Create Items & Update Stock
            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'employee_id' => $item['employee_id'],
                    'commission_amount' => $item['commission_amount'],
                    'created_at' => $transactionDate,
                    'updated_at' => now(),
                ]);

                // Update product stock (Always deduct stock upfront)
                if ($item['product']->type !== 'jasa') {
                    $item['product']->decrement('stock', $item['quantity']);

                    // Record stock movement
                    StockMovement::create([
                        'product_id' => $item['product']->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reference_type' => 'App\Models\Transaction',
                        'reference_id' => $transaction->id,
                        'notes' => "Penjualan Mobile - {$transaction->transaction_code}",
                        'created_at' => $transactionDate,
                        'updated_at' => now(),
                    ]);
                }
            }

            // Kirim Notifikasi FCM ke semua user yang punya token
            try {
                $transaction->load('items.product');
                $usersToNotify = \App\Models\User::whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->get();
                    
                if ($usersToNotify->count() > 0) {
                    \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\OrderCreated($transaction));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi mobile: ' . $e->getMessage());
            }

            // Load transaction with items for receipt
            $transaction->load(['items.product', 'user']);

            // === PAYMENT GATEWAY LOGIC ===
            if ($isDigitalPayment) {
                $pgResult = $pgService->createTransaction($transaction, $request->payment_method, $request->payment_channel);
                $transaction->update([
                    'pg_provider' => $storeSettings->pg_active,
                    'pg_reference' => $pgResult['reference'],
                    'pg_pay_url' => $pgResult['pay_url'] ?? $pgResult['qr_url'],
                    'pg_expired_at' => $pgResult['expired_at'],
                ]);

                DB::commit();

                // Dispatch WA notification for pending digital payment (outside DB transaction)
                if (WhatsappService::isEnabled() && $customer) {
                    SendWhatsappNotification::dispatch(
                        'pending',
                        $transaction->id,
                        $customer->id,
                        [
                            'pay_url' => $pgResult['pay_url'] ?? $pgResult['qr_url'] ?? null,
                            'expired_at' => $pgResult['expired_at'] ?? null,
                        ]
                    )->onConnection('sync');
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Menunggu pembayaran...',
                    'transaction' => $transaction,
                    'payment' => [
                        'provider' => $storeSettings->pg_active,
                        'pay_url' => $pgResult['pay_url'] ?? null,
                        'qr_url' => $pgResult['qr_url'] ?? null,
                        'reference' => $pgResult['reference'],
                        'expired_at' => $pgResult['expired_at'],
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disinkronisasi.',
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * RESTO MODE: Fetch Pending/Processing Orders for POS Cashier
     */
    public function getPendingOrders()
    {
        $orders = Transaction::with(['items', 'table'])
            ->whereNotIn('status', ['cancelled', 'void'])
            ->where(function ($q) {
                // Tampilkan jika dapur belum selesai (pending/processing) ATAU kasir belum bayar (unpaid)
                $q->whereIn('order_status', ['pending', 'processing'])
                  ->orWhere('payment_status', 'unpaid');
            })
            ->orderBy('created_at', 'ASC')
            ->get()->map(function($t) {
            return [
                'id' => $t->id,
                'transaction_code' => $t->transaction_code,
                'is_takeaway' => (bool) $t->is_takeaway,
                'table_name' => $t->is_takeaway ? null : ($t->table ? $t->table->nama_meja : null),
                'customer_name' => $t->customer_name,
                'order_status' => $t->order_status,
                'payment_status' => $t->payment_status,
                'payment_method' => $t->payment_method,
                'time_ago' => $t->created_at->diffForHumans(),
                'created_at' => $t->created_at ? $t->created_at->toIso8601String() : null,
                'total_amount' => $t->total_amount,
                    'items_summary' => $t->items->map(function($i) {
                        return "{$i->quantity}x {$i->product_name}";
                    })->join(', '),
                    'items' => $t->items->map(function($i) {
                        return [
                            'id' => $i->product_id,
                            'name' => $i->product_name,
                            'price' => $i->price,
                            'qty' => $i->quantity,
                            'type' => $i->type ?? 'barang',
                            'image' => '' 
                        ];
                    })
                ];
            });

        return response()->json($orders);
    }

    /**
     * RESTO MODE: Fetch Pending/Processing Orders for Kitchen
     */
    public function getKitchenOrders()
    {
        $orders = Transaction::with(['items', 'table'])
            ->whereIn('order_status', ['pending', 'processing'])
            ->orderBy('created_at', 'ASC')
            ->get()->map(function($t) {
                return [
                    'id' => $t->id,
                    'transaction_code' => $t->transaction_code,
                    'is_takeaway' => (bool) $t->is_takeaway,
                    'table_name' => $t->is_takeaway ? null : ($t->table ? $t->table->nama_meja : null),
                    'order_status' => $t->order_status,
                    'time_ago' => $t->created_at->diffForHumans(),
                    'created_at' => $t->created_at ? $t->created_at->toIso8601String() : null,
                    'items' => $t->items->map(function($i) {
                        return [
                            'name' => $i->product_name,
                            'qty' => $i->quantity,
                            'note' => $i->note,
                        ];
                    })
                ];
            });

        return response()->json($orders);
    }
    
    /**
     * RESTO MODE: Update Kitchen Order Status
     */
    public function updateOrderStatus(Request $request, $code)
    {
        $transaction = Transaction::where('transaction_code', $code)->firstOrFail();
        $status = $request->get('status');
        
        if (in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
            $updateData = ['order_status' => $status];
            if ($status === 'cancelled') {
                $updateData['status'] = 'cancelled';
            }
            $transaction->update($updateData);
            
            // Update table status if completed or cancelled
            if (in_array($status, ['completed', 'cancelled'])) {
                if ($transaction->table_id) {
                    $table = \App\Models\Table::find($transaction->table_id);
                    if ($table) {
                        $table->update(['status' => 'available']);
                    }
                }
            }
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Get available payment channels for mobile.
     */
    public function paymentChannels(Request $request, PaymentGatewayService $pgService)
    {
        $method = $request->query('method');
        if (!$method || !in_array($method, ['ewallet', 'transfer', 'qris'])) {
            return response()->json(['success' => false, 'message' => 'Invalid method']);
        }

        $channels = $pgService->getAvailableChannels($method);

        return response()->json([
            'success' => true,
            'data' => $channels
        ]);
    }

    /**
     * Get available tables for mobile POS.
     */
    public function getTables()
    {
        $tables = \App\Models\Table::all();
        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }
}
