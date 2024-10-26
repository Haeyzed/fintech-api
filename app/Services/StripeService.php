<?php

namespace App\Services;

use App\Models\Transaction;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Str;

/**
 * Class StripeService
 *
 * This service handles interactions with the Stripe API for payment processing,
 * including deposits, withdrawals, and account management.
 *
 * @package App\Services
 */
class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Generate a unique reference with the FINTECH prefix.
     *
     * @return string The generated reference.
     */
    public function generateUniqueReference(): string
    {
        do {
            $reference = config('app.payment_prefix') .'_'. Str::random(10);
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Initialize a Stripe PaymentIntent for deposit.
     *
     * @param float $amount The amount for the transaction in dollars.
     * @param string $currency The currency code (e.g., 'usd').
     * @return array The result of the initialization process.
     * @throws ApiErrorException
     */
    public function initializeTransaction(float $amount, string $currency = 'usd'): array
    {
        try {
            $reference = $this->generateUniqueReference();

            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => $currency,
                'metadata' => ['reference' => $reference],
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'reference' => $reference,
                ],
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a Stripe PaymentIntent.
     *
     * @param string $paymentIntentId The PaymentIntent ID to verify.
     * @return array The result of the verification process.
     * @throws ApiErrorException
     */
    public function verifyTransaction(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'data' => $paymentIntent->toArray(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment has not succeeded yet.',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a withdrawal to a connected Stripe account.
     *
     * @param float $amount The amount to withdraw in dollars.
     * @param string $destinationAccount The connected account ID.
     * @param string $currency The currency code (e.g., 'usd').
     * @return array The result of the withdrawal initiation.
     * @throws ApiErrorException
     */
    public function initiateWithdrawal(float $amount, string $destinationAccount, string $currency = 'usd'): array
    {
        try {
            $reference = $this->generateUniqueReference();

            $transfer = Transfer::create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => $currency,
                'destination' => $destinationAccount,
                'metadata' => ['reference' => $reference],
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $transfer->id,
                    'reference' => $reference,
                ],
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Link a bank account to a user (Create a connected account).
     *
     * @param array $accountInfo The account information.
     * @return array The result of the account creation process.
     * @throws ApiErrorException
     */
    public function linkBankAccount(array $accountInfo): array
    {
        try {
            $account = Account::create([
                'type' => 'custom',
                'country' => $accountInfo['country'],
                'email' => $accountInfo['email'],
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'external_account' => [
                    'object' => 'bank_account',
                    'country' => $accountInfo['country'],
                    'currency' => $accountInfo['currency'],
                    'account_number' => $accountInfo['account_number'],
                    'routing_number' => $accountInfo['routing_number'],
                ],
                'tos_acceptance' => [
                    'date' => time(),
                    'ip' => request()->ip(),
                ],
            ]);

            return [
                'success' => true,
                'data' => $account->toArray(),
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
