<?php

namespace Database\Factories;

use App\Enums\PaymentMethodTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(PaymentMethodTypeEnum::values());

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'details' => $this->getPaymentMethodDetails($type),
            'is_default' => $this->faker->boolean(20), // 20% chance of being default
        ];
    }

    /**
     * Get payment method details based on the type.
     *
     * @param string $type
     * @return array
     */
    private function getPaymentMethodDetails(string $type): array
    {
        return match ($type) {
            PaymentMethodTypeEnum::CREDIT_CARD => [
                'card_number' => $this->faker->creditCardNumber,
                'expiration_month' => $this->faker->numberBetween(1, 12),
                'expiration_year' => $this->faker->numberBetween(date('Y'), date('Y') + 5),
                'cvv' => $this->faker->numberBetween(100, 999),
            ],
            PaymentMethodTypeEnum::PAYPAL => [
                'email' => $this->faker->email,
            ],
            PaymentMethodTypeEnum::PAYSTACK, PaymentMethodTypeEnum::STRIPE => [
                'token' => $this->faker->md5,
            ],
            default => [],
        };
    }
}
