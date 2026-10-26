<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

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
        // Prevent key length errors during database migrations on utf8mb4 MySQL/MariaDB
        Schema::defaultStringLength(191);

        // Force HTTPS URL generation in production environments
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Enable Eloquent strict mode in non-production environments
        // Automatically alerts on N+1 lazy loading, unfillable attributes, and invalid properties
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}