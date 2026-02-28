<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashierShift;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    /**
     * Show the form for opening: starting cash
     */
    public function create()
    {
        $openShift = CashierShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            return redirect()->route('pos.index')->with('info', 'Anda sudah memiliki shift yang aktif.');
        }

        return view('pos.shift.create');
    }

    /**
     * Open a new shift
     */
    public function store(Request $request)
    {
        $request->validate([
            'starting_cash' => 'required|numeric|min:0',
        ]);

        $openShift = CashierShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            return redirect()->route('pos.index');
        }

        CashierShift::create([
            'user_id' => Auth::id(),
            'start_time' => now(),
            'starting_cash' => $request->starting_cash,
            'status' => 'open',
        ]);

        return redirect()->route('pos.index')->with('success', 'Shift kasir berhasil dibuka. Selamat bertugas!');
    }

    /**
     * Show the X-Report and form to close shift
     */
    public function edit()
    {
        $shift = CashierShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->firstOrFail();

        // Calculate everything needed for X-Report
        $cashTransactions = Transaction::where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->where('payment_method', 'tunai')
            ->get();
            
        $cashSales = $cashTransactions->sum(function ($transaction) {
            return collect([$transaction->amount_paid - $transaction->change_amount, $transaction->total_amount])->min();
            // Fallback for actual received cash vs total amount
        });

        // Or simply sum total_amount for each payment method, it matches the exact price of cart items.
        // If a cashier gave wrong change, the 'amount_paid' could be tracked but 'total_amount' is what should be in the register.
        $cashSales = Transaction::where('shift_id', $shift->id)->where('status', 'completed')->where('payment_method', 'tunai')->sum('total_amount');

        $expectedCash = $shift->starting_cash + $cashSales;
        
        $debitSales = Transaction::where('shift_id', $shift->id)->where('status', 'completed')->where('payment_method', 'debit')->sum('total_amount');
        $qrisSales = Transaction::where('shift_id', $shift->id)->where('status', 'completed')->where('payment_method', 'qris')->sum('total_amount');
        $transferSales = Transaction::where('shift_id', $shift->id)->where('status', 'completed')->where('payment_method', 'transfer')->sum('total_amount');
        
        $totalSales = $cashSales + $debitSales + $qrisSales + $transferSales;
        $totalTransactions = Transaction::where('shift_id', $shift->id)->where('status', 'completed')->count();

        return view('pos.shift.close', compact(
            'shift', 'expectedCash', 'cashSales', 'debitSales', 'qrisSales', 'transferSales', 'totalSales', 'totalTransactions'
        ));
    }

    /**
     * Close the shift
     */
    public function update(Request $request)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'expected_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $shift = CashierShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->firstOrFail();

        $shift->update([
            'end_time' => now(),
            'expected_cash' => $request->expected_cash,
            'actual_cash' => $request->actual_cash,
            'difference' => $request->actual_cash - $request->expected_cash,
            'notes' => $request->notes,
            'status' => 'closed',
        ]);

        // Send Notification to Owners
        $usersToNotify = \App\Models\User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();
            
        if ($usersToNotify->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\ShiftClosed($shift));
        }

        return redirect()->route('dashboard')->with('success', 'Shift kasir berhasil ditutup dengan aman!');
    }

    /**
     * Admin/Owner view for mapping all shifts
     */
    public function index()
    {
        $shifts = CashierShift::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('reports.shifts', compact('shifts'));
    }
}
