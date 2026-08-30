<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment(['local', 'testing']) && class_exists(Telescope::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
