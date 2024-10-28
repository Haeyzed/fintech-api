<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodTypeEnum;
use App\Models\BankAccount;
use App\Models\BlockedIp;
use App\Models\PassKey;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create payment methods
//        $creditCard = PaymentMethod::create([
//            'type' => PaymentMethodTypeEnum::CREDIT_CARD,
//            'details' => ['brand' => 'Visa', 'last4' => '4242'],
//        ]);

//        $paypal = PaymentMethod::create([
//            'type' => PaymentMethodTypeEnum::PAYPAL,
//            'details' => ['email' => 'paypal@example.com'],
//        ]);

        $paystack = PaymentMethod::create([
            'type' => PaymentMethodTypeEnum::PAYSTACK,
            'details' => ['authorization_code' => '', 'email' => 'paystack@example.com'],
            'is_active' => true,
        ]);

//        $stripe = PaymentMethod::create([
//            'type' => PaymentMethodTypeEnum::STRIPE,
//            'details' => ['email' => 'stripe@example.com'],
//        ]);

        // Create Super Admin
        $superAdmin = User::factory()
            ->has(BlockedIp::factory(3))
            ->has(PassKey::factory(3))
            ->has(BankAccount::factory(2))
//            ->has(PaymentMethod::factory(2))
            ->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'username' => 'superadmin',
                'phone' => '+2348136834496',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

        // Create transactions for Super Admin
//        Transaction::factory(10)->create([
//            'user_id' => $superAdmin->id,
//            'bank_account_id' => $superAdmin->bankAccounts->random()->id,
//            'payment_method_id' => $superAdmin->paymentMethods->random()->id,
//        ]);

        // Create John Doe
        $johnDoe = User::factory()
            ->has(BlockedIp::factory(3))
            ->has(PassKey::factory(3))
            ->has(BankAccount::factory(2))
//            ->has(PaymentMethod::factory(2))
            ->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
                'phone' => '+1234567890',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

        // Create transactions for John Doe
//        Transaction::factory(10)->create([
//            'user_id' => $johnDoe->id,
//            'bank_account_id' => $johnDoe->bankAccounts->random()->id,
//            'payment_method_id' => $johnDoe->paymentMethods->random()->id,
//        ]);

        // Create 10 random users with associated data
        User::factory(10)
            ->has(BlockedIp::factory(3))
            ->has(BankAccount::factory(3))
            ->has(PassKey::factory(3))
//            ->has(PaymentMethod::factory(2))
//            ->has(
//                Transaction::factory(5)
//                    ->state(function (array $attributes, User $user) {
//                        return [
//                            'bank_account_id' => $user->bankAccounts->random()->id,
//                            'payment_method_id' => $user->paymentMethods->random()->id,
//                        ];
//                    })
//            )
            ->create();
    }
}
