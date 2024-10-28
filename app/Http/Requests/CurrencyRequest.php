<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class CurrencyRequest extends BaseRequest
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
             * The name of the currency.
             * @var string $currency
             * @example "US Dollar"
             */
            'currency' => ['nullable', 'string'],

            /**
             * The code of the currency.
             * @var string $code
             * @example "USD"
             */
            'code' => ['nullable', 'string'],

            /**
             * The symbol of the currency.
             * @var string $symbol
             * @example "$"
             */
            'symbol' => ['nullable', 'string'],

            /**
             * The thousand separator for the currency.
             * @var string $thousand_separator
             * @example ","
             */
            'thousand_separator' => ['required', 'string'],

            /**
             * The decimal separator for the currency.
             * @var string $decimal_separator
             * @example "."
             */
            'decimal_separator' => ['required', 'string'],
        ];
    }
}
