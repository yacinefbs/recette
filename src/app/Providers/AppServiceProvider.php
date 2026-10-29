<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Behind the root .htaccess rewrite, the request path is /src/public/...; generate clean URLs.
        if (! $this->app->environment('local') && ! $this->app->runningInConsole()) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
