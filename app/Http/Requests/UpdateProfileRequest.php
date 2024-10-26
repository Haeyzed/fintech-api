<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;

class UpdateProfileRequest extends BaseRequest
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
             * The name of the user.
             * @var string $name
             * @example "John Doe"
             */
            'name' => ['sometimes', 'string', 'max:255'],

            /**
             * The email address of the user.
             * @var string $email
             * @example "john@example.com"
             */
            'email' => ['sometimes', 'email', 'unique:users,email,' . auth()->id()],

            /**
             * The username of the user.
             * @var string $username
             * @example "johndoe"
             */
            'username' => ['sometimes', 'string', 'unique:users,username,' . auth()->id()],

            /**
             * The phone number of the user.
             * @var string $phone
             * @example "+1234567890"
             */
            'phone' => ['sometimes', 'string', 'unique:users,phone,' . auth()->id()],

            /**
             * The profile image of the user.
             * @var UploadedFile $profile_image
             */
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
