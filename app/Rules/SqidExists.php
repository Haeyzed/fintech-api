<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use App\Utils\Sqid;

class SqidExists implements ValidationRule
{
    protected $table;
    protected mixed $column;

    public function __construct($table, $column = 'id')
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $id = Sqid::decode($value);

        if (!$id || !DB::table($this->table)->where($this->column, $id)->exists()) {
            $fail("The selected {$attribute} is invalid.");
        }
    }
}
