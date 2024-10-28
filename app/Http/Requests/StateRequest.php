<?php

namespace App\Http\Requests;

use App\Rules\SqidExists;

class StateRequest extends BaseRequest
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
             * The name of the state.
             * @var string $name
             * @example "California"
             */
            'name' => ['required'],

            /**
             * The ISO 3166-1 alpha-2 code of the country.
             * @var string|null $country_code
             * @example "US"
             */
            'country_code' => ['nullable'],

            /**
             * The FIPS (Federal Information Processing Standards) code.
             * @var string|null $fips_code
             * @example "06"
             */
            'fips_code' => ['nullable'],

            /**
             * The ISO 3166-2 alpha-2 code of the state.
             * @var string|null $iso2
             * @example "CA"
             */
            'iso2' => ['nullable'],

            /**
             * The latitude of the state's geographical center.
             * @var float|null $latitude
             * @example 36.7783
             */
            'latitude' => ['nullable'],

            /**
             * The longitude of the state's geographical center.
             * @var float|null $longitude
             * @example -119.4179
             */
            'longitude' => ['nullable'],

            /**
             * Indicates if the state has a flag.
             * @var bool|null $flag
             * @example true
             */
            'flag' => ['nullable', 'boolean'],

            /**
             * The WikiData ID of the state.
             * @var string|null $wikiDataId
             * @example "Q99"
             */
            'wikiDataId' => ['nullable'],
        ];
    }
}
