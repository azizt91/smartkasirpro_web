<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);
        \App\Models\CommissionSettlement::observe(\App\Observers\CommissionSettlementObserver::class);
        \App\Models\Expense::observe(\App\Observers\ExpenseObserver::class);
        \App\Models\Purchase::observe(\App\Observers\PurchaseObserver::class);
    }
}
