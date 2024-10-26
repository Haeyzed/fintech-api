<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class Verify2FARequest extends BaseRequest
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
             * The SQID of the user attempting to verify 2FA.
             * @var string $user_id
             * @example "user_01234567"
             */
            'user_id' => ['required',new SqidExists('users')],

            /**
             * The one-time password generated by the authenticator app.
             * Required if recovery_code is not provided.
             * @var string $one_time_password
             * @example "987654"
             */
            'one_time_password' => ['required_without:recovery_code','string','size:6'],

            /**
             * The recovery code provided to the user during 2FA setup.
             * Required if one_time_password is not provided.
             * @var string $recovery_code
             * @example "abcdef123456"
             */
            'recovery_code' => ['required_without:one_time_password','string'],
        ];
    }
}
