<?php

namespace App\Http\Resources;

use App\Helpers\DateTimeFormatHelper;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Bank $resource
 */
class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sqid,
            'name' => $this->name,
            'code' => $this->code,
            'slug' => $this->slug,
            'long_code' => $this->long_code,
            'gateway' => $this->gateway,
            'pay_with_bank' => $this->pay_with_bank,
            'is_active' => $this->is_active,
            'type' => $this->type,
            'ussd' => $this->ussd,
            'logo' => $this->logo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'country' => new CountryResource($this->whenLoaded('country')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
        ];
    }
}
