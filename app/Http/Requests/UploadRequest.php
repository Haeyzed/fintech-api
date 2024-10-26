<?php

namespace App\Http\Requests;

use App\Enums\StorageProviderEnum;
use DateTime;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Enum;

class UploadRequest extends BaseRequest
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
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            /**
             * The file to be uploaded.
             * @var UploadedFile $file
             * @example new \Illuminate\Http\UploadedFile('/path/to/file.jpg', 'file.jpg', 'image/jpeg', null, true)
             */
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max file size
                'mimes:jpeg,png,pdf,doc,docx,xls,xlsx,zip,rar', // Allowed file types
            ],

            /**
             * The storage path for the uploaded file.
             * @var string $path
             * @example "uploads/2023/05/12/"
             */
            'path' => [
                'required',
                'string',
                'max:255',
            ],

            /**
             * The storage provider for the uploaded file.
             * @var StorageProviderEnum $storage_provider
             * @example StorageProviderEnum::LOCAL
             */
            'storage_provider' => [
                'required',
                new Enum(StorageProviderEnum::class),
            ],

            /**
             * The description of the uploaded file.
             * @var string|null $description
             * @example "This is a sample file uploaded for testing purposes."
             */
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /**
             * The tags associated with the uploaded file.
             * @var array|null $tags
             * @example ["sample", "test", "documentation"]
             */
            'tags' => [
                'nullable',
                'array',
            ],

            /**
             * Individual tag for the uploaded file.
             * @var string $tags[*]
             * @example "sample"
             */
            'tags.*' => [
                'string',
                'max:50',
            ],

            /**
             * Whether the uploaded file is publicly accessible.
             * @var bool $is_public
             * @example true
             */
            'is_public' => [
                'boolean',
            ],

            /**
             * The expiration date of the uploaded file.
             * @var DateTime|null $expires_at
             * @example new \DateTime('2023-12-31 23:59:59')
             */
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'uploaded file',
            'path' => 'storage path',
            'storage_provider' => 'storage provider',
            'description' => 'file description',
            'tags' => 'file tags',
            'tags.*' => 'tag',
            'is_public' => 'public access',
            'expires_at' => 'expiration date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for upload.',
            'file.max' => 'The file size must not exceed 10MB.',
            'file.mimes' => 'The file must be of type: jpeg, png, pdf, doc, docx, xls, xlsx, zip, or rar.',
            'path.required' => 'A storage path is required.',
            'storage_provider.required' => 'A storage provider must be specified.',
            'tags.*.max' => 'Each tag must not exceed 50 characters.',
            'is_public.boolean' => 'The public access field must be true or false.',
            'expires_at.after' => 'The expiration date must be a future date.',
        ];
    }
}
