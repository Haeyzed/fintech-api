<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
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
             * The email address of the user.
             * @var string $email
             * @example "superadmin@example.com"
             */
            'email' => ['required', 'email'],

            /**
             * The password for the user account.
             * @var string $password
             * @example "password"
             */
            'password' => ['required', 'string'],

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
