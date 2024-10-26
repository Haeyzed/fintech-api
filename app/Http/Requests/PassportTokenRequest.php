<?php

namespace App\Http\Requests;

class PassportTokenRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * The email of the user.
             * @var string $email
             * @example "superadmin@example.com"
             */
            'email' => ['required', 'email'],

            /**
             * The password of the user.
             * @var string $password
             * @example "password"
             */
            'password' => ['required', 'string'],

            /**
             * The client ID of the Passport client.
             * @var string $client_id
             * @example "9d173999-094a-4088-9e5d-34c7397240d2"
             */
            'client_id' => ['required', 'string'],

            /**
             * The client secret of the Passport client.
             * @var string $client_secret
             * @example "t93m5iKJi6c1DUnDmivr9pZsshPVGSeh7Ee40y1X"
             */
            'client_secret' => ['required', 'string'],

            /**
             * The grant type for the token request.
             * @var string $grant_type
             * @example "password"
             */
            'grant_type' => ['required', 'string', 'in:password,client_credentials'],

            /**
             * The name of the client.
             * @var string $client_name
             * @example "My Application"
             */
            'client_name' => ['required', 'string', 'max:255'],

            /**
             * The scopes requested for the token.
             * @var array $scopes
             * @example ["read", "write"]
             */
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string'],

            /**
             * The FCM device token for push notifications.
             * @var string $device_token
             * @example "fMIRMc1kF0M:APA91bHqL8xuNZhVJPzlXTov9m9r8KCWsFS..."
             */
            'device_token' => ['nullable', 'string'],

            /**
             * The name of the device being used.
             * @var string $device_name
             * @example "iPhone 12 Pro"
             */
            'device_name' => ['nullable', 'string', 'max:255'],

            /**
             * The version of the application being used.
             * @var string $app_version
             * @example "1.0.0"
             */
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
