<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Ledger;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Auth;

class TransactionObserver
{
    private function log(string $action, Transaction $transaction)
    {
        if (Auth::check()) {
            $oldValues = [];
            $newValues = [];

            if ($action === 'updated') {
                $oldValues = array_intersect_key($transaction->getOriginal(), $transaction->getDirty());
                $newValues = $transaction->getDirty();
            } elseif ($action === 'created') {
                $newValues = $transaction->getAttributes();
            } elseif ($action === 'deleted') {
                $oldValues = $transaction->getAttributes();
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => Transaction::class,
                'model_id' => $transaction->id,
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $this->log('created', $transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $this->log('updated', $transaction);

        // Jurnal Penjualan
        if ($transaction->status === 'completed') {
            $hasLedger = Ledger::where('reference_type', 'transaction')->where('reference_id', $transaction->id)->exists();
            
            if (!$hasLedger && $transaction->total_amount > 0) {
                // Debit: Kas atau Piutang
                $debitAccount = ($transaction->payment_method === 'utang') ? '103' : 
                                (($transaction->payment_method === 'cash') ? '101' : '102');
                
                // Kredit: Penjualan
                LedgerService::record(
                    $transaction->created_at->toDateString(),
                    $debitAccount,
                    '401', // Penjualan
                    $transaction->total_amount,
                    'transaction',
                    $transaction->id,
                    "Penjualan " . strtoupper($transaction->payment_method) . " #" . $transaction->transaction_code
                );

                // Jurnal HPP (Jika ada produk fisik)
                $totalHpp = 0;
                foreach ($transaction->items()->with('product')->get() as $item) {
                    if ($item->product && $item->product->type !== 'jasa') {
                        $totalHpp += ($item->product->purchase_price * $item->quantity);
                    }
                }

                if ($totalHpp > 0) {
                    LedgerService::record(
                        $transaction->created_at->toDateString(),
                        '501', // HPP
                        '104', // Persediaan
                        $totalHpp,
                        'transaction_hpp',
                        $transaction->id,
                        "HPP Penjualan #" . $transaction->transaction_code
                    );
                }
            }
        }

        // Jurnal Pembatalan (Void)
        if ($transaction->status === 'void' && $transaction->getOriginal('status') === 'completed') {
            LedgerService::void('transaction', $transaction->id, 'Transaksi Dibatalkan');
            LedgerService::void('transaction_hpp', $transaction->id, 'Transaksi Dibatalkan');
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $this->log('deleted', $transaction);
    }
}
