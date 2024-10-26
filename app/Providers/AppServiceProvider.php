<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\{VerifyEmail, ResetPassword};
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, URL, Config};
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

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
        $this->configurePassport();
        $this->customizeResetPasswordUrl();
        $this->customizeVerificationUrl();
        $this->configureScramble();
        $this->configureDropboxStorage();
    }

    /**
     * Configure Passport token expiration.
     */
    private function configurePassport(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }

    /**
     * Customize the reset password URL.
     */
    private function customizeResetPasswordUrl(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return $this->buildCustomUrl('reset-password', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }

    /**
     * Customize the email verification URL.
     */
    private function customizeVerificationUrl(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return $this->buildCustomUrl('verify-email', [
                'url' => urlencode($verifyUrl),
            ]);
        });
    }

    /**
     * Build a custom URL for authentication-related actions.
     *
     * @param string $path
     * @param array $params
     * @return string
     */
    private function buildCustomUrl(string $path, array $params): string
    {
        $request = app(Request::class);
        $language = $request->header('Accept-Language', Config::get('app.locale'));
        $baseUrl = $request->header('Origin', Config::get('app.frontend_url'));

        $url = "{$baseUrl}/{$language}/{$path}";
        $query = http_build_query($params);

        return "{$url}?{$query}";
    }

    /**
     * Configure Scramble for API documentation.
     */
    private function configureScramble(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });
    }

    /**
     * Configure Dropbox storage driver.
     */
    private function configureDropboxStorage(): void
    {
        Storage::extend('dropbox', function (Application $app, array $config) {
            $adapter = new DropboxAdapter(new DropboxClient(
                $config['authorization_token']
            ));

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
