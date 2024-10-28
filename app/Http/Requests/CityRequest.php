<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class CityRequest extends BaseRequest
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
             * The unique identifier for the country associated with the bank.
             * @var string|null $country_id
             * @example "b12345"
             */
            'country_id' => ['nullable', new SqidExists('countries')],

            /**
             * The ID of the state to which the city belongs.
             * Must exist in the states table.
             * @var string|null
             * @example "fqhypxm"
             */
            'state_id' => ['nullable', new SqidExists('states')],

            /**
             * The name of the city.
             * @var string
             * @example "Los Angeles"
             */
            'name' => ['required'],

            /**
             * The state code of the city.
             * @var string|null
             * @example "CA"
             */
            'state_code' => ['nullable'],

            /**
             * The country code of the city.
             * @var string|null
             * @example "US"
             */
            'country_code' => ['nullable'],

            /**
             * The latitude of the city's geographical center.
             * @var float|null
             * @example 34.0522
             */
            'latitude' => ['nullable'],

            /**
             * The longitude of the city's geographical center.
             * @var float|null
             * @example -118.2437
             */
            'longitude' => ['nullable'],

            /**
             * Indicates if the city has a flag.
             * @var bool|null
             * @example true
             */
            'flag' => ['nullable', 'boolean'],

            /**
             * The WikiData ID of the city.
             * @var string|null
             * @example "Q65"
             */
            'wikiDataId' => ['nullable'],
        ];
    }
}
