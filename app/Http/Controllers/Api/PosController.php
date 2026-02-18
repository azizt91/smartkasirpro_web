<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'payment_method' => 'required|in:cash,utang,card,ewallet,transfer,qris',
            'amount_paid' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string',
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

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi. Server: {$product->stock}, Req: {$item['quantity']}");
                }

                $itemSubtotal = (float) $product->selling_price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->selling_price,
                    'subtotal' => $itemSubtotal,
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

            $totalAmount = round(max(0, $subtotal - $discount + $tax)); // Round to avoid float mismatch with mobile
            $amountPaid = $request->amount_paid;
            
            $changeAmount = 0;
            if ($request->payment_method !== 'utang') {
                  // Server-side Validation: Prevent Underpayment
                  if ($amountPaid < $totalAmount) {
                      throw new \Exception("Nominal pembayaran kurang! Total: {$totalAmount}, Bayar: {$amountPaid}");
                  }
                  $changeAmount = $amountPaid - $totalAmount;
            }

            // 3. Create Transaction
            $transactionDate = $request->created_at ? \Carbon\Carbon::parse($request->created_at) : now();
            
            $transaction = Transaction::create([
                'transaction_code' => Transaction::generateTransactionCode(),
                'user_id' => auth()->id(), // Authenticated mobile user
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'customer_name' => $request->customer_name ?? 'Umum',
                'note' => $request->note . ($request->created_at ? " (Offline Sync)" : ""),
                'created_at' => $transactionDate,
                'updated_at' => now(), // Record when it was synced to server
            ]);

            // 4. Create Items & Update Stock
            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => $transactionDate,
                    'updated_at' => now(),
                ]);

                // Update product stock
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
