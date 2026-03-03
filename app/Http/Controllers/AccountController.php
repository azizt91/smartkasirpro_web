<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class AccountController extends BaseController
{
    public function index()
    {
        $accounts = Account::orderBy('code')->get()->map(function($account) {
            $debit = Ledger::where('account_id', $account->id)->sum('debit');
            $credit = Ledger::where('account_id', $account->id)->sum('credit');
            
            $account->balance = $account->default_balance === 'debit' 
                ? ($debit - $credit) 
                : ($credit - $debit);
            
            $account->has_initial = Ledger::where('account_id', $account->id)
                                          ->where('reference_type', 'initial_balance')
                                          ->exists();
            return $account;
        });

        // Group by type for view logic
        $groupedAccounts = $accounts->groupBy('type');

        return view('accounts.index', compact('groupedAccounts', 'accounts'));
    }

    public function setInitialBalance(Request $request, Account $account)
    {
        $request->validate([
            'amount' => 'required',
        ]);

        if (Ledger::where('account_id', $account->id)->where('reference_type', 'initial_balance')->exists()) {
            return back()->with('error', 'Saldo awal sudah pernah diatur.');
        }

        $amount = (float) str_replace(['Rp', '.', ',', ' '], '', $request->amount);

        if ($amount > 0) {
            $debit = $account->default_balance === 'debit' ? $amount : 0;
            $credit = $account->default_balance === 'credit' ? $amount : 0;

            Ledger::create([
                'date' => now()->toDateString(),
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'reference_type' => 'initial_balance',
                'reference_id' => null,
                'description' => 'Saldo Awal'
            ]);
            
            // Offset to Equity (Modal Pemilik) to maintain Double-Entry balance
            $modalAccount = Account::where('code', '301')->first();
            if ($modalAccount && $modalAccount->id != $account->id) {
                Ledger::create([
                    'date' => now()->toDateString(),
                    'account_id' => $modalAccount->id,
                    'debit' => $credit, // Opposite
                    'credit' => $debit, // Opposite
                    'reference_type' => 'initial_balance',
                    'reference_id' => null,
                    'description' => 'Penyesuaian Saldo Awal (' . $account->name . ')'
                ]);
            }
        }

        return back()->with('success', 'Saldo awal berhasil disimpan!');
    }
}
