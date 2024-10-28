<?php

use App\Enums\SocialProviderEnum;
use App\Http\Controllers\{AuthController,
    BankAccountController,
    BlockedIpController,
    DashboardController,
    FCMController,
    NotificationController,
    OAuthClientController,
    PassKeyController,
    PaymentMethodController,
    PayPalController,
    PaystackController,
    TransactionController,
    UploadController,
    UserController,
    StripeController
};
use App\Http\Middleware\{Require2FA, Ensure2FASetup};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // User routes
    Route::apiResource('users', UserController::class)->middleware(['auth:api', '2fa']);
    Route::controller(UserController::class)->prefix('users')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{sqid}/restore', 'restore');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/bulk-restore', 'bulkRestore');
        Route::delete('/{sqid}/force', 'forceDelete');
        Route::post('/import', 'import');
        Route::get('/export', 'export');
        Route::post('/{sqid}/block-ip/{ipAddress}', 'blockIp');
        Route::delete('/{sqid}/unblock-ip/{ipAddress}', 'unblockIp');
    });

    // Upload routes
    Route::apiResource('uploads', UploadController::class)->middleware(['auth:api', '2fa']);
    Route::controller(UploadController::class)->prefix('uploads')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{sqid}/restore', 'restore');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/bulk-restore', 'bulkRestore');
        Route::delete('/{sqid}/force', 'forceDelete');
        Route::post('/import', 'import');
        Route::get('/export', 'export');
    });

    // Bank Account routes
    Route::apiResource('bank-accounts', BankAccountController::class)->middleware(['auth:api', '2fa']);
    Route::controller(BankAccountController::class)->prefix('bank-accounts')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{sqid}/restore', 'restore');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/bulk-restore', 'bulkRestore');
        Route::delete('/{sqid}/force', 'forceDelete');
        Route::post('/import', 'import');
        Route::get('/export', 'export');
    });

    // Transaction routes
    Route::apiResource('transactions', TransactionController::class)->middleware(['auth:api', '2fa']);
    Route::controller(TransactionController::class)->prefix('transactions')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{sqid}/restore', 'restore');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/bulk-restore', 'bulkRestore');
        Route::delete('/{sqid}/force', 'forceDelete');
        Route::post('/import', 'import');
        Route::get('/export', 'export');
    });

    // Transaction routes
    Route::apiResource('payment-methods', PaymentMethodController::class)->middleware(['auth:api', '2fa']);
    Route::controller(PaymentMethodController::class)->prefix('payment-methods')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{sqid}/restore', 'restore');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/bulk-restore', 'bulkRestore');
        Route::delete('/{sqid}/force', 'forceDelete');
    });

    // Auth routes
    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::post('/login', 'login')->name('login');
        Route::post('/register', 'register')->name('register');
        Route::post('/forgot-password', 'forgotPassword')->name('password.reset');
        Route::post('/reset-password', 'resetPassword');
        Route::post('/issue-passport-token', 'issuePassportToken');
        Route::get('/email/verify/{id}/{hash}', 'verify')
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/resend', 'resendVerification')
            ->middleware(['auth:api', '2fa', 'throttle:6,1'])
            ->name('verification.send');
        Route::get('/{provider}', 'redirectToProvider')
            ->where('provider', implode('|', SocialProviderEnum::values()));
        Route::get('/{provider}/callback', 'handleProviderCallback')
            ->where('provider', implode('|', SocialProviderEnum::values()));
        Route::middleware(['auth:api'])->group(function () {
            Route::post('/unlock', 'unlock');
            Route::put('/change-password', 'changePassword');
            Route::get('/profile', 'profile');
            Route::put('/profile', 'updateProfile');
            Route::post('/logout', 'logout')->name('logout');
            Route::post('/refresh', 'refresh');
            Route::prefix('2fa')->group(function () {
                Route::post('/enable', 'enable2FA');
                Route::post('/disable', 'disable2FA');
                Route::post('/recovery-codes', 'generateNewRecoveryCodes');
            });

//            Route::middleware(Ensure2FASetup::class)->group(function () {
//                // Add routes here that require 2FA to be set up
//            });
//
//            Route::middleware(Require2FA::class)->group(function () {
//                // Add routes here that require 2FA verification
//            });
        });
        Route::post('/2fa/verify', 'verify2FA');
//        Route::prefix('passkeys')->group(function () {
//            Route::post('/create-options', [PassKeyController::class, 'createOptions'])->middleware('auth:api');
//            Route::post('/register', [PassKeyController::class, 'register'])->middleware('auth:api');
//            Route::post('/get-options', [PassKeyController::class, 'getOptions']);
//            Route::post('/authenticate', [PassKeyController::class, 'authenticate']);
//        });
    });

    // Paystack routes
    Route::controller(PaystackController::class)->prefix('paystack')->group(function () {
        Route::prefix('payment')->group(function () {
            Route::post('/initialize', 'initializeTransaction');
            Route::post('/verify', 'verifyTransaction');
            Route::post('/process-authorized', 'processAuthorizedPayment');
        });
        Route::prefix('withdrawal')->group(function () {
            Route::post('/initialize', 'initiateWithdrawal');
            Route::post('/verify', 'verifyTransfer');
        });
    });

    Route::controller(StripeController::class)->prefix('stripe')->group(function () {
        Route::post('/initialize-transaction', 'initializeTransaction');
        Route::post('/verify-transaction', 'verifyTransaction');
        Route::post('/initiate-withdrawal', 'initiateWithdrawal');
        Route::post('/link-bank-account', 'linkBankAccount');
    });

    Route::controller(PayPalController::class)->prefix('paypal')->group(function () {
        Route::post('/create-order', 'createOrder');
        Route::post('/capture-payment', 'capturePayment');
        Route::post('/process-withdrawal', 'processWithdrawal');
    });

    // OAuth Client routes
    Route::apiResource('oauth-clients', OAuthClientController::class)->middleware(['auth:api', '2fa']);
    Route::controller(OAuthClientController::class)->prefix('oauth-clients')->middleware(['auth:api', '2fa'])->group(function () {
        Route::post('/{id}/secret', 'showSecret');
        Route::get('/tokens', 'listTokens');
        Route::delete('/tokens/{tokenId}', 'revokeToken');
        Route::get('/all-tokens', 'listAllTokens');
        Route::delete('/tokens/{id}', 'deleteToken');
    });

    // Blocked IP routes
    Route::apiResource('blocked-ips', BlockedIpController::class)->middleware(['auth:api', '2fa']);

    // Dashboard routes
    Route::get('/dashboard/metrics', [DashboardController::class, 'getMetrics'])
        ->middleware(['auth:api', '2fa'])
        ->name('dashboard.metrics');

    Route::post('/fcm/send-to-device', [FCMController::class, 'sendToDevice']);
    Route::post('/fcm/send-to-devices', [FCMController::class, 'sendToDevices']);

    Route::controller(NotificationController::class)->prefix('notifications')->middleware(['auth:api', '2fa'])->group(function () {
        Route::get('/all', 'index');
        Route::patch('/{id}/read', 'markAsRead');
        Route::post('/mark-all-read', 'markAllAsRead');
        Route::delete('/{id}', 'destroy');
        Route::get('/unread-count', 'unreadCount');
    });
});
