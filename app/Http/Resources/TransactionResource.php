<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Transaction $resource
 */
class TransactionResource extends JsonResource
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
             * The unique identifier of the transaction.
             * @var int $id
             * @example 1
             */
            'id' => $this->sqid,

            /**
             * The unique reference code for the transaction.
             * @var string $reference
             * @example "TRX_123456789"
             */
            'reference' => $this->reference,

            /**
             * The type of the transaction (e.g., 'deposit', 'withdrawal').
             * @var string $type
             * @example "deposit"
             */
            'type' => $this->type,

            /**
             * The amount of the transaction.
             * @var float $amount
             * @example 100.50
             */
            'amount' => $this->amount,

            /**
             * The status of the transaction (e.g., 'pending', 'completed', 'failed').
             * @var string $status
             * @example "completed"
             */
            'status' => $this->status,

            /**
             * A description of the transaction.
             * @var string|null $description
             * @example "Monthly salary deposit"
             */
            'description' => $this->description,

            /**
             * The timestamp when the transaction was created.
             * @var string $created_at
             * @example "2024-10-01 12:00:00"
             */
            'created_at' => $this->created_at,

            /**
             * The timestamp when the transaction was last updated.
             * @var string $updated_at
             * @example "2024-10-01 12:00:00"
             */
            'updated_at' => $this->updated_at,

            /**
             * The user who performed this transaction.
             * @var UserResource|null $user
             */
            'user' => new UserResource($this->whenLoaded('user')),

            /**
             * The bank account associated with this transaction.
             * @var BankAccountResource|null $bank_account
             */
            'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),

            /**
             * The payment method used for this transaction.
             * @var PaymentMethodResource|null $payment_method
             */
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
        ];
    }
}
