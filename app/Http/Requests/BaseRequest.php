<?php

namespace App\Http\Requests;

use App\Helpers\TranslateTextHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $translatedErrors = [];

        foreach ($errors->messages() as $field => $messages) {
            $translatedErrors[$field] = array_map(function ($message) {
                return TranslateTextHelper::translate($message);
            }, $messages);
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => TranslateTextHelper::translate('Validation failed'),
            'errors' => $translatedErrors
        ], 422));
    }
}
