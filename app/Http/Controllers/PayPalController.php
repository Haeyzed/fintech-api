<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Rules\SqidExists;
use App\Services\PayPalService;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class PayPalController
 *
 * This controller handles PayPal-related operations such as creating orders,
 * capturing payments, and processing withdrawals.
 *
 * @tags PayPal Payment
 * @package App\Http\Controllers
 */
class PayPalController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * The PayPal service instance.
     *
     * @var PayPalService
     */
    protected PayPalService $paypalService;

    /**
     * PayPalController constructor.
     *
     * @param PayPalService $paypalService The PayPal service instance.
     */
    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Create a PayPal order for deposit.
     *
     * This method starts a new transaction and creates a pending Transaction record.
     *
     * @param Request $request The incoming request containing the transaction details.
     * @return TransactionResource|JsonResponse The JSON response containing the order details.
     */
    public function createOrder(Request $request): TransactionResource|JsonResponse
    {
        $request->validate([
            /**
             * The amount of the transaction in the smallest currency unit (e.g., cents for USD, kobo for NGN).
             * @var numeric $amount
             * @example 1000
             */
            'amount' => ['required', 'numeric', 'min:0.01'],

            /**
             * The ID of the payment method to be used for the transaction.
             * @var string $payment_method_id
             * @example uw2YK1rnl0
             */
            'payment_method_id' => ['required', new SqidExists('payment_methods')],

            /**
             * The currency of the transaction to be used for the transaction.
             * @var string $currency
             * @example NG
             */
            'currency' => ['required', 'string', 'size:3'],
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = Auth::user();
                $paymentMethod = PaymentMethod::findBySqid($request->payment_method_id)->where('is_active', true)->first();
                if (!$paymentMethod) {
                    return response()->notFound('Invalid payment method');
                }

                $orderResult = $this->paypalService->createOrder($request->amount, $request->currency);

                if (!$orderResult['success']) {
                    return response()->badRequest('Order creation failed: ' . $orderResult['message']);
                }

                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'type' => TransactionTypeEnum::DEPOSIT,
                    'amount' => $request->amount,
                    'status' => TransactionStatusEnum::PENDING,
                    'description' => 'Deposit via PayPal',
                    'reference' => $orderResult['data']['reference'],
                ]);
                $transaction->save();

                return response()->success([
                    'paypal' => $orderResult['data'],
                    'transaction_id' => $transaction->sqid,
                ], 'PayPal order created successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'creating PayPal order');
        }
    }

    /**
     * Capture a PayPal payment and update the corresponding Transaction record.
     *
     * @param Request $request The incoming request containing the order ID.
     * @return TransactionResource|JsonResponse The JSON response indicating the result of the capture process.
     */
    public function capturePayment(Request $request): TransactionResource|JsonResponse
    {
        $request->validate([
            /**
             * The unique reference code for the transaction.
             * @var string $reference
             * @example "TRX_123456789"
             */
            'reference' => ['required', 'string'],

            /**
             * The unique order id for the transaction.
             * @var string $order_id
             * @example "ord_123456789"
             */
            'order_id' => ['required', 'string'],
        ]);

        $transaction = Transaction::where('reference', $request->reference)->firstOrFail();
        if (!$transaction){
            return response()->notFound('Transaction not found');
        }

        if ($transaction->status !== TransactionStatusEnum::PENDING) {
            return response()->badRequest('Transaction is not in a pending state');
        }

        $captureResult = $this->paypalService->captureOrder($request->order_id);

        if (!$captureResult['success']) {
            $transaction->status = TransactionStatusEnum::FAILED;
            $transaction->save();
            return response()->badRequest('Payment capture failed: ' . $captureResult['message']);
        }

        try {
            return DB::transaction(function () use ($transaction, $captureResult) {
                $transaction->status = TransactionStatusEnum::COMPLETED;
                $transaction->save();

                $user = $transaction->user;
                $user->balance += $transaction->amount;
                $user->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                ], 'Payment captured and completed successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'capturing PayPal payment');
        }
    }

    /**
     * Process a withdrawal via PayPal payout.
     *
     * @param Request $request The incoming request containing the withdrawal details.
     * @return JsonResponse The JSON response indicating the result of the withdrawal process.
     */
    public function processWithdrawal(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'recipient_email' => ['required', 'email'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        try {
            $user = Auth::user();
            $amount = $request->amount;

            if ($user->balance < $amount) {
                return response()->badRequest('Insufficient balance');
            }

            $payoutResult = $this->paypalService->createPayout($amount, $request->recipient_email, $request->currency);

            if (!$payoutResult['success']) {
                return response()->badRequest('Withdrawal failed: ' . $payoutResult['message']);
            }

            return DB::transaction(function () use ($user, $amount, $payoutResult) {
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'type' => TransactionTypeEnum::WITHDRAWAL,
                    'amount' => $amount,
                    'status' => TransactionStatusEnum::COMPLETED,
                    'description' => 'Withdrawal via PayPal',
                    'reference' => $payoutResult['data']['reference'],
                ]);
                $transaction->save();

                $user->balance -= $amount;
                $user->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                ], 'Withdrawal processed successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'processing PayPal withdrawal');
        }
    }
}
