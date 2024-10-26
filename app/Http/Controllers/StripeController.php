<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Rules\SqidExists;
use App\Services\StripeService;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\ApiErrorException;

/**
 * Class StripeController
 *
 * This controller handles Stripe-related operations such as initializing payments,
 * processing payments, verifying transactions, and updating user balances.
 *
 * @tags Stripe Payment
 *
 * @package App\Http\Controllers
 */
class StripeController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * The Stripe service instance.
     *
     * @var StripeService
     */
    protected StripeService $stripeService;

    /**
     * StripeController constructor.
     *
     * @param StripeService $stripeService The Stripe service instance.
     */
    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Initialize a Stripe transaction.
     *
     * This method starts a new transaction and creates a pending Transaction record.
     *
     * @param Request $request The incoming request containing the transaction details.
     * @return TransactionResource|JsonResponse The JSON response containing the initialization details.
     */
    public function initializeTransaction(Request $request): TransactionResource|JsonResponse
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
            'currency' => ['required', 'string'],
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = Auth::user();
                $paymentMethod = PaymentMethod::findBySqid($request->payment_method_id)->where('is_active', true)->first();
                if (!$paymentMethod) {
                    return response()->notFound('Invalid payment method');
                }

                $initializationResult = $this->stripeService->initializeTransaction($request->amount, $request->currency);

                if (!$initializationResult['success']) {
                    return response()->badRequest('Transaction initialization failed: ' . $initializationResult['message']);
                }

                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'type' => TransactionTypeEnum::DEPOSIT,
                    'amount' => $request->amount,
                    'status' => TransactionStatusEnum::PENDING,
                    'description' => 'Deposit via Stripe',
                    'reference' => $initializationResult['data']['reference'],
                ]);
                $transaction->save();

                return response()->success([
                    'stripe' => $initializationResult['data'],
                    'transaction_id' => $transaction->sqid,
                ], 'Transaction initialized successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'initiating stripe transaction');
        }
    }

    /**
     * Verify a Stripe transaction and update the corresponding Transaction record.
     *
     * @param Request $request The incoming request containing the transaction reference.
     * @return TransactionResource|JsonResponse The JSON response indicating the result of the verification process.
     * @throws ApiErrorException
     */
    public function verifyTransaction(Request $request): TransactionResource|JsonResponse
    {
        $request->validate([
            /**
             * The unique reference code for the transaction.
             * @var string $reference
             * @example "TRX_123456789"
             */
            'reference' => ['required', 'string'],
            /**
             * The unique intent id code for the transaction.
             * @var string $payment_intent_id
             * @example "id_123456789"
             */
            'payment_intent_id' => ['required', 'string'],
        ]);

        $transaction = Transaction::where('reference', $request->reference)->firstOrFail();
        if (!$transaction){
            return response()->notFound('Transaction not found');
        }

        if ($transaction->status !== TransactionStatusEnum::PENDING) {
            return response()->badRequest('Transaction is not in a pending state');
        }

        $verificationResult = $this->stripeService->verifyTransaction($request->payment_intent_id);

        if (!$verificationResult['success']) {
            $transaction->status = TransactionStatusEnum::FAILED;
            $transaction->save();
            return response()->badRequest('Verification failed: ' . $verificationResult['message']);
        }

        try {
            return DB::transaction(function () use ($transaction, $verificationResult) {
                $transaction->status = TransactionStatusEnum::COMPLETED;
                $transaction->save();

                $user = $transaction->user;
                $user->balance += $transaction->amount;
                $user->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                ], 'Transaction verified and completed successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'verifying stripe transaction');
        }
    }

    /**
     * Initiate a withdrawal to a connected Stripe account.
     *
     * @param Request $request The incoming request containing the withdrawal details.
     * @return JsonResponse The JSON response indicating the result of the withdrawal initiation.
     */
    public function initiateWithdrawal(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'destination_account' => ['required', 'string'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        try {
            $user = Auth::user();
            $amount = $request->amount;

            if ($user->balance < $amount) {
                return response()->badRequest('Insufficient balance');
            }

            $withdrawalResult = $this->stripeService->initiateWithdrawal($amount, $request->destination_account, $request->currency);

            if (!$withdrawalResult['success']) {
                return response()->badRequest('Withdrawal initiation failed: ' . $withdrawalResult['message']);
            }

            return DB::transaction(function () use ($user, $amount, $withdrawalResult) {
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'type' => TransactionTypeEnum::WITHDRAWAL,
                    'amount' => $amount,
                    'status' => TransactionStatusEnum::COMPLETED,
                    'description' => 'Withdrawal via Stripe',
                    'reference' => $withdrawalResult['data']['reference'],
                ]);
                $transaction->save();

                $user->balance -= $amount;
                $user->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                ], 'Withdrawal initiated successfully');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'initiating withdrawal');
        }
    }

    /**
     * Link a bank account to a user (Create a connected account).
     *
     * @param Request $request The incoming request containing the account details.
     * @return JsonResponse The JSON response indicating the result of the account linking process.
     */
    public function linkBankAccount(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'account_number' => ['required', 'string'],
            'routing_number' => ['required', 'string'],
        ]);

        try {
            $user = Auth::user();
            $accountInfo = $request->all();
            $accountInfo['email'] = $user->email;

            $linkResult = $this->stripeService->linkBankAccount($accountInfo);

            if (!$linkResult['success']) {
                return response()->badRequest('Bank account linking failed: ' . $linkResult['message']);
            }

            $user->payment_methods()->create([
                'type' => 'bank_account',
                'details' => $linkResult['data'],
                'is_active' => true,
            ]);

            return response()->success($linkResult['data'], 'Bank account linked successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'linking bank account');
        }
    }
}
