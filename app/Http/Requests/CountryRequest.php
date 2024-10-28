<?php

namespace App\Http\Requests;

class CountryRequest extends BaseRequest
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
             * The name of the country.
             * @var string $name
             * @example "Ukraine"
             */
            'name' => ['required'],

            /**
             * The ISO 3166-1 alpha-2 code.
             * @var string|null $iso2
             * @example "UA"
             */
            'iso2' => ['nullable'],

            /**
             * The ISO 3166-1 alpha-3 code.
             * @var string|null $iso3
             * @example "UKR"
             */
            'iso3' => ['nullable'],

            /**
             * The country calling code.
             * @var string|null $phonecode
             * @example "380"
             */
            'phonecode' => ['nullable'],

            /**
             * The capital city of the country.
             * @var string|null $capital
             * @example "Kyiv"
             */
            'capital' => ['nullable'],

            /**
             * The currency used in the country.
             * @var string|null $currency
             * @example "UAH"
             */
            'currency' => ['nullable'],

            /**
             * The symbol of the currency used in the country.
             * @var string|null $currency_symbol
             * @example "₴"
             */
            'currency_symbol' => ['nullable'],

            /**
             * The top-level domain of the country.
             * @var string|null $tld
             * @example ".ua"
             */
            'tld' => ['nullable'],

            /**
             * The native name of the country.
             * @var string|null $native
             * @example "Україна"
             */
            'native' => ['nullable'],

            /**
             * The region where the country is located.
             * @var string|null $region
             * @example "Europe"
             */
            'region' => ['nullable'],

            /**
             * The subregion where the country is located.
             * @var string|null $subregion
             * @example "Eastern Europe"
             */
            'subregion' => ['nullable'],

            /**
             * The timezones of the country.
             * @var string|null $timezones
             * @example "Europe/Kiev"
             */
            'timezones' => ['nullable'],

            /**
             * The translations of the country's name.
             * @var string|null $translations
             * @example {"en": "Ukraine", "uk": "Україна"}
             */
            'translations' => ['nullable'],

            /**
             * The latitude of the country's geographical center.
             * @var float|null $latitude
             * @example 48.3794
             */
            'latitude' => ['nullable'],

            /**
             * The longitude of the country's geographical center.
             * @var float|null $longitude
             * @example 31.1656
             */
            'longitude' => ['nullable'],

            /**
             * The emoji representation of the country's flag.
             * @var string|null $emoji
             * @example "🇺🇦"
             */
            'emoji' => ['nullable'],

            /**
             * The Unicode representation of the country's flag emoji.
             * @var string|null $emojiU
             * @example "U+1F1FA U+1F1E6"
             */
            'emojiU' => ['nullable'],

            /**
             * Indicates if the country has a flag.
             * @var bool|null $flag
             * @example true
             */
            'flag' => ['nullable', 'boolean'],

            /**
             * The WikiData ID of the country.
             * @var string|null $wikiDataId
             * @example "Q212"
             */
            'wikiDataId' => ['nullable'],
        ];
    }
}
