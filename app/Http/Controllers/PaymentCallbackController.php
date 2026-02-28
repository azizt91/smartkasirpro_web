<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle Tripay webhook callback.
     */
    public function tripay(Request $request)
    {
        Log::info('[Tripay Webhook] Received', ['payload' => $request->all()]);

        // 1. Verify Signature
        $settings = Setting::getStoreSettings();
        $privateKey = $settings->tripay_private_key;

        $callbackSignature = $request->header('X-Callback-Signature');
        $json = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $json, $privateKey);

        if (!$callbackSignature || !hash_equals($expectedSignature, $callbackSignature)) {
            Log::warning('[Tripay Webhook] Invalid signature', [
                'expected' => $expectedSignature,
                'received' => $callbackSignature,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // 2. Process
        $merchantRef = $request->input('merchant_ref');
        $status = $request->input('status');

        $transaction = Transaction::where('transaction_code', $merchantRef)
            ->where('pg_provider', 'tripay')
            ->first();

        if (!$transaction) {
            Log::warning('[Tripay Webhook] Transaction not found', ['merchant_ref' => $merchantRef]);
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if ($status === 'PAID') {
            $this->completeTransaction($transaction);
            Log::info('[Tripay Webhook] Payment confirmed', ['code' => $merchantRef]);
        } elseif (in_array($status, ['EXPIRED', 'FAILED'])) {
            $transaction->update(['status' => 'cancelled']);
            Log::info('[Tripay Webhook] Payment cancelled/expired', ['code' => $merchantRef, 'status' => $status]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle Duitku webhook callback.
     */
    public function duitku(Request $request)
    {
        Log::info('[Duitku Webhook] Received', ['payload' => $request->all()]);

        // 1. Verify Signature
        $settings = Setting::getStoreSettings();
        $apiKey = $settings->duitku_api_key;
        $merchantCode = $settings->duitku_merchant_code;

        $merchantOrderId = $request->input('merchantOrderId');
        $amount = $request->input('amount');

        // Duitku callback signature: md5(merchantCode + amount + merchantOrderId + apiKey)
        $expectedSignature = md5($merchantCode . $amount . $merchantOrderId . $apiKey);
        $receivedSignature = $request->input('signature');

        if (!$receivedSignature || !hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('[Duitku Webhook] Invalid signature', [
                'expected' => $expectedSignature,
                'received' => $receivedSignature,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // 2. Process
        $resultCode = $request->input('resultCode');

        $transaction = Transaction::where('transaction_code', $merchantOrderId)
            ->where('pg_provider', 'duitku')
            ->first();

        if (!$transaction) {
            Log::warning('[Duitku Webhook] Transaction not found', ['merchantOrderId' => $merchantOrderId]);
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if ($resultCode === '00') {
            $this->completeTransaction($transaction);
            Log::info('[Duitku Webhook] Payment confirmed', ['code' => $merchantOrderId]);
        } elseif ($resultCode === '01') {
            $transaction->update(['status' => 'cancelled']);
            Log::info('[Duitku Webhook] Payment expired/failed', ['code' => $merchantOrderId]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle Midtrans webhook callback (notification URL).
     */
    public function midtrans(Request $request)
    {
        Log::info('[Midtrans Webhook] Received', ['payload' => $request->all()]);

        // 1. Verify Signature Key
        $settings = Setting::getStoreSettings();
        $serverKey = $settings->midtrans_server_key;

        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');

        // Midtrans signature: hash('sha512', orderId + statusCode + grossAmount + serverKey)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $receivedSignature = $request->input('signature_key');

        if (!$receivedSignature || !hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('[Midtrans Webhook] Invalid signature', [
                'expected' => $expectedSignature,
                'received' => $receivedSignature,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // 2. Process
        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        $transaction = Transaction::where('transaction_code', $orderId)
            ->where('pg_provider', 'midtrans')
            ->first();

        if (!$transaction) {
            Log::warning('[Midtrans Webhook] Transaction not found', ['order_id' => $orderId]);
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        // Payment successful
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            if ($transactionStatus === 'capture' && $fraudStatus !== 'accept') {
                Log::warning('[Midtrans Webhook] Fraud detected', ['order_id' => $orderId]);
                return response()->json(['success' => true, 'message' => 'Fraud review']);
            }
            $this->completeTransaction($transaction);
            Log::info('[Midtrans Webhook] Payment confirmed', ['code' => $orderId]);
        }
        // Payment failed/expired/cancelled
        elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $transaction->update(['status' => 'cancelled']);
            Log::info('[Midtrans Webhook] Payment cancelled', ['code' => $orderId, 'status' => $transactionStatus]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Complete a pending transaction: reduce stock, earn points, send notification.
     * Wrapped in DB::transaction for atomicity.
     */
    protected function completeTransaction(Transaction $transaction): void
    {
        if ($transaction->status === 'completed') {
            Log::info('[PaymentCallback] Transaction already completed', ['code' => $transaction->transaction_code]);
            return; // Idempotent: skip if already completed
        }

        DB::transaction(function () use ($transaction) {
            // 1. Update status to completed
            $transaction->update(['status' => 'completed']);

            // 2. Load items with products
            $transaction->load('items.product');

            // 3. Reduce stock & record stock movement
            foreach ($transaction->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                // Bypass stock deduction for services
                if ($product->type === 'jasa') continue;

                $product->decrement('stock', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'App\Models\Transaction',
                    'reference_id' => $transaction->id,
                    'notes' => "Pembayaran Online - {$transaction->transaction_code}",
                ]);
            }

            // 4. Earn loyalty points (if applicable)
            $settings = Setting::getStoreSettings();
            if ($settings->enable_loyalty_points && $transaction->customer_name && $transaction->customer_name !== 'Umum') {
                $customer = \App\Models\Customer::where('name', $transaction->customer_name)->first();
                $pointEarningRate = $settings->point_earning_rate ?? 10000;

                if ($customer && $pointEarningRate > 0) {
                    $pointsEarned = floor((float) $transaction->total_amount / $pointEarningRate);
                    if ($pointsEarned > 0) {
                        $customer->increment('points', $pointsEarned);
                        $transaction->update(['points_earned' => $pointsEarned]);
                    }
                }
            }

            // 5. Send AuditAlert notification to owner
            try {
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\AuditAlert(
                        "💳 Pembayaran Online Diterima",
                        "Transaksi {$transaction->transaction_code} senilai Rp " . number_format((float) $transaction->total_amount, 0, ',', '.') . " telah lunas via {$transaction->pg_provider}."
                    ));
                }
            } catch (\Exception $e) {
                Log::error('[PaymentCallback] Notification error: ' . $e->getMessage());
            }
        });
    }
}
