<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PaymentMethod $resource
 */
class PaymentMethodResource extends JsonResource
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
             * The unique identifier of the payment method.
             * @var int $id
             * @example 1
             */
            'id' => $this->sqid,

            /**
             * The type of the payment method (e.g., 'credit_card', 'paypal', 'stripe').
             * @var string $type
             * @example "credit_card"
             */
            'type' => $this->type,

            /**
             * Additional details about the payment method, stored as JSON.
             * @var array $details
             * @example {"card_number": "************1234", "expiration_month": 12, "expiration_year": 2025}
             */
            'details' => $this->details,

            /**
             * Indicates whether this is the active payment method for the user.
             * @var bool $is_active
             * @example true
             */
            'is_active' => $this->is_active,

            /**
             * The timestamp when the payment method was created.
             * @var string $created_at
             * @example "2024-10-01 12:00:00"
             */
            'created_at' => $this->created_at,

            /**
             * The timestamp when the payment method was last updated.
             * @var string $updated_at
             * @example "2024-10-20 15:45:00"
             */
            'updated_at' => $this->updated_at,

            /**
             * The user who owns this payment method.
             * @var UserResource|null $user
             */
            'user' => new UserResource($this->whenLoaded('user')),

            /**
             * The transactions associated with this payment method.
             * @var TransactionResource[]|null $transactions
             */
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
