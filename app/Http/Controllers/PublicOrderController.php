<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Product;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentGatewayService;
use App\Services\WhatsappService;

class PublicOrderController extends Controller
{
    /**
     * Tampilkan Halaman Menu Utama untuk Pelanggan (via QR Scan)
     */
    public function showMenu($hash)
    {
        $table = Table::where('hash_slug', $hash)->firstOrFail();
        
        $settings = Setting::getStoreSettings();
        if ($settings->business_mode !== 'resto') {
            abort(404, 'Fitur pemesanan mandiri tidak aktif.');
        }

        // Ambil kategori yang ada produknya
        $categories = Category::whereHas('products', function($q) {
            $q->where('stock', '>', 0)->orWhere('type', 'jasa');
        })->get();

        $transferChannels = [];
        $ewalletChannels = [];
        if ($settings->pg_active && $settings->pg_active !== 'none') {
            $pgService = new PaymentGatewayService();
            $transferChannels = $pgService->getAvailableChannels('transfer');
            $ewalletChannels = $pgService->getAvailableChannels('ewallet');
        }

        return view('public.order', compact('table', 'categories', 'settings', 'transferChannels', 'ewalletChannels'));
    }

    /**
     * API Ambil data produk berdasarkan kategori untuk Public Menu
     */
    public function getProducts(Request $request)
    {
        $categoryId = $request->get('category_id');
        $query = Product::where(function($q) {
            $q->where('stock', '>', 0)->orWhere('type', 'jasa');
        });

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->selling_price,
                'image' => $product->image ? asset('storage/' . $product->image) : null,
                'description' => $product->description ?? ''
            ];
        });

        return response()->json($products);
    }

    /**
     * Submit Pesanan dari Pelanggan
     */
    public function submitOrder(Request $request, $hash)
    {
        $table = Table::where('hash_slug', $hash)->firstOrFail();
        $settings = Setting::getStoreSettings();

        // Validasi keranjang
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,qris,transfer,ewallet,card', // cash = bayar di kasir, lainnya = online / pg
            'payment_channel' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // 1. Hitung ulang subtotal untuk keamanan (jangan percaya data harga dari frontend)
            $subtotal = 0;
            $itemsData = [];
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['id']);
                
                // Cek stok fisik (selain jasa)
                if ($product->type !== 'jasa' && $product->stock < $item['qty']) {
                    throw new \Exception("Maaf, stok {$product->name} tidak mencukupi.");
                }

                $itemTotal = $product->selling_price * $item['qty'];
                $subtotal += $itemTotal;
                
                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['qty'],
                    'price' => $product->selling_price,
                    'subtotal' => $itemTotal,
                    'capital_price' => $product->capital_price,
                    'type' => $product->type
                ];
            }

            // 2. Kalkulasi Pajak
            $taxAmount = 0;
            if ($settings->tax_rate > 0) {
                $taxAmount = $subtotal * ($settings->tax_rate / 100);
            }
            $totalAmount = $subtotal + $taxAmount;

            // 3. Simpan Referensi Pelanggan ke Database Customers jika ada nama
            $paymentMethod = $request->payment_method;
            if(in_array($paymentMethod, ['qris','transfer','ewallet','card']) && $settings->pg_active === 'none') {
                $paymentMethod = 'cash'; // Fallback jika payment gateway ternyata mati
            }
            
            if (!empty($request->customer_name)) {
                \App\Models\Customer::firstOrCreate(
                    ['phone' => $request->customer_phone ?? '-'],
                    ['name' => $request->customer_name, 'points' => 0]
                );
            }

            // 4. Buat Transaksi (Status masih 'pending' / Belum Dibayar)

            $transaction = Transaction::create([
                'transaction_code' => Transaction::generateTransactionCode(),
                'user_id' => 1, // Sistem/Admin default penginput
                'table_id' => $table->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'is_self_order' => true,
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => 0, // Belum lunas
                'change_amount' => 0,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid', // <-- Belum bayar
                'order_status' => 'pending',   // <-- Antrean masuk
                'status' => 'pending', // Supaya tidak langsung memotong stok, tampil sbg Pending di Riwayat
            ]);

            // 4. Simpan Item Transaksi
            foreach ($itemsData as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'capital_price' => $item['capital_price'],
                ]);
            }

            $payUrl = null;

            // 5. Jika memilih non-cash (Qris/E-Wallet/Transfer) via Payment Gateway
            if ($paymentMethod !== 'cash' && $settings->pg_active !== 'none') {
                $pgService = new PaymentGatewayService();
                $channel = $request->payment_channel ?? '';
                
                try {
                    // Gunakan tripay/duitku dll
                    $pgResponse = $pgService->createTransaction($transaction, $paymentMethod, $channel);
                    
                    $transaction->update([
                        'pg_provider' => $settings->pg_active,
                        'pg_reference' => $pgResponse['reference'],
                        'pg_pay_url' => $pgResponse['pay_url'] ?? $pgResponse['qr_url'],
                        'pg_expired_at' => $pgResponse['expired_at']
                    ]);
                    $payUrl = $pgResponse['pay_url'] ?? $pgResponse['qr_url'];
                } catch (\Exception $e) {
                    \Log::error("Payment Gateway Error during Public Order: " . $e->getMessage());
                    // Jika gagal buat link PG, fallback ke cash (bayar di kasir)
                    $transaction->update(['payment_method' => 'cash']);
                }
            }

            DB::commit();

            // Ubah Status Meja Menjadi Occupied
            $table->update(['status' => 'occupied']);

            // === SEND PUSH NOTIFICATION TO CASHIERS/ADMINS ===
            try {
                $transaction->load('items.product'); // Load items for the notification summary
                $usersToNotify = \App\Models\User::whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->get();
                    
                if ($usersToNotify->count() > 0) {
                    \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\OrderCreated($transaction));
                }
            } catch (\Exception $e) {
                \Log::error("Gagal mengirim Notifikasi Pesanan Baru via FCM: " . $e->getMessage());
            }

            // Jika ada nomor WA pelanggan, kirim struk ringkas
            if ($settings->enable_wa_notification && $request->customer_phone && $settings->fonnte_token) {
                $waService = new WhatsappService($settings->fonnte_token);
                $storeName = $settings->store_name;
                $url = route('receipt.public', ['code' => $transaction->transaction_code]);
                
                $message = "*Halo {$request->customer_name}*, pesanan Anda di *{$storeName}* telah kami terima.\n\n";
                $message .= "🛒 *Detail Pesanan:*\n";
                $message .= "- No. Pesanan: {$transaction->transaction_code}\n";
                $message .= "- Meja: {$table->nama_meja}\n";
                $message .= "- Total Tagihan: Rp " . number_format($transaction->total_amount, 0, ',', '.') . "\n\n";
                
                if ($payUrl) {
                    $message .= "💳 Silakan selesaikan pembayaran melalui link berikut ini agar pesanan segera kami proses:\n{$payUrl}\n\n";
                } elseif ($transaction->payment_method === 'cash') {
                    $message .= "💵 Silakan selesaikan pembayaran di kasir.\n\n";
                }
                
                $message .= "🧾 Anda dapat melihat struk digital & status pesanan di sini:\n{$url}\n\n";
                $message .= "Terima kasih banyak!";
                
                // Gunakan background process (dispatch) jika memungkinkan, namun minimal tembak API Fonnte
                try {
                    $waService->sendMessage($request->customer_phone, $message);
                } catch (\Exception $e) {
                    // Log error WA notif tapi tidak membatalkan transaksi utama
                    \Log::error("Gagal mengirim WA: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'transaction_code' => $transaction->transaction_code,
                'pay_url' => $payUrl,
                'redirect' => route('public.order.success', ['code' => $transaction->transaction_code])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Tampilkan Halaman Sukses Pemesanan
     */
    public function success($code)
    {
        $transaction = Transaction::with('table')->where('transaction_code', $code)->firstOrFail();
        $settings = Setting::getStoreSettings();
        
        return view('public.success', compact('transaction', 'settings'));
    }
}
