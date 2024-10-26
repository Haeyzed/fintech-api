<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodTypeEnum;

class PaymentMethodRequest extends BaseRequest
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
             * The type of the payment method (e.g., 'credit_card', 'paypal', 'stripe').
             * @var string $type
             * @example "credit_card"
             */
            'type' => ['required', 'string', 'in:' . implode(',', PaymentMethodTypeEnum::values())],

            /**
             * Additional details about the payment method, stored as JSON.
             * @var array $details
             * @example {"card_number": "4111111111111111", "expiration_month": 12, "expiration_year": 2025, "cvv": "123"}
             */
            'details' => ['required', 'array'],

            /**
             * Indicates whether this is the active payment method.
             * @var bool $is_active
             * @example true
             */
            'is_active' => ['boolean'],

            /**
             * The card number for credit card payment methods.
             * @var string $details.card_number
             * @example "4111111111111111"
             */
            'details.card_number' => ['required_if:type,credit_card', 'string', 'max:16'],

            /**
             * The expiration month for credit card payment methods.
             * @var int $details.expiration_month
             * @example 12
             */
            'details.expiration_month' => ['required_if:type,credit_card', 'integer', 'between:1,12'],

            /**
             * The expiration year for credit card payment methods.
             * @var int $details.expiration_year
             * @example 2025
             */
            'details.expiration_year' => ['required_if:type,credit_card', 'integer', 'min:' . date('Y')],

            /**
             * The CVV for credit card payment methods.
             * @var string $details.cvv
             * @example "123"
             */
            'details.cvv' => ['required_if:type,credit_card', 'string', 'size:3'],

            /**
             * The email address for PayPal payment methods.
             * @var string $details.email
             * @example "user@example.com"
             */
            'details.email' => ['required_if:type,paypal', 'email'],

            /**
             * The token for Stripe or Paystack payment methods.
             * @var string $details.token
             * @example "tok_1234567890abcdef"
             */
            'details.token' => ['required_if:type,stripe,paystack', 'string'],
        ];
    }
}
