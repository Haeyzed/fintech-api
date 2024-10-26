<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DynamicImport implements ToCollection, WithHeadingRow
{
    protected $modelClass;
    protected mixed $updateExisting;

    public function __construct($modelClass, $updateExisting = false)
    {
        $this->modelClass = $modelClass;
        $this->updateExisting = $updateExisting;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            $model = $this->modelClass::updateOrCreate(
                ['email' => $row['email']],
                $row->toArray()
            );
        }
    }
}
