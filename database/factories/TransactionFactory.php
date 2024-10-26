<?php

namespace Database\Factories;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => $this->faker->numerify(),
            'user_id' => User::factory(),
            'bank_account_id' => BankAccount::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'type' => $this->faker->randomElement(TransactionTypeEnum::values()),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => $this->faker->randomElement(TransactionStatusEnum::values()),
            'description' => $this->faker->sentence,
        ];
    }
}
