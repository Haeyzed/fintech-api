<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class RegisterRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * The name of the user.
             * @var string $name
             * @example "John Doe"
             */
            'name' => ['required', 'string', 'max:255'],

            /**
             * The email address of the user.
             * @var string $email
             * @example "john@example.com"
             */
            'email' => ['required', 'email', 'unique:users,email'],

            /**
             * The username of the user.
             * @var string $username
             * @example "johndoe"
             */
            'username' => ['required', 'string', 'unique:users,username'],

            /**
             * The phone number of the user.
             * @var string $phone
             * @example "+1234567890"
             */
            'phone' => ['required', 'string', 'unique:users,phone'],

            /**
             * The profile image of the user.
             * @var UploadedFile $profile_image
             */
            'profile_image' => ['nullable', 'image', 'max:2048'],

            /**
             * The password for the user account.
             * @var string $password
             * @example "password"
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            /**
             * The password confirmation.
             * @var string $password_confirmation
             * @example "password"
             */
            'password_confirmation' => ['required', 'string', 'min:8'],

            /**
             * The FCM device token for push notifications.
             * @var string $device_token
             * @example "fMIRMc1kF0M:APA91bHqL8xuNZhVJPzlXTov9m9r8KCWsFS..."
             */
            'device_token' => ['nullable', 'string'],
        ];
    }
}
