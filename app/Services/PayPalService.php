<?php

namespace App\Services;

use App\Models\Transaction;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Str;

/**
 * Class PayPalService
 *
 * This service handles interactions with the PayPal API for payment processing,
 * including deposits and withdrawals.
 *
 * @package App\Services
 */
class PayPalService
{
    protected PayPalClient $paypalClient;

    public function __construct()
    {
        $this->paypalClient = new PayPalClient;
        $this->paypalClient->setApiCredentials(config('paypal'));
        $this->paypalClient->getAccessToken();
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
     * Create a PayPal order for deposit.
     *
     * @param float $amount The amount for the transaction in dollars.
     * @param string $currency The currency code (e.g., 'USD').
     * @return array The result of the order creation process.
     */
    public function createOrder(float $amount, string $currency = 'USD'): array
    {
        $reference = $this->generateUniqueReference();

        try {
            $order = $this->paypalClient->createOrder([
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $reference,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
            ]);

            if (isset($order['id'])) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $order['id'],
                        'reference' => $reference,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture a PayPal order.
     *
     * @param string $orderId The PayPal order ID to capture.
     * @return array The result of the capture process.
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $result = $this->paypalClient->capturePaymentOrder($orderId);

            if (isset($result['status']) && $result['status'] === 'COMPLETED') {
                return [
                    'success' => true,
                    'data' => $result,
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment capture failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a PayPal payout (withdrawal).
     *
     * @param float $amount The amount to withdraw in dollars.
     * @param string $recipientEmail The PayPal email of the recipient.
     * @param string $currency The currency code (e.g., 'USD').
     * @return array The result of the payout creation.
     */
    public function createPayout(float $amount, string $recipientEmail, string $currency = 'USD'): array
    {
        $reference = $this->generateUniqueReference();

        try {
            $payout = $this->paypalClient->createBatchPayout([
                'sender_batch_header' => [
                    'sender_batch_id' => $reference,
                    'email_subject' => 'You have a payout!',
                ],
                'items' => [
                    [
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($amount, 2, '.', ''),
                            'currency' => $currency,
                        ],
                        'note' => 'Thanks for your patronage!',
                        'sender_item_id' => $reference,
                        'receiver' => $recipientEmail,
                    ],
                ],
            ]);

            if (isset($payout['batch_header']['payout_batch_id'])) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $payout['batch_header']['payout_batch_id'],
                        'reference' => $reference,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create PayPal payout',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
