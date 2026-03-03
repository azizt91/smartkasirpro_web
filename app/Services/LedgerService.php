<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Ledger;

class LedgerService
{
    /**
     * Memasukkan jurnal akuntansi (Double-Entry)
     */
    public static function record(string $date, string $debitAccountCode, string $creditAccountCode, float $amount, string $referenceType, int $referenceId, string $description)
    {
        if ($amount == 0) return;

        $debitAccount = Account::where('code', $debitAccountCode)->first();
        $creditAccount = Account::where('code', $creditAccountCode)->first();

        if (!$debitAccount || !$creditAccount) {
            \Log::warning("LedgerService: Akun debit ({$debitAccountCode}) atau kredit ({$creditAccountCode}) tidak ditemukan.");
            return;
        }

        // 1. Catat sisi Debit
        Ledger::create([
            'date' => $date,
            'account_id' => $debitAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);

        // 2. Catat sisi Kredit
        Ledger::create([
            'date' => $date,
            'account_id' => $creditAccount->id,
            'debit' => 0,
            'credit' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);
    }

    /**
     * Void / Revert jurnal berdasarkan referensi
     */
    public static function void(string $referenceType, int $referenceId, string $reason)
    {
        $ledgers = Ledger::where('reference_type', $referenceType)
                         ->where('reference_id', $referenceId)
                         ->get();

        if ($ledgers->isEmpty()) return;

        $date = now()->toDateString();
        
        foreach ($ledgers as $ledger) {
            // Membalik jurnal: jika sebelumnya debit, maka kredit, dan sebaliknya.
            Ledger::create([
                'date' => $date,
                'account_id' => $ledger->account_id,
                'debit' => $ledger->credit,  // Balik
                'credit' => $ledger->debit,  // Balik
                'reference_type' => $referenceType . '_void',
                'reference_id' => $referenceId,
                'description' => "VOID: " . $reason . " (Ref: {$ledger->description})",
            ]);
        }
    }
}
