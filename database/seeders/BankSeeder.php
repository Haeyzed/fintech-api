<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = config('banks.banks');

        foreach ($banks as $bank) {
            Bank::firstOrCreate(
                ['code' => $bank['code']],
                [
                    'country_id' => $bank['country_id'],
                    'currency_id' => $bank['currency_id'],
                    'name' => $bank['name'],
                    'slug' => $bank['slug'],
                    'long_code' => $bank['long_code'],
                    'gateway' => $bank['gateway'],
                    'pay_with_bank' => $bank['pay_with_bank'],
                    'type' => $bank['type'],
                    'ussd' => $bank['ussd'],
                    'logo' => $bank['logo'],
                    'is_active' => $bank['is_active'],
                ]
            );
        }
    }
}
