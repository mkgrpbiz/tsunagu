<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.agency', function ($view) {
            $agency = Auth::guard('agency')->user();

            $showBankNotice = $agency
                && $agency->totalPendingPayout() > 0
                && ! $agency->hasBankInfoRegistered();

            $view->with('showBankNotice', $showBankNotice);
        });
    }
}
