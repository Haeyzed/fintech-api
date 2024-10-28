<?php

namespace App\Http\Resources;

use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property State $resource
 */
class StateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sqid,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'type' => $this->type,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'country' => new CountryResource($this->whenLoaded('country')),
            'cities' => CityResource::collection($this->whenLoaded('cities')),
        ];
    }
}
