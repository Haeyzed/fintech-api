<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;

class TransactionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * The ID of the bank account associated with this transaction.
             * @var int $bank_account_id
             * @example 1
             */
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],

            /**
             * The ID of the payment method used for this transaction.
             * @var int $payment_method_id
             * @example 1
             */
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],

            /**
             * The type of the transaction (e.g., 'deposit', 'withdrawal').
             * @var string $type
             * @example "deposit"
             */
            'type' => ['required', 'string', 'in:' . implode(',', TransactionTypeEnum::values())],

            /**
             * The amount of the transaction.
             * @var float $amount
             * @example 100.50
             */
            'amount' => ['required', 'numeric', 'min:0.01'],

            /**
             * The status of the transaction (e.g., 'pending', 'completed', 'failed').
             * @var string $status
             * @example "completed"
             */
            'status' => ['required', 'string', 'in:' . implode(',', TransactionStatusEnum::values())],

            /**
             * A description of the transaction.
             * @var string $description
             * @example "Monthly salary deposit"
             */
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
