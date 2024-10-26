<?php

namespace App\Http\Requests;

class ResetPasswordRequest extends BaseRequest
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
             * The password reset token.
             * @var string $token
             * @example "1234567890abcdef"
             */
            'token' => ['required', 'string'],

            /**
             * The email address of the user.
             * @var string $email
             * @example "john@example.com"
             */
            'email' => ['required', 'email'],

            /**
             * The new password for the user account.
             * @var string $password
             * @example "newpassword123"
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
