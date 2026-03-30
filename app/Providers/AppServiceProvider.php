<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(UrlGenerator $url): void
    {
        // إجبار HTTPS في production
        if (env('APP_ENV') === 'production') {
            $url->forceScheme('https');
        }
    }
}