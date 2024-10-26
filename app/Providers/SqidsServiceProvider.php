<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Sqids\Sqids;

class SqidsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Sqids::class, function ($app) {
            return new Sqids(config('sqid.alphabet'),config('sqid.min_length'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
