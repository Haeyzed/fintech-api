<?php

namespace App\Providers;

use App\Rules\SqidExists;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class SqidsValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::extend('sqid_exists', function ($attribute, $value, $parameters, $validator) {
            $table = $parameters[0] ?? null;
            $column = $parameters[1] ?? 'sqid';

            if (!$table) {
                return false;
            }

            $rule = new SqidExists($table, $column);
            $fail = function($message) use ($validator, $attribute) {
                $validator->errors()->add($attribute, $message);
            };

            $rule->validate($attribute, $value, $fail);

            return !$validator->errors()->has($attribute);
        });
    }
}
