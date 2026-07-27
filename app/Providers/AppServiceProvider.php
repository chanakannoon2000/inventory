<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\CostCipher;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

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
            $n = (float) $n;
            // แสดงทศนิยมเฉพาะตอนมีเศษสตางค์ ป้องกันราคาที่มีจุดทศนิยม (เช่น 3.50) ปัดเป็นจำนวนเต็มจนตัวเลขคลาดเคลื่อน
            $decimals = abs($n - round($n)) > 0.001 ? 2 : 0;

            return '฿'.number_format($n, $decimals, '.', ',');
        });

        View::share('fmt', function ($n) {
            $n = (float) $n;
            // แสดงทศนิยมเฉพาะตอนมีเศษ ป้องกันจำนวน/สต๊อกที่มีเศษ (เช่น 7.5) ถูกปัดจนตัวเลขคลาดเคลื่อน
            $decimals = abs($n - round($n)) > 0.001 ? 2 : 0;

            return number_format($n, $decimals, '.', ',');
        });

        View::share('costCode', function ($cost) {
            return CostCipher::encode($cost);
        });
    }
}
