<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CashierShift;
use Illuminate\Support\Facades\Auth;

class EnsureShiftIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $openShift = CashierShift::where('user_id', Auth::id())
                ->where('status', 'open')
                ->first();

            if (!$openShift) {
                if (!$request->is('pos/shift*') && !$request->routeIs('pos.shift.*') && !$request->is('logout')) {
                    return redirect()->route('pos.shift.create')->with('info', 'Silakan masukkan Modal Awal untuk membuka Kasir Anda.');
                }
            }
        }

        return $next($request);
    }
}
