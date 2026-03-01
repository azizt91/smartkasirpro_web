<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Display a public digital receipt.
     * Accessible without authentication via a shared link.
     */
    public function show(string $code)
    {
        $transaction = Transaction::with(['items.product', 'user'])
            ->where('transaction_code', $code)
            ->firstOrFail();

        $settings = \App\Models\Setting::getStoreSettings();

        return view('receipt.public', compact('transaction', 'settings'));
    }
}
