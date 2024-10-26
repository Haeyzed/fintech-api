<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Class PaystackService
 *
 * This service handles interactions with the Paystack API for payment processing,
 * including deposits, withdrawals, and bank account management.
 *
 * @package App\Services
 */
class PaystackService
{
    protected string $baseUrl = 'https://api.paystack.co';
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret');
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
     * Initialize a Paystack transaction for deposit.
     *
     * @param float $amount The amount for the transaction in Naira.
     * @param string $email The email of the customer.
     * @param string|null $callbackUrl The callback url.
     * @return array The result of the initialization process.
     * @throws ConnectionException
     */
    public function initializeTransaction(float $amount, string $email, ?string $callbackUrl): array
    {
        $reference = $this->generateUniqueReference();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transaction/initialize', [
            'email' => $email,
            'amount' => $amount * 100,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
        ]);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Transaction initialization failed',
        ];
    }

    /**
     * Verify a Paystack transaction.
     *
     * @param string $reference The transaction reference to verify.
     * @return array The result of the verification process.
     * @throws ConnectionException
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . '/transaction/verify/' . $reference);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Verification failed',
        ];
    }

    /**
     * Charge a previously authorized card.
     *
     * @param float $amount The amount to charge in Naira.
     * @param string $email The email of the customer.
     * @param PaymentMethod $paymentMethod The payment method containing the authorization code.
     * @return array The result of the charge operation.
     * @throws ConnectionException
     */
    public function chargeAuthorization(float $amount, string $email, PaymentMethod $paymentMethod): array
    {
        $reference = $this->generateUniqueReference();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transaction/charge_authorization', [
            'authorization_code' => $paymentMethod->details['authorization_code'],
            'email' => $email,
            'amount' => $amount * 100,
            'reference' => $reference,
        ]);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Payment failed',
        ];
    }

    /**
     * Initiate a withdrawal to a bank account.
     *
     * @param float $amount The amount to withdraw in Naira.
     * @param string $recipientCode The recipient code of the bank account.
     * @return array The result of the withdrawal initiation.
     * @throws ConnectionException
     */
    public function initiateWithdrawal(float $amount, string $recipientCode): array
    {
        $reference = $this->generateUniqueReference();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transfer', [
            'source' => 'balance',
            'amount' => $amount * 100,
            'recipient' => $recipientCode,
            'reason' => 'Withdrawal',
            'reference' => $reference,
        ]);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Withdrawal initiation failed',
        ];
    }

    /**
     * Link a bank account to a user.
     *
     * @param string $accountNumber The bank account number.
     * @param string $bankCode The bank code.
     * @param string $accountName The account name.
     * @return array The result of the bank account linking process.
     * @throws ConnectionException
     */
    public function linkBankAccount(string $accountNumber, string $bankCode, string $accountName): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transferrecipient', [
            'type' => 'nuban',
            'name' => $accountName,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => 'NGN',
        ]);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Bank account linking failed',
        ];
    }

    /**
     * List all transfer recipients.
     *
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function listTransferRecipients(): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . '/transferrecipient');

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to list transfer recipients',
        ];
    }

    /**
     * Fetch a specific transfer recipient.
     *
     * @param string $recipientCode The code of the recipient to fetch.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function fetchTransferRecipient(string $recipientCode): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . '/transferrecipient/' . $recipientCode);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to fetch transfer recipient',
        ];
    }

    /**
     * Update a transfer recipient.
     *
     * @param string $recipientCode The code of the recipient to update.
     * @param array $data The data to update.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function updateTransferRecipient(string $recipientCode, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->put($this->baseUrl . '/transferrecipient/' . $recipientCode, $data);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to update transfer recipient',
        ];
    }

    /**
     * Delete a transfer recipient.
     *
     * @param string $recipientCode The code of the recipient to delete.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function deleteTransferRecipient(string $recipientCode): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->delete($this->baseUrl . '/transferrecipient/' . $recipientCode);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to delete transfer recipient',
        ];
    }

    /**
     * Create a new transfer recipient.
     *
     * @param array $data The data for the new recipient.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function createTransferRecipient(array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transferrecipient', $data);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to create transfer recipient',
        ];
    }

    /**
     * Initiate a transfer.
     *
     * @param array $data The data for the transfer.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function initiateTransfer(array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transfer', $data);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to initiate transfer',
        ];
    }

    /**
     * Finalize a transfer.
     *
     * @param string $transferCode The code of the transfer to finalize.
     * @param string $otp The OTP for finalizing the transfer.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function finalizeTransfer(string $transferCode, string $otp): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transfer/finalize_transfer', [
            'transfer_code' => $transferCode,
            'otp' => $otp,
        ]);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to finalize transfer',
        ];
    }

    /**
     * Verify a transfer.
     *
     * @param string $reference The reference of the transfer to verify.
     * @return array The result of the API call.
     * @throws ConnectionException
     */
    public function verifyTransfer(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . '/transfer/verify/' . $reference);

        if ($response->successful() && $response['status'] === true) {
            return [
                'success' => true,
                'data' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to verify transfer',
        ];
    }
}
