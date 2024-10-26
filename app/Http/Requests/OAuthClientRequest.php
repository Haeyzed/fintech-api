<?php

namespace App\Http\Requests;

class OAuthClientRequest extends BaseRequest
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
             * The name of the OAuth client.
             * @var string $name
             * @example "My Application"
             */
            'name' => ['required', 'string', 'max:255'],

            /**
             * The redirect URL for the OAuth client.
             * @var string $redirect
             * @example "https://myapp.com/callback"
             */
            'redirect' => ['required', 'url'],

            /**
             * The unique identifier of the vendor.
             * @var int $vendor_id
             * @example 12345
             */
            'vendor_id' => ['nullable', 'integer', 'exists:ecommerce.vendors,id'],

            /**
             * The name of the vendor.
             * @var string $vendor_name
             * @example "Acme Corporation"
             */
            'vendor_name' => ['nullable', 'string', 'max:255'],

            /**
             * The name of the client application.
             * @var string $client_app
             * @example "Acme Mobile App"
             */
            'client_app' => ['nullable', 'string', 'max:255'],
        ];
    }
}
