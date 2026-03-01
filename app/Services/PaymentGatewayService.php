<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected Setting $settings;

    public function __construct()
    {
        $this->settings = Setting::getStoreSettings();
    }

    /**
     * Apakah payment gateway aktif?
     */
    public function isActive(): bool
    {
        return $this->settings->pg_active && $this->settings->pg_active !== 'none';
    }

    /**
     * Apakah metode pembayaran ini harus diproses melalui payment gateway?
     */
    public function isDigitalPayment(string $method): bool
    {
        return in_array($method, ['qris', 'transfer', 'ewallet']);
    }

    /**
     * Hitung biaya admin berdasarkan provider dan metode pembayaran.
     * Return fee amount yang harus ditambahkan ke total jika pg_fee_bearer = customer.
     */
    public function calculateFee(float $amount, string $method): float
    {
        if ($this->settings->pg_fee_bearer !== 'customer') {
            return 0;
        }

        $provider = $this->settings->pg_active;

        // Estimasi fee berdasarkan provider & metode
        return match ($provider) {
            'tripay' => $this->tripayFee($amount, $method),
            'duitku' => $this->duitkuFee($amount, $method),
            'midtrans' => $this->midtransFee($amount, $method),
            default => 0,
        };
    }

    /**
     * Buat transaksi pembayaran ke provider yang aktif.
     *
     * @param Transaction $transaction
     * @param string $method  Generic method: qris, transfer, ewallet
     * @param string|null $channel  Specific channel code (e.g. BRIVA, OVO, DANA)
     * @return array{pay_url: string|null, qr_url: string|null, reference: string, expired_at: string}
     * @throws \Exception
     */
    public function createTransaction(Transaction $transaction, string $method, ?string $channel = null): array
    {
        $provider = $this->settings->pg_active;

        Log::info("[PaymentGateway] Creating {$provider} transaction", [
            'transaction_code' => $transaction->transaction_code,
            'method' => $method,
            'channel' => $channel,
            'amount' => $transaction->total_amount,
        ]);

        return match ($provider) {
            'tripay' => $this->createTripayTransaction($transaction, $method, $channel),
            'duitku' => $this->createDuitkuTransaction($transaction, $method, $channel),
            'midtrans' => $this->createMidtransTransaction($transaction, $method, $channel),
            default => throw new \Exception('Payment gateway tidak dikonfigurasi.'),
        };
    }

    /**
     * Daftar channel pembayaran yang tersedia per metode berdasarkan provider aktif.
     */
    public function getAvailableChannels(string $method): array
    {
        $provider = $this->settings->pg_active;

        return match ($provider) {
            'tripay' => $this->tripayChannels($method),
            'duitku' => $this->duitkuChannels($method),
            'midtrans' => $this->midtransChannels($method),
            default => [],
        };
    }

    // =========================================================================
    // TRIPAY
    // =========================================================================

    protected function tripayBaseUrl(): string
    {
        return $this->settings->pg_mode === 'production'
            ? 'https://tripay.co.id/api'
            : 'https://tripay.co.id/api-sandbox';
    }

    protected function tripayFee(float $amount, string $method): float
    {
        return match ($method) {
            'qris' => ceil($amount * 0.007),       // ~0.7%
            'transfer' => 4000,                      // Flat ~Rp 4.000
            'ewallet' => ceil($amount * 0.015),     // ~1.5%
            default => 0,
        };
    }

    protected function tripayChannelCode(string $method, ?string $channel = null): string
    {
        if ($channel) return strtoupper($channel);

        return match ($method) {
            'qris' => 'QRIS',
            'transfer' => 'BRIVA',
            'ewallet' => 'OVO',
            default => 'QRIS',
        };
    }

    protected function tripayChannels(string $method): array
    {
        return match ($method) {
            'ewallet' => [
                ['code' => 'OVO', 'name' => 'OVO'],
                ['code' => 'DANA', 'name' => 'DANA'],
                ['code' => 'SHOPEEPAY', 'name' => 'ShopeePay'],
                ['code' => 'LINKAJA', 'name' => 'LinkAja'],
            ],
            'transfer' => [
                ['code' => 'BRIVA', 'name' => 'BRI Virtual Account'],
                ['code' => 'BNIVA', 'name' => 'BNI Virtual Account'],
                ['code' => 'MANDIRIVA', 'name' => 'Mandiri Virtual Account'],
                ['code' => 'BCAVA', 'name' => 'BCA Virtual Account'],
                ['code' => 'PERMATAVA', 'name' => 'Permata Virtual Account'],
                ['code' => 'CIMBVA', 'name' => 'CIMB Niaga VA'],
                ['code' => 'BSIVA', 'name' => 'BSI Virtual Account'],
            ],
            'qris' => [
                ['code' => 'QRIS', 'name' => 'QRIS (Semua Aplikasi)'],
                ['code' => 'QRISC', 'name' => 'QRIS (Customizable)'],
            ],
            default => [],
        };
    }

    protected function createTripayTransaction(Transaction $transaction, string $method, ?string $channel = null): array
    {
        $apiKey = $this->settings->tripay_api_key;
        $privateKey = $this->settings->tripay_private_key;
        $merchantCode = $this->settings->tripay_merchant_code;

        if (!$apiKey || !$privateKey || !$merchantCode) {
            throw new \Exception('Kredensial Tripay belum dikonfigurasi di Pengaturan.');
        }

        $channelCode = $this->tripayChannelCode($method, $channel);
        $merchantRef = $transaction->transaction_code;
        $amount = (int) $transaction->total_amount;

        // Tripay Signature: hash_hmac('sha256', merchantCode + merchantRef + amount, privateKey)
        $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);

        $payload = [
            'method' => $channelCode,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $transaction->customer_name ?? 'Pelanggan',
            'customer_email' => 'pos@store.local',
            'customer_phone' => '08000000000',
            'order_items' => [
                [
                    'name' => "Transaksi {$merchantRef}",
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'callback_url' => url('/payment/callback/tripay'),
            'return_url' => url('/pos'),
            'expired_time' => now()->addHours(1)->timestamp,
            'signature' => $signature,
        ];

        $response = Http::withToken($apiKey)
            ->post("{$this->tripayBaseUrl()}/transaction/create", $payload);

        if (!$response->successful()) {
            Log::error('[Tripay] API Error', ['response' => $response->body()]);
            throw new \Exception('Gagal membuat transaksi Tripay: ' . ($response->json('message') ?? $response->body()));
        }

        $data = $response->json('data');

        return [
            'pay_url' => $data['checkout_url'] ?? null,
            'qr_url' => $data['qr_url'] ?? null,
            'reference' => $data['reference'] ?? $merchantRef,
            'expired_at' => isset($data['expired_time'])
                ? date('Y-m-d H:i:s', $data['expired_time'])
                : now()->addHours(1)->toDateTimeString(),
        ];
    }

    // =========================================================================
    // DUITKU
    // =========================================================================

    protected function duitkuBaseUrl(): string
    {
        return $this->settings->pg_mode === 'production'
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';
    }

    protected function duitkuFee(float $amount, string $method): float
    {
        return match ($method) {
            'qris' => ceil($amount * 0.007),
            'transfer' => 4000,
            'ewallet' => ceil($amount * 0.02),
            default => 0,
        };
    }

    protected function duitkuPaymentMethod(string $method, ?string $channel = null): string
    {
        if ($channel) return $channel;

        return match ($method) {
            'qris' => 'SP',       // ShopeePay QRIS
            'transfer' => 'VC',   // Permata VA (default)
            'ewallet' => 'OV',    // OVO
            default => 'SP',
        };
    }

    protected function duitkuChannels(string $method): array
    {
        return match ($method) {
            'ewallet' => [
                ['code' => 'OV', 'name' => 'OVO'],
                ['code' => 'DA', 'name' => 'DANA'],
                ['code' => 'SA', 'name' => 'ShopeePay'],
                ['code' => 'LA', 'name' => 'LinkAja'],
            ],
            'transfer' => [
                ['code' => 'VC', 'name' => 'Permata Virtual Account'],
                ['code' => 'BC', 'name' => 'BCA Virtual Account'],
                ['code' => 'M2', 'name' => 'Mandiri Virtual Account'],
                ['code' => 'VA', 'name' => 'Maybank Virtual Account'],
                ['code' => 'BT', 'name' => 'Permata Bank Transfer'],
                ['code' => 'I1', 'name' => 'BNI Virtual Account'],
                ['code' => 'B1', 'name' => 'CIMB Niaga VA'],
                ['code' => 'A1', 'name' => 'ATM Bersama'],
            ],
            'qris' => [
                ['code' => 'SP', 'name' => 'QRIS / ShopeePay'],
                ['code' => 'LQ', 'name' => 'QRIS / DANA'],
            ],
            default => [],
        };
    }

    protected function createDuitkuTransaction(Transaction $transaction, string $method, ?string $channel = null): array
    {
        $merchantCode = $this->settings->duitku_merchant_code;
        $apiKey = $this->settings->duitku_api_key;

        if (!$merchantCode || !$apiKey) {
            throw new \Exception('Kredensial Duitku belum dikonfigurasi di Pengaturan.');
        }

        $merchantOrderId = $transaction->transaction_code;
        $paymentAmount = (int) $transaction->total_amount;
        $paymentMethod = $this->duitkuPaymentMethod($method, $channel);

        // Duitku Signature: md5(merchantCode + merchantOrderId + paymentAmount + apiKey)
        $signature = md5($merchantCode . $merchantOrderId . $paymentAmount . $apiKey);

        $payload = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => "Transaksi {$merchantOrderId}",
            'customerVaName' => $transaction->customer_name ?? 'Pelanggan',
            'email' => 'pos@store.local',
            'phoneNumber' => '08000000000',
            'callbackUrl' => url('/payment/callback/duitku'),
            'returnUrl' => url('/pos'),
            'expiryPeriod' => 60, // menit
            'signature' => $signature,
        ];

        $response = Http::post("{$this->duitkuBaseUrl()}/v2/inquiry", $payload);

        if (!$response->successful()) {
            Log::error('[Duitku] API Error', ['response' => $response->body()]);
            throw new \Exception('Gagal membuat transaksi Duitku: ' . ($response->json('Message') ?? $response->body()));
        }

        $data = $response->json();

        return [
            'pay_url' => $data['paymentUrl'] ?? null,
            'qr_url' => $data['qrString'] ?? null,
            'reference' => $data['reference'] ?? $merchantOrderId,
            'expired_at' => now()->addMinutes(60)->toDateTimeString(),
        ];
    }

    // =========================================================================
    // MIDTRANS
    // =========================================================================

    protected function midtransBaseUrl(): string
    {
        return $this->settings->pg_mode === 'production'
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    protected function midtransFee(float $amount, string $method): float
    {
        return match ($method) {
            'qris' => ceil($amount * 0.007),
            'transfer' => 4000,
            'ewallet' => ceil($amount * 0.015),
            default => 0,
        };
    }

    protected function midtransEnabledPayments(string $method, ?string $channel = null): array
    {
        if ($channel) return [$channel];

        return match ($method) {
            'qris' => ['gopay', 'shopeepay'],
            'transfer' => ['bank_transfer', 'echannel', 'permata_va'],
            'ewallet' => ['gopay', 'shopeepay', 'dana'],
            default => ['gopay'],
        };
    }

    protected function midtransChannels(string $method): array
    {
        return match ($method) {
            'ewallet' => [
                ['code' => 'gopay', 'name' => 'GoPay'],
                ['code' => 'shopeepay', 'name' => 'ShopeePay'],
            ],
            'transfer' => [
                ['code' => 'bank_transfer', 'name' => 'Bank Transfer (BCA/BNI/BRI)'],
                ['code' => 'echannel', 'name' => 'Mandiri Bill Payment'],
                ['code' => 'permata_va', 'name' => 'Permata Virtual Account'],
            ],
            'qris' => [
                ['code' => 'gopay', 'name' => 'QRIS (GoPay)'],
                ['code' => 'shopeepay', 'name' => 'QRIS (ShopeePay)'],
            ],
            default => [],
        };
    }

    protected function createMidtransTransaction(Transaction $transaction, string $method, ?string $channel = null): array
    {
        $serverKey = $this->settings->midtrans_server_key;

        if (!$serverKey) {
            throw new \Exception('Server Key Midtrans belum dikonfigurasi di Pengaturan.');
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_code,
                'gross_amount' => (int) $transaction->total_amount,
            ],
            'customer_details' => [
                'first_name' => $transaction->customer_name ?? 'Pelanggan',
                'email' => 'pos@store.local',
                'phone' => '08000000000',
            ],
            'enabled_payments' => $this->midtransEnabledPayments($method, $channel),
            'callbacks' => [
                'finish' => url('/pos'),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->midtransBaseUrl()}/transactions", $payload);

        if (!$response->successful()) {
            Log::error('[Midtrans] API Error', ['response' => $response->body()]);
            throw new \Exception('Gagal membuat transaksi Midtrans: ' . ($response->json('error_messages.0') ?? $response->body()));
        }

        $data = $response->json();

        return [
            'pay_url' => $data['redirect_url'] ?? null,
            'qr_url' => null,
            'reference' => $data['token'] ?? $transaction->transaction_code,
            'expired_at' => now()->addHours(1)->toDateTimeString(),
        ];
    }
}
