<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;

class ImportRequest extends BaseRequest
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
             * The CSV file containing user data to import.
             * @var UploadedFile $file
             * @example users.csv
             */
            'file' => ['required','file','mimes:csv,txt'],
        ];
    }
}
