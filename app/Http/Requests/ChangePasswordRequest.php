<?php

namespace App\Http\Requests;

class ChangePasswordRequest extends BaseRequest
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
             * The current password of the user.
             * @var string $current_password
             * @example "oldpassword123"
             */
            'current_password' => ['required', 'string'],

            /**
             * The new password for the user account.
             * @var string $new_password
             * @example "newpassword123"
             */
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
