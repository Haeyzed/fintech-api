<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\Client;

/** @mixin Client */
class OAuthClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'secret' => $this->secret,
            'redirect' => $this->redirect,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->vendor,
            'client_app' => $this->client_app,
            'personal_access_client' => $this->personal_access_client,
            'password_client' => $this->password_client,
            'revoked' => $this->revoked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
