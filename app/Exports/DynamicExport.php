<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DynamicExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $modelClass;
    protected $startDate;
    protected $endDate;
    protected $columns;

    public function __construct($modelClass, $startDate, $endDate, $columns)
    {
        $this->modelClass = $modelClass;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->columns = $columns;
    }

    public function query()
    {
        return $this->modelClass::query()
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function map($row): array
    {
        return collect($this->columns)->map(function ($column) use ($row) {
            return $row->$column;
        })->toArray();
    }
}
