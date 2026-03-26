<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\CompanyInformation;
use App\Models\Currency;
use App\Models\AppSetting;

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
        Vite::prefetch(concurrency: 3);

        // Share company information and app settings with all Inertia views
        Inertia::share([
            'companyInfo' => function () {
                return CompanyInformation::first();
            },
            'currency' => function () {
                $info = CompanyInformation::first();
                return $info && $info->currency ? $info->currency : null;
            },
            'currencySymbol' => function () {
                $info = CompanyInformation::first();
                if (!$info || empty($info->currency)) return null;
                $c = Currency::where('code', $info->currency)->first();
                return $c ? $c->symbol : null;
            },
            'appSettings' => function () {
                return AppSetting::first();
            },
        ]);
    }
}
