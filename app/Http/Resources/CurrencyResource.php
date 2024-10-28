<?php

namespace App\Http\Resources;

use App\Helpers\DateTimeFormatHelper;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Currency $resource
 */
class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sqid,
            'name' => $this->name,
            'code' => $this->code,
            'precision' => $this->precision,
            'symbol' => $this->symbol,
            'symbol_native' => $this->symbol_native,
            'symbol_first' => $this->symbol_first,
            'decimal_mark' => $this->decimal_mark,
            'thousands_separator' => $this->thousands_separator,

            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }
}
