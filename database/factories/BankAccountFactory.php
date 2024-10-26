<?php

namespace Database\Factories;

use App\Enums\AccountTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_number' => $this->faker->numerify('##########'),
            'bank_name' => $this->faker->company . ' Bank',
            'account_type' => $this->faker->randomElement(AccountTypeEnum::values()),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'is_primary' => $this->faker->boolean(20), // 20% chance of being primary
        ];
    }
}
