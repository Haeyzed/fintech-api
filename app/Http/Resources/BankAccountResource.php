<?php

namespace App\Http\Resources;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property BankAccount $resource
 */
class BankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * The unique identifier of the bank account.
             * @var int $id
             * @example 1
             */
            'id' => $this->sqid,

            /**
             * The account number of the bank account.
             * @var string $account_number
             * @example "1234567890"
             */
            'account_number' => $this->account_number,

            /**
             * The type of the bank account.
             * @var string $account_type
             * @example "savings"
             */
            'account_type' => $this->account_type,

            /**
             * The current balance of the bank account.
             * @var float $balance
             * @example 1000.50
             */
            'balance' => $this->balance,

            /**
             * Indicates whether this is the primary bank account for the user.
             * @var bool $is_primary
             * @example true
             */
            'is_primary' => $this->is_primary,

            /**
             * The timestamp when the bank account was created.
             * @var string $created_at
             * @example "2024-10-01 12:00:00"
             */
            'created_at' => $this->created_at,

            /**
             * The timestamp when the bank account was last updated.
             * @var string $updated_at
             * @example "2024-10-20 15:45:00"
             */
            'updated_at' => $this->updated_at,

            /**
             * The user who owns this bank account.
             * @var UserResource|null $user
             */
            'user' => new UserResource($this->whenLoaded('user')),

            /**
             * The currency who owns this bank account.
             * @var CurrencyResource|null $currency
             */
            'currency' => new CurrencyResource($this->whenLoaded('currency')),

            /**
             * The bank who owns this bank account.
             * @var BankResource|null $currency
             */
            'bank' => new BankResource($this->whenLoaded('bank')),

            /**
             * The transactions associated with this bank account.
             * @var TransactionResource[]|null $transactions
             */
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
