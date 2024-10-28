<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class BankRequest extends BaseRequest
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
             * The unique identifier for the country associated with the bank.
             * @var string|null $country_id
             * @example "b12345"
             */
            'country_id' => ['nullable', new SqidExists('countries')],

            /**
             * The unique identifier for the currency associated with the bank.
             * @var string|null $currency_id
             * @example "b12345"
             */
            'currency_id' => ['nullable', new SqidExists('currencies')],

            /**
             * The name of the bank.
             * @var string $name
             * @example "ACCESS BANK"
             */
            'name' => ['required'],

            /**
             * The code of the bank.
             * @var string $code
             * @example "044"
             */
            'code' => ['required'],

            /**
             * The slug of the bank.
             * @var string|null $slug
             * @example "access-bank"
             */
            'slug' => ['nullable'],

            /**
             * The long code of the bank.
             * @var string|null $long_code
             * @example "04412345"
             */
            'long_code' => ['nullable'],

            /**
             * The gateway used by the bank.
             * @var string|null $gateway
             * @example "flutterwave"
             */
            'gateway' => ['nullable'],

            /**
             * Indicates if the bank supports pay-with-bank.
             * @var bool $pay_with_bank
             * @example true
             */
            'pay_with_bank' => ['boolean'],

            /**
             * The USSD code associated with the bank.
             * @var string|null $ussd
             * @example "*737#"
             */
            'ussd' => ['nullable', 'string', 'max:10'],

            /**
             * The logo URL for the bank.
             * @var string|null $logo
             * @example "https://example.com/logo.png"
             */
            'logo' => ['nullable', 'url'],

            /**
             * Indicates if the bank is active.
             * @var bool $is_active
             * @example true
             */
            'is_active' => ['boolean'],

            /**
             * The type of the bank.
             * @var string|null $type
             * @example "commercial"
             */
            'type' => ['nullable'],
        ];
    }
}
