<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class UserResource extends JsonResource
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
             * The unique identifier of the user.
             * @var string $sqid
             * @example "a1234567-b89c-12d3-e456-426614174000"
             */
            'id' => $this->sqid,

            /**
             * The full name of the user.
             * @var string $name
             * @example "John Doe"
             */
            'name' => $this->name,

            /**
             * The email address of the user.
             * @var string $email
             * @example "johndoe@example.com"
             */
            'email' => $this->email,

            /**
             * The username of the user.
             * @var string $username
             * @example "john.doe"
             */
            'username' => $this->username,

            /**
             * The phone number of the user.
             * @var string|null $phone
             * @example "+1234567890"
             */
            'phone' => $this->phone,

            /**
             * The balance of the user.
             * @var float|null $balance
             * @example 0.00
             */
            'balance' => $this->balance,

            /**
             * The profile image URL of the user.
             * @var string|null $profile_image
             * @example "https://example.com/profile.jpg"
             */
            'profile_image' => $this->profile_image,

            /**
             * The timestamp when the email was verified.
             * @var string|null $email_verified_at
             * @example "2023-08-22 14:30:00"
             */
            'email_verified_at' => $this->email_verified_at,

            /**
             * The timestamp of the user's last login.
             * @var string|null $last_login_at
             * @example "2024-10-01 10:15:00"
             */
            'last_login_at' => $this->last_login_at,

            /**
             * The timestamp of the user's current login.
             * @var string|null $current_login_at
             * @example "2024-10-22 09:00:00"
             */
            'current_login_at' => $this->current_login_at,

            /**
             * The IP address of the user's last login.
             * @var string|null $last_login_ip
             * @example "192.168.1.1"
             */
            'last_login_ip' => $this->last_login_ip,

            /**
             * The IP address of the user's current login.
             * @var string|null $current_login_ip
             * @example "192.168.1.2"
             */
            'current_login_ip' => $this->current_login_ip,

            /**
             * The total number of times the user has logged in.
             * @var int|null $login_count
             * @example 10
             */
            'login_count' => $this->login_count,

            /**
             * The provider used for authentication (e.g., Google, Facebook).
             * @var string|null $provider
             * @example "google"
             */
            'provider' => $this->provider,

            /**
             * The unique ID provided by the authentication provider.
             * @var string|null $provider_id
             * @example "112233445566"
             */
            'provider_id' => $this->provider_id,

            /**
             * Indicates whether Google Two-Factor Authentication (2FA) is enabled.
             * @var bool $google2fa_enabled
             * @example true
             */
            'google2fa_enabled' => $this->google2fa_enabled,

            /**
             * The recovery codes for two-factor authentication.
             * @var array|null $two_factor_recovery_codes
             * @example ["code1", "code2", "code3"]
             */
            'recovery_codes' => $this->two_factor_recovery_codes,

            /**
             * The timestamp when the user was created.
             * @var string $created_at
             * @example "2024-10-01 12:00:00"
             */
            'created_at' => $this->created_at,

            /**
             * The timestamp when the user was last updated.
             * @var string|null $updated_at
             * @example "2024-10-20 15:45:00"
             */
            'updated_at' => $this->updated_at,

            /**
             * The timestamp when the user was deleted, if applicable.
             * @var string|null $deleted_at
             * @example "2024-10-22 18:00:00"
             */
            'deleted_at' => $this->deleted_at,
        ];
    }
}
