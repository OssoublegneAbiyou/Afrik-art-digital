<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (app()->environment('local') && request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
