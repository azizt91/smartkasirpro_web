<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\LedgerService;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        $debitAccount = (stripos($expense->name, 'gaji') !== false || stripos($expense->name, 'komisi') !== false) 
                        ? '502' : '503';

        LedgerService::record(
            $expense->date ?? now()->toDateString(),
            $debitAccount,
            '101', // Kas Tunai
            $expense->amount,
            'expense',
            $expense->id,
            "Pengeluaran: " . $expense->name
        );
    }
}
