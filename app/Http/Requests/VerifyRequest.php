<?php

namespace App\Http\Requests;

class VerifyRequest extends BaseRequest
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
             * The ID of the user to be verified.
             * @var int $id
             * @example 1
             */
            'id' => ['required', 'integer'],

            /**
             * The verification hash.
             * @var string $hash
             * @example "1234567890abcdef"
             */
            'hash' => ['required', 'string'],
        ];
    }
}
