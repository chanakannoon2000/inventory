<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\CostCipher;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            try {
                $settings = Setting::current();
                $view->with('shopName', $settings->shop_name);
                $view->with('shopLogo', $settings->logoSrc());
            } catch (\Throwable $e) {
                $view->with('shopName', 'ร้านวัสดุก่อสร้าง');
                $view->with('shopLogo', null);
            }
        });

        View::share('money', function ($n) {
            return '฿'.number_format((float) $n, 0, '.', ',');
        });

        View::share('fmt', function ($n) {
            return number_format((float) $n, 0, '.', ',');
        });

        View::share('costCode', function ($cost) {
            return CostCipher::encode($cost);
        });
    }
}
