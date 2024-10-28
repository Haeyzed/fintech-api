<?php

namespace App\Http\Resources;

use App\Helpers\DateTimeFormatHelper;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property City $resource
 */
class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sqid,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'country' => new CountryResource($this->whenLoaded('country')),
            'state' => new StateResource($this->whenLoaded('state')),
        ];
    }
}
