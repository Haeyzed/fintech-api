<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\Token;

/** @mixin Token */
class OAuthTokenResource extends JsonResource
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
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'name' => $this->name,
            'scopes' => $this->scopes,
            'revoked' => $this->revoked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expires_at' => $this->expires_at,
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'current_login_at' => $this->user->current_login_at,
                'current_login_ip' => $this->user->current_login_ip,
                'last_login_at' => $this->user->last_login_at,
                'last_login_ip' => $this->user->last_login_ip,
            ],
            'client' => [
                'vendor' => $this->client->vendor,
                'client_app' => $this->client->client_app,
            ],
        ];
    }
}
