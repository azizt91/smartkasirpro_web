<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\AuditLog;
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
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $this->log('deleted', $transaction);
    }
}
