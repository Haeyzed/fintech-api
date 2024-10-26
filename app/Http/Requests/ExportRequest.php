<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ExportRequest extends BaseRequest
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
             * The model to export data from.
             * @var string $model
             * @example "User"
             */
            'model' => ['required', 'string', Rule::in($this->getAvailableModels())],

            /**
             * The email address(es) to send the export file to.
             * @var array $emails
             * @example ["user1@example.com", "user2@example.com"]
             */
            'emails' => ['required', 'array'],
            'emails.*' => ['required', 'email'],

            /**
             * The start date for the export range.
             * @var string $start_date
             * @example "2023-01-01"
             */
            'start_date' => ['required', 'date'],

            /**
             * The end date for the export range.
             * @var string $end_date
             * @example "2023-12-31"
             */
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            /**
             * The file type for the export.
             * @var string $file_type
             * @example "csv"
             */
            'file_type' => ['required', 'in:csv,xlsx'],

            /**
             * The columns to include in the export.
             * @var array $columns
             * @example ["name", "email", "created_at"]
             */
            'columns' => ['required', 'array'],
            'columns.*' => ['required', 'string', Rule::in($this->getAvailableColumns())],
        ];
    }

    protected function getAvailableModels(): array
    {
        return [
            'User',
            // Add other model names here as needed
        ];
    }

    protected function getAvailableColumns(): array
    {
        $modelClass = 'App\\Models\\' . $this->input('model');
        if (method_exists($modelClass, 'getAvailableColumns')) {
            return $modelClass::getAvailableColumns();
        }
        return [];
    }
}
