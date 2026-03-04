<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\PaymentGatewayService;
use App\Jobs\SendWhatsappNotification;
use App\Services\WhatsappService;

class PosController extends Controller
{
    /**
     * Display the POS interface.
     */
    public function index()
    {
        $categories = Category::all();
        $customers = \App\Models\Customer::orderBy('name')->get();
        $employees = \App\Models\Employee::where('status', 'active')->orderBy('name')->get();
        $storeSettings = \App\Models\Setting::getStoreSettings(); // Tambahkan ini
        $tables = \App\Models\Table::orderBy('nama_meja')->get();
        return view('pos.index', compact('categories', 'customers', 'employees', 'storeSettings', 'tables'));
    }

    /**
     * Search products for POS.
     */
    // public function searchProducts(Request $request)
    // {
    //     $query = $request->get('q');
    //     $category = $request->get('category');
    //     $page = $request->get('page', 1);
    //     $perPage = $request->get('per_page', 10);

    //     $productsQuery = Product::with('category')
    //         ->where('stock', '>', 0);

    //     // Filter by category if specified
    //     if ($category && $category !== 'all') {
    //         $productsQuery->whereHas('category', function($q) use ($category) {
    //             $q->where('id', $category);
    //         });
    //     }

    //     // Filter by search query if specified
    //     if (!empty($query) && strlen($query) >= 2) {
    //         $productsQuery->where(function ($q) use ($query) {
    //             $q->where('name', 'like', "%{$query}%")
    //               ->orWhere('barcode', 'like', "%{$query}%");
    //         });
    //     }

    //     // Paginate results
    //     $products = $productsQuery
    //         ->skip(($page - 1) * $perPage)
    //         ->take($perPage)
    //         ->get(['id', 'name', 'barcode', 'selling_price', 'stock', 'category_id', 'image']);

    //     $total = $productsQuery->count();

    //     return response()->json([
    //         'products' => $products,
    //         'total' => $total,
    //         'current_page' => $page,
    //         'per_page' => $perPage,
    //         'last_page' => ceil($total / $perPage)
    //     ]);
    // }

    public function searchProducts(Request $request)
    {
        // Mengambil parameter dari request
        $query = $request->get('q');
        $category = $request->get('category');
        $type = $request->get('type');
        $perPage = $request->get('per_page', 10);

        // Membangun query dasar ke ProductGroup
        $groupsQuery = \App\Models\ProductGroup::with('products');

        // Filter berdasarkan kategori
        if ($category && $category !== 'all') {
            $groupsQuery->where('category_id', $category);
        }

        // Filter berdasarkan query pencarian
        if (!empty($query)) {
            $groupsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhereHas('products', function($pq) use ($query) {
                      $pq->where('barcode', 'like', "%{$query}%")
                         ->orWhere('name', 'like', "%{$query}%");
                  });
            });
        }

        // Filter berdasarkan tipe produk (Barang / Jasa)
        if (!empty($type) && in_array($type, ['barang', 'jasa'])) {
            $groupsQuery->whereHas('products', function($pq) use ($type) {
                $pq->where('type', $type);
            });
            // We also need to filter the relation so only products of this type are loaded
            $groupsQuery->with(['products' => function($pq) use ($type) {
                $pq->where('type', $type);
            }]);
        }

        // Paginate results
        $groups = $groupsQuery->latest()->paginate($perPage);

        // Transform collection untuk format yang sesuai dengan Frontend POS
        $groups->getCollection()->transform(function ($group) {
            if ($group->has_variants) {
                // Logic untuk Produk Varian
                $minPrice = $group->products->min('selling_price');
                $maxPrice = $group->products->max('selling_price');
                $totalStock = $group->products->sum('stock');
                $firstProduct = $group->products->first(); // Ambil satu untuk gambar

                return [
                    'id' => $group->id, // Group ID
                    'name' => $group->name,
                    'is_group' => true,
                    'image' => $firstProduct ? $firstProduct->image : null,
                    'price_display' => ($minPrice == $maxPrice) ? $minPrice : "$minPrice - $maxPrice", // Raw numbers for formatter later or pre-format? Let's use raw for consistency if possible, or pre-formatted string. 
                    // Frontend 'formatRupiah' expects number. Let's send min price as 'selling_price' for sorting/display base.
                    'selling_price' => $minPrice, 
                    'stock' => $totalStock,
                    'type' => $firstProduct ? $firstProduct->type : 'barang',
                    'variants' => $group->products->map(function($v) {
                        return [
                            'id' => $v->id,
                            'name' => $v->variant_name, // Just variant name "XL"
                            'full_name' => $v->name, // "Kaos (XL)"
                            'price' => $v->selling_price,
                            'stock' => $v->stock,
                            'image' => $v->image,
                            'type' => $v->type
                        ];
                    })
                ];
            } else {
                // Logic untuk Produk Satuan (Single)
                $product = $group->products->first();
                if (!$product) return null; // Should not happen

                return [
                    'id' => $product->id, // Product ID (Direct Add)
                    'name' => $product->name,
                    'is_group' => false,
                    'image' => $product->image,
                    'selling_price' => $product->selling_price,
                    'stock' => $product->stock,
                    'type' => $product->type,
                    'variants' => []
                ];
            }
        });

        // Filter out nulls (if any empty groups)
        // $groups->setCollection($groups->getCollection()->filter());

        return response()->json($groups);
    }

    public function getCategories()
    {
        $categories = \App\Models\Category::select('id', 'name')->get();
        return response()->json($categories);
    }

    /**
     * RESTO MODE: Tampilan Kitchen (Dapur)
     */
    public function kitchen()
    {
        $settings = \App\Models\Setting::getStoreSettings();
        if ($settings->business_mode !== 'resto') {
            return redirect()->route('dashboard')->with('error', 'Fitur Kitchen View hanya tersedia di mode Resto');
        }
        return view('pos.kitchen', compact('settings'));
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
                    'table_name' => $t->table ? $t->table->nama_meja : 'Unknown',
                    'order_status' => $t->order_status,
                    'time_ago' => $t->created_at->diffForHumans(),
                    'items' => $t->items->map(function($i) {
                        return [
                            'name' => $i->product_name,
                            'qty' => $i->quantity,
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
            
            // Note: If marked as completed by kitchen/cashier, and payment is done, 
            // trigger observer logic if not already triggered (Phase 5 check)
            
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
                    'table_id' => $t->table_id,
                    'transaction_code' => $t->transaction_code,
                    'table_name' => $t->table ? $t->table->nama_meja : 'Unknown',
                    'customer_name' => $t->customer_name,
                    'order_status' => $t->order_status,
                    'payment_status' => $t->payment_status,
                    'payment_method' => $t->payment_method,
                    'time_ago' => $t->created_at->diffForHumans(),
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
                            'image' => '' // Can be populated if needed
                        ];
                    })
                ];
            });

        return response()->json($orders);
    }

    /**
     * Show product search results for POS.
     */
    public function show(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $products = Product::with('category')
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->take(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'selling_price' => (float) $product->selling_price,
                    'stock' => $product->stock,
                    'type' => $product->type,
                    'is_low_stock' => $product->is_low_stock,
                ];
            });

        return response()->json($products);
    }

    /**
     * Process transaction.
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
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $items = [];

            // Process each item and calculate subtotal
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability (Bypass stok untuk Jasa)
                if ($product->type !== 'jasa' && $product->stock < $item['quantity']) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}");
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

            // Gunakan nilai diskon dan pajak dari request
            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            
            // Points Logic
            $pointsRedeemed = $request->points_redeemed ?? 0;
            $pointsDiscountAmount = 0;
            $pointsEarned = 0;
            
            $storeSettings = \App\Models\Setting::getStoreSettings();
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
            
            // Validasi: Total harus dihitung ulang di backend untuk keamanan
            // Total = (Subtotal - Diskon) + Pajak
            // Pastikan diskon tidak melebihi subtotal
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            
            $totalAmount = max(0, $subtotal - $discount - $pointsDiscountAmount + $tax);

            // === Fee Payment Gateway ===
            $pgService = new PaymentGatewayService();
            $pgFee = 0;
            
            $paymentMethod = $request->payment_method;
            if ($pgService->isDigitalPayment($paymentMethod) && !$pgService->isActive()) {
                // Fallback to cash if PG is disabled but digital method is selected
                $paymentMethod = 'cash';
            }
            
            $isDigitalPayment = $pgService->isActive() && $pgService->isDigitalPayment($paymentMethod);
            if ($isDigitalPayment) {
                $pgFee = $pgService->calculateFee($totalAmount, $paymentMethod);
                $totalAmount += $pgFee;
            }

            $amountPaid = $request->amount_paid;
            $changeAmount = 0;

            if ($isDigitalPayment) {
                // Digital: amount = total, tidak ada kembalian
                $amountPaid = $totalAmount;
                $changeAmount = 0;
            } elseif ($paymentMethod === 'utang') {
                $changeAmount = 0; // Utang tidak ada kembalian di POS standar
            } else {
                 $changeAmount = $amountPaid - $totalAmount;
                 if ($changeAmount < 0) {
                     throw new \Exception('Jumlah pembayaran kurang dari total belanja.');
                 }
            }

            // Backdate Logic
            $transactionDate = now();
            if ($request->filled('transaction_date') && auth()->user()->hasPermission('can_backdate_sales')) {
                try {
                    $inputDate = \Carbon\Carbon::parse($request->transaction_date);
                    // Keep the current time, just change the date
                    $transactionDate = $inputDate->setTimeFrom(now());
                } catch (\Exception $e) {
                    // Ignore invalid date, use now()
                }
            }

            // Get open shift
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

            // Create or Update transaction
            if ($request->filled('pending_order_code')) {
                // Gunakan Eloquent dengan lockForUpdate untuk mencegah race conditions saat pembayaran bersamaan
                $transaction = Transaction::where('transaction_code', $request->pending_order_code)->lockForUpdate()->firstOrFail();
                
                $transaction->update([
                    'shift_id' => $openShift ? $openShift->id : null,
                    'table_id' => $request->table_id ?? $transaction->table_id, // Gunakan dari request atau pertahankan yang lama
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $isDigitalPayment ? $totalAmount : $amountPaid,
                    'change_amount' => $isDigitalPayment ? 0 : $changeAmount,
                    'status' => $isDigitalPayment ? 'pending' : 'completed',
                    'order_status' => 'completed', // Memastikan pesanan hilang dari "Antrean Pesanan Masuk"
                    'payment_status' => $isDigitalPayment ? 'unpaid' : 'paid',
                    'note' => $request->note,
                    'points_earned' => $isDigitalPayment ? 0 : $pointsEarned,
                    'points_redeemed' => $pointsRedeemed,
                    'points_discount_amount' => $pointsDiscountAmount,
                    'updated_at' => $transactionDate,
                ]);

                // Hapus item lama agar diganti dengan yang baru dari POS (jika kasir ada modifikasi)
                $transaction->items()->delete();
                
            } else {
                $transaction = Transaction::create([
                    'transaction_code' => Transaction::generateTransactionCode(),
                    'user_id' => auth()->id(),
                    'shift_id' => $openShift ? $openShift->id : null,
                    'customer_name' => $customer ? $customer->name : ($request->customer_name ?: 'Umum'),
                    'customer_phone' => $customer ? $customer->phone : null,
                    'is_self_order' => false,
                    'table_id' => $request->table_id, // <-- Add this
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $isDigitalPayment ? $totalAmount : $amountPaid,
                    'change_amount' => $isDigitalPayment ? 0 : $changeAmount,
                    'status' => $isDigitalPayment ? 'pending' : 'completed',
                    'order_status' => $storeSettings->business_mode === 'resto' ? 'pending' : 'completed',
                    'payment_status' => $isDigitalPayment ? 'unpaid' : (($paymentMethod === 'utang') ? 'unpaid' : 'paid'),
                    'customer_name' => $request->customer_name ?? 'Umum',
                    'note' => $request->note,
                    'points_earned' => $isDigitalPayment ? 0 : $pointsEarned,
                    'points_redeemed' => $pointsRedeemed,
                    'points_discount_amount' => $pointsDiscountAmount,
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate,
                ]);
            }

            // Create transaction items and update stock
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
                    'updated_at' => $transactionDate,
                ]);

                // Update product stock (Bypass Jasa & digital payment pending)
                if ($item['product']->type !== 'jasa' && !$isDigitalPayment) {
                    $item['product']->decrement('stock', $item['quantity']);

                    // Record stock movement
                    StockMovement::create([
                        'product_id' => $item['product']->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reference_type' => 'App\Models\Transaction',
                        'reference_id' => $transaction->id,
                        'notes' => "Penjualan - {$transaction->transaction_code}",
                        'created_at' => $transactionDate,
                        'updated_at' => $transactionDate,
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
                \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi: ' . $e->getMessage());
            }

            $transaction->load(['items.product', 'user']);

            // === PAYMENT GATEWAY LOGIC ===
            if ($isDigitalPayment) {
                // Panggil payment gateway API untuk mendapatkan URL pembayaran
                $pgResult = $pgService->createTransaction($transaction, $paymentMethod, $request->payment_channel);
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
                'message' => 'Transaksi berhasil diproses.',
                'transaction' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $transaction->load(['items.product', 'user']);

        return response()->json($transaction);
    }

    /**
     * Check the payment status of a transaction (for PG polling).
     */
    public function checkStatus(string $code)
    {
        $transaction = Transaction::where('transaction_code', $code)->first();

        if (!$transaction) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $transaction->status,
            'transaction_code' => $transaction->transaction_code,
            'pg_provider' => $transaction->pg_provider,
        ]);
    }

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
}
