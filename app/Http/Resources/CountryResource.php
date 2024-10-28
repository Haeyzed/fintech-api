<?php

namespace App\Http\Resources;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Country $resource
 */
class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sqid,
            'name' => $this->name,
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'phone_code' => $this->phone_code,
            'region' => $this->region,
            'subregion' => $this->subregion,
            'native' => $this->native,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'emoji' => $this->emoji,
            'emojiU' => $this->emojiU,
            'status' => $this->status,
        ];
    }
}
