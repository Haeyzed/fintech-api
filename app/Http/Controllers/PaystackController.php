<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Resources\TransactionResource;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Rules\SqidExists;
use App\Services\PaystackService;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class PaystackController
 *
 * This controller handles Paystack-related operations such as initializing payments,
 * processing payments, verifying transactions, and updating user balances.
 *
 * @tags Paystack Payment
 *
 * @package App\Http\Controllers
 */
class PaystackController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * The Paystack service instance.
     *
     * @var PaystackService
     */
    protected PaystackService $paystackService;

    /**
     * PaystackController constructor.
     *
     * @param PaystackService $paystackService The Paystack service instance.
     */
    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Initialize a Paystack transaction.
     *
     * This method starts a new transaction and creates a pending Transaction record.
     *
     * @param Request $request The incoming request containing the transaction details.
     * @return TransactionResource|JsonResponse The JSON response containing the initialization details.
     */
    public function initializeTransaction(Request $request): TransactionResource|JsonResponse
    {
        // Validate request input for amount and payment method
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
             * The ID of the payment method to be used for the transaction.
             * @var string $bank_account_id
             * @example EfhxLZ9ck8
             */
            'bank_account_id' => ['required', new SqidExists('bank_accounts')],
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = Auth::user();
                // Fetch payment method and ensure it is active
                $paymentMethod = PaymentMethod::findBySqid($request->payment_method_id)->where('is_active', true)->first();
                if (!$paymentMethod) {
                    return response()->notFound('Invalid payment method');
                }
                $bankAccount = BankAccount::findBySqid($request->bank_account_id)->where('user_id', $user->id)->first();
                if (!$bankAccount) {
                    return response()->notFound('Invalid bank account');
                }

                // Initialize the transaction through Paystack service
                $initializationResult = $this->paystackService->initializeTransaction(
                    $request->amount,
                    $user->email,
                    config('app.frontend_url') . '/en/payment/paystack',
                    $bankAccount->bank->currency->code
                );

                // Handle a failed initialization attempt
                if (!$initializationResult['success']) {
                    return response()->badRequest('Transaction initialization failed: ' . $initializationResult['message']);
                }

                // Create a new transaction record in the database
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'bank_account_id' => $bankAccount->id,
                    'type' => TransactionTypeEnum::DEPOSIT,
                    'amount' => $request->amount,
                    'status' => TransactionStatusEnum::PENDING,
                    'description' => 'Deposit via Paystack',
//                    'details' => [
//                        "authorization_url" => $initializationResult['data']['authorization_url'],
//                        "access_code" => $initializationResult['data']['access_code'],
//                        "reference" => $initializationResult['data']['reference']
//                    ],
                    'reference' => $initializationResult['data']['reference'],
                ]);
                $transaction->save();

                return response()->success([
                    'paystack' => $initializationResult['data'],
                    'transaction_id' => $transaction->sqid,
                ], 'Transaction initiated. Please complete the payment in the new tab.');
            });
        } catch (Exception $e) {
            // Exception handling in case of an error during initialization
            return $this->handleException($e, 'initiating paystack transaction');
        }
    }

    /**
     * Verify a Paystack transaction and update the corresponding Transaction record.
     *
     * @param Request $request The incoming request containing the transaction reference.
     * @return TransactionResource|JsonResponse The JSON response indicating the result of the verification process.
     * @throws ConnectionException
     */
    public function verifyTransaction(Request $request): TransactionResource|JsonResponse
    {
        // Validate the reference code in the request
        $request->validate([
            /**
             * The unique reference code for the transaction.
             * @var string $reference
             * @example "TRX_123456789"
             */
            'reference' => ['required', 'string'],
        ]);

        // Retrieve the transaction based on the reference code
        $transaction = Transaction::where('reference', $request->reference)->firstOrFail();
        if (!$transaction) {
            return response()->notFound('Transaction not found');
        }

        // Ensure the transaction is in a pending state before verification
        if ($transaction->status !== TransactionStatusEnum::PENDING) {
            return response()->badRequest('Transaction is not in a pending state');
        }

        try {
            // Attempt to verify the transaction through Paystack service
            $verificationResult = $this->paystackService->verifyTransaction($request->reference);

            // Update transaction status if verification fails
            if (!$verificationResult['success']) {
                $transaction->status = TransactionStatusEnum::FAILED;
                $transaction->save();
                return response()->badRequest('Verification failed: ' . $verificationResult['message']);
            }

            // Confirm if payment was successful
            if ($verificationResult['data']['status'] === 'success') {
                return DB::transaction(function () use ($transaction, $verificationResult) {
                    // Check for duplicate transaction
                    $existingTransaction = Transaction::where('reference', $transaction->reference)
                        ->where('status', TransactionStatusEnum::COMPLETED)
                        ->first();

                    if ($existingTransaction) {
                        return response()->badRequest('This transaction has already been processed.');
                    }

                    $transaction->status = TransactionStatusEnum::COMPLETED;

                    // Update the bank-account balance
                    $bankAccount = $transaction->bankAccount;
                    $transaction->start_balance = $bankAccount->balance;
                    $bankAccount->balance += $transaction->amount;
                    $transaction->end_balance = $bankAccount->balance;
                    $bankAccount->save();

                    $transaction->save();

                    // Update the user's balance upon successful transaction
                    $user = $transaction->user;
                    $user->balance += $transaction->amount;
                    $user->save();

                    return response()->success([
                        'transaction' => new TransactionResource($transaction),
                        'new_balance' => $user->balance,
                    ], 'Transaction verified and completed successfully');
                });
            } else {
                // Mark the transaction as failed if payment was unsuccessful
                $transaction->status = TransactionStatusEnum::FAILED;
                $transaction->save();
                return response()->badRequest('Payment was not successful');
            }
        } catch (Exception $e) {
            // Handle any exception that may occur during verification
            return $this->handleException($e, 'verifying paystack transaction');
        }
    }

    /**
     * Process a payment using Paystack with a previously authorized payment method.
     *
     * This method handles the entire payment process for a pre-authorized payment method, including:
     * - Validating the request
     * - Charging the payment method via Paystack
     * - Creating a new Transaction record
     * - Updating the user's balance
     *
     * @param Request $request The incoming request containing payment details.
     * @return TransactionResource|JsonResponse The JSON response indicating the result of the payment process.
     */
    public function processAuthorizedPayment(Request $request): TransactionResource|JsonResponse
    {
        // Validate amount and payment method in the request
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
             * @example EfhxLZ9ck8
             */
            'payment_method_id' => ['required', new SqidExists('payment_methods')],

            /**
             * The ID of the payment method to be used for the transaction.
             * @var string $bank_account_id
             * @example EfhxLZ9ck8
             */
            'bank_account_id' => ['required', new SqidExists('bank_accounts')],
        ]);

        // Fetch user and validate active payment method
        $user = Auth::user();
        $paymentMethod = PaymentMethod::findBySqid($request->payment_method_id)->where('is_active', true)->first();
        if (!$paymentMethod) {
            return response()->notFound('Invalid payment method');
        }
        $bankAccount = BankAccount::findBySqid($request->bank_account_id)->where('user_id', $user->id)->first();
        if (!$bankAccount) {
            return response()->notFound('Invalid bank account');
        }

        try {
            return DB::transaction(function () use ($request, $user, $paymentMethod, $bankAccount) {
                // Check if there are enough balances for withdrawal
                if ($bankAccount->balance < $request->amount) {
                    return response()->badRequest('Insufficient balance in the bank account');
                }

                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'bank_account_id' => $bankAccount->id,
                    'type' => TransactionTypeEnum::WITHDRAWAL,
                    'amount' => $request->amount,
                    'status' => TransactionStatusEnum::PENDING,
                    'description' => 'Withdrawal via Paystack',
                    'start_balance' => $bankAccount->balance,
                ]);
                $transaction->save();

                $transferResult = $this->paystackService->initiateWithdrawal($request->amount, $request->reference_code);

                if (!$transferResult['success']) {
                    throw new Exception($transferResult['message']);
                }

                $transaction->status = TransactionStatusEnum::COMPLETED;
                $transaction->reference = $transferResult['data']['reference'];

                // Update bank-account balance
                $bankAccount->balance -= $request->amount;
                $transaction->end_balance = $bankAccount->balance;
                $bankAccount->save();

                $transaction->save();

                // Update user's balance
                $user->balance -= $request->amount;
                $user->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                    'new_bank_account_balance' => $bankAccount->balance,
                ], 'Withdrawal successful');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'withdrawing funds via Paystack');
        }
    }

    /**
     * Initiate a withdrawal to a user's bank account.
     *
     * This method handles withdrawal requests, including
     * - Validating the request
     * - Confirming sufficient balance
     * - Initiating the withdrawal process through Paystack
     *
     * @param Request $request The incoming request containing withdrawal details.
     * @return TransactionResource|JsonResponse The JSON response indicating the result of the withdrawal process.
     */
    public function initiateWithdrawal(Request $request): TransactionResource|JsonResponse
    {
        // Validate withdrawal request input
        $request->validate([
            /**
             * The amount to be withdrawn from the user's balance.
             * @var numeric $amount
             * @example 500
             */
            'amount' => ['required', 'numeric', 'min:0.01'],

            /**
             * The ID of the payment method associated with the withdrawal.
             * @var string $payment_method_id
             * @example uw2YK1rnl0
             */
            'payment_method_id' => ['required', new SqidExists('payment_methods')],

            /**
             * The ID of the payment method to be used for the transaction.
             * @var string $bank_account_id
             * @example EfhxLZ9ck8
             */
            'bank_account_id' => ['required', new SqidExists('bank_accounts')],

            /**
             * The reference code of the withdrawal.
             * @var string|null $reference_code
             * @example 500
             */
            'reference_code' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        // Confirm sufficient balance
        if ($user->balance < $request->amount) {
            return response()->badRequest('Insufficient balance');
        }

        // Fetch active payment method
        $paymentMethod = PaymentMethod::findBySqid($request->payment_method_id)->where('is_active', true)->first();
        if (!$paymentMethod) {
            return response()->notFound('Invalid payment method');
        }

        // Fetch a bank account
        $bankAccount = BankAccount::findBySqid($request->bank_account_id)->where('user_id', $user->id)->first();
        if (!$bankAccount) {
            return response()->notFound('Invalid bank account');
        }

        try {
            return DB::transaction(function () use ($request, $user, $paymentMethod, $bankAccount) {
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'bank_account_id' => $bankAccount->id,
                    'type' => TransactionTypeEnum::WITHDRAWAL,
                    'amount' => $request->amount,
                    'status' => TransactionStatusEnum::PENDING,
                    'description' => 'Withdrawal via Paystack',
                ]);
                $transaction->save();

                $transferResult = $this->paystackService->initiateWithdrawal($request->amount, $request->reference_code);

                if (!$transferResult['success']) {
                    throw new Exception($transferResult['message']);
                }

                $transaction->status = TransactionStatusEnum::COMPLETED;
                $transaction->reference = $transferResult['data']['reference'];
                $transaction->save();

                // Update user's balance
                $user->balance -= $request->amount;
                $user->save();

                // Update bank account balance
                $bankAccount->balance -= $request->amount;
                $bankAccount->save();

                return response()->success([
                    'transaction' => new TransactionResource($transaction),
                    'new_balance' => $user->balance,
                    'new_bank_account_balance' => $bankAccount->balance,
                ], 'Withdrawal successful');
            });
        } catch (Exception $e) {
            return $this->handleException($e, 'withdrawing funds via Paystack');
        }
    }

    /**
     * Verify a transfer.
     *
     * @param string $reference
     * @return JsonResponse
     */
    public function verifyTransfer(string $reference): JsonResponse
    {
        try {
            $result = $this->paystackService->verifyTransfer($reference);
            return response()->success($result, 'OK');
        } catch (Exception $e) {
            return $this->handleException($e, 'verifying transfer');
        }
    }
}
