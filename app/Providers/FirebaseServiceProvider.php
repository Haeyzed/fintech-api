<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Messaging::class, function ($app) {
            $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase-service-account.json')), true);

            return (new Factory)
                ->withServiceAccount($serviceAccount)
                ->createMessaging();
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
