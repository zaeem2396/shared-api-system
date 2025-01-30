<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helper\Helper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('Helper', function () {
            return new Helper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Asia/Kolkata');
    }
}
