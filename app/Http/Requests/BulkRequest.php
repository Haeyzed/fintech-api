<?php

namespace App\Http\Requests;

class BulkRequest extends BaseRequest
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
             * An array of SQIDs representing the users to perform bulk actions on.
             * @var array $sqids
             * @example ["abc123", "def456", "ghi789"]
             */
            'sqids' => ['required','array'],

            /**
             * Each SQID in the array must be a string.
             * @var string $sqids.*
             * @example "abc123"
             */
            'sqids.*' => ['required','string'],
        ];
    }
}
