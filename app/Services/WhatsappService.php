<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $token;
    protected string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? (Setting::getStoreSettings()->fonnte_token ?? '');
    }

    /**
     * Check if WA notification is enabled and token is configured.
     */
    public static function isEnabled(): bool
    {
        $settings = Setting::getStoreSettings();
        return $settings->enable_wa_notification && !empty($settings->fonnte_token);
    }

    /**
     * Get customer phone number by ID. Returns null if customer not found,
     * is 'Umum', or has no phone number.
     */
    public static function getCustomerPhone(?int $customerId): ?string
    {
        if (!$customerId) return null;

        $customer = Customer::find($customerId);
        if (!$customer || empty($customer->phone)) return null;

        return $customer->phone;
    }

    /**
     * Get customer name by ID.
     */
    public static function getCustomerName(?int $customerId): string
    {
        if (!$customerId) return 'Pelanggan';

        $customer = Customer::find($customerId);
        return $customer ? $customer->name : 'Pelanggan';
    }

    /**
     * Format phone number to international format (628xx).
     */
    public static function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 08xx to 628xx
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 prefix if not present
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Send a WhatsApp message via Fonnte API.
     */
    public function sendMessage(string $phone, string $message): array
    {
        $phone = self::formatPhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            Log::info('[WhatsApp] Message sent', [
                'phone' => $phone,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return [
                'success' => ($result['status'] ?? false) === true,
                'detail' => $result['detail'] ?? ($result['reason'] ?? 'Unknown'),
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Failed to send message', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test Fonnte API connection by validating the token.
     * Uses the /validate endpoint or sends to own number.
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/validate');

            $result = $response->json();

            return [
                'success' => ($result['status'] ?? false) === true,
                'detail' => $result['detail'] ?? ($result['reason'] ?? 'Unknown response'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build and send pending payment notification to customer.
     */
    public function sendPendingPaymentNotification(Transaction $transaction, array $paymentData, ?int $customerId): void
    {
        $phone = self::getCustomerPhone($customerId);
        if (!$phone) return;

        $settings = Setting::getStoreSettings();
        $storeName = $settings->store_name ?? 'Toko Kami';
        $customerName = self::getCustomerName($customerId);

        $payUrl = $paymentData['pay_url'] ?? $paymentData['qr_url'] ?? '-';
        $expiredAt = $paymentData['expired_at'] ?? '-';

        if ($expiredAt !== '-') {
            try {
                $expiredAt = \Carbon\Carbon::parse($expiredAt)->format('d M Y H:i') . ' WIB';
            } catch (\Exception $e) {
                // Keep original value
            }
        }

        $message = "Halo {$customerName}! 👋\n\n"
            . "Terima kasih telah berbelanja di *{$storeName}*.\n\n"
            . "Berikut detail pembayaran Anda:\n"
            . "🧾 No. Transaksi: {$transaction->transaction_code}\n"
            . "💰 Total Bayar: Rp " . number_format((float) $transaction->total_amount, 0, ',', '.') . "\n"
            . "🏦 Metode: " . strtoupper($transaction->payment_method) . "\n"
            . "🔗 Link Bayar: {$payUrl}\n"
            . "⏰ Batas Waktu: {$expiredAt}\n\n"
            . "Silakan selesaikan pembayaran sebelum batas waktu.\n"
            . "Terima kasih! 🙏";

        $this->sendMessage($phone, $message);
    }

    /**
     * Build and send success notification to customer.
     */
    public function sendSuccessNotification(Transaction $transaction): void
    {
        // Lookup customer_id from transaction
        $customerId = $this->resolveCustomerId($transaction);
        $phone = self::getCustomerPhone($customerId);
        if (!$phone) return;

        $settings = Setting::getStoreSettings();
        $storeName = $settings->store_name ?? 'Toko Kami';
        $customerName = self::getCustomerName($customerId);

        // Digital receipt link
        $receiptUrl = url("/receipt/{$transaction->transaction_code}");

        $message = "Halo {$customerName}! ✅\n\n"
            . "Pembayaran Anda telah dikonfirmasi.\n\n"
            . "🧾 No. Transaksi: {$transaction->transaction_code}\n"
            . "💰 Total: Rp " . number_format((float) $transaction->total_amount, 0, ',', '.') . "\n"
            . "🏦 Via: " . strtoupper($transaction->payment_method) . "\n"
            . "🔗 Lihat Struk: {$receiptUrl}\n\n"
            . "Terima kasih telah berbelanja di *{$storeName}*! 🙏";

        $this->sendMessage($phone, $message);
    }

    /**
     * Resolve customer ID from transaction.
     * Tries customer_name lookup if no direct customer_id field.
     */
    protected function resolveCustomerId(Transaction $transaction): ?int
    {
        // If transaction has customer_id field directly
        if (!empty($transaction->customer_id)) {
            return $transaction->customer_id;
        }

        // Fallback: search by customer_name
        if ($transaction->customer_name && $transaction->customer_name !== 'Umum') {
            $customer = Customer::where('name', $transaction->customer_name)->first();
            return $customer?->id;
        }

        return null;
    }
}
