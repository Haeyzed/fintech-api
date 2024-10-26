<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialRpEntity;

class WebAuthnServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Server::class, function ($app) {
            $rpEntity = new PublicKeyCredentialRpEntity(
                config('services.webauthn.relying_party.name'),
                config('services.webauthn.relying_party.id')
            );

            return new Server($rpEntity);
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
