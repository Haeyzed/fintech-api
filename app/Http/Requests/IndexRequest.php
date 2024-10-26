<?php

namespace App\Http\Requests;

class IndexRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'with_trashed' => filter_var($this->get('with_trashed'), FILTER_VALIDATE_BOOLEAN),
        ]);
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
             * @query
             * The number of items to return per page.
             * @var int|null $per_page
             * @example 15
             */
            'per_page' => ['integer', 'min:1', 'max:100'],

            /**
             * @query
             * The search term to filter results.
             * @var string|null $search
             * @example "keyword"
             */
            'search' => ['nullable', 'string', 'max:255'],

            /**
             * @query
             * Include soft deleted records in the results.
             * @var bool|null $with_trashed
             * @example true
             */
            'with_trashed' => ['boolean'],

            /**
             * @query
             * The field to order the results by.
             * @var string|null $order_by
             * @example created_at
             */
            'order_by' => ['string'],

            /**
             * @query
             * The direction to order the results.
             * @var string|null $order_direction
             * @example desc
             */
            'order_direction' => ['nullable', 'string', 'in:asc,desc'],

            /**
             * @query
             * The start date to filter results.
             * @var string|null $start_date
             * @example 2023-01-01
             */
            'start_date' => ['nullable', 'date'],

            /**
             * @query
             * The end date to filter results.
             * @var string|null $end_date
             * @example 2023-12-31
             */
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
