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
            
            $transaction = Transaction::create([
                'transaction_code' => Transaction::generateTransactionCode(),
                'user_id' => auth()->id(), // Authenticated mobile user
                'shift_id' => $openShift ? $openShift->id : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'amount_paid' => $isDigitalPayment ? $totalAmount : $amountPaid,
                'change_amount' => $isDigitalPayment ? 0 : $changeAmount,
                'status' => $isDigitalPayment ? 'pending' : 'completed',
                'customer_name' => $request->customer_name ?? 'Umum',
                'note' => $request->note . ($request->created_at ? " (Offline Sync)" : ""),
                'points_earned' => $isDigitalPayment ? 0 : $pointsEarned,
                'points_redeemed' => $pointsRedeemed,
                'points_discount_amount' => $pointsDiscountAmount,
                'created_at' => $transactionDate,
                'updated_at' => now(), // Record when it was synced to server
            ]);

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

                // Update product stock (BYPASS FOR JASA & digital payment pending)
                if ($item['product']->type !== 'jasa' && !$isDigitalPayment) {
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

            // Load transaction with items for receipt
            $transaction->load(['items.product', 'user']);

            // === PAYMENT GATEWAY LOGIC ===
            if ($isDigitalPayment) {
                $pgResult = $pgService->createTransaction($transaction, $request->payment_method);
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
                    );
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
                'message' => $e->getMessage(),
            ], 422); // Unprocessable Entity
        }
    }
}
