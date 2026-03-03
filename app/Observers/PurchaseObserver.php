<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Services\LedgerService;

class PurchaseObserver
{
    /**
     * Handle the Purchase "created" event.
     */
    public function created(Purchase $purchase): void
    {
        LedgerService::record(
            $purchase->date ?? now()->toDateString(),
            '104', // Persediaan Barang
            '101', // Kas Tunai
            $purchase->total_amount,
            'purchase',
            $purchase->id,
            "Pembelian Barang #" . $purchase->transaction_code
        );
    }
}
