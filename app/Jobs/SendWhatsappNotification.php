<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10; // seconds between retries

    protected string $type; // 'pending' or 'success'
    protected int $transactionId;
    protected ?int $customerId;
    protected array $paymentData;

    /**
     * Create a new job instance.
     *
     * @param string $type 'pending' or 'success'
     * @param int $transactionId
     * @param int|null $customerId
     * @param array $paymentData Payment gateway data (for pending type)
     */
    public function __construct(string $type, int $transactionId, ?int $customerId = null, array $paymentData = [])
    {
        $this->type = $type;
        $this->transactionId = $transactionId;
        $this->customerId = $customerId;
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Double-check WA is enabled
        if (!WhatsappService::isEnabled()) {
            Log::info('[WhatsApp Job] Skipped — WA notification disabled');
            return;
        }

        $transaction = Transaction::find($this->transactionId);
        if (!$transaction) {
            Log::warning('[WhatsApp Job] Transaction not found', ['id' => $this->transactionId]);
            return;
        }

        // Skip if no valid customer phone
        $phone = WhatsappService::getCustomerPhone($this->customerId);
        if (!$phone) {
            Log::info('[WhatsApp Job] Skipped — no customer phone', [
                'transaction' => $transaction->transaction_code,
                'customer_id' => $this->customerId,
            ]);
            return;
        }

        $service = new WhatsappService();

        try {
            if ($this->type === 'pending') {
                $service->sendPendingPaymentNotification($transaction, $this->paymentData, $this->customerId);
            } elseif ($this->type === 'success') {
                $service->sendSuccessNotification($transaction);
            }
        } catch (\Exception $e) {
            Log::error('[WhatsApp Job] Failed', [
                'type' => $this->type,
                'transaction' => $transaction->transaction_code,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw so the job can retry
        }
    }
}
