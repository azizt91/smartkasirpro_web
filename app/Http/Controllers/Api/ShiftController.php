<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftController extends Controller
{
    /**
     * Check if the user has an open shift.
     */
    public function check(Request $request)
    {
        $openShift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            return response()->json([
                'success' => true,
                'has_open_shift' => true,
                'shift' => $openShift,
                'message' => 'Anda memiliki shift aktif.'
            ]);
        }

        return response()->json([
            'success' => true,
            'has_open_shift' => false,
            'shift' => null,
            'message' => 'Belum ada shift yang aktif.'
        ]);
    }

    /**
     * Open a new shift for the user.
     */
    public function open(Request $request)
    {
        $request->validate([
            'starting_cash' => 'required|numeric|min:0',
        ]);

        // Check if there is already an open shift
        $openShift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki shift yang berstatus open.',
                'shift' => $openShift,
            ], 422);
        }

        $shift = CashierShift::create([
            'user_id' => auth()->id(),
            'start_time' => now(),
            'starting_cash' => $request->starting_cash,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dibuka.',
            'shift' => $shift,
        ]);
    }

    /**
     * Close the currently open shift.
     */
    public function close(Request $request)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $shift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada shift yang sedang terbuka untuk ditutup.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cashSales = \App\Models\Transaction::where('shift_id', $shift->id)
                ->where('payment_method', 'cash')
                ->where('status', 'completed')
                ->sum('amount_paid') - \App\Models\Transaction::where('shift_id', $shift->id)
                ->where('payment_method', 'cash')
                ->where('status', 'completed')
                ->sum('change_amount');

            $expectedCash = $shift->starting_cash + $cashSales;
            $difference = $request->actual_cash - $expectedCash;

            $shift->update([
                'end_time' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $request->actual_cash,
                'difference' => $difference,
                'status' => 'closed',
                'notes' => $request->notes
            ]);

            DB::commit();

            // Send Notification to Owners
            $usersToNotify = \App\Models\User::whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get();
                
            if ($usersToNotify->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\ShiftClosed($shift));
            }

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup.',
                'shift' => $shift,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
