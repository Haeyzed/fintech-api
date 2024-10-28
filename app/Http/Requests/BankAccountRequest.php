<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class BankAccountRequest extends BaseRequest
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
             * The user of the bank account.
             * @var string $user_id
             * @example "1234567890"
             */
            'user_id' => ['nullable', new SqidExists('users')],

            /**
             * The account number of the bank account.
             * @var string $bank_id
             * @example "sqiuwu"
             */
            'bank_id' => ['nullable', new SqidExists('banks')],

            /**
             * The unique identifier for the currency associated with the bank.
             * @var string|null $currency_id
             * @example "b12345"
             */
            'currency_id' => ['nullable', new SqidExists('currencies')],

            /**
             * The account number of the bank account.
             * @var string $account_number
             * @example "1234567890"
             */
            'account_number' => ['required', 'string', 'max:255'],

            /**
             * The type of the bank account.
             * @var string $account_type
             * @example "savings"
             */
            'account_type' => ['required', 'string', 'in:savings,checking,business'],

            /**
             * The current balance of the bank account.
             * @var float|null $balance
             * @example 1000.50
             */
            'balance' => ['nullable', 'numeric', 'min:0'],

            /**
             * Indicates whether this is the primary bank account for the user.
             * @var bool $is_primary
             * @example true
             */
            'is_primary' => ['boolean'],
        ];
    }
}
